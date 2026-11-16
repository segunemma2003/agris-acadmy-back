<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * Endpoints documented here rather than on their controllers: organisation
 * self-registration, account management, locations, captions, the participant
 * end-of-course check, webhooks, the Finance service API and VR Studio.
 *
 * @OA\SecurityScheme(
 *     securityScheme="academyServiceKey",
 *     type="apiKey",
 *     in="header",
 *     name="X-Academy-Service-Key",
 *     description="Shared secret for server-to-server calls from Agrisiti Finance (ACADEMY_SERVICE_KEY). Never use from a browser or app."
 * )
 *
 * @OA\Tag(name="Account", description="Password reset/change, profile picture and account deletion")
 * @OA\Tag(name="Locations", description="Nigerian states and LGAs (public, cached)")
 * @OA\Tag(name="Captions", description="Lesson video captions, translated on request")
 * @OA\Tag(name="Participant Survey", description="End-of-course and six-month outcome check (participant form Part 5)")
 * @OA\Tag(name="Webhooks", description="Inbound callbacks from Africa's Talking")
 * @OA\Tag(name="Internal (Finance)", description="Server-to-server API used by Agrisiti Finance to verify graduates. Requires the X-Academy-Service-Key header.")
 * @OA\Tag(name="VR Studio", description="Authoring (tutors, facilitators, admins via a Studio handoff token) and the learner VR player")
 * @OA\Tag(name="Realtime", description="Private channel authorisation for Reverb/Pusher")
 */
class PlatformApiDoc
{
    // ── Organisations ───────────────────────────────────────

    /**
     * @OA\Post(
     *     path="/api/organisations/register",
     *     tags={"Organisations"},
     *     summary="Self-register an organisation that needs talent (Nigeria or abroad)",
     *     description="Creates an `organisation` user and a pending organisation profile, and returns a token (the account works immediately). Openings can only be posted, and matched candidates seen, after an admin approves the organisation; the admin is emailed on sign-up and the organisation is emailed on approval. Nigerian organisations must give `state`; others must give `city`. Rate limited to 10 per minute.",
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"contact_name","email","password","password_confirmation","name","organisation_type","country","talent_needs"},
     *         @OA\Property(property="contact_name", type="string", example="Ngozi Obi"),
     *         @OA\Property(property="email", type="string", format="email", example="hr@greenfarms.ng"),
     *         @OA\Property(property="phone", type="string", example="+2348030000000"),
     *         @OA\Property(property="password", type="string", format="password", minLength=8),
     *         @OA\Property(property="password_confirmation", type="string", format="password"),
     *         @OA\Property(property="name", type="string", example="Green Farms Ltd"),
     *         @OA\Property(property="organisation_type", type="string", enum={"farm","agro_enterprise","recruitment_agency","institution","ngo","government","other"}),
     *         @OA\Property(property="talent_needs", type="string", example="5 poultry attendants and 1 farm supervisor in Kano"),
     *         @OA\Property(property="description", type="string"),
     *         @OA\Property(property="sector", type="string", example="Poultry"),
     *         @OA\Property(property="country", type="string", example="Nigeria"),
     *         @OA\Property(property="state", type="string", description="Required when country is Nigeria", example="Kano"),
     *         @OA\Property(property="lga", type="string"),
     *         @OA\Property(property="city", type="string", description="Required when country is not Nigeria"),
     *         @OA\Property(property="website", type="string")
     *     )),
     *     @OA\Response(response=201, description="Registered, pending approval", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean"), @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object", @OA\Property(property="user", ref="#/components/schemas/User"),
     *             @OA\Property(property="organisation", type="object"), @OA\Property(property="token", type="string")))),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError")),
     *     @OA\Response(response=429, description="Too many requests")
     * )
     */
    public function registerOrganisation() {}

    // ── Account ─────────────────────────────────────────────

