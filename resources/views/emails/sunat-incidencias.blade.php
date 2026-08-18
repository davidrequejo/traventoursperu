<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Incidencias en el envio a SUNAT</title>
</head>
<body style="margin:0;padding:0;background:#f3f5f7;color:#263238;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f5f7;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="900" cellspacing="0" cellpadding="0" style="width:900px;max-width:94%;background:#ffffff;border:1px solid #d9e1e7;border-radius:6px;overflow:hidden;">
                    <tr>
                        <td style="background:#456b4e;color:#ffffff;padding:22px 22px 18px;">
                            <div style="font-size:22px;line-height:1.25;font-weight:700;margin:0 0 8px;">Incidencias en el envio a SUNAT</div>
                            <div style="font-size:14px;line-height:1.4;font-weight:700;">
                                Ambiente: {{ $ambiente }} | Origen: {{ $origen }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px;">
                            <p style="margin:0 0 16px;font-size:14px;line-height:1.55;">
                                Se encontraron incidencias durante el procesamiento de comprobantes electronicos.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin:0 0 20px;border:1px solid #d9e1e7;">
                                <tr>
                                    <td style="border:1px solid #d9e1e7;padding:9px 10px;font-size:14px;font-weight:700;width:25%;">Fecha</td>
                                    <td style="border:1px solid #d9e1e7;padding:9px 10px;font-size:14px;width:40%;">{{ $resumen['fecha'] ?? now()->format('d/m/Y H:i:s') }}</td>
                                    <td style="border:1px solid #d9e1e7;padding:9px 10px;font-size:14px;font-weight:700;width:20%;">Procesados</td>
                                    <td style="border:1px solid #d9e1e7;padding:9px 10px;font-size:14px;width:15%;">{{ $resumen['procesados'] ?? 0 }}</td>
                                </tr>
                                <tr>
                                    <td style="border:1px solid #d9e1e7;padding:9px 10px;font-size:14px;font-weight:700;">Aceptados</td>
                                    <td style="border:1px solid #d9e1e7;padding:9px 10px;font-size:14px;">{{ $resumen['aceptados'] ?? 0 }}</td>
                                    <td style="border:1px solid #d9e1e7;padding:9px 10px;font-size:14px;font-weight:700;">Incidencias</td>
                                    <td style="border:1px solid #d9e1e7;padding:9px 10px;font-size:14px;">{{ count($incidencias) }}</td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;border:1px solid #d9e1e7;">
                                <thead>
                                    <tr>
                                        <th align="left" style="background:#eef1f4;border:1px solid #d9e1e7;padding:9px 8px;font-size:14px;">ID</th>
                                        <th align="left" style="background:#eef1f4;border:1px solid #d9e1e7;padding:9px 8px;font-size:14px;">Comprobante</th>
                                        <th align="left" style="background:#eef1f4;border:1px solid #d9e1e7;padding:9px 8px;font-size:14px;">Resultado</th>
                                        <th align="left" style="background:#eef1f4;border:1px solid #d9e1e7;padding:9px 8px;font-size:14px;">Mensaje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($incidencias as $incidencia)
                                        <tr>
                                            <td style="border:1px solid #d9e1e7;padding:9px 8px;font-size:14px;vertical-align:top;">{{ $incidencia['id'] ?? '-' }}</td>
                                            <td style="border:1px solid #d9e1e7;padding:9px 8px;font-size:14px;vertical-align:top;">{{ $incidencia['comprobante'] ?? '-' }}</td>
                                            <td style="border:1px solid #d9e1e7;padding:9px 8px;font-size:14px;vertical-align:top;font-weight:700;">{{ $incidencia['resultado'] ?? '-' }}</td>
                                            <td style="border:1px solid #d9e1e7;padding:9px 8px;font-size:14px;vertical-align:top;line-height:1.45;">{{ $incidencia['mensaje'] ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <p style="margin:20px 0 0;color:#56738a;font-size:13px;line-height:1.5;">
                                Los errores tecnicos quedan registrados con estado ERROR. Los rechazos oficiales requieren revision antes de un nuevo envio.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
