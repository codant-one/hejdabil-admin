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
            line-height: 0.6;
            position: relative;
        }

        .main-container {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
        }

        h2 {
            font-size: 14px;
            color: #008C91;
            margin-top: 0;
            margin-bottom: 6px;
        }

        /* --- HEADER --- */
        .header-logo {
            display: inline-block;
            text-align: right;
        }

        .header-logo img {
            height: 60px;
            width: auto;
        }

        .header-logo-cell {
            vertical-align: bottom;
            text-align: right;
        }

        .header-title-cell {
            text-align: left;
            vertical-align: bottom;
        }

        .header-title-cell h1, .header-logo-cell h1 {
            margin: 0 0 6px 0;
            font-size: 24px;
            color: #454545;
        }

        .header-title-cell .contract-details, .header-logo-cell .contract-details {
            font-size: 10px;
            color: #454545;
            line-height: 1;
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
            margin-bottom: 4px;
        }

        .info-table .value {
            font-size: 10px;
            background-color: #F6F6F6;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #E7E7E7;
            color: #5D5D5D;
        }

        .info-table .value2 {
            font-size: 9px;
            background-color: #F6F6F6;
            padding: 5px 6px;
            border-radius: 4px;
            border: 1px solid #E7E7E7;
            line-height: 1;
            min-height: 120px;
            border-left: 1px solid #008C91;
            white-space: pre-line;
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
            font-size: 12px; /* Ajustado para consistencia */
            color: #454545;
        }
    </style>
