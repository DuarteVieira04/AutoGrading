<x-layouts.app :title="__('Minhas Estatísticas')">
    <div class="container mx-auto p-4">
        <h1 class="mb-4 text-2xl font-bold">As Minhas Estatísticas</h1>

        <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="card rounded-lg border bg-white p-4 text-center shadow-sm dark:border-neutral-700 dark:bg-neutral-800">
                <div class="card-header font-semibold">Total Gasto</div>
                <div class="card-body">
                    <h3 class="card-title text-xl">{{ number_format($totalSpent, 2, ',', '.') }} €</h3>
                </div>
            </div>
            <div class="card rounded-lg border bg-white p-4 text-center shadow-sm dark:border-neutral-700 dark:bg-neutral-800">
                <div class="card-header font-semibold">Total de Encomendas</div>
                <div class="card-body">
                    <h3 class="card-title text-xl">{{ $orderCount }}</h3>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="card rounded-lg border bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-800">
                <div class="card-header border-b p-4 font-semibold dark:border-neutral-700">Meus Gastos por Categoria</div>
                <ul class="list-group list-group-flush">
                    @forelse($spendingByCategory as $category)
                        <li class="list-group-item flex items-center justify-between p-4">
                            {{ $category->category_name }}
                            <span>{{ number_format($category->total_spent, 2, ',', '.') }} €</span>
                        </li>
                    @empty
                        <li class="list-group-item p-4">Ainda não fez compras.</li>
                    @endforelse
                </ul>
            </div>
            <div class="card rounded-lg border bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-800">
                <div class="card-header border-b p-4 font-semibold dark:border-neutral-700">Últimas Operações do Cartão</div>
                <table class="w-full text-left">
                    <tbody>
                        @forelse($operations as $op)
                            <tr class="border-b dark:border-neutral-700">
                                <td class="p-4">{{ \Carbon\Carbon::parse($op->date)->format('d/m/Y') }}</td>
                                <td class="p-4">
                                    @if($op->type == 'credit')
                                        Crédito
                                    @else
                                        Débito ({{ $op->debit_type == 'order' ? 'Encomenda' : 'Inscrição' }})
                                    @endif
                                </td>
                                <td class="p-4 text-right @if($op->type == 'credit') text-green-500 @else text-red-500 @endif">
                                    {{ ($op->type == 'credit' ? '+' : '-') . number_format($op->value, 2, ',', '.') }} €
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="p-4">Sem operações registadas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.app>
