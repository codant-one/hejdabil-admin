@extends("emails.layouts.layout")

@section("content")
<table style="font-family: Poppins,Helvetica,sans-serif; clear: both; margin-top: 6px!important; margin-bottom: 6px!important;  max-width: none!important; border-collapse: separate!important;  border-spacing: 0; width: 100%; padding: 0 5px;" align="center">
    <tr>
        <td align="center" valign="top" style="padding:0;margin:0;width:520px">
            <table cellpadding="0" cellspacing="0" width="100%" bgcolor="#FFFFFF" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:separate;border-spacing:0px;border-width:10px;border-style:solid;border-color:transparent;background-color:#ffffff;border-radius:32px; min-height: 400px;">
                <tr>
                    <td align="center" style="padding:0;margin:0;">
                        <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:54px;color:#9966FF;font-size:36px">
                            <strong>{{ $reg_num }} Dokuments</strong>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0;margin:0;padding-left:15px;padding-right:15px">
                        <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#2E0684;font-size:16px">                            
                            Hej,
                        </p><br>
                        <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#2E0684;font-size:16px">                            
                           Du har fått bifogade handlingar från bilföretaget {{ env('APP_NAME') }}
                        </p>
                        <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#2E0684;font-size:16px">                            
                          Frågor hänvisas till bilfirman.
                        </p><br><br><br>
                        <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#2E0684;font-size:16px">                            
                          OBS! Detta mejl går inte att svara på.
                        </p>
                        <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#2E0684;font-size:16px">                            
                          Har du några frågor eller funderingar?
                        </p>
                        <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#2E0684;font-size:16px">                            
                          Kontakta oss på info@autoflow.nu
                        </p><br><br><br>
                        <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#2E0684;font-size:16px">
                            Teamet på Zplit Payments AB
                        </p><br>
                        
                    </td>
                </tr>                
            </table>
        </td>
    </tr>
</table>
@endsection
        