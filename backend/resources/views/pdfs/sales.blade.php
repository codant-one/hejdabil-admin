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
            font-size: 14px;
            color: #008C91;
            margin-top: 10px;
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
            font-size: 10px;
            background-color: #F6F6F6;
            padding: 5px 6px;
            border-radius: 4px;
            border: 1px solid #E7E7E7;
            line-height: 1;
            min-height: 120px;
            border-left: 1px solid #008C91;
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
            font-size: 12px;
            font-weight: 700;
            color: #008C91;
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
            left: 0;
            right: 0;
            bottom: 0px;
            padding: 0;
            background: #FFFFFF;
        }

        .consent-text {
            font-size: 8px;
            background-color: #F6F6F6;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #E7E7E7;
            border-left: 1px solid #008C91;
            margin-bottom: 0;
            line-height: 0.8;
        }

        .signatures-table {
            width: 100%;
            margin-top: 6px;
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

        .page-break {
            page-break-before: always;
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
                                <h1>Försäljningsavtal</h1>
                                <div class="contract-details">
                                    Avtalsnummer: {{ $agreement->agreement_id}} <br>
                                    {{ strtoupper($company->address) }}, {{ strtoupper($company->postal_code) }} {{ strtoupper($company->street) }} 
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
                <td class="column-cell column-cell-left">
                    <h2>Säljarinformation</h2>
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
                <td class="column-cell column-cell-right">
                    <h2>Köparinformation</h2>
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
                        @if($agreement->vehicle_client->vehicle->control_inspection)
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
                        @else
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
                        @if($agreement->vehicle_interchange)
                            @if($agreement->vehicle_client->vehicle->last_service !== null)
                            <tr>
                                <td>
                                    <div class="label">Vinterdäck finns?</div>
                                    <div class="value">
                                        {{ $agreement->vehicle_client->vehicle->winter_tire === 0 ? 'Ja' : 'Nej' }}
                                    </div>
                                </td>
                                <td class="column-cell column-cell-right-2">
                                    <div class="label">Senaste service: Mil/datum</div>
                                    <div class="value">
                                        @if($agreement->vehicle_client->vehicle->last_service !== null)
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
                                @if($agreement->vehicle_client->vehicle->last_dist_belt !== null)
                                <tr>
                                    <td>
                                        <div class="label">Kamrem bytt?</div>
                                        <div class="value">
                                            {{ $agreement->vehicle_client->vehicle->dist_belt === 0 ? 'Ja' : 
                                            (
                                                $agreement->vehicle_client->vehicle->dist_belt === 1 ? 
                                                'Nej' : 
                                                'Vet ej'
                                            ) 
                                            }}
                                        </div>
                                    </td>                            
                                    <td class="column-cell column-cell-right-2">
                                        <div class="label">Kamrem bytt vid: Mil/datum</div>
                                        <div class="value">
                                            @if($agreement->vehicle_client->vehicle->last_dist_belt !== null)
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
                                            {{ $agreement->vehicle_client->vehicle->dist_belt === 0 ? 'Ja' : 
                                            (
                                                $agreement->vehicle_client->vehicle->dist_belt === 1 ? 
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
                                        {{ $agreement->vehicle_client->vehicle->dist_belt === 0 ? 'Ja' : 
                                        (
                                            $agreement->vehicle_client->vehicle->dist_belt === 1 ? 
                                            'Nej' : 
                                            'Vet ej'
                                        ) 
                                        }}
                                    </div>
                                </td>                           
                            </tr> 
                            @endif          
                            @if($agreement->guaranty === 1)
                            <tr>                                        
                                <td class="column-cell column-cell-left-2">
                                    <div class="label">Garanti</div>
                                    <div class="value">
                                        {{ $agreement->guaranty_description }}
                                    </div>
                                </td>
                                <td class="column-cell column-cell-right-2">
                                    <div class="label">Typ av garanti</div>
                                    <div class="value">
                                        {{ $agreement->guaranty_type->name}}
                                    </div>
                                </td>                                        
                            </tr>
                            @endif
                            @if($agreement->insurance_company === 1)
                            <tr>
                                <td class="column-cell column-cell-left-2">
                                    <div class="label">Försäkring</div>
                                    <div class="value">
                                        {{ $agreement->insurance_company_description }} 
                                    </div>
                                </td>
                                <td class="column-cell column-cell-right-2">
                                    <div class="label">Försäkringstyp</div>
                                    <div class="value">
                                        {{ $agreement->insurance_type->name}}
                                    </div>
                                </td>
                            </tr>
                            @endif
                            <tr>
                                <td colspan="2">
                                    <div class="label">Pris</div>
                                    <div class="value">
                                        {{ formatCurrency($agreement->price) }} kr
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <div class="label">Försäljningsdatum</div>
                                    <div class="value">
                                        {{ $agreement->vehicle_client->vehicle->sale_date }}
                                    </div>
                                </td>
                            </tr>
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
                        @endif
                    </table>
                </td>
                
                <td class="column-cell column-cell-right">                     
                    @if($agreement->vehicle_interchange)
                        <h2>Inbytesfordon</h2>   
                    @else
                        <h2 style="color: white;">.</h2>  
                    @endif                 
                    <table class="info-table">
                        @if($agreement->vehicle_interchange)
                            <tr>
                                <td colspan="2">
                                    <div class="label">Bilnamn</div>
                                    <div class="value">
                                        {{ $agreement->vehicle_interchange?->car_name ?? ' ' }}
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="column-cell column-cell-left-2">
                                    <div class="label">Reg nr</div>
                                    <div class="value">
                                        {{ $agreement->vehicle_interchange?->reg_num ?? ' '}}
                                    </div>
                                </td>
                                <td class="column-cell column-cell-right-2">
                                    <div class="label">Färg & Årsmodell</div>
                                    <div class="value">
                                        {{ $agreement->vehicle_interchange?->color }} 
                                        @if($agreement->vehicle_interchange?->color && $agreement->vehicle_interchange?->year)
                                            / 
                                        @endif
                                        {{ $agreement->vehicle_interchange?->year ?? ' ' }}
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="column-cell column-cell-left-2">
                                    <div class="label">Chassinummer (VIN)</div>
                                    <div class="value">
                                        {{ $agreement->vehicle_interchange?->chassis ?? ' ' }}
                                    </div>
                                </td>
                                <td class="column-cell column-cell-right-2">
                                    <div class="label">Miltal</div>
                                    <div class="value">
                                        @if($agreement->vehicle_interchange?->mileage !== null)
                                            {{ $agreement->vehicle_interchange->mileage }} Mil
                                        @else
                                             
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @if($agreement->vehicle_interchange?->fuel && $agreement->vehicle_interchange?->gearbox)
                            <tr>
                                <td class="column-cell column-cell-left-2">
                                    <div class="label">Drivmedel</div>
                                    <div class="value">
                                        {{ $agreement->vehicle_interchange?->fuel?->name ?? ' ' }}
                                    </div>
                                </td>
                                <td class="column-cell column-cell-right-2">
                                    <div class="label">Växellåda</div>
                                    <div class="value">
                                    {{ $agreement->vehicle_interchange?->gearbox?->name ?? ' '}}
                                    </div>
                                </td>
                            </tr>
                            @elseif($agreement->vehicle_interchange?->vehicle?->fuel)
                            <tr>
                                <td colspan="2">
                                    <div class="label">Drivmedel</div>
                                    <div class="value">
                                        {{ $agreement->vehicle_interchange?->vehicle?->fuel?->name ?? ' ' }}
                                    </div>
                                </td>
                            </tr>
                            @elseif($agreement->vehicle_interchange?->vehicle?->gearbox)
                            <tr>
                                <td colspan="2">
                                    <div class="label">Växellåda</div>
                                    <div class="value">
                                    {{ $agreement->vehicle_interchange?->vehicle?->gearbox?->name ?? ' ' }}
                                    </div>
                                </td>
                            </tr>
                            @endif
                            @if($agreement->vehicle_interchange?->generation)
                            <tr>
                                <td class="column-cell column-cell-left-2">
                                    <div class="label">Kaross</div>
                                    <div class="value">
                                        {{ $agreement->vehicle_interchange?->carbody?->name ?? ' ' }}
                                    </div>
                                </td>
                                <td class="column-cell column-cell-right-2">
                                    <div class="label">Generation</div>
                                    <div class="value">
                                    {{ $agreement->vehicle_interchange?->generation ?? ' ' }}
                                    </div>
                                </td>
                            </tr>
                            @else
                            <tr>
                                <td colspan="2">
                                    <div class="label">Kaross</div>
                                    <div class="value">
                                        {{ $agreement->vehicle_interchange?->carbody?->name ?? ' ' }}
                                    </div>
                                </td>
                            </tr>
                            @endif
                            @if($agreement->vehicle_interchange?->control_inspection)
                            <tr>
                                <td class="column-cell column-cell-left-2">
                                    <div class="label">Kontrollbesiktning gäller tom</div>
                                    <div class="value">
                                        {{ $agreement->vehicle_interchange?->control_inspection ?? ' ' }}
                                    </div>
                                </td>
                                <td class="column-cell column-cell-right-2">
                                    <div class="label">Antal nycklar till fordonet</div>
                                    <div class="value">
                                    {{ $agreement->vehicle_interchange?->number_keys ?? ' '}}
                                    </div>
                                </td>
                            </tr>
                            @else
                            <tr>
                                <td colspan="2">
                                    <div class="label">Antal nycklar till fordonet</div>
                                    <div class="value">
                                    {{ $agreement->vehicle_interchange?->number_keys ?? ' '}}
                                    </div>
                                </td>
                            </tr>
                            @endif
                            <tr>
                                <td class="column-cell column-cell-left-2">
                                    <div class="label">Servicebok finns?</div>
                                    <div class="value">
                                        @if($agreement->vehicle_interchange)
                                            {{ $agreement->vehicle_interchange?->service_book === 0 ? 'Ja' : 'Nej' }}
                                        @else
                                             
                                        @endif
                                    </div>
                                </td>
                                <td class="column-cell column-cell-right-2">
                                    <div class="label">Sommardäck finns?</div>
                                    <div class="value">
                                        @if($agreement->vehicle_interchange)  
                                            {{ $agreement->vehicle_interchange?->summer_tire === 0 ? 'Ja' : 'Nej' }}
                                        @else
                                              
                                        @endif
                                    </div>
                                </td>
                            </tr>    
                            @if($agreement->vehicle_interchange && $agreement->vehicle_interchange->last_service !== null)
                            <tr>
                                <td>
                                    <div class="label">Vinterdäck finns?</div>
                                    <div class="value">
                                        @if($agreement->vehicle_interchange)
                                            {{ $agreement->vehicle_interchange?->winter_tire === 0 ? 'Ja' : 'Nej' }}
                                        @else
                                             
                                        @endif
                                    </div>
                                </td>
                                <td class="column-cell column-cell-right-2">
                                    <div class="label">Senaste service: Mil/datum</div>
                                    <div class="value">
                                        @if($agreement->vehicle_interchange && $agreement->vehicle_interchange->last_service !== null)
                                            {{ $agreement->vehicle_interchange?->last_service ?? 0 }} Mil / {{ $agreement->vehicle_interchange?->last_service_date ?? 'YYYY-MM-DD' }}
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
                                        @if($agreement->vehicle_interchange)
                                            {{ $agreement->vehicle_interchange?->winter_tire === 0 ? 'Ja' : 'Nej' }}
                                        @else
                                             
                                        @endif
                                    </div>
                                </td>
                            </tr> 
                            @endif
                            @if($agreement->vehicle_interchange?->dist_belt === 0)
                                @if($agreement->vehicle_interchange?->last_dist_belt !== null)
                                <tr>
                                    <td>
                                        <div class="label">Kamrem bytt?</div>
                                        <div class="value">
                                            {{ $agreement->vehicle_interchange?->dist_belt === 0 ? 'Ja' : 
                                            (
                                                $agreement->vehicle_interchange?->dist_belt === 1 ? 
                                                'Nej' : 
                                                'Vet ej'
                                            ) 
                                            }}
                                        </div>
                                    </td>                            
                                    <td class="column-cell column-cell-right-2">
                                        <div class="label">Kamrem bytt vid: Mil/datum</div>
                                        <div class="value">
                                            @if($agreement->vehicle_interchange?->last_dist_belt !== null)
                                            {{ $agreement->vehicle_interchange?->last_dist_belt ?? 0 }} Mil / {{ $agreement->vehicle_interchange?->last_dist_belt_date ?? 'YYYY-MM-DD' }}
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
                                            {{ $agreement->vehicle_interchange?->dist_belt === 0 ? 'Ja' : 
                                            (
                                                $agreement->vehicle_interchange?->dist_belt === 1 ? 
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
                                        @if($agreement->vehicle_interchange)
                                            {{ $agreement->vehicle_interchange?->dist_belt === 0 ? 'Ja' : 
                                            (
                                                $agreement->vehicle_interchange?->dist_belt === 1 ? 
                                                'Nej' : 
                                                'Vet ej'
                                            ) 
                                            }}
                                        @else
                                             
                                        @endif
                                    </div>
                                </td>                           
                            </tr> 
                            @endif         
                            <tr>
                                <td class="column-cell column-cell-left-2">
                                    <div class="label">Inbytespris</div>
                                    <div class="value">
                                        @if($agreement->vehicle_interchange)
                                        {{ formatCurrency($agreement->vehicle_interchange?->purchase_price) }} kr
                                        @else
                                             
                                        @endif  
                                    </div>
                                </td>
                                <td class="column-cell column-cell-right-2">
                                    <div class="label">Avdragbar moms</div>
                                    <div class="value">
                                        {{ $agreement->vehicle_interchange?->iva_purchase?->name ?? ' ' }}
                                    </div>
                                </td>
                            </tr>
                            @if($agreement->residual_debt === 1)
                            <tr>
                                <td colspan="2">
                                    <div class="label">Restskuld</div>
                                    <div class="value">
                                        {{ formatCurrency($agreement->residual_price) }} kr
                                    </div>
                                </td>
                            </tr>
                            @endif
                            @if($agreement->vehicle_interchange)
                            <tr>
                                <td colspan="2">
                                    <div class="label">Verkligt värde</div>
                                    <div class="value">
                                        @if($agreement->vehicle_interchange)
                                        {{ formatCurrency($agreement->fair_value) }} kr
                                        @else
                                             
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endif                            
                            <tr>
                                <td colspan="2">
                                    <div class="label">Inköpsdatum</div>
                                    <div class="value">
                                        {{ $agreement->vehicle_interchange?->created_at?->format('Y-m-d') ?? ' ' }}
                                    </div>
                                </td>
                            </tr>
                            @if($agreement->vehicle_interchange?->comments)
                            <tr>
                                <td colspan="2">
                                    <div class="label">Anteckningar</div>
                                    <div class="value">
                                        {{ $agreement->vehicle_interchange?->comments ?? ' ' }}
                                    </div>
                                </td>
                            </tr>
                            @endif
                        @else
                            @if($agreement->vehicle_client->vehicle->last_service !== null)
                            <tr>
                                <td>
                                    <div class="label">Vinterdäck finns?</div>
                                    <div class="value">
                                        {{ $agreement->vehicle_client->vehicle->winter_tire === 0 ? 'Ja' : 'Nej' }}
                                    </div>
                                </td>
                                <td class="column-cell column-cell-right-2">
                                    <div class="label">Senaste service: Mil/datum</div>
                                    <div class="value">
                                        @if($agreement->vehicle_client->vehicle->last_service !== null)
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
                                @if($agreement->vehicle_client->vehicle->last_dist_belt !== null)
                                <tr>
                                    <td>
                                        <div class="label">Kamrem bytt?</div>
                                        <div class="value">
                                            {{ $agreement->vehicle_client->vehicle->dist_belt === 0 ? 'Ja' : 
                                            (
                                                $agreement->vehicle_client->vehicle->dist_belt === 1 ? 
                                                'Nej' : 
                                                'Vet ej'
                                            ) 
                                            }}
                                        </div>
                                    </td>                            
                                    <td class="column-cell column-cell-right-2">
                                        <div class="label">Kamrem bytt vid: Mil/datum</div>
                                        <div class="value">
                                            @if($agreement->vehicle_client->vehicle->last_dist_belt !== null)
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
                                            {{ $agreement->vehicle_client->vehicle->dist_belt === 0 ? 'Ja' : 
                                            (
                                                $agreement->vehicle_client->vehicle->dist_belt === 1 ? 
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
                                        {{ $agreement->vehicle_client->vehicle->dist_belt === 0 ? 'Ja' : 
                                        (
                                            $agreement->vehicle_client->vehicle->dist_belt === 1 ? 
                                            'Nej' : 
                                            'Vet ej'
                                        ) 
                                        }}
                                    </div>
                                </td>                           
                            </tr> 
                            @endif          
                            @if($agreement->guaranty === 1)
                            <tr>                                        
                                <td class="column-cell column-cell-left-2">
                                    <div class="label">Garanti</div>
                                    <div class="value">
                                        {{ $agreement->guaranty_description }}
                                    </div>
                                </td>
                                <td class="column-cell column-cell-right-2">
                                    <div class="label">Typ av garanti</div>
                                    <div class="value">
                                        {{ $agreement->guaranty_type->name}}
                                    </div>
                                </td>                                        
                            </tr>
                            @endif
                            @if($agreement->insurance_company === 1)
                            <tr>
                                <td class="column-cell column-cell-left-2">
                                    <div class="label">Försäkring</div>
                                    <div class="value">
                                        {{ $agreement->insurance_company_description }} 
                                    </div>
                                </td>
                                <td class="column-cell column-cell-right-2">
                                    <div class="label">Försäkringstyp</div>
                                    <div class="value">
                                        {{ $agreement->insurance_type->name}}
                                    </div>
                                </td>
                            </tr>
                            @endif
                            <tr>
                                <td colspan="2">
                                    <div class="label">Pris</div>
                                    <div class="value">
                                        {{ formatCurrency($agreement->price) }} kr
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <div class="label">Försäljningsdatum</div>
                                    <div class="value">
                                        {{ $agreement->vehicle_client->vehicle->sale_date }}
                                    </div>
                                </td>
                            </tr>
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
                        @endif
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
                                    @if($agreement->vehicle_interchange && $agreement->vehicle_interchange->purchase_price !== null && $agreement->vehicle_interchange->purchase_price > 0)
                                    <td>- {{ formatCurrency($agreement->vehicle_interchange->purchase_price) }} kr</td>
                                    @else
                                    <td>{{ formatCurrency($agreement->vehicle_interchange?->purchase_price ?? 0) }} kr</td>
                                    @endif
                                @endif
                            </tr>
                            <tr>
                                <td>Kontant / Handpenning</td>
                                @if($agreement->payment_received !== null && $agreement->payment_received > 0)
                                <td>- {{ formatCurrency($agreement->payment_received) }} kr</td>
                                @else
                                <td>{{ formatCurrency($agreement->payment_received ?? 0) }} kr</td>
                                @endif
                            </tr>
                            <tr>
                                <td>Avgår rabatt</td>
                                @if($agreement->discount !== null && $agreement->discount > 0)
                                <td>- {{ formatCurrency($agreement->discount) }} kr</td>
                                @else
                                <td>{{ formatCurrency($agreement->discount ?? 0) }} kr</td>
                                @endif
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
            <tr @if(is_null($agreement->vehicle_interchange)) class="page-break" @endif>
                <td colspan="2" class="section-cell">
                    <h2>Betalningsinformation</h2>                    
                </td>
            </tr>
            <tr>
                <td class="column-cell column-cell-left">                    
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
                <td class="column-cell column-cell-right">         
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
                                <div class="value2">{{ $agreement->terms_other_conditions }}</div>
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
                                <div class="value2">{{ $agreement->terms_other_information }}</div>
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
                    Köparen samtycker till att personuppgifter behandlas och lagras för att uppfylla rättsliga skyldigheter avseende räkenskapsinformation enligt bokföringslagen (2010:1514), 2 kap. 8 § första stycket 8 b.
                </p>
            </div>
            <table class="signatures-table" style="width: 100%;">
                <tr>
                    <!-- Celda Izquierda: Firma del Comprador (Köparens) - CON LA FIRMA DEL CLIENTE -->
                    <td style="width: 50%; padding-right: 20px; vertical-align: bottom; position: relative;">
                        <div style="min-height: 70px;">
                            @if(isset($signature_url))  
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