<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\FacilitatorQueueAlert;

class FacilitatorAssignmentService
{
    /**
     * Assign a facilitator to a student based on LGA (preferred) then state.
     * If no match, marks the student as queued for manual admin review.
     */
    public function assign(User $student): void
    {
        $lga   = trim($student->lga ?? '');
        $state = trim($student->state ?? '');

        if (empty($lga) && empty($state)) {
            return;
        }

        $facilitator = $this->findByLga($lga, $state)
            ?? $this->findByState($state);

        if ($facilitator) {
            $student->update([
                'facilitator_id'          => $facilitator->id,
                'is_in_facilitator_queue' => false,
            ]);
            return;
        }

        $student->update(['is_in_facilitator_queue' => true]);
        $this->alertAdmins($student);
    }

    /**
     * Re-run assignment after a student changes their state/LGA.
     * Only reassigns if the facilitator actually changed or they were queued.
     */
    public function reassign(User $student): void
    {
        $this->assign($student);
    }

    // ── Private helpers ────────────────────────────────────────────────────

    private function findByLga(string $lga, string $state): ?User
    {
        if (empty($lga)) {
            return null;
        }

        return User::where('role', 'facilitator')
            ->where('is_active', true)
            ->whereJsonContains('covered_states', $state)
            ->whereJsonContains('covered_lgas', $lga)
            ->withCount('assignedLearners')
            ->orderBy('assigned_learners_count')
            ->first();
    }

    private function findByState(string $state): ?User
    {
        if (empty($state)) {
            return null;
        }

        return User::where('role', 'facilitator')
            ->where('is_active', true)
            ->whereJsonContains('covered_states', $state)
            ->withCount('assignedLearners')
            ->orderBy('assigned_learners_count')
            ->first();
    }

    private function alertAdmins(User $student): void
    {
        try {
            $admins = User::where('role', 'admin')->where('is_active', true)->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new FacilitatorQueueAlert($student));
            }
        } catch (\Throwable $e) {
            Log::warning('FacilitatorQueueAlert failed: ' . $e->getMessage());
        }
    }
}
