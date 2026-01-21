<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Swish-betalningskvitto</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #57F287;
            font-size: 28px;
            margin: 0;
        }
        .details {
            background-color: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .detail-row {
            margin-bottom: 10px;
        }
        .detail-label {
            color: #666;
            font-size: 12px;
            margin: 0;
        }
        .detail-value {
            color: #2E0684;
            font-size: 14px;
            font-weight: bold;
            margin: 0;
        }
        .amount-value {
            color: #57F287;
            font-size: 18px;
            font-weight: bold;
            margin: 0;
        }
        .image-container {
            text-align: center;
            margin-top: 20px;
        }
        .image-container img {
            max-width: 100%;
            max-height: 500px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Swish-betalningskvitto</h1>
    </div>

    <div class="details">
        <div class="detail-row">
            <p class="detail-label">Referens:</p>
            <p class="detail-value">{{ $payout->reference ?? '-' }}</p>
        </div>
        <div class="detail-row">
            <p class="detail-label">Meddelande:</p>
            <p class="detail-value">{{ $payout->message ?? '-' }}</p>
        </div>
        <div class="detail-row">
            <p class="detail-label">Belopp:</p>
            <p class="amount-value">{{ number_format($payout->amount, 2, ',', ' ') }} kr</p>
        </div>
        <div class="detail-row">
            <p class="detail-label">Datum:</p>
            <p class="detail-value">{{ \Carbon\Carbon::parse($payout->created_at)->format('Y/m/d H:i') }}</p>
        </div>
    </div>

    @if($imageBase64)
    <div class="image-container">
        <img src="{{ $imageBase64 }}" alt="Kvitto">
    </div>
    @endif
</body>
</html>
