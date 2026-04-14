<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\DashboardRepository;
class DashboardController extends Controller
{
    protected $dashboardRepo;

    public function __construct(DashboardRepository $dashboardRepo)
    {
        $this->dashboardRepo = $dashboardRepo;
    }
    
    public function index(Request $request)
    {
        $dashboardData = $this->dashboardRepo->getDashboardData($request);
        return response()->json($dashboardData);
    }
}
