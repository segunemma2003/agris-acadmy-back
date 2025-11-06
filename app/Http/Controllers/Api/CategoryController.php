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

        return response()->json($categories);
    }

    public function show(Category $category)
    {
        return response()->json($category->load('courses'));
    }

    public function withCourses()
    {
        $categories = Category::where('is_active', true)
            ->with(['courses' => function ($query) {
                $query->where('is_published', true)
                    ->orderBy('created_at', 'desc');
            }])
            ->orderBy('sort_order')
            ->get();

        return response()->json($categories);
    }
}



