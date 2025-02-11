<!DOCTYPE html>
<html lang="es">  
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Invoice Template</title>
    </head>
    <style>

        @font-face {
            font-family: 'Gelion Regular';
            font-style: normal;
            font-weight: 400;
            src: url('{{ asset('fonts/gelion-Regular.ttf') }}') format('truetype');
            font-display: swap;
        }

        body {
            background-color:#FFFFFF;
            padding: 20px;
            font-family: 'Gelion Regular', Arial, sans-serif !important;
            color: #33303CAD;
            line-height: 1.5;
        }

        table {
            border-radius: 16px !important;
            border-spacing: unset;
            font-size: 10px;
            font-weight: 400;
            letter-spacing: normal;
            text-transform: none;
        }

        table thead {
            font-weight: 700;
        }

        table tr {
            height: 40px;
        }

        .invoice-background {
            background-color: #F2EFFF;
        }

        .data-from {
            padding: 24px;
        }

        .m-0 {
            margin: 0;
        }

        .mt-10 {
            margin-top: 20px;
        }

        .table-main {
            width: 100%;
            height: 100%;
        }

        .number-invoice {
            justify-content: end;
            display: flex;
            flex-direction: column;
            width: auto;
            align-items: end;
        }

        .font-weight-medium {
            font-weight: 700;
        }

        .table-items {
            margin-top: 10px;
            border-radius: 8px !important;
            border-width: thin !important;
            border-style: solid !important;
            border-color: rgba(47,43,61, 0.16) !important;
        }

        .pr-0 {
            padding-right: 0;
        }

        .info-total {
            display: flex;
            flex-direction: row;
        }

        .info-total table tr {
            height: 20px;
        }

        .info-total .text {
            text-align: end;
        }

        .info-total .numbers {
            width: 30% !important;
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
                                <td width="65%" class="data-from">
                                    <div class="d-flex align-center mb-6">
                                        @if(!$billing->supplier)
                                            <img src="{{ public_path('logos/logo_black.png') }}" width="auto" height="50" alt="logo-main">  
                                        @else
                                            @if($billing->supplier->logo)
                                                <img src="{{ public_path('storage/'.$billing->supplier->logo) }}" width="auto" height="50" alt="logo-main">
                                            @else
                                                <img src="{{ public_path('logos/logo_black.png') }}" width="auto" height="50" alt="logo-main">
                                            @endif
                                        @endif
                                    </div>
                                    <p class="m-0">
                                    Client No: {{$billing->client_id}}
                                    </p>
                                    <p class="m-0">
                                        Name:  {{$billing->client->fullname}}
                                    </p>
                                    <p class="m-0">
                                        E-mail: {{$billing->client->email}}
                                    </p>
                                    @if($billing->organization_number)
                                    <p class="m-0">
                                        Organization number: {{$billing->organization_number}}
                                    </p>
                                    @endif
                                    @if($billing->reference)
                                    <p class="m-0">
                                        Reference: {{$billing->reference}}
                                    </p> 
                                    @endif   
                                    <p class="mt-10 m-0">After the due date, interest is charged according to the Interest Act.</p>           
                                </td>
                                <td width="35%" class="data-from number-invoice">
                                    <h3 class="font-weight-medium m-0">
                                        Invoice No #{{ $billing->invoice_id }}
                                    </h3>
                                    <p class="mt-12 m-0 mt-10">
                                        <span>Invoice Date: </span>
                                        <span>{{ \Carbon\Carbon::parse($billing->invoice_date)->format('d/m/Y') }}</span>
                                    </p>
                                    <p class="m-0">
                                        <span>Due date: </span>
                                        <span>{{ \Carbon\Carbon::parse($billing->due_date)->format('d/m/Y') }}</span>
                                    </p>
                                    <p class="m-0">
                                        <span>Payment Terms: </span>
                                        <span>{{ $billing->payment_terms }}</span>
                                    </p>
                                    <p class="m-0 number-invoice">
                                        <h4 class="font-weight-medium m-0 mt-10">
                                            Billing Address
                                        </h4>
                                        <span class="number-invoice">
                                            <span class="font-weight-medium">{{ $billing->client->address }}</span>
                                            <span>{{ $billing->client->street }}</span>
                                            <span>{{ $billing->client->postal_code }}</span>
                                        </span>
                                    </p>
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
                                    <td style="width: {{$type->type_id === 1 ? '40' : (60/(count($types) - 1)) }}%"> {{ $type->name_en }}</td>
                                    @endforeach
                                </tr>
                            </thead>
                            @foreach($invoices as $rowIndex => $row)
                                <tr>
                                    @foreach($row as $colIndex => $column)
                                        <td>
                                            {{ $column['value'] }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </table>     
                    </td>
                </tr>
                <!---------------------------TOTAL------------------------->
                <tr>
                    <td>
                        <table width="100%">
                            <tr>
                                <td width="65%" class="data-from"></td>
                                <td width="35%" class="data-from number-invoice pr-0 info-total">
                                    <table width="100%">
                                        <tr>
                                            <td class="text">Subtotal:</td>
                                            <td class="numbers"><span>{{ $billing->subtotal }} KR</span></td>
                                        </tr>
                                        <tr>
                                            <td class="text">Tax:</td>
                                            <td class="numbers"><span>{{ $billing->tax }}%</span></td>
                                        </tr>
                                        <tr>
                                            <td class="text">Total:</td>
                                            <td class="numbers"><span>{{ $billing->total }} KR</span></td>
                                        </tr>
                                    </table>                              
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <!------------------------- BILL TO---------------------------------->
                <tr>
                    <td>
                        <table width="100%" class="table-supplier border-top">
                            <tr>
                                <td>
                                    <p class="m-0 info-supplier">
                                        <h4 class="font-weight-medium m-0 mt-10">
                                            Address
                                        </h4>
                                        @if(!$billing->supplier)
                                            <span class="info-supplier">
                                                <span>Hejdå Bil AB</span>
                                                <span>Abrahamsbergsvägen 47</span>
                                                <span>16830 BROMMA</span>
                                            </span>
                                        @else
                                            <span class="info-supplier">
                                                <span>{{ $billing->supplier->address }}</span>
                                                <span>{{ $billing->supplier->street }}</span>
                                                <span>{{ $billing->supplier->postal_code }}</span>
                                            </span>
                                        @endif
                                    </p>
                                    <p class="m-0 info-supplier">
                                        <h4 class="font-weight-medium m-0">
                                            Registered office of the company
                                        </h4>
                                        <span class="info-supplier">
                                            <span>Stockholm, Sweden</span>
                                        </span>
                                    </p>
                                    <p class="m-0 info-supplier">
                                        <h4 class="font-weight-medium m-0">
                                            Swish
                                        </h4>
                                        <span class="info-supplier">
                                            <span>??</span>
                                        </span>
                                    </p>
                                </td>
                                <td>
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
                                    <p class="m-0 info-supplier">
                                        <h4 class="font-weight-medium m-0">
                                            VAT reg. no.
                                        </h4>
                                        @if(!$billing->supplier)
                                            <span class="info-supplier">
                                                <span>SE559374026801 ??</span>
                                            </span>
                                        @else
                                            <span class="info-supplier">
                                                <span>??</span>
                                            </span>
                                        @endif
                                    </p>
                                </td>
                                <td>
                                    <p class="m-0 info-supplier">
                                        <h4 class="font-weight-medium m-0 mt-10">
                                            Website
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
                                            Company e-mail
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
                                <td>
                                    <p class="m-0 info-supplier">
                                        <h4 class="font-weight-medium m-0 mt-10">
                                            Bank account number
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
                                                <span>??</span>
                                            </span>
                                        @endif
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                @if($billing->note)
                <tr>
                    <td>
                        <table width="100%" class="border-top mt-10">
                            <tr>
                                <td>
                                    <span class="mt-10">Note: {{$billing->note}}</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </body>
</html>