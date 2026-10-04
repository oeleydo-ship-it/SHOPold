{{-- @include('emails.partials.button', ['url' => ..., 'label' => ..., 'outline' => false]) --}}
@php($brand = \App\View\EmailBrand::data())
<table role="presentation" cellpadding="0" cellspacing="0" align="center" style="margin:24px auto;">
    <tr>
        <td style="border-radius:8px;background:{{ ($outline ?? false) ? '#ffffff' : $brand['primary'] }};border:2px solid {{ $brand['primary'] }};">
            <a href="{{ $url }}" style="display:inline-block;padding:12px 28px;font-weight:bold;font-size:15px;text-decoration:none;color:{{ ($outline ?? false) ? $brand['primary'] : '#ffffff' }};">{{ $label }}</a>
        </td>
    </tr>
</table>
