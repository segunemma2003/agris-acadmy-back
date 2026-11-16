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
use App\Support\CvTemplates;
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
        $cv = LearnerCv::firstOrNew(['user_id' => $user->id]);
        $template = $cv->template ?: CvTemplates::DEFAULT;
        $data = $this->collect($user, $cv->details ?? [], $template);

        $cv->template = $template;
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
    public function collect(User $user, array $details = [], string $template = CvTemplates::DEFAULT): array
    {
        $tpl = CvTemplates::get($template);
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

        // ── Learner-entered details, merged with what the platform knows ──
        $clean = fn ($v) => is_string($v) ? trim($v) : $v;
        $experience = collect($details['experiences'] ?? [])
            ->map(fn ($e) => array_map($clean, array_intersect_key((array) $e, array_flip(['title', 'organisation', 'location', 'start', 'end', 'current', 'description']))))
            ->filter(fn ($e) => ! empty($e['title']) || ! empty($e['organisation']))
            ->sortByDesc(fn ($e) => (! empty($e['current']) ? '9999' : ($e['end'] ?? '')) . ($e['start'] ?? ''))
            ->values()->all();
        $educationHistory = collect($details['education'] ?? [])
            ->map(fn ($e) => array_map($clean, array_intersect_key((array) $e, array_flip(['qualification', 'institution', 'location', 'start', 'end', 'details']))))
            ->filter(fn ($e) => ! empty($e['qualification']) || ! empty($e['institution']))
            ->sortByDesc(fn ($e) => $e['end'] ?? $e['start'] ?? '')
            ->values()->all();
        $ownSkills = collect($details['skills'] ?? [])->map($clean)->filter()->values();
        $skills = $ownSkills->merge($skills)->unique(fn ($x) => strtolower($x))->take(25)->values()->all();

        $personal = [];
        foreach ($tpl['personal'] as $field) {
            $value = match ($field) {
                'date_of_birth' => $user->date_of_birth?->format('j F Y'),
                'gender' => $user->gender ? ucfirst($user->gender) : null,
                'nationality' => $details['nationality'] ?? ($user->nationality === 'nigerian' ? 'Nigerian' : null),
                'state_of_origin' => $details['state_of_origin'] ?? $user->state,
                'lga' => $details['lga'] ?? $user->lga,
                'marital_status' => $details['marital_status'] ?? null,
                'visa_status' => $details['visa_status'] ?? null,
                default => null,
            };
            if ($value) {
                $personal[$field] = $value;
            }
        }

        $references = match ($tpl['references']) {
            'list' => collect($details['references'] ?? [])
                ->map(fn ($x) => array_map($clean, array_intersect_key((array) $x, array_flip(['name', 'role', 'organisation', 'email', 'phone']))))
                ->filter(fn ($x) => ! empty($x['name']))->values()->all() ?: 'on_request',
            'on_request' => 'on_request',
            default => null,
        };

        return [
            'template' => array_key_exists($template, CvTemplates::ALL) ? $template : CvTemplates::DEFAULT,
            'template_label' => $tpl['label'],
            'summary_title' => $tpl['summary_title'],
            'section_order' => $tpl['order'],
            'name' => $user->name,
            'headline' => ! empty($details['headline']) ? trim($details['headline']) : $headline,
            'summary' => ! empty($details['summary']) ? trim($details['summary']) : $summary,
            'personal' => $personal,
            'photo' => $tpl['photo'] && $user->avatar ? $user->avatar : null,
            'experience' => $experience,
            'education_history' => $educationHistory,
            'languages' => collect($details['languages'] ?? [])
                ->map(fn ($l) => array_map($clean, array_intersect_key((array) $l, array_flip(['language', 'level']))))
                ->filter(fn ($l) => ! empty($l['language']))->values()->all(),
            'achievements' => collect($details['achievements'] ?? [])->map($clean)->filter()->values()->all(),
            'references' => $references,
            'location' => ['state' => $user->state, 'lga' => $user->lga, 'country' => 'Nigeria'],
            'contact' => array_filter([
                'email' => $user->email,
                'phone' => ! empty($details['phone']) ? trim($details['phone']) : $user->phone,
                'location' => ! empty($details['location']) ? trim($details['location']) : null,
                'linkedin' => ! empty($details['linkedin']) ? trim($details['linkedin']) : null,
            ]),
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
            // Until the learner accepts: no contact details, personal details, photo or referees
            unset($data['contact'], $data['personal'], $data['photo']);
            if (is_array($data['references'] ?? null)) {
                $data['references'] = 'on_request';
            }
        }
        unset($data['gender']);

        return $data;
    }

    /**
     * The CV as PDF, laid out by its template (sections, order, personal
     * details, references, colour). FPDF core fonts are Latin-1, so text is transliterated.
     */
    public function renderPdf(array $d, bool $withContact = true): string
    {
        $tpl = CvTemplates::get($d['template'] ?? null);
        [$cr, $cg, $cb] = $tpl['color'];
        $t = fn ($s) => $s === null ? '' : (string) @iconv('UTF-8', 'windows-1252//TRANSLIT', str_replace(['₦', '·', '–', '—', '’', '“', '”'], ['NGN ', '-', '-', '-', "'", '"', '"'], (string) $s));
        $month = fn (?string $ym) => $ym ? Carbon::parse(strlen($ym) === 7 ? $ym . '-01' : $ym)->format('M Y') : null;

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetMargins(18, 16, 18);
        $pdf->SetAutoPageBreak(true, 16);
        $pdf->AddPage();
        $w = 174;

        // ── Header ──
        $pdf->SetFillColor($cr, $cg, $cb);
        $pdf->Rect(0, 0, 210, 36, 'F');
        $textX = 18;
        $photo = $withContact && ! empty($d['photo']) ? $this->photoFile($d['photo']) : null;
        if ($photo) {
            try {
                $pdf->Image($photo, 18, 6, 24, 24);
                $textX = 47;
            } catch (\Throwable) {
                // unreadable image: carry on without it
            }
        }
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY($textX, 8);
        $pdf->SetFont('Helvetica', 'B', 20);
        $pdf->Cell($w, 9, $t($d['name'] ?? ''), 0, 2);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell($w, 6, $t($d['headline'] ?? ''), 0, 2);
        if ($withContact && ! empty($d['contact'])) {
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->Cell($w, 5, $t(implode('  |  ', array_filter([
                $d['contact']['email'] ?? null, $d['contact']['phone'] ?? null, $d['contact']['location'] ?? null, $d['contact']['linkedin'] ?? null,
            ]))), 0, 2);
        }
        if ($photo && str_starts_with($photo, sys_get_temp_dir())) {
            @unlink($photo);
        }
        $pdf->SetTextColor(30, 30, 30);
        $pdf->SetY(42);

        $section = function (string $title) use ($pdf, $w, $t, $cr, $cg, $cb) {
            $pdf->Ln(2);
            $pdf->SetFont('Helvetica', 'B', 11);
            $pdf->SetTextColor($cr, $cg, $cb);
            $pdf->Cell($w, 6, $t(strtoupper($title)), 0, 1);
            $pdf->SetDrawColor($cr, $cg, $cb);
            $pdf->Line(18, $pdf->GetY(), 192, $pdf->GetY());
            $pdf->Ln(2);
            $pdf->SetTextColor(30, 30, 30);
        };
        $para = function (string $text, int $size = 10, string $style = '') use ($pdf, $w, $t) {
            $pdf->SetFont('Helvetica', $style, $size);
            $pdf->MultiCell($w, 5, $t($text));
        };
        $entry = function (string $left, ?string $right, ?string $sub = null, ?string $body = null) use ($pdf, $t, $para) {
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->Cell(128, 5, $t($left), 0, 0);
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->Cell(46, 5, $t($right ?? ''), 0, 1, 'R');
            if ($sub) {
                $para($sub, 9, 'I');
            }
            if ($body) {
                $para($body, 9);
            }
            $pdf->Ln(1.5);
        };

        // Personal details (only the ones this template uses)
        if ($withContact && ! empty($d['personal'])) {
            $labels = ['date_of_birth' => 'Date of birth', 'gender' => 'Sex', 'nationality' => 'Nationality', 'state_of_origin' => 'State of origin',
                'lga' => 'LGA', 'marital_status' => 'Marital status', 'visa_status' => 'Visa status'];
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->MultiCell($w, 5, $t(implode('   |   ', array_map(fn ($k, $v) => ($labels[$k] ?? $k) . ': ' . $v, array_keys($d['personal']), $d['personal']))));
            $pdf->Ln(1);
        }

        $perf = $d['performance'] ?? [];
        foreach ($d['section_order'] ?? $tpl['order'] as $key) {
            switch ($key) {
                case 'summary':
                    if (! empty($d['summary'])) {
                        $section($d['summary_title'] ?? $tpl['summary_title']);
                        $para($d['summary']);
                    }
                    break;

                case 'experience':
                    $placements = $d['placements'] ?? [];
                    if (! empty($d['experience']) || $placements) {
                        $section('Work experience');
                        foreach ($d['experience'] ?? [] as $e) {
                            $dates = trim(($month($e['start'] ?? null) ?? '') . ' - ' . (! empty($e['current']) ? 'Present' : ($month($e['end'] ?? null) ?? '')), ' -');
                            $entry(trim(($e['title'] ?? '') . (! empty($e['organisation']) ? ', ' . $e['organisation'] : ''), ', '),
                                $dates, $e['location'] ?? null, $e['description'] ?? null);
                        }
                        foreach ($placements as $p) {
                            $dates = $p['start'] ? trim(Carbon::parse($p['start'])->format('M Y') . ' - ' . ($p['end'] ? Carbon::parse($p['end'])->format('M Y') : 'Present')) : null;
                            $entry(trim(($p['role'] ?? 'Placement') . ', ' . ($p['organisation'] ?? ''), ', '), $dates,
                                'Placement through Agrisiti Academy', $p['skills'] ? 'Skills: ' . implode(', ', $p['skills']) : null);
                        }
                    }
                    break;

                case 'training':
                    if (! empty($d['courses'])) {
                        $section('Training (Agrisiti Academy)');
                        $para(implode('   |   ', array_filter([
                            ($perf['courses_completed'] ?? 0) . (($perf['courses_completed'] ?? 0) === 1 ? ' course completed' : ' courses completed'),
                            isset($perf['average_quiz_score']) ? "average assessment score {$perf['average_quiz_score']}%" : null,
                            ($perf['lessons_completed'] ?? 0) . ' lessons',
                        ])), 9, 'I');
                        $pdf->Ln(1);
                        foreach ($d['courses'] as $c) {
                            $entry($c['title'], $c['completed_at'] ? 'Completed ' . Carbon::parse($c['completed_at'])->format('M Y') : null,
                                implode(' · ', array_filter([$c['category'], $c['level'], $c['quiz_average'] !== null ? "Assessment average {$c['quiz_average']}%" : null])));
                        }
                    }
                    break;

                case 'education':
                    if (! empty($d['education_history']) || ! empty($d['education'])) {
                        $section('Education');
                        foreach ($d['education_history'] ?? [] as $e) {
                            $dates = trim(($month($e['start'] ?? null) ?? '') . ' - ' . ($month($e['end'] ?? null) ?? ''), ' -');
                            $entry(trim(($e['qualification'] ?? '') . (! empty($e['institution']) ? ', ' . $e['institution'] : ''), ', '),
                                $dates, $e['location'] ?? null, $e['details'] ?? null);
                        }
                        if (empty($d['education_history']) && ! empty($d['education'])) {
                            $para('Highest level: ' . $d['education']);
                        }
                    }
                    break;

                case 'skills':
                    if (! empty($d['skills'])) {
                        $section('Skills');
                        $para(implode('  •  ', $d['skills']));
                    }
                    break;

                case 'languages':
                    if (! empty($d['languages'])) {
                        $section('Languages');
                        $para(implode('   |   ', array_map(fn ($l) => trim($l['language'] . (! empty($l['level']) ? " ({$l['level']})" : '')), $d['languages'])));
                    }
                    break;

                case 'achievements':
                    if (! empty($d['achievements'])) {
                        $section('Achievements');
                        foreach ($d['achievements'] as $a) {
                            $para('•  ' . $a, 9);
                        }
                    }
                    break;

                case 'certificates':
                    if (! empty($d['certificates'])) {
                        $section('Certificates');
                        foreach ($d['certificates'] as $c) {
                            $para(trim(($c['course'] ?? 'Certificate') . ' - No. ' . ($c['number'] ?? '') . ($c['issued_date'] ? ', issued ' . Carbon::parse($c['issued_date'])->format('j M Y') : '')), 9);
                            if ($c['verify_url']) {
                                $pdf->SetTextColor($cr, $cg, $cb);
                                $para('Verify: ' . $c['verify_url'], 8);
                                $pdf->SetTextColor(30, 30, 30);
                            }
                        }
                    }
                    break;

                case 'interests':
                    if (! empty($d['interests'])) {
                        $section('Interests and goals');
                        $para(implode(' · ', (array) $d['interests']));
                    }
                    break;

                case 'references':
                    if (($d['references'] ?? null) === null) {
                        break;
                    }
                    $section('References');
                    if (is_array($d['references']) && $withContact) {
                        foreach ($d['references'] as $ref) {
                            $entry($ref['name'], null, trim(($ref['role'] ?? '') . (! empty($ref['organisation']) ? ', ' . $ref['organisation'] : ''), ', ') ?: null,
                                implode('  |  ', array_filter([$ref['email'] ?? null, $ref['phone'] ?? null])) ?: null);
                        }
                    } else {
                        $para('Available on request.');
                    }
                    break;
            }
        }

        $pdf->Ln(4);
        $pdf->SetFont('Helvetica', 'I', 8);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->MultiCell($w, 4, $t('Generated by Agrisiti Academy on ' . Carbon::parse($d['generated_at'] ?? now())->format('j M Y') . '. Training records are verified by Agrisiti Academy.'));

        return $pdf->Output('S');
    }

    /** Local temp copy of the profile photo (JPEG/PNG only, as FPDF needs), or null. */
    private function photoFile(string $url): ?string
    {
        try {
            $path = parse_url($url, PHP_URL_PATH) ?? '';
            if (str_contains($path, '/storage/')) {
                $local = storage_path('app/public/' . ltrim(substr($path, strpos($path, '/storage/') + 9), '/'));
                $type = is_file($local) ? (@getimagesize($local)[2] ?? null) : null;
                if (in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
                    return $local;
                }
            }
            $bytes = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 4]]));
            if (! $bytes || strlen($bytes) > 3_000_000) {
                return null;
            }
            $info = @getimagesizefromstring($bytes);
            $ext = match ($info[2] ?? null) { IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', default => null };
            if (! $ext) {
                return null;
            }
            $tmp = tempnam(sys_get_temp_dir(), 'cvphoto') . '.' . $ext;
            file_put_contents($tmp, $bytes);

            return $tmp;
        } catch (\Throwable) {
            return null;
        }
    }
}
