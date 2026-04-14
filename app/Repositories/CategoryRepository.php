<?php
namespace App\Repositories;

use App\Models\Category;
use Illuminate\Support\Facades\Auth;

class CategoryRepository
{
	public function getCategories($request)
	{
        $query = Category::with('parent')->latest();
        $userId = auth()->id();
        if ($userId == 1) {
            $perPage = $request->query('per_page', 5);
            $categories = $query->paginate($perPage);
        } else {
            // Other Roles: Get All and wrap in a fake paginator
            $results = $query->get();
            $categories = new \Illuminate\Pagination\LengthAwarePaginator(
                $results, 
                $results->count(), 
                $results->count() ?: 1, 
                1
            );
        }
		$categories->getCollection()->transform(function($cat) {
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
        return [
            'data' => $categories->items(),
            'total' => $categories->total(),
            'per_page' => $categories->perPage(),
            'current_page' => $categories->currentPage(),
        ];
        // return $categories;
	}

	private function buildChildNamePath($category) {
        if (!$category->parentRecursive) {
            return $category->name;
        }

        $childBranches = $category->childrenRecursive->map(function($child) {
            return $this->buildChildNamePath($child);
        })->implode(', ');

        return $category->name . ' ->' . $childBranches ;
    }

    public function store($request)
    {
    	$category = Category::create($request);
    	return $category;
    }

    public function getCategorywithFullname($request)
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

    private function buildFullName($category)
    {
        if (!$category->parentRecursive) {
            return $category->name;
        }

        return $this->buildFullName($category->parentRecursive) . ' -> ' . $category->name;
    }

    public function editCategory($id)
    {
    	$category = Category::findOrFail($id);
    	return $category;
    }

    public function updateCategory(array $data, $id)
    {
    	$category = Category::findOrFail($id);
        $data['is_active'] = isset($data['is_active']);
        $category->update($data);
        return $category;
    }

    public function destroyCategory($id)
    {
    	$category = Category::findOrFail($id);
    	// if ($category->children()->count() > 0) {
        //     return response()->json([
        //         'message' => 'Cannot delete this category. Please delete its child categories first.',
        //         'status' => 'error'
        //     ], 409); // Use a 409 Conflict status code
        // }
    	$category->delete();
    	return "Deleted Record";
    }
}