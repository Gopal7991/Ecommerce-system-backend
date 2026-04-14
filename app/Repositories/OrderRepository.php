<?php
namespace App\Repositories;
use App\Models\{Order, OrderProduct};
use Illuminate\Http\Request;

class OrderRepository
{
    public function getAll(Request $request)
    {
        $perPage = $request->input('per_page', 5);
        $page = $request->input('page', 1);
        // $orders = Order::with('orderProducts.product.images','orderProducts.variant','coupon','user')
        //     ->latest()
        //     ->get();
        $query = Order::with('orderProducts.product.images','orderProducts.variant','coupon','user')->latest();
        $orders = $query->paginate($perPage);
        return [
            'data' => $orders->items(),
            'total' => $orders->total(),
            'per_page' => $orders->perPage(),
            'current_page' => $orders->currentPage(),
        ];
        // return $orders;
    }

    public function orderHistoryData()
    {
        $userId = auth()->id();

        $histories = Order::with('orderProducts.product.images','orderProducts.variant','coupon')
            ->where('user_id', $userId)
            ->get();
        $histories->each(function($order) {
            $order->orderProducts->each(function($item) {
                if ($item->product->images->count() > 0) {
                    // Take first image
                    $imagePath = storage_path('app/public/' . $item->product->images->first()->image);
                    
                    if (file_exists($imagePath)) {
                        $base64 = 'data:image/' . pathinfo($imagePath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($imagePath));
                        $item->product->images[0]->base64 = $base64; // Add a new attribute
                    } else {
                        $item->product->images[0]->base64 = null;
                    }
                }
            });
        });

        return $histories;

    }

    public function getMyOrder($request)
    {

        $userId = auth()->id();

        $orders = Order::with('orderProducts.product.images','orderProducts.variant','coupon')
            ->where('user_id', $userId)
            ->latest()
            ->get();
        $orders->each(function($order) {
            $order->orderProducts->each(function($item) {
                if ($item->product->images->count() > 0) {
                    // Take first image
                    $imagePath = storage_path('app/public/' . $item->product->images->first()->image);
                    
                    if (file_exists($imagePath)) {
                        $base64 = 'data:image/' . pathinfo($imagePath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($imagePath));
                        $item->product->images[0]->base64 = $base64; // Add a new attribute
                    } else {
                        $item->product->images[0]->base64 = null;
                    }
                }
            });
        });

        return $orders;
    }
}