    /**
     * @OA\Post(
     *     path="/api/forgot-password",
     *     tags={"Account"},
     *     summary="Email a 6-digit password reset code (once per minute)",
     *     @OA\RequestBody(required=true, @OA\JsonContent(required={"email"}, @OA\Property(property="email", type="string", format="email"))),
     *     @OA\Response(response=200, description="Code sent", @OA\JsonContent(ref="#/components/schemas/ApiSuccess")),
     *     @OA\Response(response=404, description="No user with this email", @OA\JsonContent(ref="#/components/schemas/ApiError")),
     *     @OA\Response(response=429, description="Requested less than a minute ago", @OA\JsonContent(ref="#/components/schemas/ApiError")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function forgotPassword() {}

    /**
     * @OA\Post(
     *     path="/api/reset-password",
     *     tags={"Account"},
     *     summary="Set a new password using the emailed 6-digit code",
     *     @OA\RequestBody(required=true, @OA\JsonContent(required={"email","token","password","password_confirmation"},
     *         @OA\Property(property="email", type="string", format="email"),
     *         @OA\Property(property="token", type="string", minLength=6, maxLength=6, example="483920"),
     *         @OA\Property(property="password", type="string", format="password", minLength=8),
     *         @OA\Property(property="password_confirmation", type="string", format="password"))),
     *     @OA\Response(response=200, description="Password reset", @OA\JsonContent(ref="#/components/schemas/ApiSuccess")),
     *     @OA\Response(response=400, description="Invalid or expired code", @OA\JsonContent(ref="#/components/schemas/ApiError")),
     *     @OA\Response(response=404, description="No user with this email", @OA\JsonContent(ref="#/components/schemas/ApiError")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function resetPassword() {}

    /**
     * @OA\Put(
     *     path="/api/user/password",
     *     tags={"Account"},
     *     summary="Change my password",
     *     security={{"sanctumAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(required={"current_password","password","password_confirmation"},
     *         @OA\Property(property="current_password", type="string", format="password"),
     *         @OA\Property(property="password", type="string", format="password", minLength=8),
     *         @OA\Property(property="password_confirmation", type="string", format="password"))),
     *     @OA\Response(response=200, description="Password changed", @OA\JsonContent(ref="#/components/schemas/ApiSuccess")),
     *     @OA\Response(response=400, description="Current password wrong, or new password same as current", @OA\JsonContent(ref="#/components/schemas/ApiError")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function changePassword() {}

    /**
     * @OA\Post(
     *     path="/api/user/profile/avatar",
     *     tags={"Account"},
     *     summary="Upload my profile picture (jpeg/png/jpg/gif, max 2 MB)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(required={"avatar"},
     *         @OA\Property(property="avatar", type="string", format="binary")))),
     *     @OA\Response(response=200, description="Uploaded", @OA\JsonContent(@OA\Property(property="success", type="boolean"), @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object", @OA\Property(property="user", type="object",
     *             @OA\Property(property="id", type="integer"), @OA\Property(property="name", type="string"), @OA\Property(property="email", type="string"), @OA\Property(property="avatar", type="string", format="uri"))))),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function uploadAvatar() {}

    /**
     * @OA\Delete(
     *     path="/api/user/profile/avatar",
     *     tags={"Account"},
     *     summary="Remove my profile picture",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="Removed", @OA\JsonContent(ref="#/components/schemas/ApiSuccess"))
     * )
     */
    public function deleteAvatar() {}

    /**
     * @OA\Delete(
     *     path="/api/user/account",
     *     tags={"Account"},
     *     summary="Permanently delete my account (password required)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(required={"password"}, @OA\Property(property="password", type="string", format="password"))),
     *     @OA\Response(response=200, description="Account deleted", @OA\JsonContent(ref="#/components/schemas/ApiSuccess")),
     *     @OA\Response(response=400, description="Password is incorrect", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function deleteAccount() {}

    // ── Locations ───────────────────────────────────────────

    /**
     * @OA\Get(
     *     path="/api/locations/states",
     *     tags={"Locations"},
     *     summary="Nigerian states (sorted by name)",
     *     @OA\Response(response=200, description="States", @OA\JsonContent(@OA\Property(property="success", type="boolean"),
     *         @OA\Property(property="data", type="array", @OA\Items(type="object", @OA\Property(property="key", type="string", example="kano"), @OA\Property(property="name", type="string", example="Kano")))))
     * )
     */
    public function states() {}

