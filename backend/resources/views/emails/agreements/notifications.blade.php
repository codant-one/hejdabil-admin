@extends("emails.layouts.layout")

@section("content")
<table cellpadding="0" cellspacing="0" width="100%" bgcolor="#FFFFFF" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:separate;border-spacing:0px;border-width:10px;border-style:solid;border-color:transparent;background-color:#ffffff;border-radius:16px; min-height: 300px; padding: 16px;">
    <tr>
        <td align="center" style="padding:0;margin:0;padding-bottom:15px;">
            <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#454545;font-size:16px">         
                Hej {{$user}}, <br>
                Vi hoppas att detta meddelande når dig väl. <br>
                Ett nytt avtal har skickats till dig via Billogg.
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
    <tr>
        <td align="center" style="padding:0;margin:0;">
            <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#454545;font-size:16px">         
                Avtalet finns bifogat i PDF-format och kan även laddas ner via knappen nedan. <br>
                Vänligen granska innehållet noggrant.
            </p>
        </td>
    </tr>
    @if(isset($pdfFile))
    <td align="center" style="padding:0;margin:0;padding:24px;">
            <span class="es-button-border" style="border-style:solid;border-width:0px 0px 2px 0px;display:block;border-radius:48px;width:auto;border-bottom-width:0px">
                <a href="{{ $pdfFile }}" class="btn-gradient" style="font-family: 'Titillium Web', sans-serif; font-size: 16px; color: #416054;">
                    Ladda ner avtal
                </a>
            </span>
        </td>
    </tr>
    @endif
    <tr>
        <td align="center" style="padding:0;margin:0;">
            <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#454545;font-size:16px">         
                Har du frågor kring avtalets innehåll, vänligen kontakta företaget som har skickat avtalet. <br>
                Observera: Detta är ett automatiskt mejl och kan inte besvaras.
            </p>
        </td>
    </tr>
</table>
@endsection
        