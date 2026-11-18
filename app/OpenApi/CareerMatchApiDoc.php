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
 *     @OA\Property(property="template", type="string", enum={"nigeria","nigeria_public","west_africa","uk","us","europass","gulf"}),
 *     @OA\Property(property="template_label", type="string", example="Nigeria (standard CV)"),
 *     @OA\Property(property="summary_title", type="string", example="Career objective"),
 *     @OA\Property(property="section_order", type="array", @OA\Items(type="string", enum={"summary","experience","training","education","skills","languages","achievements","certificates","interests","references"})),
 *     @OA\Property(property="name", type="string", example="Amina Bello"),
 *     @OA\Property(property="headline", type="string", example="Poultry · Agrisiti Academy graduate · Dala, Kano"),
 *     @OA\Property(property="summary", type="string", example="Completed 2 Agrisiti Academy courses in Livestock with an average assessment score of 82%. Goal: secure employment in an existing agro-enterprise."),
 *     @OA\Property(property="location", type="object", @OA\Property(property="state", type="string", nullable=true), @OA\Property(property="lga", type="string", nullable=true), @OA\Property(property="country", type="string", example="Nigeria")),
 *     @OA\Property(property="contact", type="object", nullable=true, @OA\Property(property="email", type="string"), @OA\Property(property="phone", type="string", nullable=true),
 *         @OA\Property(property="location", type="string", nullable=true), @OA\Property(property="linkedin", type="string", nullable=true)),
 *     @OA\Property(property="personal", type="object", description="Only the personal details the template uses (Nigeria government/academic: DOB, sex, marital status, nationality, state of origin, LGA; Gulf: nationality, visa status, DOB; UK/US/Nigeria private: none). Hidden from employers until the learner accepts.",
 *         @OA\Property(property="date_of_birth", type="string", example="4 May 2000"), @OA\Property(property="gender", type="string"),
 *         @OA\Property(property="nationality", type="string"), @OA\Property(property="state_of_origin", type="string")),
 *     @OA\Property(property="photo", type="string", nullable=true, description="Profile photo URL when the template uses a photo (Nigeria, West Africa, Gulf)"),
 *     @OA\Property(property="experience", type="array", description="Past work entered by the learner, current job first", @OA\Items(ref="#/components/schemas/CvExperience")),
 *     @OA\Property(property="education_history", type="array", @OA\Items(ref="#/components/schemas/CvEducation")),
 *     @OA\Property(property="languages", type="array", @OA\Items(type="object", @OA\Property(property="language", type="string"), @OA\Property(property="level", type="string", example="B2"))),
 *     @OA\Property(property="achievements", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="references", description="List of referees (Nigeria, West Africa, Gulf), the string `on_request` (UK, Europass, or none given), or null (US/Canada)",
 *         oneOf={@OA\Schema(type="array", @OA\Items(ref="#/components/schemas/CvReference")), @OA\Schema(type="string", enum={"on_request"})}, nullable=true),
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
 *     schema="CvExperience",
 *     required={"title"},
 *     @OA\Property(property="title", type="string", maxLength=150, example="Poultry attendant"),
 *     @OA\Property(property="organisation", type="string", maxLength=150, example="Kano Birds Ltd"),
 *     @OA\Property(property="location", type="string", maxLength=150, example="Kano"),
 *     @OA\Property(property="start", type="string", example="2021-03", description="YYYY-MM"),
 *     @OA\Property(property="end", type="string", nullable=true, example="2023-06", description="YYYY-MM"),
 *     @OA\Property(property="current", type="boolean", description="Still in this job"),
 *     @OA\Property(property="description", type="string", maxLength=2000)
 * )
 *
 * @OA\Schema(
 *     schema="CvEducation",
 *     required={"qualification"},
 *     @OA\Property(property="qualification", type="string", maxLength=150, example="SSCE"),
 *     @OA\Property(property="institution", type="string", maxLength=150),
 *     @OA\Property(property="location", type="string", maxLength=150),
 *     @OA\Property(property="start", type="string", example="2014-09", description="YYYY-MM"),
 *     @OA\Property(property="end", type="string", example="2017-07", description="YYYY-MM"),
 *     @OA\Property(property="details", type="string", maxLength=1000)
 * )
 *
 * @OA\Schema(
 *     schema="CvReference",
 *     required={"name"},
 *     @OA\Property(property="name", type="string", maxLength=120), @OA\Property(property="role", type="string", maxLength=120),
 *     @OA\Property(property="organisation", type="string", maxLength=150), @OA\Property(property="email", type="string", format="email"),
 *     @OA\Property(property="phone", type="string", maxLength=30)
 * )
 *
 * @OA\Schema(
 *     schema="CvDetails",
 *     description="What the learner types in themselves. Lists replace what was saved before.",
 *     @OA\Property(property="headline", type="string", maxLength=150),
 *     @OA\Property(property="summary", type="string", maxLength=2000, description="Personal statement / career objective; replaces the automatic summary"),
 *     @OA\Property(property="phone", type="string", maxLength=30), @OA\Property(property="location", type="string", maxLength=150),
 *     @OA\Property(property="linkedin", type="string", maxLength=255), @OA\Property(property="nationality", type="string", maxLength=100),
 *     @OA\Property(property="state_of_origin", type="string", maxLength=100),
 *     @OA\Property(property="lga", type="string", maxLength=100),
 *     @OA\Property(property="marital_status", type="string", enum={"single","married","divorced","widowed","prefer_not_to_say"}, description="Only shown by the Nigeria government/academic template"),
 *     @OA\Property(property="visa_status", type="string", maxLength=120, example="UAE residence visa (transferable)", description="Shown by the Gulf template"),
 *     @OA\Property(property="no_experience", type="boolean", description="Learner ticked 'I don't have work experience yet' (the app requires a job or this tick before preview)"),
 *     @OA\Property(property="experiences", type="array", maxItems=20, @OA\Items(ref="#/components/schemas/CvExperience")),
 *     @OA\Property(property="education", type="array", maxItems=10, @OA\Items(ref="#/components/schemas/CvEducation")),
 *     @OA\Property(property="skills", type="array", maxItems=30, @OA\Items(type="string", maxLength=60)),
 *     @OA\Property(property="languages", type="array", maxItems=10, @OA\Items(type="object", required={"language"}, @OA\Property(property="language", type="string"), @OA\Property(property="level", type="string"))),
 *     @OA\Property(property="achievements", type="array", maxItems=15, @OA\Items(type="string", maxLength=300)),
 *     @OA\Property(property="references", type="array", maxItems=5, @OA\Items(ref="#/components/schemas/CvReference"))
 * )
 *
 * @OA\Schema(
 *     schema="CvTemplate",
 *     @OA\Property(property="key", type="string", example="uk"),
 *     @OA\Property(property="label", type="string", example="United Kingdom CV"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="countries", type="array", @OA\Items(type="string"), description="Countries this template suits"),
 *     @OA\Property(property="photo", type="boolean"),
 *     @OA\Property(property="personal", type="array", @OA\Items(type="string", enum={"date_of_birth","gender","marital_status","nationality","state_of_origin","lga","visa_status"})),
 *     @OA\Property(property="references", type="string", enum={"list","on_request","none"}),
 *     @OA\Property(property="summary_title", type="string"),
 *     @OA\Property(property="order", type="array", @OA\Items(type="string"))
 * )
 *
 * @OA\Schema(
 *     schema="LearnerCv",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="user_id", type="integer"),
 *     @OA\Property(property="data", ref="#/components/schemas/CvData"),
 *     @OA\Property(property="details", ref="#/components/schemas/CvDetails", nullable=true),
 *     @OA\Property(property="template", type="string", enum={"nigeria","nigeria_public","west_africa","uk","us","europass","gulf"}),
 *     @OA\Property(property="target_country", type="string", nullable=true),
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
     *     @OA\Response(response=403, description="CV locked: the learner hasn't filled the participant form yet. errors.missing lists the field keys, errors.labels their names; code=profile_incomplete. Applies to every /api/cv endpoint.", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=false),
     *         @OA\Property(property="code", type="string", example="profile_incomplete"),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="errors", type="object",
     *             @OA\Property(property="missing", type="array", @OA\Items(type="string"), example={"income_band","value_chain_track"}),
     *             @OA\Property(property="labels", type="array", @OA\Items(type="string"), example={"Monthly income","Value chain track"})))),
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
     *     @OA\Response(response=403, description="CV locked: the learner hasn't filled the participant form yet. errors.missing lists the field keys, errors.labels their names; code=profile_incomplete. Applies to every /api/cv endpoint.", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=false),
     *         @OA\Property(property="code", type="string", example="profile_incomplete"),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="errors", type="object",
     *             @OA\Property(property="missing", type="array", @OA\Items(type="string"), example={"income_band","value_chain_track"}),
     *             @OA\Property(property="labels", type="array", @OA\Items(type="string"), example={"Monthly income","Value chain track"})))),
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
     *     @OA\Response(response=403, description="CV locked: the learner hasn't filled the participant form yet. errors.missing lists the field keys, errors.labels their names; code=profile_incomplete. Applies to every /api/cv endpoint.", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=false),
     *         @OA\Property(property="code", type="string", example="profile_incomplete"),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="errors", type="object",
     *             @OA\Property(property="missing", type="array", @OA\Items(type="string"), example={"income_band","value_chain_track"}),
     *             @OA\Property(property="labels", type="array", @OA\Items(type="string"), example={"Monthly income","Value chain track"})))),
     *     @OA\Response(response=200, description="Updated CV settings", @OA\JsonContent(@OA\Property(property="success", type="boolean"), @OA\Property(property="data", ref="#/components/schemas/LearnerCv"))),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function updateCvSettings() {}

    /**
     * @OA\Get(
     *     path="/api/cv/templates",
     *     tags={"CV & Job Matching"},
     *     summary="CV templates by country convention (Nigeria, West/East Africa, UK, US/Canada, Europass, Gulf)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="Templates", @OA\JsonContent(@OA\Property(property="success", type="boolean"), @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CvTemplate"))))
     * )
     */
    public function templates() {}

    /**
     * @OA\Put(
     *     path="/api/cv/details",
     *     tags={"CV & Job Matching"},
     *     summary="Save my own CV details (past experience, education, skills, languages, achievements, referees) and template",
     *     description="Everything is optional; send only what changed. `details` is saved as a whole (lists replace the previous lists). The CV and its PDF are rebuilt straight away. Past experience also feeds employer job matching.",
     *     security={{"sanctumAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="template", type="string", enum={"nigeria","nigeria_public","west_africa","uk","us","europass","gulf"}),
     *         @OA\Property(property="target_country", type="string", example="United Kingdom"),
     *         @OA\Property(property="details", ref="#/components/schemas/CvDetails"))),
     *     @OA\Response(response=403, description="CV locked: the learner hasn't filled the participant form yet. errors.missing lists the field keys, errors.labels their names; code=profile_incomplete. Applies to every /api/cv endpoint.", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=false),
     *         @OA\Property(property="code", type="string", example="profile_incomplete"),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="errors", type="object",
     *             @OA\Property(property="missing", type="array", @OA\Items(type="string"), example={"income_band","value_chain_track"}),
     *             @OA\Property(property="labels", type="array", @OA\Items(type="string"), example={"Monthly income","Value chain track"})))),
     *     @OA\Response(response=200, description="Updated CV", @OA\JsonContent(@OA\Property(property="success", type="boolean"), @OA\Property(property="message", type="string"), @OA\Property(property="data", ref="#/components/schemas/LearnerCv"))),
     *     @OA\Response(response=422, description="Validation error (e.g. dates must be YYYY-MM)", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function updateCvDetails() {}

    /**
     * @OA\Post(
     *     path="/api/cv/suggest",
     *     tags={"CV & Job Matching"},
     *     summary="Ask Claude to draft CV wording (headline, summary, a job description, achievements or skills)",
     *     description="Uses the learner's training record plus the editor's current (unsaved) `details`. Nothing is saved — the learner reviews, edits and then saves with PUT /api/cv/details. Claude is told not to invent facts; missing numbers come back as [placeholders]. Rate limited to 20 requests a minute.",
     *     security={{"sanctumAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(required={"field"},
     *         @OA\Property(property="field", type="string", enum={"headline","summary","experience","achievements","skills"}),
     *         @OA\Property(property="template", type="string", enum={"nigeria","nigeria_public","west_africa","uk","us","europass","gulf"}),
     *         @OA\Property(property="target_country", type="string", example="Nigeria"),
     *         @OA\Property(property="details", ref="#/components/schemas/CvDetails"),
     *         @OA\Property(property="index", type="integer", description="For field=experience: which item in details.experiences to describe", example=0),
     *         @OA\Property(property="notes", type="string", description="Learner's rough notes to work from", example="fed 2000 birds, did vaccination"))),
     *     @OA\Response(response=403, description="CV locked: the learner hasn't filled the participant form yet. errors.missing lists the field keys, errors.labels their names; code=profile_incomplete. Applies to every /api/cv endpoint.", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=false),
     *         @OA\Property(property="code", type="string", example="profile_incomplete"),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="errors", type="object",
     *             @OA\Property(property="missing", type="array", @OA\Items(type="string"), example={"income_band","value_chain_track"}),
     *             @OA\Property(property="labels", type="array", @OA\Items(type="string"), example={"Monthly income","Value chain track"})))),
     *     @OA\Response(response=200, description="Suggestion (string for headline/summary/experience, array of strings for achievements/skills)", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean"),
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="field", type="string"),
     *             @OA\Property(property="suggestion", oneOf={@OA\Schema(type="string"), @OA\Schema(type="array", @OA\Items(type="string"))})))),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError")),
     *     @OA\Response(response=503, description="Writing assistant unavailable (Claude not configured or failed)")
     * )
     */
    public function suggestCv() {}

    /**
     * @OA\Get(
     *     path="/api/cv/pdf",
     *     tags={"CV & Job Matching"},
     *     summary="Download my CV as a PDF",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=403, description="CV locked: the learner hasn't filled the participant form yet. errors.missing lists the field keys, errors.labels their names; code=profile_incomplete. Applies to every /api/cv endpoint.", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=false),
     *         @OA\Property(property="code", type="string", example="profile_incomplete"),
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="errors", type="object",
     *             @OA\Property(property="missing", type="array", @OA\Items(type="string"), example={"income_band","value_chain_track"}),
     *             @OA\Property(property="labels", type="array", @OA\Items(type="string"), example={"Monthly income","Value chain track"})))),
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
