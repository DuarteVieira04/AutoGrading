<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SupplyOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class SupplyOrderController extends Controller
{

    public function create()
    {
        // Busca produtos com stock baixo ou esgotado para sugerir na encomenda
        $productsToRestock = Product::whereColumn('stock', '<=', 'stock_lower_limit')
                                    ->orWhere('stock', '=', 0)
                                    ->get();

        return view('admin.supply-orders.create', ['products' => $productsToRestock]);
    }

    public function store(Request $request)
    {
        // Valida os dados recebidos do formulário
        $request->validate([
            'products' => 'required|array',
            'products.*.quantity' => 'required|integer|min:1'
        ]);

        $productsData = $request->input('products');
        $createdCount = 0;

        foreach ($productsData as $productId => $details) {
            // Cria uma nova encomenda de fornecimento para cada produto selecionado
            SupplyOrder::create([
                'product_id' => $productId,
                'quantity' => $details['quantity'],
                'status' => 'requested', // O estado inicial é 'requested'
                'registered_by_user_id' => Auth::id(),
            ]);
            $createdCount++;
        }

        if ($createdCount > 0) {
            return redirect()->route('admin.inventory.index')->with('success', "$createdCount encomendas de fornecimento criadas com sucesso!");
        }

        return redirect()->back()->with('error', 'Nenhum produto foi selecionado para encomenda.');
    }

    public function index()
    {
        $supplyOrders = SupplyOrder::with('product', 'registered_by')
                                   ->latest()
                                   ->paginate(15);

        return view('admin.supply-orders.index', ['supplyOrders' => $supplyOrders]);
    }

    public function update(SupplyOrder $supply_order)
    {
        if ($supply_order->status === 'requested') {
            $supply_order->product->increment('stock', $supply_order->quantity);

            $supply_order->status = 'completed';
            $supply_order->save();

            return redirect()->back()->with('success', 'Encomenda de fornecimento marcada como concluída e stock atualizado!');
        }

        return redirect()->back()->with('error', 'Esta encomenda já foi processada.');
    }


    public function destroy(SupplyOrder $supply_order)
    {
        if ($supply_order->status === 'requested') {
            $supply_order->delete();
            return redirect()->back()->with('success', 'Encomenda de fornecimento apagada com sucesso.');
        }

        return redirect()->back()->with('error', 'Não é possível apagar uma encomenda já concluída.');
    }
}
