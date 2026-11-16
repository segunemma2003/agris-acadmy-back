<?php

namespace App\Services;

use App\Models\ApprenticeshipSlot;
use App\Models\Enrollment;
use App\Models\JobMatch;
use App\Models\LearnerCv;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Matches an employer's opening with learners who have a CV and are open to
 * work. Each candidate gets a 0–100 score with plain-language reasons:
 *
 *   Subject fit     35  job and learner share agri subjects (poultry, fish, crops…)
 *   Skills overlap  15  shared words between the job and the learner's courses/skills
 *   Required course 10  finished the course the employer asked for (if set, it's a must)
 *   Performance     15  average assessment score
 *   Experience      10  courses completed, placements done
 *   Location        10  same state (7) and LGA (+3)
 *   Job-seeking      5  looking for work / wants employment
 *
 * Matches the employer already acted on (selected, dismissed…) keep their status
 * when scores are refreshed.
 */
class JobMatchingService
{
    /** Refresh a slot's matches if older than this. */
    public const STALE_MINUTES = 60;

    public const MIN_SCORE = 20;

    private const TOPICS = [
        'poultry' => ['poultry', 'chicken', 'broiler', 'layer', 'hatchery', 'egg', 'birds', 'turkey'],
        'livestock' => ['livestock', 'goat', 'sheep', 'cattle', 'pig', 'piggery', 'dairy', 'ruminant', 'animal', 'feedlot', 'veterinary'],
        'fish' => ['fish', 'aquaculture', 'catfish', 'tilapia', 'pond', 'fingerling', 'hatchery'],
        'crops' => ['crop', 'vegetable', 'maize', 'rice', 'cassava', 'tomato', 'pepper', 'soil', 'horticulture', 'agronomy', 'seed', 'yam', 'sorghum', 'soybean', 'groundnut', 'planting', 'harvest'],
        'greenhouse' => ['hydroponic', 'greenhouse', 'urban', 'vertical', 'aquaponic', 'soilless', 'nursery'],
        'processing' => ['processing', 'packaging', 'milling', 'drying', 'storage', 'value addition', 'agro-processing', 'factory'],
        'business' => ['business', 'market', 'marketing', 'sales', 'finance', 'record', 'bookkeeping', 'entrepreneur', 'trade', 'export', 'procurement'],
        'logistics' => ['logistics', 'transport', 'supply chain', 'warehouse', 'distribution', 'driver', 'cold chain'],
        'tech' => ['tech', 'drone', 'iot', 'sensor', 'data', 'digital', 'precision', 'irrigation', 'machinery', 'tractor', 'mechanisation', 'equipment'],
    ];

    private const STOPWORDS = [
        'with', 'that', 'this', 'from', 'your', 'have', 'will', 'into', 'about', 'their', 'they', 'them', 'were', 'what',
        'when', 'where', 'which', 'while', 'work', 'working', 'role', 'jobs', 'must', 'able', 'good', 'basic', 'course',
        'module', 'introduction', 'intro', 'skills', 'other', 'more', 'also', 'such', 'than', 'then', 'each', 'very',
        'over', 'under', 'between', 'within', 'apply', 'applicants', 'candidate', 'candidates', 'experience', 'agrisiti',
        'academy', 'learn', 'learning', 'training', 'graduate', 'month', 'months', 'week', 'weeks', 'year', 'years',
    ];

    public function __construct(private CvService $cvs)
    {
    }

    /** Ranked matches for a slot, recomputed when stale. */
    public function matchesFor(ApprenticeshipSlot $slot, bool $force = false): Collection
    {
        $latest = JobMatch::where('apprenticeship_slot_id', $slot->id)->max('updated_at');
        if ($force || ! $latest || now()->diffInMinutes($latest) >= self::STALE_MINUTES || $slot->updated_at > $latest) {
            $this->refresh($slot);
        }

        return JobMatch::where('apprenticeship_slot_id', $slot->id)
            ->with('user:id,name,avatar,state,lga')
            ->orderByRaw("CASE status WHEN 'accepted' THEN 0 WHEN 'selected' THEN 1 WHEN 'suggested' THEN 2 WHEN 'declined' THEN 3 ELSE 4 END")
            ->orderByDesc('score')
            ->get();
    }

    /** Score every open-to-work learner against the slot and store the good fits. */
    public function refresh(ApprenticeshipSlot $slot): int
    {
        $slot->loadMissing('requiredCourse:id,title');
        $job = $this->jobProfile($slot);

        $cvs = LearnerCv::where('open_to_work', true)
            ->whereHas('user', fn ($q) => $q->where('role', 'student')->where('is_active', true))
            ->with('user')
            ->get();

        $requiredDone = $slot->required_course_id
            ? Enrollment::where('course_id', $slot->required_course_id)
                ->where(fn ($q) => $q->where('status', 'completed')->orWhere('progress_percentage', '>=', 100))
                ->pluck('user_id')->flip()
            : null;

        $existing = JobMatch::where('apprenticeship_slot_id', $slot->id)->get()->keyBy('user_id');
        $kept = [];

        foreach ($cvs as $cv) {
            if ($requiredDone && ! $requiredDone->has($cv->user_id)) {
                continue; // must have finished the required course
            }
            [$score, $reasons] = $this->score($job, $cv, (bool) $requiredDone);
            $match = $existing->get($cv->user_id);

            if ($score < self::MIN_SCORE && (! $match || $match->status === 'suggested')) {
                continue;
            }
            $kept[] = $cv->user_id;

            if ($match) {
                $match->forceFill(['score' => $score, 'reasons' => $reasons])->touch(); // saves + bumps updated_at
            } else {
                JobMatch::create([
                    'apprenticeship_slot_id' => $slot->id, 'user_id' => $cv->user_id,
                    'score' => $score, 'reasons' => $reasons, 'status' => 'suggested',
                ]);
            }
        }

        // Suggestions that no longer fit (CV hidden, score dropped) go away; acted-on rows stay.
        JobMatch::where('apprenticeship_slot_id', $slot->id)->where('status', 'suggested')
            ->whereNotIn('user_id', $kept ?: [0])->delete();

        return count($kept);
    }

    /**
     * @return array{0: int, 1: list<string>}
     */
    public function score(array $job, LearnerCv $cv, bool $requiredCourseSet = false): array
    {
        $d = $cv->data ?? [];
        $user = $cv->user;
        $reasons = [];
        $score = 0.0;

        $learnerText = strtolower(implode(' ', array_merge(
            array_column($d['courses'] ?? [], 'title'),
            array_column($d['courses'] ?? [], 'category'),
            $d['skills'] ?? [],
            array_values($d['interests'] ?? []),
            [$d['work']['job_title'] ?? '', $d['work']['sector'] ?? ''],
            array_merge(...array_map(fn ($p) => array_merge([$p['role'] ?? ''], $p['skills'] ?? []), $d['placements'] ?? [])) ?: [],
            array_merge(...array_map(fn ($e) => [$e['title'] ?? '', $e['description'] ?? ''], $d['experience'] ?? [])) ?: [],
        )));
        $learnerTopics = $this->topics($learnerText);

        // Subject fit
        $sharedTopics = array_values(array_intersect($job['topics'], $learnerTopics));
        if ($job['topics']) {
            $score += 35 * min(1, count($sharedTopics) / max(1, min(2, count($job['topics']))));
        }
        if ($sharedTopics) {
            $reasons[] = 'Trained in ' . implode(' and ', array_map(fn ($t) => $this->topicLabel($t), $sharedTopics));
        }

        // Skills overlap
        $shared = array_values(array_intersect($job['words'], $this->words($learnerText)));
        $score += min(15, count($shared) * 3);
        if (count($shared) >= 2 && ! $sharedTopics) {
            $reasons[] = 'Relevant skills: ' . implode(', ', array_slice($shared, 0, 4));
        }

        // Required course
        if ($requiredCourseSet) {
            $score += 10;
            $reasons[] = 'Completed the required course' . ($job['required_course'] ? ": {$job['required_course']}" : '');
        }

        // Performance
        $avg = $d['performance']['average_quiz_score'] ?? null;
        if ($avg !== null) {
            $score += 15 * min(1, max(0, ($avg - 40) / 50)); // 40% → 0, 90%+ → full
            if ($avg >= 70) {
                $reasons[] = "Strong assessment results (average {$avg}%)";
            }
        }

        // Experience on the platform
        $n = (int) ($d['performance']['courses_completed'] ?? 0);
        $placements = count($d['placements'] ?? []);
        $jobs = count($d['experience'] ?? []);
        $score += min(10, $n * 3 + $placements * 4 + $jobs * 2);
        if ($placements) {
            $reasons[] = $placements === 1 ? 'Has done a placement before' : "Has done {$placements} placements";
        } elseif ($jobs) {
            $reasons[] = $jobs === 1 ? '1 past job' : "{$jobs} past jobs";
        } elseif ($n >= 2) {
            $reasons[] = "Completed {$n} courses";
        }

        // Location
        if ($job['state'] && $user?->state && strcasecmp(trim($job['state']), trim($user->state)) === 0) {
            $score += 7;
            $sameLga = $job['lga'] && $user->lga && strcasecmp(trim($job['lga']), trim($user->lga)) === 0;
            if ($sameLga) {
                $score += 3;
            }
            $reasons[] = 'Lives in ' . ($sameLga ? "{$user->lga}, " : '') . $user->state;
        }

        // Looking for work
        if (in_array($user?->main_work, ['unemployed_seeking', 'apprentice', 'student', 'wage_casual'], true)
            || $user?->training_objective === 'secure_employment') {
            $score += 5;
            $reasons[] = 'Looking for work';
        }

        return [(int) round(min(100, $score)), $reasons];
    }

    /** Topic set + significant words describing the opening. */
    public function jobProfile(ApprenticeshipSlot $slot): array
    {
        $text = strtolower(implode(' ', [$slot->title, $slot->description, $slot->sector, $slot->requiredCourse?->title]));

        return [
            'topics' => $this->topics($text),
            'words' => $this->words($text),
            'state' => $slot->state,
            'lga' => $slot->lga,
            'required_course' => $slot->requiredCourse?->title,
        ];
    }

    /** @return list<string> */
    private function topics(string $text): array
    {
        $found = [];
        foreach (self::TOPICS as $topic => $keywords) {
            foreach ($keywords as $k) {
                if (preg_match('/\b' . preg_quote($k, '/') . '/', $text)) {
                    $found[] = $topic;
                    break;
                }
            }
        }

        return $found;
    }

    /** @return list<string> */
    private function words(string $text): array
    {
        preg_match_all('/[a-z]{4,}/', $text, $m);

        return array_values(array_unique(array_diff($m[0], self::STOPWORDS)));
    }

    private function topicLabel(string $topic): string
    {
        return [
            'poultry' => 'poultry', 'livestock' => 'livestock', 'fish' => 'fish farming', 'crops' => 'crop production',
            'greenhouse' => 'greenhouse / hydroponics', 'processing' => 'agro-processing', 'business' => 'agribusiness',
            'logistics' => 'agri-logistics', 'tech' => 'agri-tech and equipment',
        ][$topic] ?? $topic;
    }
}
