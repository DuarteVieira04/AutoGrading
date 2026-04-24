<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;

class StatisticsController extends Controller
{
    public function index()
    {
        $totalRevenue = Order::where('status', 'completed')->sum('total');

        $totalMembers = User::whereIn('type', ['member', 'board'])->count();

        $salesByCategory = DB::table('items_orders')
            ->join('products', 'items_orders.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->join('orders', 'items_orders.order_id', '=', 'orders.id')
            ->where('orders.status', 'completed')
            ->select('categories.name as category_name', DB::raw('SUM(items_orders.subtotal) as total_sales'))
            ->groupBy('categories.name')
            ->orderBy('total_sales', 'desc')
            ->get();

        $topSellingProducts = DB::table('items_orders')
            ->join('products', 'items_orders.product_id', '=', 'products.id')
            ->join('orders', 'items_orders.order_id', '=', 'orders.id')
            ->where('orders.status', 'completed')
            ->select('products.name as product_name', DB::raw('SUM(items_orders.quantity) as total_quantity'))
            ->groupBy('products.name')
            ->orderBy('total_quantity', 'desc')
            ->limit(5)
            ->get();

        return view('admin.statistics.index', compact(
        'totalRevenue',
        'totalMembers',
        'salesByCategory',
        'topSellingProducts'
        ));
    }

    public function memberIndex()
    {
        $member = Auth::user();

        $totalSpent = $member->orders()->where('status', 'completed')->sum('total');

        $orderCount = $member->orders()->count();

        $operations = DB::table('operations')
                        ->where('card_id', $member->id) // No seu projeto, card_id = user_id
                        ->latest('date')
                        ->limit(5)
                        ->get();

        $spendingByCategory = DB::table('items_orders')
            ->join('orders', 'items_orders.order_id', '=', 'orders.id')
            ->join('products', 'items_orders.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('orders.member_id', $member->id)
            ->where('orders.status', 'completed')
            ->select('categories.name as category_name', DB::raw('SUM(items_orders.subtotal) as total_spent'))
            ->groupBy('categories.name')
            ->orderBy('total_spent', 'desc')
            ->get();

        return view('member.index', compact(
            'totalSpent',
            'orderCount',
            'operations',
            'spendingByCategory'
        ));
    }
}
