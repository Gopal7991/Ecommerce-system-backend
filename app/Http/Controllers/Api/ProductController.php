<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\{Category,Product};
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\CategoryResource;
use App\Http\Requests\StoreProductRequest;
use Illuminate\Support\Facades\DB;


class ProductController extends Controller
{
    public function index(Request $request)
    {
         $products = Product::with(['variants', 'category'])->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ], 200);
        // $categories = Category::with('parent')->get()->map(function($cat) {
        //     $names = [];
        //     $current = $cat;
        //     while ($current) {
        //         array_unshift($names, $current->name);
        //         $current = $current->parent;
        //     }
        //     $cat->full_name = implode(' -> ', $names);
            
        //     $cat->full_child_name = $this->buildChildNamePath($cat);
        //     return $cat;
        // });

        // return response()->json([
        //     'message' => 'Category List',
        //     'data' => $categories
        // ]);
    }
   
    // private function buildChildNamePath($category) {
    //     // if ($category->childrenRecursive->isEmpty()) {
    //     //     return $category->name;
    //     // }
    //     if (!$category->parentRecursive) {
    //         return $category->name;
    //     }

    //     $childBranches = $category->childrenRecursive->map(function($child) {
    //         return $this->buildChildNamePath($child);
    //     })->implode(', ');

    //     return $category->name . ' ->' . $childBranches ;
    // }

    // public function store(Request $request)
    // {
    //     echo "<pre>"; print_r($request->all());exit;
    //     $request->validate([
    //         'name' => 'required|string|max:255',
    //         'parent_id' => 'nullable|exists:categories,id',
    //         'is_active' => 'required|boolean'
    //     ]);

        // $category = Category::create($request->all());

        // return response()->json([
        //     'message' => 'Category Added Successfully',
        //     'data' => $category
        // ]);
    // }
    public function store(StoreProductRequest $request)
    {
        // Start transaction to ensure data integrity
        return DB::transaction(function () use ($request) {
            
            $product = Product::create($request->validated());

            $product->variants()->createMany($request->variants);
            

            return response()->json([
                'message' => 'Product and variants created successfully!',
                'product' => $product->load('variants')
            ], 201);
        });
    }

    // private function buildFullName($category)
    // {
    //     if (!$category->parentRecursive) {
    //         return $category->name;
    //     }

    //     return $this->buildFullName($category->parentRecursive) . ' -> ' . $category->name;
    // }

    public function categoryWithChild(Request $request)
    {
        // $categories = Category::with('parentRecursive')->get();

        // $result = [];
        // foreach ($categories as $category) {
        //     $result[] = [
        //         'id' => $category->id,
        //         'name' => $category->name,
        //         'parent_id' => $category->parent_id,
        //         'full_name' => $this->buildFullName($category),
        //     ];
        // }

        // return response()->json($result);
    }

    public function edit($id)
    {
        $product = Product::with('variants')->findOrFail($id);

        return response()->json($product);
    }

    public function update(StoreProductRequest  $request, $id)
    {
       return DB::transaction(function () use ($request, $id) {
        
        $product = Product::updateOrCreate(
            ['id' => $id], 
            $request->validated()
        );
        if ($request->has('variants')) {
            $product->variants()->delete(); 

            if (!empty($request->variants)) {
                $product->variants()->createMany($request->variants);
            }
        }
        
            return response()->json([
                'message' => 'Product updated successfully!',
                'product' => $product->load('variants')
            ], 200);
        });
    }


    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
        $product = Product::findOrFail($id);
        $product->variants()->delete();
        $product->delete();

        return response()->json([
            'message' => 'Product and all its variants deleted successfully!'
        ], 200);
    });
    }
}