</head>
<body>

    <table class="main-container">
        <tbody>
            <!-- === HEADER === -->
            <tr>
                <td colspan="2" style="padding-bottom: 4px; border-bottom: 2px solid #E7E7E7;">
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
                                @if($company->logo)
                                    <div class="header-logo">
                                        <img src="{{ asset('storage/'.$company->logo) }}" alt="logo-main">
                                    </div>
                                @else
                                    <h1>{{ $company->company }} </h1>
                                    <div class="contract-details">
                                        {{ $company->name }} {{ $company->last_name }} <br>
                                        {{ $company->email }}
                                    </div>
                                @endif
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
                    <h2 style="margin-top: 6px;">Fordonsägare</h2>
                    <table class="info-table">
                        <tr>
                            <td colspan="2">
                                <div class="label">Namn</div>
                                <div class="value">
                                    {{ $agreement->commission->client->fullname }}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="column-cell column-cell-left-2">
                                <div class="label">Org/person nr.</div>
                                <div class="value">
                                    {{ $agreement->commission->client->organization_number }}
                                </div>
                            </td>
                            <td class="column-cell column-cell-right-2">
                                <div class="label">Telefon</div>
                                <div class="value">
                                    {{ $agreement->commission->client->phone }}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="label">E-post</div>
                                <div class="value">
                                    {{ $agreement->commission->client->email }}
                                </div>
                            </td>
                        </tr>                        
                        <tr>
                            <td colspan="2">
                                <div class="label">Adress</div>
                                <div class="value">
                                    {{ $agreement->commission->client->address }}, {{ $agreement->commission->client->postal_code }} {{ $agreement->commission->client->street }} 
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="column-cell column-cell-right section-cell">
                    <h2 style="margin-top: 6px;">Förmedlare</h2>
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
                            <td class="column-cell column-cell-left-2">
                                <div class="label">Org/person nr.</div>
                                <div class="value">
                                    {{ $company->organization_number }} 
                                </div>
                            </td>
                            <td class="column-cell column-cell-right-2">
                                <div class="label">Telefon</div>
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
                        <tr>
                            <td colspan="2">
                                <div class="label">Adress</div>
                                <div class="value">
                                    {{ $company->address }}, {{ $company->postal_code }} {{ $company->street }}  
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <!-- ====================================================== -->
            <!-- ============ PESTAÑA 2: FORDONINFORMATION ============ -->
            <!-- ====================================================== -->
            <tr>
                <td class="column-cell column-cell-left">
                    <h2>Fordonsinformation</h2>                 
                    <table class="info-table">
                        <tr>
                            <td colspan="2">
                                <div class="label">Bilnamn</div>
                                <div class="value">
                                    {{ $agreement->commission->vehicle->car_name }}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="column-cell column-cell-left-2">
                                <div class="label">Reg nr</div>
                                <div class="value">
                                    {{ $agreement->commission->vehicle->reg_num }}
                                </div>
                            </td>
                            <td class="column-cell column-cell-right-2">
                                <div class="label">Färg & Årsmodell</div>
                                <div class="value">
                                    {{ $agreement->commission->vehicle->color }} / {{ $agreement->commission->vehicle->year }}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="column-cell column-cell-left-2">
                                <div class="label">Chassinummer (VIN)</div>
                                <div class="value">
                                    {{ $agreement->commission->vehicle->chassis }}
                                </div>
                            </td>
                            <td class="column-cell column-cell-right-2">
                                <div class="label">Miltal</div>
                                <div class="value">
                                    {{ $agreement->commission->vehicle->mileage }} Mil
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="column-cell column-cell-left-2">
                                <div class="label">Drivmedel</div>
                                <div class="value">
                                    {{ $agreement->commission->vehicle->fuel?->name }}
                                </div>
                            </td>
                            <td class="column-cell column-cell-right-2">
                                <div class="label">Växellåda</div>
                                <div class="value">
                                {{ $agreement->commission->vehicle->gearbox?->name }}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="column-cell column-cell-left-2">
                                <div class="label">Kaross</div>
                                <div class="value">
                                    {{ $agreement->commission->vehicle->carbody?->name }}
                                </div>
                            </td>
                            <td class="column-cell column-cell-right-2">
                                <div class="label">Generation</div>
                                <div class="value">
                                {{ $agreement->commission->vehicle->generation }}
                                </div>
                            </td>
                        </tr>                        
                    </table>                           
                </td>
                <td class="column-cell column-cell-right">
                    <h2 style="color: white;">.</h2>                 
                    <table class="info-table">     
                        <tr>
                            <td class="column-cell column-cell-left-2">
                                <div class="label">Kontrollbesiktning gäller tom</div>
                                <div class="value">
                                    {{ $agreement->commission->vehicle->control_inspection }}
                                </div>
                            </td>
                            <td class="column-cell column-cell-right-2">
                                <div class="label">Antal nycklar till fordonet</div>
                                <div class="value">
                                {{ $agreement->commission->vehicle->number_keys }}
                                </div>
                            </td>
                        </tr>   
                        <tr>
                            <td class="column-cell column-cell-left-2">
                                <div class="label">Servicebok finns?</div>
                                <div class="value">
                                    {{ $agreement->commission->vehicle->service_book === 0 ? 'Ja' : 'Nej' }}
                                </div>
                            </td>
                            <td class="column-cell column-cell-right-2">
                                <div class="label">Sommardäck finns?</div>
                                <div class="value">
                                    {{ $agreement->commission->vehicle->summer_tire === 0 ? 'Ja' : 'Nej' }}
                                </div>
                            </td>
                        </tr>  
                        @if($agreement->commission->vehicle->last_service || $agreement->commission->vehicle->last_service_date)
                        <tr>
                            <td>
                                <div class="label">Vinterdäck finns?</div>
                                <div class="value">
                                    {{ $agreement->commission->vehicle->winter_tire === 0 ? 'Ja' : 'Nej' }}
                                </div>
                            </td>
                            <td class="column-cell column-cell-right-2">
                                <div class="label">Senaste service: Mil/datum</div>
                                <div class="value">
                                    @if($agreement->commission->vehicle->last_service || $agreement->commission->vehicle->last_service_date)
                                        {{ $agreement->commission->vehicle->last_service ?? 0 }} Mil / {{ $agreement->commission->vehicle->last_service_date ?? 'YYYY-MM-DD' }}
                                    @else
                                         
                                    @endif
                                </div>
                            </td>
                        </tr>  
                        @else
                         <tr>
                            <td colspan="2">
                                <div class="label">Vinterdäck finns?</div>
                                <div class="value">
                                    {{ $agreement->commission->vehicle->winter_tire === 0 ? 'Ja' : 'Nej' }}
                                </div>
                            </td>
                        </tr>
                        @endif
                        @if($agreement->commission->vehicle->dist_belt === 0)
                            @if($agreement->commission->vehicle->last_dist_belt || $agreement->commission->vehicle->last_dist_belt_date) 
                            <tr>
                                <td>
                                    <div class="label">Kamrem bytt?</div>
                                    <div class="value">
                                        {{ $agreement->commission->vehicle->dist_belt === 0 ? 'Ja' : 
                                        (
                                            $agreement->commission->vehicle->dist_belt === 1 ? 
                                            'Nej' : 
                                            'Vet ej'
                                        ) 
                                        }}
                                    </div>
                                </td>                            
                                <td class="column-cell column-cell-right-2">
                                    <div class="label">Kamrem bytt vid: Mil/datum</div>
                                    <div class="value">
                                        @if($agreement->commission->vehicle->last_dist_belt || $agreement->commission->vehicle->last_dist_belt_date) 
                                            {{ $agreement->commission->vehicle->last_dist_belt ?? 0 }} Mil / {{ $agreement->commission->vehicle->last_dist_belt_date ?? 'YYYY-MM-DD' }}
                                        @else
                                             
                                        @endif                                    
                                    </div>
                                </td>                            
                            </tr> 
                            @else
                            <tr>
                                <td colspan="2">
                                    <div class="label">Kamrem bytt?</div>
                                    <div class="value">
                                        {{ $agreement->commission->vehicle->dist_belt === 0 ? 'Ja' : 
                                        (
                                            $agreement->commission->vehicle->dist_belt === 1 ? 
                                            'Nej' : 
                                            'Vet ej'
                                        ) 
                                        }}
                                    </div>
                                </td>                          
                            </tr> 
                            @endif
                        @else
                        <tr>
                            <td colspan="2">
                                <div class="label">Kamrem bytt?</div>
                                <div class="value">
                                    {{ $agreement->commission->vehicle->dist_belt === 0 ? 'Ja' : 
                                      (
                                        $agreement->commission->vehicle->dist_belt === 1 ? 
                                        'Nej' : 
                                        'Vet ej'
                                      ) 
                                    }}
                                </div>
                            </td>                          
                        </tr> 
                        @endif
                        <tr>
                            <td colspan="2">
                                <div class="label">Försäljningspris</div>
                                <div class="value">
                                    {{ formatCurrency($agreement->price) }} kr
                                </div>
                            </td>                          
                        </tr> 
                    </table>                
                </td>                
            </tr>
            @if($agreement->commission->vehicle->comments)
            <tr>
                <td colspan="2">
                    <table class="info-table">
                        <tr>
                            <td>
                                <div class="label">Anteckningar</div>
                                <div class="value">
                                    {{ $agreement->commission->vehicle->comments ?? ' ' }}
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            @endif
            
            <!-- ====================================================== -->
            <!-- ============ PESTAÑA 3: FÖRMEDLINGSAVGIFT ============ -->
            <!-- ====================================================== -->
            <tr>
                <td class="column-cell column-cell-left">
                    <h2>Förmedlingsavgift & Villkor</h2>
                    <table class="info-table">
                        <tr>
                            <td class="column-cell column-cell-left-2">
                                <div class="label">Typ av provision</div>
                                <div class="value">
                                    {{ $agreement->commission->commission_type->name }}
                                </div>
                            </td>
                            <td class="column-cell column-cell-right-2">
                                <div class="label">Provisionsavgift</div>
                                <div class="value">
                                    {{ formatCurrency($agreement->commission->commission_fee) }} kr
                                </div>
                            </td>
                        </tr>
                        @if($agreement->commission->outstanding_debt === 0)
                        <tr>
                            <td colspan="2">
                                <div class="label">Restskuld</div>
                                <div class="value">
                                    {{ formatCurrency($agreement->commission->remaining_debt) }} kr
                                </div>
                            </td>
                        </tr>
                        @endif                                
                    </table>
                </td>
                <td class="column-cell column-cell-right">
                    <h2 style="color: white;">.</h2>                 
                    <table class="info-table">
                        @if($agreement->commission->outstanding_debt === 0)
                        <tr>
                            <td class="column-cell column-cell-left-2">
                                <div class="label">Restskuld finns?</div>
                                <div class="value">
                                    {{ $agreement->commission->outstanding_debt === 0 ? 'Ja' : 'Nej' }}
                                </div>
                            </td>
                            <td>
                                <div class="label">Vem betalar restskulden?</div>
                                <div class="value">
                                    {{ $agreement->commission->residual_debt === 0 ? 'Bilhandlare' : 'Kund' }}
                                </div>
                            </td>
                        </tr>
                        @else
                        <tr>
                            <td colspan="2">
                                <div class="label">Restskuld finns?</div>
                                <div class="value">
                                    {{ $agreement->commission->outstanding_debt === 0 ? 'Ja' : 'Nej' }}
                                </div>
                            </td>
                        </tr>                                    
                        @endif
                        @if($agreement->commission->outstanding_debt === 0)
                        <tr>
                            <td colspan="2">
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
                        @if($agreement->commission->payment_description!==null)
                        <tr>
                            <td class="column-cell column-cell-left-2">
                                <div class="label">Betalningsvillkor (dagar)</div>
                                <div class="value">
                                    {{ $agreement->commission->payment_days }}
                                </div>
                            </td>
                            <td class="column-cell column-cell-right-2">
                                <div class="label">Betalningsbeskrivning</div>
                                <div class="value">
                                    {{ $agreement->commission->payment_description }}
                                </div>
                            </td>
                        </tr>
                        @else
                        <tr>
                            <td colspan="2">
                                <div class="label">Betalningsvillkor (dagar)</div>
                                <div class="value">
                                    {{ $agreement->commission->payment_days }}
                                </div>
                            </td>
                        </tr>
                        @endif                        
                    </table>
                </td>
            </tr>           

            <!-- ====================================================== -->
            <!-- =============== PESTAÑA 6: TILLÄGG =================== -->
            <!-- ====================================================== -->
            <tr>
                
                <td class="column-cell column-cell-left section-cell">
                    <h2>Övriga upplysningar</h2>
                    <table class="info-table">
                        <tr>
                            <td>
                                <div class="value2">{{ $agreement->terms_other_information }}</div>
                            </td>
                        </tr>
                    </table>
                </td>               
                @if($agreement->terms_other_conditions!==null)
                <td class="column-cell column-cell-right section-cell">
                    <h2>Övriga villkor</h2>
                    <table class="info-table">
                        <tr>
                            <td>
                                <div class="value2">{{ $agreement->terms_other_conditions }}</div>
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