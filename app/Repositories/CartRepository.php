<?php
namespace App\Repositories;

use App\Models\{Cart, CartItem, ProductVariant};
use Illuminate\Support\Facades\Auth;

class CartRepository
{
    public function storeCartData($request)
    {
        $cart = Cart::firstOrCreate([
            'user_id' => auth()->id()
        ]);

        $productvariant = ProductVariant::where('color',$request->color)->where('size',$request->size)->where('product_id',$request->product_id)->first();
        if($productvariant->quantity == 0)
        {
            return response()->json([
                'status' => false,
                'message' => 'Product Not Available'
            ]);
        }

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $request->product_id,
            'product_variant_id' => $productvariant->id,
            'quantity' => $request->quantity
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Added to cart'
        ]);
    }

    public function getCart()
    {
        return Cart::firstOrCreate(['user_id' => Auth::id()]);
    }

    public function getCartData()
    {
        $cart = $this->getCart(); 

        $count = CartItem::where('cart_id', $cart->id)->sum('quantity') ?? 0;

        // Cart items with stock status
        $cartData = CartItem::with(['product', 'variant', 'product.images'])
            ->where('cart_id', $cart->id)
            ->get()
            ->map(function ($item) {
                $item->is_available = $item->variant && $item->variant->quantity > 0;
                $item->error_message = !$item->is_available ? 'Out of Stock or Removed' : null;
                return $item;
            });

        // Can checkout only if all items are available
        $can_checkout = $cartData->every('is_available', true);

        return [
            'count' => $count,
            'cartData' => $cartData,
            'can_checkout' => $can_checkout,
        ];
    }

    public function cartItemUpdateData($request, $id)
    {
        $cartItem = CartItem::where('id', $id)
            ->whereHas('cart', function($query) {
                $query->where('user_id', auth()->id());
            })->first();

        if ($cartItem) {
            $cartItem->update([
                'quantity' => $request->quantity
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Quantity updated'
            ]);
        }
        else {
            return response()->json(['status' => false, 'message' => 'Item not found'], 404);
        }
    }

    public function cartItemDeleteData($id, $request)
    {
        $cartItem = CartItem::where('id', $id)
            ->whereHas('cart', function($query) {
                $query->where('user_id', auth()->id());
            })->firstOrFail();

        $cartItem->delete();

        return response()->json([
            'status' => true,
            'message' => 'Item removed from cart'
        ]);

    }
}