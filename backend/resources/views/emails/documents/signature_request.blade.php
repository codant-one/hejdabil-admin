<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud de firma</title>
</head>
<body style="font-family: Arial, sans-serif;">
    <div style="max-width: 600px; margin: auto; padding: 20px; border: 1px solid #eee;">
        <h2>Solicitud de Firma de Documento</h2>
        <p>{{ $text }}</p>
        <p>Por favor, haga clic en el siguiente botón para revisar y firmar su documento.</p>
        <p style="text-align: center; margin: 25px 0;">
            <a href="{{ $signingUrl }}" style="background-color: #007bff; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px;">Firmar Contrato</a>
        </p>
        <p>Si el botón no funciona, copie y pegue esta URL en su navegador: {{ $signingUrl }}</p>
        <p>Gracias.</p>
    </div>
</body>
</html>