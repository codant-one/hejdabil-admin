<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prisförslag</title>
    <style>
        /* Estilos optimizados para PDF de UNA SOLA PÁGINA con layout de TABLAS */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

        @page {
            margin: 1.5cm;
        }

        body {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            color: #333;
            background-color: #fff;
            margin: 0;
            padding: 0;
        }

        .invoice-box {
            /* --- CAMBIO PRINCIPAL AQUÍ --- */
            width: 100%;
            padding: 30px;
            box-sizing: border-box; /* Importante para que el padding no incremente el ancho total */
        }

        .main-title {
            font-size: 48px;
            font-weight: 800;
            color: #9333ea; /* Purple */
            margin-bottom: 40px;
        }

        .main-layout-table {
            width: 100%;
            border-collapse: collapse;
        }

        .main-layout-table td {
            vertical-align: top;
        }

        .left-column {
            width: 35%;
            padding-right: 20px;
        }

        .right-column {
            width: 65%;
            padding-left: 20px;
        }
        
        .info-block {
            margin-bottom: 20px;
        }

        .info-block p {
            margin: 0 0 5px 0;
        }

        .info-block strong {
            display: block;
            margin-bottom: 3px;
            color: #111;
        }

        .logo-container {
            padding-top: 100px; /* Space to push logo down */
        }

        .logo {
            max-width: 120px;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .details-table thead th {
            background-color: #a855f7; /* Lighter Purple */
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        
        .details-table th.amount, .details-table td.amount {
            text-align: right;
        }

        .details-table tr.item-row td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .details-table tr.anmarkning-header td {
            background-color: #a855f7; /* Lighter Purple */
            color: white;
            padding: 10px;
            font-weight: bold;
        }

        .details-table tr.anmarkning-row td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .details-table tr.summary-row td {
            background-color: #cffafe; /* Light Cyan */
            font-weight: bold;
            padding: 10px;
            border-top: 2px solid #9333ea;
        }

        .notes {
            margin-top: 30px;
            padding-left: 20px;
        }

        .notes ul {
            margin: 0;
            padding: 0;
            list-style: disc;
        }
        
        .notes li {
            margin-bottom: 10px;
        }

        .thank-you {
            margin-top: 40px;
            font-size: 16px;
            font-weight: bold;
            color: #9333ea;
        }

        .signature-area {
            margin-top: 40px;
            width: 250px;
            height: 60px; /* Altura para mantener el espacio de la firma */
            position: relative;
        }
        
        .signature-area img {
            max-width: 100%;
            max-height: 100%;
        }

        .signature-line {
            border-top: 1px solid #555;
            margin-top: 5px;
        }

        .signature-name {
            text-align: center;
            margin-top: 5px;
        }
        
        .seal-container {
            margin-top: 30px;
            text-align: center;
        }

        .seal {
            width: 100px;
            height: 100px;
        }

    </style>
</head>
<body>
    <div class="invoice-box">
        <!-- Título principal -->
        <h1 class="main-title">Prisförslag</h1>

        <!-- Tabla principal para layout de 2 columnas -->
        <table class="main-layout-table">
            <tr>
                <!-- COLUMNA IZQUIERDA -->
                <td class="left-column">
                    <div class="info-block">
                        <strong>Datum</strong>
                        <p>{{ $agreement->offer->created_at->format('Y-m-d')}}</p>
                    </div>

                    <div class="info-block">
                        <strong>Offert #</strong>
                        <p>{{ $agreement->offer->id }}</p>
                    </div>

                    <div class="info-block">
                        <strong>Förberedd av</strong>
                        <p>{{ $user->name }} {{ $user->last_name }}</p>
                    </div>

                    <div class="info-block">
                        <strong>Mail</strong>
                        <p>{{ $user->email }}</p>
                    </div>

                    <div class="info-block">
                        <strong>Adress</strong>
                        <p>
                            {{ $user->userDetail->address }} 
                        </p>
                    </div>

                    <div class="info-block">
                        <strong>Tel</strong>
                        <p>{{ $user->userDetail->phone }} </p>
                    </div>
                    
                    <div class="logo-container">
                        @if(!$agreement->supplier)
                            <img src="{{ asset('/logos/logo_black.png') }}" width="200" alt="logo-main">  
                        @else
                            @if($agreement->supplier->logo)
                                <img src="{{ asset('storage/'.$agreement->supplier->logo) }}" width="200" alt="logo-main">
                            @else
                                <img src="{{ asset('/logos/logo_black.png') }}" width="150" alt="logo-main">
                            @endif
                        @endif
                    </div>
                </td>

                <!-- COLUMNA DERECHA -->
                <td class="right-column">
                    <h2>Kommentarer och special instruktioner</h2>

                    <!-- Tabla de precios y detalles -->
                    <table class="details-table">
                        <thead>
                            <tr>
                                <th>Beskrivning</th>
                                <th class="amount">BELOPP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="item-row">
                                <td>
                                    <strong>{{ $agreement->offer->reg_num }}</strong><br>
                                    {{ $agreement->offer->model->brand->name }} {{ $agreement->offer->model->name }}<br>
                                    Mätarställning: Cirka {{ $agreement->offer->mileage }} Mil
                                </td>
                                <td class="amount">{{ formatCurrency($agreement->offer->price) }} kr</td>
                            </tr>
                            @if($agreement->offer->comment)
                            <tr class="anmarkning-header">
                                <td colspan="2">Anmärkning</td>
                            </tr>
                             <tr class="anmarkning-row">
                                <td colspan="2">{{ $agreement->offer->comment }}</td>
                            </tr>
                            @endif
                        </tbody>
                        <tfoot>
                            <tr class="summary-row">
                                <td>SUMMA</td>
                                <td class="amount">{{ formatCurrency($agreement->offer->price) }} kr</td>
                            </tr>
                        </tfoot>
                    </table>
                    
                    <div class="notes">
                        {{ $agreement->offer->terms_other_conditions }}
                    </div>

                    <p class="thank-you">TACK FÖR DIN BESÖK!</p>
                    
                    <table style="width: 100%;">
                        <tr>
                            <td style="width: 50%; vertical-align: bottom;">
                                <div class="signature-area">
                                    
                                </div>
                                <div class="signature-line"></div>
                                <div class="signature-name">
                                    <p>{{ $user->name }} {{ $user->last_name }}</p>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>