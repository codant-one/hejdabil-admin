@extends("emails.layouts.layout")

@section("content")
<table cellpadding="0" cellspacing="0" width="100%" bgcolor="#FFFFFF" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:separate;border-spacing:0px;border-width:10px;border-style:solid;border-color:transparent;background-color:#ffffff;border-radius:16px; min-height: 300px; padding: 16px;">
    <tr>
        <td align="center" style="padding:0;margin:0;">
            <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#454545;font-size:16px">  
                Hej, <br>
                Du har mottagit bifogade dokument.
                Vid frågor kring dokumentens innehåll, vänligen kontakta företaget som har skickat dokumentet.
            </p>
        </td>
    </tr>    
    <tr>
        <td style="padding:0;margin:0;padding-bottom:15px;padding-top:15px;">
            <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#454545;font-size:24px; font-weight: 700;">         
                Uppgifter om avsändande företag
            </p>
        </td>
    </tr>
    <tr>
        <td style="padding:0;margin:0;padding-bottom:15px;padding-top:15px;">
            <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#454545;font-size:16px">
                <strong>Företagsnamn:</strong> {!! $company->company !!}
            </p>
            <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#454545;font-size:16px">
                <strong>Organisationsnummer:</strong> {!! $company->organization_number !!}
            </p>
            <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#454545;font-size:16px">
                <strong>E-post:</strong> {!! $company->email !!}
            </p><br>
        </td>
    </tr>         
</table>        
@endsection
