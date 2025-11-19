<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
            'message' => 'Categories retrieved successfully'
        ]);
    }

    public function show(Category $category)
    {
        if (!$category->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        $category->load(['courses' => function ($query) {
            $query->where('is_published', true)
                ->with(['tutor:id,name,avatar'])
                ->orderBy('created_at', 'desc');
        }]);

        return response()->json([
            'success' => true,
            'data' => $category,
            'message' => 'Category details retrieved successfully'
        ]);
    }

    public function withCourses()
    {
        $categories = Category::where('is_active', true)
            ->with(['courses' => function ($query) {
                $query->where('is_published', true)
                    ->with(['tutor:id,name,avatar'])
                    ->orderBy('created_at', 'desc');
            }])
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
            'message' => 'Categories with courses retrieved successfully'
        ]);
    }

    public function featuredCourses()
    {
        $categories = Category::where('is_active', true)
            ->with(['courses' => function ($query) {
                $query->where('is_published', true)
                    ->where('is_featured', true)
                    ->with(['tutor:id,name,avatar'])
                    ->orderBy('created_at', 'desc');
            }])
            ->whereHas('courses', function ($query) {
                $query->where('is_published', true)
                    ->where('is_featured', true);
            })
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
            'message' => 'Featured courses per category retrieved successfully'
        ]);
    }
}



