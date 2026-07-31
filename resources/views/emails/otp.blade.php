<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
</head>
<body style="background:#f4f4f4; font-family:Arial, sans-serif; padding:20px; margin:0;">
    <table width="100%" style="max-width:420px; margin:0 auto; background:#ffffff; border-radius:10px; overflow:hidden; box-shadow:0 4px 10px rgba(0,0,0,0.1);">
        <tr>
            <td style="background:#0d6efd; color:white; text-align:center; padding:20px;">
                <h2 style="margin:0;">VINTRACK</h2>
                <p style="margin:5px 0 0;">{{ $title }}</p>
            </td>
        </tr>
        <tr>
            <td style="padding:30px; text-align:center;">
                <p style="font-size:16px; color:#333; margin-bottom:20px;">Tu código de verificación es:</p>
                <div style="font-size:36px; font-weight:bold; letter-spacing:6px; color:#0d6efd; margin:20px 0;">{{ $otp }}</div>
                <p style="color:#666; font-size:14px;">Este código expira en 5 minutos.</p>
            </td>
        </tr>
        <tr>
            <td style="background:#f1f1f1; text-align:center; font-size:12px; color:#666; padding:15px;">
                &copy; {{ date('Y') }} VINTRACK
            </td>
        </tr>
    </table>
</body>
</html>
