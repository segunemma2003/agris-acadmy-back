<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\ChatbotChunkEvent;
use App\Events\ChatbotDoneEvent;
use App\Mail\AdminNotificationMail;
use App\Models\ChatbotIntakeAnswer;
use App\Models\ChatbotSession;
use App\Models\User;
use App\Services\ChatbotService;
use App\Services\NotificationService;
use App\Services\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
     *     description="**Always the very first API call.** Fired automatically 3 seconds after the page/screen loads, or immediately when the user taps the chatbot FAB.
     *
     * ### Step-by-step process
     *
     * 1. Client reads `session_token` from localStorage (null if first visit).
     * 2. Client reads `user_id` from auth context (null if not logged in).
     * 3. Both are sent to this endpoint.
     * 4. Backend looks up the token:
     *    - **Found** → returns `is_returning: true` + full message history → client renders history, skips intro and quiz.
     *    - **Not found / null** → creates a new session → returns `is_returning: false`, `messages: []`.
     * 5. Client saves the returned `session_token` to localStorage.
     * 6. Client connects pusher-js to Reverb → subscribes to channel `chatbot.{session_token}`.
     * 7. If `is_returning: false` → show greeting locally (NO extra API call):
     *    *'Hi! I am Agri, your Agrisiti Academy guide. I will help you find the perfect course. First — what is your name?'*
     * 8. If `is_returning: true` → display previous messages immediately and open to free-chat mode.
     *
     * ### Auth scenarios
     *
     * | Scenario | session_token | user_id | Result |
     * |---|---|---|---|
     * | Anonymous, first visit | null | null | New session created |
     * | Anonymous, returning | stored UUID | null | Session resumed with history |
     * | Logged-in, first visit | null | 101 | New session linked to user |
     * | Logged-in, returning | stored UUID | 101 | Session resumed and linked |",
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="session_token", type="string", format="uuid", nullable=true, example=null, description="Token from localStorage. Send null on first visit."),
     *             @OA\Property(property="user_id", type="integer", nullable=true, example=null, description="Authenticated user ID. Send null for anonymous visitors.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Session created or resumed. Check is_returning to decide which UI flow to show.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="session_token", type="string", example="550e8400-e29b-41d4-a716-446655440000", description="Store this in localStorage. Send on every subsequent chatbot request."),
     *                 @OA\Property(property="is_returning", type="boolean", example=false, description="true = show history, skip intro. false = show greeting, start quiz flow."),
     *                 @OA\Property(property="last_seen_at", type="string", format="date-time", nullable=true, description="null for brand-new sessions."),
     *                 @OA\Property(property="messages", type="array", description="Full conversation history. Empty array for new sessions.", @OA\Items(type="object",
     *                     @OA\Property(property="role", type="string", enum={"user","assistant"}),
     *                     @OA\Property(property="content", type="string"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 ))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error — session_token must be a valid UUID if provided")
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
     *     summary="Send a message — plain REST, full reply in one response (Tier 3 fallback)",
     *     description="**Tier 3 transport — last resort fallback.** Used only when both Reverb WebSocket (Tier 1) and SSE streaming (Tier 2) have failed. The reply is NOT streamed — the user waits for the full response before seeing anything.
     *
     * ### When this is called
     *
     * The frontend tries Tier 1 → Tier 2 → Tier 3 automatically. This endpoint is only
     * reached if the other two transports both fail. Under normal conditions,
     * POST /api/chatbot/message/ws (Tier 1) or POST /api/chatbot/message/stream (Tier 2) is used instead.
     *
     * ### Step-by-step (when this fallback fires)
     *
     * 1. User submits a message in the chat input.
     * 2. User bubble added to UI immediately (optimistic update).
     * 3. Empty assistant placeholder bubble inserted, typing indicator shown.
     * 4. This endpoint is called.
     * 5. Backend calls Claude API synchronously (no streaming), waits for full reply.
     * 6. Full reply returned in one JSON response.
     * 7. Placeholder bubble replaced with the full reply text.
     * 8. Input unlocked.
     *
     * ### When is this vs the quiz flow?
     *
     * This handles **free-form chat only** (name collection, follow-up questions,
     * post-recommendation conversation). The quiz (Q1–Q5) runs entirely client-side
     * with no API calls per question. Quiz answers are submitted via POST /api/chatbot/answers.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"session_token","content"},
     *             @OA\Property(property="session_token", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *             @OA\Property(property="content", type="string", maxLength=2000, example="Is there a course about drip irrigation?")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Full assistant reply returned in one response.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="message", type="object",
     *                     @OA\Property(property="role", type="string", example="assistant"),
     *                     @OA\Property(property="content", type="string", example="Yes! We have a great course on drip irrigation systems."),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Session not found — session_token does not match any session"),
     *     @OA\Response(response=422, description="Validation error — session_token or content missing/invalid")
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
     *     path="/api/chatbot/message/stream",
     *     tags={"Chatbot"},
     *     summary="Send a message — SSE streaming reply (Tier 2 fallback)",
     *     description="**Tier 2 transport.** Used when Reverb WebSocket (Tier 1) is unavailable. Streams Claude's reply token-by-token using Server-Sent Events so the user sees text appear in real time.
     *
     * ### Step-by-step process
     *
     * 1. Reverb connection has already failed — frontend switches to this endpoint automatically.
     * 2. User submits a message.
     * 3. User bubble added to UI immediately (optimistic update).
     * 4. Empty assistant placeholder bubble inserted with typing indicator.
     * 5. This endpoint is called with headers: Accept text/event-stream and Content-Type application/json.
     * 6. Backend opens a streamed response and calls Claude API with stream enabled.
     * 7. For each token Claude yields, backend emits one SSE event line followed by a blank line.
     *    Format: data: followed by a JSON object with a delta field containing the token text.
     * 8. Frontend reads each data: line, parses the JSON, appends the delta value to the bubble.
     * 9. When Claude finishes, backend emits the end signal: data: [DONE] followed by a blank line.
     * 10. Frontend receives [DONE] — finalises bubble, unlocks input box.
     *
     * ### Exact SSE line format emitted by this endpoint
     *
     * Each token arrives as: data: followed by a JSON object with key delta and string value, then two newlines.
     * Stream ends with: data: [DONE] followed by two newlines.
     * Example stream body for the reply 'Yes! We have a great course on drip irrigation.':
     *   data: (json delta=Yes! ) blank-line
     *   data: (json delta=We have a great course) blank-line
     *   data: (json delta= on drip irrigation.) blank-line
     *   data: [DONE] blank-line
     *
     * ### Required response headers (backend must set all of these)
     *
     * - Content-Type: text/event-stream
     * - Cache-Control: no-cache
     * - X-Accel-Buffering: no — CRITICAL: disables Nginx buffering so tokens reach client immediately
     * - Connection: keep-alive",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"session_token","content"},
     *             @OA\Property(property="session_token", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *             @OA\Property(property="content", type="string", maxLength=2000, example="Do you have a course on irrigation?")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="SSE stream opened. Body contains newline-delimited data: events. Final event is data: [DONE]."
     *     ),
     *     @OA\Response(response=404, description="Session not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function messageStream(Request $request): StreamedResponse
    {
        $request->validate([
            'session_token' => 'required|uuid',
            'content'       => 'required|string|max:2000',
        ]);

        $session = ChatbotSession::where('session_token', $request->input('session_token'))->first();

        if (!$session) {
            return response()->stream(function () {
                echo "data: " . json_encode(['error' => 'Session not found']) . "\n\n";
                ob_flush(); flush();
            }, 404, ['Content-Type' => 'text/event-stream', 'Cache-Control' => 'no-cache']);
        }

        $content = $request->input('content');
        $service = $this->chatbotService;

        return response()->stream(function () use ($session, $content, $service) {
            foreach ($service->streamFromClaude($session, $content) as $token) {
                echo "data: " . json_encode(['delta' => $token]) . "\n\n";
                ob_flush();
                flush();
            }
            echo "data: [DONE]\n\n";
            ob_flush();
            flush();
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',   // disable Nginx buffering
            'Connection'        => 'keep-alive',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/chatbot/message/ws",
     *     tags={"Chatbot"},
     *     summary="Send a message — real-time reply via Reverb WebSocket (Tier 1, preferred)",
     *     description="**Tier 1 transport — preferred.** This HTTP POST triggers Claude streaming on the backend. It returns 202 immediately. The actual reply tokens arrive on the frontend through a Pusher.js channel subscription, NOT through this HTTP response.
     *
     * ### IMPORTANT: Frontend must subscribe BEFORE calling this endpoint
     *
     * The frontend must already be connected to Reverb and subscribed to
     * channel `chatbot.{session_token}` before this POST is made.
     * Otherwise tokens will be broadcast with nobody listening.
     *
     * ### Full step-by-step process
     *
     * **Setup (once after POST /api/chatbot/session):**
     * 1. Frontend creates pusher-js client pointing at the Reverb server.
     * 2. Subscribes to public channel: chatbot.SESSION_TOKEN (replace SESSION_TOKEN with the actual token).
     * 3. Binds event handlers on the channel:
     *    - On event 'chunk': read the delta field from the payload, append to assistant bubble.
     *    - On event 'done':  finalise the assistant turn, unlock the input.
     * 4. Connection state = 'connected' → activeTransport = 'websocket'.
     *
     * **Per message (every time user sends a message):**
     * 1. User submits message → user bubble added to UI.
     * 2. Empty assistant placeholder bubble inserted.
     * 3. POST /api/chatbot/message/ws { session_token, content } → 202 Accepted.
     * 4. Backend iterates the streamFromClaude() PHP Generator:
     *    - Each yielded token is broadcast as a ChatbotChunkEvent to the Reverb channel.
     *    - After the loop ends, a ChatbotDoneEvent is broadcast to the same channel.
     * 5. Reverb pushes these events to channel chatbot.SESSION_TOKEN.
     * 6. Frontend 'chunk' handler fires for each token → text fills the bubble.
     * 7. Frontend 'done'  handler fires → input unlocked, bubble finalised.
     *
     * ### Reverb channel and events
     *
     * Channel: `chatbot.{session_token}` (public — no Pusher auth needed)
     * Event `chunk`  payload: `{ delta: 'text token' }` — append to assistant bubble
     * Event `done`   payload: `{}`                      — end of turn, unlock input
     *
     * ### Pusher.js connection config (frontend — React/React Native)
     *
     * Pass these options to the Pusher constructor: wsHost from VITE_REVERB_HOST,
     * wsPort 443 in production (8080 in local dev), wssPort 443, forceTLS true in production,
     * enabledTransports ws and wss, disableStats true, cluster mt1 (required by library but ignored by Reverb).
     *
     * ### Fallback behaviour
     *
     * If the pusher-js connection fails at setup time, `activeTransport` switches to `sse`
     * and subsequent messages use POST /api/chatbot/message/stream instead.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"session_token","content"},
     *             @OA\Property(property="session_token", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="Must already be subscribed to channel chatbot.{session_token} before calling."),
     *             @OA\Property(property="content", type="string", maxLength=2000, example="Do you have a course on drip irrigation?")
     *         )
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Accepted — backend is streaming Claude reply to Reverb channel. Tokens arrive as 'chunk' Pusher events.",
     *         @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true))
     *     ),
     *     @OA\Response(response=404, description="Session not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function messageWebSocket(Request $request): JsonResponse
    {
        $request->validate([
            'session_token' => 'required|uuid',
            'content'       => 'required|string|max:2000',
        ]);

        $session = ChatbotSession::where('session_token', $request->input('session_token'))->first();

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found'], 404);
        }

        $token   = $session->session_token;
        $content = $request->input('content');

        // Stream Claude's reply and broadcast each token to the Reverb WebSocket channel.
        // ShouldBroadcastNow fires synchronously — no queue needed.
        foreach ($this->chatbotService->streamFromClaude($session, $content) as $delta) {
            broadcast(new ChatbotChunkEvent($token, $delta));
        }

        broadcast(new ChatbotDoneEvent($token));

        return response()->json(['success' => true], 202);
    }

    /**
     * @OA\Post(
     *     path="/api/chatbot/dismiss",
     *     tags={"Chatbot"},
     *     summary="Record that the user closed the chatbot widget",
     *     description="Called **fire-and-forget** when the user taps the close (×) button on the chatbot.
     * Errors from this call must be silently ignored — the widget should still collapse regardless.
     *
     * ### Step-by-step (client side)
     *
     * 1. User taps close button.
     * 2. Client stores `chatbot_dismissed_at = now().toISOString()` in localStorage.
     * 3. This endpoint is called asynchronously (do not await it).
     * 4. Widget collapses to FAB immediately — do not wait for the response.
     *
     * ### Effect on next app open
     *
     * On every app open the client checks localStorage for `chatbot_dismissed_at`:
     * - If less than 24 hours ago → POST /api/chatbot/session still fires (session init),
     *   but the widget stays collapsed as FAB. User can tap FAB to open manually.
     * - If 24 hours or more ago → widget auto-opens normally after 3-second delay.
     *
     * ### Backend use of this signal
     *
     * - Updates `metadata->dismissed_at` on the session record.
     * - Used for churn analytics and suppressing push notifications during the 24h window.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"session_token"},
     *             @OA\Property(property="session_token", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Dismissed recorded. Widget can collapse immediately without waiting for this response.", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true))),
     *     @OA\Response(response=422, description="Validation error")
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
     *     summary="Save onboarding quiz answers after the user completes all 5 questions",
     *     description="Called once after the user finishes the onboarding quiz (or taps 'Skip All').
     * Always followed immediately by POST /api/chatbot/recommendations.
     *
     * ### IMPORTANT — Quiz questions are hardcoded in the frontend
     *
     * The backend NEVER sends these questions. The frontend renders them locally.
     * This endpoint only receives the answers. Mobile developers must implement
     * these exact 5 questions in their UI before calling this endpoint.
     *
     * **Q1 — occupation** — 'What best describes you?'
     * Chips: Farmer | Student | Agri-business owner | Researcher | Other (or free text)
     * → stored in field: occupation
     *
     * **Q2 — state_lga** — 'Which State/LGA are you in?'
     * Free text only (no chips) — e.g. 'Kano, Nassarawa LGA'
     * → stored in field: state_lga
     *
     * **Q3 — learning_goal** — 'What is your main learning goal?'
     * Chips: Start a farm | Improve my farm | Learn agri-tech | Get certified | Other
     * → stored in field: learning_goal
     *
     * **Q4 — experience_level** — 'What is your farming experience?'
     * Chips: None | Beginner | Intermediate | Expert
     * → stored in field: experience_level
     *
     * **Q5 — preferred_language** — 'What is your preferred language?'
     * Chips: English (stored as 'en') | Hausa (stored as 'ha')
     * → stored in field: preferred_language
     *
     * ### Skip logic
     *
     * If a question is skipped, its 1-based index goes into `skipped_questions`
     * and its field is omitted from the payload.
     *
     * | Example action | skipped_questions |
     * |---|---|
     * | All 5 answered | [] |
     * | Q2 and Q5 skipped | [2, 5] |
     * | 'Skip All' tapped immediately | [1, 2, 3, 4, 5] |
     *
     * ### After this call
     *
     * On success: immediately call POST /api/chatbot/recommendations.
     * On failure: show a retry button. Do NOT call /recommendations until this succeeds.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"session_token"},
     *             @OA\Property(property="session_token", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *             @OA\Property(property="occupation", type="string", nullable=true, example="Farmer", description="Answer to Q1. Chip value or free text."),
     *             @OA\Property(property="state_lga", type="string", nullable=true, example="Kano, Nassarawa LGA", description="Answer to Q2. Free text only."),
     *             @OA\Property(property="learning_goal", type="string", nullable=true, example="Start a farm", description="Answer to Q3. Chip value."),
     *             @OA\Property(property="experience_level", type="string", nullable=true, example="Beginner", description="Answer to Q4. Chip value."),
     *             @OA\Property(property="preferred_language", type="string", nullable=true, enum={"en","ha"}, example="en", description="Answer to Q5. 'English' chip → 'en'. 'Hausa' chip → 'ha'."),
     *             @OA\Property(property="skipped_questions", type="array", nullable=true, example={2}, description="1-based indices of skipped questions. Empty array if none skipped.", @OA\Items(type="integer", minimum=1, maximum=5))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Answers saved. Immediately call POST /api/chatbot/recommendations next.", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true))),
     *     @OA\Response(response=404, description="Session not found"),
     *     @OA\Response(response=422, description="Validation error")
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
     *     summary="Get ranked course recommendations based on quiz answers",
     *     description="Called **immediately after** POST /api/chatbot/answers. Returns top-N courses scored and ranked against the user's quiz answers.
     *
     * ### When this is called
     *
     * The client calls this right after /chatbot/answers succeeds.
     * While waiting, show a loading state: 'Finding your courses...'
     *
     * ### How recommendations are scored (backend logic)
     *
     * 1. Read quiz answers linked to this session_token.
     * 2. Score all published courses against:
     *    - occupation → relevance of course topic to the user's role
     *    - learning_goal → alignment with course outcomes
     *    - experience_level → match with course level (beginner/intermediate/expert)
     *    - preferred_language → language availability of course
     *    - state_lga → regional relevance where applicable
     * 3. Return top courses ordered by score descending, each with a label and rationale.
     * 4. If all 5 questions were skipped → return popular/featured courses as fallback.
     *
     * ### What to render for each recommendation card
     *
     * - `label` — e.g. 'Best Match', 'Highly Relevant' — display as a badge on the card
     * - `score` — 0 to 1 float — can be shown as percentage or hidden from user
     * - `course.thumbnail` — image URL for the card header
     * - `course.title` — course name
     * - `course.level` — beginner / intermediate / advanced
     * - `course.estimated_hours` — e.g. '6h 30m'
     * - `course.languages` — array, e.g. ['English', 'Hausa']
     * - `course.category` — e.g. 'Crop Production'
     * - `course.rationale` — why this course was chosen — show this text on the card
     * - `course.id` — use to navigate to the course detail screen on card tap
     *
     * ### On error
     *
     * Show: 'Could not load courses right now. You can still chat with me!'
     * Do not block the chat input — user can continue messaging freely.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"session_token"},
     *             @OA\Property(property="session_token", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ranked course recommendations. Render each as a tappable card. Tapping navigates to course detail.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="recommendations", type="array", @OA\Items(type="object",
     *                     @OA\Property(property="label", type="string", example="Best Match", description="Badge text for the card."),
     *                     @OA\Property(property="score", type="number", format="float", example=0.96, description="Relevance score 0-1."),
     *                     @OA\Property(property="course", type="object",
     *                         @OA\Property(property="id", type="integer", example=42, description="Use for navigation to course detail."),
     *                         @OA\Property(property="title", type="string", example="Introduction to Organic Farming"),
     *                         @OA\Property(property="thumbnail", type="string", nullable=true, example="https://cdn.agrisiti.com/courses/42/thumb.jpg"),
     *                         @OA\Property(property="rationale", type="string", example="Perfect for beginners starting a farm in northern Nigeria.", description="Show this text on the recommendation card."),
     *                         @OA\Property(property="estimated_hours", type="string", nullable=true, example="6h 30m"),
     *                         @OA\Property(property="languages", type="array", @OA\Items(type="string"), example={"English","Hausa"}),
     *                         @OA\Property(property="category", type="string", nullable=true, example="Crop Production"),
     *                         @OA\Property(property="level", type="string", nullable=true, example="beginner")
     *                     )
     *                 ))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Session not found"),
     *     @OA\Response(response=422, description="Validation error")
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
     *     summary="Link an anonymous session and quiz answers to a registered user",
     *     description="Called **automatically** when a user who was chatting anonymously then logs in or registers. No user action is needed — the app detects the auth state change and fires this silently in the background.
     *
     * ### When this is triggered
     *
     * The frontend watches `user_id` in the auth context. The moment it changes from
     * `null` to a number (user just logged in or registered), if a `session_token`
     * already exists in localStorage, this endpoint is called automatically.
     *
     * ### Step-by-step
     *
     * 1. User was chatting anonymously → `session_token: 'abc'` in localStorage.
     * 2. User navigates to login/register screen and authenticates.
     * 3. Auth context updates: `user_id` changes from `null` → `101`.
     * 4. App detects this change (useEffect / reactive state watcher).
     * 5. App calls POST /api/chatbot/link-answers { session_token: 'abc', user_id: 101 }.
     * 6. Backend links the session and intake answers to user 101.
     * 7. **Errors from this call must be silently ignored.** The session still works locally.
     *
     * ### What the backend does
     *
     * - Finds session by `session_token`.
     * - Sets `session.user_id = user_id`.
     * - If an intake answer record exists for this session, sets `intake_answer.user_id = user_id`.
     * - Rejects with 403 if the session is already linked to a DIFFERENT user.
     *
     * ### Why this matters
     *
     * After linking, the user's quiz answers and conversation history are permanently tied
     * to their account. On future logins from any device, the backend can recognise them
     * and pre-load personalised recommendations without needing the chatbot quiz again.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"session_token","user_id"},
     *             @OA\Property(property="session_token", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="The anonymous session token from localStorage."),
     *             @OA\Property(property="user_id", type="integer", example=101, description="The authenticated user's ID from the auth context.")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Session and quiz answers linked to user. Ignore errors silently.", @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true))),
     *     @OA\Response(response=403, description="Session already linked to a different user — do not overwrite."),
     *     @OA\Response(response=404, description="Session not found — token may have expired or never existed."),
     *     @OA\Response(response=422, description="Validation error")
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
