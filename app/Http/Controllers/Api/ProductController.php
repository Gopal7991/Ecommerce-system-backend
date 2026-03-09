<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\{Category,Product,ProductImage};
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\CategoryResource;
use App\Http\Requests\StoreProductRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
         $products = Product::with(['variants', 'category'])->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ], 200);
    }
  
    public function store(StoreProductRequest $request)
    {
        return DB::transaction(function () use ($request) {
            
            $product = Product::create($request->validated());

            $product->variants()->createMany($request->variants);
            

            return response()->json([
                'message' => 'Product and variants created successfully!',
                'product' => $product->load('variants')
            ], 201);
        });
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

    public function uploadProductImage(Request $request)
    {
        $productId = $request->product_id;
        $request->validate([
            'images.*' => 'required|image|max:2048',
        ]);

        $product = Product::findOrFail($productId);
        $folderPath = "products/{$product->id}";

        if (Storage::disk('public')->exists($folderPath)) {
            Storage::disk('public')->deleteDirectory($folderPath);
        }

        $product->images()->delete();

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store($folderPath, 'public');

                ProductImage::create([
                    'product_id' => $product->id,
                    'image' => $path,
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Folder cleared and new images uploaded successfully',
            'images' => $product->images()->get(),
        ]);
    }

}
