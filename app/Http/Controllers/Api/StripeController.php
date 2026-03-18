<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stripe\PaymentIntent;
use Stripe\Stripe;

require_once base_path('vendor\stripe\stripe-php\init.php');

class StripeController extends Controller
{
        
  public function initiatePayment(Request $request)
{
    Stripe::setApiKey(config('services.stripe.secret'));
    $caBundle = config('services.stripe.ca_bundle');
    if (is_string($caBundle) && $caBundle !== '') {
        $caBundlePath = $caBundle;
        // Allow relative paths like "storage/certs/cacert_with_norton.pem"
        if (!preg_match('/^[A-Za-z]:\\\\/', $caBundlePath) && !str_starts_with($caBundlePath, '/')) {
            $caBundlePath = base_path($caBundlePath);
        }
        if (is_file($caBundlePath)) {
            Stripe::setCABundlePath($caBundlePath);
        }
    }

    try {
        $amount = $request->amount ?? 1000; // default ₹10
        $currency = $request->currency ?? 'inr';

        $paymentIntent = PaymentIntent::create([
            'amount' => $amount,
            'currency' => $currency,
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);

        return response()->json([
            'clientSecret' => $paymentIntent->client_secret // ✅ MATCH FRONTEND
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}
}
