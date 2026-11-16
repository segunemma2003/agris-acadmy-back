<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * Auto-generated learner CVs and employer job matching.
 *
 * @OA\Tag(
 *     name="CV & Job Matching",
 *     description="Flow: (1) a learner completes a course → their CV is (re)built automatically and stored privately.
 * (2) An approved organisation posts an opening (POST /api/org/slots) and opens GET /api/org/slots/{slot}/matches — learners with a CV who are open to work are scored 0–100 with reasons.
 * (3) The employer selects a candidate (POST /api/org/matches/{match}/select) → the learner is notified in-app and by email.
 * (4) The learner accepts or declines (POST /api/career/offers/{match}/respond). Accepting creates an accepted placement (apprenticeship) and only then reveals the learner's email/phone to the employer.
 *
 * Score breakdown (max 100): subject fit 35, skills overlap 15, required course 10 (if the opening requires a course, learners without it are excluded), assessment performance 15, courses/placements done 10, same state 7 + same LGA 3, looking for work 5."
 * )
 *
 * @OA\Schema(
 *     schema="CvData",
 *     description="Contents of an auto-generated CV. `contact` is omitted for employers until the learner accepts their offer; `gender` is never shown to employers.",
 *     @OA\Property(property="name", type="string", example="Amina Bello"),
 *     @OA\Property(property="headline", type="string", example="Poultry · Agrisiti Academy graduate · Dala, Kano"),
 *     @OA\Property(property="summary", type="string", example="Completed 2 Agrisiti Academy courses in Livestock with an average assessment score of 82%. Goal: secure employment in an existing agro-enterprise."),
 *     @OA\Property(property="location", type="object", @OA\Property(property="state", type="string", nullable=true), @OA\Property(property="lga", type="string", nullable=true), @OA\Property(property="country", type="string", example="Nigeria")),
 *     @OA\Property(property="contact", type="object", nullable=true, @OA\Property(property="email", type="string"), @OA\Property(property="phone", type="string", nullable=true)),
 *     @OA\Property(property="education", type="string", nullable=true),
 *     @OA\Property(property="work", type="object", @OA\Property(property="status", type="string"), @OA\Property(property="job_title", type="string"), @OA\Property(property="employment_type", type="string"), @OA\Property(property="since", type="string", example="Mar 2022"), @OA\Property(property="organisation", type="string"), @OA\Property(property="sector", type="string")),
 *     @OA\Property(property="interests", type="object", @OA\Property(property="value_chain", type="string"), @OA\Property(property="commodity", type="string"), @OA\Property(property="objective", type="string")),
 *     @OA\Property(property="skills", type="array", @OA\Items(type="string"), example={"Livestock", "Brooding and day-old chick care", "Feed and water management"}),
 *     @OA\Property(property="courses", type="array", @OA\Items(type="object",
 *         @OA\Property(property="id", type="integer"), @OA\Property(property="title", type="string"), @OA\Property(property="category", type="string", nullable=true),
 *         @OA\Property(property="level", type="string", nullable=true), @OA\Property(property="completed_at", type="string", format="date", nullable=true), @OA\Property(property="quiz_average", type="integer", nullable=true))),
 *     @OA\Property(property="certificates", type="array", @OA\Items(type="object",
 *         @OA\Property(property="course", type="string"), @OA\Property(property="number", type="string"), @OA\Property(property="issued_date", type="string", format="date"), @OA\Property(property="verify_url", type="string", format="uri"))),
 *     @OA\Property(property="placements", type="array", @OA\Items(type="object",
 *         @OA\Property(property="organisation", type="string"), @OA\Property(property="role", type="string"), @OA\Property(property="status", type="string", enum={"accepted","completed"}),
 *         @OA\Property(property="start", type="string", format="date", nullable=true), @OA\Property(property="end", type="string", format="date", nullable=true), @OA\Property(property="skills", type="array", @OA\Items(type="string")))),
 *     @OA\Property(property="performance", type="object",
 *         @OA\Property(property="courses_completed", type="integer"), @OA\Property(property="average_quiz_score", type="integer", nullable=true),
 *         @OA\Property(property="lessons_completed", type="integer"), @OA\Property(property="certificates", type="integer")),
 *     @OA\Property(property="generated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="LearnerCv",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="user_id", type="integer"),
 *     @OA\Property(property="data", ref="#/components/schemas/CvData"),
 *     @OA\Property(property="open_to_work", type="boolean", description="When false the CV is not matched to employers"),
 *     @OA\Property(property="generated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="JobOffer",
 *     @OA\Property(property="id", type="integer", description="Job match id"),
 *     @OA\Property(property="status", type="string", enum={"selected","accepted","declined"}),
 *     @OA\Property(property="score", type="integer", example=78),
 *     @OA\Property(property="reasons", type="array", @OA\Items(type="string"), example={"Trained in poultry", "Lives in Kano", "Looking for work"}),
 *     @OA\Property(property="selected_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="responded_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="slot", type="object", nullable=true,
 *         @OA\Property(property="id", type="integer"), @OA\Property(property="title", type="string"), @OA\Property(property="description", type="string"),
 *         @OA\Property(property="state", type="string", nullable=true), @OA\Property(property="lga", type="string", nullable=true), @OA\Property(property="duration", type="string", nullable=true),
 *         @OA\Property(property="organisation", type="object", @OA\Property(property="id", type="integer"), @OA\Property(property="name", type="string"),
 *             @OA\Property(property="country", type="string"), @OA\Property(property="state", type="string", nullable=true), @OA\Property(property="city", type="string", nullable=true), @OA\Property(property="website", type="string", nullable=true)))
 * )
 *
 * @OA\Schema(
 *     schema="CandidateMatch",
 *     @OA\Property(property="id", type="integer", description="Job match id"),
 *     @OA\Property(property="score", type="integer", minimum=0, maximum=100, example=82),
 *     @OA\Property(property="reasons", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="status", type="string", enum={"suggested","selected","accepted","declined","dismissed"}),
 *     @OA\Property(property="selected_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="candidate", type="object", @OA\Property(property="name", type="string"), @OA\Property(property="avatar", type="string", nullable=true),
 *         @OA\Property(property="state", type="string", nullable=true), @OA\Property(property="lga", type="string", nullable=true)),
 *     @OA\Property(property="cv", ref="#/components/schemas/CvData")
 * )
 */
class CareerMatchApiDoc
{
    /**
     * @OA\Get(
     *     path="/api/cv",
     *     tags={"CV & Job Matching"},
     *     summary="Get my auto-generated CV (built on first request if it doesn't exist yet)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="The learner's CV", @OA\JsonContent(@OA\Property(property="success", type="boolean"), @OA\Property(property="data", ref="#/components/schemas/LearnerCv"))),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function myCv() {}

    /**
     * @OA\Post(
     *     path="/api/cv/refresh",
     *     tags={"CV & Job Matching"},
     *     summary="Rebuild my CV now from my latest profile, courses and certificates (also happens automatically on course completion)",
     *     description="Rate limited to 10 per minute.",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="Rebuilt CV", @OA\JsonContent(@OA\Property(property="success", type="boolean"), @OA\Property(property="data", ref="#/components/schemas/LearnerCv"), @OA\Property(property="message", type="string"))),
     *     @OA\Response(response=429, description="Too many requests")
     * )
     */
    public function refreshCv() {}

    /**
     * @OA\Put(
     *     path="/api/cv/settings",
     *     tags={"CV & Job Matching"},
     *     summary="Choose whether employers can find me (open to work)",
     *     description="Turning it off removes the learner from pending (suggested) matches immediately. Offers already sent stay.",
     *     security={{"sanctumAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(required={"open_to_work"}, @OA\Property(property="open_to_work", type="boolean", example=true))),
     *     @OA\Response(response=200, description="Updated CV settings", @OA\JsonContent(@OA\Property(property="success", type="boolean"), @OA\Property(property="data", ref="#/components/schemas/LearnerCv"))),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function updateCvSettings() {}

    /**
     * @OA\Get(
     *     path="/api/cv/pdf",
     *     tags={"CV & Job Matching"},
     *     summary="Download my CV as a PDF",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="PDF file (Content-Disposition: attachment)", @OA\MediaType(mediaType="application/pdf", @OA\Schema(type="string", format="binary")))
     * )
     */
    public function myCvPdf() {}

    /**
     * @OA\Get(
     *     path="/api/career/offers",
     *     tags={"CV & Job Matching"},
     *     summary="Job offers from employers who selected me (pending first)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="Offers", @OA\JsonContent(@OA\Property(property="success", type="boolean"), @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/JobOffer"))))
     * )
     */
    public function myOffers() {}

    /**
     * @OA\Post(
     *     path="/api/career/offers/{match}/respond",
     *     tags={"CV & Job Matching"},
     *     summary="Accept or decline a job offer",
     *     description="Accept → creates/updates an apprenticeship with status `accepted` (linked to the learner's certificate) and shares the learner's email/phone with the employer. The employer is notified either way.",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="match", in="path", required=true, description="Job match id (JobOffer.id)", @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(required={"response"}, @OA\Property(property="response", type="string", enum={"accept","decline"}))),
     *     @OA\Response(response=200, description="Answer recorded", @OA\JsonContent(@OA\Property(property="success", type="boolean"), @OA\Property(property="message", type="string"), @OA\Property(property="data", type="object"))),
     *     @OA\Response(response=400, description="Offer already answered", @OA\JsonContent(ref="#/components/schemas/ApiError")),
     *     @OA\Response(response=403, description="Not your offer", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function respond() {}

    /**
     * @OA\Get(
     *     path="/api/org/slots/{slot}/matches",
     *     tags={"CV & Job Matching"},
     *     summary="Learners matched to one of my openings, best fit first (organisation)",
     *     description="Scores are recomputed when older than 60 minutes, when the opening was edited, or with `refresh=1`. Order: accepted, offer sent, suggested, declined, not a fit; then by score. `cv.contact` is included only for candidates who accepted.",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="slot", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="refresh", in="query", required=false, description="Force re-scoring now", @OA\Schema(type="boolean")),
     *     @OA\Response(response=200, description="Matches", @OA\JsonContent(@OA\Property(property="success", type="boolean"), @OA\Property(property="data", type="object",
     *         @OA\Property(property="slot", type="object", @OA\Property(property="id", type="integer"), @OA\Property(property="title", type="string")),
     *         @OA\Property(property="matches", type="array", @OA\Items(ref="#/components/schemas/CandidateMatch"))))),
     *     @OA\Response(response=403, description="Not the owning organisation, or organisation not yet approved", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function slotMatches() {}

    /**
     * @OA\Post(
     *     path="/api/org/matches/{match}/select",
     *     tags={"CV & Job Matching"},
     *     summary="Select a candidate (send them an offer)",
     *     description="Works on suggested or previously passed candidates. The learner is notified in-app and by email and can accept or decline.",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="match", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Candidate selected", @OA\JsonContent(@OA\Property(property="success", type="boolean"), @OA\Property(property="message", type="string"), @OA\Property(property="data", type="object"))),
     *     @OA\Response(response=400, description="Already selected", @OA\JsonContent(ref="#/components/schemas/ApiError")),
     *     @OA\Response(response=403, description="Not the owning organisation, or not approved", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function select() {}

    /**
     * @OA\Post(
     *     path="/api/org/matches/{match}/dismiss",
     *     tags={"CV & Job Matching"},
     *     summary="Mark a suggested candidate as not a fit",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="match", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Candidate passed", @OA\JsonContent(@OA\Property(property="success", type="boolean"), @OA\Property(property="data", type="object"))),
     *     @OA\Response(response=400, description="Only suggested candidates can be passed on", @OA\JsonContent(ref="#/components/schemas/ApiError")),
     *     @OA\Response(response=403, description="Not the owning organisation, or not approved", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function dismiss() {}

    /**
     * @OA\Get(
     *     path="/api/org/matches/{match}/cv",
     *     tags={"CV & Job Matching"},
     *     summary="Download a matched candidate's CV as PDF (contact details only after they accept)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="match", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="PDF file", @OA\MediaType(mediaType="application/pdf", @OA\Schema(type="string", format="binary"))),
     *     @OA\Response(response=403, description="Not the owning organisation, or not approved", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function matchCvPdf() {}
}
