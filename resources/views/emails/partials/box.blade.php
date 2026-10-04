{{-- Light panel: @include('emails.partials.box', ['title' => 'Shipping to', 'lines' => [...], 'html' => null]) --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0;background:#f9fafb;border-radius:8px;">
    <tr>
        <td style="padding:16px 20px;font-size:14px;">
            @if(!empty($title))<p style="margin:0 0 6px;font-weight:bold;">{{ $title }}</p>@endif
            @foreach(array_filter($lines ?? []) as $line)
                <p style="margin:0;color:#4b5563;">{{ $line }}</p>
            @endforeach
        </td>
    </tr>
</table>
