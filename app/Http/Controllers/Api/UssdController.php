<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UssdService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use OpenApi\Annotations as OA;

class UssdController extends Controller
{
    public function __construct(private UssdService $ussdService) {}

    /**
     * @OA\Post(
     *     path="/api/ussd",
     *     tags={"USSD"},
     *     summary="Africa's Talking USSD gateway webhook",
     *     description="Handles USSD session callbacks from Africa's Talking. Requires HMAC signature validation via X-AfricasTalking-Hash header.",
     *     @OA\Parameter(name="X-AfricasTalking-Hash", in="header", required=true, @OA\Schema(type="string"), description="HMAC-SHA256 signature of the raw request body"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 required={"sessionId","serviceCode","phoneNumber","text"},
     *                 @OA\Property(property="sessionId", type="string", example="ATUid_abc123"),
     *                 @OA\Property(property="serviceCode", type="string", example="*737*123#"),
     *                 @OA\Property(property="phoneNumber", type="string", example="+2348012345678"),
     *                 @OA\Property(property="text", type="string", example="1*1", description="Star-delimited menu navigation path")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="USSD response string prefixed with CON (continue) or END (final)"),
     *     @OA\Response(response=401, description="Invalid HMAC signature")
     * )
     */
    public function handle(Request $request): Response
    {
        // HMAC validation
        $hashKey = config('services.africastalking.hash_key');

        if ($hashKey) {
            $providedHash = $request->header('X-AfricasTalking-Hash', '');
            $rawBody      = $request->getContent();

            if (!$this->ussdService->validateHmac($rawBody, $providedHash, $hashKey)) {
                Log::warning('USSD invalid HMAC signature', ['ip' => $request->ip()]);
                return response('Unauthorized', 401, ['Content-Type' => 'text/plain']);
            }
        }

        $params = [
            'sessionId'   => $request->input('sessionId', ''),
            'serviceCode' => $request->input('serviceCode', ''),
            'phoneNumber' => $request->input('phoneNumber', ''),
            'text'        => $request->input('text', ''),
        ];

        try {
            $ussdResponse = $this->ussdService->handle($params);
        } catch (\Throwable $e) {
            Log::error('USSD handle error', ['error' => $e->getMessage()]);
            $ussdResponse = 'END Sorry, a technical error occurred. Please try again later.';
        }

        return response($ussdResponse, 200, ['Content-Type' => 'text/plain']);
    }
}
