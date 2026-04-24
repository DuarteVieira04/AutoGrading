<x-layouts.main-content title="List of your orders">
    <form method="GET" class="mb-6 flex justify-end">
        <label for="status" class="mr-2 text-zinc-700 dark:text-zinc-200">Filter by status:</label>
        <select name="status" id="status" onchange="this.form.submit()" class="rounded border-zinc-300 dark:bg-zinc-800 dark:text-white">
            <option value="">All</option>
            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
            <option value="canceled" {{ request('status') == 'canceled' ? 'selected' : '' }}>Canceled</option>
        </select>
    </form>
    @if($orders->count() === 0)
        <div class="text-center text-zinc-500 py-12">
            You have no orders yet.
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-8 gap-6">
            @each('orders.partials.cards', $orders, 'order')
        </div>
        <div class="mt-6">
            {{ $orders->appends(request()->query())->links() }}
        </div>
    @endif
</x-layouts.main-content>
