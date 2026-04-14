<?php

namespace App\Repositories;
use Illuminate\Support\Facades\Auth;
use App\Models\{Coupon, Cart,CartItem};
use Carbon\Carbon;
use Illuminate\Http\Request;

class CouponRepository
{
    public function all(Request $request)
    {                   
        $perPage = $request->input('per_page', 5);
        $page = $request->input('page', 1);
        $search = $request->input('search', ''); 
        // $coupons = Coupon::where('is_active','1')->latest()->get();
        $query = Coupon::where('is_active', '1')->latest();
        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $coupons = $query->paginate($perPage);
        return [
            'data' => $coupons->items(),
            'total' => $coupons->total(),
            'per_page' => $coupons->perPage(),
            'current_page' => $coupons->currentPage(),
        ];
        // return $coupons;
    }

    public function couponStore($request)
    {
        $request->merge(['used_count' => 0]);
        $coupon = Coupon::create($request->all());
        return $coupon;
    }

    public function couponEdit($id)
    {
        $coupon = Coupon::findOrFail($id);
        return $coupon;
    }

    public function couponUpdate($request, $id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->update($request->validated());
        return $coupon;
    }

    public function couponDestroy($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();
        return $coupon;

    }

    public function CouponApplyed($request)
    {
        $coupon = Coupon::where('code', $request->code)->first();

        if (!$coupon) {
            return [
                'status' => false,
                'message' => 'This coupon code does not exist!'
            ];
        }

        if ($coupon->max_attach <= $coupon->used_count) {
            return [
                'status' => false,
                'message' => 'This coupon code has reached maximum usage!'
            ];
        }

        $currentDate = Carbon::now()->format('Y-m-d');

        if ($currentDate > $coupon->end_date) {
            return [
                'status' => false,
                'message' => 'This coupon code has expired!'
            ];
        }

        if ($currentDate < $coupon->start_date) {
            return [
                'status' => false,
                'message' => 'This coupon is not active yet!'
            ];
        }

        $cart = Cart::where('user_id', auth()->id())->first();

        if (!$cart) {
            return [
                'status' => false,
                'message' => 'Cart not found!'
            ];
        }

        $allCartData = CartItem::with(['product','variant'])
            ->where('cart_id', $cart->id)
            ->get();

        $brandId = $coupon->brand_id;
        $discountAmount = 0;
        $subtotal = 0;
        $appliedItemIds = [];

        if ($brandId) {

            foreach ($allCartData as $item) {
                if ($item->product->brand_id == $brandId) {
                    $price = $item->product->price;
                    $subtotal += ($price * $item->quantity);
                    $appliedItemIds[] = $item->id;
                }
            }

            if (count($appliedItemIds) == 0) {
                return [
                    'status' => false,
                    'message' => 'Coupon not applicable for these products!'
                ];
            }

            if ($subtotal < $coupon->min_order_amount) {
                return [
                    'status' => false,
                    'message' => "Minimum {$coupon->min_order_amount} required for this brand!"
                ];
            }

        } else {

            foreach ($allCartData as $item) {
                $price = $item->product->price;
                $subtotal += ($price * $item->quantity);
                $appliedItemIds[] = $item->id;
            }

            if ($subtotal < $coupon->min_order_amount) {
                return [
                    'status' => false,
                    'message' => "Minimum {$coupon->min_order_amount} required in cart!"
                ];
            }
        }

        // Discount
        if ($coupon->coupon_type == 'percentage') {
            $discountAmount = ($subtotal * $coupon->discount_percentage) / 100;
        } else {
            $discountAmount = $coupon->max_discount;
        }

        if ($discountAmount > $coupon->max_discount) {
            $discountAmount = $coupon->max_discount;
        }

        return [
            'status' => true,
            'message' => 'Coupon applied successfully!',
            'data' => [
                'allCartData' => $allCartData,
                'appliedItemIds' => $appliedItemIds,
                'discount' => $discountAmount
            ]
        ];
    }
}