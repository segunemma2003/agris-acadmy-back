<?php

namespace App\Services;

use App\Models\User;
use App\Support\CvTemplates;

/**
 * Drafts CV wording with Claude (headline, summary, a job description,
 * achievements, skills) from what the learner has already entered plus their
 * Agrisiti training record. The learner reviews and edits before saving.
 */
class CvWriterService
{
    public const FIELDS = ['headline', 'summary', 'experience', 'achievements', 'skills'];

    public function __construct(private CvService $cvs, private ClaudeTextService $claude) {}

    /**
     * @return string|string[]|null  text for headline/summary/experience, a list for
     *                               achievements/skills; null when Claude is unavailable
     */
    public function suggest(User $user, string $field, array $details, string $template, ?string $country, ?int $index = null, ?string $notes = null): string|array|null
    {
        $tpl = CvTemplates::get($template);
        $cv = $this->cvs->collect($user, $details, $template);

        $system = implode("\n", [
            'You help young Nigerian agricultural workers write their CVs on the Agrisiti Academy platform.',
            'Write in clear, simple, professional English that an employer in the target country expects.',
            'Use ONLY the facts given. Never invent employers, job titles, dates, qualifications, certificates or numbers.',
            'Where a number would make a point stronger but none is given, put a short placeholder in square brackets, e.g. [number] birds, for the learner to fill in.',
            'Write in the first person without "I" (CV style). No markdown, no headings, no quotation marks, no commentary — output only the requested text.',
        ]);

        $context = "Target country: " . ($country ?: 'Nigeria') . "\nCV style: {$tpl['label']}\n\nCandidate record:\n" . $this->describe($cv);
        if ($notes) {
            $context .= "\n\nThe candidate's own rough notes:\n" . $notes;
        }

        $ask = match ($field) {
            'headline' => 'Write a CV headline (job title line under the name), at most 10 words.',
            'summary' => "Write the \"{$tpl['summary_title']}\" section: 3 to 4 sentences (under 80 words) on what the candidate can do, their training and the kind of role they want.",
            'experience' => $this->experiencePrompt($details['experiences'][$index ?? 0] ?? null),
            'achievements' => 'Suggest 3 to 5 achievements for the Achievements section, drawn from the record. One per line, each under 20 words, starting with a past-tense action verb.',
            'skills' => 'Suggest 6 to 10 skills (practical and soft skills) supported by the record that are not already listed. One per line, 1 to 4 words each.',
        };

        $text = $this->claude->complete($system, $context . "\n\nTask: " . $ask, 800);
        if ($text === null) {
            return null;
        }

        if (in_array($field, ['achievements', 'skills'], true)) {
            $lines = array_values(array_filter(array_map(
                fn ($l) => trim(preg_replace('/^\s*([-*•·]|\d+[.)])\s*/u', '', $l), " \t\"'"),
                preg_split('/\r?\n/', $text)
            )));
            if ($field === 'skills') {
                $have = array_map('mb_strtolower', $cv['skills'] ?? []);
                $lines = array_values(array_filter($lines, fn ($s) => mb_strlen($s) <= 60 && ! in_array(mb_strtolower($s), $have, true)));
            }

            return array_slice($lines, 0, $field === 'skills' ? 10 : 5);
        }

        return trim($text, " \t\n\"'");
    }

    private function experiencePrompt(?array $job): string
    {
        if (! $job || blank($job['title'] ?? null)) {
            return 'Write 3 to 4 short bullet lines for a job description. One per line, starting with "• ".';
        }
        $what = trim(($job['title'] ?? '') . (filled($job['organisation'] ?? null) ? " at {$job['organisation']}" : ''));
        $existing = filled($job['description'] ?? null) ? "\nWhat the candidate already wrote (improve it, keep every fact):\n{$job['description']}" : '';

        return "Write the description for this job: {$what}.{$existing}\n3 to 5 short lines on duties and results, one per line, each starting with \"• \" and a past-tense action verb (present tense if it is the current job).";
    }

    /** Plain-text summary of the CV data for the prompt. */
    private function describe(array $cv): string
    {
        $out = [];
        $out[] = 'Name: ' . ($cv['name'] ?? '');
        if (filled($cv['headline'] ?? null)) $out[] = 'Current headline: ' . $cv['headline'];
        if (filled($cv['summary'] ?? null)) $out[] = 'Current summary: ' . $cv['summary'];
        $place = $cv['contact']['location'] ?? implode(', ', array_filter([$cv['location']['lga'] ?? null, $cv['location']['state'] ?? null]));
        if ($place) $out[] = 'Location: ' . $place;

        foreach ($cv['experience'] ?? [] as $e) {
            $out[] = '- Job: ' . trim(($e['title'] ?? '') . ', ' . ($e['organisation'] ?? ''), ', ')
                . ' (' . ($e['start'] ?? '?') . ' to ' . (($e['current'] ?? false) ? 'present' : ($e['end'] ?? '?')) . ')'
                . (filled($e['description'] ?? null) ? ': ' . $e['description'] : '');
        }
        foreach ($cv['placements'] ?? [] as $p) {
            $out[] = '- Placement via Agrisiti: ' . trim(($p['role'] ?? '') . ', ' . ($p['organisation'] ?? ''), ', ');
        }
        foreach ($cv['courses'] ?? [] as $c) {
            $out[] = '- Completed Agrisiti course: ' . $c['title'] . (isset($c['quiz_average']) ? " (quiz average {$c['quiz_average']}%)" : '');
        }
        $p = $cv['performance'] ?? [];
        if ($p) {
            $out[] = "Training totals: {$p['courses_completed']} courses, {$p['lessons_completed']} lessons, {$p['certificates']} certificates"
                . (isset($p['average_quiz_score']) ? ", average quiz score {$p['average_quiz_score']}%" : '');
        }
        foreach ($cv['education_history'] ?? [] as $e) {
            $out[] = '- Education: ' . trim(($e['qualification'] ?? '') . ', ' . ($e['institution'] ?? ''), ', ');
        }
        if (! empty($cv['skills'])) $out[] = 'Skills listed: ' . implode(', ', $cv['skills']);
        if (! empty($cv['languages'])) $out[] = 'Languages: ' . implode(', ', array_map(fn ($l) => $l['language'] . (filled($l['level'] ?? null) ? " ({$l['level']})" : ''), $cv['languages']));
        foreach ($cv['achievements'] ?? [] as $a) $out[] = '- Achievement: ' . $a;
        $interests = array_filter((array) ($cv['interests'] ?? []));
        if ($interests) $out[] = 'Interests: ' . implode(', ', array_map(fn ($v) => is_array($v) ? implode(', ', $v) : $v, $interests));

        return implode("\n", $out);
    }
}
