<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Försäljningsavtal - PDF</title>
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
            margin-top: 0;
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
            border-radius: 8px;
            border: 1px solid #E7E7E7;
            min-height: 12px;
            color: #5D5D5D;
        }

        .info-table .value2 {
            font-size: 10px;
            background-color: #F6F6F6;
            padding: 5px 6px;
            border-radius: 8px;
            border: 1px solid #E7E7E7;
            line-height: 0.8;
            min-height: 40px;
            border-left: 1px solid #008C91;
        }

        /* --- TABLA FINANCIERA --- */
        .financials-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            font-size: 10px;
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
            padding-top: 4px;
            padding-bottom: 8px;
        }

        .financials-table .moms-row td { font-weight: normal; }

        /* --- PIE DE PÁGINA --- */
        .footer-section {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0px;
            padding: 0;
            background: #FFFFFF;
        }

        .consent-text {
            font-size: 10px;
            background-color: #F6F6F6;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #E7E7E7;
            border-left: 1px solid #008C91;
            margin-bottom: 30px;
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
            padding-top: 4px;
            font-size: 11px; /* Ajustado para consistencia */
            color: #454545;
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
                                <h1>Försäljningsavtal</h1>
                                <div class="contract-details">
                                    Avtalsnummer: {{ $agreement->agreement_id}} <br>
                                    {{ strtoupper($company->address) }}, {{ strtoupper($company->postal_code) }} {{ strtoupper($company->street) }} 
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
                    <h2>Säljarinformation</h2>
                    <table class="info-table">
                        <!-- INICIO DEL CAMBIO 2 (Múltiples campos) -->
                        <tr>
                            <td colspan="2">
                                <div class="label">Bilhandlare</div>
                                <div class="value">
                                        {{ $company->company }} 
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="label">Försäljare</div>
                                <div class="value"> 
                                    {{ $company->name }} {{ $company->last_name }}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="column-cell column-cell-left-2">
                                <div class="label">Organisationsnr</div>
                                <div class="value">
                                    {{ $company->organization_number }}
                                </div>
                            </td>
                            <td class="column-cell column-cell-right-2">
                                <div class="label">Mobiltelefon</div>
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
                    <h2>Köparinformation</h2>
                    <table class="info-table">
                        <tr>
                            <td colspan="2">
                                <div class="label">Köpare</div>
                                <div class="value">
                                    {{ $agreement->agreement_client->fullname }}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="label">Adress</div>
                                <div class="value">
                                {{ $agreement->agreement_client->address }}, {{ $agreement->agreement_client->postal_code }} {{ $agreement->agreement_client->street }} 
                                </div>
                            </td>
                        </tr>
                         <tr>
                            <td class="column-cell column-cell-left-2">
                                <div class="label">Person- eller organisationsnr.</div>
                                <div class="value">
                                    {{ $agreement->agreement_client->organization_number }}
                                </div>
                            </td>
                            <td class="column-cell column-cell-right-2">
                                <div class="label">Mobiltelefon</div>
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
                                            <div class="label">Regnr</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->reg_num }}
                                            </div>
                                        </td>
                                        <td class="column-cell column-cell-left-2">
                                            <div class="label">Försäljningsdatum</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->sale_date }}
                                            </div>
                                        </td>
                                    </tr>
                                    @if($agreement->guaranty === 1)
                                    <tr>                                        
                                        <td colspan="2">
                                            <div class="label">Garanti</div>
                                            <div class="value">
                                                {{ $agreement->guaranty_description }}
                                            </div>
                                        </td>                                        
                                    </tr>
                                    @endif
                                    @if($agreement->insurance_company === 1)
                                    <tr>
                                        <td colspan="2">
                                            <div class="label">Försäkring</div>
                                            <div class="value">
                                                {{ $agreement->insurance_company_description }} 
                                            </div>
                                        </td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td colspan="2">
                                            <div class="label">Miltal</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->mileage }} Mil
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="column-cell column-cell-right">
                    <h2>Inbytesfordon</h2>
                    <table style="width: 100%;">
                        <tr>
                            <td style="padding:0;">
                                <table class="info-table">
                                    <tr>
                                        <td class="column-cell column-cell-left-2">
                                            <div class="label">Märke & Modell</div>
                                            <div class="value">
                                                {{ collect([
                                                    $agreement->vehicle_interchange?->model?->brand?->name,
                                                    $agreement->vehicle_interchange?->model?->name
                                                ])->filter()->implode(', ') }}
                                            </div>
                                        </td>
                                        <td class="column-cell column-cell-right-2">
                                            <div class="label">Färg</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_interchange?->color }}
                                            </div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="column-cell column-cell-left-2">
                                            <div class="label">Årsmodell</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_interchange?->year }}
                                            </div>
                                        </td>
                                        <td class="column-cell column-cell-right-2">
                                            <div class="label">Chassinummer (VIN)</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_interchange?->chassis }}
                                            </div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="column-cell column-cell-left-2">
                                            <div class="label">Regnr</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_interchange?->reg_num }}
                                            </div>
                                        </td>
                                        <td class="column-cell column-cell-right-2">
                                            <div class="label">Försäljningsdatum</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_interchange?->created_at->format('Y-m-d') }}
                                            </div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="column-cell column-cell-left-2">
                                            <div class="label">Inbytespris</div>
                                            <div class="value">
                                                {{ formatCurrency($agreement->vehicle_interchange?->purchase_price) }} kr
                                            </div>
                                        </td>
                                        <td class="column-cell column-cell-right-2">
                                            <div class="label">Mätarställning</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_interchange?->meter_reading }}
                                            </div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="column-cell column-cell-left-2">
                                            <div class="label">Kaross</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_interchange?->carbody?->name }}
                                            </div>
                                        </td>
                                        <td class="column-cell column-cell-right-2">
                                            <div class="label">Avdragbar moms</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_interchange?->iva_purchase?->name }}
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        @if($agreement->residual_debt === 1)
                                        <td class="column-cell column-cell-left-2">
                                            <div class="label">Restskuld</div>
                                            <div class="value">
                                                {{ formatCurrency($agreement->residual_price) }} kr
                                            </div>
                                        </td>
                                        @endif
                                        <td class="column-cell column-cell-right-2">
                                            <div class="label">Verkligt värde</div>
                                            <div class="value">
                                                {{ formatCurrency($agreement->fair_value) }} kr
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- === FINANCIAL OVERVIEW === -->
            <tr>
                <td colspan="2" class="section-cell">
                    <h2>Ekonomisk Översikt</h2>
                    <table class="financials-table">
                        <tbody>
                            <tr>
                                <td>Bilens begärda pris</td>
                                <td>{{ formatCurrency($agreement->price) }} kr</td>
                            </tr>
                            <tr>
                                <td>Inbytespris</td>
                                @if($agreement->residual_debt === 1)
                                <td>{{ formatCurrency($agreement->fair_value) }} kr</td>
                                @else
                                <td>- {{ formatCurrency($agreement->vehicle_interchange?->purchase_price) }} kr</td>
                                @endif
                            </tr>
                            <tr>
                                <td>Avgår rabatt</td>
                                <td>{{ formatCurrency($agreement->discount) }} kr</td>
                            </tr>
                            <tr>
                                <td>Registreringsavgift</td>
                                <td>{{ formatCurrency($agreement->registration_fee) }} kr</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td>Totalpris</td>
                                <td>{{ formatCurrency($agreement->middle_price) }} kr</td>
                            </tr>
                            <tr class="moms-row">
                                <td>Varav moms (20%)</td>
                                <td>{{ formatCurrency($agreement->iva_sale_amount) }} kr</td>
                            </tr>
                        </tfoot>
                    </table>
                </td>
            </tr>
            
            <!-- === PAYMENT INFORMATION === -->
            <tr>
                <td colspan="2" class="section-cell">
                    <h2>Betalningsinformation</h2>
                    <table style="width:100%;">
                        <tr>
                            <td class="column-cell" style="padding-top:0; padding-right:15px; padding-left:0;">
                                <table class="info-table">
                                    <tr>
                                        <td>
                                            <div class="label">Kontant / Handpenning</div>
                                            <div class="value">
                                                {{ formatCurrency($agreement->payment_received) }} kr
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="label">Återstående belopp / Finansiering</div>
                                            <div class="value">
                                                {{ formatCurrency($agreement->installment_amount) }} kr
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="label">Plusgiro</div>
                                            <div class="value">
                                                {{ $company->plus_spin }}
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                            <td class="column-cell" style="padding-top:0; padding-left:15px; padding-right:0;">
                                <table class="info-table">
                                    <tr>
                                        <td>
                                            <div class="label">Betalsätt för handpenning</div>
                                            <div class="value">
                                                {{ $agreement->payment_types->name }} 
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="label">Bank för inbetalning</div>
                                            <div class="value">
                                                {{ $company->bank }}
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="label">Kontonummer</div>
                                            <div class="value">
                                                {{ $company->account_number }}
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            @if($agreement->terms_other_conditions!==null || $agreement->terms_other_information!==null)
            <tr>
                <td colspan="2" class="section-cell">
                    <h2>Villkor</h2>
                </td>
            </tr>
            @endif
            <tr>  
                @if($agreement->terms_other_conditions!==null)
                <td class="column-cell column-cell-left section-cell">
                    <h2>Övriga villkor</h2>
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
                @endif
                @if($agreement->terms_other_information!==null)
                <td class="column-cell column-cell-right section-cell">
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
                @endif
            </tr>
        </tbody>
    </table>
        <!-- === FOOTER === -->
        <div class="footer-section">
            <div class="consent-text">
                <p style="margin: 0;">
                    Köparen samtycker till att dennes uppgifter lagras för lagstadgad räkenskapsinformation, Kap.2§ första stycket 8b BFL 2010:1514.
                </p>
            </div>
            <table class="signatures-table" style="width: 100%;">
                <tr>
                    <!-- Celda Izquierda: Firma del Comprador (Köparens) - CON LA FIRMA DEL CLIENTE -->
                    <td style="width: 50%; padding-right: 20px; vertical-align: bottom; position: relative;">
                        <div style="min-height: 70px;">
                            {{-- Lógica para la firma estática del cliente (comprador) --}}
                            @if(isset($signature_url) && $signature_x === null)  
                                <img src="{{ $signature_url }}" alt="Firma" style="width: auto; height: 70px;">
                            @endif
                        </div>
                        <div class="signature-box">(Köparens underskrift)</div>
                    </td>
                    
                    <!-- Celda Derecha: Firma del Vendedor (Säljföretagets) - VACÍA -->
                    <td style="width: 50%; padding-left: 20px; vertical-align: bottom;">
                        <div style="min-height: 70px;">
                            @if($company->img_signature)
                                <img src="{{ asset('storage/' . $company->img_signature) }}" alt="Firma Förmedlaren" style="width: auto; height: 70px;">
                            @endif
                        </div>
                        <div class="signature-box">(Säljföretagets underskrift)</div>
                    </td>
                </tr>
            </table>
        </div>
</body>
</html>