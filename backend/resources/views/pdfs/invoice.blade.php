<!DOCTYPE html>
<html lang="es">  
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Fakturamall</title>
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
            border-radius: 16px !important;
            border-spacing: unset;
            font-size: 0.8rem;
            font-weight: 400;
        }

        table thead {
            font-weight: 700;
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

        .table-items {
            font-family: 'gelion', 'dm sans', sans-serif !important;
            margin-top: 10px;
            border-radius: 8px !important;
            border-width: thin !important;
            border-style: solid !important;
            border-color: rgba(47,43,61, 0.16) !important;
            letter-spacing: 0 !important;
        }

        .table-supplier {
            font-family: 'gelion', 'dm sans', sans-serif !important;
            letter-spacing: 0 !important;
        }

        .invoice-background {
            background-color: #F6F6F6;
        }

        .table-background-top {
            border-bottom-left-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
        }

        .table-background-bottom {
            border-top-left-radius: 0 !important;
            border-top-right-radius: 0 !important;
        }

        .invoice-background td:first-child {
            border-top-left-radius: 8px !important;
        }

        .invoice-background td:last-child {
            border-top-right-radius: 8px !important;
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
                        <table width="100%" class="invoice-background table-background-top">
                            <tr>
                                <td width="35%" class="data-from pb-0">
                                    <div class="d-flex align-center mb-6">
                                        @if($company->logo)
                                            <img src="{{ asset('storage/'.$company->logo) }}" width="150" alt="logo-main">
                                        @else
                                            <img src="{{ asset('/logos/logo_black.png') }}" width="150" alt="logo-main">
                                        @endif
                                    </div>
                                </td>
                                <td width="65%" class="data-from pb-0">
                                    <div style="text-align: right;">
                                        <span class="m-0 faktura">
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
                                    </div>
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
                        <table width="100%" class="invoice-background table-background-bottom">
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
                                                <span>{{ $billing->invoice_date }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td width="30%">Förfallodatum:</td>
                                            <td>
                                                <span>{{ $billing->due_date }}</span>
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
                                    @foreach($types as $key => $type)
                                    <td 
                                        style="
                                            text-align: {{ $key === 0 ? 'start' : 'right' }}!important;
                                            width: {{$type->type_id === 1 ? '40' : '15' }}%; 
                                            {{ $key === 0 ? 'padding-left' : 'padding-right' }}: 10px !important;
                                            height: 40px !important;"> 
                                        {{ $type->name }}
                                    </td>
                                    @endforeach
                                    @if($billing->rabatt)
                                    <td 
                                        style="
                                            text-align: right!important;
                                            width: 15%; 
                                            padding-right: 10px !important;
                                            height: 40px !important;"> 
                                        Rabbat
                                    </td>
                                    @endif
                                </tr>
                            </thead>
                            @foreach($invoices as $rowIndex => $row)
                                <tr style="height: 40px !important;">
                                    @foreach($row as $colIndex => $column)
                                        @isset($column['id'])
                                            @if($column['id'] < 5)
                                            <td 
                                                style="
                                                text-align: {{ $column['id'] === 1 ? ' start' : 'right' }}!important;
                                                {{ $column['id'] === 1 ? 'padding-left' : 'padding-right' }}: 10px !important;
                                                height: 40px !important; 
                                                border-top: 1px solid #D9D9D9;">
                                                <span style="{{ $column['id'] === 1 ? 'font-weight: 700;' : 'font-weight: 400;' }}">
                                                {{ ($column['id'] === 2 || $column['id'] === 3)
                                                    ? formatCurrency($column['value'])
                                                    : $column['value'] 
                                                }}
                                                </span>
                                            </td>
                                            @elseif($column['id'] === 5 && $billing->rabatt)
                                            <td 
                                                style="
                                                text-align: right!important;
                                                padding-right: 10px!important;
                                                height: 40px !important; 
                                                border-top: 1px solid #D9D9D9;">
                                                <span style="font-weight: 400;">
                                                {{ formatCurrency($column['value'])}} %
                                                </span>
                                            </td>
                                            @endif
                                        @else
                                        <td 
                                            colspan="{{ $billing->rabatt ? 5 : 4 }}"
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
        <div style="position: fixed; bottom: 0; width: 100%;">
            <table width="100%" class="table-supplier">
                <tr>
                    <td width="15%"></td>
                    <td width="15%"></td>
                    <td width="15%"></td>
                    <td width="55%" class="info-total">
                        <table width="100%">
                            <tr>
                                <td style="text-align: right;">
                                    Netto:
                                </td>
                                <td class="numbers" style="text-align: right;">
                                    <span>{{ formatCurrency($billing->subtotal) }} kr</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align: right;">
                                    Moms {{$billing->tax}}% (beräknad på {{ formatCurrency($billing->subtotal) }} kr):
                                </td>
                                <td class="numbers" style="text-align: right;">
                                    <span>{{ formatCurrency($billing->amount_tax) }} kr</span>
                                </td>
                            </tr>
                            @if($billing->discount > 0)
                            <tr>
                                <td style="text-align: right;">
                                    Preliminär skattereduktion {{$billing->discount}}% av {{ formatCurrency($billing->subtotal) }} kr:
                                </td>
                                <td class="numbers" style="text-align: right;">
                                    <span>- {{ formatCurrency($billing->amount_discount) }} kr</span>
                                </td>
                            </tr>
                            @endif
                            <tr>
                                <td style="text-align: right;">
                                    <strong>Summa att betala:</strong>
                                </td>
                                <td class="numbers" style="text-align: right;">
                                    <strong><span>{{ formatCurrency($billing->total) }} kr</span></strong>
                                </td>
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