<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use App\Models\{Order, Coupon, Cart, CartItem, OrderProduct, ProductVariant};
use Stripe\Checkout\Session;

class StripeRepository
{
	public function createCheckout(array $data, $user)
	{
	    try {
	        return DB::transaction(function () use ($data, $user) {

	            $coupon = Coupon::where('code', $data['couponcode'] ?? null)->first();
	            $couponId = $coupon ? $coupon->id : null;

	            $totalDiscountAmount = $data['discount'] ?? 0;
	            $appliedCartItemIds = $data['discountproduct'] ?? [];

	            $order = Order::updateOrCreate(
	                ['user_id' => $user->id, 'status' => 'pending'],
	                [
	                    'name'     => $data['shipping']['name'],
	                    'email'    => $data['shipping']['email'],
	                    'address'  => $data['shipping']['address'],
	                    'city'     => $data['shipping']['city'],
	                    'state'    => $data['shipping']['state'],
	                    'zipcode'  => $data['shipping']['zip'],
	                    'phone'    => $data['shipping']['phone'],
	                    'amount'   => $data['amount'],
	                    'coupon_id'=> $couponId,
	                ]
	            );

	            $cart = Cart::where('user_id', $user->id)->first();
	            $allCartData = CartItem::where('cart_id', $cart->id)->get();

	            $eligibleProductMatch = $allCartData->whereIn('id', $appliedCartItemIds)
	                ->map(fn($item) => $item->product_id . '-' . $item->product_variant_id)
	                ->toArray();

	            $eligibleSubtotal = 0;

	            foreach ($data['items'] as $item) {
	                $key = $item['product_id'] . '-' . ($item['product_variant_id'] ?? 0);
	                if (in_array($key, $eligibleProductMatch)) {
	                    $eligibleSubtotal += ($item['price'] * $item['quantity']);
	                }
	            }

	            $lineItems = [];

	            foreach ($data['items'] as $item) {

	                $key = $item['product_id'] . '-' . ($item['product_variant_id'] ?? 0);
	                $discount = 0;

	                if ($eligibleSubtotal > 0 && in_array($key, $eligibleProductMatch)) {
	                    $lineTotal = $item['price'] * $item['quantity'];
	                    $ratio = $lineTotal / $eligibleSubtotal;
	                    $discount = round($ratio * $totalDiscountAmount, 2);
	                }

	                $finalPrice = ($item['price'] * $item['quantity'] - $discount) / $item['quantity'];

	                $lineItems[] = [
	                    'price_data' => [
	                        'currency' => 'usd',
	                        'product_data' => ['name' => $item['name']],
	                        'unit_amount' => (int)(round($finalPrice, 2) * 100),
	                    ],
	                    'quantity' => $item['quantity'],
	                ];

	                OrderProduct::updateOrCreate(
	                    [
	                        'order_id' => $order->id,
	                        'product_id' => $item['product_id'],
	                        'product_variant_id' => $item['product_variant_id'] ?? 0,
	                    ],
	                    [
	                        'quantity' => $item['quantity'],
	                        'price' => (int)$item['price'],
	                        'discount_price' => $discount,
	                    ]
	                );
	            }

	            Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

	            $session = Session::create([
	                'line_items' => $lineItems,
	                'mode' => 'payment',
	                'success_url' => 'http://localhost:5173/payment-success?session_id={CHECKOUT_SESSION_ID}',
	                'cancel_url' => route('payment.cancel'),
	                'metadata' => [
	                    'user_id' => $user->id,
	                    'order_id' => $order->id,
	                ],
	            ]);

	            return [
	                'status' => true,
	                'url' => $session->url
	            ];
	        });

	    } catch (\Exception $e) {
	        return [
	            'status' => false,
	            'message' => $e->getMessage()
	        ];
	    }
	}
    public function handleSuccess($sessionId)
	{
	    try {
	        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

	        $session = Session::retrieve([
	            'id' => $sessionId,
	            'expand' => ['payment_intent']
	        ]);

	        $order = Order::findOrFail($session->metadata->order_id);

	        if ($order->status === 'paid') {
	            return ['status' => true, 'data' => $order];
	        }

	        if ($session->payment_status === 'paid') {

	            DB::transaction(function () use ($order, $session) {

	                $order->update([
	                    'status' => 'paid',
	                    'transaction_id' => $session->payment_intent->id
	                ]);

	                Cart::where('user_id', $order->user_id)->delete();

	                foreach ($order->orderProducts as $item) {
	                    ProductVariant::find($item->product_variant_id)
	                        ?->decrement('quantity', $item->quantity);
	                }

	                if ($order->coupon_id) {
	                    Coupon::where('id', $order->coupon_id)->increment('used_count');
	                }
	            });

	            return [
	                'status' => true,
	                'message' => 'Payment successful',
	                'data' => $order->load(['orderProducts.product.images'])
	            ];
	        }

	        return ['status' => false, 'message' => 'Payment failed'];

	    } catch (\Exception $e) {
	        return ['status' => false, 'message' => $e->getMessage()];
	    }
	}
}