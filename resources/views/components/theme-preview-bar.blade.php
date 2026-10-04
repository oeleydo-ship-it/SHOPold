@if(app('theme')->isPreviewing())
@php($previewSlug = app('theme')->previewSlug())
<div id="theme-preview-bar" style="position:fixed;bottom:16px;left:50%;transform:translateX(-50%);z-index:99999;background:#111827;color:#fff;font:14px/1.4 system-ui,sans-serif;padding:10px 16px;border-radius:9999px;box-shadow:0 8px 24px rgba(0,0,0,.3);display:flex;gap:12px;align-items:center;flex-wrap:wrap;max-width:calc(100vw - 24px)">
    <span>Previewing theme <strong>{{ ucfirst($previewSlug) }}</strong> (only you can see this)</span>
    <a href="{{ url('/admin/themes/' . $previewSlug . '/customize') }}" style="color:#93c5fd">Customize</a>
    <form method="POST" action="{{ url('/admin/themes/preview') }}" style="margin:0">
        @csrf @method('DELETE')
        <button type="submit" style="background:#fff;color:#111827;border:0;border-radius:9999px;padding:4px 12px;cursor:pointer">Exit preview</button>
    </form>
</div>
<script>if (window.self !== window.top) document.getElementById('theme-preview-bar').style.display = 'none';</script>
@endif
