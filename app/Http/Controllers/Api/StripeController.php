<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Models\{Order, OrderProduct, Cart, ProductVariant, Coupon, CartItem};
use App\Events\OrderSuccess;
use Illuminate\Support\Facades\Auth;

class StripeController extends Controller
{
    public function checkout(Request $request)
    {
        // echo "<pre>"; print_r($request->couponcode);exit;
        $coupon = Coupon::where('code',$request->couponcode)->first();
        $couponId = null;
        if ($coupon) {
            $couponId = $coupon->id;
        }
        $totalDiscountAmount = $request->discount ?? 0; 
        $appliedCartItemIds = $request->discountproduct ?? [];
        $eligibleSubtotal = 0;
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        $order = Order::updateOrCreate([

            'user_id' => $request->user()->id,
            'status'  => 'pending'
        ],
        [
            'name'     => $request->shipping['name'],
            'email'    => $request->user()->email,
            'address'  => $request->shipping['address'],
            "city"     => $request->shipping['city'],
            'state'    => $request->shipping['state'],
            'zipcode'  => $request->shipping['zip'],
            'phone'    => $request->shipping['phone'],
            'amount'   => $request->amount,
            'coupon_id' =>$couponId,
            ]
        );
       try {
        $cart = Cart::where('user_id', auth()->id())->first();
        $allCartData = CartItem::with(['product','variant'])->where('cart_id',$cart->id)->get();
        $eligibleProductMatch = $allCartData->whereIn('id', $appliedCartItemIds)
            ->map(function($cartItem) {
                return $cartItem->product_id . '-' . $cartItem->product_variant_id;
            })->toArray();
        foreach ($request->items as $item) {
            $itemKey = $item['product_id'] . '-' . ($item['product_variant_id'] ?? 0);
            
            if (in_array($itemKey, $eligibleProductMatch)) {
                $eligibleSubtotal += ($item['price'] * $item['quantity']);
            }
        }
        $lineItems = [];

        foreach ($request->items as $item) {
            $itemTotalDiscount = 0;
            $itemKey = $item['product_id'] . '-' . ($item['product_variant_id'] ?? 0);
            if ($eligibleSubtotal > 0 && in_array($itemKey, $eligibleProductMatch)) {
                $itemLineTotal = $item['price'] * $item['quantity'];
                $ratio = $itemLineTotal / $eligibleSubtotal;
                $itemTotalDiscount = round($ratio * $totalDiscountAmount, 2);
            }
            $finalProductPrice = ($item['price'] * $item['quantity'] - $itemTotalDiscount) / $item['quantity'];
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $item['name'],
                    ],
                    'unit_amount' => (int)(round($finalProductPrice, 2) * 100),  
                ],
                'quantity' => $item['quantity'],
            ];
            OrderProduct::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'product_variant_id' => $item['product_variant_id'],
                'quantity' => $item['quantity'],
                'price' => (int)($item['price']),
                'discount_price'    => $itemTotalDiscount,
            ]);
        }

        $session = Session::create([
            'line_items' => $lineItems,

            'mode' => 'payment',
            'success_url' => route('payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('payment.cancel'),
            'metadata' => [
                'user_id' => $request->user()->id,
                'order_id' => $order->id, 
            ],
        ]);
        $transactionId = $checkoutSession->payment_intent->id; 
        echo $transactionId; exit;
        $orderId = $checkoutSession->metadata->order_id;
        return response()->json(['url' => $session->url]);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
    }
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');
        // echo '<pre>'; print_r($sessionId);exit;

            if (!$sessionId) {
                return response()->json(['error' => 'No session ID provided'], 400);
            }

            Stripe::setApiKey(env('STRIPE_SECRET_KEY'));


            try {
                // $checkoutSession = \Stripe\Checkout\Session::retrieve($sessionId);
                // $orderId = $checkoutSession['metadata']['order_id'];

                $checkoutSession = Session::retrieve([
                    'id' => $sessionId,
                    'expand' => ['payment_intent']
                ]);
                
                $transactionId = $checkoutSession->payment_intent->id;
                $orderId = $checkoutSession->metadata->order_id;
                $order = Order::findOrFail($orderId);
                $orderProduct = OrderProduct::where('order_id',$orderId)->get();
                if ($checkoutSession->payment_status === 'paid') {
                    $order->update([
                        'status' => 'paid',
                        'session_id' => $sessionId
                    ]);
                    $customerEmail = $checkoutSession->customer_details->email;
                    // event(new OrderSuccess($order));
                    $userId = $order->user_id;

                    $cart = Cart::where('user_id', $userId)->first();
                    if ($cart) {
                        $cart->items()->delete(); 
                        $cart->delete(); 
                    }
                    foreach ($orderProduct as $item) {
                        $variant = ProductVariant::find($item->product_variant_id);
                        if ($variant) {
                            $variant->decrement('quantity', $item->quantity);
                        }
                    }
                    $couponCount = Coupon::where('id', $order->coupon_id)->first();
                    if($couponCount) {
                        $couponCount->used_count = ($couponCount->used_count ?? 0) + 1;
                        $couponCount->save();
                    }
                    // return response()->json([
                    //     'message' => 'Payment successful!',
                    //     'customer' => $customerEmail,
                    //     'amount' => $checkoutSession->amount_total / 100
                    // ]);
                    return response()->json([
                        'status' => $session->payment_status,
                        'transaction_id' => $transactionId,
                        'order_id' => $order?->id,
                        'amount' => $session->amount_total / 100
                    ]);
                } else {
                    $order->update(['status' => 'failed']);
                    return response()->json(['message' => 'Payment failed', 'order' => $order], 402);
                }
            } catch (\Exception $e) {
                return response()->json(['error' => 'Stripe error: ' . $e->getMessage()], 500);
            }
        // echo "<pre>"; print_r($_REQUEST); exit;
        // return view('success');
    }

    public function cancel(Request $request)
    {
        //echo "<pre>"; print_r($request->all());exit;
        return view('cancel');
    }  
}

