<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\CategoryResource;
use App\Http\Requests\StoreCategoryRequest; 

class CategoryController extends Controller
{
    protected $categoryRepo;

    public function __construct(CategoryRepository $categoryRepo)
    {
        $this->categoryRepo = $categoryRepo;
    }

    public function index(Request $request)
    {
        $categories = $this->categoryRepo->getCategories($request);

        return response()->json([
            'success' => true,
            'message' => 'Categories list fetched successfully',
            'data' => $categories['data'],
            'total' => $categories['total'],
            'per_page' => $categories['per_page'],
            'current_page' => $categories['current_page'],
        ]);
        // return response()->json([
        //     'message' => 'Category List',
        //     'data' => $categories
        // ]);
    }
   
    public function store(StoreCategoryRequest $request)
    {
        $category = $this->categoryRepo->store($request->all());
        return response()->json([
            'message' => 'Category Added Successfully',
            'data' => $category
        ]);
    }
    public function categoryWithChild(Request $request)
    {
        $category = $this->categoryRepo->getCategorywithFullname($request);
        return response()->json($category);
    }

    public function edit($id)
    {
        $category = $this->categoryRepo->editCategory($id);
        return response()->json($category);
    }

    public function update(StoreCategoryRequest $request, $id)
    {
        $data = $request->validated();
        $this->categoryRepo->updateCategory($data, $id);

        return response()->json([
            'message' => 'Category updated successfully'
        ]);
    }

    public function destroy($id)
    {
        $this->categoryRepo->destroyCategory($id);
        return response()->json(['message' => 'Deleted']);
    }
}
