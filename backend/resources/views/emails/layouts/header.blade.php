<tr>
    <td align="left" style="padding: 20px; background-color: #ffffff;">
        <table border="0" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
                <td align="left">
                    @if(isset($logo))
                        <img src="{{ $logo }}" alt="Company Logo" title="Company Logo" width="100" />
                    @else
                        <img src="{{ asset('/images/main.png') }}" alt="Billogg" title="Billogg" width="104" />
                    @endif
                </td>
            </tr>
        </table>
    </td>
</tr>