// public function checkout(Request $request)
// {
//     Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

//     $order = Order::updateOrCreate(
//         [
//             'user_id' => $request->user()->id,
//             'status'  => 'pending'
//         ],
//         [
//             'name'     => $request->user()->firstname,
//             'email'    => $request->user()->email,
//             'address'  => $request->shipping['address'],
//             "city"     => $request->shipping['city'],
//             'state'    => $request->shipping['state'],
//             'zipcode'  => $request->shipping['zip'],
//             'phone'    => $request->shipping['phone'],
//             'amount'   => $request->amount,
//         ]
//     );

//     try {
//         $order->orderProducts()->delete(); 

//         $lineItems = [];

//         foreach ($request->items as $item) {
//             $lineItems[] = [
//                 'price_data' => [
//                     'currency' => 'usd',
//                     'product_data' => ['name' => $item['name']],
//                     'unit_amount' => (int)($item['price'] * 100), 
//                 ],
//                 'quantity' => $item['quantity'],
//             ];

//             // 3. Re-insert the current items
//             OrderProduct::create([
//                 'order_id' => $order->id,
//                 'product_id' => $item['product_id'],
//                 'product_variant_id' => $item['product_variant_id'],
//                 'quantity' => $item['quantity'],
//                 'price' => (int)($item['price']), 
//             ]);
//         }

//         $session = Session::create([
//             'line_items' => $lineItems,
//             'mode' => 'payment',
//             'success_url' => route('payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
//             'cancel_url' => route('payment.cancel'),
//             'metadata' => [
//                 'user_id' => $request->user()->id,
//                 'order_id' => $order->id, 
//             ],
//         ]);

//         return response()->json(['url' => $session->url]);

//     } catch (\Exception $e) {
//         return response()->json(['error' => $e->getMessage()], 500);
//     }
// }
