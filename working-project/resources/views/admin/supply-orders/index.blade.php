@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Histórico de Encomendas de Fornecimento</h1>
        <a href="{{ route('admin.supply-orders.create') }}" class="btn btn-primary">Criar Nova Encomenda</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Produto</th>
                        <th class="text-center">Quantidade</th>
                        <th>Registado Por</th>
                        <th>Data</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($supplyOrders as $order)
                        <tr>
                            <td>#{{ $order->id }}</td>
                            <td>{{ $order->product->name ?? 'Produto não encontrado' }}</td>
                            <td class="text-center">{{ $order->quantity }}</td>
                            <td>{{ $order->registered_by->name ?? 'Utilizador não encontrado' }}</td>
                            <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-center">
                                @if ($order->status == 'completed')
                                    <span class="badge badge-success">Concluída</span>
                                @else
                                    <span class="badge badge-warning">Solicitada</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($order->status == 'requested')
                                    <form action="{{ route('admin.supply-orders.update', $order) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-success btn-sm" title="Marcar como Concluída">Concluir</button>
                                    </form>
                                    <form action="{{ route('admin.supply-orders.destroy', $order) }}" method="POST" class="d-inline" onsubmit="return confirm('Tem a certeza que quer apagar?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" title="Apagar">Apagar</button>
                                    </form>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">Nenhuma encomenda de fornecimento encontrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $supplyOrders->links() }}
    </div>
</div>
@endsection