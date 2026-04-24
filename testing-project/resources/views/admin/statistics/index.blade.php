<x-layouts.app :title="__('Estatísticas Gerais')">
    <div class="container mx-auto p-4">
        <h1 class="mb-4 text-2xl font-bold">Estatísticas Gerais do Clube</h1>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="col-md-6 mb-4">
                <div class="card rounded-lg border bg-white p-4 shadow-sm dark:border-neutral-700 dark:bg-neutral-800">
                    <div class="card-header font-semibold">Resumo Financeiro</div>
                    <div class="card-body">
                        <h3 class="text-xl">Receita Total: {{ number_format($totalRevenue, 2, ',', '.') }} €</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card rounded-lg border bg-white p-4 shadow-sm dark:border-neutral-700 dark:bg-neutral-800">
                    <div class="card-header font-semibold">Membros</div>
                    <div class="card-body">
                        <h3 class="text-xl">Total de Membros Ativos: {{ $totalMembers }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="col-md-6">
                <div class="card rounded-lg border bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-800">
                    <div class="card-header border-b p-4 font-semibold dark:border-neutral-700">Top 5 Produtos Mais Vendidos</div>
                    <ul class="list-group list-group-flush">
                        @forelse($topSellingProducts as $product)
                            <li class="list-group-item flex items-center justify-between p-4">
                                {{ $product->product_name }}
                                <span class="badge rounded-full bg-blue-500 px-2 py-1 text-xs text-white">{{ $product->total_quantity }} unidades</span>
                            </li>
                        @empty
                            <li class="list-group-item p-4">Sem dados de vendas.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
            <div class="col-md-6">
                 <div class="card rounded-lg border bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-800">
                    <div class="card-header border-b p-4 font-semibold dark:border-neutral-700">Vendas por Categoria</div>
                     <ul class="list-group list-group-flush">
                        @forelse($salesByCategory as $category)
                            <li class="list-group-item flex items-center justify-between p-4">
                                {{ $category->category_name }}
                                <span class="badge rounded-full bg-green-500 px-2 py-1 text-xs text-white">{{ number_format($category->total_sales, 2, ',', '.') }} €</span>
                            </li>
                        @empty
                            <li class="list-group-item p-4">Sem dados de vendas.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
