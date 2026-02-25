<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\CategoryResource;


class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::with('parent')->get()->map(function($cat) {
            $names = [];
            $current = $cat;
            while ($current) {
                array_unshift($names, $current->name);
                $current = $current->parent;
            }
            $cat->full_name = implode(' -> ', $names);
            return $cat;
        });

        return response()->json([
            'message' => 'Category Added Successfully',
            'data' => $categories
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'required|boolean'
        ]);

        $category = Category::create($request->all());

        return response()->json([
            'message' => 'Category Added Successfully',
            'data' => $category
        ]);
    }

    private function buildFullName($category)
    {
        if (!$category->parentRecursive) {
            return $category->name;
        }

        return $this->buildFullName($category->parentRecursive) . ' -> ' . $category->name;
    }

    public function categoryWithChild(Request $request)
    {
        $categories = Category::with('parentRecursive')->get();

        $result = [];

        foreach ($categories as $category) {
            $result[] = [
                'id' => $category->id,
                'name' => $category->name,
                'parent_id' => $category->parent_id,
                'full_name' => $this->buildFullName($category),
            ];
        }

        return response()->json($result);
    }
}