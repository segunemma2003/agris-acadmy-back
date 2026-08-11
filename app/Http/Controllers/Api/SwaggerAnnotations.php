<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OAT;

#[OAT\Info(
    version: '1.0.0',
    title: 'Agrisiti Academy API',
    description: 'REST and WebSocket API for the Agrisiti Academy e-learning platform. Covers student registration, course management, progress tracking, tests, assignments, notes, messaging, community forum, notifications, and the Agri chatbot (AI-powered onboarding assistant with real-time Reverb WebSocket streaming, SSE fallback, and course recommendations). See the Chatbot tag for the complete user journey, quiz questions, and 3-tier transport documentation.',
    contact: new OAT\Contact(email: 'admin@agrisiti.com'),
)]
#[OAT\Server(url: 'https://academy-backends.agrisiti.com', description: 'Production Server')]
#[OAT\Server(url: 'http://localhost:8000', description: 'Local Development Server')]
#[OAT\SecurityScheme(
    securityScheme: 'sanctumAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Token',
    description: 'Enter your Bearer token obtained from /api/login',
)]
#[OAT\Schema(
    schema: 'ApiSuccess',
    properties: [
        new OAT\Property(property: 'success', type: 'boolean', example: true),
        new OAT\Property(property: 'message', type: 'string'),
    ]
)]
#[OAT\Schema(
    schema: 'ApiError',
    properties: [
        new OAT\Property(property: 'success', type: 'boolean', example: false),
        new OAT\Property(property: 'message', type: 'string'),
    ]
)]
#[OAT\Schema(
    schema: 'ValidationError',
    properties: [
        new OAT\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OAT\Property(property: 'errors', type: 'object', additionalProperties: new OAT\AdditionalProperties(type: 'array', items: new OAT\Items(type: 'string'))),
    ]
)]
#[OAT\Schema(
    schema: 'Pagination',
    properties: [
        new OAT\Property(property: 'current_page', type: 'integer'),
        new OAT\Property(property: 'last_page', type: 'integer'),
        new OAT\Property(property: 'per_page', type: 'integer'),
        new OAT\Property(property: 'total', type: 'integer'),
    ]
)]
#[OAT\Schema(
    schema: 'FacilitatorSummary',
    properties: [
        new OAT\Property(property: 'id', type: 'integer', example: 12),
        new OAT\Property(property: 'name', type: 'string', example: 'Amina Facilitator'),
        new OAT\Property(property: 'email', type: 'string', format: 'email', example: 'amina@agrisiti.com'),
        new OAT\Property(property: 'phone', type: 'string', nullable: true, example: '+2348012345678'),
        new OAT\Property(property: 'location', type: 'string', nullable: true, example: 'Kano'),
        new OAT\Property(property: 'state', type: 'string', nullable: true, example: 'Kano'),
        new OAT\Property(property: 'lga', type: 'string', nullable: true, example: 'Nassarawa'),
        new OAT\Property(property: 'avatar', type: 'string', nullable: true),
    ]
)]
#[OAT\Schema(
    schema: 'User',
    properties: [
        new OAT\Property(property: 'id', type: 'integer'),
        new OAT\Property(property: 'name', type: 'string'),
        new OAT\Property(property: 'email', type: 'string', format: 'email'),
        new OAT\Property(property: 'phone', type: 'string', nullable: true),
        new OAT\Property(property: 'gender', type: 'string', nullable: true),
        new OAT\Property(property: 'date_of_birth', type: 'string', format: 'date', nullable: true),
        new OAT\Property(property: 'age', type: 'integer', nullable: true),
        new OAT\Property(property: 'location', type: 'string', nullable: true),
        new OAT\Property(property: 'state', type: 'string', nullable: true),
        new OAT\Property(property: 'lga', type: 'string', nullable: true),
        new OAT\Property(property: 'occupation', type: 'string', nullable: true),
        new OAT\Property(property: 'referral', type: 'string', nullable: true),
        new OAT\Property(property: 'bio', type: 'string', nullable: true),
        new OAT\Property(property: 'avatar', type: 'string', nullable: true),
        new OAT\Property(property: 'role', type: 'string', example: 'student'),
        new OAT\Property(property: 'locale', type: 'string', example: 'en'),
        new OAT\Property(property: 'facilitator_id', type: 'integer', nullable: true, example: 12, description: 'Assigned facilitator based on learner location (LGA → state → location)'),
        new OAT\Property(property: 'is_in_facilitator_queue', type: 'boolean', example: false, description: 'True when no matching facilitator was found for the learner location'),
        new OAT\Property(property: 'facilitator', ref: '#/components/schemas/FacilitatorSummary', nullable: true),
        new OAT\Property(property: 'notification_preferences', type: 'object', nullable: true),
        new OAT\Property(property: 'is_active', type: 'boolean'),
    ]
)]
#[OAT\Schema(
    schema: 'ModuleLockStatus',
    properties: [
        new OAT\Property(property: 'locked', type: 'boolean', example: true),
        new OAT\Property(property: 'quiz_passed', type: 'boolean', example: false, description: 'Whether the previous module quiz was completed and passed (unlocks this module)'),
        new OAT\Property(property: 'has_attempted', type: 'boolean', example: true),
        new OAT\Property(property: 'required_percentage', type: 'number', format: 'float', example: 80, description: 'Pass threshold (minimum 80%)'),
        new OAT\Property(property: 'best_percentage', type: 'number', format: 'float', example: 45),
        new OAT\Property(property: 'message', type: 'string', nullable: true, example: 'You scored 45%. You need 80% to unlock the next module.'),
        new OAT\Property(
            property: 'previous_module',
            type: 'object',
            nullable: true,
            properties: [
                new OAT\Property(property: 'id', type: 'integer', example: 3),
                new OAT\Property(property: 'title', type: 'string', example: 'Module 3: Soil Health'),
            ]
        ),
    ]
)]
#[OAT\Schema(
    schema: 'ModuleQuizStatus',
    properties: [
        new OAT\Property(property: 'has_quiz', type: 'boolean', example: true),
        new OAT\Property(property: 'quiz_completed', type: 'boolean', example: true, description: 'True when the learner has submitted at least one attempt for this module quiz'),
        new OAT\Property(property: 'quiz_passed', type: 'boolean', example: false, description: 'True when best attempt meets the 80% threshold — required before the next module unlocks'),
        new OAT\Property(property: 'has_attempted', type: 'boolean', example: true),
        new OAT\Property(property: 'required_percentage', type: 'number', format: 'float', example: 80),
        new OAT\Property(property: 'best_percentage', type: 'number', format: 'float', example: 45),
        new OAT\Property(property: 'message', type: 'string', nullable: true, example: 'You scored 45%. You need 80% to unlock the next module.'),
    ]
)]
#[OAT\Schema(
    schema: 'Course',
    properties: [
        new OAT\Property(property: 'id', type: 'integer'),
        new OAT\Property(property: 'title', type: 'string'),
        new OAT\Property(property: 'slug', type: 'string'),
        new OAT\Property(property: 'description', type: 'string', nullable: true),
        new OAT\Property(property: 'thumbnail', type: 'string', nullable: true),
        new OAT\Property(property: 'price', type: 'number', format: 'float', nullable: true),
        new OAT\Property(property: 'is_free', type: 'boolean'),
        new OAT\Property(property: 'status', type: 'string'),
        new OAT\Property(property: 'category_id', type: 'integer', nullable: true),
    ]
)]
#[OAT\Schema(
    schema: 'Enrollment',
    properties: [
        new OAT\Property(property: 'id', type: 'integer'),
        new OAT\Property(property: 'user_id', type: 'integer'),
        new OAT\Property(property: 'course_id', type: 'integer'),
        new OAT\Property(property: 'status', type: 'string'),
        new OAT\Property(property: 'progress_percentage', type: 'number', format: 'float'),
        new OAT\Property(property: 'enrolled_at', type: 'string', format: 'date-time'),
        new OAT\Property(property: 'completed_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
class SwaggerAnnotations {}
