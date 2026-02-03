@extends("emails.layouts.layout")

@section("content")
<table cellpadding="0" cellspacing="0" width="100%" bgcolor="#FFFFFF" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:separate;border-spacing:0px;border-width:10px;border-style:solid;border-color:transparent;background-color:#ffffff;border-radius:16px; min-height: 300px; padding: 16px;">
    <tr>
        <td align="center" style="padding:0;margin:0;">
             <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#454545;font-size:16px">  
                Hej,<br>
                Du har fått ett dokument skickat till dig för digital signering via Billogg.<br>
                Vänligen klicka på knappen nedan för att läsa igenom och signera dokumentet.<br>
                Signeringen sker digitalt och är säker.
            </p>
        <td>
    </tr>
    <tr>
        <td align="center" style="padding:0;margin:0;padding:24px;">
            <span class="es-button-border" style="border-style:solid;border-width:0px 0px 2px 0px;display:block;border-radius:48px;width:auto;border-bottom-width:0px">
                <a href="{{ $signingUrl }}" class="btn-gradient" style="font-family: 'Titillium Web', sans-serif; font-size: 16px; color: #416054;">
                    Öppna och signera dokument
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
                <a href="{{ $signingUrl }}" class="btn-ghost" style="font-family: 'Titillium Web', sans-serif; font-size: 16px; color: #6E9383;">
                    {{ $signingUrl }}
                </a>
            </span>
        </td>
    </tr>
    <tr>
        <td align="center" style="padding:0;margin:0;padding-bottom:15px;">
             <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#454545;font-size:16px">  
                Har du frågor kring dokumentets innehåll, vänligen kontakta avsändaren.
            </p>
        <td>
    </tr>
    @if(!empty($text))
    <tr>
        <td style="padding:0;margin:0;padding-bottom:15px;padding-top:15px;">
            <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#454545;font-size:24px; font-weight: 700;">         
                Meddelande från avsändaren:
            </p>
        </td>
    </tr>
    <tr>
        <td style="padding:0;margin:0;padding-bottom:15px;">
            <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#454545;font-size:16px">         
                {!! $text !!}
            </p>
        </td>
    </tr>
    @endif
</table>
@endsection