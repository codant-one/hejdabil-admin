@extends('emails.layouts.layout')

@section('content')
<table cellpadding="0" cellspacing="0" width="100%" bgcolor="#FFFFFF" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:separate;border-spacing:0px;border-width:10px;border-style:solid;border-color:transparent;background-color:#ffffff;border-radius:16px; min-height: 300px; padding: 16px;">
    <tr>
        <td align="center" style="padding:0;margin:0;padding-bottom:16px;">
            <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:24px;color:#454545;font-size:16px;">
                Ditt konto har nu avaktiverats. Tack för att du har valt Billogg och för förtroendet att få vara en del av din verksamhet.
            </p>
        </td>
    </tr>
    <tr>
        <td align="center" style="padding:0;margin:0;padding-bottom:16px;">
            <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:24px;color:#454545;font-size:16px;">
                Om du i framtiden vill komma tillbaka eller behöver hjälp med något är du alltid varmt välkommen att kontakta oss.
            </p>
        </td>
    </tr>
    @include('emails.layouts.copy')
</table>
@endsection