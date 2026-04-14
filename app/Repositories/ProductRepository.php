<?php
namespace App\Repositories;

use App\Models\{Product, ProductImage,Brand};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductRepository
{
    public function getProducts(array $filters)
    {
        $userId = auth()->id();

        if($userId == 1) {
            $query = Product::with(['category', 'category.children', 'images', 'brand', 'variants'])->latest();
        }else {
            $query = Product::with(['category', 'category.children', 'images', 'brand', 'variants']);
        }

        if (!empty($filters['sub_category_id'])) {
            $query->where('products.category_id', $filters['sub_category_id']);
        } 
        elseif (!empty($filters['category_id'])) {
            $query->whereHas('category', function ($q) use ($filters) {
                $q->where('id', $filters['category_id'])
                  ->orWhere('parent_id', $filters['category_id']);
            });
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                  ->orWhereHas('category', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhereHas('children', function ($q3) use ($search) {
                             $q3->where('name', 'like', "%{$search}%");
                         });
                  });
            });
        }

        if (!empty($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        $sortBy = $filters['sort_by'] ?? 'id';
        $sortOrder = $filters['sort_order'] ?? 'asc';

        if ($sortBy === 'category') {
            $query->join('categories', 'products.category_id', '=', 'categories.id')
                  ->orderBy('categories.name', $sortOrder)
                  ->select('products.*');
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }
        $userId = auth()->id();

        if ($userId == 1) {
            $perPage = $filters['per_page'] ?? 5;
            $products = $query->paginate($perPage);
        }else {
            $results = $query->get();
        
            $products = new \Illuminate\Pagination\LengthAwarePaginator(
                $results, 
                $results->count(), 
                $results->count() ?: 1, 
                1
            );
        }
        
        $products->getCollection()->transform(function ($product) {
            $product->images = $product->images->map(function ($img) use ($product) {
                return [
                    'url' => asset("storage/products/{$product->id}/{$img->filename}"),
                    'filename' => $img->filename
                ];
            });
            return $product;
        });

        return [
            'data' => $products->items(),
            'total' => $products->total(),
            'per_page' => $products->perPage(),
            'current_page' => $products->currentPage(),
        ];
    }
	
    public function createProduct(array $data)
    {
        return DB::transaction(function () use ($data) {

            $product = Product::create($data);
            if (!empty($data['variants'])) {
                $product->variants()->createMany($data['variants']);
            }
            $product->load('variants');
            return [
                'status' => true,
                'message' => 'Product and variants created successfully!',
                'data' => $product
            ];
        });
    }

    public function EditProduct($id)
    {
        try {
            $product = Product::with(['variants', 'images'])->findOrFail($id);

            return [
                'status' => true,
                'data' => $product
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Product not found!'
            ];
        }
    }

    public function updateProduct(array $data, $id)
    {
        try {
            return DB::transaction(function () use ($data, $id) {
                $product = Product::updateOrCreate(
                    ['id' => $id],
                    $data
                );

                if (isset($data['variants'])) {

                    $product->variants()->delete();

                    if (!empty($data['variants'])) {
                        $product->variants()->createMany($data['variants']);
                    }
                }

                $product->load('variants');

                return [
                    'status' => true,
                    'message' => 'Product updated successfully!',
                    'data' => $product
                ];
            });

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function deleteProduct($id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $product = Product::findOrFail($id);

                $product->variants()->delete();

                $product->delete();

                return [
                    'status' => true,
                    'message' => 'Product and all its variants deleted successfully!'
                ];
            });

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Product not found or already deleted!'
            ];
        }
    }

    public function uploadProductImages($request)
    {
        try {
            $product = Product::findOrFail($request->product_id);

            $folderPath = "products/{$product->id}";
            $uploadedImages = [];

            if ($request->hasFile('images')) {

                foreach ($request->file('images') as $file) {

                    $path = $file->store($folderPath, 'public');

                    $image = ProductImage::create([
                        'product_id' => $product->id,
                        'image' => $path,
                    ]);

                    $uploadedImages[] = $image;
                }
            }

            return [
                'status' => true,
                'message' => 'Images uploaded successfully',
                'data' => $uploadedImages
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Product not found or upload failed!'
            ];
        }
    }

    public function getProductImages($id)
    {
        try {
            $product = Product::with('images')->findOrFail($id);

            $images = $product->images->map(function ($img) {
                return [
                    'id' => $img->id,
                    'url' => asset('storage/' . $img->image)
                ];
            });

            return [
                'status' => true,
                'data' => $images
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Product not found!'
            ];
        }
    }

    public function deleteProductImage($id)
    {
        try {
            $image = ProductImage::findOrFail($id);

            if (Storage::disk('public')->exists($image->image)) {
                Storage::disk('public')->delete($image->image);
            }

            $image->delete();

            return [
                'status' => true,
                'message' => 'Image deleted successfully'
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Image not found!'
            ];
        }
    }
    public function getBrands()
    {
        $brands = Brand::all();

        return [
            'status' => true,
            'data' => $brands
        ];
    }  
}