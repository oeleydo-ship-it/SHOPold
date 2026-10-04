{{-- Checkout: page scripts (guest verification state, shipping recalculation). Shared by every theme; field names and scripts must stay as they are. Replace at themes/<slug>/checkout/blocks/scripts.blade.php --}}
@if($isGuest ?? false)
<script>
function checkoutForm() {
    return {
        guestEmail: '{{ session('guest_verified_email', '') }}',
        otpCode: '',
        otpSent: false,
        sendingOtp: false,
        verifyingOtp: false,
        emailVerified: {{ ($guestEmailVerified ?? false) ? 'true' : 'false' }},
        otpError: '',
        otpSuccess: '',
        otpExpiry: {{ setting('features.guest_otp_expiry', 10) }},
        resendCooldown: 0,
        
        async sendOtp() {
            if (!this.guestEmail || this.sendingOtp) return;
            
            this.sendingOtp = true;
            this.otpError = '';
            this.otpSuccess = '';
            
            try {
                const response = await fetch('{{ route('checkout.guest.send-otp') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email: this.guestEmail })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.otpSent = true;
                    this.otpSuccess = data.message;
                    this.otpExpiry = data.expiry;
                    this.startResendCooldown();
                } else {
                    this.otpError = data.message || 'Failed to send verification code';
                }
            } catch (error) {
                this.otpError = 'Network error. Please try again.';
            }
            
            this.sendingOtp = false;
        },
        
        async verifyOtp() {
            if (!this.otpCode || this.verifyingOtp) return;
            
            this.verifyingOtp = true;
            this.otpError = '';
            this.otpSuccess = '';
            
            try {
                const response = await fetch('{{ route('checkout.guest.verify-otp') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email: this.guestEmail, otp: this.otpCode })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.emailVerified = true;
                    this.otpSuccess = data.message;
                    // Update the email field in the form
                    const emailInput = document.querySelector('input[name="shipping_email"]');
                    if (emailInput) emailInput.value = this.guestEmail;
                } else {
                    this.otpError = data.message || 'Invalid verification code';
                    if (data.attempts_remaining !== undefined) {
                        this.otpError += ` (${data.attempts_remaining} attempts remaining)`;
                    }
                }
            } catch (error) {
                this.otpError = 'Network error. Please try again.';
            }
            
            this.verifyingOtp = false;
        },
        
        resendOtp() {
            if (this.resendCooldown > 0) return;
            this.otpSent = false;
            this.otpCode = '';
            this.sendOtp();
        },
        
        startResendCooldown() {
            this.resendCooldown = 60;
            const interval = setInterval(() => {
                this.resendCooldown--;
                if (this.resendCooldown <= 0) {
                    clearInterval(interval);
                }
            }, 1000);
        }
    }
}
</script>
@else
{{-- signed-in customers have no email step; the page root still calls checkoutForm() --}}
<script>
function checkoutForm() {
    return { emailVerified: true };
}
</script>
@endif
