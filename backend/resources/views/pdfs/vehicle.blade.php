<!DOCTYPE html>
<html lang="es">  
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sammanställning</title>
    </head>
    <style>

        /* Using fonts from storage/fonts - synced between local and server */
        body {
            font-family: 'gelion', 'dm sans', sans-serif !important;
            background-color: #FFFFFF;
            padding: 0;
            margin: 0;
            color: #33303CAD;
            letter-spacing: 0 !important;
            word-spacing: normal !important;
        }

        table {
            border-radius: 6px !important;
            border-spacing: unset;
            font-size: 0.8rem;
            font-weight: 400;
            letter-spacing: normal;
            text-transform: none;
        }

        table thead {
            font-weight: 700;
        }

        .invoice-background {
            background-color: #F6F6F6;
        }

        .invoice-background td:first-child {
            border-top-left-radius: 6px !important;
        }

        .invoice-background td:last-child {
            border-top-right-radius: 6px !important;
        }

        .data-from {
            padding: 32px;
        }

        .m-0 {
            margin: 0;
        }

        .mt-10 {
            margin-top: 10px;
        }

        .mt-20 {
            margin-top: 20px;
        }

        .mt-auto {
            margin-top: auto;
        }

        .w-30 {
            width: 30%;
        }

        .pb-0 {
            padding-bottom: 0 !important;
        }

        .pt-8 {
            padding-top: 8px !important;
        }

       .faktura {
            font-family: 'gelion', 'dm sans', sans-serif;
            font-size: 32px;
            font-weight: 700;
            color: #454545;
            border-top: 2px solid #454545;
            border-bottom: 2px solid #454545;
            padding: 4px 0;
            display: inline-block;
            letter-spacing: 0 !important;
        }

        .table-main {
            width: 100%;
        }

        .number-invoice {
            align-items: end;
            justify-content: end;
            text-align: right;
            width: auto;
            display: flex;
            flex-direction: column;
        }

        .font-weight-medium {
            font-weight: 700;
        }

        .table-items {
            margin-top: 10px;
            border-radius: 6px !important;
            border-width: thin !important;
            border-style: solid !important;
            border-color: rgba(47,43,61, 0.16) !important;
        }

        .pr-0 {
            padding-right: 0;
        }

        .info-total table tr {
            height: 20px;
        }

        .info-total .text {
            text-align: end;
        }

        .info-total .numbers {
            width: auto !important;
            text-align: end;
        }

        .border-top {
            border-top-width: thin !important;
            border-top-style: solid !important;
            border-top-color: rgba(47,43,61, 0.16) !important;
            border-radius: 0 !important;
        }

        .info-supplier {
            display: flex;
            flex-direction: column;
            width: auto;
        }

        .table-supplier {
            font-family: 'gelion', 'dm sans', sans-serif !important;
            letter-spacing: 0 !important;
        }

        .table-supplier td {
            vertical-align: top;
        }
    </style> 
    <body>
        <table class="table-main" width="100%" cellspacing="0" cellpadding="0">
            <tbody>
                <tr>
                    <td>
                        <table width="100%" class="invoice-background">
                            <tr>
                                <td width="35%" class="data-from pb-0">
                                    <div class="d-flex align-center mb-6">
                                        @if($company->logo)
                                            <img src="{{ asset('storage/'.$company->logo) }}" width="150" alt="logo-main">
                                        @else
                                            <h1>{{ $company->company }} </h1>
                                            <div class="contract-details">
                                                {{ $company->name }} {{ $company->last_name }} <br>
                                                {{ $company->email }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td width="65%" class="data-from">
                                    <span class="m-0 faktura" style="display: flex; max-width: 250px; width: fit-content; margin-left: auto;">
                                        Sammanställning
                                    </span>          
                                </td>                                
                            </tr>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>
       
        <table width="100%" class="data-from">
            <tr>
                <td class="font-weight-medium">Reg nr</td>
                <td>{{ $vehicle->reg_num }}</td>
            </tr>
            <tr>
                <td class="font-weight-medium">Inköpspris</td>
                <td>{{ formatCurrency($vehicle->purchase_price) }} kr</td>
            </tr>
            <tr>
                <td class="font-weight-medium">VMB / Moms</td>
                <td>{{ $vehicle->iva_purchase?->name }}</td>
            </tr>
            <tr>
                <td class="font-weight-medium">InköpsDatum </td>
                <td>{{ $vehicle->purchase_date }}</td>
            </tr>
            <tr>
                <td class="font-weight-medium">Kostnad </td>
                <td>{{ formatCurrency(collect($vehicle->tasks)->sum(fn($item) => floatval($item['cost']))) }} kr</td>
            </tr>
        </table>

        <div style="position: fixed; bottom: 0; width: 100%;">
            <table width="100%" class="table-supplier border-top mt-10">
                <tr>
                    <td width="25%">
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0 mt-10">
                                Adress
                            </h4>
                            <span class="info-supplier">
                                <p class="m-0">{{ $company->address }}</p>
                                <p class="m-0">{{ $company->postal_code }}</p>
                                <p class="m-0">{{ $company->street }}</p>
                                <p class="m-0">{{ $company->phone }}</p>
                            </span>
                        </p>
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0">
                                Bolagets säte
                            </h4>
                            <span class="info-supplier">
                                <span>Stockholm, Sweden</span>
                            </span>
                        </p>
                        @if(!is_null($company->swish))
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0">
                                Swish
                            </h4>
                            <span class="info-supplier">
                                <span>{{ $company->swish }}</span>
                            </span>
                        </p>
                        @endif
                    </td>
                    <td width="25%">
                        @if(!is_null($company->organization_number))
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0 mt-10">
                                Org.nr.
                            </h4>
                            <span class="info-supplier">
                                <span>{{ $company->organization_number }}</span>
                            </span>
                        </p>
                        @endif
                        @if(!is_null($company->vat))
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0">
                                Vat
                            </h4>
                            <span class="info-supplier">
                                <span>{{ $company->vat }}</span>
                            </span>
                        </p>
                        @endif
                        @if(!is_null($company->bic))
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0">
                                BIC
                            </h4>
                            <span class="info-supplier">
                                <span>{{ $company->bic }}</span>
                            </span>
                        </p>
                        @endif
                        @if(!is_null($company->plus_spin))
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0">
                                Plusgiro
                            </h4>
                            <span class="info-supplier">
                                <span>{{ $company->plus_spin }}</span>
                            </span>
                        </p>
                        @endif
                    </td>
                    <td width="25%">
                        @if(!is_null($company->link))
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0 mt-10">
                                Webbplats
                            </h4>
                            <span class="info-supplier">
                                <span>{{ $company->link }}</span>
                            </span>
                        </p>
                        @endif
                        
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0">
                                Företagets e-post
                            </h4>
                            <span class="info-supplier">
                                <span>{{ $company->email }}</span>
                            </span>
                        </p>
                    </td>
                    <td width="25%">
                        @if(!is_null($company->bank))
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0 mt-10">
                                Bank
                            </h4>
                            <span class="info-supplier">
                                <span>{{ $company->bank }}</span>
                            </span>
                        </p>
                        @endif
                        @if(!is_null($company->iban))
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0">
                                Bankgiro
                            </h4>
                            <span class="info-supplier">
                                <span>{{ $company->iban }}</span>
                            </span>
                        </p>
                        @endif
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0">
                                Kontonummer
                            </h4>
                            <span class="info-supplier">
                                <span>{{ $company->account_number }}</span>
                            </span>
                        </p>
                        @if(!is_null($company->iban_number))
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0">
                                Iban nummer
                            </h4>
                            <span class="info-supplier">
                                <span>{{ $company->iban_number }}</span>
                            </span>
                        </p>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </body>
</html>