    /**
     * @OA\Get(
     *     path="/api/locations/lgas/{state}",
     *     tags={"Locations"},
     *     summary="LGAs in a state",
     *     @OA\Parameter(name="state", in="path", required=true, description="State key or name", @OA\Schema(type="string", example="Kano")),
     *     @OA\Response(response=200, description="LGA names", @OA\JsonContent(@OA\Property(property="success", type="boolean"), @OA\Property(property="data", type="array", @OA\Items(type="string", example="Dala")))),
     *     @OA\Response(response=404, description="State not found", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function lgas() {}

    // ── Captions ────────────────────────────────────────────

    /**
     * @OA\Get(
     *     path="/api/topics/{topic}/captions",
     *     tags={"Captions"},
     *     summary="Captions for a lesson video in a language (translation starts on first request)",
     *     description="Returns the cues for `language` when ready, plus the status of every caption language: ready | pending | available (will be translated on request) | failed | unavailable | missing. Poll while pending.",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="topic", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="language", in="query", required=false, description="Caption language code (default: source language)", @OA\Schema(type="string", example="ha")),
     *     @OA\Response(response=200, description="Captions and language statuses", @OA\JsonContent(@OA\Property(property="success", type="boolean"), @OA\Property(property="data", type="object"))),
     *     @OA\Response(response=403, description="Not enrolled in this course", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function captions() {}

    // ── Participant survey / dashboard / enrollments ────────

    /**
     * @OA\Get(
     *     path="/api/course-surveys/pending",
     *     tags={"Participant Survey"},
     *     summary="Outcome checks due for me (at completion, and six months after)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="Pending checks", @OA\JsonContent(@OA\Property(property="success", type="boolean"),
     *         @OA\Property(property="data", type="array", @OA\Items(type="object",
     *             @OA\Property(property="enrollment_id", type="integer"), @OA\Property(property="stage", type="string", enum={"completion","six_month"}),
     *             @OA\Property(property="course", type="object", @OA\Property(property="id", type="integer"), @OA\Property(property="title", type="string")),
     *             @OA\Property(property="completed_course", type="boolean")))))
     * )
     */
    public function pendingSurveys() {}

