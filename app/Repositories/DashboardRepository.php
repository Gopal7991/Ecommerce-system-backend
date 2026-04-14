<?php
namespace App\Repositories;

use Illuminate\Support\Facades\Auth;
use App\Models\{Cart, ProductVariant, CartItem, Order, OrderProduct, User, Product };
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardRepository
{
		public function getDashboardData(Request $request)
		{
			$range = $request->query('range', 'all');
			$query = Order::where('status', 'paid');
			$startDate = null;
			$endDate = null;
			if($range === 'custom') {
				$startDate = $request->query('start_date');
        		$endDate = $request->query('end_date');
			}
			else if ($range === 'Week') {
			    $startDate = Carbon::now()->startOfWeek(Carbon::MONDAY); 
			    $endDate = Carbon::now()->endOfWeek(Carbon::SUNDAY);
			} else {
			    $startDate = match($range) {
			        'Today'     => Carbon::today(),
			        'Month'     => Carbon::now()->startOfMonth(),
			        'Year'      => Carbon::now()->startOfYear(),
			        default     => null,
			    };
			    $endDate = match($range) {
			        'Today'     => Carbon::today(),
			        'Month'     => Carbon::now()->endOfMonth(),
			        'Year'      => Carbon::now()->endOfYear(),
			        default     => null,
			    };
			}
			if($startDate)
			{
				if ($range === 'custom' ) {
					$query->whereBetween('created_at', [$startDate, $endDate]);
				} else if ($range === 'Today') {
			        $query->whereDate('created_at', $startDate);
			    } elseif ($range === 'Week') {
			        $query->whereBetween('created_at', [$startDate, $endDate]);
			    }else if ($range === 'Month') {
			    	$query->whereBetween('created_at', [$startDate, $endDate]);
			    }else if ($range === 'Year') {
			    	$query->whereBetween('created_at', [$startDate, $endDate]);
			    } else {
			        $query->where('created_at', '>=', $startDate);
			    }
			}
			$orders = (clone $query)->get();
			// $orders = Order::where('status', 'paid')->get();
	        $totalorderamount = $orders->sum('amount');
	        $users = User::where('role',2)->get();
	        $usercount = count($users);
	        $products = Product::where('is_active','1')->get();
	        $productcount = count($products);
	        $count = count($orders);
	        
	        return response()->json([
	            'ordercount' => $count,
	            'orders' => $orders,
	            'users' => $usercount,
	            'productcount' => $productcount,
	            'ordersamount' => $totalorderamount
	        ]);
		}
}