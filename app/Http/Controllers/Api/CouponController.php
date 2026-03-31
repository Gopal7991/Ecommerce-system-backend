<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Cart, Coupon, CartItem, User };
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\CouponStoreRequest;
use Carbon\Carbon;

class CouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::all();

        return response()->json([
            'message' => 'Coupon List',
            'data' => $coupons
        ]);
    }

    public function store(CouponStoreRequest $request)
    {
        $coupon = Coupon::create($request->all());

        return response()->json([
            'message' => 'Coupon Added Successfully',
            'data' => $coupon
        ]);
    }

    public function edit($id)
    {
        $coupon = Coupon::findOrFail($id);

        return response()->json($coupon);
    }

    public function update(CouponStoreRequest $request, $id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->update($request->validated());

        return response()->json(['message' => 'Coupon updated successfully!']);
    }

    public function destroy($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function applyCoupon(Request $request)
    {
        $coupon = Coupon::where('code',$request->code)->first();
        if (!$coupon) {
            return response()->json(['error_message' => 'This coupon code does not exist!']);
        }

        if($coupon->max_attach <= $coupon->used_count) {
            return response()-> json(['error_message' => 'This coupon code is expide!']);
        }
        
        $currentDate = Carbon::now()->format('Y-m-d');
        if ($currentDate > $coupon->end_date) {
            return response()->json(['error_message' => 'This coupon code is expide!']);
        }
        if ($currentDate < $coupon->start_date) {
            return response()->json(['error_message' => 'This coupon Currently not apply Apply after Some Time!']);
        }

        $cart = Cart::where('user_id', auth()->id())->first();
        $allCartData = CartItem::with(['product','variant'])->where('cart_id',$cart->id)->get();
        $brandId = $coupon->brand_id;
        $discountAmount = 0;
        $Subtotal = 0;
        $productDiscount = [];
        $appliedItemIds = [];
        $appliedCartProduct = [];
        
        if ($brandId) {

            foreach ($allCartData as $item) {
                if ($item->product->brand_id == $brandId) {
                    $price = $item->product->price;
                    $Subtotal += ($price * $item->quantity);
                    $appliedItemIds[] = $item->id;
                    if ($coupon->coupon_type == 'percentage') {
                        $productDiscount[] = ($price * $coupon->discount_percentage) / 100;
                    }
                }
            }
            if(count($appliedItemIds) == 0) {
                return response()->json(['error_message' => 'This Coupon Not Available In This Product!']);
            }
            // echo "<pre>"; print_r($productDiscount);exit;
            if ($coupon->coupon_type == 'percentage') {
                $discountAmount = ($Subtotal * $coupon->discount_percentage) / 100;
            } else {
                $discountAmount = $coupon->max_discount;
            }

            if($coupon->max_discount <= $discountAmount) {
                $discountAmount = $coupon->max_discount;
            }
            if($Subtotal < $coupon->min_order_amount) {
                $discountAmount = 0;
            }
            
        }
        else {

            foreach ($allCartData as $item) {
                $price = $item->product->price;
                $Subtotal += ($price * $item->quantity);
                $appliedItemIds[] = $item->id;
            }
            if ($coupon->coupon_type == 'percentage') {
                $discountAmount = ($Subtotal * $coupon->discount_percentage) / 100;
            } else {
                $discountAmount = $coupon->max_discount;
            }
            if($coupon->max_discount <= $discountAmount) {
                $discountAmount = $coupon->max_discount;
            }
            if($Subtotal < $coupon->min_order_amount) {
                $discountAmount = 0;
            }

        }
        // echo "<pre>"; print_r($appliedItemIds);exit;
        return response()->json([
            'allCartData' => $allCartData,
            'appliedItemIds' => $appliedItemIds,
            'discount' => $discountAmount,
            'message' => 'Coupon Apply Successfully!'
        ]);

    }
}
