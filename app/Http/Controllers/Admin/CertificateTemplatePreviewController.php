<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use App\Services\CertificateGenerationService;

class CertificateTemplatePreviewController extends Controller
{
    /**
     * Render the template with a sample name and stream it inline so the
     * admin sees the actual certificate design, not just a downloaded file.
     */
    public function __invoke(CertificateTemplate $certificateTemplate, CertificateGenerationService $service)
    {
        $contents = $service->render($certificateTemplate, 'JOHN ADEBAYO OKONKWO', 'CERT-PREVIEW0000SAMPLE');

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="certificate-preview.pdf"',
        ]);
    }
}
