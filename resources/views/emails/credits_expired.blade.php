<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créditos vencidos</title>
</head>
<body>
    <h2>Tus créditos VINTRACK han vencido</h2>

    <p>Hola {{ $wallet->user->name ?? 'Cliente' }},</p>

    <p>Te informamos que los créditos del servicio <strong>{{ $service->name }}</strong> han vencido el día <strong>{{ $wallet->validity_end->format('d/m/Y') }}</strong>.</p>

    <p>
        <strong>Créditos no consumidos:</strong> {{ number_format($amount, 2) }}<br>
        <strong>Servicio:</strong> {{ $service->name }}
    </p>

    <p>Estos créditos ya no están disponibles en tu cuenta. Si deseas seguir consultando, puedes realizar una nueva compra de créditos o paquetes desde tu panel.</p>

    <p>Saludos,<br>Equipo VINTRACK</p>
</body>
</html>
