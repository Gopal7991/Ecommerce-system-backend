<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Cart, Coupon, CartItem, User };
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\CouponStoreRequest;
use App\Repositories\CouponRepository;

use Carbon\Carbon;

class CouponController extends Controller
{
    protected $couponRepo;

    public function __construct(CouponRepository $couponRepo)
    {
        $this->couponRepo = $couponRepo;
    }
    public function index(Request $request)
    {
        $coupons = $this->couponRepo->all($request);
        
        return response()->json([
            'success' => true,
            'message' => 'Product list fetched successfully',
            'data' => $coupons['data'],
            'total' => $coupons['total'],
            'per_page' => $coupons['per_page'],
            'current_page' => $coupons['current_page'],
        ]);
        // return response()->json([
        //     'message' => 'Coupon List',
        //     'data' => $coupons
        // ]);
    }

    public function store(CouponStoreRequest $request)
    {
        $coupon = $this->couponRepo->couponStore($request);
        return response()->json([
            'message' => 'Coupon Added Successfully',
            'data' => $coupon
        ]);
    }

    public function edit($id)
    {
        $coupon = $this->couponRepo->couponEdit($id);
        return response()->json($coupon);
    }

    public function update(CouponStoreRequest $request, $id)
    {
        $coupon = $this->couponRepo->couponUpdate($request, $id);
        return response()->json(['message' => 'Coupon updated successfully!']);
    }

    public function destroy($id)
    {
        $coupon = $this->couponRepo->couponDestroy($id);
        return response()->json(['message' => 'Deleted']);
    }

    public function applyCoupon(Request $request)
    {
        $result = $this->couponRepo->CouponApplyed($request);
        
        if (!$result['status']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ]);
    }

    public function postData()
    {
        return response()->json([
            'message' => 'Coupon Added Successfully',
        ]);
    }
    public function postStore(Request $request)
    {
        echo "<pre>"; print_r($request->all());exit;
        return response()->json([
            'message' => 'Coupon Added Successfully',
        ]);
    }
}
