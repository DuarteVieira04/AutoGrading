<?php
// app/Http/Controllers/DashboardController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\UserType;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        switch ($user->type) {
            case UserType::BOARD:
                // Pode buscar dados rápidos para o admin aqui (ex: nº de users, encomendas pendentes)
                $stats = ['users' => \App\Models\User::count(), 'pendingOrders' => \App\Models\Order::where('status', 'pending')->count()];
                return view('dashboards.board', compact('stats'));

            case UserType::EMPLOYEE:
                // Dados para o funcionário (ex: encomendas a preparar, stock baixo)
                $lowStockProducts = \App\Models\Product::whereRaw('stock < stock_lower_limit')->count();
                return view('dashboards.employee', compact('lowStockProducts'));

            case UserType::MEMBER:
                // Dados para o membro (ex: saldo do cartão, última encomenda)
                $balance = $user->card->balance;
                return view('dashboards.member', compact('balance'));

            case UserType::PENDING_MEMBER:
                // Vista específica para pagar a quota
                $membershipFee = \App\Models\Setting::first()->membership_fee;
                return view('dashboards.pending_member', compact('membershipFee'));

            default:
                // Uma vista genérica ou redirecionamento para o catálogo
                return redirect()->route('catalog.index');
        }
    }
}
