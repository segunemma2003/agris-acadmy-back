<?php

namespace App\Services;

use App\Models\Apprenticeship;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\LearnerCv;
use App\Models\StudentProgress;
use App\Models\TestAttempt;
use App\Models\TopicTestAttempt;
use App\Models\User;
use App\Support\ParticipantProfile as PP;
use FPDF;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Builds a learner's CV from what the platform knows: profile, completed
 * courses, quiz performance, certificates and completed placements. Rebuilt
 * whenever a course is completed or a certificate issued. The PDF copy is kept
 * on the private (local) disk and only served through authorised endpoints.
 */
class CvService
{
    private const DISK = 'local';

    public function build(User $user): LearnerCv
    {
        $data = $this->collect($user);

        $cv = LearnerCv::firstOrNew(['user_id' => $user->id]);
        $cv->data = $data;
        $cv->generated_at = now();
        if (! $cv->exists) {
            $cv->open_to_work = true;
        }

        $path = "cvs/{$user->id}.pdf";
        Storage::disk(self::DISK)->put($path, $this->renderPdf($data));
        $cv->pdf_path = $path;
        $cv->save();

        return $cv;
    }

    /** The CV, building it first if it doesn't exist yet. */
    public function forUser(User $user): LearnerCv
    {
        return LearnerCv::where('user_id', $user->id)->first() ?? $this->build($user);
    }

    /** PDF bytes (rebuilt if the stored copy is missing). */
    public function pdf(LearnerCv $cv): string
    {
        $disk = Storage::disk(self::DISK);
        if (! $cv->pdf_path || ! $disk->exists($cv->pdf_path)) {
            $cv = $this->build($cv->user);
        }

        return $disk->get($cv->pdf_path);
    }

