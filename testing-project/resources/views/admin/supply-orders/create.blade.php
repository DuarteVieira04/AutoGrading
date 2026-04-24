@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Criar Encomenda de Fornecimento</h1>
    <p>Lista de produtos que necessitam de reabastecimento de stock.</p>

    @if($products->isEmpty())
        <div class="alert alert-info">
            Não há produtos a precisar de reabastecimento de stock de momento.
        </div>
    @else
        <form action="{{ route('admin.supply-orders.store') }}" method="POST">
            @csrf
            <div class="card">
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th class="text-center">Stock Atual</th>
                                <th class="text-center">Sugestão Automática</th>
                                <th style="width: 150px;">Quantidade a Encomendar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $product)
                                <tr>
                                    <td>{{ $product->name }}</td>
                                    <td class="text-center">{{ $product->stock }}</td>
                                    <td class="text-center">{{ $product->stock_upper_limit - $product->stock }}</td>
                                    <td>
                                        <input type="number"
                                               name="products[{{ $product->id }}][quantity]"
                                               class="form-control"
                                               min="1"
                                               placeholder="0">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Criar Encomenda(s)</button>
                <a href="{{ route('admin.inventory.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    @endif
</div>
@endsection
