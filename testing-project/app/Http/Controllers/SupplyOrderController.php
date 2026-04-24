<?php

namespace App\Http\Controllers;

use id;
use Illuminate\View\View;
use App\Models\SupplyOrder;
use Illuminate\Http\Request;
use App\Http\Requests\ProductFormRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\SupplyOrderFormRequest;

class SupplyOrderController extends Controller
{
    public function index(Request $request) : View
    {
        $supplyOrders = SupplyOrder::paginate(20);

        $filterByQuantity = $request->input('quantity');
        $filterByStatus = $request->input('status');
        $filterByUser = $request->input('registered_by_user_id');
        $filterByProductId = $request->input('product_id');

        if( $filterByQuantity || $filterByStatus || $filterByUser || $filterByProductId ) {
            $supplyOrders = SupplyOrder::query();

            if ($filterByQuantity) {
                $supplyOrders->where('quantity', '>=', $filterByQuantity);
            }

            if ($filterByStatus) {
                $supplyOrders->where('status', $filterByStatus);
            }

            if ($filterByUser) {
                $supplyOrders->where('registered_by_user_id', $filterByUser);
            }

            if ($filterByProductId) {
                $supplyOrders->where('product_id', $filterByProductId);
            }

            $supplyOrders = $supplyOrders->paginate(20);
        }

        return view('supplyOrders.index', compact('supplyOrders', 'filterByStatus', 'filterByUser', 'filterByQuantity', 'filterByProductId'));
    }

    public function create(Request $request) : View
    {
        if ($request->has('product_id')) {
            $productId = $request->input('product_id');
            $request->session()->put('product_id', $productId);
        }

        $registed_by_user_id = Auth::user()->id ?? null;

        return view('supplyOrders.create', ['productId' => $productId ?? null, 'registed_by_user_id' => $registed_by_user_id]);
    }

    public function store(SupplyOrderFormRequest $request)
    {
        $newSupplyOrder = SupplyOrder::create($request->validated());

        $url = route('supplyOrders.index', ['supplyOrders' => $newSupplyOrder]);
        $htmlMessage = "Supply Order <a href='$url'><strong>{$newSupplyOrder->id}</strong>
                    - </a> New Supply Order has been created successfully!";
        return redirect()->route('supplyOrders.index')
            ->with('alert-type', 'success')
            ->with('alert-msg', $htmlMessage);
    }

    public function show(SupplyOrder $supplyOrder)
    {
        return view('supplyOrders.show', compact('supplyOrder'));
    }

    public function edit(SupplyOrder $supplyOrder)
    {
        return view('supplyOrders.edit', compact('supplyOrder'));
    }


    public function update(SupplyOrderFormRequest $request, SupplyOrder $supplyOrder)
    {
        $supplyOrder->update($request->validated());

        $url = route('supplyOrders.show', ['supplyOrder' => $supplyOrder]);
        $htmlMessage = "Supply Order <a href='$url'><strong>{$supplyOrder->id}</strong>
                    - </a> Supply Order has been updated successfully!";
        return redirect()->route('supplyOrders.index')
            ->with('alert-type', 'success')
            ->with('alert-msg', $htmlMessage);
    }

    public function destroy(SupplyOrder $supplyOrder)
    {
        $supplyOrder->delete();

        return redirect()->route('supplyOrders.index')
            ->with('alert-type', 'success')
            ->with('alert-msg', 'Supply Order deleted successfully!');
    }
}
