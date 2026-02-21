<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Prisförslag - PDF</title>
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

        /* --- PIE DE PÁGINA --- */
        .footer-section {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0px;
            padding: 0;
            background: #FFFFFF;
        }

        .notes-text {
            font-size: 10px;
            color: #5D5D5D;
            background-color: #F6F6F6;
            padding: 10px;
            border-radius: 4px;
            border-left: 1px solid #008C91;
            margin-bottom: 15px;
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
        
        .thank-you-text {
            font-size: 14px;
            font-weight: bold;
            color: #008C91;
            margin-bottom: 8px;
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
                                <h1>Prisförslag</h1>
                                <div class="contract-details">
                                    Offert nr: {{ $agreement->offer->offer_id }} <br>
                                    Datum: {{ $agreement->offer->created_at->format('Y-m-d')}}
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

            <!-- === PREPARED BY / VEHICLE INFORMATION === -->
            <tr>
                <td class="column-cell column-cell-left">
                    <h2>Förberedd av</h2>
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
                    <h2>Kundinformation</h2>
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
                    <h2>Information om fordonet</h2>                 
                    <table class="info-table">
                        <tr>
                            <td colspan="2">
                                <div class="label">Bilnamn</div>
                                <div class="value">
                                    {{ $agreement->offer->car_name }}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="column-cell column-cell-left-2">
                                <div class="label">Reg nr</div>
                                <div class="value">
                                    {{ $agreement->offer->reg_num }}
                                </div>
                            </td>
                            <td class="column-cell column-cell-right-2">
                                <div class="label">Färg & Årsmodell</div>
                                <div class="value">
                                    {{ $agreement->offer->color }} / {{ $agreement->offer->year }}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="column-cell column-cell-left-2">
                                <div class="label">Chassinummer (VIN)</div>
                                <div class="value">
                                    {{ $agreement->offer->chassis }}
                                </div>
                            </td>
                            <td class="column-cell column-cell-right-2">
                                <div class="label">Miltal</div>
                                <div class="value">
                                    {{ $agreement->offer->mileage }} Mil
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="column-cell column-cell-left-2">
                                <div class="label">Drivmedel</div>
                                <div class="value">
                                    {{ $agreement->offer->fuel?->name }}
                                </div>
                            </td>
                            <td class="column-cell column-cell-right-2">
                                <div class="label">Växellåda</div>
                                <div class="value">
                                {{ $agreement->offer->gearbox?->name }}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="column-cell column-cell-left-2">
                                <div class="label">Kaross</div>
                                <div class="value">
                                    {{ $agreement->offer->carbody?->name }}
                                </div>
                            </td>
                            <td class="column-cell column-cell-right-2">
                                <div class="label">Generation</div>
                                <div class="value">
                                {{ $agreement->offer->generation }}
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
                                    {{ $agreement->offer->control_inspection }}
                                </div>
                            </td>
                            <td class="column-cell column-cell-right-2">
                                <div class="label">Antal nycklar till fordonet</div>
                                <div class="value">
                                {{ $agreement->offer->number_keys }}
                                </div>
                            </td>
                        </tr>   
                        <tr>
                            <td class="column-cell column-cell-left-2">
                                <div class="label">Servicebok finns?</div>
                                <div class="value">
                                    {{ $agreement->offer->service_book === 0 ? 'Ja' : 'Nej' }}
                                </div>
                            </td>
                            <td class="column-cell column-cell-right-2">
                                <div class="label">Sommardäck finns?</div>
                                <div class="value">
                                    {{ $agreement->offer->summer_tire === 0 ? 'Ja' : 'Nej' }}
                                </div>
                            </td>
                        </tr>  
                        @if($agreement->offer->last_service || $agreement->offer->last_service_date)                        
                        <tr>
                            <td>
                                <div class="label">Vinterdäck finns?</div>
                                <div class="value">
                                    {{ $agreement->offer->winter_tire === 0 ? 'Ja' : 'Nej' }}
                                </div>
                            </td>
                            <td class="column-cell column-cell-right-2">
                                <div class="label">Senaste service: Mil/datum</div>
                                <div class="value">
                                    @if($agreement->offer->last_service || $agreement->offer->last_service_date)
                                        {{ $agreement->offer->last_service ?? 0 }} Mil / {{ $agreement->offer->last_service_date ?? 'YYYY-MM-DD' }}
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
                                    {{ $agreement->offer->winter_tire === 0 ? 'Ja' : 'Nej' }}
                                </div>
                            </td>
                        </tr>  
                        @endif
                        @if($agreement->offer->dist_belt === 0)
                            @if($agreement->offer->last_dist_belt || $agreement->offer->last_dist_belt_date) 
                            <tr>
                                <td>
                                    <div class="label">Kamrem bytt?</div>
                                    <div class="value">
                                        {{ $agreement->offer->dist_belt === 0 ? 'Ja' : 
                                        (
                                            $agreement->offer->dist_belt === 1 ? 
                                            'Nej' : 
                                            'Vet ej'
                                        ) 
                                        }}
                                    </div>
                                </td>                            
                                <td class="column-cell column-cell-right-2">
                                    <div class="label">Kamrem bytt vid: Mil/datum</div>
                                    <div class="value">
                                        @if($agreement->offer->last_dist_belt || $agreement->offer->last_dist_belt_date)    
                                        {{ $agreement->offer->last_dist_belt ?? 0 }} Mil / {{ $agreement->offer->last_dist_belt_date ?? 'YYYY-MM-DD' }}
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
                                        {{ $agreement->offer->dist_belt === 0 ? 'Ja' : 
                                        (
                                            $agreement->offer->dist_belt === 1 ? 
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
                                    {{ $agreement->offer->dist_belt === 0 ? 'Ja' : 
                                      (
                                        $agreement->offer->dist_belt === 1 ? 
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
                                <div class="label">Belopp kr</div>
                                <div class="value">
                                    {{ formatCurrency($agreement->offer->price) }} kr
                                </div>
                            </td>                          
                        </tr> 
                    </table>                
                </td>                
            </tr>

            <!-- === FINANCIAL OVERVIEW === -->
            <tr>
                <td colspan="2" class="section-cell">
                    <h2>Prisinformation</h2>
                    <table class="financials-table">
                        <tbody>
                            <tr>
                                <td>Pris för {{ $agreement->offer->reg_num }}</td>
                                <td>{{ formatCurrency($agreement->offer->price) }} kr</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td>SUMMA</td>
                                <td>{{ formatCurrency($agreement->offer->price) }} kr</td>
                            </tr>
                        </tfoot>
                    </table>
                </td>
            </tr>
            
            <!-- === COMMENTS & CONDITIONS === -->
            <tr>
                <td colspan="2" class="section-cell">
                    @if($agreement->offer->comment)
                        <h2>Anmärkning</h2>
                        <div class="notes-text">
                            {{ $agreement->offer->comment }}
                        </div>
                    @endif

                    @if($agreement->offer->terms_other_conditions)
                        <h2>Övriga villkor</h2>
                        <div class="notes-text">
                            {!! nl2br(e($agreement->offer->terms_other_conditions)) !!}
                        </div>
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    <!-- === FOOTER & SIGNATURE (fixed for DOMPDF) === -->
    <div class="footer-section">
        <div class="thank-you-text">TACK FÖR DIN FÖRFRÅGAN!</div>
        <table class="signatures-table">
            <tr>
                <!-- Left: Kund (köpare) -->
                <td style="padding-right: 20px; vertical-align: bottom; position: relative;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="height: 70px; vertical-align: bottom;">
                                @if(isset($signature_url))
                                    <img src="{{ $signature_url }}" alt="Firma" style="width: auto; height: 70px;">
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="signature-box">(Köparens underskrift)</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="height: 15px; font-size: 10px;">&nbsp;</td>
                        </tr>
                    </table>
                </td>

                <!-- Right: Säljföretaget -->
                <td style="padding-left: 20px; vertical-align: bottom; text-align: right;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="height: 70px; vertical-align: bottom; text-align: right;">
                                @if($company->img_signature)
                                    <img src="{{ asset('storage/' . $company->img_signature) }}" alt="Firma Förmedlaren" style="width: auto; height: 70px;">
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align: right;">
                                <div class="signature-box">(Säljföretagets underskrift)</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="height: 15px; font-size: 10px; text-align: right;">{{ $company->name }} {{ $company->last_name }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>