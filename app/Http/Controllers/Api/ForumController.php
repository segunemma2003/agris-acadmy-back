<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ForumPost;
use App\Models\ForumComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ForumController extends Controller
{
    /**
     * List forum posts with basic filters (search, category, sort).
     */
    public function index(Request $request): JsonResponse
    {
        $query = ForumPost::query()->withCount('comments')->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('content', 'like', '%' . $search . '%')
                    ->orWhere('user_name', 'like', '%' . $search . '%')
                    ->orWhere('category', 'like', '%' . $search . '%');
            });
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        // Simple sort option: trending (by likes+comments), newest (default)
        if ($request->input('sort') === 'trending') {
            $query->orderByRaw('(likes + comments) DESC');
        }

        $posts = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'data' => $posts->items(),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    /**
     * Create a new forum post.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'category' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'image_url' => ['nullable', 'string', 'max:2048'],
        ]);

        $post = ForumPost::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_avatar' => $user->avatar ?? null,
            'is_verified' => (bool)($user->is_verified ?? false),
            'category' => $validated['category'] ?? null,
            'content' => $validated['content'],
            'image_url' => $validated['image_url'] ?? null,
        ]);

        return response()->json([
            'message' => 'Post created successfully',
            'data' => $post,
        ], 201);
    }

    /**
     * Show a single forum post with comments.
     */
    public function show(ForumPost $post): JsonResponse
    {
        $post->load(['comments' => function ($q) {
            $q->whereNull('parent_id')->latest();
        }]);

        return response()->json([
            'data' => $post,
        ]);
    }

    /**
     * Add a comment to a forum post.
     */
    public function addComment(Request $request, ForumPost $post): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'content' => ['required', 'string'],
            'parent_id' => ['nullable', 'exists:forum_comments,id'],
        ]);

        $comment = ForumComment::create([
            'forum_post_id' => $post->id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_avatar' => $user->avatar ?? null,
            'is_verified' => (bool)($user->is_verified ?? false),
            'content' => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        // Increment comments count on post
        $post->increment('comments');

        return response()->json([
            'message' => 'Comment added successfully',
            'data' => $comment,
        ], 201);
    }

    /**
     * List comments for a forum post.
     */
    public function comments(ForumPost $post): JsonResponse
    {
        $comments = ForumComment::where('forum_post_id', $post->id)
            ->whereNull('parent_id')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $comments,
        ]);
    }

    /**
     * Like or unlike a post.
     */
    public function toggleLike(ForumPost $post, Request $request): JsonResponse
    {
        // For now, just increment/decrement likes without tracking per-user.
        $like = filter_var($request->input('like', true), FILTER_VALIDATE_BOOLEAN);

        if ($like) {
            $post->increment('likes');
        } else {
            if ($post->likes > 0) {
                $post->decrement('likes');
            }
        }

        $post->refresh();

        return response()->json([
            'message' => $like ? 'Post liked' : 'Post unliked',
            'data' => $post,
        ]);
    }

    /**
     * Like or unlike a comment.
     */
    public function toggleCommentLike(ForumComment $comment, Request $request): JsonResponse
    {
        $like = filter_var($request->input('like', true), FILTER_VALIDATE_BOOLEAN);

        if ($like) {
            $comment->increment('likes');
        } else {
            if ($comment->likes > 0) {
                $comment->decrement('likes');
            }
        }

        $comment->refresh();

        return response()->json([
            'message' => $like ? 'Comment liked' : 'Comment unliked',
            'data' => $comment,
        ]);
    }
}
