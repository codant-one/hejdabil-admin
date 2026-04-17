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
            color: #454545;
            letter-spacing: 0 !important;
            word-spacing: normal !important;
        }

        table {
            border-radius: 16px !important;
            border-spacing: unset;
            font-size: 12px;
            font-weight: 400;
        }

        .faktura {
            font-family: 'gelion', 'dm sans', sans-serif;
            font-size: 32px;
            font-weight: 600;
            color: #FFFFFF;
            border-top: 1px solid #FFFFFF;
            border-bottom: 1px solid #FFFFFF;
            padding: 8px 16px 4px 16px;
            line-height: 0.6;
            display: inline-block;
            letter-spacing: 0 !important;
        }

        .table-items {
            font-family: 'gelion', 'dm sans', sans-serif !important;
            margin-top: 10px;
            border-radius: 32px !important;
            letter-spacing: 0 !important;
            line-height: 100%;
        }

        .table-supplier {
            font-family: 'gelion', 'dm sans', sans-serif !important;
            letter-spacing: 0 !important;
        }

        .invoice-background {
            background-color: {{ $company->primary_color ?? '#E7E7E7' }};
            color: #FFFFFF !important;
        }

        .bg-items {
            background-color: {{ $company->secondary_color ?? '#F6F6F6' }};
            font-weight: 600 !important;
        }

        .bg-items td:first-child {
            border-top-left-radius: 32px !important;
            border-bottom-left-radius: 32px !important;
        }

        .bg-items td:last-child {
            border-top-right-radius: 32px !important;
            border-bottom-right-radius: 32px !important;
        }

        .box-logo {
            width: 160px;
            height: 72px;
            background: white;
            border-radius: 8px;
            position: relative;
            padding: 8px;
        }

        .box-logo img {
            display: block;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .table-background-top {
            border-bottom-left-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
        }

        .table-background-bottom {
            border-top-left-radius: 0 !important;
            border-top-right-radius: 0 !important;
        }

        .data-from {
            padding: 16px;
        }

        .m-0 {
            margin: 0;
        }

        .mt-5 {
            margin-top: 5px;
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

        .pt-0 {
            padding-top: 0;
        }

        .pb-0 {
            padding-bottom: 0;
        }

        .info-total table tr {
            height: 20px;
            font-size: 12px;
        }

        .info-total .text {
            text-align: end;
        }

        .info-total .numbers {
            width: auto !important;
            text-align: end;
        }

        .info-total .summary {
            font-size: 20px;
            font-weight: 700;
            color: {{ $company->primary_color ?? '#E7E7E7' }};
        }

        .border-top {
            border-top-width: thin !important;
            border-top-style: solid !important;
            border-top-color: #E7E7E7 !important;
            border-radius: 0 !important;
        }

        .info-supplier {
            display: flex;
            flex-direction: column;
            width: auto;            
        }

        .style-supplier {
            color: #878787;
        }

        .table-supplier td {
            vertical-align: top;
        }
    </style> 
    <body>
        modern 2
    </body>
</html>