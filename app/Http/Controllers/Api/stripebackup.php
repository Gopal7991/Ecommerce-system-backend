<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Models\{Order, OrderProduct, Cart, ProductVariant, Coupon, CartItem};
use App\Events\{OrderSuccess,OrderInvoice};
use Illuminate\Support\Facades\Auth;

class StripeController extends Controller
{

    public function checkout(Request $request)
    {
        $coupon = Coupon::where('code', $request->couponcode)->first();
        $couponId = $coupon ? $coupon->id : null;

        $totalDiscountAmount = $request->discount ?? 0;
        $appliedCartItemIds = $request->discountproduct ?? [];

        return DB::transaction(function () use ($request, $couponId, $totalDiscountAmount, $appliedCartItemIds) {
            
            $order = Order::updateOrCreate(
                ['user_id' => $request->user()->id, 'status' => 'pending'],
                [
                    'name'     => $request->shipping['name'],
                    'email'    => $request->shipping['email'],
                    'address'  => $request->shipping['address'],
                    'city'     => $request->shipping['city'],
                    'state'    => $request->shipping['state'],
                    'zipcode'  => $request->shipping['zip'],
                    'phone'    => $request->shipping['phone'],
                    'amount'   => $request->amount,
                    'coupon_id'=> $couponId,
                ]
            );

            // OrderProduct::where('order_id', $order->id)->delete();

            $cart = Cart::where('user_id', auth()->id())->first();
            $allCartData = CartItem::where('cart_id', $cart->id)->get();
            
            $eligibleProductMatch = $allCartData->whereIn('id', $appliedCartItemIds)
                ->map(fn($item) => $item->product_id . '-' . $item->product_variant_id)
                ->toArray();

            $eligibleSubtotal = 0;
            foreach ($request->items as $item) {
                $itemKey = $item['product_id'] . '-' . ($item['product_variant_id'] ?? 0);
                if (in_array($itemKey, $eligibleProductMatch)) {
                    $eligibleSubtotal += ($item['price'] * $item['quantity']);
                }
            }

            $lineItems = [];
            foreach ($request->items as $item) {
                $itemKey = $item['product_id'] . '-' . ($item['product_variant_id'] ?? 0);
                $itemTotalDiscount = 0;

                if ($eligibleSubtotal > 0 && in_array($itemKey, $eligibleProductMatch)) {
                    $itemLineTotal = $item['price'] * $item['quantity'];
                    $ratio = $itemLineTotal / $eligibleSubtotal;
                    $itemTotalDiscount = round($ratio * $totalDiscountAmount, 2);
                }

                $finalProductPrice = ($item['price'] * $item['quantity'] - $itemTotalDiscount) / $item['quantity'];

                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => ['name' => $item['name']],
                        'unit_amount' => (int)(round($finalProductPrice, 2) * 100),
                    ],
                    'quantity' => $item['quantity'],
                ];
                OrderProduct::updateOrCreate(
                    [
                        'order_id'           => $order->id,
                        'product_id'         => $item['product_id'],
                        'product_variant_id' => $item['product_variant_id'] ?? 0,
                    ],
                    [
                        'quantity'           => $item['quantity'],
                        'price'              => (int)$item['price'],
                        'discount_price'     => $itemTotalDiscount,
                    ]
                );
                
            }
            $requestedProductIds = collect($request->items)->pluck('product_id');

            OrderProduct::where('order_id', $order->id)
                ->whereNotIn('product_id', $requestedProductIds)
                ->delete();
                
            Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
            $session = Session::create([
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => 'http://localhost:5173/payment-success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('payment.cancel'),
                'metadata' => [
                    'user_id' => $request->user()->id,
                    'order_id' => $order->id,
                ],
            ]);

            return response()->json(['url' => $session->url]);
        });
    }

   
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            return response()->json(['error' => 'No session ID provided'], 400);
        }

        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

        try {
            $checkoutSession = Session::retrieve([
                'id' => $sessionId,
                'expand' => ['payment_intent']
            ]);

            $orderId = $checkoutSession->metadata->order_id;
            $order = Order::findOrFail($orderId);

            if ($order->status === 'paid') {
                return response()->json([
                    'message' => 'Order already processed.',
                    'order' => $order->load(['orderProducts.product.images', 'coupon'])
                ]);
            }

            if ($checkoutSession->payment_status === 'paid') {
                
                $processedOrder = DB::transaction(function () use ($checkoutSession, $order, $sessionId) {
                    
                    $order->update([
                        'status' => 'paid',
                        'session_id' => $sessionId,
                        'transaction_id' => $checkoutSession->payment_intent->id
                    ]);

                    $cart = Cart::where('user_id', $order->user_id)->first();
                    if ($cart) {
                        $cart->items()->delete(); 
                        $cart->delete(); 
                    }

                    foreach ($order->orderProducts as $item) {
                        $variant = ProductVariant::find($item->product_variant_id);
                        if ($variant) {
                            $variant->decrement('quantity', $item->quantity);
                        }
                    }

                    if ($order->coupon_id) {
                        Coupon::where('id', $order->coupon_id)->increment('used_count');
                    }

                    return $order->load(['orderProducts.product.images', 'coupon']);
                });

                // event(new OrderSuccess($processedOrder));

                return response()->json([
                    'message' => 'Payment successful!',
                    'order' => $processedOrder,
                    'transaction_id' => $checkoutSession->payment_intent->id,
                    'amount' => $checkoutSession->amount_total / 100
                ]);

            } else {
                $order->update(['status' => 'failed']);
                return response()->json(['message' => 'Payment failed', 'order' => $order], 402);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    public function cancel(Request $request)
    {
        return view('cancel');
    }

    public function sendReceipt(Request $request)
    {
        $order = Order::findOrFail($request->order_id);
        $path = $request->file('receipt')->store('receipts'); 
        event(new OrderInvoice($order, $path));

        return response()->json(['message' => 'Email is being sent!']);
        // event(new OrderInvoice($order));
    } 
}

