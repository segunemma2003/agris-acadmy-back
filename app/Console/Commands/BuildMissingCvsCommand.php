<?php

namespace App\Console\Commands;

use App\Models\Enrollment;
use App\Models\LearnerCv;
use App\Models\User;
use App\Services\CvService;
use Illuminate\Console\Command;

/** Build CVs for learners who completed a course but have none yet (e.g. graduates from before CVs existed). */
class BuildMissingCvsCommand extends Command
{
    protected $signature = 'cvs:build-missing {--limit=500 : Max CVs to build per run}';

    protected $description = 'Generate CVs for course graduates who do not have one yet';

    public function handle(CvService $cvs): int
    {
        $ids = Enrollment::where(fn ($q) => $q->where('status', 'completed')->orWhere('progress_percentage', '>=', 100))
            ->whereNotIn('user_id', LearnerCv::select('user_id'))
            ->distinct()->limit((int) $this->option('limit'))->pluck('user_id');

        $built = 0;
        foreach (User::whereIn('id', $ids)->where('role', 'student')->cursor() as $user) {
            try {
                $cvs->build($user);
                $built++;
            } catch (\Throwable $e) {
                $this->warn("User {$user->id}: {$e->getMessage()}");
            }
        }
        $this->info("Built {$built} CV(s).");

        return self::SUCCESS;
    }
}
