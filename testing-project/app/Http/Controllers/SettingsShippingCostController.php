<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Models\SettingsShippingCost;
use App\Http\Requests\SettingsShippingCostFormRequest;

class SettingsShippingCostController extends Controller
{
    public function index(Request $request) : View
    {
        $settingsShippingCosts = SettingsShippingCost::paginate(20);

        $filterByMinValue = $request->input('min_value_threshold');
        $filterByMaxValue = $request->input('max_value_threshold');
        $filterByShippingCost = $request->input('shipping_cost');

        if( $filterByMinValue || $filterByMaxValue || $filterByShippingCost ) {
            $settingsShippingCosts = SettingsShippingCost::query();

            if ($filterByMinValue) {
                $settingsShippingCosts->where('min_value_threshold', '<=', $filterByMinValue);
            }

            if ($filterByMaxValue) {
                $settingsShippingCosts->where('max_value_threshold', '>=', $filterByMaxValue);
            }

            if ($filterByShippingCost) {
                $settingsShippingCosts->where('shipping_cost', $filterByShippingCost);
            }

            $settingsShippingCosts = $settingsShippingCosts->paginate(20);
        }

        return view('settingsShippingCosts.index', compact('settingsShippingCosts', 'filterByMinValue', 'filterByMaxValue', 'filterByShippingCost'));
    }

    public function show($id)
    {
        $settingsShippingCost = \App\Models\SettingsShippingCost::findOrFail($id);
        return view('settingsShippingCosts.show', compact('settingsShippingCost'));
    }

    public function edit($id)
    {
        $settingsShippingCost = SettingsShippingCost::findOrFail($id);
        return view('settingsShippingCosts.edit', compact('settingsShippingCost'));
    }

    public function update(SettingsShippingCostFormRequest $request, SettingsShippingCost $settingsShippingCost)
    {
        $settingsShippingCost->update($request->validated());
        $url = route('settingsShippingCosts.index');
        $htmlMessage = "Shipping cost settings have been updated successfully!";
        $htmlMessage .= " <a href='$url'>View Settings</a>";

        return redirect()
            ->route('settingsShippingCosts.index')
            ->with('success', 'Shipping cost updated successfully.');
    }

    public function create()
    {
        return view('settingsShippingCosts.create');
    }

    public function destroy($id)
    {
        $settingsShippingCost = SettingsShippingCost::findOrFail($id);
        $settingsShippingCost->delete();
        return redirect()->route('settingsShippingCosts.index')
            ->with('success', 'Shipping cost settings deleted successfully.');
    }

    public function store(SettingsShippingCostFormRequest $request)
    {
        SettingsShippingCost::create($request->validated());
        return redirect()
            ->route('settingsShippingCosts.index')
            ->with('success', 'Shipping cost settings created successfully.');
    }
}

