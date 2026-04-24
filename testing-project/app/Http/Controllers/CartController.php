<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\ItemsOrder;
use App\Models\Operation;
use App\Models\SettingsShippingCost;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function show()
    {
        $cart = session()->get('cart', []);
        $subtotal = 0;
        $cartWithDetails = [];

        if (!empty($cart)) {
            $productIds = array_keys($cart);
            $products = Product::find($productIds)->keyBy('id');

            foreach ($cart as $id => $quantity) {
                $product = $products->get($id);
                if ($product) {
                    $cartWithDetails[$id] = [
                        'name' => $product->name,
                        'quantity' => $quantity,
                        'price' => $product->price,
                        'photo' => $product->photo
                    ];
                    $subtotal += $product->price * $quantity;
                }
            }
        }

        return view('cart.show', ['cart' => $cartWithDetails, 'total' => $subtotal]);
    }


    public function add(Request $request, Product $product)
    {
        $quantity = max(1, (int) $request->input('quantity', 1));
        $cart = session()->get('cart', []);

        if (isset($cart[$product->id])) {
            $cart[$product->id] += $quantity;
        } else {
            $cart[$product->id] = $quantity;
        }

        session(['cart' => $cart]);
        return back()->with('success', 'Produto adicionado ao carrinho!');
    }

    /**
     * Atualiza a quantidade de um produto no carrinho.
     */
    public function update(Request $request, Product $product)
    {
        $quantity = max(0, (int) $request->input('quantity', 0));
        $cart = session()->get('cart', []);

        if ($quantity > 0) {
            $cart[$product->id] = $quantity;
        } else {
            unset($cart[$product->id]);
        }

        session(['cart' => $cart]);
        return back()->with('success', 'Carrinho atualizado!');
    }

    /**
     * Remove um produto completamente do carrinho.
     */
    public function remove(Product $product)
    {
        $cart = session()->get('cart', []);
        unset($cart[$product->id]);
        session(['cart' => $cart]);
        return back()->with('success', 'Produto removido do carrinho!');
    }

    /**
     * Limpa todo o carrinho de compras.
     */
    public function destroy()
    {
        session()->forget('cart');
        return redirect()->route('cart.show')->with('success', 'Carrinho esvaziado!');
    }

    /**
     * Mostra a página de confirmação do pedido.
     */
    public function confirm()
    {
        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('products.showcase');
        }

        $user = Auth::user();
        $subtotal = 0;
        $cartWithDetails = [];
        $productIds = array_keys($cart);
        $products = Product::find($productIds)->keyBy('id');

        foreach ($cart as $id => $quantity) {
            $product = $products->get($id);
            if ($product) {
                $subtotal += $product->price * $quantity;
                $cartWithDetails[$id] = [
                    'name' => $product->name,
                    'quantity' => $quantity,
                    'price' => $product->price
                ];
            }
        }

        $shippingCost = SettingsShippingCost::where('min_value_threshold', '<=', $subtotal)
            ->where(function ($query) use ($subtotal) {
                $query->where('max_value_threshold', '>', $subtotal)
                      ->orWhereNull('max_value_threshold');
            })
            ->first();

        $shippingPrice = $shippingCost ? $shippingCost->shipping_cost : 0;
        $total = $subtotal + $shippingPrice;
        $card = $user->card;

        if ($card) {
            $balance = $card->balance;
        } else {
           return redirect()->route('cart.show')->with('error', 'Não tem um cartão associado. Por favor, associe um cartão para continuar.');
        }
        $hasEnoughFunds = $balance >= $total;

        return view('cart.confirm', compact('cartWithDetails', 'subtotal', 'shippingPrice', 'total', 'user', 'hasEnoughFunds'));
    }

    /**
     * Processa a encomenda após a confirmação.
     */
    public function processConfirm(Request $request)
    {
        $request->validate([
            'delivery_address' => 'required|string|max:255',
            'nif' => 'nullable|string|digits:9',
        ]);

        $cart = session()->get('cart', []);
        $user = Auth::user();

        $productIds = array_keys($cart);
        $products = Product::find($productIds)->keyBy('id');
        $subtotal = 0;

        foreach ($cart as $id => $quantity) {
            $product = $products->get($id);
            if ($product) {
                $subtotal += $product->price * $quantity;
            }
        }

        $shippingCost = SettingsShippingCost::where('min_value_threshold', '<=', $subtotal)
            ->where(function ($query) use ($subtotal) {
                $query->where('max_value_threshold', '>', $subtotal)
                    ->orWhereNull('max_value_threshold');
            })
            ->first();

        $shippingPrice = $shippingCost ? $shippingCost->shipping_cost : 0;
        $total = $subtotal + $shippingPrice;

        if ($user->card->balance < $total) {
            return redirect()->route('cart.confirm')->with('error', 'Saldo insuficiente para completar a compra.');
        }

        try {
            DB::transaction(function () use ($user, $cart, $products, $subtotal, $shippingPrice, $total, $request) {
                $order = Order::create([
                    'member_id' => $user->id,
                    'status' => 'pending',
                    'date' => now(),
                    'total_items' => $subtotal,
                    'shipping_costs' => $shippingPrice,
                    'total' => $total,
                    'delivery_address' => $request->delivery_address,
                    'nif' => $request->nif,
                ]);

                foreach ($cart as $id => $quantity) {
                    $product = $products->get($id);
                    if ($product) {
                        ItemsOrder::create([
                            'order_id' => $order->id,
                            'product_id' => $id,
                            'quantity' => $quantity,
                            'unit_price' => $product->price,
                            'subtotal' => $product->price * $quantity,
                        ]);
                    }
                }

                $user->card->balance -= $total;
                $user->card->save();

                Operation::create([
                    'card_id' => $user->card->id,
                    'order_id' => $order->id,
                    'type' => 'debit',
                    'debit_type' => 'order',
                    'value' => $total,
                    'date' => now(),
                ]);

                session()->forget('cart');
            });
        } catch (\Exception $e) {
            return redirect()->route('cart.show')->with('error', 'Ocorreu um erro ao processar a sua encomenda. Tente novamente.');
        }

        return redirect()->route('dashboard')->with('success', 'Encomenda realizada com sucesso!');
    }
}
