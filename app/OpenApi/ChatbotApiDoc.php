<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Chatbot",
 *     description="All chatbot endpoints are PUBLIC — no Authorization header required.
 * Session identity uses session_token (UUID) in the request body, not auth headers.
 * Store this token in localStorage (web) or AsyncStorage (React Native).
 *
 * =====================================================================
 * COMPLETE USER JOURNEY — READ THIS BEFORE IMPLEMENTING
 * =====================================================================
 *
 * ──────────────────────────────────────────────────────────────────────
 * SCENARIO A: First-time anonymous user (no token in storage)
 * ──────────────────────────────────────────────────────────────────────
 *
 * STEP 1 — App opens. Storage has no chatbot_session_token. User is not logged in.
 * After a 3-second delay (or immediately on FAB tap):
 * Call POST /api/chatbot/session  body: session_token=null, user_id=null
 * Response: is_returning=false, fresh UUID session_token, messages=empty array.
 * Save the session_token to storage. Connect pusher-js to Reverb server.
 * Subscribe to public Reverb channel: chatbot.SESSION_TOKEN
 *
 * STEP 2 — Show greeting locally. NO API CALL.
 * Render this as an assistant chat bubble (no server call needed):
 * Hi! I am Agri, your Agrisiti Academy guide.
 * I will help you find the perfect course. First — what is your name?
 *
 * STEP 3 — User types their name and taps Send.
 * Add user bubble to UI immediately (optimistic, before API responds).
 * Insert empty assistant placeholder bubble. Show typing indicator.
 * Call POST /api/chatbot/message/ws  body: session_token, content: I am Amina
 * Response: 202 Accepted immediately. Actual reply arrives via Reverb.
 * Listen on channel — receive chunk events → append delta text to placeholder bubble.
 * Receive done event → remove typing indicator, unlock input.
 *
 * STEP 4 — Show quiz offer. NO API CALL.
 * After the assistant replies, render two buttons:
 * Button 1: Start Quiz
 * Button 2: Skip — just chat
 *
 * STEP 5 — User taps Start Quiz. Show Question 1 of 5. NO API CALL.
 * Question text: What best describes you?
 * Render chips: Farmer, Student, Agri-business owner, Researcher, Other
 * Also allow free text input.
 * Show a Skip Q1 link.
 * User taps chip or types free text → buffer answer in sessionStorage. No API call.
 * Transition to Q2.
 *
 * STEP 6 — Question 2 of 5. NO API CALL.
 * Question text: Which State and LGA are you in?
 * Render a free text input only — no chips for this question.
 * Placeholder hint: e.g. Kano, Nassarawa LGA
 * User types → buffer in sessionStorage. Transition to Q3.
 *
 * STEP 7 — Question 3 of 5. NO API CALL.
 * Question text: What is your main learning goal?
 * Render chips: Start a farm, Improve my farm, Learn agri-tech, Get certified, Other
 * User taps → buffer. Transition to Q4.
 *
 * STEP 8 — Question 4 of 5. NO API CALL.
 * Question text: What is your farming experience?
 * Render chips: None, Beginner, Intermediate, Expert
 * User taps → buffer. Transition to Q5.
 *
 * STEP 9 — Question 5 of 5. NO API CALL.
 * Question text: What is your preferred language?
 * Render two chips only: English and Hausa
 * English chip stores the string value en in the payload.
 * Hausa chip stores the string value ha in the payload.
 * User taps → buffer.
 *
 * STEP 10 — User answered Q5 (or tapped Skip All). TWO SEQUENTIAL API CALLS.
 * Show loading screen with text: Finding your courses...
 * API CALL 1: POST /api/chatbot/answers
 *   Body: session_token, plus any answered fields, plus skipped_questions array.
 *   On failure: show Retry button. Do NOT call /recommendations yet.
 * API CALL 2 (only after CALL 1 succeeds): POST /api/chatbot/recommendations
 *   Body: session_token only.
 *   On failure: show message Cannot load courses right now. You can still chat.
 * Also save answers to localStorage as chatbot_prefill for pre-filling on future visits.
 *
 * STEP 11 — Render course recommendation cards.
 * Use these fields for each card:
 *   label       — badge text e.g. Best Match (show as a coloured chip on the card)
 *   score       — float 0-1 (can show as percentage or hide from user)
 *   course.thumbnail     — image at top of card
 *   course.title         — large text below image
 *   course.category      — subtitle text
 *   course.level         — badge: beginner, intermediate, or advanced
 *   course.estimated_hours — e.g. 6h 30m
 *   course.languages     — array, show as small flags or text: English, Hausa
 *   course.rationale     — important: show this text as a description on the card
 *   course.id            — use this to navigate to course detail screen on tap
 *
 * STEP 12 — Post-recommendation free chat loop.
 * Chat input remains available below the cards.
 * Any message follows the same 3-tier transport chain as Step 3.
 * This loop continues indefinitely.
 *
 * ──────────────────────────────────────────────────────────────────────
 * SCENARIO B: Returning anonymous user (token exists in storage)
 * ──────────────────────────────────────────────────────────────────────
 *
 * STEP 1 — App opens. Read storage: chatbot_session_token is an existing UUID.
 * Call POST /api/chatbot/session  body: session_token=existing UUID, user_id=null
 * Response: is_returning=true, same session_token, messages=full history array.
 * Display all previous messages immediately in chronological order.
 * Skip greeting and skip quiz entirely — open directly in free-chat mode.
 * Connect Reverb and subscribe to channel. Ready for new messages.
 *
 * ──────────────────────────────────────────────────────────────────────
 * SCENARIO C: Logged-in user
 * ──────────────────────────────────────────────────────────────────────
 *
 * Same as Scenario A or B, but include user_id from auth context:
 * Call POST /api/chatbot/session  body: session_token=null or stored, user_id=101
 * The session is linked to the user from the very start.
 * Quiz flow and chat flow are identical to Scenario A.
 *
 * ──────────────────────────────────────────────────────────────────────
 * SCENARIO D: Anonymous user who logs in during a session
 * ──────────────────────────────────────────────────────────────────────
 *
 * STEP 1 — User chatted anonymously. session_token abc is in storage. user_id was null.
 * STEP 2 — User logs in or registers on any other screen in the app.
 * STEP 3 — Auth context detects user_id changed from null to 101.
 * STEP 4 — App automatically fires (fire-and-forget, requires no user action):
 * POST /api/chatbot/link-answers  body: session_token=abc, user_id=101
 * All quiz answers and conversation history are now owned by user 101.
 * STEP 5 — Any error from this call must be silently ignored.
 * The chat session still works fine on the current device regardless.
 *
 * ──────────────────────────────────────────────────────────────────────
 * SCENARIO E: User dismisses the chatbot
 * ──────────────────────────────────────────────────────────────────────
 *
 * STEP 1 — User taps the close X button on the chatbot panel.
 * Immediately store chatbot_dismissed_at = current ISO timestamp in localStorage.
 * Call POST /api/chatbot/dismiss  body: session_token (fire-and-forget, do not await).
 * Collapse widget to FAB button immediately — do not wait for the API response.
 * Any error must be silently ignored.
 *
 * STEP 2 — Next app open within 24 hours.
 * Check: current time minus chatbot_dismissed_at is less than 24 hours.
 * POST /api/chatbot/session still fires (session init happens silently in background).
 * Widget stays collapsed as FAB. User can tap FAB to manually open the chat.
 *
 * STEP 3 — Next app open after 24 hours have elapsed.
 * Widget auto-opens normally with the 3-second delay.
 *
 * ──────────────────────────────────────────────────────────────────────
 * WIDGET STATES — implement all of these
 * ──────────────────────────────────────────────────────────────────────
 *
 * closed          Nothing visible. Timer has not fired yet.
 * minimized       FAB button only. After dismiss or manual collapse.
 * open            Full chat panel. Normal interactive state.
 * thinking        Typing indicator (3 dots animation) in assistant bubble. Waiting for first token.
 * streaming       Text appearing token by token in assistant bubble. Receiving Reverb or SSE chunks.
 * quiz            Quiz question with chip options rendered. No free-text input shown.
 * loading_recs    Spinner or shimmer cards. Between /answers and /recommendations calls.
 * recommendations Course cards rendered below the assistant message. Input still active.
 *
 * ──────────────────────────────────────────────────────────────────────
 * ERROR HANDLING — required behaviour for each failure case
 * ──────────────────────────────────────────────────────────────────────
 *
 * /chatbot/session fails         Silent retry once. If still fails, hide widget entirely.
 * Reverb connect fails           Silently switch to SSE (Tier 2). No error shown to user.
 * SSE fails                      Silently switch to plain REST (Tier 3). No error shown to user.
 * All 3 message tiers fail       Show error bubble: Could not reach server. Please try again.
 * /chatbot/answers fails         Show a Retry button in the quiz. Do NOT call /recommendations.
 * /chatbot/recommendations fails Show inline message: Could not load courses. You can still chat!
 * /chatbot/dismiss fails         Ignore silently. Widget collapses regardless.
 * /chatbot/link-answers fails    Ignore silently. Chat session still works on current device."
 * )
 */
class ChatbotApiDoc
{
}
