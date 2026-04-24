<!DOCTYPE html>
<html>
<head>
    <title>Recibo da Encomenda</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        .container { padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Recibo da Encomenda #{{ $order->id }}</h1>
            <p>Data: {{ \Carbon\Carbon::parse($order->date)->format('d/m/Y') }}</p>
        </div>
        <p><strong>Cliente:</strong> {{ $order->member->name }}</p>
        <hr>
        <h3>Itens:</h3>
        <table width="100%" border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Qtd</th>
                    <th>Preço Unit.</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items_orders as $item)
                <tr>
                    <td>{{ $item->product->name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->unit_price, 2) }} €</td>
                    <td>{{ number_format($item->subtotal, 2) }} €</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <h3 style="text-align: right; margin-top: 20px;">Total: {{ number_format($order->total, 2) }} €</h3>
        <div class="footer">
            <p>Obrigado pela sua compra!</p>
        </div>
    </div>
</body>
</html>