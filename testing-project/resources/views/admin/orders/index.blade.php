<x-layouts.app>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Gestão de Encomendas</h1>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form action="{{ route('admin.orders.index') }}" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="status" class="form-label">Filtrar por Estado</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">Todos os Estados</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pendente</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Concluído</option>
                            <option value="canceled" {{ request('status') == 'canceled' ? 'selected' : '' }}>Cancelado</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    </div>
                </form>
            </div>
        </div>

        @if($orders->isEmpty())
            <div class="alert alert-info">
                Não foram encontradas encomendas com os filtros selecionados.
            </div>
        @else
            <div class="card">
                <div class="card-body">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th># Encomenda</th>
                                <th>Cliente</th>
                                <th>Data</th>
                                <th class="text-center">Estado</th>
                                <th class="text-end">Total</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $order)
                                <tr>
                                    <td>#{{ $order->id }}</td>
                                    <td>{{ $order->member->name }}</td>
                                    <td>{{ $order->date->format('d/m/Y') }}</td>
                                    <td class="text-center">
                                        @php
                                            $statusClass = [
                                                'pending' => 'warning',
                                                'completed' => 'success',
                                                'canceled' => 'danger'
                                            ][$order->status] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $statusClass }}">{{ ucfirst($order->status) }}</span>
                                    </td>
                                    <td class="text-end fw-bold">{{ number_format($order->total, 2, ',', '.') }} €</td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-primary btn-sm">Ver Detalhes</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>