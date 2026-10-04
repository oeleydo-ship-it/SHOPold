{{-- Checkout: payment method choices with gateway details, and the gateway scripts it pushes (Stripe and others). Shared by every theme; field names and scripts must stay as they are. Replace at themes/<slug>/checkout/blocks/payment.blade.php --}}
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-bold mb-4">Payment Method</h3>
                    <div class="space-y-3">
                        @php
                            $paymentService = app(\App\Services\PaymentGatewayService::class);
                            $paymentMethods = $paymentService->getAvailableMethods();
                            $firstMethod = true;
                        @endphp

                        @forelse($paymentMethods as $methodKey => $method)
                            <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:border-primary-500 transition">
                                <input type="radio" name="payment_method" value="{{ $methodKey }}" 
                                       {{ $firstMethod ? 'checked' : '' }} class="text-primary-600">
                                <div class="ml-3 flex-1">
                                    <div class="flex items-center">
                                        <i class="fas {{ $method['icon'] }} text-gray-500 mr-2"></i>
                                        <span class="font-medium">{{ $method['name'] }}</span>
                                        @if(isset($method['fee']) && $method['fee'] > 0)
                                            <span class="ml-2 text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded">+{{ format_currency($method['fee']) }} fee</span>
                                        @endif
                                    </div>
                                    <span class="block text-sm text-gray-500">{{ $method['description'] }}</span>
                                </div>
                            </label>
                            @php $firstMethod = false; @endphp
                        @empty
                            <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <p class="text-yellow-800"><i class="fas fa-exclamation-triangle mr-2"></i>No payment methods available. Please contact support.</p>
                            </div>
                        @endforelse

                        @if(isset($paymentMethods['stripe']))
                            <div id="stripe-card-element-container" class="hidden p-4 bg-gray-50 rounded-lg mt-2">
                                <h4 class="font-medium mb-3">Card Details</h4>
                                <div id="stripe-card-element" class="p-3 border rounded-lg bg-white"></div>
                                <div id="stripe-card-errors" class="text-red-500 text-sm mt-2" role="alert"></div>
                                <input type="hidden" name="stripe_payment_method" id="stripe-payment-method">
                            </div>
                        @endif

                        @if(isset($paymentMethods['bank_transfer']))
                            <div id="bank-transfer-details" class="hidden p-4 bg-gray-50 rounded-lg mt-2">
                                <h4 class="font-medium mb-2">Bank Transfer Details</h4>
                                @if(!empty($paymentMethods['bank_transfer']['bank_name']))
                                    <p class="text-sm"><strong>Bank:</strong> {{ $paymentMethods['bank_transfer']['bank_name'] }}</p>
                                @endif
                                @if(!empty($paymentMethods['bank_transfer']['account_name']))
                                    <p class="text-sm"><strong>Account Name:</strong> {{ $paymentMethods['bank_transfer']['account_name'] }}</p>
                                @endif
                                @if(!empty($paymentMethods['bank_transfer']['account_number']))
                                    <p class="text-sm"><strong>Account Number:</strong> {{ $paymentMethods['bank_transfer']['account_number'] }}</p>
                                @endif
                                @if(!empty($paymentMethods['bank_transfer']['routing_number']))
                                    <p class="text-sm"><strong>Routing/SWIFT:</strong> {{ $paymentMethods['bank_transfer']['routing_number'] }}</p>
                                @endif
                                @if(!empty($paymentMethods['bank_transfer']['instructions']))
                                    <p class="text-sm mt-2 text-gray-600">{{ $paymentMethods['bank_transfer']['instructions'] }}</p>
                                @endif
                            </div>
                        @endif

                        @if(isset($paymentMethods['check']))
                            <div id="check-details" class="hidden p-4 bg-gray-50 rounded-lg mt-2">
                                <h4 class="font-medium mb-2">Check / Money Order Details</h4>
                                @if(!empty($paymentMethods['check']['payable_to']))
                                    <p class="text-sm"><strong>Make Payable To:</strong> {{ $paymentMethods['check']['payable_to'] }}</p>
                                @endif
                                @if(!empty($paymentMethods['check']['mailing_address']))
                                    <p class="text-sm mt-2"><strong>Mail To:</strong><br>{{ nl2br(e($paymentMethods['check']['mailing_address'])) }}</p>
                                @endif
                            </div>
                        @endif

                        @if(isset($paymentMethods['paystack']))
                            <div id="paystack-details" class="hidden p-4 bg-gray-50 rounded-lg mt-2">
                                <p class="text-sm text-gray-600"><i class="fas fa-info-circle mr-1"></i> You will be redirected to Paystack to complete your payment securely.</p>
                            </div>
                        @endif

                        @if(isset($paymentMethods['flutterwave']))
                            <div id="flutterwave-details" class="hidden p-4 bg-gray-50 rounded-lg mt-2">
                                <p class="text-sm text-gray-600"><i class="fas fa-info-circle mr-1"></i> You will be redirected to Flutterwave to complete your payment securely.</p>
                            </div>
                        @endif

                        @if(isset($paymentMethods['razorpay']))
                            <div id="razorpay-details" class="hidden p-4 bg-gray-50 rounded-lg mt-2">
                                <p class="text-sm text-gray-600"><i class="fas fa-info-circle mr-1"></i> You will be redirected to Razorpay to complete your payment securely.</p>
                            </div>
                        @endif

                        @if(isset($paymentMethods['paytm']))
                            <div id="paytm-details" class="hidden p-4 bg-gray-50 rounded-lg mt-2">
                                <p class="text-sm text-gray-600"><i class="fas fa-info-circle mr-1"></i> You will be redirected to Paytm to complete your payment securely.</p>
                            </div>
                        @endif

                        @if(isset($paymentMethods['instamojo']))
                            <div id="instamojo-details" class="hidden p-4 bg-gray-50 rounded-lg mt-2">
                                <p class="text-sm text-gray-600"><i class="fas fa-info-circle mr-1"></i> You will be redirected to Instamojo to complete your payment securely.</p>
                            </div>
                        @endif

                        @if(isset($paymentMethods['payfast']))
                            <div id="payfast-details" class="hidden p-4 bg-gray-50 rounded-lg mt-2">
                                <p class="text-sm text-gray-600"><i class="fas fa-info-circle mr-1"></i> You will be redirected to PayFast to complete your payment securely.</p>
                            </div>
                        @endif

                        @if(isset($paymentMethods['mercadopago']))
                            <div id="mercadopago-details" class="hidden p-4 bg-gray-50 rounded-lg mt-2">
                                <p class="text-sm text-gray-600"><i class="fas fa-info-circle mr-1"></i> You will be redirected to Mercado Pago to complete your payment securely.</p>
                            </div>
                        @endif

                        @if(isset($paymentMethods['authorizenet']))
                            <div id="authorizenet-details" class="hidden p-4 bg-gray-50 rounded-lg mt-2">
                                <h4 class="font-medium mb-3">Card Details</h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Card Number</label>
                                        <input type="text" name="card_number" placeholder="1234 5678 9012 3456" class="w-full px-4 py-2 border rounded-lg">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Expiry</label>
                                        <input type="text" name="card_expiry" placeholder="MM/YY" class="w-full px-4 py-2 border rounded-lg">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">CVV</label>
                                        <input type="text" name="card_cvv" placeholder="123" class="w-full px-4 py-2 border rounded-lg">
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if(isset($paymentMethods['iyzico']))
                            <div id="iyzico-details" class="hidden p-4 bg-gray-50 rounded-lg mt-2">
                                <p class="text-sm text-gray-600"><i class="fas fa-info-circle mr-1"></i> You will be redirected to iyzico to complete your payment securely.</p>
                            </div>
                        @endif

                        @if(isset($paymentMethods['sslcommerz']))
                            <div id="sslcommerz-details" class="hidden p-4 bg-gray-50 rounded-lg mt-2">
                                <p class="text-sm text-gray-600"><i class="fas fa-info-circle mr-1"></i> You will be redirected to SSLCommerz to complete your payment securely.</p>
                            </div>
                        @endif

                        @if(isset($paymentMethods['bkash']))
                            <div id="bkash-details" class="hidden p-4 bg-gray-50 rounded-lg mt-2">
                                <p class="text-sm text-gray-600"><i class="fas fa-info-circle mr-1"></i> You will be redirected to bKash to complete your payment securely.</p>
                            </div>
                        @endif

                        @if(isset($paymentMethods['nagad']))
                            <div id="nagad-details" class="hidden p-4 bg-gray-50 rounded-lg mt-2">
                                <p class="text-sm text-gray-600"><i class="fas fa-info-circle mr-1"></i> You will be redirected to Nagad to complete your payment securely.</p>
                            </div>
                        @endif
                    </div>
                </div>

                @push('scripts')
                @if(isset($paymentMethods['stripe']))
                <script src="https://js.stripe.com/v3/"></script>
                @endif
                <script>
                    @if(isset($paymentMethods['stripe']))
                    // Initialize Stripe
                    const stripe = Stripe('{{ $paymentMethods["stripe"]["public_key"] }}');
                    const elements = stripe.elements();
                    const cardElement = elements.create('card', {
                        style: {
                            base: {
                                fontSize: '16px',
                                color: '#374151',
                                '::placeholder': {
                                    color: '#9CA3AF',
                                },
                            },
                            invalid: {
                                color: '#EF4444',
                            },
                        },
                    });
                    cardElement.mount('#stripe-card-element');

                    // Handle card errors
                    cardElement.on('change', function(event) {
                        const displayError = document.getElementById('stripe-card-errors');
                        if (event.error) {
                            displayError.textContent = event.error.message;
                        } else {
                            displayError.textContent = '';
                        }
                    });

                    // Handle form submission for Stripe
                    const checkoutForm = document.querySelector('form[action="{{ route("checkout.process") }}"]');
                    checkoutForm.addEventListener('submit', async function(event) {
                        const selectedPayment = document.querySelector('input[name="payment_method"]:checked');
                        if (selectedPayment && selectedPayment.value === 'stripe') {
                            event.preventDefault();
                            
                            const submitBtn = checkoutForm.querySelector('button[type="submit"]');
                            const originalText = submitBtn.innerHTML;
                            submitBtn.disabled = true;
                            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';

                            const {paymentMethod, error} = await stripe.createPaymentMethod({
                                type: 'card',
                                card: cardElement,
                            });

                            if (error) {
                                document.getElementById('stripe-card-errors').textContent = error.message;
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = originalText;
                            } else {
                                document.getElementById('stripe-payment-method').value = paymentMethod.id;
                                checkoutForm.submit();
                            }
                        }
                    });
                    @endif

                    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
                        radio.addEventListener('change', function() {
                            // Hide all payment details sections
                            const paymentDetails = [
                                'bank-transfer-details', 'stripe-card-element-container', 'check-details',
                                'paystack-details', 'flutterwave-details', 'razorpay-details', 'paytm-details',
                                'instamojo-details', 'payfast-details', 'mercadopago-details', 'authorizenet-details',
                                'iyzico-details', 'sslcommerz-details', 'bkash-details', 'nagad-details'
                            ];
                            
                            paymentDetails.forEach(id => {
                                const el = document.getElementById(id);
                                if (el) el.classList.add('hidden');
                            });
                            
                            // Show the selected payment method details
                            const detailsMap = {
                                'bank_transfer': 'bank-transfer-details',
                                'stripe': 'stripe-card-element-container',
                                'check': 'check-details',
                                'paystack': 'paystack-details',
                                'flutterwave': 'flutterwave-details',
                                'razorpay': 'razorpay-details',
                                'paytm': 'paytm-details',
                                'instamojo': 'instamojo-details',
                                'payfast': 'payfast-details',
                                'mercadopago': 'mercadopago-details',
                                'authorizenet': 'authorizenet-details',
                                'iyzico': 'iyzico-details',
                                'sslcommerz': 'sslcommerz-details',
                                'bkash': 'bkash-details',
                                'nagad': 'nagad-details'
                            };
                            
                            const detailsId = detailsMap[this.value];
                            if (detailsId) {
                                const el = document.getElementById(detailsId);
                                if (el) el.classList.remove('hidden');
                            }
                        });
                    });

                    // Show stripe card element if stripe is pre-selected
                    const preSelectedPayment = document.querySelector('input[name="payment_method"]:checked');
                    if (preSelectedPayment && preSelectedPayment.value === 'stripe') {
                        const stripeCard = document.getElementById('stripe-card-element-container');
                        if (stripeCard) {
                            stripeCard.classList.remove('hidden');
                        }
                    }
                    
                    // Handle country change to update shipping methods
                    const countrySelect = document.querySelector('select[name="shipping_country"]');
                    if (countrySelect) {
                        countrySelect.addEventListener('change', function() {
                            updateShippingMethods(this.value);
                        });
                    }
                    
                    // Handle shipping method selection styling and total update
                    function initShippingMethodListeners() {
                        document.querySelectorAll('.shipping-method-radio').forEach(radio => {
                            radio.addEventListener('change', function() {
                                // Update styling
                                document.querySelectorAll('.shipping-method-radio').forEach(r => {
                                    r.closest('label').classList.remove('border-primary-500', 'bg-primary-50');
                                });
                                this.closest('label').classList.add('border-primary-500', 'bg-primary-50');
                                
                                // Update total
                                updateOrderTotal(parseFloat(this.dataset.cost));
                            });
                        });
                    }
                    initShippingMethodListeners();
                    
                    // Handle delivery type change (shipping vs pickup)
                    document.querySelectorAll('input[name="delivery_type"]').forEach(radio => {
                        radio.addEventListener('change', function() {
                            if (this.value === 'pickup') {
                                // Set shipping cost to 0 for pickup
                                updateOrderTotal(0);
                            } else {
                                // Get currently selected shipping method cost
                                const selectedShipping = document.querySelector('.shipping-method-radio:checked');
                                if (selectedShipping) {
                                    updateOrderTotal(parseFloat(selectedShipping.dataset.cost));
                                }
                            }
                        });
                    });
                    
                    // Handle pickup location selection styling
                    document.querySelectorAll('input[name="pickup_location_id"]').forEach(radio => {
                        radio.addEventListener('change', function() {
                            document.querySelectorAll('input[name="pickup_location_id"]').forEach(r => {
                                r.closest('label').classList.remove('border-primary-500', 'bg-primary-50');
                            });
                            this.closest('label').classList.add('border-primary-500', 'bg-primary-50');
                        });
                    });
                    
                    function updateShippingMethods(country) {
                        const container = document.getElementById('shipping-methods-container');
                        const loading = document.getElementById('shipping-loading');
                        
                        container.classList.add('hidden');
                        loading.classList.remove('hidden');
                        
                        fetch('{{ route("checkout.shipping-methods") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ country: country })
                        })
                        .then(response => response.json())
                        .then(data => {
                            loading.classList.add('hidden');
                            container.classList.remove('hidden');
                            
                            if (data.has_methods && data.methods.length > 0) {
                                let html = '<div class="space-y-3">';
                                data.methods.forEach((method, index) => {
                                    const isFirst = index === 0;
                                    html += `
                                        <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:border-primary-500 transition ${isFirst ? 'border-primary-500 bg-primary-50' : ''}">
                                            <input type="radio" name="shipping_method" value="${method.id}" 
                                                   data-cost="${method.cost}"
                                                   ${isFirst ? 'checked' : ''} 
                                                   class="text-primary-600 shipping-method-radio">
                                            <div class="ml-3 flex-1">
                                                <div class="flex items-center justify-between">
                                                    <span class="font-medium">${method.name}</span>
                                                    <span class="font-semibold text-primary-600">${method.cost_formatted}</span>
                                                </div>
                                                ${method.description ? `<span class="block text-sm text-gray-500">${method.description}</span>` : ''}
                                                ${method.delivery_time ? `<span class="block text-xs text-gray-400 mt-1"><i class="fas fa-clock mr-1"></i>${method.delivery_time}</span>` : ''}
                                            </div>
                                        </label>
                                    `;
                                });
                                html += '</div>';
                                container.innerHTML = html;
                                
                                // Re-initialize listeners
                                initShippingMethodListeners();
                                
                                // Update order summary
                                document.getElementById('shipping-cost').textContent = data.shipping_formatted;
                                document.getElementById('tax-amount').textContent = data.tax_formatted;
                                document.getElementById('order-total').textContent = data.total_formatted;
                            } else {
                                container.innerHTML = `
                                    <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                        <p class="text-yellow-800"><i class="fas fa-exclamation-triangle mr-2"></i>No shipping methods available for your location. Please contact support.</p>
                                    </div>
                                    <input type="hidden" name="shipping_method" value="">
                                `;
                                // Update order summary with zero shipping
                                document.getElementById('shipping-cost').textContent = data.shipping_formatted;
                                document.getElementById('tax-amount').textContent = data.tax_formatted;
                                document.getElementById('order-total').textContent = data.total_formatted;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            loading.classList.add('hidden');
                            container.classList.remove('hidden');
                        });
                    }
                    
                    function updateOrderTotal(shippingCost) {
                        const subtotal = {{ $subtotal }};
                        const discount = {{ $discount }};
                        const taxRate = {{ (float) setting('payment.tax_rate', 10) / 100 }};
                        const tax = (subtotal - discount) * taxRate;
                        const total = subtotal - discount + shippingCost + tax;
                        
                        document.getElementById('shipping-cost').textContent = formatCurrency(shippingCost);
                        document.getElementById('order-total').textContent = formatCurrency(total);
                    }
                    
                    function formatCurrency(amount) {
                        const symbol = '{{ setting("payment.currency_symbol", setting("store.currency_symbol", "AED")) }}';
                        const position = '{{ setting("payment.currency_position", "before") }}';
                        const formatted = parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                        
                        if (position === 'after') {
                            return formatted + symbol;
                        }
                        return symbol + formatted;
                    }
                </script>
                @endpush
