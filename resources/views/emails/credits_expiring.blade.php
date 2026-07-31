<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family:Arial,Helvetica,sans-serif;color:#333;">
    <h2 style="color:#fd7e14;">Tus créditos están próximos a vencer</h2>
    <p>Hola {{ $wallet->user?->name ?? 'Cliente' }},</p>
    <p>Los créditos del servicio <strong>{{ $service->name }}</strong> vencen en <strong>{{ $daysRemaining }} {{ $daysRemaining === 1 ? 'día' : 'días' }}</strong>.</p>
    <p>
        <strong>Saldo disponible:</strong> {{ number_format((float) $wallet->balance, 2) }}<br>
        <strong>Fecha de vencimiento:</strong> {{ $wallet->validity_end?->format('d/m/Y H:i') }}<br>
        <strong>Proveedor:</strong> {{ $service->provider?->name ?? '—' }}
    </p>
    <p>Utiliza tus créditos antes de la fecha indicada. Los créditos no consumidos serán retirados al vencer.</p>
    <p><a href="{{ route('customer.credits') }}" style="display:inline-block;padding:10px 18px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:5px;">Consultar mis créditos</a></p>
    <p style="color:#777;">© {{ date('Y') }} VINTRACK</p>
</body>
</html>
