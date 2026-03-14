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
            'image' => ['nullable', 'image', 'max:4096'], // up to 4MB
        ]);

        $imageUrl = null;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('forum-posts', 'public');
            $imageUrl = \Storage::disk('public')->url($path);
        }

        $post = ForumPost::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_avatar' => $user->avatar ?? null,
            'is_verified' => (bool)($user->is_verified ?? false),
            'category' => $validated['category'] ?? null,
            'content' => $validated['content'],
            'image_url' => $imageUrl,
        ]);

        return response()->json([
            'message' => 'Post created successfully',
            'data' => $post,
        ], 201);
    }

    /**
     * Show a single forum post with comments.
     */
    public function show(Request $request, ForumPost $post): JsonResponse
    {
        $user = $request->user();
        
        $post->load(['comments' => function ($q) {
            $q->whereNull('parent_id')->latest();
        }]);

        // Add is_liked status if user is authenticated
        if ($user) {
            $isLiked = \DB::table('forum_post_likes')
                ->where('forum_post_id', $post->id)
                ->where('user_id', $user->id)
                ->exists();
            $post->is_liked = $isLiked;
        }

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
    public function comments(Request $request, ForumPost $post): JsonResponse
    {
        $user = $request->user();
        
        $comments = ForumComment::where('forum_post_id', $post->id)
            ->whereNull('parent_id')
            ->orderByDesc('created_at')
            ->get();

        // Add is_liked status for each comment if user is authenticated
        if ($user) {
            $commentIds = $comments->pluck('id')->toArray();
            $likedCommentIds = \DB::table('forum_comment_likes')
                ->whereIn('forum_comment_id', $commentIds)
                ->where('user_id', $user->id)
                ->pluck('forum_comment_id')
                ->toArray();

            foreach ($comments as $comment) {
                $comment->is_liked = in_array($comment->id, $likedCommentIds);
            }
        }

        return response()->json([
            'data' => $comments,
        ]);
    }

    /**
     * Like or unlike a post.
     */
    public function toggleLike(ForumPost $post, Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Check if user already liked this post
        $existingLike = \DB::table('forum_post_likes')
            ->where('forum_post_id', $post->id)
            ->where('user_id', $user->id)
            ->first();

        $isLiked = $existingLike !== null;
        $like = filter_var($request->input('like', !$isLiked), FILTER_VALIDATE_BOOLEAN);

        if ($like && !$isLiked) {
            // Like the post
            \DB::table('forum_post_likes')->insert([
                'forum_post_id' => $post->id,
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $post->increment('likes');
        } elseif (!$like && $isLiked) {
            // Unlike the post
            \DB::table('forum_post_likes')
                ->where('forum_post_id', $post->id)
                ->where('user_id', $user->id)
                ->delete();
            if ($post->likes > 0) {
                $post->decrement('likes');
            }
        }

        $post->refresh();
        
        // Check if current user liked the post
        $isLikedByUser = \DB::table('forum_post_likes')
            ->where('forum_post_id', $post->id)
            ->where('user_id', $user->id)
            ->exists();

        $postData = $post->toArray();
        $postData['is_liked'] = $isLikedByUser;

        return response()->json([
            'message' => $like ? 'Post liked' : 'Post unliked',
            'data' => $postData,
        ]);
    }
    
    /**
     * Share a post (increment share count).
     */
    public function share(ForumPost $post): JsonResponse
    {
        $post->increment('shares');
        $post->refresh();

        return response()->json([
            'message' => 'Post shared',
            'data' => $post,
        ]);
    }

    /**
     * Like or unlike a comment.
     */
    public function toggleCommentLike(ForumComment $comment, Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Check if user already liked this comment
        $existingLike = \DB::table('forum_comment_likes')
            ->where('forum_comment_id', $comment->id)
            ->where('user_id', $user->id)
            ->first();

        $isLiked = $existingLike !== null;
        $like = filter_var($request->input('like', !$isLiked), FILTER_VALIDATE_BOOLEAN);

        if ($like && !$isLiked) {
            // Like the comment
            \DB::table('forum_comment_likes')->insert([
                'forum_comment_id' => $comment->id,
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $comment->increment('likes');
        } elseif (!$like && $isLiked) {
            // Unlike the comment
            \DB::table('forum_comment_likes')
                ->where('forum_comment_id', $comment->id)
                ->where('user_id', $user->id)
                ->delete();
            if ($comment->likes > 0) {
                $comment->decrement('likes');
            }
        }

        $comment->refresh();
        
        // Check if current user liked the comment
        $isLikedByUser = \DB::table('forum_comment_likes')
            ->where('forum_comment_id', $comment->id)
            ->where('user_id', $user->id)
            ->exists();

        $commentData = $comment->toArray();
        $commentData['is_liked'] = $isLikedByUser;

        return response()->json([
            'message' => $like ? 'Comment liked' : 'Comment unliked',
            'data' => $commentData,
        ]);
    }
}
