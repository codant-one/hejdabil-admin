<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Förmedlingsavtal - PDF</title>
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
            line-height: 0.7;
            position: relative;
        }

        h2 {
            font-size: 13px;
            color: #008C91;
            margin-top: 0;
            margin-bottom: 8px;
        }


        .main-container {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
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
            padding-top: 10px;
        }

        .column-cell {
            width: 50%;
            vertical-align: top;
            padding-top: 8px;
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
            padding: 5px;
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
            min-height: 60px;
            border-left: 1px solid #008C91;
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
                                <h1>Förmedlingsavtal</h1>
                                <div class="contract-details">
                                    Avtalsnummer: #{{ $agreement->commission->commission_id}} <br>
                                    Skapad: {{ $agreement->commission->created_at->format('Y-m-d')}}
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
                                    {{ $company->company}} 
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
                                <div class="value2">
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
                        @if(isset($signature_url))
                            <img src="{{ $signature_url }}" alt="Firma" style="width: auto; height: 70px;">
                        @endif
                    </div>
                    <div class="signature-box">(Fordonsägarens underskrift)</div>
                    
                </td>

                <!-- Celda Derecha: Firma del Vendedor (Säljarens) - CON LA FIRMA DEL CLIENTE -->
                <td style="width: 50%; padding-left: 20px; vertical-align: bottom; position: relative;">
                    <div style="min-height: 70px;">
                        @if($company->img_signature)
                            <img src="{{ asset('storage/' . $company->img_signature) }}" alt="Firma Förmedlaren" style="width: auto; height: 70px;">
                        @endif
                    </div>
                    <div class="signature-box">(Förmedlarens underskrift)</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>