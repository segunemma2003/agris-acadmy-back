<?php

namespace App\Services;

use App\Models\Certificate;
use Smalot\PdfParser\Parser;

class CertificateVerificationService
{
    private const CODE_PATTERN = '/CERT-[A-Z0-9]+/';

    /**
     * Extract the verification code embedded in an uploaded certificate PDF.
     */
    public function extractCode(string $pdfPath): ?string
    {
        // Certificate templates are image-heavy; pdfparser decodes every
        // embedded stream while walking the file, which needs more than the
        // default memory ceiling.
        $previousLimit = ini_get('memory_limit');
        ini_set('memory_limit', '512M');

        try {
            $parser = new Parser();
            $text = $parser->parseFile($pdfPath)->getText();
        } finally {
            ini_set('memory_limit', $previousLimit);
        }

        return preg_match(self::CODE_PATTERN, $text, $matches) ? $matches[0] : null;
    }

    /**
     * Look up a certificate by its verification code.
     */
    public function findByCode(string $code): ?Certificate
    {
        return Certificate::with('course:id,title,slug')
            ->where('certificate_number', $code)
            ->first();
    }

    /**
     * Verify an uploaded certificate PDF, returning the matching certificate if genuine.
     */
    public function verifyUpload(string $pdfPath): ?Certificate
    {
        $code = $this->extractCode($pdfPath);

        return $code ? $this->findByCode($code) : null;
    }
}
