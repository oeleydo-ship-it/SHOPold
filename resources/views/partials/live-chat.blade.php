{{-- Third-party live chat (Tawk / Crisp / Intercom / custom), Settings > Features. Shared by the Blade layout and the React storefront template. --}}
@if(setting('features.live_chat_enabled'))
    @php
        $chatProvider = setting('features.chat_provider', 'custom');
    @endphp
    
    @if($chatProvider === 'tawk')
        @php
            $tawkPropertyId = setting('features.tawk_property_id', '');
            $tawkWidgetId = setting('features.tawk_widget_id', 'default');
        @endphp
        @if(!empty($tawkPropertyId))
        <!--Start of Tawk.to Script-->
        <script type="text/javascript">
        var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
        (function(){
        var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
        s1.async=true;
        s1.src='https://embed.tawk.to/{{ $tawkPropertyId }}/{{ $tawkWidgetId }}';
        s1.charset='UTF-8';
        s1.setAttribute('crossorigin','*');
        s0.parentNode.insertBefore(s1,s0);
        })();
        </script>
        <!--End of Tawk.to Script-->
        @endif
    @elseif($chatProvider === 'crisp')
        @php
            $crispWebsiteId = setting('features.crisp_website_id', '');
        @endphp
        @if(!empty($crispWebsiteId))
        <!-- Crisp Chat Widget -->
        <script type="text/javascript">
        window.$crisp=[];window.CRISP_WEBSITE_ID="{{ $crispWebsiteId }}";
        (function(){
        d=document;s=d.createElement("script");
        s.src="https://client.crisp.chat/l.js";
        s.async=1;d.getElementsByTagName("head")[0].appendChild(s);
        })();
        </script>
        @endif
    @elseif($chatProvider === 'intercom')
        @php
            $intercomAppId = setting('features.intercom_app_id', '');
        @endphp
        @if(!empty($intercomAppId))
        <!-- Intercom Chat Widget -->
        <script>
        window.intercomSettings = {
            api_base: "https://api-iam.intercom.io",
            app_id: "{{ $intercomAppId }}"
        };
        </script>
        <script>
        (function(){var w=window;var ic=w.Intercom;if(typeof ic==="function"){ic('reattach_activator');ic('update',w.intercomSettings);}else{var d=document;var i=function(){i.c(arguments);};i.q=[];i.c=function(args){i.q.push(args);};w.Intercom=i;var l=function(){var s=d.createElement('script');s.type='text/javascript';s.async=true;s.src='https://widget.intercom.io/widget/{{ $intercomAppId }}';var x=d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s,x);};if(document.readyState==='complete'){l();}else if(w.attachEvent){w.attachEvent('onload',l);}else{w.addEventListener('load',l,false);}}})();
        </script>
        @endif
    @elseif($chatProvider === 'custom')
        @php
            $customChatCode = setting('features.custom_chat_code', '');
        @endphp
        @if(!empty($customChatCode))
        <!-- Custom Chat Widget -->
        {!! $customChatCode !!}
        @endif
    @endif
@endif
