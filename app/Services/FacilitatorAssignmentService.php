<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\FacilitatorQueueAlert;

class FacilitatorAssignmentService
{
    /**
     * Assign a facilitator to a student based on LGA (preferred), then state,
     * then free-text location. If no match, marks the student as queued.
     */
    public function assign(User $student): ?User
    {
        $lga = trim((string) ($student->lga ?? ''));
        $state = trim((string) ($student->state ?? ''));
        $location = trim((string) ($student->location ?? ''));

        if ($lga === '' && $state === '' && $location === '') {
            return null;
        }

        $facilitator = $this->findByLga($lga, $state)
            ?? $this->findByState($state !== '' ? $state : $location)
            ?? $this->findByLocation($location !== '' ? $location : $state);

        if ($facilitator) {
            $student->update([
                'facilitator_id' => $facilitator->id,
                'is_in_facilitator_queue' => false,
            ]);

            return $facilitator;
        }

        $student->update(['is_in_facilitator_queue' => true]);
        $this->alertAdmins($student);

        return null;
    }

    /**
     * Re-run assignment after a student changes their state/LGA/location.
     */
    public function reassign(User $student): ?User
    {
        return $this->assign($student);
    }

    private function findByLga(string $lga, string $state): ?User
    {
        if ($lga === '') {
            return null;
        }

        $candidates = $this->activeFacilitators()->filter(function (User $facilitator) use ($lga, $state) {
            $lgas = $this->normalizeList($facilitator->covered_lgas ?? []);
            if (! $this->listContains($lgas, $lga)) {
                return false;
            }

            if ($state === '') {
                return true;
            }

            $states = $this->normalizeList($facilitator->covered_states ?? []);

            return $states === [] || $this->listContains($states, $state);
        });

        return $this->pickLeastLoaded($candidates);
    }

    private function findByState(string $state): ?User
    {
        if ($state === '') {
            return null;
        }

        $candidates = $this->activeFacilitators()->filter(function (User $facilitator) use ($state) {
            $states = $this->normalizeList($facilitator->covered_states ?? []);
            if ($this->listContains($states, $state)) {
                return true;
            }

            return $this->placeMatches((string) ($facilitator->state ?? ''), $state)
                || $this->placeMatches((string) ($facilitator->location ?? ''), $state);
        });

        return $this->pickLeastLoaded($candidates);
    }

    private function findByLocation(string $location): ?User
    {
        if ($location === '') {
            return null;
        }

        $candidates = $this->activeFacilitators()->filter(function (User $facilitator) use ($location) {
            if ($this->placeMatches((string) ($facilitator->location ?? ''), $location)
                || $this->placeMatches((string) ($facilitator->state ?? ''), $location)
                || $this->placeMatches((string) ($facilitator->lga ?? ''), $location)
            ) {
                return true;
            }

            $states = $this->normalizeList($facilitator->covered_states ?? []);
            $lgas = $this->normalizeList($facilitator->covered_lgas ?? []);

            return $this->listContains($states, $location) || $this->listContains($lgas, $location);
        });

        return $this->pickLeastLoaded($candidates);
    }

    /**
     * @return Collection<int, User>
     */
    private function activeFacilitators(): Collection
    {
        return User::query()
            ->where('role', 'facilitator')
            ->where('is_active', true)
            ->withCount('assignedLearners')
            ->get();
    }

    /**
     * @param  Collection<int, User>  $candidates
     */
    private function pickLeastLoaded(Collection $candidates): ?User
    {
        if ($candidates->isEmpty()) {
            return null;
        }

        return $candidates
            ->sortBy(fn (User $user) => (int) ($user->assigned_learners_count ?? 0))
            ->values()
            ->first();
    }

    /**
     * @param  list<mixed>  $values
     * @return list<string>
     */
    private function normalizeList(array $values): array
    {
        return array_values(array_filter(array_map(
            fn ($value) => $this->normalizePlace((string) $value),
            $values
        )));
    }

    /**
     * @param  list<string>  $haystack
     */
    private function listContains(array $haystack, string $needle): bool
    {
        $needle = $this->normalizePlace($needle);
        if ($needle === '') {
            return false;
        }

        foreach ($haystack as $item) {
            if ($item === $needle || str_contains($item, $needle) || str_contains($needle, $item)) {
                return true;
            }
        }

        return false;
    }

    private function placeMatches(string $left, string $right): bool
    {
        $a = $this->normalizePlace($left);
        $b = $this->normalizePlace($right);

        if ($a === '' || $b === '') {
            return false;
        }

        return $a === $b || str_contains($a, $b) || str_contains($b, $a);
    }

    private function normalizePlace(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        $value = preg_replace('/\s+state$/', '', $value) ?? $value;

        return trim($value);
    }

    private function alertAdmins(User $student): void
    {
        try {
            $admins = User::where('role', 'admin')->where('is_active', true)->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new FacilitatorQueueAlert($student));
            }
        } catch (\Throwable $e) {
            Log::warning('FacilitatorQueueAlert failed: '.$e->getMessage());
        }
    }
}
