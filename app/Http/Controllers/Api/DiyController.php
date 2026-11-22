<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseDiyContent;
use App\Models\CourseDiySubmission;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Learners doing a DIY practical: tick off steps, record observations (once or
 * several times, with photos) and hand it in for the tutor to review.
 */
class DiyController extends Controller
{
    private function guard(Request $request, CourseDiyContent $diy)
    {
        $user = $request->user();
        if (! $diy->is_active || ! Enrollment::where('user_id', $user->id)->where('course_id', $diy->course_id)->exists()) {
            abort(response()->json(['message' => 'You must be enrolled in this course to do this DIY task.'], 403));
        }

        return CourseDiySubmission::firstOrCreate(
            ['course_diy_content_id' => $diy->id, 'user_id' => $user->id],
            ['course_id' => $diy->course_id, 'status' => 'in_progress', 'steps_done' => [], 'entries' => []],
        );
    }

    /** Save which steps are done. */
    public function steps(Request $request, CourseDiyContent $diy)
    {
        $data = $request->validate(['done' => 'present|array', 'done.*' => 'integer|min:0|max:200']);
        $sub = $this->guard($request, $diy);
        $sub->steps_done = array_values(array_unique(array_map('intval', $data['done'])));
        $sub->save();

        return response()->json(['data' => $sub->toLearnerArray()]);
    }

    /** Save one observation entry (n = 1 .. entries_required). Photos come as files named photo_<key>. */
    public function entry(Request $request, CourseDiyContent $diy)
    {
        $max = max(1, (int) $diy->entries_required);
        $request->validate([
            'n' => "required|integer|min:1|max:{$max}",
            'values' => 'nullable|array',
        ]);
        $sub = $this->guard($request, $diy);
        if ($sub->status === 'approved') {
            return response()->json(['message' => 'This task has already been approved.'], 422);
        }
        $fields = collect($diy->fields())->keyBy('key');
        $n = (int) $request->input('n');
        $entries = collect($sub->entries ?? [])->keyBy(fn ($e) => (int) ($e['n'] ?? 0));
        $old = $entries->get($n)['values'] ?? [];
        $values = [];
        $errors = [];
        foreach ($fields as $key => $f) {
            if ($f['type'] === 'photo') {
                $file = $request->file("photo_{$key}");
                if ($file) {
                    if (! $file->isValid() || ! str_starts_with((string) $file->getMimeType(), 'image/') || $file->getSize() > 8 * 1024 * 1024) {
                        $errors["photo_{$key}"] = ['Please upload a photo (JPG or PNG, up to 8 MB).'];

                        continue;
                    }
                    $values[$key] = $file->store("diy/{$diy->id}/{$request->user()->id}", 'public');
                } elseif (! empty($old[$key])) {
                    $values[$key] = $old[$key];
                }
            } else {
                $raw = $request->input("values.{$key}");
                if ($f['type'] === 'number') {
                    $raw = ($raw === null || $raw === '') ? null : (is_numeric($raw) ? $raw + 0 : null);
                } elseif ($f['type'] === 'check') {
                    $raw = filter_var($raw, FILTER_VALIDATE_BOOLEAN);
                } elseif ($f['type'] === 'choice') {
                    $raw = in_array($raw, $f['options'], true) ? $raw : null;
                } else {
                    $raw = $raw === null ? null : mb_substr(trim((string) $raw), 0, 2000);
                }
                if ($raw !== null && $raw !== '') {
                    $values[$key] = $raw;
                }
            }
            if ($f['required'] && ! array_key_exists($key, $values)) {
                $errors["values.{$key}"] = ["Please fill in “{$f['label']}”."];
            }
        }
        if ($errors) {
            return response()->json(['message' => 'Some answers are missing.', 'errors' => $errors], 422);
        }
        $entries->put($n, ['n' => $n, 'recorded_at' => now()->toIso8601String(), 'values' => $values]);
        $sub->entries = $entries->sortKeys()->values()->all();
        if ($sub->status === 'needs_work') {
            $sub->status = 'in_progress';
        }
        $sub->save();

        return response()->json(['data' => $sub->toLearnerArray()]);
    }

    /** Hand the work in for review (or mark it complete when no review is needed). */
    public function submit(Request $request, CourseDiyContent $diy)
    {
        $sub = $this->guard($request, $diy);
        $have = count($sub->entries ?? []);
        $need = max(1, (int) $diy->entries_required);
        if ($have < $need) {
            return response()->json(['message' => "Record all {$need} ".strtolower($diy->entry_label ?: 'entries')." before handing in (you have {$have})."], 422);
        }
        $sub->status = $diy->requires_review ? 'submitted' : 'approved';
        $sub->submitted_at = now();
        $sub->save();

        return response()->json(['data' => $sub->toLearnerArray(), 'message' => $diy->requires_review ? 'Handed in — your tutor will review it.' : 'Well done — task complete.']);
    }

    /** Shape of a DIY task for the Academy, with this learner's work. */
    public static function forLearner(CourseDiyContent $diy, ?CourseDiySubmission $sub): array
    {
        return array_merge($diy->only(['id', 'title', 'description', 'instructions', 'materials_needed', 'video_url', 'estimated_time_minutes', 'difficulty_level', 'entries_required', 'entry_label', 'requires_review']), [
            'image' => $diy->image ? (str_starts_with($diy->image, 'http') ? $diy->image : Storage::disk('public')->url($diy->image)) : null,
            'steps' => array_map(fn ($st) => [
                'title' => $st['title'] ?? '',
                'body' => $st['body'] ?? '',
                'image' => ! empty($st['image']) ? (str_starts_with($st['image'], 'http') ? $st['image'] : Storage::disk('public')->url($st['image'])) : null,
            ], array_values($diy->steps ?? [])),
            'observation_fields' => $diy->fields(),
            'my_submission' => $sub?->toLearnerArray(),
        ]);
    }
}
