@extends("emails.layouts.layout")

@section("content")
<table cellpadding="0" cellspacing="0" width="100%" bgcolor="#FFFFFF" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:separate;border-spacing:0px;border-width:10px;border-style:solid;border-color:transparent;background-color:#ffffff;border-radius:16px; min-height: 450px;">
    <tr>
        <td align="center" style="padding:0;margin:0;padding-bottom:15px;padding-top:15px;">
            <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#000000;font-size:16px">         
                Hej,<br>
                Vi har mottagit en begäran om att återställa lösenordet för ditt Billogg-konto.<br>
                För att skapa ett nytt lösenord, klicka på knappen nedan:
            </p>
        </td>
    </tr>
    <tr>
        <td align="center" style="padding:0;margin:0;padding-left:40px;padding-right:40px">
            <span class="es-button-border" style="border-style:solid;border-width:0px 0px 2px 0px;display:block;border-radius:48px;width:auto;border-bottom-width:0px">
                <a href="{{ $buttonLink }}" class="btn-gradient" style="color:#454545; font-family: 'Titillium Web', sans-serif; font-size: 16px;">
                    Återställ lösenord
                </a>
            </span>
        </td>
    </tr>
    <tr>
        <td align="center" style="padding:0;margin:0;padding-bottom:15px;padding-top:15px;">
            <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#000000;font-size:16px">         
                Länken är giltig under en begränsad tid och kan endast användas en gång.<br>
                Om du inte har begärt att återställa ditt lösenord kan du bortse från detta mejl - inga ändringar görs utan att länken används.<br>
                Har du frågor eller behöver hjälp är du välkommen att kontakta oss.
            </p>
        </td>
    </tr>
    @include('emails.layouts.copy')
</table>
@endsection
        