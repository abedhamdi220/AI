<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Design;
use App\Models\Order;
use App\Models\ShowcaseDesign;
use Illuminate\Http\Request;

class AdminStatsController extends Controller
{
    public function stats()
    {
        try {
            $totalUsers = User::count();
            $totalOrders = Order::count();
            $totalDesigns = Design::count();
            $totalShowcase = ShowcaseDesign::count();
            $pendingOrders = Order::where('status', 'pending')->count();
            $completedOrders = Order::where('status', 'completed')->count();

            $orders = Order::select('final_price')->get();
            $totalRevenue = $orders->sum('final_price');

            return response()->json([
                'total_users' => $totalUsers,
                'total_orders' => $totalOrders,
                'total_designs' => $totalDesigns,
                'total_showcase' => $totalShowcase,
                'pending_orders' => $pendingOrders,
                'completed_orders' => $completedOrders,
                'total_revenue' => $totalRevenue,
            ]);
        } catch (\Exception $error) {
            \Log::error('Get Stats Error: ' . $error->getMessage());
            return response()->json(['detail' => 'خطأ في جلب الإحصائيات'], 500);
        }
    }
}
