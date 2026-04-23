<!DOCTYPE html>
<html lang="sv">
<head>
    @include('pdfs.shared.fonts')
    <meta charset="UTF-8">
    <title>Förmedlingsavtal - PDF</title>
    <style>
        @page {
            margin: 0;
        }

        html, body {
            height: 100%;
        }

        body {
            font-family: 'DM Sans', Arial, sans-serif !important;
            font-size: 10px;
            color: #454545;
            background-color: {{ $company->secondary_color ?? '#F6F6F6' }};
            padding: 0;
            margin: 0;
            color: #454545;
            letter-spacing: 0 !important;
            word-spacing: normal !important;
            line-height: 0.5;
        }

        .page-background-top {
            position: fixed;
            top: -50px;
            right: -50px;
            left: -50px;
            bottom: auto;
            height: 470px;
            background-color: {{ $company->primary_color ?? '#E7E7E7' }};
            z-index: -2;
        }

        .page-background-bottom {
            position: fixed;
            top: 470px;
            right: -50px;
            bottom: -50px;
            left: -50px;
            background-color: {{ $company->secondary_color ?? '#F6F6F6' }};
            z-index: -2;
        }
        
        .main-container {
            position: relative;
            z-index: 1;
            padding: 15px 60px;
            border-spacing: 4px;
        }

        .table-spacing {
            width: 100%; 
            border-spacing: 0; 
            border-collapse: collapse;
        }

        .card-classic {
            background-color: #FFFFFF;
            border-radius: 8px !important;
            border: 1px solid #E7E7E7 !important;
            padding: 8px 8px 4px 8px !important;
        }

        .card-header {
            color: #FFFFFF;
        }

        h2 {
            font-size: 14px;
            color: {{ $company->primary_color ?? '#008C91' }} !important;
            margin-top: 0;
            margin-bottom: 6px;
        }

        /* --- HEADER --- */
        .header-logo {
            display: inline-block;
            text-align: right;

            width: 130px;
            height: 50px;
            background: white;
            border-radius: 8px;
            position: relative;
            padding: 8px;
        }

        .header-logo img {
            display: block;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-width: 125px;
            max-height: 45px;
        }

        .header-logo-cell {
            vertical-align: top;
            text-align: right;
        }

        .header-title-cell {
            text-align: left;
            vertical-align: top;
        }

        .header-title-cell h1, .header-logo-cell h1 {
            margin: 0 0 6px 0;
            font-size: 24px;
            color: #FFFFFF;
        }

        .header-title-cell .contract-details, .header-logo-cell .contract-details {
            font-size: 10px;
            color: #FFFFFF;
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
            border-radius: 6px;
            border: 1px solid #E7E7E7;
            color: #5D5D5D;
        }

        .info-table .value2-box {
            background-color: {{ $company->primary_color ?? '#008C91' }};
            border-radius: 8px;
            padding-left: 4px;
            line-height: 0;
            font-size: 0;
        }

        .info-table .value2-text {
            font-size: 8px;
            line-height: 8px;
            color: #878787;
            background-color: #E7E7E7;
            padding: 5px 6px;
            border-radius: 4px 8px 8px 4px;
            min-height: 120px;
            white-space: pre-line;
        }

        /* --- PIE DE PÁGINA --- */
        .footer-section {
            position: absolute;
            left: 58px;
            right: 58px;
            bottom: 8px;
            padding: 0;
            background-color: {{ $company->secondary_color ?? '#F6F6F6' }};
        }

        .card-footer {
            background-color: #FFFFFF;
            border-radius: 8px !important;
            padding: 8px !important;
        }

        .signatures-table {
            width: 100%;
            margin-top: 12px;
            table-layout: fixed;
            border-spacing: 8px;
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
    <div class="page-background-top"></div>
    <div class="page-background-bottom"></div>
    <table class="main-container">
        <tbody>
            <!-- === HEADER === -->
            <tr>
                <td colspan="2" class="card-header">
                    <table class="table-spacing">
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
                <td class="column-cell column-cell-left section-cell card-classic">
                    <h2>Fordonsägare</h2>
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
                <td class="column-cell column-cell-right section-cell card-classic">
                    <h2>Förmedlare</h2>
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
                <td colspan="2" class="card-classic">
                    <table class="table-spacing">
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
                                                {{ $agreement->commission->vehicle->color }} 
                                                @if($agreement->commission->vehicle->color && $agreement->commission->vehicle->year)
                                                    / 
                                                @endif
                                                {{ $agreement->commission->vehicle->year }}
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
                                    @if($agreement->commission->vehicle->fuel && $agreement->commission->vehicle->gearbox)
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
                                    @elseif($agreement->commission->vehicle->fuel)
                                    <tr>
                                        <td colspan="2">
                                            <div class="label">Drivmedel</div>
                                            <div class="value">
                                                {{ $agreement->commission->vehicle->fuel?->name }}
                                            </div>
                                        </td>
                                    </tr>
                                    @elseif($agreement->commission->vehicle->gearbox)
                                    <tr>
                                        <td colspan="2">
                                            <div class="label">Växellåda</div>
                                            <div class="value">
                                            {{ $agreement->commission->vehicle->gearbox?->name }}
                                            </div>
                                        </td>
                                    </tr>
                                    @endif
                                    @if($agreement->commission->vehicle->generation)
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
                                    @else
                                    <tr>
                                        <td colspan="2">
                                            <div class="label">Kaross</div>
                                            <div class="value">
                                                {{ $agreement->commission->vehicle->carbody?->name }}
                                            </div>
                                        </td>
                                    </tr> 
                                    @endif                     
                                </table>                           
                            </td>
                            <td class="column-cell column-cell-right">
                                <div style="height: 17.5px;"></div>                       
                                <table class="info-table">    
                                    @if($agreement->commission->vehicle->control_inspection && $agreement->commission->vehicle->number_keys)
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
                                    @elseif($agreement->commission->vehicle->control_inspection)
                                    <tr>
                                        <td colspan="2">
                                            <div class="label">Kontrollbesiktning gäller tom</div>
                                            <div class="value">
                                                {{ $agreement->commission->vehicle->control_inspection }}
                                            </div>
                                        </td>
                                    </tr> 
                                    @elseif($agreement->commission->vehicle->number_keys)
                                    <tr>
                                        <td colspan="2">
                                            <div class="label">Antal nycklar till fordonet</div>
                                            <div class="value">
                                            {{ $agreement->commission->vehicle->number_keys }}
                                            </div>
                                        </td>
                                    </tr> 
                                    @endif
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
                                        <td class="column-cell column-cell-left-2">
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
                                            <td class="column-cell column-cell-left-2">
                                                <div class="label">Kamrem bytt?</div>
                                                <div class="value">
                                                    {{ ['Ja', 'Nej', 'Kamkedja', 'Vet ej'][(int) $agreement->commission->vehicle->dist_belt] ?? 'Vet ej' }}
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
                                                    {{ ['Ja', 'Nej', 'Kamkedja', 'Vet ej'][(int) $agreement->commission->vehicle->dist_belt] ?? 'Vet ej' }}
                                                </div>
                                            </td>                          
                                        </tr> 
                                        @endif
                                    @else
                                    <tr>
                                        <td colspan="2">
                                            <div class="label">Kamrem bytt?</div>
                                            <div class="value">
                                                {{ ['Ja', 'Nej', 'Kamkedja', 'Vet ej'][(int) $agreement->commission->vehicle->dist_belt] ?? 'Vet ej' }}
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
                    </table>
                </td>             
            </tr>
            
            <!-- ====================================================== -->
            <!-- ============ PESTAÑA 3: FÖRMEDLINGSAVGIFT ============ -->
            <!-- ====================================================== -->
            <tr>
                <td colspan="2" class="card-classic">
                    <table class="table-spacing">
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
                    <div style="height: 17.5px;"></div>                   
                    <table class="info-table">
                        @if($agreement->commission->outstanding_debt === 0)
                        <tr>
                            <td class="column-cell column-cell-left-2">
                                <div class="label">Restskuld finns?</div>
                                <div class="value">
                                    {{ $agreement->commission->outstanding_debt === 0 ? 'Ja' : 'Nej' }}
                                </div>
                            </td>
                            <td class="column-cell column-cell-right-2">
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
                </table>
                </td>
            </tr>

            <!-- ====================================================== -->
            <!-- PESTAÑA 4 & 5: BETALNINGSINFORMATION & FÖRMEDLINGSDATUM -->
            <!-- ====================================================== -->
            <tr>
                <td class="column-cell column-cell-left section-cell card-classic">
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
                <td class="column-cell column-cell-right section-cell card-classic">
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
                                <div class="value2-box">
                                    <div class="value2-text">{{ $agreement->terms_other_information }}</div>
                                </div>
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
                                <div class="value2-box">
                                    <div class="value2-text">{{ $agreement->terms_other_conditions }}</div>
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
        <table class="signatures-table" style="width: 100%;">
            <tr>
                <!-- Celda Izquierda: Firma del Comprador (Köparens) - VACÍA -->
                <td style="vertical-align: bottom; position: relative;" class="card-footer">
                    <div style="min-height: 75px; text-align: center;">
                        @if(isset($signature_url))
                            <img src="{{ $signature_url }}" alt="Firma" style="width: auto; height: 75px;">
                        @endif
                    </div>
                    <div class="signature-box">(Fordonsägarens underskrift)</div>
                    
                </td>

                <!-- Celda Derecha: Firma del Vendedor (Säljarens) - CON LA FIRMA DEL CLIENTE -->
                <td style="vertical-align: bottom;" class="card-footer">
                    <div style="min-height: 75px; text-align: center;">
                        @if($company->img_signature)
                            <img src="{{ asset('storage/' . $company->img_signature) }}" alt="Firma Förmedlaren" style="width: auto; height: 75px;">
                        @endif
                    </div>
                    <div class="signature-box">(Förmedlarens underskrift)</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
