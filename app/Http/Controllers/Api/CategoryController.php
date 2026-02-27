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
            
            $cat->full_child_name = $this->buildChildNamePath($cat);
            return $cat;
        });

        return response()->json([
            'message' => 'Category List',
            'data' => $categories
        ]);
    }
   
    private function buildChildNamePath($category) {
        // if ($category->childrenRecursive->isEmpty()) {
        //     return $category->name;
        // }
        if (!$category->parentRecursive) {
            return $category->name;
        }

        $childBranches = $category->childrenRecursive->map(function($child) {
            return $this->buildChildNamePath($child);
        })->implode(', ');

        return $category->name . ' ->' . $childBranches ;
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

    public function edit($id)
    {
        $category = Category::findOrFail($id);

        return response()->json($category);
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|max:255',
            'parent_id' => 'nullable',
        ]);

        $validatedData['is_active'] = $request->has('is_active');
        $category->update($validatedData);

        return response()->json(['message' => 'Category updated successfully!']);
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // if ($category->children()->count() > 0) {
        //     return response()->json([
        //         'message' => 'Cannot delete this category. Please delete its child categories first.',
        //         'status' => 'error'
        //     ], 409); // Use a 409 Conflict status code
        // }
        $category->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
