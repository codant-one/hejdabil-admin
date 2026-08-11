@extends("emails.layouts.layout")

@section("content")
<table cellpadding="0" cellspacing="0" width="100%" bgcolor="#FFFFFF" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:separate;border-spacing:0px;border-width:10px;border-style:solid;border-color:transparent;background-color:#ffffff;border-radius:16px; min-height: 150px; padding: 16px;">
    <tr>
        <td align="center" style="padding:0;margin:0;padding-bottom:15px;">
            <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#454545;font-size:16px">         
                Hej {{$user}}, <br>
                Din {{$supplier->is_yearly === 0 ? 'månadsfaktura' : 'årsvis faktura'}} för ditt Bilflogg-abonnemang har skapats och bifogas i detta e-postmeddelande.
            </p>
        </td>
    </tr>
    <tr>
        <td align="center" style="padding:0;margin:0;">
            <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#454545;font-size:16px">         
                Vi ber dig betala fakturan senast på förfallodagen för att säkerställa att ditt abonnemang fortsätter utan avbrott.
            </p>
        </td>
    </tr>
    <tr>
        <td align="center" style="padding:0;margin:0;">
            <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#454545;font-size:16px">         
                Har du några frågor är du varmt välkommen att kontakta oss på info@bilflogg.se eller till 072-277 22 97.
            </p>
        </td>
    </tr>
    <tr>
        <td align="center" style="padding:0;margin:0;">
            <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#454545;font-size:16px">         
                Tack för att du använder Bilflogg!
            </p>
        </td>
    </tr>
    @include('emails.layouts.copy')
</table>
@endsection
        