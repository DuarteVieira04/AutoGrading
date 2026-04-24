@extends('layouts.app')

@section('content')
<div class="container py-5">
    <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary mb-3">‹ Voltar para Encomendas</a>

    @if (session('success'))
        <div class="alert alert-success" role="alert">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3>Detalhes da Encomenda #{{ $order->id }}</h3>
            @php
                $statusClass = [
                    'pending' => 'warning',
                    'completed' => 'success',
                    'canceled' => 'danger'
                ][$order->status] ?? 'secondary';
            @endphp
            <strong>Estado:</strong> <span class="badge bg-{{ $statusClass }}">{{ ucfirst($order->status) }}</span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h4>Informação do Cliente</h4>
                    <p><strong>Nome:</strong> {{ $order->member->name }}</p>
                    <p><strong>Email:</strong> {{ $order->member->email }}</p>
                    <p><strong>NIF:</strong> {{ $order->nif ?? 'N/A' }}</p>
                </div>
                <div class="col-md-6">
                    <h4>Informação de Entrega</h4>
                    <p><strong>Morada de Entrega:</strong> {{ $order->delivery_address }}</p>
                    @if($order->status == 'completed' && $order->pdf_receipt)
                        <p><strong>Recibo:</strong> <a href="{{ asset('storage/' . $order->pdf_receipt) }}" target="_blank">Ver PDF</a></p>
                    @endif
                     @if($order->status == 'canceled')
                        <p><strong>Motivo do Cancelamento:</strong> {{ $order->cancel_reason }}</p>
                    @endif
                </div>
            </div>
            <hr>

            <h4>Itens da Encomenda</h4>
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Produto</th>
                        <th class="text-center">Quantidade</th>
                        <th class="text-end">Preço Unitário</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                    <tr>
                        <td>{{ $item->product->name }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-end">{{ number_format($item->unit_price, 2, ',', '.') }} €</td>
                        <td class="text-end">{{ number_format($item->subtotal, 2, ',', '.') }} €</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Subtotal Itens:</strong></td>
                        <td class="text-end">{{ number_format($order->total_items, 2, ',', '.') }} €</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Custos de Envio:</strong></td>
                        <td class="text-end">{{ number_format($order->shipping_costs, 2, ',', '.') }} €</td>
                    </tr>
                    <tr class="table-group-divider">
                        <td colspan="3" class="text-end"><h4>Total da Encomenda:</h4></td>
                        <td class="text-end"><h4>{{ number_format($order->total, 2, ',', '.') }} €</h4></td>
                    </tr>
                </tfoot>
            </table>

            @if ($order->status == 'pending')
            <hr>
            <h4>Ações</h4>
            <div class="d-flex flex-wrap gap-2">
                <form action="{{ route('admin.orders.complete', $order) }}" method="POST" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success">Marcar como Concluída</button>
                </form>

                @can('cancel', $order)
                <form action="{{ route('admin.orders.cancel', $order) }}" method="POST" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <div class="input-group">
                        <input type="text" name="cancel_reason" class="form-control" placeholder="Motivo do cancelamento" required>
                        <button type="submit" class="btn btn-danger">Cancelar Encomenda</button>
                    </div>
                </form>
                @endcan
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
