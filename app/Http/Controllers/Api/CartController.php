<?php

namespace App\Http\Controllers\Api;
use App\Repositories\CartRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\CartStoreRequest; 
use Illuminate\Http\Request;
use App\Models\{Cart, ProductVariant, CartItem, Order, OrderProduct,User };
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    protected $cartRepo;

    public function __construct(CartRepository $cartRepo)
    {
        $this->cartRepo = $cartRepo;
    }

    public function cartStore(CartStoreRequest $request)
    {
        $cartData = $this->cartRepo->storeCartData($request);
        return response()->json($cartData);
    }

    public function cartCount()
    {
        $cartData = $this->cartRepo->getCartData();
        return response()->json($cartData);
    }

    public function cartItemUpdate(CartStoreRequest $request, $id)
    {
        $cartData = $this->cartRepo->cartItemUpdateData($request, $id);
        return response()->json($cartData);
    }

    public function cartItemDelete($id, Request $request)
    {
        $cartData = $this->cartRepo->cartItemDeleteData($id, $request);
        return response()->json($cartData);
            
    }
    
}