    /**
     * @OA\Post(
     *     path="/api/course-surveys",
     *     tags={"Participant Survey"},
     *     summary="Submit an outcome check for one of my enrollments",
     *     security={{"sanctumAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"enrollment_id","stage","completed_course","work_change","started_enterprise","income_band","rating","used_learning","would_recommend","declaration_name","declaration"},
     *         @OA\Property(property="enrollment_id", type="integer"),
     *         @OA\Property(property="stage", type="string", enum={"completion","six_month"}),
     *         @OA\Property(property="completed_course", type="boolean"),
     *         @OA\Property(property="work_change", type="string", enum={"same","new_job","better_pay","started_enterprise","grew_business","further_study"}),
     *         @OA\Property(property="started_enterprise", type="boolean"),
     *         @OA\Property(property="feels_respected", type="string", enum={"yes","no","prefer_not_to_say"}),
     *         @OA\Property(property="income_band", type="string", enum={"none","under_30000","30000_70000","70001_150000","150001_300000","300001_500000","above_500000"}),
     *         @OA\Property(property="rating", type="string", enum={"excellent","good","fair","poor"}),
     *         @OA\Property(property="used_learning", type="string", enum={"yes","planning","no"}),
     *         @OA\Property(property="would_recommend", type="boolean"),
     *         @OA\Property(property="improvement", type="string", maxLength=2000),
     *         @OA\Property(property="declaration_name", type="string"),
     *         @OA\Property(property="declaration", type="boolean", example=true, description="Must be accepted")
     *     )),
     *     @OA\Response(response=200, description="Saved", @OA\JsonContent(@OA\Property(property="success", type="boolean"), @OA\Property(property="message", type="string"), @OA\Property(property="data", type="object"))),
     *     @OA\Response(response=404, description="Enrollment not found (or not mine)", @OA\JsonContent(ref="#/components/schemas/ApiError")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function storeSurvey() {}

    /**
     * @OA\Get(
     *     path="/api/dashboard/topic-tests",
     *     tags={"Dashboard"},
     *     summary="Active lesson quizzes in my enrolled (published) courses",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="Quizzes", @OA\JsonContent(@OA\Property(property="success", type="boolean"),
     *         @OA\Property(property="data", type="array", @OA\Items(type="object",
     *             @OA\Property(property="test_id", type="integer"), @OA\Property(property="title", type="string"), @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="course_id", type="integer"), @OA\Property(property="course_title", type="string"),
     *             @OA\Property(property="module_id", type="integer"), @OA\Property(property="module_title", type="string"),
     *             @OA\Property(property="topic_id", type="integer"), @OA\Property(property="topic_title", type="string"),
     *             @OA\Property(property="questions_count", type="integer"), @OA\Property(property="passing_score", type="integer"),
     *             @OA\Property(property="time_limit_minutes", type="integer", nullable=true), @OA\Property(property="max_attempts", type="integer", nullable=true))))),
     *     @OA\Response(response=403, description="Not available for organisation accounts", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function topicTests() {}

    /**
     * @OA\Put(
     *     path="/api/enrollments/{enrollment}/signup-answers",
     *     tags={"Enrollments"},
     *     summary="Save how I'll take this course (participant form Part 4)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="enrollment", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="learning_type", type="string", enum={"course","workshop","mentoring","apprenticeship"}),
     *         @OA\Property(property="learning_mode", type="string", enum={"online","in_person","blended"}),
     *         @OA\Property(property="heard_from", type="string", enum={"whatsapp","social_media","radio_tv","friend_family","other"}),
     *         @OA\Property(property="partner_hub", type="string", maxLength=255))),
     *     @OA\Response(response=200, description="Saved", @OA\JsonContent(@OA\Property(property="success", type="boolean"), @OA\Property(property="message", type="string"), @OA\Property(property="data", type="object"))),
     *     @OA\Response(response=404, description="Not my enrollment", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function signupAnswers() {}

    // ── Webhooks ────────────────────────────────────────────

    /**
     * @OA\Post(
     *     path="/api/sms/inbound",
     *     tags={"Webhooks"},
     *     summary="Africa's Talking inbound SMS (handles STOP opt-outs from inactivity nudges)",
     *     description="Accepts form or JSON fields `from` (or `phoneNumber`) and `text` (or `message`). Rate limited to 60 per minute.",
     *     @OA\RequestBody(required=true, @OA\JsonContent(@OA\Property(property="from", type="string", example="+2348030000000"), @OA\Property(property="text", type="string", example="STOP"))),
     *     @OA\Response(response=200, description="Handled", @OA\JsonContent(@OA\Property(property="success", type="boolean"), @OA\Property(property="opted_out", type="boolean"))),
     *     @OA\Response(response=422, description="Missing from/text", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function smsInbound() {}

    // ── Internal (Finance) ──────────────────────────────────

    /**
     * @OA\Post(
     *     path="/api/internal/learners/completion-status",
     *     tags={"Internal (Finance)"},
     *     summary="Has this learner completed courses / earned certificates? (by email or phone)",
     *     security={{"academyServiceKey":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(description="Give email or phone", @OA\Property(property="email", type="string", format="email"), @OA\Property(property="phone", type="string", maxLength=30))),
     *     @OA\Response(response=200, description="Completion status", @OA\JsonContent(@OA\Property(property="success", type="boolean"), @OA\Property(property="data", type="object"))),
     *     @OA\Response(response=401, description="Missing or wrong service key"),
     *     @OA\Response(response=422, description="Neither email nor phone given", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function internalCompletionStatus() {}

    /**
     * @OA\Post(
     *     path="/api/internal/learners/lookup",
     *     tags={"Internal (Finance)"},
     *     summary="Look up a learner's profile summary (by email or phone)",
     *     security={{"academyServiceKey":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(description="Give email or phone", @OA\Property(property="email", type="string", format="email"), @OA\Property(property="phone", type="string", maxLength=30))),
     *     @OA\Response(response=200, description="Learner summary (`data` is null when no learner matches)", @OA\JsonContent(@OA\Property(property="success", type="boolean"), @OA\Property(property="data", type="object", nullable=true))),
     *     @OA\Response(response=401, description="Missing or wrong service key"),
     *     @OA\Response(response=422, description="Neither email nor phone given", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function internalLookup() {}

    /**
     * @OA\Post(
     *     path="/api/internal/learners/certificate",
     *     tags={"Internal (Finance)"},
     *     summary="Verify a certificate number and return its learner and course",
     *     security={{"academyServiceKey":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(required={"certificate_number"}, @OA\Property(property="certificate_number", type="string", maxLength=64))),
     *     @OA\Response(response=200, description="Certificate details (`data` is null when the number is unknown)", @OA\JsonContent(@OA\Property(property="success", type="boolean"), @OA\Property(property="data", type="object", nullable=true))),
     *     @OA\Response(response=401, description="Missing or wrong service key")
     * )
     */
    public function internalCertificate() {}

