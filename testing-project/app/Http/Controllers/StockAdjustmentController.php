<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockAdjustmentFormRequest;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Models\StockAdjustment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class StockAdjustmentController extends Controller
{

    public function index(Request $request) : View
    {
        $stockAdjustments = StockAdjustment::paginate(20);

        $filterByQuantityChanged = $request->input('quantity_changed');
        $filterByUser = $request->input('registered_by_user_id');
        $filterByProductId = $request->input('product_id');

        if( $filterByQuantityChanged || $filterByUser || $filterByProductId ) {
            $stockAdjustments = stockAdjustment::query();

            if ($filterByQuantityChanged) {
                $stockAdjustments->where('quantity_changed', '>=', $filterByQuantityChanged);
            }

            if ($filterByUser) {
                $stockAdjustments->where('registered_by_user_id', $filterByUser);
            }

            if ($filterByProductId) {
                $stockAdjustments->where('product_id', $filterByProductId);
            }

            $stockAdjustments = $stockAdjustments->paginate(20);
        }

        return view('stockAdjustments.index', compact('stockAdjustments', 'filterByQuantityChanged', 'filterByUser', 'filterByProductId'));
    }


    public function create() : View
    {
        $stockAdjustment = new StockAdjustment();

        return view('stockAdjustments.create', compact('stockAdjustment'));
    }


    public function store(StockAdjustmentFormRequest $request) : RedirectResponse
    {
        $newStockAdjustment = StockAdjustment::create($request->validated());

        $url = route('admin.stock-adjustments.show', ['stock_adjustment' => $newStockAdjustment]);
        $htmlMessage = "Stock Adjustment <a href='$url'><strong>{$newStockAdjustment->id}</strong>
                    - </a> New Stock Adjustment has been created successfully!";
        return redirect()->route('stockAdjustments.index')
            ->with('alert-type', 'success')
            ->with('alert-msg', $htmlMessage);
    }

    public function show(StockAdjustment $stockAdjustment)
    {
        // Return the view for showing a specific stock adjustment
        return view('stockAdjustments.show', compact('stockAdjustment'));
    }

    public function edit(StockAdjustment $stockAdjustment)
    {
        // Return the view for editing a specific stock adjustment
        return view('stockAdjustments.edit', compact('stockAdjustment'));
    }

    public function update(StockAdjustmentFormRequest $request, StockAdjustment $stockAdjustment)
    {
        // Validate and update the stock adjustment
        $stockAdjustment->update($request->validated());

        $url = route('admin.stock-adjustments.show', ['stock_adjustment' => $stockAdjustment]); // <-- FIXED
        $htmlMessage = "Stock Adjustment <a href='$url'><strong>{$stockAdjustment->id}</strong>
                    - </a> has been updated successfully!";
        return redirect()->route('stockAdjustments.index')
            ->with('alert-type', 'success')
            ->with('alert-msg', $htmlMessage);
    }

    public function destroy(StockAdjustment $stockAdjustment)
    {
        // Delete the stock adjustment
        $stockAdjustment->delete();

        // Redirect back to the stock adjustments index with a success message
        return redirect()->route('stockAdjustments.index')
            ->with('alert-type', 'success')
            ->with('alert-msg', 'Stock Adjustment deleted successfully!');
    }
}
