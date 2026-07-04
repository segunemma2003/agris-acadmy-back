<?php

namespace App\Services;

use App\Models\CertificateTemplate;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class CertificateGenerationService
{
    /**
     * Render the template with the given name and return the raw PDF bytes.
     */
    public function render(CertificateTemplate $template, string $name): string
    {
        $templatePath = Storage::disk('public')->path($template->file_path);

        $pdf = new Fpdi();
        $pdf->setSourceFile($templatePath);
        $pageId = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($pageId);

        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($pageId);

        $pdf->SetFont($template->font_family, $template->font_style, $template->font_size);
        [$r, $g, $b] = $this->hexToRgb($template->font_color);
        $pdf->SetTextColor($r, $g, $b);

        $y = $size['height'] * ((float) $template->name_y_percent / 100);
        $pdf->SetXY(0, $y);
        $pdf->Cell($size['width'], 10, $this->toWinAnsi($name), 0, 0, 'C');

        return $pdf->Output('S');
    }

    /**
     * Render the certificate and upload it to S3, returning the public URL.
     */
    public function generateAndUpload(CertificateTemplate $template, string $name, string $storagePath): string
    {
        $contents = $this->render($template, $name);

        Storage::disk('s3')->put($storagePath, $contents, 'public');

        return Storage::disk('s3')->url($storagePath);
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