    // ── VR Studio ───────────────────────────────────────────

    /**
     * @OA\Post(
     *     path="/api/vr-studio/handoff/exchange",
     *     tags={"VR Studio"},
     *     summary="Exchange a one-time Studio handoff token (from the admin/tutor panel) for a Sanctum session",
     *     @OA\RequestBody(required=true, @OA\JsonContent(required={"token"}, @OA\Property(property="token", type="string", minLength=20))),
     *     @OA\Response(response=200, description="Studio session (token + experience)", @OA\JsonContent(@OA\Property(property="message", type="string"), @OA\Property(property="data", type="object"))),
     *     @OA\Response(response=401, description="Invalid or expired handoff token")
     * )
     */
    public function vrExchange() {}

    /**
     * @OA\Get(
     *     path="/api/vr-studio/experiences",
     *     tags={"VR Studio"},
     *     summary="VR experiences I can edit (admins: all; tutors: their courses; facilitators: courses in their location)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="Experiences and the current author", @OA\JsonContent(@OA\Property(property="data", type="array", @OA\Items(type="object")), @OA\Property(property="user", type="object"))),
     *     @OA\Response(response=403, description="Only tutors, facilitators and admins can author VR")
     * )
     */
    public function vrIndex() {}

    /**
     * @OA\Get(
     *     path="/api/vr-studio/experiences/{experience}",
     *     tags={"VR Studio"},
     *     summary="One VR experience with its scene (a default scene if empty)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="experience", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Experience", @OA\JsonContent(@OA\Property(property="data", type="object"))),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function vrShow() {}

    /**
     * @OA\Put(
     *     path="/api/vr-studio/experiences/{experience}",
     *     tags={"VR Studio"},
     *     summary="Save a VR experience (details and scene)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="experience", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="title", type="string", maxLength=255),
     *         @OA\Property(property="description", type="string"),
     *         @OA\Property(property="instructions", type="string"),
     *         @OA\Property(property="cta_label", type="string", maxLength=100),
     *         @OA\Property(property="module_id", type="integer"),
     *         @OA\Property(property="scene", type="object", description="Scene JSON"),
     *         @OA\Property(property="duration_minutes", type="integer", minimum=0))),
     *     @OA\Response(response=200, description="Saved", @OA\JsonContent(@OA\Property(property="data", type="object"))),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function vrUpdate() {}

    /**
     * @OA\Post(
     *     path="/api/vr-studio/experiences/{experience}/publish",
     *     tags={"VR Studio"},
     *     summary="Publish a VR experience so learners can launch it",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="experience", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Published", @OA\JsonContent(@OA\Property(property="data", type="object"))),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function vrPublish() {}

    /**
     * @OA\Post(
     *     path="/api/vr-studio/experiences/{experience}/generate",
     *     tags={"VR Studio"},
     *     summary="Generate a scene from a text prompt (AI)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="experience", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(required={"prompt"}, @OA\Property(property="prompt", type="string", minLength=8, maxLength=4000))),
     *     @OA\Response(response=200, description="Generated scene", @OA\JsonContent(@OA\Property(property="data", type="object"))),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function vrGenerate() {}

    /**
     * @OA\Post(
     *     path="/api/vr-studio/experiences/{experience}/assets",
     *     tags={"VR Studio"},
     *     summary="Upload an image asset for a scene (jpg/jpeg/png/webp, max 20 MB)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="experience", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(required={"file"}, @OA\Property(property="file", type="string", format="binary")))),
     *     @OA\Response(response=200, description="Uploaded asset URL", @OA\JsonContent(@OA\Property(property="data", type="object"))),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function vrUploadAsset() {}

    /**
     * @OA\Get(
     *     path="/api/vr-studio/play/{slug}",
     *     tags={"VR Studio"},
     *     summary="Player payload for a published VR experience",
     *     description="Auth optional (Bearer token or query token); experiences tied to a course require the learner to be enrolled. Rate limited to 60 per minute.",
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Scene and experience details", @OA\JsonContent(@OA\Property(property="data", type="object",
     *         @OA\Property(property="id", type="integer"), @OA\Property(property="title", type="string"), @OA\Property(property="instructions", type="string"),
     *         @OA\Property(property="cta_label", type="string"), @OA\Property(property="course_id", type="integer"), @OA\Property(property="course_title", type="string"),
     *         @OA\Property(property="scene", type="object")))),
     *     @OA\Response(response=403, description="Must be enrolled"),
     *     @OA\Response(response=404, description="Not found or not published")
     * )
     */
    public function vrPlay() {}

