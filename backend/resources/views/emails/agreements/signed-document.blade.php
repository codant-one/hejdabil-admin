@extends("emails.layouts.layout")

@section("content")
<table cellpadding="0" cellspacing="0" width="100%" bgcolor="#FFFFFF" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:separate;border-spacing:0px;border-width:10px;border-style:solid;border-color:transparent;background-color:#ffffff;border-radius:16px; min-height: 300px; padding: 16px;">
    @if($agreement)
    <tr>
        <td align="center" style="padding:0;margin:0;">
            <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#454545;font-size:16px">  
                Hej, {{$fullname}}<br>
                Vi bekräftar härmed att avtalet har signerats digitalt av samtliga parter.            </p>
        <td>
    </tr>
    <tr>
        <td style="padding:0;margin:0;padding-bottom:15px;padding-top:15px;">
            <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#454545;font-size:24px; font-weight: 700;">         
                Avtalspart
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
                Det signerade avtalet finns nu tillgängligt i PDF-format och kan laddas ner via knappen nedan. <br>
                Avtalet är juridiskt bindande och innehåller tidsstämpel samt signeringsinformation.
            </p>
        <td>
    </tr>
    <tr>
        <td align="center" style="padding:0;margin:0;padding:24px;">
            <span class="es-button-border" style="border-style:solid;border-width:0px 0px 2px 0px;display:block;border-radius:48px;width:auto;border-bottom-width:0px">
                <a href="{{ $downloadUrl }}" class="btn-gradient" style="font-family: 'Titillium Web', sans-serif; font-size: 16px; color: #416054;">
                    Ladda ner signerat avtal
                </a>
            </span>
        </td>
    </tr>
    <tr>
        <td align="center" style="padding:0;margin:0;">
            <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#454545;font-size:16px">  
                Har du frågor kring avtalets innehåll eller nästa steg, vänligen kontakta företaget som har skickat avtalet. <br>
                Observera: Detta är ett automatiskt mejl och kan inte besvaras.
            </p>
        <td>
    </tr>
    @elseif($document)
    <tr>
        <td align="center" style="padding:0;margin:0;">
            <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#454545;font-size:16px">  
                Hej,<br>
                Dokumentet som skickades till dig har nu signerats av samtliga parter.<br>
                Det signerade dokumentet är juridiskt bindande och finns tillgängligt för nedladdning.
            </p>
        <td>
    </tr>
     <tr>
        <td align="center" style="padding:0;margin:0;padding:24px;">
            <span class="es-button-border" style="border-style:solid;border-width:0px 0px 2px 0px;display:block;border-radius:48px;width:auto;border-bottom-width:0px">
                <a href="{{ $downloadUrl }}" class="btn-gradient" style="font-family: 'Titillium Web', sans-serif; font-size: 16px; color: #416054;">
                    Ladda ner signerat dokument
                </a>
            </span>
        </td>
    </tr>
    <tr>
        <td align="center" style="padding:0;margin:0;">
            <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#454545;font-size:16px">  
                Om knappen ovan inte fungerar kan du istället använda denna länk:
            </p>
        <td>
    </tr>
    <tr>
        <td align="center" style="margin:0;padding-left:24px;padding-right:24px;padding-top:15px;padding-bottom:15px;">
            <span class="es-button-border" style="border-style:solid;border-width:0px 0px 2px 0px;display:block;border-radius:48px;width:auto;border-bottom-width:0px">
                <a href="{{ $downloadUrl }}" class="btn-ghost" style="font-family: 'Titillium Web', sans-serif; font-size: 16px; color: #6E9383;">
                    {{ $downloadUrl }}
                </a>
            </span>
        </td>
    </tr>
    <tr>
        <td align="center" style="padding:0;margin:0;">
            <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#454545;font-size:16px">  
                Har du frågor kring dokumentets innehåll eller nästa steg, vänligen kontakta företaget som har skickat dokumentet.
            </p>
        <td>
    </tr>
    @endif
</table>
@endsection