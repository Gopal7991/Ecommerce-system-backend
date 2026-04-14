<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\OrderRepository;
use App\Models\{Cart, ProductVariant, CartItem, Order, OrderProduct,User };

class OrderController extends Controller
{
    protected $orderRepo;

    public function __construct(OrderRepository $orderRepo)
    {
        $this->orderRepo = $orderRepo;
    }

    public function orderHistory(Request $request)
    {
        $histories = $this->orderRepo->orderHistoryData();
        return response()->json([
            'histories' => $histories
        ]);
        
    }

    public function allOrders(Request $request)
    {
        $orders = $this->orderRepo->getAll($request);

        return response()->json([
            'success' => true,
            'message' => 'Orders list fetched successfully',
            'data' => $orders['data'],
            'total' => $orders['total'],
            'per_page' => $orders['per_page'],
            'current_page' => $orders['current_page'],
        ]);
        // return response()->json([
        //         'orders' => $orders
        //     ]);
    }

    public function myOrder(Request $request)
    {
        $orders = $this->orderRepo->getMyOrder($request);

        return response()->json([
            'orders' => $orders
        ]);
    }
        
}



// public function myOrder(Request $request)
// {
//     $userId = auth()->id();

//     $orders = Order::with('orderProducts.product.images','orderProducts.variant','coupon')
//         ->where('user_id', $userId)
//         ->latest()
//         ->get();
//     $orders->each(function($order) {
//         $order->orderProducts->each(function($item) {
//             if ($item->product->images->count() > 0) {
//                 $imagePath = storage_path('app/public/' . $item->product->images->first()->image);
                    
//                 if (file_exists($imagePath)) {
//                     $base64 = 'data:image/' . pathinfo($imagePath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($imagePath));
//                         $item->product->images[0]->base64 = $base64; // Add a new attribute
//                 } else {
//                     $item->product->images[0]->base64 = null;
//                 }
//             }
//         });
//     });

//     return response()->json([
//         'orders' => $orders
//     ]);
// }