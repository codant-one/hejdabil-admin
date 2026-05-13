@extends("emails.layouts.layout")

@section("content")
<table cellpadding="0" cellspacing="0" width="100%" bgcolor="#FFFFFF" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:separate;border-spacing:0px;border-width:10px;border-style:solid;border-color:transparent;background-color:#ffffff;border-radius:16px; min-height: 300px; padding: 16px;">
	<tr>
		<td align="center" style="padding:0;margin:0;padding-bottom:15px;">
			<p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:24px;color:#454545;font-size:16px">
				Hej {{ $user }}, <br>
				Du har fått en ny notis i Billogg.
			</p>
		</td>
	</tr>
	<tr>
		<td style="padding:0;margin:0;padding-bottom:15px;padding-top:15px;">
			<p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:30px;color:#454545;font-size:24px; font-weight:700;">
				Notisdetaljer
			</p>
		</td>
	</tr>
	<tr>
		<td style="padding:0;margin:0;padding-bottom:15px;">
			<p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:24px;color:#454545;font-size:16px">
				<strong>Titel:</strong> {{ $notificationTitle }}
			</p>

			@if(!empty($notificationSubtitle))
				<p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:24px;color:#454545;font-size:16px">
					<strong>Detalj:</strong> {{ $notificationSubtitle }}
				</p>
			@endif

			<p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:24px;color:#454545;font-size:16px">
				<strong>Meddelande:</strong> {{ $notificationText }}
			</p>

			<p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:24px;color:#454545;font-size:16px">
				<strong>Tid:</strong> {{ $notificationDate }}
			</p>
		</td>
	</tr>

	@if(!empty($notificationRoute))
		<tr>
			<td align="center" style="padding:0;margin:0;padding:24px;">
				<span class="es-button-border" style="border-style:solid;border-width:0px 0px 2px 0px;display:block;border-radius:48px;width:auto;border-bottom-width:0px">
					<a href="{{ $notificationRoute }}" class="btn-gradient" style="font-family: 'Titillium Web', sans-serif; font-size:16px; color:#416054;">
						Öppna i Billogg
					</a>
				</span>
			</td>
		</tr>
	@endif

	<tr>
		<td align="center" style="padding:0;margin:0;padding-bottom:15px;">
			<p style="margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:24px;color:#454545;font-size:16px">
				Detta är ett automatiskt mejl och kan inte besvaras.
			</p>
		</td>
	</tr>
</table>
@endsection
