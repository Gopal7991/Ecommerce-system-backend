<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Cart, ProductVariant, CartItem, Order, OrderProduct,User };
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function cartStore(Request $request)
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

    public function cartCount(Request $request)
    {
        $cart = Cart::where('user_id', auth()->id())->first();
        $cartData = CartItem::with(['product','variant','product.images'])->where('cart_id',$cart->id)->get();
        $count = CartItem::where('cart_id',$cart->id)->sum('quantity');

        return response()->json([
            'count' => $count,
            'cartData' => $cartData
        ]);
    }

    public function orderHistory(Request $request)
    {

        $userId = auth()->id();

        $histories = Order::with('orderProducts.product.images','coupon')
            ->where('user_id', auth()->id())
            ->get();

            // @foreach($history as $order)
            //     @foreach($order->orderProducts as $item)
            //         {{ $item->product->name }}
            //         <img src="{{ $item->product->images->first()->image_url }}">
            //     @endforeach
            // @endforeach
            return response()->json([
                // 'count' => $count,
                'histories' => $histories
            ]);
    }

    public function dashboardData(Request $request)
    {
        $orders = Order::where('status', 'paid')->get();
        $totalOrderAmount = Order::where('status', 'paid')->sum('amount');
        $users = User::where('role',2)->get();
        $usercount = count($users);
        $count = count($orders);
        // print_r(count($orders));exit;
        return response()->json([
            'count' => $count,
            'orders' => $orders,
            'users' => $usercount,
            'ordersamount' => $totalOrderAmount
        ]);
    }

    public function cartItemUpdate(Request $request, $id)
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

        return response()->json(['status' => false, 'message' => 'Item not found'], 404);
    }


    public function cartItemDelete($id, Request $request)
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

    public function generateReceipt($id)
    {
        $orders = OrderProduct::where('order_id', $id)->get();
        $userId = auth()->id();

        $orderproducts = Order::where('id', $id)->with('orderProducts.product.images','coupon')
            ->where('user_id', auth()->id())
            ->get();

            // @foreach($history as $order)
            //     @foreach($order->orderProducts as $item)
            //         {{ $item->product->name }}
            //         <img src="{{ $item->product->images->first()->image_url }}">
            //     @endforeach
            // @endforeach
            return response()->json([
                // 'count' => $count,
                'orderProduct' => $orderproducts
            ]);
    }
}
