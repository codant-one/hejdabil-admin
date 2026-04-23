<!DOCTYPE html>
<html lang="sv">
<head>
    @include('pdfs.shared.fonts')
    <meta charset="UTF-8">
    <title>Inköpsavtal - PDF</title>
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
            padding: 15px 50px;
            border-spacing: 6px;
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
            font-size: 10px;
            line-height: 0.8;
            color: #878787;
            background-color: #E7E7E7;
            padding: 4px;
            border-radius: 4px 8px 8px 4px;
            min-height: 115px;
            white-space: pre-line;
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
            font-weight: 700;
            font-size: 16px;
            color: {{ $company->primary_color ?? '#008C91' }} !important;
            border-top: 2px solid #E7E7E7;
            border-bottom: none;
            padding-top: 8px;
            padding-bottom: 8px;
        }

        .financials-table .moms-row td { 
            font-weight: normal; 
            padding-top: 0;
        }

        /* --- PIE DE PÁGINA --- */
        .footer-section {
            position: absolute;
            left: 43px;
            right: 43px;
            bottom: 10px;
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
            table-layout: fixed;
            border-spacing: 14px;
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

        .pb-0 {
            padding-bottom: 0 !important;
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
                                <h1>Inköpsavtal</h1>
                                <div class="contract-details">
                                    Avtalsnummer: {{ $agreement->agreement_id}} <br>
                                    Skapad:  {{ $agreement->vehicle_client->vehicle->purchase_date }}
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
            <!-- === SELLER / BUYER INFORMATION === -->
            <tr>
                <td class="column-cell column-cell-left card-classic">
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
                <td class="column-cell column-cell-right card-classic">
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
                            <td class="column-cell column-cell-left-2">
                                <div class="label">Org/person nr.</div>
                                <div class="value">
                                    {{ $agreement->agreement_client->organization_number }}
                                </div>
                            </td>
                            <td class="column-cell column-cell-right-2">
                                <div class="label">Telefon</div>
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
                        <tr>
                            <td colspan="2">
                                <div class="label">Adress</div>
                                <div class="value">
                                    {{ $agreement->agreement_client->address }}, {{ $agreement->agreement_client->postal_code }} {{ $agreement->agreement_client->street }} 
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <!-- === VEHICLE INFORMATION === -->
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
                                                {{ $agreement->vehicle_client->vehicle->car_name }}
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
                                            <div class="label">Färg & Årsmodell</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->color }} 
                                                @if($agreement->vehicle_client->vehicle->color && $agreement->vehicle_client->vehicle->year)
                                                    / 
                                                @endif
                                                {{ $agreement->vehicle_client->vehicle->year }}
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="column-cell column-cell-left-2">
                                            <div class="label">Chassinummer (VIN)</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->chassis }}
                                            </div>
                                        </td>
                                        <td class="column-cell column-cell-right-2">
                                            <div class="label">Miltal</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->mileage }} Mil
                                            </div>
                                        </td>
                                    </tr>
                                    @if($agreement->vehicle_client->vehicle->fuel && $agreement->vehicle_client->vehicle->gearbox)
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
                                    @elseif($agreement->vehicle_client->vehicle->fuel)
                                    <tr>
                                        <td colspan="2">
                                            <div class="label">Drivmedel</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->fuel?->name }}
                                            </div>
                                        </td>
                                    </tr>
                                    @elseif($agreement->vehicle_client->vehicle->gearbox)
                                    <tr>
                                        <td colspan="2">
                                            <div class="label">Växellåda</div>
                                            <div class="value">
                                            {{ $agreement->vehicle_client->vehicle->gearbox?->name }}
                                            </div>
                                        </td>
                                    </tr>
                                    @endif
                                    @if($agreement->vehicle_client->vehicle->generation)
                                    <tr>
                                        <td class="column-cell column-cell-left-2">
                                            <div class="label">Kaross</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->carbody?->name }}
                                            </div>
                                        </td>
                                        <td class="column-cell column-cell-right-2">
                                            <div class="label">Generation</div>
                                            <div class="value">
                                            {{ $agreement->vehicle_client->vehicle->generation }}
                                            </div>
                                        </td>
                                    </tr>
                                    @else
                                    <tr>
                                        <td colspan="2">
                                            <div class="label">Kaross</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->carbody?->name }}
                                            </div>
                                        </td>
                                    </tr>
                                    @endif
                                    @if($agreement->vehicle_client->vehicle->control_inspection && $agreement->vehicle_client->vehicle->number_keys)
                                    <tr>
                                        <td class="column-cell column-cell-left-2">
                                            <div class="label">Kontrollbesiktning gäller tom</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->control_inspection }}
                                            </div>
                                        </td>
                                        <td class="column-cell column-cell-right-2">
                                            <div class="label">Antal nycklar till fordonet</div>
                                            <div class="value">
                                            {{ $agreement->vehicle_client->vehicle->number_keys }}
                                            </div>
                                        </td>
                                    </tr>   
                                    @elseif($agreement->vehicle_client->vehicle->control_inspection)
                                    <tr>
                                        <td colspan="2">
                                            <div class="label">Kontrollbesiktning gäller tom</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->control_inspection }}
                                            </div>
                                        </td>
                                    </tr> 
                                    @elseif($agreement->vehicle_client->vehicle->number_keys)
                                    <tr>
                                        <td colspan="2">
                                            <div class="label">Antal nycklar till fordonet</div>
                                            <div class="value">
                                            {{ $agreement->vehicle_client->vehicle->number_keys }}
                                            </div>
                                        </td>
                                    </tr> 
                                    @endif
                                    <tr>
                                        <td class="column-cell column-cell-left-2">
                                            <div class="label">Servicebok finns?</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->service_book === 0 ? 'Ja' : 'Nej' }}
                                            </div>
                                        </td>
                                        <td class="column-cell column-cell-right-2">
                                            <div class="label">Sommardäck finns?</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->summer_tire === 0 ? 'Ja' : 'Nej' }}
                                            </div>
                                        </td>
                                    </tr>
                                </table>                           
                            </td>
                            <td class="column-cell column-cell-right">
                                <div style="height: 17px;"></div>                    
                                <table class="info-table">  
                                    @if($agreement->vehicle_client->vehicle->last_service || $agreement->vehicle_client->vehicle->last_service_date)                       
                                    <tr>
                                        <td class="column-cell column-cell-left-2">
                                            <div class="label">Vinterdäck finns?</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->winter_tire === 0 ? 'Ja' : 'Nej' }}
                                            </div>
                                        </td>
                                        <td class="column-cell column-cell-right-2">
                                            <div class="label">Senaste service: Mil/datum</div>
                                            <div class="value">
                                                @if($agreement->vehicle_client->vehicle->last_service || $agreement->vehicle_client->vehicle->last_service_date)
                                                    {{ $agreement->vehicle_client->vehicle->last_service ?? 0 }} Mil / {{ $agreement->vehicle_client->vehicle->last_service_date ?? 'YYYY-MM-DD' }}
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
                                                {{ $agreement->vehicle_client->vehicle->winter_tire === 0 ? 'Ja' : 'Nej' }}
                                            </div>
                                        </td>
                                    </tr>
                                    @endif
                                    @if($agreement->vehicle_client->vehicle->dist_belt === 0)
                                        @if($agreement->vehicle_client->vehicle->last_dist_belt || $agreement->vehicle_client->vehicle->last_dist_belt_date)
                                        <tr>
                                            <td class="column-cell column-cell-left-2">
                                                <div class="label">Kamrem bytt?</div>
                                                <div class="value">
                                                    {{ ['Ja', 'Nej', 'Kamkedja', 'Vet ej'][(int) $agreement->vehicle_client->vehicle->dist_belt] ?? 'Vet ej' }}
                                                </div>
                                            </td>                            
                                            <td class="column-cell column-cell-right-2">
                                                <div class="label">Kamrem bytt vid: Mil/datum</div>
                                                <div class="value">
                                                    @if($agreement->vehicle_client->vehicle->last_dist_belt || $agreement->vehicle_client->vehicle->last_dist_belt_date)
                                                    {{ $agreement->vehicle_client->vehicle->last_dist_belt ?? 0 }} Mil / {{ $agreement->vehicle_client->vehicle->last_dist_belt_date ?? 'YYYY-MM-DD' }}
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
                                                    {{ ['Ja', 'Nej', 'Kamkedja', 'Vet ej'][(int) $agreement->vehicle_client->vehicle->dist_belt] ?? 'Vet ej' }}
                                                </div>
                                            </td>                           
                                        </tr>
                                        @endif
                                    @else
                                    <tr>
                                        <td colspan="2">
                                            <div class="label">Kamrem bytt?</div>
                                            <div class="value">
                                                {{ ['Ja', 'Nej', 'Kamkedja', 'Vet ej'][(int) $agreement->vehicle_client->vehicle->dist_belt] ?? 'Vet ej' }}
                                            </div>
                                        </td>                          
                                    </tr>
                                    @endif 
                                    @if($agreement->vehicle_client->vehicle->comments)  
                                    <tr>
                                        <td colspan="2">
                                            <div class="label">Anteckningar</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->comments ?? ' ' }}
                                            </div>
                                        </td>                  
                                    </tr>     
                                    @endif                          
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
                                                {{ formatCurrency($agreement->iva_purchase_amount) }} kr
                                            </div>
                                        </td>
                                    </tr>
                                    @if($agreement->vehicle_client->vehicle->payment->is_loan === 0)
                                    <tr>
                                        <td class="column-cell column-cell-left-2">
                                            <div class="label">Restskulden löses av</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->payment->settled_by === 0 ? 'Bilhandlare' : 'Kund' }} 
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
                                    @if(!is_null($agreement->payment_type_id))
                                    <tr>
                                        <td colspan="2">
                                            <div class="label">Typ av utbetalning till säljaren</div>
                                            <div class="value">
                                                @if($agreement->vehicle_client->vehicle->payment->payment_types)
                                                    {{ $agreement->vehicle_client->vehicle->payment->payment_types->name }}
                                                @else   
                                                    En annan..
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @elseif($agreement->payment_type)
                                    <tr>
                                        <td class="column-cell column-cell-left-2">
                                            <div class="label">Typ av utbetalning till säljaren</div>
                                            <div class="value">
                                                @if($agreement->vehicle_client->vehicle->payment->payment_types)
                                                    {{ $agreement->vehicle_client->vehicle->payment->payment_types->name }}
                                                @else   
                                                    En annan..
                                                @endif
                                            </div>
                                        </td>
                                        <td class="column-cell column-cell-right-2">
                                            <div class="label">Betalsätt</div>
                                            <div class="value">
                                                {{ $agreement->payment_type }}
                                            </div>
                                        </td>
                                    </tr>
                                    @endif
                                    @if($agreement->vehicle_client->vehicle->payment->bank && $agreement->vehicle_client->vehicle->payment->account)
                                    <tr>
                                        <td class="column-cell column-cell-left-2">
                                            <div class="label">Namn på banken</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->payment->bank }}
                                            </div>                                
                                        </td>
                                        <td class="column-cell column-cell-right-2">
                                            <div class="label">Clearing/kontonummer</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->payment->account }}
                                            </div>
                                        </td>
                                    </tr>
                                    @elseif($agreement->vehicle_client->vehicle->payment->bank)
                                    <tr>
                                        <td colspan="2">
                                            <div class="label">Namn på banken</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->payment->bank }}
                                            </div>                                
                                        </td>
                                    </tr>
                                    @elseif($agreement->vehicle_client->vehicle->payment->account)
                                    <tr>
                                        <td colspan="2">
                                            <div class="label">Clearing/kontonummer</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->payment->account }}
                                            </div>                                
                                        </td>
                                    </tr>
                                    @endif
                                    @if($agreement->vehicle_client->vehicle->payment->description)
                                    <tr>
                                        <td colspan="2">
                                            <div class="label">Betalningsbeskrivning</div>
                                            <div class="value">
                                                {{ $agreement->vehicle_client->vehicle->payment->description }}
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
                        <tbody>
                            <tr>
                                <td>Bilens begärda pris</td>
                                <td>{{ formatCurrency($agreement->vehicle_client->vehicle->purchase_price) }} kr</td>
                            </tr>
                            <tr>
                                <td>Kreditbelopp</td>
                                <td>
                                    @if($agreement->vehicle_client->vehicle->payment->loan_amount !== null && $agreement->vehicle_client->vehicle->payment->loan_amount > 0)
                                        - {{ formatCurrency($agreement->vehicle_client->vehicle->payment->loan_amount) }} kr
                                    @else
                                        {{ formatCurrency($agreement->vehicle_client->vehicle->payment->loan_amount) }} kr
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td>Restsumma</td>
                                <td> {{ formatCurrency($agreement->vehicle_client->vehicle->payment->remaining_amount) }} kr</td>
                            </tr>
                            <tr class="moms-row">
                                <td>Varav moms (25%)</td>
                                <td>{{ formatCurrency($agreement->iva_purchase_amount) }} kr</td>
                            </tr>
                        </tfoot>
                    </table>
                    <p style="font-size:10px;margin-top:4px;">Utbetalning sker {{ $agreement->vehicle_client->vehicle->purchase_date }}</p>
                </td>
            </tr>
            @if($agreement->terms_other_conditions || $agreement->terms_other_information)
            <tr>
                <td class="column-cell-left">
                    <h2>Villkor</h2>
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
                <td class="column-cell-right">
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
            </tr>
            @endif
        </tbody>
    </table>
    <!-- === FOOTER === -->
    <div class="footer-section">
        <table class="signatures-table" style="width: 100%;">
            <tr>
                <!-- Celda Izquierda: Firma del Comprador (Köparens) - VACÍA -->
                <td style="vertical-align: bottom; position: relative;" class="card-footer">
                    <div style="min-height: 100px; text-align: center;">
                        @if($company->img_signature)
                            <img src="{{ asset('storage/' . $company->img_signature) }}" alt="Firma Förmedlaren" style="width: auto; height: 100px;">
                        @endif
                    </div>
                    <div class="signature-box">(Köparens underskrift)</div>
                </td>

                <!-- Celda Derecha: Firma del Vendedor (Säljarens) - CON LA FIRMA DEL CLIENTE -->
                <td style="vertical-align: bottom; position: relative;" class="card-footer">
                    <div style="min-height: 100px; text-align: center;">
                        @if(isset($signature_url))
                            <img src="{{ $signature_url }}" alt="Firma" style="width: auto; height: 100px;">
                        @endif
                    </div>
                    <div class="signature-box">(Säljarens underskrift)</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
