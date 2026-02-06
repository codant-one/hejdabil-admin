<table border="0" cellpadding="0" cellspacing="0" width="100%" class="content-table" role="presentation">
    <tr>
        <td align="center">
            @if(isset($icon))
                <img src="{{ $icon }}" alt="Icon" title="Icon" width="142" />
            @else
                <img src="{{ asset('/images/check.png') }}" alt="Billogg" alt="Icon" title="Icon" width="142" />
            @endif
        </td>
    </tr>
    <tr>
        <td align="center" style="padding-bottom: 40px;">
            <h1 class="title-text">
                @if(isset($title))
                    {{ $title }}
                @else
                    Email Title
                @endif
            </h1>
        </td>
    </tr>
</table>