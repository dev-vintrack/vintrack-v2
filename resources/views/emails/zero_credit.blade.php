<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family:Arial,Helvetica,sans-serif;color:#333;">
    <h2 style="color:#dc3545;">Tus créditos se han agotado</h2>
    <p>Hola {{ $wallet->user?->name ?? 'Cliente' }},</p>
    <p>El saldo del servicio <strong>{{ $service->name }}</strong> llegó a cero.</p>
    <p><strong>Proveedor:</strong> {{ $service->provider?->name ?? '—' }}</p>
    <p>Para continuar realizando consultas de este servicio, adquiere nuevos créditos o un paquete disponible.</p>
    <p><a href="{{ route('customer.credits') }}" style="display:inline-block;padding:10px 18px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:5px;">Consultar mis créditos</a></p>
    <p style="color:#777;">© {{ date('Y') }} VINTRACK</p>
</body>
</html>
