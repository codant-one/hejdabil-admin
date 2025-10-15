<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Förmedlingsavtal</title>
    <style>
        /* Estilos optimizados para PDF de UNA SOLA PÁGINA con layout de TABLAS */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

        @page {
            margin: 1cm;
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            font-size: 9px;
            color: #333;
        }

        h1 {
            margin: 0;
            font-size: 16px;
            color: #111;
        }

        h2 {
            font-size: 10px;
            color: #0056b3;
            margin: 0 0 4px 0;
            padding-bottom: 2px;
            border-bottom: 1px solid #ccc;
        }

        .main-container {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
        }

        .section-cell {
            padding-top: 6px;
        }

        /* Cambiamos a 4 columnas para compactar */
        .column-cell {
            width: 25%;
            vertical-align: top;
            padding-top: 8px;
        }
        .column-cell-left { padding-right: 10px; }
        .column-cell-right { padding-left: 10px; }

        .column-cell-left-2 { padding-right: 8px !important; }
        .column-cell-right-2 { padding-left: 8px !important; }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 0;
            padding-bottom: 3px;
            vertical-align: top;
        }

        .label {
            font-weight: 600;
            font-size: 8px;
            margin-bottom: 2px;
            color: #555;
        }

        .value {
            font-size: 9px;
            padding: 3px 4px;
            background-color: #f9f9f9;
            border: 1px solid #eee;
            border-radius: 2px;
            line-height: 1.2;
            min-height: 12px;
        }

        .value-large {
            min-height: auto;
        }

        .footer-section {
            padding-top: 10px;
        }

        .signatures-table {
            margin-top: 20px;
            width: 100%;
        }
        
        .signature-box {
            border-top: 1px solid #333;
            padding-top: 4px;
            font-size: 9px;
            color: #333;
            text-align: center;
        }

        .header-logo img {
            max-width: 140px;
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
    </style>
</head>
<body>

<table class="main-container">
    <tbody>
        <!-- ====================================================== -->
        <!-- ======================= HEADER ======================= -->
        <!-- ====================================================== -->
        <tr>
            <td colspan="2" style="padding-bottom:15px; border-bottom:2px solid #e0e0e0;">
                <table style="width:100%;">
                    <tr>
                        <td style="vertical-align:top; width:160px;">
                            <div class="header-logo">
                                @if($company->logo)
                                    <img src="{{ asset('storage/'.$company->logo) }}" width="150" alt="logo-main">
                                @else
                                    <img src="{{ asset('/logos/logo_black.png') }}" width="150" alt="logo-main">
                                @endif
                            </div>
                        </td>
                        <td class="header-title-cell">
                            <h1>Förmedlingsavtal</h1>
                            <div class="contract-details">
                                Avtalsnummer: #{{ $agreement->commission->commission_id}} <br>
                                Skapad: {{ $agreement->commission->created_at->format('Y-m-d')}}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- ====================================================== -->
        <!-- ====== PESTAÑA 1: KUND (FORDONSÄGARE & FÖRMEDLARE) ====== -->
        <!-- ====================================================== -->
        <tr>
            <td class="column-cell column-cell-left section-cell">
                <h2>Fordonsägare</h2>
                <table class="info-table">
                    <tr>
                        <td>
                            <div class="label">Namn</div>
                            <div class="value">
                                {{ $agreement->commission->client->fullname }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="label">Org/personummer</div>
                            <div class="value">
                                {{ $agreement->commission->client->organization_number }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="label">Adress</div>
                            <div class="value">
                                {{ $agreement->commission->client->address }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0;">
                            <table class="info-table">
                                <tr>
                                    <td class="column-cell column-cell-left-2">
                                        <div class="label">Postnummer / Ort</div>
                                        <div class="value">
                                            {{ $agreement->commission->client->postal_code }}  {{ $agreement->commission->client->street }}
                                        </div>
                                    </td>
                                    <td class="column-cell column-cell-right-2">
                                        <div class="label">Telefon</div>
                                        <div class="value">
                                            {{ $agreement->commission->client->phone }}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Fila combinada para E-post y Tipo de cliente -->
                    <tr>
                        <td style="padding:0;">
                            <table class="info-table">
                                <tr>
                                    <td class="column-cell column-cell-left-2">
                                        <div class="label">E-post</div>
                                        <div class="value">
                                            {{ $agreement->commission->client->email }}
                                        </div>
                                    </td>
                                    <td class="column-cell column-cell-right-2">
                                        <div class="label">Fordonsägaren är:</div>
                                        <div class="value">
                                            {{ $agreement->commission->client->client_type->name }}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="column-cell column-cell-right section-cell">
                <h2>Förmedlare</h2>
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
                            <div class="label">Org/personummer</div>
                            <div class="value">
                                {{ $company->organization_number }} 
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
                        <td style="padding:0;">
                            <table class="info-table">
                                <tr>
                                    <td class="column-cell column-cell-left-2">
                                        <div class="label">Postnummer / Ort</div>
                                        <div class="value">
                                            {{ $company->postal_code }} {{ $company->street }} 
                                        </div>
                                    </td>
                                    <td class="column-cell column-cell-right-2">
                                        <div class="label">Telefon</div>
                                        <div class="value">
                                            {{ $company->phone }} 
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Fila combinada para E-post y Bilfirma -->
                    <tr>
                        <td style="padding:0;">
                             <table class="info-table">
                                <tr>
                                    <td class="column-cell column-cell-left-2">
                                        <div class="label">E-post</div>
                                        <div class="value">
                                            {{ $company->email }} 
                                        </div>
                                    </td>
                                    <td class="column-cell column-cell-right-2">
                                        <div class="label">Bilfirma</div>
                                        <div class="value">
                                            {{ $company->company }} 
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- ====================================================== -->
        <!-- ============ PESTAÑA 2: FORDONINFORMATION ============ -->
        <!-- ====================================================== -->
        <tr>
            <td colspan="2" class="section-cell">
                <h2>Fordonsinformation</h2>
                <table style="width:100%; border-spacing:0;">
                    <tr>
                        <td class="column-cell column-cell-left" style="padding-right: 8px;">
                            <table class="info-table">
                                <tr>
                                    <td style="padding:0;">
                                        <table class="info-table">
                                            <tr>
                                                <td class="column-cell column-cell-left-2">
                                                    <div class="label">Märke</div>
                                                    <div class="value">
                                                        {{ $agreement->commission->vehicle->model->brand->name }}
                                                    </div>
                                                </td>
                                                <td class="column-cell column-cell-right-2">
                                                    <div class="label">Modell</div>
                                                    <div class="value">
                                                        {{ $agreement->commission->vehicle->model->name }}
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0;">
                                        <table class="info-table">
                                            <tr>
                                                <td class="column-cell column-cell-left-2">
                                                    <div class="label">Årsmodell</div>
                                                    <div class="value">
                                                        {{ $agreement->commission->vehicle->year }}
                                                    </div>
                                                </td>
                                                <td class="column-cell column-cell-right-2">
                                                    <div class="label">Farg</div>
                                                    <div class="value">
                                                        {{ $agreement->commission->vehicle->color }}
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="label">Antal nycklar</div>
                                        <div class="value">
                                            {{ $agreement->commission->vehicle->number_keys }}
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="label">Försäljningspris</div>
                                        <div class="value">
                                            {{ formatCurrency($agreement->price) }} kr
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td class="column-cell column-cell-right" style="padding-left: 8px;">
                            <table class="info-table">
                                <tr>
                                    <td style="padding:0;">
                                        <table class="info-table">
                                            <tr>
                                                <td class="column-cell column-cell-left-2">
                                                    <div class="label">Chassinummer</div>
                                                    <div class="value">
                                                        {{ $agreement->commission->vehicle->chassis }}
                                                    </div>
                                                </td>
                                                <td class="column-cell column-cell-right-2">
                                                    <div class="label">Miltal</div>
                                                    <div class="value">
                                                        {{ $agreement->commission->vehicle->mileage }}
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0;">
                                        <table class="info-table">
                                            <tr>
                                                <td class="column-cell column-cell-left-2">
                                                    <div class="label">Växellåda</div>
                                                    <div class="value">
                                                        {{ $agreement->commission->vehicle->gearbox?->name }}
                                                    </div>
                                                </td>
                                                <td class="column-cell column-cell-right-2">
                                                    <div class="label">Drivmedel</div>
                                                    <div class="value">
                                                        {{ $agreement->commission->vehicle->fuel?->name }}
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0;">
                                        <table class="info-table">
                                            <tr>
                                                <td class="column-cell column-cell-left-2">
                                                    <div class="label">Servicebok finns?</div>
                                                    <div class="value">
                                                        {{ $agreement->commission->vehicle->service_book === 0 ? 'Ja' : 'Nej' }}
                                                    </div>
                                                </td>
                                                <td class="column-cell column-cell-right-2">
                                                    <div class="label">Vinterdäck finns?</div>
                                                    <div class="value">
                                                        {{ $agreement->commission->vehicle->winter_tire === 0 ? 'Ja' : 'Nej' }}
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="label">Sommardäck finns?</div>
                                        <div class="value">
                                            {{ $agreement->commission->vehicle->summer_tire === 0 ? 'Ja' : 'Nej' }}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @if($agreement->commission->vehicle->comments!==null)
                    <tr>
                        <td colspan="2">
                            <table class="info-table">
                                <tr>
                                    <td>
                                        <div class="label">Kända fel, brister och övrig information</div>
                                        <div class="value">
                                            {{ $agreement->commission->vehicle->comments }}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
        
        <!-- ====================================================== -->
        <!-- ============ PESTAÑA 3: FÖRMEDLINGSAVGIFT ============ -->
        <!-- ====================================================== -->
        <tr>
            <td colspan="2" class="section-cell">
                <h2>Förmedlingsavgift & Villkor</h2>
                <table style="width:100%; border-spacing:0;">
                    <tr>
                        <td class="column-cell column-cell-left" style="padding-right: 8px;">
                            <table class="info-table">
                                <tr>
                                    <td>
                                        <div class="label">Typ av provision</div>
                                        <div class="value">
                                            {{ $agreement->commission->commission_type->name }}
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="label">Restskuld finns?</div>
                                        <div class="value">
                                            {{ $agreement->commission->outstanding_debt === 0 ? 'Ja' : 'Nej' }}
                                        </div>
                                    </td>
                                </tr>
                                @if($agreement->commission->outstanding_debt === 0)
                                <tr>
                                    <td>
                                        <div class="label">Vem betalar restskulden?</div>
                                        <div class="value">
                                            {{ $agreement->commission->residual_debt === 0 ? 'Bilhandlare' : 'Kund' }}
                                        </div>
                                    </td>
                                </tr>
                                @endif
                            </table>
                        </td>
                        <td class="column-cell column-cell-right" style="padding-left: 8px;">
                            <table class="info-table">
                                <tr>
                                    <td>
                                        <div class="label">Provisionsavgift</div>
                                        <div class="value">
                                            {{ formatCurrency($agreement->commission->commission_fee) }} kr
                                        </div>
                                    </td>
                                </tr>
                                @if($agreement->commission->outstanding_debt === 0)
                                <tr>
                                    <td>
                                        <div class="label">Restskuld</div>
                                        <div class="value">
                                            {{ formatCurrency($agreement->commission->remaining_debt) }} kr
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="label">Belopp att betala till bank</div>
                                        <div class="value">
                                            {{ $agreement->commission->paid_bank }}
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

        <!-- ====================================================== -->
        <!-- PESTAÑA 4 & 5: BETALNINGSINFORMATION & FÖRMEDLINGSDATUM -->
        <!-- ====================================================== -->
        <tr>
            <td class="column-cell column-cell-left section-cell">
                <h2>Betalningsinformation</h2>
                <table class="info-table">
                    <tr>
                        <td>
                            <div class="label">Bank</div>
                            <div class="value">
                                {{ $company->bank }} 
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="label">Konto nr</div>
                            <div class="value">
                                {{ $company->account_number }} 
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="column-cell column-cell-right section-cell">
                <h2>Förmedlingsdatum</h2>
                <table class="info-table">
                    <tr>
                        <td style="padding:0;">
                            <table class="info-table">
                                <tr>
                                    <td class="column-cell column-cell-left-2">
                                        <div class="label">Startdatum</div>
                                        <div class="value">
                                            {{ $agreement->commission->start_date }}
                                        </div>
                                    </td>
                                    <td class="column-cell column-cell-right-2">
                                        <div class="label">Slutdatum</div>
                                        <div class="value">
                                            {{ $agreement->commission->end_date }}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="label">Betalningsvillkor (dagar)</div>
                            <div class="value">
                                {{ $agreement->commission->payment_days }}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        @if($agreement->commission->payment_description!==null)
         <tr>
            <td colspan="2">
                <table class="info-table">
                    <tr>
                        <td>
                            <div class="label">Betalningsbeskrivning</div>
                            <div class="value">
                                {{ $agreement->commission->payment_description }}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        @endif

        <!-- ====================================================== -->
        <!-- =============== PESTAÑA 6: TILLÄGG =================== -->
        <!-- ====================================================== -->
        <tr>
            @if($agreement->terms_other_conditions!==null)
            <td class="column-cell column-cell-left section-cell">
                <h2>Övriga villkor</h2>
                <table class="info-table">
                    <tr>
                        <td>
                            <div class="value value-large">
                                {{ $agreement->terms_other_conditions }}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            @endif
            <td class="column-cell column-cell-right section-cell">
                <h2>Övriga upplysningar</h2>
                <table class="info-table">
                    <tr>
                        <td>
                            <div class="value value-large">
                            {{ $agreement->terms_other_information }}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </tbody>
</table>
@if(isset($signature_url) && isset($signature_x) && isset($signature_y))
    <div style="position: absolute; left: {{ $signature_x }}%; top: {{ $signature_y }}%; z-index: 100;">
        <img src="{{ $signature_url }}" alt="Firma" style="width: 150px; height: auto;">
    </div>
@endif
<table class="signatures-table" style="width: 100%;">
    <tr>
        <!-- CELDA DE LA IZQUIERDA (SIMPLIFICADA) -->
        <td style="width: 50%; padding-right: 20px; vertical-align: bottom; position: relative;">
            <div style="min-height: 70px;">
                <!-- El espacio para la firma está visualmente aquí, pero la imagen se superpone desde arriba -->
                @if(isset($signature_url) && !isset($signature_x))   
                    <img src="{{ $signature_url }}" alt="Firma" style="width: auto; height: 70px;">
                @endif
            </div>
            <div class="signature-box" style="border-top: 1px solid #ccc; padding-top: 5px;">
                (Fordonsägarens underskrift)
            </div>
        </td>

        <!-- CELDA DE LA DERECHA (SIMPLIFICADA) -->
        <td style="width: 50%; padding-left: 20px; vertical-align: bottom;">
            <div style="min-height: 70px;">
                @if($company->img_signature)
                    <img src="{{ asset('storage/' . $company->img_signature) }}" alt="Firma Förmedlaren" style="width: auto; height: 70px;">
                @endif
            </div>
            <div class="signature-box" style="border-top: 1px solid #ccc; padding-top: 5px;">
                (Förmedlarens underskrift)
            </div>
        </td>
    </tr>
</table>
</body>
</html>