    /** Everything the CV shows, as plain data. Contact details are included but only shared with an employer after the learner accepts an offer. */
    public function collect(User $user): array
    {
        $completed = Enrollment::where('user_id', $user->id)
            ->where(fn ($q) => $q->where('status', 'completed')->orWhere('progress_percentage', '>=', 100))
            ->with(['course:id,title,category_id,level,short_description', 'course.category:id,name', 'course.modules:id,course_id,title,is_active'])
            ->orderByDesc('completed_at')
            ->get()
            ->filter(fn ($e) => $e->course);

        $quiz = TestAttempt::query()->join('module_tests', 'module_tests.id', '=', 'test_attempts.module_test_id')
            ->where('test_attempts.user_id', $user->id)->whereNotNull('test_attempts.completed_at')
            ->get(['module_tests.course_id', 'test_attempts.percentage'])
            ->concat(TopicTestAttempt::query()->join('topic_tests', 'topic_tests.id', '=', 'topic_test_attempts.topic_test_id')
                ->where('topic_test_attempts.user_id', $user->id)->whereNotNull('topic_test_attempts.completed_at')
                ->get(['topic_tests.course_id', 'topic_test_attempts.percentage']));
        $avgByCourse = $quiz->groupBy('course_id')->map(fn ($rows) => round((float) $rows->avg('percentage')));
        $avgQuiz = $quiz->isNotEmpty() ? (int) round((float) $quiz->avg('percentage')) : null;

        $courses = $completed->map(fn (Enrollment $e) => [
            'id' => $e->course->id,
            'title' => $e->course->title,
            'category' => $e->course->category?->name,
            'level' => $e->course->level ? ucfirst($e->course->level) : null,
            'completed_at' => optional($e->completed_at)->toDateString(),
            'quiz_average' => $avgByCourse->get($e->course->id),
        ])->values()->all();

        // Skills: subjects of finished courses + their module titles
        $skills = $completed->pluck('course.category.name')
            ->merge($completed->flatMap(fn ($e) => $e->course->modules->where('is_active', true)->pluck('title')))
            ->filter()->map(fn ($s) => trim(preg_replace('/^(module|week|unit)\s*\d+\s*[:.\-–]\s*/i', '', $s)))
            ->filter()->unique(fn ($s) => strtolower($s))->take(15)->values()->all();

        $verifyBase = rtrim((string) config('services.frontend.url'), '/') . '/verify-certificate?number=';
        $certificates = Certificate::where('user_id', $user->id)->with('course:id,title')->orderByDesc('issued_date')->get()
            ->map(fn ($c) => [
                'course' => $c->course?->title,
                'number' => $c->certificate_number,
                'issued_date' => optional($c->issued_date)->toDateString(),
                'verify_url' => $c->certificate_number ? $verifyBase . urlencode($c->certificate_number) : null,
            ])->values()->all();

        $placements = Apprenticeship::where('user_id', $user->id)->whereIn('status', ['accepted', 'completed'])
            ->with('slot.organisation:id,name')->latest()->get()
            ->map(fn ($a) => [
                'organisation' => $a->slot?->organisation?->name,
                'role' => $a->completion_role ?: $a->slot?->title,
                'status' => $a->status,
                'start' => optional($a->completion_start_date)->toDateString(),
                'end' => optional($a->completion_end_date)->toDateString(),
                'skills' => $a->completion_key_skills ?? [],
            ])->values()->all();

        $label = fn (array $map, $key) => $key !== null && $key !== '' ? ($map[$key] ?? null) : null;
        $track = $label(PP::VALUE_CHAIN_TRACK, $user->value_chain_track);
        if ($user->value_chain_track === 'other' && $user->value_chain_track_other) {
            $track = $user->value_chain_track_other;
        }
        $since = $user->work_started_year
            ? trim(($user->work_started_month ? Carbon::create()->month((int) $user->work_started_month)->format('M') . ' ' : '') . $user->work_started_year)
            : null;

        $place = trim(implode(', ', array_filter([$user->lga, $user->state])));
        $headlineSubject = $track ?? ($courses[0]['category'] ?? 'Agriculture');
        $headline = trim("{$headlineSubject} · Agrisiti Academy graduate" . ($place ? " · {$place}" : ''));

        $nCourses = count($courses);
        $summary = trim(
            ($nCourses ? "Completed {$nCourses} Agrisiti Academy " . ($nCourses === 1 ? 'course' : 'courses') : 'Agrisiti Academy learner')
            . ($nCourses ? ' in ' . implode(', ', array_slice(array_unique(array_filter(array_column($courses, 'category'))), 0, 3)) : '')
            . ($avgQuiz !== null ? " with an average assessment score of {$avgQuiz}%" : '')
            . '.'
            . ($label(PP::TRAINING_OBJECTIVE, $user->training_objective) ? ' Goal: ' . lcfirst($label(PP::TRAINING_OBJECTIVE, $user->training_objective)) . '.' : '')
        );

        return [
            'name' => $user->name,
            'headline' => $headline,
            'summary' => $summary,
            'location' => ['state' => $user->state, 'lga' => $user->lga, 'country' => 'Nigeria'],
            'contact' => ['email' => $user->email, 'phone' => $user->phone],
            'gender' => $user->gender,
            'education' => $label(PP::EDUCATION, $user->education_level),
            'work' => array_filter([
                'status' => $label(PP::MAIN_WORK + PP::MAIN_WORK_LEGACY, $user->main_work),
                'job_title' => $user->job_title,
                'employment_type' => $label(PP::EMPLOYMENT_TYPE, $user->employment_type),
                'since' => $since,
                'organisation' => $user->work_organisation,
                'sector' => $label(PP::SECTOR, $user->work_sector),
            ]),
            'interests' => array_filter([
                'value_chain' => $track,
                'commodity' => $label(PP::COMMODITY, $user->main_commodity),
                'objective' => $label(PP::TRAINING_OBJECTIVE, $user->training_objective),
            ]),
            'skills' => $skills,
            'courses' => $courses,
            'certificates' => $certificates,
            'placements' => $placements,
            'performance' => [
                'courses_completed' => $nCourses,
                'average_quiz_score' => $avgQuiz,
                'lessons_completed' => StudentProgress::where('user_id', $user->id)->where('is_completed', true)->count(),
                'certificates' => count($certificates),
            ],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /** The CV as seen by an employer before the learner accepts: no email/phone. */
    public static function forEmployer(array $data, bool $withContact = false): array
    {
        if (! $withContact) {
            unset($data['contact']);
        }
        unset($data['gender']);

        return $data;
    }

    /** A clean one/two-page PDF (FPDF core fonts are Latin-1, so text is transliterated). */
    public function renderPdf(array $d, bool $withContact = true): string
    {
        $t = fn (?string $s) => $s === null ? '' : (string) @iconv('UTF-8', 'windows-1252//TRANSLIT', str_replace(['₦', '·', '–', '—'], ['NGN ', '-', '-', '-'], $s));

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetMargins(18, 16, 18);
        $pdf->SetAutoPageBreak(true, 16);
        $pdf->AddPage();
        $w = 174;

        // Header
        $pdf->SetFillColor(27, 127, 110);
        $pdf->Rect(0, 0, 210, 34, 'F');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY(18, 9);
        $pdf->SetFont('Helvetica', 'B', 20);
        $pdf->Cell($w, 9, $t($d['name'] ?? ''), 0, 2);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell($w, 6, $t($d['headline'] ?? ''), 0, 2);
        if ($withContact && ! empty($d['contact'])) {
            $pdf->Cell($w, 5, $t(implode('  |  ', array_filter([$d['contact']['email'] ?? null, $d['contact']['phone'] ?? null]))), 0, 2);
        }
        $pdf->SetTextColor(30, 30, 30);
        $pdf->SetY(40);

        $section = function (string $title) use ($pdf, $w, $t) {
            $pdf->Ln(2);
            $pdf->SetFont('Helvetica', 'B', 11);
            $pdf->SetTextColor(27, 127, 110);
            $pdf->Cell($w, 6, $t(strtoupper($title)), 0, 1);
            $pdf->SetDrawColor(92, 188, 171);
            $pdf->Line(18, $pdf->GetY(), 192, $pdf->GetY());
            $pdf->Ln(2);
            $pdf->SetTextColor(30, 30, 30);
        };
        $para = function (string $text, int $size = 10, string $style = '') use ($pdf, $w, $t) {
            $pdf->SetFont('Helvetica', $style, $size);
            $pdf->MultiCell($w, 5, $t($text));
        };

        $section('Profile');
        $para($d['summary'] ?? '');

        $perf = $d['performance'] ?? [];
        $section('At a glance');
        $para(implode('   |   ', array_filter([
            ($perf['courses_completed'] ?? 0) . ' courses completed',
            isset($perf['average_quiz_score']) ? "Average assessment score {$perf['average_quiz_score']}%" : null,
            ($perf['lessons_completed'] ?? 0) . ' lessons completed',
            ($perf['certificates'] ?? 0) . ' certificates',
        ])));

        if (! empty($d['skills'])) {
            $section('Skills');
            $para(implode('  •  ', $d['skills']));
        }

        if (! empty($d['courses'])) {
            $section('Training (Agrisiti Academy)');
            foreach ($d['courses'] as $c) {
                $pdf->SetFont('Helvetica', 'B', 10);
                $pdf->Cell(130, 5, $t($c['title']), 0, 0);
                $pdf->SetFont('Helvetica', '', 9);
                $pdf->Cell(44, 5, $t($c['completed_at'] ? 'Completed ' . Carbon::parse($c['completed_at'])->format('M Y') : ''), 0, 1, 'R');
                $para(implode(' · ', array_filter([$c['category'], $c['level'], $c['quiz_average'] !== null ? "Assessment average {$c['quiz_average']}%" : null])), 9);
                $pdf->Ln(1);
            }
        }

        if (! empty($d['placements'])) {
            $section('Placements');
            foreach ($d['placements'] as $p) {
                $pdf->SetFont('Helvetica', 'B', 10);
                $pdf->Cell($w, 5, $t(trim(($p['role'] ?? 'Placement') . ' - ' . ($p['organisation'] ?? ''), ' -')), 0, 1);
                $para(implode(' · ', array_filter([
                    $p['start'] ? Carbon::parse($p['start'])->format('M Y') . ' to ' . ($p['end'] ? Carbon::parse($p['end'])->format('M Y') : 'present') : null,
                    $p['skills'] ? implode(', ', $p['skills']) : null,
                ])), 9);
            }
        }

        $work = $d['work'] ?? [];
        if ($work) {
            $section('Work');
            $para(implode(' · ', array_filter([$work['job_title'] ?? null, $work['organisation'] ?? null, $work['employment_type'] ?? null, isset($work['since']) ? "since {$work['since']}" : null])) ?: ($work['status'] ?? ''));
            if (! empty($work['job_title']) && ! empty($work['status'])) {
                $para($work['status'], 9);
            }
        }

        if (! empty($d['education'])) {
            $section('Education');
            $para($d['education']);
        }

        $interests = $d['interests'] ?? [];
        if ($interests) {
            $section('Interests and goals');
            $para(implode(' · ', $interests));
        }

        if (! empty($d['certificates'])) {
            $section('Certificates');
            foreach ($d['certificates'] as $c) {
                $para(trim(($c['course'] ?? 'Certificate') . ' - No. ' . ($c['number'] ?? '') . ($c['issued_date'] ? ', issued ' . Carbon::parse($c['issued_date'])->format('j M Y') : '')), 9);
                if ($c['verify_url']) {
                    $pdf->SetTextColor(27, 127, 110);
                    $para('Verify: ' . $c['verify_url'], 8);
                    $pdf->SetTextColor(30, 30, 30);
                }
            }
        }

        $pdf->Ln(4);
        $pdf->SetFont('Helvetica', 'I', 8);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->MultiCell($w, 4, $t('Generated by Agrisiti Academy on ' . Carbon::parse($d['generated_at'] ?? now())->format('j M Y') . ' from verified course records.'));

        return $pdf->Output('S');
    }
}
