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
    schema: 'User',
    properties: [
        new OAT\Property(property: 'id', type: 'integer'),
        new OAT\Property(property: 'name', type: 'string'),
        new OAT\Property(property: 'email', type: 'string', format: 'email'),
        new OAT\Property(property: 'phone', type: 'string', nullable: true),
        new OAT\Property(property: 'avatar', type: 'string', nullable: true),
        new OAT\Property(property: 'bio', type: 'string', nullable: true),
        new OAT\Property(property: 'location', type: 'string', nullable: true),
        new OAT\Property(property: 'role', type: 'string'),
        new OAT\Property(property: 'is_active', type: 'boolean'),
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
