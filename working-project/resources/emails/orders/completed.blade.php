<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encomenda Concluída</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .header {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            Encomenda Concluída!
        </div>
        <p>Olá, {{ $order->member->name }}!</p>

        <p>A sua encomenda <strong>#{{ $order->id }}</strong> foi processada e enviada com sucesso.</p>

        <p>Em anexo, encontrará o recibo da sua compra em formato PDF para sua referência.</p>

        <p>Agradecemos a sua preferência e esperamos vê-lo em breve!</p>

        <p>Com os melhores cumprimentos,</p>
        <p><strong>A equipa do Grocery Club</strong></p>

        <div class="footer">
            <p>Este é um email automático, por favor não responda.</p>
        </div>
    </div>
</body>
</html>