<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Inköpsavtal - PDF</title>
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
        
        .main-container {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
        }

        h2 {
            font-size: 13px;
            color: #008C91;
            margin-top: 10px;
            margin-bottom: 4px;
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
            margin: 0 0 6px 0;
            font-size: 24px;
            color: #454545;
        }

        .header-title-cell .contract-details {
            font-size: 10px;
            color: #454545;
        }

        /* --- Celdas principales de las secciones --- */
        .section-cell {
            padding-top: 4px;
        }

        .column-cell {
            width: 50%;
            vertical-align: top;
            padding-top: 4px;
        }

        .column-cell-left { padding-right: 4px; }
        .column-cell-right { padding-left: 4px; }
        .column-cell-left-2 { padding-right: 4px !important; }
        .column-cell-right-2 { padding-left: 4px !important; }

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
            padding: 6px 5px 0px 5px;
            border-radius: 4px;
            border: 1px solid #E7E7E7;
            min-height: 12px;
            color: #5D5D5D;
        }

        .info-table .value2 {
            font-size: 10px;
            background-color: #F6F6F6;
            padding: 5px 6px;
            border-radius: 4px;
            border: 1px solid #E7E7E7;
            line-height: 0.8;
            min-height: 60px;
            border-left: 1px solid #008C91;
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
        .footer-section {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0px;
            padding: 0;
            background: #FFFFFF;
        }

        .signatures-table {
            width: 100%;
            margin-top: 12px;
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

        .pb-0 {
            padding-bottom: 0 !important;
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
                                <h1>Inköpsavtal</h1>
                                <div class="contract-details">
                                    Avtalsnummer: {{ $agreement->agreement_id}} <br>
                                    Skapad:  {{ $agreement->vehicle_client->vehicle->purchase_date }}
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
            <!-- === SELLER / BUYER INFORMATION === -->
            <tr>
                <td class="column-cell column-cell-left">
                    <h2>Köpare</h2>
                    <table class="info-table">
                        <tr>
                            <td class="column-cell column-cell-left-2">
                                <div class="label">Företag</div>
                                <div class="value">
                                    {{ $company->company }} 
                                </div>
                            </td>
                            <td class="column-cell column-cell-right-2 pb-0">
                                <div class="label">Namn</div>
                                <div class="value">
                                    {{ $company->name }} {{ $company->last_name }}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="label">Adress</div>
                                <div class="value">
                                    {{ $company->address }} 
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="label">Postnr. ort</div>
                                <div class="value">
                                    {{ $company->postal_code }} {{ $company->street }}  
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="column-cell column-cell-left-2">
                                <div class="label">Org/person nr.</div>
                                <div class="value">
                                    {{ $company->organization_number }} 
                                </div>
                            </td>
                            <td class="column-cell column-cell-right-2">
                                <div class="label">Mobil</div>
                                <div class="value">
                                    {{ $company->phone }}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="label">E-post</div>
                                <div class="value">
                                    {{ $company->email }} 
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="column-cell column-cell-right">
                    <h2>Säljare</h2>
                    <table class="info-table">
                        <tr>
                            <td colspan="2">
                                <div class="label">Namn</div>
                                <div class="value">
                                    {{ $agreement->agreement_client->fullname }}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">    
                                <div class="label">Adress</div>
                                <div class="value">
                                    {{ $agreement->agreement_client->address }}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="label">Postnr. ort</div>
                                <div class="value">
                                    {{ $agreement->agreement_client->postal_code }} {{ $agreement->agreement_client->street }} 
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="column-cell column-cell-left-2">
                                <div class="label">Org/person nr.</div>
                                <div class="value">
                                    {{ $agreement->agreement_client->organization_number }}
                                </div>
                            </td>
                            <td class="column-cell column-cell-right-2">
                                <div class="label">Mobil</div>
                                <div class="value">
                                    {{ $agreement->agreement_client->phone }}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="label">E-post</div>
                                <div class="value">
                                    {{ $agreement->agreement_client->email }}
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <!-- === VEHICLE INFORMATION === -->
            <tr>
                <td class="column-cell column-cell-left">
                    <h2>Fordonsinformation</h2>
                    <table style="width: 100%;">
                        <tr>
                            <td style="padding:0;">
                                <table class="info-table">
                                    <tr>
                                        <td class="column-cell column-cell-left-2">
                                            <div class="label">Märke & Modell</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->model->brand->name }},
                                                {{ $agreement->vehicle_client->vehicle->model->name }}
                                            </div>
                                        </td>
                                        <td class="column-cell column-cell-right-2">
                                            <div class="label">Färg</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->color }}
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="column-cell column-cell-left-2">
                                            <div class="label">Årsmodell</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->year }}
                                            </div>
                                        </td>
                                        <td class="column-cell column-cell-right-2">
                                            <div class="label">Chassinummer (VIN)</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->chassis }}
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="column-cell column-cell-left-2">
                                            <div class="label">Reg nr</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->reg_num }}
                                            </div>
                                        </td>
                                        <td class="column-cell column-cell-right-2">
                                            <div class="label">Miltal</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->mileage }} Mil
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="column-cell column-cell-left-2">
                                            <div class="label">Drivmedel</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->fuel?->name }}
                                            </div>
                                        </td>
                                        <td class="column-cell column-cell-right-2">
                                            <div class="label">Växellåda</div>
                                            <div class="value">
                                            {{ $agreement->vehicle_client->vehicle->gearbox?->name }}
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="column-cell column-cell-left-2">
                                            <div class="label">Servicebok</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->service_book === 0 ? 'Ja' : 'Nej' }}
                                            </div>
                                        </td>
                                        <td class="column-cell column-cell-right-2">
                                            <div class="label">Antal nycklar till fordonet</div>
                                            <div class="value">
                                            {{ $agreement->vehicle_client->vehicle->number_keys }}
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="column-cell column-cell-right">
                    <h2 style="color: white;">.</h2>
                    <table style="width: 100%;">
                        <tr>
                            <td style="padding:0;">
                                <table class="info-table">
                                    <tr>
                                        <td>
                                            <div class="label">Sommardäck finns?</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->summer_tire === 0 ? 'Ja' : 'Nej' }}
                                            </div>
                                        </td>
                                        <td class="column-cell column-cell-right-2">
                                            <div class="label">Vinterdäck finns?</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->winter_tire === 0 ? 'Ja' : 'Nej' }}
                                            </div>
                                        </td>
                                    </tr>                                   
                                    <tr>
                                        <td class="column-cell column-cell-left-2">
                                            <div class="label">Inköpspris</div>
                                            <div class="value">
                                                {{ formatCurrency($agreement->vehicle_client->vehicle->purchase_price) }} kr
                                            </div>
                                        </td>
                                        <td class="column-cell column-cell-right-2">
                                            <div class="label">Varav moms</div>
                                            <div class="value">
                                                {{ formatCurrency($agreement->iva_sale_amount) }} kr
                                            </div>
                                        </td>
                                    </tr>
                                    @if($agreement->vehicle_client->vehicle->payment->is_loan === 0)
                                    <tr>
                                        <td class="column-cell column-cell-left-2">
                                            <div class="label">Kreditbelopp</div>
                                            <div class="value">
                                                {{ formatCurrency($agreement->vehicle_client->vehicle->payment->loan_amount) }} kr
                                            </div>
                                        </td>
                                        <td class="column-cell column-cell-right-2">
                                            <div class="label">Kredit/leasinggivare</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->payment->lessor }}
                                            </div>
                                        </td>
                                    </tr>
                                    @endif
                                    @if($agreement->vehicle_client->vehicle->payment->settled_by === 1)
                                    <tr>
                                        <td class="column-cell column-cell-left-2">
                                            <div class="label">Typ av utbetalning till säljaren</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->payment->payment_types->name }}
                                            </div>
                                        </td>
                                        <td class="column-cell column-cell-right-2">
                                            <div class="label">Restsumma</div>
                                            <div class="value">
                                                {{ formatCurrency($agreement->vehicle_client->vehicle->payment->remaining_amount) }} kr
                                            </div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="column-cell column-cell-left-2">
                                            <div class="label">Clearing/kontonummer</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->payment->account }}
                                            </div>
                                        </td>
                                        <td class="column-cell column-cell-right-2">
                                            <div class="label">Namn på banken</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->payment->bank }}
                                            </div>
                                        </td>
                                    </tr>
                                    @endif
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>                
            </tr>
            @if($agreement->vehicle_client->vehicle->comments)
            <tr>
                <td colspan="2">
                    <table class="info-table">
                        <tr>
                            <td colspan="2">
                                <div class="label">Anteckningar</div>
                                <div class="value">
                                    {{ $agreement->vehicle_client->vehicle->comments }}
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            @endif
            <!-- Kontantfaktura -->
            <tr>
                <td colspan="2" class="section-cell">
                    <h2>Kontantfaktura</h2>
                    <table class="financials-table">
                        <thead>
                            <tr>
                                <td>Specifikation</td>
                                <td>Pris</td>
                                <td>Moms</td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ $agreement->vehicle_client->vehicle->reg_num }}</td>
                                <td>{{ formatCurrency($agreement->vehicle_client->vehicle->purchase_price) }} kr</td>
                                <td>{{ formatCurrency($agreement->iva_sale_amount) }} kr</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2">Varav moms</td>
                                <td>{{ formatCurrency($agreement->iva_sale_amount) }} kr</td>
                            </tr>
                            <tr class="total-row">
                                <td colspan="2">Totalsumma</td>
                                <td>{{ formatCurrency($agreement->vehicle_client->vehicle->purchase_price) }} kr</td>
                            </tr>
                        </tfoot>
                    </table>
                    <p style="font-size:10px;margin-top:4px;">Utbetalning sker {{ $agreement->vehicle_client->vehicle->purchase_date }}</p>
                </td>
            </tr>
            <tr>
                <td class="column-cell column-cell-left">
                    <h2>Villkor</h2>
                    <table class="info-table">
                        <tr>
                            <td>
                                <div class="value2">
                                    {{ $agreement->terms_other_conditions }}
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="column-cell column-cell-right">
                    <h2>Övriga upplysningar</h2>
                    <table class="info-table">
                        <tr>
                            <td>
                                <div class="value2">
                                    {{ $agreement->terms_other_information }}
                                </div>
                            </td>
                        </tr>                    
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
    <!-- === FOOTER === -->
    <div class="footer-section">
        <table class="signatures-table">
            <tr>
                <!-- Celda Izquierda: Firma del Comprador (Köparens) - VACÍA -->
                <td style="width: 50%; padding-right: 20px; vertical-align: bottom;">
                    <div style="min-height: 70px;">
                        @if($company->img_signature)
                            <img src="{{ asset('storage/' . $company->img_signature) }}" alt="Firma Förmedlaren" style="width: auto; height: 70px;">
                        @endif
                    </div>
                    <div class="signature-box">(Köparens underskrift)</div>
                </td>

                <!-- Celda Derecha: Firma del Vendedor (Säljarens) - CON LA FIRMA DEL CLIENTE -->
                <td style="width: 50%; padding-left: 20px; vertical-align: bottom; position: relative;">
                    <div style="min-height: 70px;">
                        @if(isset($signature_url))
                            <img src="{{ $signature_url }}" alt="Firma" style="width: auto; height: 70px;">
                        @endif
                    </div>
                    <div class="signature-box">(Säljarens underskrift)</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>