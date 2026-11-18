<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Apprenticeship;
use App\Models\ApprenticeshipSlot;
use App\Models\Certificate;
use App\Models\JobMatch;
use App\Services\CvService;
use App\Services\JobMatchingService;
use App\Services\NotificationService;
use App\Support\CvTemplates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Auto-generated CVs and employer job matching:
 *   learner  — view/refresh/download their CV, choose whether employers can find them, answer job offers
 *   employer — see learners matched to an opening, view their CV, select or pass on them
 */
class CareerMatchController extends Controller
{
    public function __construct(private CvService $cvs, private JobMatchingService $matching)
    {
    }

    /**
     * The CV stays locked until the learner has filled the participant form.
     * 403 with errors.missing (field keys) and errors.labels for the app.
     */
    private function cvLocked(Request $request): ?\Illuminate\Http\JsonResponse
    {
        $missing = \App\Support\ParticipantProfile::missingForCv($request->user());
        if (! $missing) {
            return null;
        }

        return response()->json([
            'success' => false,
            'code' => 'profile_incomplete',
            'message' => 'Complete your profile form to unlock your CV.',
            'errors' => ['missing' => array_keys($missing), 'labels' => array_values($missing)],
        ], 403);
    }

    // ── Learner ─────────────────────────────────────────────

    public function myCv(Request $request)
    {
        if ($locked = $this->cvLocked($request)) {
            return $locked;
        }
        $cv = $this->cvs->forUser($request->user());

        return response()->json(['success' => true, 'data' => $cv]);
    }

    public function refreshCv(Request $request)
    {
        if ($locked = $this->cvLocked($request)) {
            return $locked;
        }
        $cv = $this->cvs->build($request->user());

        return response()->json(['success' => true, 'data' => $cv, 'message' => 'Your CV has been updated']);
    }

    public function updateCvSettings(Request $request)
    {
        if ($locked = $this->cvLocked($request)) {
            return $locked;
        }
        $data = $request->validate(['open_to_work' => 'required|boolean']);
        $cv = $this->cvs->forUser($request->user());
        $cv->update($data);

        // Hidden CVs drop out of pending suggestions straight away
        if (! $cv->open_to_work) {
            JobMatch::where('user_id', $cv->user_id)->where('status', 'suggested')->delete();
        }

        return response()->json(['success' => true, 'data' => $cv]);
    }

    /** Templates for the CV template picker (country conventions). */
    public function templates()
    {
        return response()->json(['success' => true, 'data' => CvTemplates::catalogue()]);
    }

    /**
     * Save the learner's own CV details (past experience, education, languages…)
     * and template, then rebuild the CV. Sent as a whole: lists replace what was there.
     */
    public function updateCvDetails(Request $request)
    {
        if ($locked = $this->cvLocked($request)) {
            return $locked;
        }
        $ym = 'nullable|date_format:Y-m';
        $data = $request->validate([
            'template' => 'sometimes|in:' . implode(',', CvTemplates::keys()),
            'target_country' => 'nullable|string|max:100',
            'details' => 'sometimes|array',
            'details.headline' => 'nullable|string|max:150',
            'details.summary' => 'nullable|string|max:2000',
            'details.phone' => 'nullable|string|max:30',
            'details.location' => 'nullable|string|max:150',
            'details.linkedin' => 'nullable|string|max:255',
            'details.nationality' => 'nullable|string|max:100',
            'details.state_of_origin' => 'nullable|string|max:100',
            'details.lga' => 'nullable|string|max:100',
            'details.marital_status' => 'nullable|in:single,married,divorced,widowed,prefer_not_to_say',
            'details.visa_status' => 'nullable|string|max:120',
            'details.experiences' => 'nullable|array|max:20',
            'details.experiences.*.title' => 'required|string|max:150',
            'details.experiences.*.organisation' => 'nullable|string|max:150',
            'details.experiences.*.location' => 'nullable|string|max:150',
            'details.experiences.*.start' => $ym,
            'details.experiences.*.end' => $ym,
            'details.experiences.*.current' => 'nullable|boolean',
            'details.experiences.*.description' => 'nullable|string|max:2000',
            'details.education' => 'nullable|array|max:10',
            'details.education.*.qualification' => 'required|string|max:150',
            'details.education.*.institution' => 'nullable|string|max:150',
            'details.education.*.location' => 'nullable|string|max:150',
            'details.education.*.start' => $ym,
            'details.education.*.end' => $ym,
            'details.education.*.details' => 'nullable|string|max:1000',
            'details.skills' => 'nullable|array|max:30',
            'details.skills.*' => 'string|max:60',
            'details.languages' => 'nullable|array|max:10',
            'details.languages.*.language' => 'required|string|max:60',
            'details.languages.*.level' => 'nullable|string|max:40',
            'details.achievements' => 'nullable|array|max:15',
            'details.achievements.*' => 'string|max:300',
            'details.references' => 'nullable|array|max:5',
            'details.references.*.name' => 'required|string|max:120',
            'details.references.*.role' => 'nullable|string|max:120',
            'details.references.*.organisation' => 'nullable|string|max:150',
            'details.references.*.email' => 'nullable|email|max:150',
            'details.references.*.phone' => 'nullable|string|max:30',
        ]);

        $cv = $this->cvs->forUser($request->user());
        $cv->fill(array_intersect_key($data, array_flip(['template', 'target_country', 'details'])))->save();
        $cv = $this->cvs->build($request->user());

        return response()->json(['success' => true, 'data' => $cv, 'message' => 'Your CV has been updated']);
    }

