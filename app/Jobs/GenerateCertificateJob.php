<?php

namespace App\Jobs;

use App\Mail\CertificateReadyMail;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Enrollment;
use App\Services\CertificateGenerationService;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class GenerateCertificateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [30, 120];
    public $timeout = 120;

    private const ADMIN_RECIPIENTS = ['admin@agrisiti.com', 'segun.bamidele@agrisiti.com'];

    public function __construct(
        public int $enrollmentId,
        public int $certificateTemplateId,
        public ?string $nameOverride = null,
    ) {}

    public function handle(CertificateGenerationService $service): void
    {
        $enrollment = Enrollment::with(['user', 'course'])->find($this->enrollmentId);
        $template = CertificateTemplate::find($this->certificateTemplateId);

        if (!$enrollment || !$enrollment->user || !$enrollment->course || !$template) {
            Log::error('Certificate generation skipped: missing enrollment, user, course or template', [
                'enrollment_id' => $this->enrollmentId,
                'certificate_template_id' => $this->certificateTemplateId,
            ]);
            return;
        }

        $user = $enrollment->user;
        $course = $enrollment->course;
        $name = $this->nameOverride ?: $user->name;

        try {
            $certificateNumber = Certificate::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->value('certificate_number') ?? Certificate::generateCertificateNumber();

            $storagePath = sprintf(
                'certificates/%d/%s-%s.pdf',
                $course->id,
                Str::slug($user->name),
                $certificateNumber
            );

            $url = $service->generateAndUpload($template, $name, $storagePath, $certificateNumber);

            $certificate = Certificate::updateOrCreate(
                ['user_id' => $user->id, 'course_id' => $course->id],
                [
                    'enrollment_id' => $enrollment->id,
                    'certificate_template_id' => $template->id,
                    'certificate_number' => $certificateNumber,
                    'recipient_name' => $name,
                    'issued_date' => now(),
                    'file_path' => $url,
                ]
            );

            Cache::forget("user_{$user->id}_certificates");

            NotificationService::create(
                $user,
                'certificate_ready',
                'Certificate Ready 🎓',
                "Your certificate for '{$course->title}' is ready to download.",
                'certificate',
                $enrollment->id,
                [
                    'course_id' => $course->id,
                    'course_title' => $course->title,
                ]
            );

            Mail::to($user->email)->queue(new CertificateReadyMail($certificate));
            Mail::to(self::ADMIN_RECIPIENTS)->queue(new CertificateReadyMail($certificate, isAdminCopy: true));
        } catch (\Throwable $e) {
            Log::error('Failed to generate certificate', [
                'enrollment_id' => $this->enrollmentId,
                'certificate_template_id' => $this->certificateTemplateId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
