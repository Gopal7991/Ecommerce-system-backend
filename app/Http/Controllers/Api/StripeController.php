<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Repositories\StripeRepository;
use App\Models\{Order, OrderProduct, Cart, ProductVariant, Coupon, CartItem};
use App\Events\{OrderSuccess,OrderInvoice};
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\CheckoutRequest;


class StripeController extends Controller
{
    protected $stripeRepo;

    public function __construct(StripeRepository $stripeRepo)
    {
        $this->stripeRepo = $stripeRepo;
    }
    public function checkout(CheckoutRequest $request)
    {
        $result = $this->stripeRepo->createCheckout($request->all(), $request->user());

        if (!$result['status']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'url' => $result['url']
        ]);
    }

    // public function success(Request $request)
    // {
    //     $result = $this->stripeRepo->handleSuccess($request->session_id);

    //     if (!$result['status']) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => $result['message']
    //         ], 400);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'data' => $result['data']
    //     ]);
    // }
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