    /**
     * Claude drafts CV wording from the learner's (unsaved) editor contents and
     * training record. Nothing is saved — the learner edits and saves it.
     */
    public function suggestCv(Request $request, \App\Services\CvWriterService $writer)
    {
        if ($locked = $this->cvLocked($request)) {
            return $locked;
        }
        $data = $request->validate([
            'field' => 'required|in:' . implode(',', \App\Services\CvWriterService::FIELDS),
            'template' => 'nullable|in:' . implode(',', CvTemplates::keys()),
            'target_country' => 'nullable|string|max:100',
            'details' => 'nullable|array',
            'details.experiences' => 'nullable|array|max:20',
            'details.experiences.*' => 'array',
            'index' => 'nullable|integer|min:0|max:19',
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();
        $saved = $this->cvs->forUser($user);
        $suggestion = $writer->suggest(
            $user,
            $data['field'],
            // validated() keeps only keys with rules — take the whole draft
            is_array($request->input('details')) ? $request->input('details') : ($saved->details ?? []),
            $data['template'] ?? ($saved->template ?: CvTemplates::DEFAULT),
            $data['target_country'] ?? $saved->target_country,
            $data['index'] ?? null,
            $data['notes'] ?? null,
        );

        if ($suggestion === null || $suggestion === '' || $suggestion === []) {
            return response()->json(['success' => false, 'message' => 'The writing assistant is not available right now. Please try again later.'], 503);
        }

        return response()->json(['success' => true, 'data' => ['field' => $data['field'], 'suggestion' => $suggestion]]);
    }

    public function myCvPdf(Request $request)
    {
        if ($locked = $this->cvLocked($request)) {
            return $locked;
        }
        $cv = $this->cvs->forUser($request->user());

        return $this->pdfResponse($this->cvs->pdf($cv), $cv->data['name'] ?? 'cv');
    }

    /** Offers from employers who selected the learner. */
    public function myOffers(Request $request)
    {
        $offers = JobMatch::where('user_id', $request->user()->id)
            ->whereIn('status', ['selected', 'accepted', 'declined'])
            ->with('slot.organisation:id,name,country,state,city,website')
            ->orderByRaw("CASE status WHEN 'selected' THEN 0 ELSE 1 END")
            ->latest('selected_at')
            ->get()
            ->map(fn (JobMatch $m) => [
                'id' => $m->id,
                'status' => $m->status,
                'score' => $m->score,
                'reasons' => $m->reasons,
                'selected_at' => $m->selected_at,
                'responded_at' => $m->responded_at,
                'slot' => $m->slot ? [
                    'id' => $m->slot->id,
                    'title' => $m->slot->title,
                    'description' => $m->slot->description,
                    'state' => $m->slot->state,
                    'lga' => $m->slot->lga,
                    'duration' => $m->slot->duration,
                    'organisation' => $m->slot->organisation,
                ] : null,
            ]);

        return response()->json(['success' => true, 'data' => $offers]);
    }

    /** Accept (→ placement in apprenticeship tracking; employer gets contact details) or decline an offer. */
    public function respond(Request $request, JobMatch $match)
    {
        $user = $request->user();
        if ($match->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Not your offer'], 403);
        }
        if ($match->status !== 'selected') {
            return response()->json(['success' => false, 'message' => 'This offer has already been answered'], 400);
        }
        $data = $request->validate(['response' => 'required|in:accept,decline']);
        $slot = $match->slot()->with('organisation.user')->first();

        DB::transaction(function () use ($match, $data, $user, $slot) {
            $apprenticeshipId = null;
            if ($data['response'] === 'accept') {
                $apprenticeship = Apprenticeship::firstOrNew(['apprenticeship_slot_id' => $slot->id, 'user_id' => $user->id]);
                $apprenticeship->fill([
                    'status' => 'accepted',
                    'reviewed_at' => now(),
                    'certificate_id' => $apprenticeship->certificate_id
                        ?? Certificate::where('user_id', $user->id)
                            ->when($slot->required_course_id, fn ($q) => $q->where('course_id', $slot->required_course_id))
                            ->latest('issued_date')->value('id'),
                ])->save();
                $apprenticeshipId = $apprenticeship->id;
            }
            $match->update([
                'status' => $data['response'] === 'accept' ? 'accepted' : 'declined',
                'responded_at' => now(),
                'apprenticeship_id' => $apprenticeshipId,
            ]);
        });

        if ($orgUser = $slot->organisation?->user) {
            $accepted = $data['response'] === 'accept';
            NotificationService::create(
                $orgUser,
                'job_match_response',
                $accepted ? 'Candidate accepted your offer' : 'Candidate declined your offer',
                $accepted
                    ? "{$user->name} accepted your offer for '{$slot->title}'. Their contact details are now on the candidate's card."
                    : "{$user->name} declined your offer for '{$slot->title}'.",
                'job_match',
                $match->id,
                ['slot_id' => $slot->id]
            );
        }

        return response()->json(['success' => true, 'data' => $match->fresh(), 'message' => 'Thanks, the employer has been told.']);
    }

    // ── Employer ────────────────────────────────────────────

    /** Learners matched to one of the organisation's openings, best first. */
    public function slotMatches(Request $request, ApprenticeshipSlot $slot)
    {
        if ($denied = $this->denyUnlessOwner($request, $slot)) {
            return $denied;
        }

        $matches = $this->matching->matchesFor($slot, $request->boolean('refresh'));
        $cvs = \App\Models\LearnerCv::whereIn('user_id', $matches->pluck('user_id'))->get()->keyBy('user_id');

        $data = $matches->map(function (JobMatch $m) use ($cvs) {
            $cv = $cvs->get($m->user_id)?->data ?? [];
            $withContact = $m->status === 'accepted';

            return [
                'id' => $m->id,
                'score' => $m->score,
                'reasons' => $m->reasons ?? [],
                'status' => $m->status,
                'selected_at' => $m->selected_at,
                'candidate' => [
                    'name' => $m->user?->name,
                    'avatar' => $m->user?->avatar,
                    'state' => $m->user?->state,
                    'lga' => $m->user?->lga,
                ],
                'cv' => CvService::forEmployer($cv, $withContact),
            ];
        })->values();

        return response()->json(['success' => true, 'data' => [
            'slot' => ['id' => $slot->id, 'title' => $slot->title],
            'matches' => $data,
        ]]);
    }

    /** "I want to work with this candidate": the learner is told and can accept or decline. */
    public function select(Request $request, JobMatch $match)
    {
        $slot = $match->slot;
        if ($denied = $this->denyUnlessOwner($request, $slot)) {
            return $denied;
        }
        if (! in_array($match->status, ['suggested', 'dismissed'], true)) {
            return response()->json(['success' => false, 'message' => 'You have already selected this candidate'], 400);
        }

        $match->update(['status' => 'selected', 'selected_at' => now()]);
        $learner = $match->user;
        $orgName = $slot->organisation?->name ?? 'An employer';

        NotificationService::create(
            $learner,
            'job_offer',
            'An employer wants to work with you',
            "{$orgName} selected you for '{$slot->title}'. Open Careers to accept or decline.",
            'job_match',
            $match->id,
            ['slot_id' => $slot->id]
        );
        try {
            $link = rtrim((string) config('services.frontend.url'), '/') . '/career/apprenticeships?tab=offers';
            Mail::raw(
                "Hello {$learner->name},\n\n{$orgName} reviewed your Agrisiti Academy CV and selected you for: {$slot->title}"
                . ($slot->state ? " ({$slot->state})" : '') . ".\n\nAccept or decline the offer here:\n{$link}\n\n"
                . "Your contact details are only shared with them if you accept.\n\nThe Agrisiti team",
                fn ($m) => $m->to($learner->email)->subject("Job offer from {$orgName}")
            );
        } catch (\Throwable $e) {
            Log::warning('Job offer email failed', ['match_id' => $match->id, 'error' => $e->getMessage()]);
        }

        return response()->json(['success' => true, 'data' => $match, 'message' => 'Candidate selected. They have been notified.']);
    }

    public function dismiss(Request $request, JobMatch $match)
    {
        if ($denied = $this->denyUnlessOwner($request, $match->slot)) {
            return $denied;
        }
        if ($match->status !== 'suggested') {
            return response()->json(['success' => false, 'message' => 'Only suggested candidates can be passed on'], 400);
        }
        $match->update(['status' => 'dismissed']);

        return response()->json(['success' => true, 'data' => $match]);
    }

    /** Candidate CV as PDF (contact details only once they've accepted). */
    public function matchCvPdf(Request $request, JobMatch $match)
    {
        if ($denied = $this->denyUnlessOwner($request, $match->slot)) {
            return $denied;
        }
        $cv = $this->cvs->forUser($match->user);
        $pdf = $this->cvs->renderPdf($cv->data, $match->status === 'accepted');

        return $this->pdfResponse($pdf, $cv->data['name'] ?? 'candidate');
    }

    private function denyUnlessOwner(Request $request, ?ApprenticeshipSlot $slot)
    {
        $organisation = $request->user()->organisation;
        if (! $slot || ! $organisation || $slot->organisation_id !== $organisation->id) {
            return response()->json(['success' => false, 'message' => 'You do not manage this opening'], 403);
        }
        if (! $organisation->is_approved) {
            return response()->json(['success' => false, 'message' => 'Your organisation must be approved first'], 403);
        }

        return null;
    }

    private function pdfResponse(string $bytes, string $name)
    {
        $file = 'CV-' . preg_replace('/[^A-Za-z0-9]+/', '-', trim($name)) . '.pdf';

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $file . '"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
