@extends("emails.layouts.layout")

@section("content")
<table style="font-family: Poppins,Helvetica,sans-serif; clear: both; margin-top: 6px!important; margin-bottom: 6px!important;  max-width: none!important; border-collapse: separate!important;  border-spacing: 0; width: 100%; padding: 0 5px;" align="center">
    <tr>
        <td align="center" valign="top" style="padding:0;margin:0;width:520px">
            <table cellpadding="0" cellspacing="0" width="100%" bgcolor="#FFFFFF" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:separate;border-spacing:0px;border-width:10px;border-style:solid;border-color:transparent;background-color:#ffffff;border-radius:32px; min-height: 400px;">
                <tr>
                    <td align="center" style="padding:20px;margin:0;">
                        <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:54px;color:#57F287;font-size:36px">
                            <strong>Swish-betalningskvitto</strong>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0;margin:0;padding-left:15px;padding-right:15px">
                        <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#2E0684;font-size:16px">                            
                            Hej,
                        </p><br>
                        <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#2E0684;font-size:16px">                            
                           Här kommer ditt Swish-betalningskvitto från {{ env('APP_NAME') }}.
                        </p><br>

                        @foreach($payouts as $payout)
                        <table cellpadding="0" cellspacing="0" width="100%" style="background-color:#f8f9fa;border-radius:12px;margin-bottom:15px;">
                            <tr>
                                <td style="padding:20px;">
                                    <table cellpadding="0" cellspacing="0" width="100%">
                                        <tr>
                                            <td style="padding:5px 0;">
                                                <p style="margin:0;font-family:arial, 'helvetica neue', helvetica, sans-serif;font-size:14px;color:#666;">Referens:</p>
                                                <p style="margin:0;font-family:arial, 'helvetica neue', helvetica, sans-serif;font-size:16px;color:#2E0684;font-weight:bold;">{{ $payout->reference ?? '-' }}</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:5px 0;">
                                                <p style="margin:0;font-family:arial, 'helvetica neue', helvetica, sans-serif;font-size:14px;color:#666;">Meddelande:</p>
                                                <p style="margin:0;font-family:arial, 'helvetica neue', helvetica, sans-serif;font-size:16px;color:#2E0684;font-weight:bold;">{{ $payout->message ?? '-' }}</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:5px 0;">
                                                <p style="margin:0;font-family:arial, 'helvetica neue', helvetica, sans-serif;font-size:14px;color:#666;">Belopp:</p>
                                                <p style="margin:0;font-family:arial, 'helvetica neue', helvetica, sans-serif;font-size:20px;color:#57F287;font-weight:bold;">{{ number_format($payout->amount, 2, ',', ' ') }} kr</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:5px 0;">
                                                <p style="margin:0;font-family:arial, 'helvetica neue', helvetica, sans-serif;font-size:14px;color:#666;">Datum:</p>
                                                <p style="margin:0;font-family:arial, 'helvetica neue', helvetica, sans-serif;font-size:16px;color:#2E0684;font-weight:bold;">{{ \Carbon\Carbon::parse($payout->created_at)->format('Y/m/d H:i') }}</p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                        @endforeach

                        <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#2E0684;font-size:14px;font-style:italic;">                            
                          Se bifogat kvitto för mer information.
                        </p><br><br>
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