    /**
     * @OA\Post(
     *     path="/api/vr-studio/experiences/{experience}/complete",
     *     tags={"VR Studio"},
     *     summary="Record that I completed a VR experience",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="experience", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Completion recorded", @OA\JsonContent(@OA\Property(property="data", type="object"))),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Not enrolled")
     * )
     */
    public function vrComplete() {}

    // ── Realtime ────────────────────────────────────────────

    /**
     * @OA\Post(
     *     path="/api/broadcasting/auth",
     *     tags={"Realtime"},
     *     summary="Authorise a private message channel (Reverb/Pusher)",
     *     description="Channel format `private-course.{courseId}.user.{userId}`; userId must be the caller. courseId 0 = location-based messages; otherwise the caller must be enrolled in or teach the course. Events: MessageSent, MessageRead.",
     *     security={{"sanctumAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(required={"channel_name","socket_id"},
     *         @OA\Property(property="channel_name", type="string", example="private-course.12.user.345"),
     *         @OA\Property(property="socket_id", type="string", example="123.456"))),
     *     @OA\Response(response=200, description="Pusher auth signature", @OA\JsonContent(@OA\Property(property="auth", type="string"))),
     *     @OA\Response(response=400, description="Invalid channel name"),
     *     @OA\Response(response=403, description="Not your channel / not in this course")
     * )
     */
    public function broadcastingAuth() {}
}
