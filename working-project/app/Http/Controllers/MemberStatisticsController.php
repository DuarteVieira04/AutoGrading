<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\Operation;
use App\Models\Category;

class MemberStatisticsController extends Controller
{
    public function index()
    {
        $memberId = Auth::id();

        // Totais do Membro
        $totalSpent = Order::where('member_id', $memberId)->where('status', 'completed')->sum('total');
        $totalOrders = Order::where('member_id', $memberId)->count();

        // Últimas 5 transações (créditos e débitos)
        // O card_id é o mesmo que o user_id, conforme o enunciado
        $lastTransactions = Operation::where('card_id', $memberId)
            ->latest('created_at')
            ->take(5)
            ->get();

        // Categorias mais compradas pelo membro
        $topCategories = Category::select('categories.name', DB::raw('COUNT(items_orders.id) as products_bought'))
            ->join('products', 'categories.id', '=', 'products.category_id')
            ->join('items_orders', 'products.id', '=', 'items_orders.product_id')
            ->join('orders', 'items_orders.order_id', '=', 'orders.id')
            ->where('orders.member_id', $memberId)
            ->where('orders.status', 'completed')
            ->groupBy('categories.name')
            ->orderByDesc('products_bought')
            ->take(5)
            ->get();


        return view('member.statistics.index', compact(
            'totalSpent',
            'totalOrders',
            'lastTransactions',
            'topCategories'
        ));
    }
}
