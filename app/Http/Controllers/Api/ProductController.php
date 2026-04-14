<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\{Category,Product,ProductImage, Brand};
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\CategoryResource;
use App\Repositories\ProductRepository;
use App\Http\Requests\StoreProductRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    protected $productRepo;

    public function __construct(ProductRepository $productRepo)
    {
        $this->productRepo = $productRepo;
    }
    
    public function index(Request $request)
    {
        $result = $this->productRepo->getProducts($request->all());
        return response()->json([
            'success' => true,
            'message' => 'Product list fetched successfully',
            'data' => $result['data'],
            'total' => $result['total'],
            'per_page' => $result['per_page'],
            'current_page' => $result['current_page'],
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $result = $this->productRepo->createProduct($request->validated());

        return response()->json([
            'success' => $result['status'],
            'message' => $result['message'],
            'data' => $result['data']
        ], 201);
    }

    public function edit($id)
    {
        $result = $this->productRepo->EditProduct($id);
        if (!$result['status']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data']
        ]);
    }

    public function update(StoreProductRequest  $request, $id)
    {
        $result = $this->productRepo->updateProduct($request->validated(), $id);

        if (!$result['status']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ]);
    }
    public function destroy($id)
    {
        $result = $this->productRepo->deleteProduct($id);

        if (!$result['status']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message']
        ]);
    }

    public function uploadProductImage(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'images.*' => 'image|max:2048',
        ]);

        $result = $this->productRepo->uploadProductImages($request);

        if (!$result['status']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ]);
    }

    public function getImages($id)
    {
        $result = $this->productRepo->getProductImages($id);

        if (!$result['status']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data']
        ]);
    }

    public function deleteImage($id)
    {
        $result = $this->productRepo->deleteProductImage($id);
        
        if (!$result['status']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message']
        ]);

    }

    public function getBrand()
    {
        $result = $this->productRepo->getBrands();

        return response()->json([
            'success' => true,
            'data' => $result['data']
        ]);
    }

}
