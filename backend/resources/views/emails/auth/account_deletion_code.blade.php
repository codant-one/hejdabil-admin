@extends('emails.layouts.layout')

@section('content')
<table cellpadding="0" cellspacing="0" width="100%" bgcolor="#FFFFFF" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:separate;border-spacing:0;border-width:10px;border-style:solid;border-color:transparent;background-color:#ffffff;border-radius:16px;min-height:300px;padding:16px;">
    <tr>
        <td align="center" style="padding:0;margin:0;padding-bottom:16px;">
            <p style="margin:0;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:24px;color:#454545;font-size:16px;">
                Du har begärt att radera ditt Bilflogg-konto permanent. Använd verifieringskoden nedan för att bekräfta raderingen.
            </p>
        </td>
    </tr>
    <tr>
        <td align="center" style="padding:12px 24px 24px 24px;margin:0;">
            <strong style="font-family:arial, 'helvetica neue', helvetica, sans-serif;font-size:32px;line-height:40px;letter-spacing:8px;color:#1c2925;">
                {{ $code }}
            </strong>
        </td>
    </tr>
    <tr>
        <td align="center" style="padding:0;margin:0;padding-bottom:16px;">
            <p style="margin:0;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:24px;color:#454545;font-size:16px;">
                Ange koden i Bilflogg för att fortsätta med raderingen. Om du inte har begärt att ditt konto ska raderas kan du bortse från detta mejl. Ditt konto påverkas inte så länge raderingen inte bekräftas.
            </p>
        </td>
    </tr>
    @include('emails.layouts.copy')
</table>
@endsection