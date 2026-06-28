<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\AdminNotificationMail;
use App\Models\ChatbotIntakeAnswer;
use App\Models\ChatbotSession;
use App\Models\User;
use App\Services\ChatbotService;
use App\Services\NotificationService;
use App\Services\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use OpenApi\Annotations as OA;

class ChatbotController extends Controller
{
    public function __construct(
        private ChatbotService $chatbotService,
        private RecommendationService $recommendationService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/chatbot/session",
     *     tags={"Chatbot"},
     *     summary="Start or resume a chatbot session",
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="session_token", type="string", format="uuid", nullable=true, example="550e8400-e29b-41d4-a716-446655440000"),
     *             @OA\Property(property="user_id", type="integer", nullable=true, example=42)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Session created or resumed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="session_token", type="string", example="550e8400-..."),
     *                 @OA\Property(property="is_returning", type="boolean", example=false),
     *                 @OA\Property(property="last_seen_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="messages", type="array", @OA\Items(type="object",
     *                     @OA\Property(property="role", type="string", enum={"user","assistant"}),
     *                     @OA\Property(property="content", type="string"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 ))
     *             )
     *         )
     *     )
     * )
     */
    public function session(Request $request): JsonResponse
    {
        $request->validate([
            'session_token' => 'nullable|uuid',
            'user_id'       => 'nullable|integer|exists:users,id',
        ]);

        $session = $this->chatbotService->findOrCreateSession(
            $request->input('session_token'),
            $request->input('user_id')
        );

        $isReturning = $this->chatbotService->isReturningVisitor($session);

        $messages = $session->messages()
            ->orderBy('created_at')
            ->get()
            ->map(fn ($m) => [
                'role'       => $m->role,
                'content'    => $m->content,
                'created_at' => $m->created_at,
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'session_token' => $session->session_token,
                'is_returning'  => $isReturning,
                'last_seen_at'  => $session->last_seen_at,
                'messages'      => $messages,
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/chatbot/message",
     *     tags={"Chatbot"},
     *     summary="Send a user message and receive an AI reply",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"session_token","content"},
     *             @OA\Property(property="session_token", type="string", format="uuid", example="550e8400-..."),
     *             @OA\Property(property="content", type="string", maxLength=2000, example="What courses do you offer?")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="AI reply",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="message", type="object",
     *                     @OA\Property(property="role", type="string", example="assistant"),
     *                     @OA\Property(property="content", type="string"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Session not found")
     * )
     */
    public function message(Request $request): JsonResponse
    {
        $request->validate([
            'session_token' => 'required|uuid',
            'content'       => 'required|string|max:2000',
        ]);

        $session = ChatbotSession::where('session_token', $request->input('session_token'))->first();

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found'], 404);
        }

        $reply = $this->chatbotService->sendToClaudeAndPersist($session, $request->input('content'));

        return response()->json([
            'success' => true,
            'data'    => [
                'message' => [
                    'role'       => $reply->role,
                    'content'    => $reply->content,
                    'created_at' => $reply->created_at,
                ],
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/chatbot/dismiss",
     *     tags={"Chatbot"},
     *     summary="Record that the user explicitly closed the chatbot widget",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"session_token"},
     *             @OA\Property(property="session_token", type="string", format="uuid")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Dismissed", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true)))
     * )
     */
    public function dismiss(Request $request): JsonResponse
    {
        $request->validate(['session_token' => 'required|uuid']);

        ChatbotSession::where('session_token', $request->input('session_token'))
            ->update(['metadata->dismissed_at' => now()->toIso8601String()]);

        return response()->json(['success' => true]);
    }

    /**
     * @OA\Post(
     *     path="/api/chatbot/answers",
     *     tags={"Chatbot"},
     *     summary="Save or update intake quiz answers for a session",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"session_token"},
     *             @OA\Property(property="session_token", type="string", format="uuid"),
     *             @OA\Property(property="occupation", type="string", nullable=true, example="Farmer"),
     *             @OA\Property(property="state_lga", type="string", nullable=true, example="Kano"),
     *             @OA\Property(property="learning_goal", type="string", nullable=true, example="Improve my farm"),
     *             @OA\Property(property="experience_level", type="string", nullable=true, example="Beginner"),
     *             @OA\Property(property="preferred_language", type="string", nullable=true, enum={"en","ha"}),
     *             @OA\Property(property="skipped_questions", type="array", nullable=true, @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Answers saved", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true))),
     *     @OA\Response(response=404, description="Session not found")
     * )
     */
    public function saveAnswers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_token'      => 'required|uuid',
            'occupation'         => 'nullable|string|max:255',
            'state_lga'          => 'nullable|string|max:255',
            'learning_goal'      => 'nullable|string|max:255',
            'experience_level'   => 'nullable|string|max:255',
            'preferred_language' => 'nullable|in:en,ha',
            'skipped_questions'  => 'nullable|array',
            'skipped_questions.*'=> 'integer|min:1|max:5',
        ]);

        $session = ChatbotSession::where('session_token', $validated['session_token'])->first();

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found'], 404);
        }

        unset($validated['session_token']);

        ChatbotIntakeAnswer::updateOrCreate(
            ['chatbot_session_id' => $session->id],
            array_merge($validated, ['source' => 'chatbot'])
        );

        // Notify admins of new chatbot lead
        $this->notifyAdmins($session, $validated);

        return response()->json(['success' => true]);
    }

    /**
     * @OA\Post(
     *     path="/api/chatbot/recommendations",
     *     tags={"Chatbot"},
     *     summary="Get top 3 course recommendations based on quiz answers",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"session_token"},
     *             @OA\Property(property="session_token", type="string", format="uuid")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Course recommendations",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="recommendations", type="array", @OA\Items(type="object",
     *                     @OA\Property(property="label", type="string", example="Best match"),
     *                     @OA\Property(property="score", type="number", example=0.92),
     *                     @OA\Property(property="course", type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="title", type="string"),
     *                         @OA\Property(property="thumbnail", type="string"),
     *                         @OA\Property(property="rationale", type="string"),
     *                         @OA\Property(property="estimated_hours", type="string"),
     *                         @OA\Property(property="languages", type="array", @OA\Items(type="string"))
     *                     )
     *                 ))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Session not found")
     * )
     */
    public function recommendations(Request $request): JsonResponse
    {
        $request->validate(['session_token' => 'required|uuid']);

        $session = ChatbotSession::where('session_token', $request->input('session_token'))
            ->with('intakeAnswer')
            ->first();

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found'], 404);
        }

        $recommendations = $this->recommendationService->topThree($session);

        return response()->json([
            'success' => true,
            'data'    => ['recommendations' => $recommendations],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/chatbot/link-answers",
     *     tags={"Chatbot"},
     *     summary="Link chatbot quiz answers to a newly registered user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"session_token","user_id"},
     *             @OA\Property(property="session_token", type="string", format="uuid"),
     *             @OA\Property(property="user_id", type="integer", example=99)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Linked successfully", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true))),
     *     @OA\Response(response=403, description="Session already linked to a different user"),
     *     @OA\Response(response=404, description="Session not found")
     * )
     */
    public function linkAnswers(Request $request): JsonResponse
    {
        $request->validate([
            'session_token' => 'required|uuid',
            'user_id'       => 'required|integer|exists:users,id',
        ]);

        $session = ChatbotSession::where('session_token', $request->input('session_token'))->first();

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found'], 404);
        }

        $userId = $request->input('user_id');

        // Guard: reject if already linked to a different user
        if ($session->user_id && $session->user_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Session already linked to a different user'], 403);
        }

        $session->update(['user_id' => $userId]);

        if ($session->intakeAnswer) {
            $session->intakeAnswer->update(['user_id' => $userId]);
        }

        return response()->json(['success' => true]);
    }

    private function notifyAdmins(ChatbotSession $session, array $answers): void
    {
        try {
            $detail = implode(', ', array_filter([
                isset($answers['occupation']) ? "Occupation: {$answers['occupation']}" : null,
                isset($answers['state_lga']) ? "State: {$answers['state_lga']}" : null,
                isset($answers['learning_goal']) ? "Goal: {$answers['learning_goal']}" : null,
            ]));

            $message = "New chatbot lead from session {$session->session_token}. Details: {$detail}";

            NotificationService::createForRole(
                'admin',
                'new_lead',
                'New Chatbot Lead',
                $message,
                'chatbot_lead',
                $session->id
            );

            $admins = User::where('role', 'admin')->where('is_active', true)->get();
            foreach ($admins as $admin) {
                Mail::to($admin->email)->queue(
                    new AdminNotificationMail($admin, 'New Chatbot Lead — Agrisiti Academy', $message)
                );
            }
        } catch (\Throwable $e) {
            // Non-critical — log and continue
            \Log::warning('Failed to notify admins of chatbot lead', ['error' => $e->getMessage()]);
        }
    }
}
