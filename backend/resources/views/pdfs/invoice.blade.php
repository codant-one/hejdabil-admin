<!DOCTYPE html>
<html lang="es">  
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Fakturamall</title>
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
            color: #9966FF;
            border-top: 2px solid #9966FF;
            border-bottom: 2px solid #9966FF;
        }

        .table-main {
            width: 100%;
            height: calc(100% - 150px); /* Resta el espacio necesario para el footer */
            margin-bottom: 150px;
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
                                <td width="35%" class="data-from pb-0">
                                    <div class="d-flex align-center mb-6">
                                        @if(!$billing->supplier)
                                            <img src="{{ asset('/logos/logo_black.png') }}" width="150" alt="logo-main">  
                                        @else
                                            @if($billing->supplier->logo)
                                                <img src="{{ asset('storage/'.$billing->supplier->logo) }}" width="150" alt="logo-main">
                                            @else
                                                <img src="{{ asset('/logos/logo_black.png') }}" width="150" alt="logo-main">
                                            @endif
                                        @endif
                                    </div>
                                </td>
                                <td width="65%" class="data-from pb-0">
                                    <span class="m-0 faktura" style="display: flex; max-width: 250px; width: fit-content; margin-left: auto;">
                                        {{ 
                                            $billing->state_id === 9 ? 
                                            'KREDIT FAKTURA' : 
                                            ( 
                                                $billing->payment_terms === '0 dagar netto' ?
                                                'KONTANT FAKTURA' :
                                                'FAKTURA'
                                            )
                                        }}
                                    </span>
                                    <h3 class="m-0 mt-10" style="text-align: right;">
                                        {{$billing->client->fullname}}
                                    </h3> 
                                    @if($billing->reference)
                                    <div style="text-align: right;">
                                        <span width="30%">Vår referens: </span>
                                        <span>{{ $billing->reference }}</span>
                                    </div>
                                    @endif 
                                </td>
                            </tr>
                        </table>
                        <table width="100%" class="invoice-background">
                            <tr>
                                <td width="65%" class="data-from pt-8">
                                    <table width="100%" class="invoice-background">
                                        <tr class="font-weight-medium m-0 d-flex">
                                            <td width="30%">
                                                <h4 class="font-weight-medium m-0 d-flex">
                                                    Faktura nr:
                                                </h4>
                                            </td>
                                            <td>
                                                <h4 class="font-weight-medium m-0 d-flex">
                                                    {{ $billing->invoice_id }}
                                                </h4>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td width="30%">Kund nr:</td>
                                            <td>{{ $billing->client->order_id }}</td>
                                        </tr>
                                        <tr>
                                            <td width="30%">Fakturadatum:</td>
                                            <td>
                                                <span>{{ \Carbon\Carbon::parse($billing->invoice_date)->format('d/m/Y') }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td width="30%">Förfallodatum:</td>
                                            <td>
                                                <span>{{ \Carbon\Carbon::parse($billing->due_date)->format('d/m/Y') }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td width="30%">Betalningsvillkor:</td>
                                            <td>{{ $billing->payment_terms }}</td>
                                        </tr>
                                    </table>
 
                                    <p class="mt-20 m-0">Efter förfallodagen debiteras ränta enligt räntelagen.</p>           
                                </td>
                                <td width="35%" class="data-from pt-8" style="vertical-align: bottom;">
                                    <div class="mt-auto number-invoice">
                                        <h4 class="font-weight-medium m-0">
                                            Faktureringsadress
                                        </h4>
                                        <span class="number-invoice">
                                            <p class="m-0">{{ $billing->client->address }}</p>
                                            <p class="m-0">{{ $billing->client->postal_code }}</p>
                                            <p class="m-0">{{ $billing->client->street }}</p>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <!------------------------TABLE ITEMS--------------------->    
                <tr>
                    <td>
                        <table width="100%" class="table-items">
                            <thead class="invoice-background">
                                <tr>
                                    @foreach($types as $type)
                                    <td 
                                        style="
                                            width: {{$type->type_id === 1 ? '40' : (60/(count($types) - 1)) }}%; 
                                            padding-left: 10px !important;
                                            text-align: start !important;
                                            height: 40px !important;"> 
                                            {{ $type->name }}
                                        </td>
                                    @endforeach
                                </tr>
                            </thead>
                            @foreach($invoices as $rowIndex => $row)
                                <tr style="height: 40px !important;">
                                    @foreach($row as $colIndex => $column)
                                        @isset($column['id'])
                                        <td 
                                            style="
                                            padding-left: 10px !important; 
                                            text-align: start !important; 
                                            height: 40px !important; 
                                            border-top: 1px solid #D9D9D9;">
                                            <span style="{{ $column['id'] === 1 ? 'font-weight: 700;' : 'font-weight: 400;' }}">
                                            {{ ($column['id'] === 2 || $column['id'] === 3)
                                                ? formatCurrency($column['value'])
                                                : $column['value'] 
                                            }}
                                            </span>
                                        </td>
                                        @else
                                        <td 
                                            colspan="4"
                                            style="
                                            padding-left: 10px !important; 
                                            text-align: start !important; 
                                            height: 40px !important; 
                                            border-top: 1px solid #D9D9D9;">
                                            <span style="font-weight: 700;">
                                            {{ $column['note'] }}
                                            </span>
                                        </td>
                                        @endisset
                                    @endforeach
                                </tr>
                            @endforeach
                        </table>     
                    </td>
                </tr>
            </tbody>
        </table>
        <!------------------------- BILL TO---------------------------------->
        <div style="position: fixed; bottom: 0; width: 100%; padding-bottom: 20px;">
            <table width="100%" class="table-supplier">
                <tr>
                    <td width="25%"></td>
                    <td width="25%"></td>
                    <td width="25%"></td>
                    <td width="25%" class="info-total">
                        <table width="100%">
                            <tr>
                                <td class="text">Netto:</td>
                                <td class="numbers" style="text-align: right;"><span>{{ formatCurrency($billing->subtotal) }} kr</span></td>
                            </tr>
                            <tr>
                                <td class="text">Moms:</td>
                                <td class="numbers" style="text-align: right;"><span>{{ formatCurrency($billing->tax) }}%</span></td>
                            </tr>
                            <tr>
                                <td class="text">Summa att betala:</td>
                                <td class="numbers" style="text-align: right;"><span>{{ formatCurrency($billing->total) }} kr</span></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <table width="100%" class="table-supplier border-top mt-10">
                <tr>
                    <td width="25%">
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0 mt-10">
                                Adress
                            </h4>
                            @if(!$billing->supplier)
                                <span class="info-supplier">
                                    <p class="m-0">Abrahamsbergsvägen 47</p>
                                    <p class="m-0">16830 BROMMA</p>
                                    <p>Hejdå Bil AB</p>
                                </span>
                            @else
                                <span class="info-supplier">
                                    <p class="m-0">{{ $billing->supplier->address }}</p>
                                    <p class="m-0">{{ $billing->supplier->postal_code }}</p>
                                    <p class="m-0">{{ $billing->supplier->street }}</p>
                                    <p class="m-0">{{ $billing->supplier->phone }}</p>
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
                        @if($billing->supplier && !is_null($billing->supplier->swish))
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0">
                                Swish
                            </h4>
                            <span class="info-supplier">
                                <span>{{ $billing->supplier->swish }}</span>
                            </span>
                        </p>
                        @endif
                    </td>
                    <td width="25%">
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0 mt-10">
                                Org.nr.
                            </h4>
                            @if(!$billing->supplier)
                                <span class="info-supplier">
                                    <span>559374-0268</span>
                                </span>
                            @else
                                <span class="info-supplier">
                                    <span>{{ $billing->supplier->organization_number }}</span>
                                </span>
                            @endif
                        </p>
                        @if(($billing->supplier && !is_null($billing->supplier->vat)) || !$billing->supplier)
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0">
                                Vat
                            </h4>
                            @if(!$billing->supplier)
                                <span class="info-supplier">
                                    <span>SE559374026801</span>
                                </span>
                            @else
                                <span class="info-supplier">
                                    <span>{{ $billing->supplier->vat }}</span>
                                </span>
                            @endif
                        </p>
                        @endif
                        @if(($billing->supplier && !is_null($billing->supplier->bic)))
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0">
                                BIC
                            </h4>
                            <span class="info-supplier">
                                <span>{{ $billing->supplier->bic }}</span>
                            </span>
                        </p>
                        @endif
                        @if(($billing->supplier && !is_null($billing->supplier->plus_spin)))
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0">
                                Plusgiro
                            </h4>
                            <span class="info-supplier">
                                <span>{{ $billing->supplier->plus_spin }}</span>
                            </span>
                        </p>
                        @endif
                    </td>
                    <td width="25%">
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0 mt-10">
                                Webbplats
                            </h4>
                            @if(!$billing->supplier)
                                <span class="info-supplier">
                                    <span>www.hejdabil.se</span>
                                </span>
                            @else
                                <span class="info-supplier">
                                    <span>{{ $billing->supplier->link }}</span>
                                </span>
                            @endif
                        </p>
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0">
                                Företagets e-post
                            </h4>
                            @if(!$billing->supplier)
                                <span class="info-supplier">
                                    <span>info@hejdabil.se</span>
                                </span>
                            @else
                                <span class="info-supplier">
                                    <span>{{ $billing->supplier->user->email }}</span>
                                </span>
                            @endif
                        </p>
                    </td>
                    <td width="25%">
                        @if(($billing->supplier && !is_null($billing->supplier->bank)))
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0">
                                Bank
                            </h4>
                            <span class="info-supplier">
                                <span>{{ $billing->supplier->bank }}</span>
                            </span>
                        </p>
                        @endif
                         @if(($billing->supplier && !is_null($billing->supplier->iban)) || !$billing->supplier)
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0">
                                Bankgiro
                            </h4>
                            @if(!$billing->supplier)
                                <span class="info-supplier">
                                    <span>5886-4976</span>
                                </span>
                            @else
                                <span class="info-supplier">
                                    <span>{{ $billing->supplier->iban }}</span>
                                </span>
                            @endif
                        </p>
                        @endif
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0 mt-10">
                                Kontonummer
                            </h4>
                            @if(!$billing->supplier)
                                <span class="info-supplier">
                                    <span>9960 1821054721</span>
                                </span>
                            @else
                                <span class="info-supplier">
                                    <span>{{ $billing->supplier->account_number }}</span>
                                </span>
                            @endif
                        </p>
                        @if(($billing->supplier && !is_null($billing->supplier->iban_number)))
                        <p class="m-0 info-supplier">
                            <h4 class="font-weight-medium m-0">
                                Iban nummer
                            </h4>
                            <span class="info-supplier">
                                <span>{{ $billing->supplier->iban_number }}</span>
                            </span>
                        </p>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </body>
</html>