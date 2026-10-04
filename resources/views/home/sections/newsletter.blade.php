@module('newsletter')
@if($appSettings['homepage']['show_newsletter'] ?? $appSettings['theme']['show_newsletter'] ?? true)
    @php
        $newsletterBadge = $appSettings['homepage']['newsletter_badge'] ?? __('messages.stay_updated');
        $newsletterTitle = $appSettings['homepage']['newsletter_title'] ?? __('messages.subscribe_newsletter');
        $newsletterDesc = $appSettings['homepage']['newsletter_desc'] ?? __('messages.newsletter_desc');
        $newsletterPlaceholder = $appSettings['homepage']['newsletter_placeholder'] ?? __('messages.enter_email');
        $newsletterButton = $appSettings['homepage']['newsletter_button'] ?? __('messages.subscribe');
        $newsletterNote = $appSettings['homepage']['newsletter_note'] ?? __('messages.no_spam');

        $newsletterBgType = $newsletterBgType ?? ($appSettings['homepage']['newsletter_bg_type'] ?? 'color');
        $newsletterBgColor = $newsletterBgColor ?? ($appSettings['homepage']['newsletter_bg_color'] ?? '');
        $newsletterBgImage = $newsletterBgImage ?? ($appSettings['homepage']['newsletter_bg_image'] ?? '');
    @endphp
    <section class="py-8 md:py-12 homepage-body relative overflow-hidden"
        style="{{ $newsletterBgType === 'color' ? ($newsletterBgColor ? 'background-color: ' . $newsletterBgColor . ';' : 'background-color: #1f2937;') : '' }}">

        @if($newsletterBgType === 'image' && !empty($newsletterBgImage))
            <div class="absolute inset-0 z-0">
                <img src="{{ asset('storage/' . $newsletterBgImage) }}" alt="Newsletter Background" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-black/50"></div>
            </div>
        @endif

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            <span
                class="inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full text-white text-xs sm:text-sm font-medium mb-4 border border-white/20">
                <i class="fas fa-envelope mr-2"></i>{{ $newsletterBadge }}
            </span>
            <h2
                class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-3 md:mb-4 homepage-heading leading-tight">
                {{ $newsletterTitle }}
            </h2>
            <p class="text-white/80 mb-5 md:mb-6 max-w-2xl mx-auto text-sm sm:text-base md:text-lg leading-relaxed">
                {{ $newsletterDesc }}
            </p>

            <form id="newsletterForm" action="{{ route('newsletter.subscribe') }}" method="POST" class="max-w-xl mx-auto">
                @csrf
                <div
                    class="flex flex-col sm:flex-row gap-3 sm:gap-0 bg-white/10 backdrop-blur-sm p-2 rounded-2xl sm:rounded-full border border-white/20">
                    <div class="relative flex-1">
                        <i class="fas fa-envelope absolute left-5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="email" name="email" id="newsletterEmail" placeholder="{{ $newsletterPlaceholder }}" required
                            class="w-full pl-12 pr-4 py-3 bg-white rounded-xl sm:rounded-full focus:ring-2 focus:ring-primary-300 border-0 text-gray-900 placeholder-gray-400 text-sm sm:text-base">
                    </div>
                    <button type="submit" id="newsletterBtn"
                        class="px-6 sm:px-8 py-3 bg-primary-600 text-white font-semibold rounded-xl sm:rounded-full hover:bg-primary-700 transition-all duration-300 shadow-lg hover:shadow-xl flex items-center justify-center gap-2 group sm:ml-2">
                        <span id="newsletterBtnText">{{ $newsletterButton }}</span>
                        <i id="newsletterBtnIcon" class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </div>
                <div id="newsletterMessage" class="mt-4 hidden"></div>
            </form>
            <script>
                document.getElementById('newsletterForm').addEventListener('submit', function(e) {
                    e.preventDefault();
                    const btn = document.getElementById('newsletterBtn');
                    const btnText = document.getElementById('newsletterBtnText');
                    const btnIcon = document.getElementById('newsletterBtnIcon');
                    const msgDiv = document.getElementById('newsletterMessage');
                    const email = document.getElementById('newsletterEmail').value;
                    
                    btn.disabled = true;
                    btnText.textContent = '{{ __("messages.please_wait") ?? "Please wait..." }}';
                    btnIcon.className = 'fas fa-spinner fa-spin';
                    
                    fetch(this.action, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ email: email })
                    })
                    .then(response => response.json())
                    .then(data => {
                        msgDiv.classList.remove('hidden');
                        if (data.success) {
                            msgDiv.innerHTML = '<div class="text-green-400 flex items-center justify-center gap-2"><i class="fas fa-check-circle"></i> ' + data.message + '</div>';
                            document.getElementById('newsletterEmail').value = '';
                        } else {
                            msgDiv.innerHTML = '<div class="text-red-400 flex items-center justify-center gap-2"><i class="fas fa-exclamation-circle"></i> ' + (data.message || 'Something went wrong') + '</div>';
                        }
                    })
                    .catch(error => {
                        msgDiv.classList.remove('hidden');
                        msgDiv.innerHTML = '<div class="text-red-400 flex items-center justify-center gap-2"><i class="fas fa-exclamation-circle"></i> {{ __("messages.error_occurred") ?? "An error occurred" }}</div>';
                    })
                    .finally(() => {
                        btn.disabled = false;
                        btnText.textContent = '{{ $newsletterButton }}';
                        btnIcon.className = 'fas fa-arrow-right group-hover:translate-x-1 transition-transform';
                        setTimeout(() => msgDiv.classList.add('hidden'), 5000);
                    });
                });
            </script>

            <div
                class="mt-4 md:mt-5 flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-6 text-white/60 text-xs sm:text-sm">
                <span class="flex items-center gap-2">
                    <i class="fas fa-check-circle text-green-400"></i>{{ $newsletterNote }}
                </span>
                <span class="flex items-center gap-2">
                    <i class="fas fa-gift text-yellow-400"></i>{{ __('messages.exclusive_offers') ?? 'Exclusive offers' }}
                </span>
                <span class="flex items-center gap-2">
                    <i class="fas fa-bell text-blue-400"></i>{{ __('messages.new_arrivals_alert') ?? 'New arrivals alert' }}
                </span>
            </div>
        </div>
    </section>
@endif
@endmodule
