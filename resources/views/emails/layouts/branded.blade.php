{{--
    Shared email frame: store logo/name in the header bar (active theme's primary colour), white card, footer with
    contact details and social links. Table layout and inline styles because many email clients ignore flexbox and <style>.
    Sections: title, preheader (inbox preview text), heading (optional, shown on the coloured bar), content.
--}}
@php($brand = \App\View\EmailBrand::data())
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>@yield('title', $brand['name'])</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1f2937;line-height:1.6;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">@yield('preheader')</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="background:{{ $brand['primary'] }};padding:24px 32px;text-align:center;">
                            <a href="{{ $brand['url'] }}" style="color:#ffffff;text-decoration:none;font-size:20px;font-weight:bold;">
                                @if($brand['logo'])
                                    <img src="{{ $brand['logo'] }}" alt="{{ $brand['name'] }}" height="40" style="height:40px;max-width:220px;border:0;display:inline-block;">
                                @else
                                    {{ $brand['name'] }}
                                @endif
                            </a>
                            @hasSection('heading')
                                <h1 style="margin:16px 0 0;color:#ffffff;font-size:22px;line-height:1.3;">@yield('heading')</h1>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;font-size:15px;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f9fafb;padding:20px 32px;text-align:center;font-size:12px;color:#6b7280;">
                            @if(count($brand['social']))
                                <p style="margin:0 0 8px;">
                                    @foreach($brand['social'] as $network => $url)
                                        <a href="{{ $url }}" style="color:{{ $brand['primary'] }};text-decoration:none;margin:0 6px;">{{ ucfirst($network) }}</a>
                                    @endforeach
                                </p>
                            @endif
                            @if($brand['address'])<p style="margin:0;">{{ $brand['address'] }}</p>@endif
                            @if($brand['email'] || $brand['phone'])
                                <p style="margin:0;">{{ implode(' · ', array_filter([$brand['email'], $brand['phone']])) }}</p>
                            @endif
                            <p style="margin:8px 0 0;">&copy; {{ date('Y') }} {{ $brand['name'] }}. All rights reserved.</p>
                            @yield('footer_note')
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
