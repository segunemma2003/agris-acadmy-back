<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class SupportMessageController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/support-messages",
     *     tags={"Support"},
     *     summary="Submit a support/contact message (public; if the request carries a valid auth token, it is linked to that account)",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","subject","message"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="subject", type="string"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Support message received"),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $data['user_id'] = $request->user('sanctum')?->id;

        $supportMessage = SupportMessage::create($data);

        NotificationService::createForRole(
            'admin',
            'support_message',
            'New support message',
            "{$supportMessage->name} sent a message: \"{$supportMessage->subject}\"",
            'support_message',
            $supportMessage->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'Your message has been received. Our team will get back to you soon.',
        ], 201);
    }
}
