@extends("emails.layouts.layout")

@section("content")
<table cellpadding="0" cellspacing="0" width="100%" bgcolor="#FFFFFF" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:separate;border-spacing:0px;border-width:10px;border-style:solid;border-color:transparent;background-color:#ffffff;border-radius:16px; min-height: 300px; padding: 16px;">
    @if($agreement->agreement_type_id === 4)
        <tr>
            <td align="center" style="padding:0;margin:0;padding-bottom:15px;">
                <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#454545;font-size:16px">         
                    Hej {{$user}}, <br>
                    Ett prisförslag har tagits fram till dig.<br>
                </p>
            </td>
        </tr>   
    @else
    <tr>
        <td align="center" style="padding:0;margin:0;padding-bottom:15px;">
            <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#454545;font-size:16px">         
                Hej {{$user}}, <br>
                Ett avtal har skickats till dig och väntar nu på din digitala signering.
            </p>
        </td>
    </tr>
    @endif
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
    @if($agreement->agreement_type_id === 4)
    <tr>
        <td align="center" style="padding:0;margin:0;">
            <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#454545;font-size:16px">         
                För att läsa igenom och signera prisförslaget, klicka på knappen nedan. <br>
                Där har du även möjlighet att godkänna och signera prisförslaget digitalt. <br>
                Vänligen granska innehållet och återkom vid frågor.
            </p>
        </td>
    </tr>
    @else
    <tr>
        <td align="center" style="padding:0;margin:0;">
            <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#454545;font-size:16px">         
                För att läsa igenom och signera avtalet, klicka på knappen nedan. <br>
                Signeringen sker digitalt och är juridiskt bindande när samtliga parter har signerat.
            </p>
        </td>
    </tr>
    @endif
    @if($agreement->agreement_type_id === 4)
    <tr>
       <td align="center" style="padding:0;margin:0;padding:24px;">
            <span class="es-button-border" style="border-style:solid;border-width:0px 0px 2px 0px;display:block;border-radius:48px;width:auto;border-bottom-width:0px">
                <a href="{{ $signingUrl }}" class="btn-gradient" style="font-family: 'Titillium Web', sans-serif; font-size: 16px; color: #416054;">
                    Visa och signera prisförslag
                </a>
            </span>
        </td>
    </tr>
    <tr>
        <td align="center" style="padding:0;margin:0;padding-bottom:15px;">
            <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#454545;font-size:16px">         
                Observera att detta prisförslag inte är bindande förrän det har accepterats av båda parter. <br>
                Observera: Detta är ett automatiskt mejl och kan inte besvaras.
        </td>
    </tr>
    @else
    <tr>
       <td align="center" style="padding:0;margin:0;padding:24px;">
            <span class="es-button-border" style="border-style:solid;border-width:0px 0px 2px 0px;display:block;border-radius:48px;width:auto;border-bottom-width:0px">
                <a href="{{ $signingUrl }}" class="btn-gradient" style="font-family: 'Titillium Web', sans-serif; font-size: 16px; color: #416054;">
                    Öppna och signera avtal
                </a>
            </span>
        </td>
    </tr>
    <tr>
        <td align="center" style="padding:0;margin:0;padding-bottom:15px;">
            <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#454545;font-size:16px">         
                När avtalet är signerat får du automatiskt en bekräftelse samt tillgång till det signerade dokumentet. <br>
                Har du frågor kring avtalets innehåll, vänligen kontakta företaget som har skickat avtalet.
        </td>
    </tr>
    @endif
</table>             
@endsection