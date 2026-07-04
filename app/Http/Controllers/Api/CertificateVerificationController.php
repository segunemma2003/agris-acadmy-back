<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CertificateVerificationService;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class CertificateVerificationController extends Controller
{
    public function __construct(private CertificateVerificationService $verificationService) {}

    /**
     * @OA\Post(
     *     path="/api/certificates/verify",
     *     tags={"Certificates"},
     *     summary="Verify a certificate by uploading its PDF",
     *     description="Extracts the hidden verification code embedded in the PDF and checks it against issued certificates.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"certificate"},
     *                 @OA\Property(property="certificate", type="string", format="binary", description="The certificate PDF to verify")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Verification result",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="This certificate is genuine."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="valid", type="boolean", example=true),
     *                 @OA\Property(property="certificate", type="object", nullable=true,
     *                     @OA\Property(property="recipient_name", type="string", example="John Adebayo Okonkwo"),
     *                     @OA\Property(property="course_title", type="string", example="Rice & Aquaculture Value Chain Programme"),
     *                     @OA\Property(property="certificate_number", type="string", example="CERT-AB12CD34EF56GH78"),
     *                     @OA\Property(property="issued_date", type="string", format="date", example="2026-07-04")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function verifyUpload(Request $request)
    {
        $request->validate([
            'certificate' => 'required|file|mimes:pdf|max:10240',
        ]);

        $certificate = $this->verificationService->verifyUpload(
            $request->file('certificate')->getRealPath()
        );

        return $this->respond($certificate);
    }

    /**
     * @OA\Get(
     *     path="/api/certificates/verify/{code}",
     *     tags={"Certificates"},
     *     summary="Verify a certificate by its verification code",
     *     @OA\Parameter(name="code", in="path", required=true, @OA\Schema(type="string"), example="CERT-AB12CD34EF56GH78"),
     *     @OA\Response(
     *         response=200,
     *         description="Verification result",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="This certificate is genuine."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="valid", type="boolean", example=true),
     *                 @OA\Property(property="certificate", type="object", nullable=true)
     *             )
     *         )
     *     )
     * )
     */
    public function verifyCode(string $code)
    {
        return $this->respond($this->verificationService->findByCode($code));
    }

    private function respond(?\App\Models\Certificate $certificate)
    {
        if (!$certificate) {
            return response()->json([
                'success' => true,
                'message' => 'This certificate could not be verified. It may be fake or altered.',
                'data' => ['valid' => false, 'certificate' => null],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'This certificate is genuine.',
            'data' => [
                'valid' => true,
                'certificate' => [
                    'recipient_name' => $certificate->recipient_name,
                    'course_title' => $certificate->course->title,
                    'certificate_number' => $certificate->certificate_number,
                    'issued_date' => $certificate->issued_date->toDateString(),
                ],
            ],
        ]);
    }
}
