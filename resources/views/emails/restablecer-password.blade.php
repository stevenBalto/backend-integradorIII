<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer contraseña</title>
</head>
<body style="margin:0;padding:0;background:#f7f5f2;font-family:'Segoe UI',Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f7f5f2;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0"
                    style="background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e5e7eb;max-width:480px;width:100%;">
                    <!-- Header -->
                    <tr>
                        <td style="background:#1e1e1e;padding:24px;text-align:center;">
                            <span style="color:#ffffff;font-size:22px;font-weight:700;">Rooster</span>
                            <span style="color:#e13642;font-size:22px;font-weight:700;"> &#9733;</span>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding:32px 28px;color:#1e1e1e;text-align:center;">
                            <h1 style="font-size:20px;margin:0 0 16px;">Restablecer tu contraseña</h1>
                            <p style="font-size:15px;line-height:1.55;color:#374151;margin:0 0 24px;">
                                Hola{{ $nombre ? ' ' . $nombre : '' }}, recibimos una solicitud para restablecer la
                                contraseña de tu cuenta. Hacé clic en el botón para elegir una nueva:
                            </p>
                            <!-- Botón centrado -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $urlReset }}" target="_blank"
                                            style="display:inline-block;padding:14px 32px;background:#e13642;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;border-radius:10px;">
                                            Restablecer contraseña
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="font-size:13px;line-height:1.5;color:#6b7280;margin:0 0 8px;">
                                Si el botón no funciona, copiá y pegá este enlace en tu navegador:
                            </p>
                            <p style="font-size:12px;word-break:break-all;color:#e13642;margin:0 0 20px;">
                                <a href="{{ $urlReset }}" target="_blank" style="color:#e13642;text-decoration:none;">{{ $urlReset }}</a>
                            </p>
                            <p style="font-size:13px;line-height:1.5;color:#6b7280;margin:0;">
                                Este enlace vence en <strong>60 minutos</strong>. Si vos no solicitaste este cambio,
                                podés ignorar este correo — tu contraseña seguirá igual.
                            </p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding:18px 28px;background:#faf7f2;text-align:center;color:#9ca3af;font-size:12px;">
                            Rooster Pizza &amp; Grill — Este es un correo automático, no respondas a este mensaje.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
