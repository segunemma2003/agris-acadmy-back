<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\CvService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** (Re)build a learner's CV after they complete a course or receive a certificate. */
class BuildLearnerCvJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 60;

    public function __construct(public int $userId)
    {
    }

    public function uniqueId(): string
    {
        return (string) $this->userId;
    }

    public function handle(CvService $cvs): void
    {
        if ($user = User::find($this->userId)) {
            $cvs->build($user);
        }
    }
}
