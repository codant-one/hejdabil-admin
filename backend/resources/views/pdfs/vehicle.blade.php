<!DOCTYPE html>
<html lang="es">  
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sammanställning</title>
    </head>
    <style>

        @font-face {
            font-family: 'Gelion Regular';
            font-style: normal;
            font-weight: 400;
            src: url({{ storage_path('fonts/gelion-Regular.ttf') }}) format('truetype');
            font-display: swap;
        }

        body {
            background-color:#FFFFFF;
            padding: 0;
            margin: 0;
            font-family: 'Gelion Regular', Arial, sans-serif !important;
            color: #33303CAD;
            line-height: 1.5;
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
            background-color: #F2EFFF;
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
            font-size: 24px;
            color: #57F287;
            border-top: 2px solid #57F287;
            border-bottom: 2px solid #57F287;
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
                                <td width="35%" class="data-from">
                                    <div class="d-flex align-center mb-6">
                                        @if(!$vehicle->user->supplier)
                                            <img src="{{ asset('/logos/logo_black.png') }}" width="150" alt="logo-main">  
                                        @else
                                            @if($vehicle->user->supplier->logo)
                                                <img src="{{ asset('storage/'.$vehicle->user->supplier->logo) }}" width="150" alt="logo-main">
                                            @else
                                                <img src="{{ asset('/logos/logo_black.png') }}" width="150" alt="logo-main">
                                            @endif
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
                <td>{{ formatCurrency(collect($vehicle->costs)->sum(fn($item) => floatval($item['value']))) }} kr</td>
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
                            @if(!$vehicle->user->supplier)
                                <span class="info-supplier">
                                    <p class="m-0">Abrahamsbergsvägen 47</p>
                                    <p class="m-0">16830 BROMMA</p>
                                    <p>Hejdå Bil AB</p>
                                </span>
                            @else
                                <span class="info-supplier">
                                    <p class="m-0">{{ $vehicle->user->supplier->address }}</p>
                                    <p class="m-0">{{ $vehicle->user->supplier->postal_code }}</p>
                                    <p class="m-0">{{ $vehicle->user->supplier->street }}</p>
                                    <p class="m-0">{{ $vehicle->user->supplier->phone }}</p>
                                </span>
                            @endif
                        </p>
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0">
                                Bolagets säte
                            </h4>
                            <span class="info-supplier">
                                <span>Stockholm, Sweden</span>
                            </span>
                        </p>
                        @if($vehicle->user->supplier && !is_null($vehicle->user->supplier->swish))
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0">
                                Swish
                            </h4>
                            <span class="info-supplier">
                                <span>{{ $vehicle->user->supplier->swish }}</span>
                            </span>
                        </p>
                        @endif
                    </td>
                    <td width="25%">
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0 mt-10">
                                Org.nr.
                            </h4>
                            @if(!$vehicle->user->supplier)
                                <span class="info-supplier">
                                    <span>559374-0268</span>
                                </span>
                            @else
                                <span class="info-supplier">
                                    <span>{{ $vehicle->user->supplier->organization_number }}</span>
                                </span>
                            @endif
                        </p>
                        @if(($vehicle->user->supplier && !is_null($vehicle->user->supplier->vat)) || !$vehicle->user->supplier)
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0">
                                Vat
                            </h4>
                            @if(!$vehicle->user->supplier)
                                <span class="info-supplier">
                                    <span>SE559374026801</span>
                                </span>
                            @else
                                <span class="info-supplier">
                                    <span>{{ $vehicle->user->supplier->vat }}</span>
                                </span>
                            @endif
                        </p>
                        @endif
                        @if(($vehicle->user->supplier && !is_null($vehicle->user->supplier->bic)))
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0">
                                BIC
                            </h4>
                            <span class="info-supplier">
                                <span>{{ $vehicle->user->supplier->bic }}</span>
                            </span>
                        </p>
                        @endif
                        @if(($vehicle->user->supplier && !is_null($vehicle->user->supplier->plus_spin)))
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0">
                                Plusgiro
                            </h4>
                            <span class="info-supplier">
                                <span>{{ $vehicle->user->supplier->plus_spin }}</span>
                            </span>
                        </p>
                        @endif
                    </td>
                    <td width="25%">
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0 mt-10">
                                Webbplats
                            </h4>
                            @if(!$vehicle->user->supplier)
                                <span class="info-supplier">
                                    <span>www.hejdabil.se</span>
                                </span>
                            @else
                                <span class="info-supplier">
                                    <span>{{ $vehicle->user->supplier->link }}</span>
                                </span>
                            @endif
                        </p>
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0">
                                Företagets e-post
                            </h4>
                            @if(!$vehicle->user->supplier)
                                <span class="info-supplier">
                                    <span>info@hejdabil.se</span>
                                </span>
                            @else
                                <span class="info-supplier">
                                    <span>{{ $vehicle->user->supplier->user->email }}</span>
                                </span>
                            @endif
                        </p>
                    </td>
                    <td width="25%">
                        @if(($vehicle->user->supplier && !is_null($vehicle->user->supplier->bank)))
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0">
                                Bank
                            </h4>
                            <span class="info-supplier">
                                <span>{{ $vehicle->user->supplier->bank }}</span>
                            </span>
                        </p>
                        @endif
                            @if(($vehicle->user->supplier && !is_null($vehicle->user->supplier->iban)) || !$vehicle->user->supplier)
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0">
                                Bankgiro
                            </h4>
                            @if(!$vehicle->user->supplier)
                                <span class="info-supplier">
                                    <span>5886-4976</span>
                                </span>
                            @else
                                <span class="info-supplier">
                                    <span>{{ $vehicle->user->supplier->iban }}</span>
                                </span>
                            @endif
                        </p>
                        @endif
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0 mt-10">
                                Kontonummer
                            </h4>
                            @if(!$vehicle->user->supplier)
                                <span class="info-supplier">
                                    <span>9960 1821054721</span>
                                </span>
                            @else
                                <span class="info-supplier">
                                    <span>{{ $vehicle->user->supplier->account_number }}</span>
                                </span>
                            @endif
                        </p>
                        @if(($vehicle->user->supplier && !is_null($vehicle->user->supplier->iban_number)))
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0">
                                Iban nummer
                            </h4>
                            <span class="info-supplier">
                                <span>{{ $vehicle->user->supplier->iban_number }}</span>
                            </span>
                        </p>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </body>
</html>