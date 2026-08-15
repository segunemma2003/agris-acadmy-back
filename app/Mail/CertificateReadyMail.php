<?php

namespace App\Mail;

use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class CertificateReadyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300];
    public $timeout = 60;
    public $maxExceptions = 2;

    public function __construct(
        public Certificate $certificate,
        public bool $isAdminCopy = false,
    ) {}

    public function build()
    {
        $this->certificate->loadMissing(['user', 'course']);

        $courseTitle = $this->certificate->course->title;
        $recipientName = $this->certificate->recipient_name;
        $locale = $this->resolveLocale();

        $subject = $this->isAdminCopy
            ? ($locale === 'ha'
                ? "An samar da takardar shaida: {$recipientName} - {$courseTitle}"
                : "Certificate generated: {$recipientName} - {$courseTitle}")
            : ($locale === 'ha'
                ? "Takardar shaidarka ta '{$courseTitle}' a shirye take"
                : "Your certificate for '{$courseTitle}' is ready");

        $frontend = rtrim(config('services.frontend.url', config('app.url')), '/');
        $pathBUrl = $frontend . '/career/apprenticeships';
        $pathAUrl = rtrim(config('services.finance.url', 'https://lend.agrisiti.com'), '/') . '/apply';

        $mailable = $this->subject($subject)
            ->view('emails.certificate-ready', [
                'certificate' => $this->certificate,
                'user' => $this->certificate->user,
                'course' => $this->certificate->course,
                'isAdminCopy' => $this->isAdminCopy,
                'locale' => $locale,
                'pathAUrl' => $pathAUrl,
                'pathBUrl' => $pathBUrl,
                'certificatesUrl' => $frontend . '/my-certificates',
            ]);

        $this->attachCertificatePdf($mailable);

        return $mailable;
    }

    private function resolveLocale(): string
    {
        $locale = $this->certificate->user?->locale ?? 'en';

        return $locale === 'ha' ? 'ha' : 'en';
    }

    private function attachCertificatePdf(Mailable $mailable): void
    {
        $relativePath = $this->resolvePublicDiskPath($this->certificate->file_path);

        if (!$relativePath) {
            return;
        }

        $disk = Storage::disk('public');

        if (!$disk->exists($relativePath)) {
            return;
        }

        $filename = sprintf(
            'agrisiti-certificate-%s.pdf',
            $this->certificate->certificate_number ?: $this->certificate->id
        );

        $mailable->attachData($disk->get($relativePath), $filename, [
            'mime' => 'application/pdf',
        ]);
    }

    /**
     * Certificates store a public URL (…/storage/certificates/…). Map that back
     * to the relative public-disk path, or accept a relative path as-is.
     */
    private function resolvePublicDiskPath(?string $filePath): ?string
    {
        if (!$filePath) {
            return null;
        }

        if (!str_starts_with($filePath, 'http://') && !str_starts_with($filePath, 'https://')) {
            return ltrim($filePath, '/');
        }

        $path = parse_url($filePath, PHP_URL_PATH) ?: '';
        $marker = '/storage/';
        $pos = strpos($path, $marker);

        if ($pos === false) {
            return null;
        }

        return ltrim(substr($path, $pos + strlen($marker)), '/');
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('Certificate ready email failed to send', [
            'certificate_id' => $this->certificate->id,
            'is_admin_copy' => $this->isAdminCopy,
            'error' => $exception->getMessage(),
        ]);
    }
}
