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
    // public function index(Request $request)
    // {
    //      $products = Product::with(['variants', 'category'])->latest()->get();

    //     return response()->json([
    //         'success' => true,
    //         'data' => $products
    //     ], 200);
    // }

    // public function index(Request $request)
    // {
    //     $query = Product::with(['category', 'images']); // eager load images

    //     $sortBy = $request->query('sort_by', 'id'); 
    //     $sortOrder = $request->query('sort_order', 'asc'); 

    //     if ($sortBy == 'category') {
    //         $query->join('categories', 'products.category_id', '=', 'categories.id')
    //             ->orderBy('categories.name', $sortOrder)
    //             ->select('products.*');
    //     } else {
    //         $query->orderBy($sortBy, $sortOrder);
    //     }

    //     $products = $query->paginate(5);

    //     $products->getCollection()->transform(function($product) {
    //         $product->images = $product->images->map(function($img) {
    //             return [
    //                 'url' => asset("storage/products/{$img->product_id}/{$img->filename}"),
    //                 'filename' => $img->filename
    //             ];
    //         });
    //         return $product;
    //     });

    //     return response()->json([
    //         'data' => $products->items(),
    //         'total' => $products->total(),
    //         'per_page' => $products->perPage(),
    //         'current_page' => $products->currentPage(),
    //     ]);
    // }
  
    public function index(Request $request)
    {
        // Eager load category, its children, and images
        $query = Product::with(['category', 'category.children', 'images']);

        // --- Search ---
        if ($search = $request->query('search')) {
            $query->where(function($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                ->orWhereHas('category', function($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%")
                        ->orWhereHas('children', function($q3) use ($search) {
                            $q3->where('name', 'like', "%{$search}%");
                        });
                });
            });
        }

        $sortBy = $request->query('sort_by', 'id'); 
        $sortOrder = $request->query('sort_order', 'asc'); 

        if ($sortBy === 'category') {
            $query->join('categories', 'products.category_id', '=', 'categories.id')
                ->orderBy('categories.name', $sortOrder)
                ->select('products.*'); // Ensure only products columns
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = $request->query('per_page', 5);
        $products = $query->paginate($perPage);

        $products->getCollection()->transform(function($product) {
            $product->images = $product->images->map(function($img) use ($product) {
                return [
                    'url' => asset("storage/products/{$product->id}/{$img->filename}"),
                    'filename' => $img->filename
                ];
            });
            return $product;
        });

        return response()->json([
            'data' => $products->items(),
            'total' => $products->total(),
            'per_page' => $products->perPage(),
            'current_page' => $products->currentPage(),
        ]);
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
            'images.*' => 'image|max:2048',
        ]);

        $product = Product::findOrFail($productId);
        $folderPath = "products/{$product->id}";

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
            'message' => 'Images uploaded successfully',
            'images' => $product->images()->get(),
        ]);
    }

    public function getImages($id)
    {
        $product = Product::with('images')->findOrFail($id);
        $images = $product->images->map(function ($img) {
            return [
                'id' => $img->id,
                'url' => asset('storage/' . $img->image)
            ];
        });

        return response()->json([
            'status' => true,
            'images' => $images
        ]);
    }

    public function deleteImage($id)
    {
        $image = ProductImage::findOrFail($id);

        if (Storage::disk('public')->exists($image->image)) {
            Storage::disk('public')->delete($image->image);
        }

        $image->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Image deleted'
        ]);
    }

}
