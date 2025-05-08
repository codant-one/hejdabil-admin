@extends("emails.layouts.layout")

@section("content")
<table style="font-family: Poppins,Helvetica,sans-serif; clear: both; margin-top: 6px!important; margin-bottom: 6px!important;  max-width: none!important; border-collapse: separate!important;  border-spacing: 0; width: 100%; padding: 0 5px;" align="center">
    <tr>
        <td align="center" valign="top" style="padding:0;margin:0;width:520px">
            <table cellpadding="0" cellspacing="0" width="100%" bgcolor="#FFFFFF" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:separate;border-spacing:0px;border-width:10px;border-style:solid;border-color:transparent;background-color:#ffffff;border-radius:32px; min-height: 400px;">
                <tr>
                    <td align="center" style="padding:0;margin:0;">
                        <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:54px;color:#9966FF;font-size:36px">
                            <strong>Kära nån: {!! $user !!},</strong>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0;margin:0;padding-left:15px;padding-right:15px">
                        <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#2E0684;font-size:16px">
                            {!! $text !!}
                        </p><br>
                        <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#2E0684;font-size:16px">
                            <strong>Fakturanummer:</strong> {!! $billing->invoice_id !!}
                        </p>
                        <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#2E0684;font-size:16px">
                            <strong>Fakturadatum:</strong> {!! $billing->invoice_date !!}
                        </p>
                        <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#2E0684;font-size:16px">
                            <strong>Förfallodatum:</strong> {!! $billing->due_date !!}
                        </p>
                        <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#2E0684;font-size:16px">
                            <strong>Summa att betala:</strong> {!! formatCurrency($billing->total) !!} kr
                        </p><br>
                        <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#2E0684;font-size:16px">
                            Information om det utgivande företaget:
                        </p>
                        <br>
                        <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#2E0684;font-size:16px">
                            <strong>Företagsnamn:</strong> {!! $billing->supplier->company !!}
                        </p>
                        <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#2E0684;font-size:16px">
                            <strong>Org.nr.</strong> {!! $billing->supplier->organization_number !!}
                        </p>
                        <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#2E0684;font-size:16px">
                            <strong>E-post:</strong> {!! $billing->supplier->user->email !!}
                        </p><br>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0;margin:0;padding-left:15px;padding-right:15px">
                        <p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:19px;color:#2E0684;font-size:16px">
                            {!! $text_info !!}
                        </p>
                    </td>
                </tr>
                @if(isset($pdfFile))
                <tr>
                    <td align="center" style="padding:40px;margin:0;">
                        <span style="border-style:solid;border-color:#2CB543;background:#ff0090;border-width:0px 0px 2px 0px;display:block;border-radius:32px;width:auto;border-bottom-width:0px">
                            <a href="{{ $pdfFile }}" class="es-button" target="_blank" style="mso-style-priority:100 !important;text-decoration:none;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;color:#FFFFFF;font-size:16px;padding:10px 20px 10px 20px;display:block;background:#9966FF;border-radius:32px;font-family:arial, 'helvetica neue', helvetica, sans-serif;font-weight:700;font-style:normal;line-height:19px;width:auto;text-align:center;mso-padding-alt:0;mso-border-alt:10px solid #9966FF;padding-left:5px;padding-right:5px;">
                                {!! $buttonText !!}
                            </a>
                        </span>
                    </td>
                </tr>
                @endif
            </table>
        </td>
    </tr>
</table>
@endsection
        