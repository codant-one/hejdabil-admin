<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Försäljningsavtal - PDF</title>
    <style>
        /* Estilos optimizados para PDF de UNA SOLA PÁGINA con layout de TABLAS */
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
        
        /* Tabla principal que actúa como contenedor */
        .main-container {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
        }

        /* Títulos de sección */
        h2 {
            font-size: 13px;
            color: #0056b3;
            margin-top: 0;
            margin-bottom: 0;
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
            padding-top: 10px;
        }
        .column-cell {
            width: 50%;
            vertical-align: top;
            padding-top: 10px;
        }
        /* Añade espacio entre las columnas */
        .column-cell-left { padding-right: 15px; }
        .column-cell-right { padding-left: 15px; }

        .column-cell-left-2 { padding-right: 10px !important; }
        .column-cell-right-2 { padding-left: 10px !important; }

        /* --- Tablas de información (Key-Value) --- */
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 0;
            padding-bottom: 4px;
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
        }
        .financials-table td {
            padding: 5px;
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
        }
        .financials-table .moms-row td { font-weight: normal; }

        /* --- PIE DE PÁGINA --- */
        .footer-section { padding-top: 10px; }
        .consent-text {
            font-size: 10px;
            color: #666;
            background-color: #f9f9f9;
            padding: 10px;
            border-radius: 4px;
            border-left: 3px solid #0056b3;
            margin-bottom: 30px;
        }
        .signatures-table {
            width: 100%;
        }
        .signature-box {
            border-top: 1px solid #333;
            padding-top: 8px;
            font-size: 13px;
            color: #333;
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
                            <td class="header-title-cell">
                                <h1>Försäljningsavtal</h1>
                                <div class="contract-details">
                                    Köpeavtal nr: {{ $agreement->agreement_id}} <br>
                                    @if(!$agreement->supplier)
                                        {{ strtoupper($user->userDetail->address) }}, {{ strtoupper($user->userDetail->postal_code) }} {{ strtoupper($user->userDetail->street) }} 
                                    @else
                                        {{ strtoupper($agreement->supplier?->address) }}, {{ strtoupper($agreement->supplier?->postal_code) }} {{ strtoupper($agreement->supplier?->street) }} 
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
                        <tr>
                            <td>
                                <div class="label">Bilhandlare</div>
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
                                <div class="label">Försäljare</div>
                                <div class="value">
                                    {{ $user->name }} {{ $user->last_name }} 
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="label">Organisationsnr</div>
                                <div class="value">
                                    @if(!$agreement->supplier)
                                        {{ $user->userDetail->organization_number }} 
                                    @else
                                        {{ $agreement->supplier?->organization_number }} 
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="label">E-post</div>
                                <div class="value">
                                    {{ $user->email }} 
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="label">Mobiltelefon</div>
                                <div class="value">
                                    @if(!$agreement->supplier)
                                        {{ $user->userDetail->phone }} 
                                    @else
                                        {{ $agreement->supplier?->phone }} 
                                    @endif
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="column-cell column-cell-right">
                    <h2>Köparinformation</h2>
                    <table class="info-table">
                        <tr>
                            <td>
                                <div class="label">Köpare</div>
                                <div class="value">
                                    {{ $agreement->agreement_client->fullname }}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="label">Utdelningsadress</div>
                                <div class="value">
                                {{ $agreement->agreement_client->address }}, {{ $agreement->agreement_client->postal_code }} {{ $agreement->agreement_client->street }} 
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="label">Person- eller organisationsnr.</div>
                                <div class="value">
                                    {{ $agreement->agreement_client->organization_number }}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="label">E-post</div>
                                <div class="value">
                                    {{ $agreement->agreement_client->email }}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="label">Mobiltelefon</div>
                                <div class="value">
                                    {{ $agreement->agreement_client->phone }}
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
                                        <td class="column-cell column-cell-right-2">
                                            <div class="label">Första registreringsdatum</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->purchase_date }}
                                            </div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="column-cell column-cell-left-2">
                                            <div class="label">Försäljningsdatum</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->sale_date }}
                                            </div>
                                        </td>
                                        <td class="column-cell column-cell-right-2">
                                            <div class="label">Garanti</div>
                                            <div class="value">
                                                {{ $agreement->guaranty->name }}
                                            </div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td colspan="2">
                                            <div class="label">Försäkring</div>
                                            <div class="value">
                                                {{ $agreement->insurance_company->name }} 
                                            </div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td colspan="2">
                                            <div class="label">Miltal</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->mileage }}
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
                                <td>{{ formatCurrency($agreement->total_sale) }} kr</td>
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
                                                @if(!$agreement->supplier)
                                                    {{ $user->userDetail->plus_spin }} 
                                                @else
                                                    {{ $agreement->supplier?->plus_spin }} 
                                                @endif 
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
                                                @if(!$agreement->supplier)
                                                    {{ $user->userDetail->bank }} 
                                                @else
                                                    {{ $agreement->supplier?->bank }} 
                                                @endif 
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="label">Kontonummer</div>
                                            <div class="value">
                                               @if(!$agreement->supplier)
                                                    {{ $user->userDetail->account_number }} 
                                                @else
                                                    {{ $agreement->supplier?->account_number }} 
                                                @endif 
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- === FOOTER === -->
            <tr>
                <td colspan="2" class="footer-section">
                    <div class="consent-text"><p style="margin: 0;">Köparen samtycker till att dennes uppgifter lagras för lagstadgad räkenskapsinformation, Kap.2§ första stycket 8b BFL 2010:1514.</p></div>
                    <table class="signatures-table">
                        <tr>
                            <td style="width: 50%; padding-right: 20px;"><div class="signature-box">(Köparens underskrift)</div></td>
                            <td style="width: 50%; padding-left: 20px;"><div class="signature-box">(Säljföretagets underskrift - behörig företrädare)</div></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

</body>
</html>