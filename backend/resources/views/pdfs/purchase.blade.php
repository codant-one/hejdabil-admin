<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Inköpsavtal - Förhandsvisning</title>
    <!-- Basado en los estilos de sales_billog.blade.php -->
    <style>
        /* Estilos optimizados para PDF de UNA SOLA PÁGINA con layout de TABLAS */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

        @page {
            margin: 1.2cm;
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            font-size: 10.5px;
            color: #333;
            background-color: #fff;
            line-height: 1.35;
        }

        /* Tabla principal */
        .main-container {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
        }

        /* Títulos */
        h2 {
            font-size: 12px;
            color: #0056b3;
            margin: 0 0 6px 0;
        }

        /* --- HEADER --- */
        .header-logo {
            width: 140px;
        }
        .header-title-cell {
            text-align: right;
            vertical-align: top;
        }
        .header-title-cell h1 {
            margin: 0;
            font-size: 20px;
            color: #111;
        }
        .header-title-cell .contract-details {
            font-size: 9.5px;
            color: #555;
            margin-top: 4px;
        }

        /* --- Celdas de secciones --- */
        .section-cell {
            padding-top: 8px;
        }
        .column-cell {
            width: 50%;
            vertical-align: top;
            padding-top: 8px;
        }
        .column-cell-left { padding-right: 10px; }
        .column-cell-right { padding-left: 10px; }

        .column-cell-left-2 { padding-right: 8px !important; }
        .column-cell-right-2 { padding-left: 8px !important; }

        /* --- Tablas de información --- */
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 0;
            padding-bottom: 3px;
        }
        .info-table .label {
            font-weight: 600;
            display: block;
            margin-bottom: 2px;
            color: #555;
            font-size: 9px;
        }
        .info-table .value {
            font-size: 9.5px;
            background-color: #f9f9f9;
            padding: 4px 5px;
            border-radius: 3px;
            border: 1px solid #eee;
            min-height: 12px;
        }
        .info-table .value2 {
            font-size: 9.5px;
            background-color: #f9f9f9;
            padding: 5px 6px;
            border-radius: 3px;
            border: 1px solid #eee;
            min-height: 35px;
        }

        /* --- TABLA FINANCIERA --- */
        .financials-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .financials-table td {
            padding: 4px 5px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
            font-size: 9.5px;
        }
        .financials-table td:last-child {
            text-align: right;
            font-weight: 600;
        }
        .financials-table .total-row td {
            font-size: 12px;
            font-weight: 700;
            color: #0056b3;
            border-top: 2px solid #ccc;
            border-bottom: none;
        }
        .financials-table .moms-row td { font-weight: normal; }

        /* --- PIE DE PÁGINA --- */
        .footer-section { padding-top: 10px; }
        .consent-text {
            font-size: 9.5px;
            color: #666;
            background-color: #f9f9f9;
            padding: 8px 10px;
            border-radius: 3px;
            border-left: 3px solid #0056b3;
            margin-bottom: 20px;
        }
        .signatures-table {
            margin-top: 20px;
            width: 100%;
        }
        .signature-box {
            border-top: 1px solid #333;
            padding-top: 6px;
            font-size: 11px;
            color: #333;
            text-align: center;
        }
    </style>
</head>
<body>
<table class="main-container">
    <tbody>
        <!-- Encabezado -->
        <tr>
            <td colspan="2" style="padding-bottom:10px;border-bottom:2px solid #e0e0e0;">
                <table style="width:100%;">
                    <tr>
                        <td style="vertical-align:top;width:160px;">
                            <div class="header-logo">
                                @if($company->logo)
                                    <img src="{{ asset('storage/'.$company->logo) }}" width="150" alt="logo-main">
                                @else
                                    <img src="{{ asset('/logos/logo_black.png') }}" width="150" alt="logo-main">
                                @endif
                            </div>
                        </td>
                        <td class="header-title-cell">
                            <h1>Inköpsavtal</h1>
                            <div class="contract-details">
                                Avtalsnummer: {{ $agreement->agreement_id}} <br>
                                Skapad:  {{ $agreement->vehicle_client->vehicle->purchase_date }}
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
                            <div class="label">Postnr. ort</div>
                            <div class="value">
                                {{ $company->postal_code }} {{ $company->street }}  
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="label">Org/person nr.</div>
                            <div class="value">
                                {{ $company->organization_number }} 
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="label">E-post</div>
                            <div class="value">
                                {{ $company->email }} 
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="label">Mobil</div>
                                <div class="value">
                                {{ $company->phone }}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="column-cell column-cell-right">
                <h2>Säljare</h2>
                <table class="info-table">
                    <tr>
                        <td>
                            <div class="label">Namn</div>
                            <div class="value">
                                {{ $agreement->agreement_client->fullname }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="label">Adress</div>
                            <div class="value">
                                {{ $agreement->agreement_client->address }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="label">Postnr. ort</div>
                            <div class="value">
                                {{ $agreement->agreement_client->postal_code }} {{ $agreement->agreement_client->street }} 
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="label">Org/person nr.</div>
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
                            <div class="label">Mobil</div>
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
                                        <div clss="label">Miltal</div>
                                        <div class="value">
                                            {{ $agreement->vehicle_client->vehicle->mileage }}
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
                                    <td colspan="2">
                                        <div class="label">Anteckningar</div>
                                        <div class="value">
                                            {{ $agreement->vehicle_client->vehicle->comments }}
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
        <!-- === FOOTER === -->
        <tr>
            <td colspan="2" class="footer-section">
                <table class="signatures-table">
                    <tr>
                        <!-- Celda Izquierda: Firma del Comprador (Köparens) -->
                        <td style="width: 50%; padding-right: 20px; vertical-align: bottom;">
                            
                            <!-- Contenedor para la imagen con altura mínima -->
                            <div style="min-height: 70px;">
                                @if(isset($signature_url))
                                    <!-- La imagen de la firma se muestra aquí si existe -->
                                    <img src="{{ $signature_url }}" alt="Firma" style="width: 200px; height: auto; display: block; margin-bottom: 5px; margin-left: auto; margin-right: auto;">
                                @endif
                            </div>
                            
                            <!-- La línea de firma, siempre visible -->
                            <div class="signature-box">(Köparens underskrift)</div>
                        </td>

                        <!-- Celda Derecha: Firma del Vendedor (Säljarens) -->
                        <td style="width: 50%; padding-left: 20px; vertical-align: bottom;">
                            
                            <!-- Contenedor VACÍO con la MISMA altura mínima para alinear -->
                            <div style="min-height: 70px;">
                                <!-- Este espacio vacío garantiza la alineación vertical -->
                            </div>
                            
                            <!-- La línea de firma, que se alineará con la de la izquierda -->
                            <div class="signature-box">(Säljarens underskrift)</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

    </tbody>
</table>
</body>
</html>