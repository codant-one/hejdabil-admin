<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Swish-betalningskvitto - PDF</title>
    <style>
        html, body {
            height: 100%;
        }

        body {
            font-family: 'gelion', 'dm sans', sans-serif !important;
            background-color: #FFFFFF;
            padding: 0;
            margin: 0;
            letter-spacing: 0 !important;
            word-spacing: normal !important;
            line-height: 0.6;
            position: relative;
        }

        .header {
            text-align: center;
        }

        .header h1 {
            font-size: 24px;
            color: #454545;
        }

        .image-container {
            text-align: center;
        }

        .image-container img {
            max-width: 100%;
            max-height: 500px;
            border-radius: 16px;
            border: 1px solid #E7E7E7;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Swish-betalningskvitto</h1>
    </div>

    @if($imageBase64)
    <div class="image-container">
        <img src="{{ $imageBase64 }}" alt="Kvitto">
    </div>
    @endif
</body>
</html>
