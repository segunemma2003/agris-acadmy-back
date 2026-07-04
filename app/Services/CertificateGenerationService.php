<?php

namespace App\Services;

use App\Models\CertificateTemplate;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class CertificateGenerationService
{
    /**
     * Render the template with the given name and return the raw PDF bytes.
     * When $verificationCode is given, it's printed as a tiny, near-invisible
     * footer line (real text, not metadata) so uploaded certificates can be
     * verified by extracting it back out.
     */
    public function render(CertificateTemplate $template, string $name, ?string $verificationCode = null): string
    {
        $templatePath = Storage::disk('public')->path($template->file_path);

        $pdf = new Fpdi();
        $pdf->setSourceFile($templatePath);
        $pageId = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($pageId);

        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        // Text placed close to the bottom edge (the verification code) would
        // otherwise trigger FPDF's automatic page break, silently pushing it
        // onto a blank second page instead of the certificate itself.
        $pdf->SetAutoPageBreak(false);
        $pdf->useTemplate($pageId);

        $pdf->SetFont($template->font_family, $template->font_style, $template->font_size);
        [$r, $g, $b] = $this->hexToRgb($template->font_color);
        $pdf->SetTextColor($r, $g, $b);

        $y = $size['height'] * ((float) $template->name_y_percent / 100);
        $pdf->SetXY(0, $y);
        $pdf->Cell($size['width'], 10, $this->toWinAnsi($name), 0, 0, 'C');

        if ($verificationCode) {
            $pdf->SetFont('Helvetica', '', 5);
            $pdf->SetTextColor(190, 190, 190);
            $pdf->SetXY(0, $size['height'] - 4);
            $pdf->Cell($size['width'], 3, $verificationCode, 0, 0, 'C');
        }

        return $pdf->Output('S');
    }

    /**
     * Render the certificate and upload it to S3, returning the public URL.
     */
    public function generateAndUpload(CertificateTemplate $template, string $name, string $storagePath, ?string $verificationCode = null): string
    {
        $contents = $this->render($template, $name, $verificationCode);

        // The 's3' disk is configured with 'throw' => false and 'report' => false,
        // so a failed write would otherwise return false silently with no
        // exception and no log line. Force writes on this disk to throw so the
        // real S3 error (e.g. bucket ACLs disabled, bad credentials) surfaces
        // to the job's error handling instead of producing a dead link.
        config(['filesystems.disks.s3.throw' => true]);
        $disk = Storage::disk('s3');
        $disk->put($storagePath, $contents, 'public');

        if (!$disk->exists($storagePath)) {
            throw new \RuntimeException("Certificate upload to S3 did not persist: {$storagePath}");
        }

        return $disk->url($storagePath);
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * FPDI's core fonts only support the Windows-1252 charset.
     */
    private function toWinAnsi(string $text): string
    {
        return mb_convert_encoding($text, 'Windows-1252', 'UTF-8');
    }
}
