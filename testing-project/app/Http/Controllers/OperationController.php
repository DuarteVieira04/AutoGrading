<?php

namespace App\Http\Controllers;

use App\Http\Requests\OperationFormRequest;
use App\Models\Operation;
use Illuminate\View\View;
use Illuminate\Http\Request;

class OperationController extends Controller
{

    public function index(Request $request) : View
    {
        $cardId = $request->input('card_id');
        $type = $request->input('type');
        $orderId = $request->input('order_id');

        $query = Operation::query();

        if ($cardId) {
            $query->where('card_id', $cardId);
        }
        if ($type) {
            $query->where('type', $type);
        }
        if ($orderId) {
            $query->where('order_id', $orderId);
        }

        $operations = $query->paginate(20);

        return view('operations.index', [
            'operations' => $operations,
            'cardId' => $cardId,
            'type' => $type,
            'orderId' => $orderId,
        ]);
    }

    public function create()
    {
        return view('operations.create');
    }

    public function store(OperationFormRequest $request)
    {
        $newOperation = Operation::create($request->validated());
        $url = route('operations.show', ['operation' => $newOperation]);
        $htmlMessage = "Operation <a href='$url'><strong>{$newOperation->name}</strong>";

        return redirect()->route('operations.index')
            ->with('alert-type', 'success')
            ->with('alert-msg', "Operation <strong>{$newOperation->name}</strong> created successfully!");
    }


    public function show(Operation $operation)
    {
        return view('operations.show', compact('operation'));
    }

    public function edit(Operation $operation)
    {
        return view('operations.edit', compact('operation'));
    }

    public function update(OperationFormRequest $request, Operation $operation)
    {
        $operation->update($request->validated());

        return redirect()->route('operations.index')
            ->with('alert-type', 'success')
            ->with('alert-msg', 'Operation updated successfully!');
    }

    public function destroy(Operation $operation)
    {
        $operation->delete();

        return redirect()->route('operations.index')
            ->with('alert-type', 'success')
            ->with('alert-msg', 'Operation deleted successfully!');
    }
}
