<div class="border rounded-lg p-4 bg-zinc-900 text-white shadow flex flex-col justify-between h-full">
    <div>
        <h3 class="font-bold text-lg mb-2">Order #{{ $order->id }}</h3>
        <p><strong>Status:</strong> {{ $order->status }}</p>
        <p><strong>Date:</strong> {{ $order->date }}</p>
        <p><strong>Total Items:</strong> {{ $order->total_items }}</p>
        <p><strong>Total:</strong> €{{ number_format($order->total, 2) }}</p>
    </div>
    <div class="mt-4">
        <a href="{{ route('orders.show', $order) }}" class="text-blue-400 hover:underline">View Details</a>
        @if($order->pdf_receipt)
            <a href="{{ $order->pdf_receipt }}" class="ml-4 text-green-400 hover:underline" target="_blank">PDF Receipt</a>
        @endif
    </div>
</div>
