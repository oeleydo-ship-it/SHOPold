{{-- Checkout: guest checkout notice and email verification (OTP) panel. Shared by every theme; field names and scripts must stay as they are. Replace at themes/<slug>/checkout/blocks/guest.blade.php --}}
    @if($isGuest ?? false)
    <!-- Guest Checkout Notice -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
        <div class="flex items-start gap-3">
            <i class="fas fa-user-clock text-blue-500 text-xl mt-0.5"></i>
            <div class="flex-1">
                <h3 class="font-semibold text-blue-900">Checking out as Guest</h3>
                <p class="text-sm text-blue-700">You're checking out without an account. <a href="{{ route('login') }}" class="underline hover:text-blue-900">Login</a> or <a href="{{ route('register') }}" class="underline hover:text-blue-900">create an account</a> to track your orders easily.</p>
            </div>
        </div>
    </div>
    
    @if($guestOtpRequired ?? false)
    <!-- Guest Email Verification -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6" x-show="!emailVerified">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                <i class="fas fa-envelope text-primary-600"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold">Verify Your Email</h3>
                <p class="text-sm text-gray-500">We need to verify your email before you can complete checkout</p>
            </div>
        </div>
        
        <div class="space-y-4">
            <!-- Email Input -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                <div class="flex gap-2">
                    <input type="email" x-model="guestEmail" :disabled="otpSent" 
                           placeholder="Enter your email"
                           class="flex-1 px-4 py-2 border rounded-lg focus:ring-primary-500 focus:border-primary-500 disabled:bg-gray-100">
                    <button type="button" @click="sendOtp()" :disabled="sendingOtp || !guestEmail || otpSent"
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <i class="fas fa-paper-plane" :class="sendingOtp ? 'fa-spin fa-spinner' : 'fa-paper-plane'"></i>
                        <span x-text="otpSent ? 'Code Sent' : 'Send Code'"></span>
                    </button>
                </div>
            </div>
            
            <!-- OTP Input -->
            <div x-show="otpSent" x-transition>
                <label class="block text-sm font-medium text-gray-700 mb-1">Verification Code *</label>
                <div class="flex gap-2">
                    <input type="text" x-model="otpCode" maxlength="6" placeholder="Enter 6-digit code"
                           class="flex-1 px-4 py-2 border rounded-lg focus:ring-primary-500 focus:border-primary-500 text-center text-2xl tracking-widest font-mono">
                    <button type="button" @click="verifyOtp()" :disabled="verifyingOtp || otpCode.length < 4"
                            class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <i :class="verifyingOtp ? 'fas fa-spin fa-spinner' : 'fas fa-check'"></i>
                        Verify
                    </button>
                </div>
                <div class="flex items-center justify-between mt-2">
                    <p class="text-sm text-gray-500">
                        <i class="fas fa-clock mr-1"></i> Code expires in <span x-text="otpExpiry"></span> minutes
                    </p>
                    <button type="button" @click="resendOtp()" :disabled="resendCooldown > 0" 
                            class="text-sm text-primary-600 hover:text-primary-700 disabled:text-gray-400">
                        <span x-show="resendCooldown > 0">Resend in <span x-text="resendCooldown"></span>s</span>
                        <span x-show="resendCooldown === 0">Resend Code</span>
                    </button>
                </div>
            </div>
            
            <!-- Error/Success Messages -->
            <div x-show="otpError" x-transition class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                <i class="fas fa-exclamation-circle mr-2"></i><span x-text="otpError"></span>
            </div>
            <div x-show="otpSuccess" x-transition class="p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                <i class="fas fa-check-circle mr-2"></i><span x-text="otpSuccess"></span>
            </div>
        </div>
    </div>
    
    <!-- Verified Badge -->
    <div x-show="emailVerified" x-transition class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
            <div>
                <h3 class="font-semibold text-green-900">Email Verified</h3>
                <p class="text-sm text-green-700">Your email <strong x-text="guestEmail"></strong> has been verified. You can now complete your order.</p>
            </div>
        </div>
    </div>
    @endif
    @endif
