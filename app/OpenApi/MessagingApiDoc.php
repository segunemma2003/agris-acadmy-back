<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * Peer and tutor messaging. Who may message whom (App\Support\Messaging):
 * learner ↔ course tutor, learner ↔ facilitator in the same location (course_id 0),
 * learner ↔ learner enrolled in the same course ("classmates"); admins may message anyone.
 *
 * @OA\Schema(
 *     schema="MessagePerson",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="avatar", type="string", nullable=true),
 *     @OA\Property(property="role", type="string", nullable=true),
 *     @OA\Property(property="kind", type="string", enum={"tutor","classmate","learner","facilitator"}, nullable=true)
 * )
 */
class MessagingApiDoc
{
    /**
     * @OA\Get(
     *     path="/api/messages/contacts",
     *     tags={"Messages"},
     *     summary="People I can start a conversation with",
     *     description="Grouped by course: tutors and classmates for a learner (or the class list for a tutor), plus facilitators in the learner's location. Up to 50 learners per course; use `search` to find others.",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="search", in="query", required=false, description="Filter by name", @OA\Schema(type="string", maxLength=100)),
     *     @OA\Response(response=200, description="Contacts", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean"),
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="courses", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="course_id", type="integer"), @OA\Property(property="course_title", type="string"),
     *                 @OA\Property(property="tutors", type="array", @OA\Items(ref="#/components/schemas/MessagePerson")),
     *                 @OA\Property(property="learners", type="array", @OA\Items(ref="#/components/schemas/MessagePerson")))),
     *             @OA\Property(property="facilitators", type="array", @OA\Items(ref="#/components/schemas/MessagePerson")))))
     * )
     */
    public function contacts() {}

    /**
     * @OA\Get(
     *     path="/api/messages/conversations",
     *     tags={"Messages"},
     *     summary="Inbox: one row per conversation (person + course), newest first",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Response(response=200, description="Conversations", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean"),
     *         @OA\Property(property="data", type="array", @OA\Items(type="object",
     *             @OA\Property(property="with", ref="#/components/schemas/MessagePerson"),
     *             @OA\Property(property="course_id", type="integer", description="0 = location-based (learner ↔ facilitator)"),
     *             @OA\Property(property="course_title", type="string", nullable=true),
     *             @OA\Property(property="last_message", type="string"),
     *             @OA\Property(property="last_at", type="string", format="date-time"),
     *             @OA\Property(property="last_from_me", type="boolean"),
     *             @OA\Property(property="unread", type="integer")))))
     * )
     */
    public function conversations() {}

    /**
     * @OA\Get(
     *     path="/api/messages/thread",
     *     tags={"Messages"},
     *     summary="One conversation, oldest first (marks messages to me as read)",
     *     security={{"sanctumAuth":{}}},
     *     @OA\Parameter(name="with", in="query", required=true, description="The other person's user id", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="course_id", in="query", required=false, description="Course of the conversation; 0 or omitted = location-based", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Thread", @OA\JsonContent(
     *         @OA\Property(property="success", type="boolean"),
     *         @OA\Property(property="data", type="object",
     *             @OA\Property(property="with", ref="#/components/schemas/MessagePerson"),
     *             @OA\Property(property="course_id", type="integer"),
     *             @OA\Property(property="course_title", type="string", nullable=true),
     *             @OA\Property(property="can_reply", type="boolean", description="Whether POST /api/messages to this person is allowed"),
     *             @OA\Property(property="messages", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="id", type="integer"), @OA\Property(property="sender_id", type="integer"), @OA\Property(property="recipient_id", type="integer"),
     *                 @OA\Property(property="message", type="string"), @OA\Property(property="is_read", type="boolean"), @OA\Property(property="created_at", type="string", format="date-time")))))),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function thread() {}
}
