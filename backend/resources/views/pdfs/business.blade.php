<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Prisförslag - PDF</title>
    <style>
        body {
            font-family: 'gelion', 'dm sans', sans-serif !important;
            background-color: #FFFFFF;
            padding: 0;
            margin: 0;
            letter-spacing: 0 !important;
            word-spacing: normal !important;
            line-height: 1;
        }
        
        .main-container {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
        }

        h2 {
            font-size: 13px;
            color: #008C91;
            margin-top: 0;
            margin-bottom: 8px; /* Un poco de espacio extra para los títulos de sección */
        }

        /* --- HEADER --- */
        .header-logo {
            width: 150px;
            display: inline-block;
            text-align: right;
        }
        .header-logo img {
            max-width: 100%;
        }
        .header-logo-cell {
            vertical-align: top;
            text-align: right;
        }
        .header-title-cell {
            text-align: left;
            vertical-align: top;
        }
        .header-title-cell h1 {
            margin: 0;
            font-size: 24px;
            color: #5D5D5D;
        }
        .header-title-cell .contract-details {
            font-size: 10px;
            color: #5D5D5D;
        }

        /* --- Celdas principales de las secciones --- */
        .section-cell {
            padding-top: 10px;
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
            color: #454545;
            font-size: 10px;
            margin-bottom: 2px;
        }
        .info-table .value {
            font-size: 10px;
            background-color: #F6F6F6;
            padding: 5px;
            border-radius: 8px;
            border: 1px solid #E7E7E7;
            min-height: 12px;
            color: #5D5D5D;
        }

        /* --- TABLA FINANCIERA --- */
        .financials-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        .financials-table td {
            padding: 6px 0;
            text-align: left;
            border-bottom: 1px solid #E7E7E7;
        }
        .financials-table td:last-child {
            text-align: right;
            font-weight: 600;
        }
        .financials-table .total-row td {
            font-size: 13px;
            font-weight: 700;
            color: #008C91;
            border-top: 2px solid #E7E7E7;
            border-bottom: none;
            padding-top: 8px;
            padding-bottom: 8px;
        }

        /* --- PIE DE PÁGINA --- */
        .footer-section { padding-top: 25px; }
        .notes-text {
            font-size: 10px;
            color: #5D5D5D;
            background-color: #F6F6F6;
            padding: 10px;
            border-radius: 8px;
            border-left: 1px solid #008C91;
            margin-bottom: 15px;
        }
        .signatures-table {
            width: 100%;
            margin-top: 30px;
            table-layout: fixed;
            border-collapse: collapse;
        }
        .signatures-table td {
            width: 50%;
            vertical-align: bottom;
        }
        .signature-box {
            border-top: 1px solid #454545;
            padding-top: 8px;
            font-size: 11px; /* Ajustado para consistencia */
            color: #454545;
        }
        .thank-you-text {
            font-size: 14px;
            font-weight: bold;
            color: #008C91;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <table class="main-container">
        <tbody>
            <!-- === HEADER === -->
            <tr>
                <td colspan="2" style="padding-bottom: 10px; border-bottom: 2px solid #E7E7E7;">
                    <table style="width: 100%;">
                        <tr>                            
                            <td class="header-title-cell">
                                <h1>Prisförslag</h1>
                                <div class="contract-details">
                                    Offert nr: {{ $agreement->offer->id }} <br>
                                    Datum: {{ $agreement->offer->created_at->format('Y-m-d')}}
                                </div>
                            </td>
                            <td class="header-logo-cell">
                                <div class="header-logo">
                                    @if($company->logo)
                                        <img src="{{ asset('storage/'.$company->logo) }}" width="150" alt="logo-main">
                                    @else
                                        <img src="{{ asset('/logos/logo_black.png') }}" width="150" alt="logo-main">
                                    @endif
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
                                    {{ $company->company }}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="label">Namn</div>
                                <div class="value">
                                    {{ $company->name }} {{ $company->last_name }} 
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="label">Adress</div>
                                <div class="value">
                                    {{ $company->address }}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="label">E-post</div>
                                <div class="value">{{ $company->email }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="label">Telefon</div>
                                <div class="value">{{ $company->phone }}</div>
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
                                    @if($agreement->offer && $agreement->offer->model)
                                        {{ $agreement->offer->model->brand->name }} {{ $agreement->offer->model->name }}
                                    @else
                                        -
                                    @endif
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

            <!-- === CUSTOMER INFORMATION === -->
            @if($agreement->agreement_client)
            <tr>
                <td colspan="2" class="section-cell">
                    <h2>Kundinformation</h2>
                    <table class="info-table">
                        <tr>
                            <td style="width: 50%; padding-right: 15px; vertical-align: top;">
                                <div class="label">Namn</div>
                                <div class="value">{{ $agreement->agreement_client->fullname ?? '-' }}</div>
                            </td>
                            <td style="width: 50%; padding-left: 15px; vertical-align: top;">
                                <div class="label">E-post</div>
                                <div class="value">{{ $agreement->agreement_client->email ?? '-' }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 50%; padding-right: 15px; vertical-align: top;">
                                <div class="label">Org/personummer</div>
                                <div class="value">{{ $agreement->agreement_client->organization_number ?? '-' }}</div>
                            </td>
                            <td style="width: 50%; padding-left: 15px; vertical-align: top;">
                                <div class="label">Telefon</div>
                                <div class="value">{{ $agreement->agreement_client->phone ?? '-' }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="label">Adress</div>
                                <div class="value">
                                    {{ $agreement->agreement_client->address ?? '' }}
                                    @if(!empty($agreement->agreement_client->postal_code) || !empty($agreement->agreement_client->street))
                                        <br>
                                        {{ $agreement->agreement_client->postal_code ?? '' }} {{ $agreement->agreement_client->street ?? '' }}
                                    @endif
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            @endif

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
                        <div class="notes-text">
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
                            <!-- Left: Kund (köpare) -->
                            <td style="padding-right: 20px; vertical-align: bottom; position: relative;">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="height: 70px; vertical-align: bottom;">
                                            @if(isset($signature_url) && $signature_x === null)
                                                <img src="{{ $signature_url }}" alt="Firma" style="width: auto; height: 70px;">
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="signature-box">(Köparens underskrift)</div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="height: 15px; font-size: 10px;">&nbsp;</td>
                                    </tr>
                                </table>
                            </td>

                            <!-- Right: Säljföretaget -->
                            <td style="padding-left: 20px; vertical-align: bottom; text-align: right;">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="height: 70px; vertical-align: bottom; text-align: right;">
                                            @if($company->img_signature)
                                                <img src="{{ asset('storage/' . $company->img_signature) }}" alt="Firma Förmedlaren" style="width: auto; height: 70px;">
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: right;">
                                            <div class="signature-box">(Säljföretagets underskrift)</div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="height: 15px; padding-top: 5px; font-size: 10px; text-align: right;">{{ $company->name }} {{ $company->last_name }}</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

</body>
</html>