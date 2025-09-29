<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Prisförslag - PDF</title>
    <!-- ESTILOS COPIADOS EXACTAMENTE DEL CONTRATO 1 -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

        @page {
            margin: 1.5cm;
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            font-size: 11px;
            color: #333;
            background-color: #fff;
        }
        
        .main-container {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
        }

        h2 {
            font-size: 13px;
            color: #0056b3;
            margin-top: 0;
            margin-bottom: 8px; /* Un poco de espacio extra para los títulos de sección */
        }

        /* --- HEADER --- */
        .header-logo {
            width: 150px;
        }
        .header-logo img {
            max-width: 100%;
        }
        .header-title-cell {
            text-align: right;
            vertical-align: top;
        }
        .header-title-cell h1 {
            margin: 0;
            font-size: 24px;
            color: #111;
        }
        .header-title-cell .contract-details {
            font-size: 10px;
            color: #555;
            margin-top: 5px;
        }

        /* --- Celdas principales de las secciones --- */
        .section-cell {
            padding-top: 20px;
        }
        .column-cell {
            width: 50%;
            vertical-align: top;
            padding-top: 15px;
        }
        .column-cell-left { padding-right: 15px; }
        .column-cell-right { padding-left: 15px; }

        /* --- Tablas de información (Key-Value) --- */
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 0;
            padding-bottom: 6px;
        }
        .info-table .label {
            font-weight: 600;
            display: block;
            margin-bottom: 3px;
            color: #555;
            font-size: 10px;
        }
        .info-table .value {
            font-size: 10px;
            background-color: #f9f9f9;
            padding: 5px;
            border-radius: 4px;
            border: 1px solid #eee;
            min-height: 12px
        }

        /* --- TABLA FINANCIERA --- */
        .financials-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        .financials-table td {
            padding: 6px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        .financials-table td:last-child {
            text-align: right;
            font-weight: 600;
        }
        .financials-table .total-row td {
            font-size: 13px;
            font-weight: 700;
            color: #0056b3;
            border-top: 2px solid #ccc;
            border-bottom: none;
            padding-top: 8px;
            padding-bottom: 8px;
        }

        /* --- PIE DE PÁGINA --- */
        .footer-section { padding-top: 25px; }
        .notes-text {
            font-size: 10px;
            color: #666;
            background-color: #f9f9f9;
            padding: 10px;
            border-radius: 4px;
            border-left: 3px solid #0056b3;
            margin-bottom: 15px;
        }
        .signatures-table {
            width: 100%;
            margin-top: 30px;
        }
        .signature-box {
            border-top: 1px solid #333;
            padding-top: 8px;
            font-size: 11px; /* Ajustado para consistencia */
            color: #333;
        }
        .thank-you-text {
            font-size: 14px;
            font-weight: bold;
            color: #0056b3;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <table class="main-container">
        <tbody>
            <!-- === HEADER === -->
            <tr>
                <td colspan="2" style="padding-bottom: 10px; border-bottom: 2px solid #e0e0e0;">
                    <table style="width: 100%;">
                        <tr>
                            <td style="vertical-align: top;">
                                <div class="header-logo">
                                    @if(!$agreement->supplier)
                                        <img src="{{ asset('/logos/logo_black.png') }}" alt="logo-main">  
                                    @else
                                        @if($agreement->supplier->logo)
                                            <img src="{{ asset('storage/'.$agreement->supplier->logo) }}" alt="logo-main">
                                        @else
                                            <img src="{{ asset('/logos/logo_black.png') }}" alt="logo-main">
                                        @endif
                                    @endif
                                </div>
                            </td>
                            <td class="header-title-cell">
                                <h1>Prisförslag</h1>
                                <div class="contract-details">
                                    Offert nr: {{ $agreement->offer->id }} <br>
                                    Datum: {{ $agreement->offer->created_at->format('Y-m-d')}}
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- === PREPARED BY / VEHICLE INFORMATION === -->
            <tr>
                <td class="column-cell column-cell-left">
                    <h2>Förberedd av</h2>
                    <table class="info-table">
                        <tr>
                            <td>
                                <div class="label">Företag</div>
                                <div class="value">
                                    @if(!$agreement->supplier)
                                        {{ $user->userDetail->company }} 
                                    @else
                                        {{ $agreement->supplier?->company }} 
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="label">Namn</div>
                                @if($user)
                                    <div class="value">
                                        {{ $user->name }} {{ $user->last_name }} 
                                    </div>
                                @else
                                    <div class="value">
                                        Admin Billogg
                                    </div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="label">Adress</div>
                                <div class="value">
                                    @if(!$agreement->supplier)
                                        {{ $user->userDetail->address }}
                                    @else
                                        {{ $agreement->supplier?->address }}
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="label">E-post</div>
                                @if($user)
                                    <div class="value">{{ $user->email }}</div>
                                @else
                                    <div class="value">admin@billogg.se</div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="label">Telefon</div>
                                @if($user)
                                    <div class="value">{{ $user->userDetail->phone }}</div>
                                @else
                                    <div class="value">+57 313 209 8455</div>
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="column-cell column-cell-right">
                    <h2>Information om fordonet</h2>
                    <table class="info-table">
                        <tr>
                            <td>
                                <div class="label">Regnr</div>
                                <div class="value">{{ $agreement->offer->reg_num }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="label">Märke & Modell</div>
                                <div class="value">
                                    {{ $agreement->offer->model->brand->name }} {{ $agreement->offer->model->name }}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="label">Mätarställning</div>
                                <div class="value">
                                    {{ $agreement->offer->mileage }} Mil
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- === FINANCIAL OVERVIEW === -->
            <tr>
                <td colspan="2" class="section-cell">
                    <h2>Prisinformation</h2>
                    <table class="financials-table">
                        <tbody>
                            <tr>
                                <td>Pris för {{ $agreement->offer->reg_num }}</td>
                                <td>{{ formatCurrency($agreement->offer->price) }} kr</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td>SUMMA</td>
                                <td>{{ formatCurrency($agreement->offer->price) }} kr</td>
                            </tr>
                        </tfoot>
                    </table>
                </td>
            </tr>
            
            <!-- === COMMENTS & CONDITIONS === -->
            <tr>
                <td colspan="2" class="section-cell">
                    @if($agreement->offer->comment)
                        <h2>Anmärkning</h2>
                        <div class="notes-text" style="margin-bottom: 20px;">
                            {{ $agreement->offer->comment }}
                        </div>
                    @endif

                    @if($agreement->offer->terms_other_conditions)
                        <h2>Övriga villkor</h2>
                        <div class="notes-text">
                            {!! nl2br(e($agreement->offer->terms_other_conditions)) !!}
                        </div>
                    @endif
                </td>
            </tr>


            <!-- === FOOTER & SIGNATURE === -->
            <tr>
                <td colspan="2" class="footer-section">
                    <div class="thank-you-text">TACK FÖR DIN FÖRFRÅGAN!</div>
                    <table class="signatures-table">
                        <tr>
                            @if(isset($signature_url))
                                <div style="margin-top: 50px; padding-top: 10px; border-top: 1px solid #ccc;">
                                    <p style="margin-bottom: 5px;"><strong>Firma Electrónica del Cliente:</strong></p>
                                    <img src="{{ $signature_url }}" alt="Firma" style="width: 250px; height: auto;">
                                </div>
                            @endif
                            <td style="width: 50%; padding-right: 20px;">
                                <div class="signature-box">(Köparens underskrift)</div>
                                @if($user)
                                <div style="padding-top: 5px; font-size: 10px;">{{ $user->name }} {{ $user->last_name }}</div>
                                @else
                                <div style="padding-top: 5px; font-size: 10px;">Admin Billogg</div>
                                @endif
                            </td>
                            <td style="width: 50%;"></td> <!-- Espacio en blanco a la derecha -->
                        </tr>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

</body>
</html>