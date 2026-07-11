<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family:Arial,Helvetica,sans-serif; color:#333;">
    <h2>Alerta de créditos bajos</h2>
    <p>Hola, {{ $userEmail }}</p>
    <p>Tu saldo de créditos disponible es de <strong>{{ number_format((float) $balance, 2) }}</strong>.</p>
    <p>Te sugerimos adquirir más créditos con el administrador del sitio para continuar realizando consultas.</p>
    <p style="color:#777;">© {{ date('Y') }} VINTRACK</p>
</body>
</html>
