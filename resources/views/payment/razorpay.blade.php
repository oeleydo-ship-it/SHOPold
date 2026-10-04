@extends('layouts.app')

@section('title', 'Complete Payment')

@section('content')
<div class="max-w-lg mx-auto px-4 py-16 text-center">
    <div class="bg-white rounded-xl shadow-sm p-8">
        <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-credit-card text-indigo-600 text-2xl"></i>
        </div>
        
        <h1 class="text-2xl font-bold mb-2">Complete Your Payment</h1>
        <p class="text-gray-600 mb-6">Order #{{ $order->order_number }}</p>
        
        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Amount to Pay</span>
                <span class="text-2xl font-bold text-primary-600">{{ format_currency($order->total) }}</span>
            </div>
        </div>
        
        <button id="razorpay-button" class="w-full py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
            <i class="fas fa-lock mr-2"></i>Pay with Razorpay
        </button>
        
        <p class="text-sm text-gray-500 mt-4">
            <i class="fas fa-shield-alt mr-1"></i>Secured by Razorpay
        </p>
    </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    var options = {
        "key": "{{ $keyId }}",
        "amount": "{{ $amount }}",
        "currency": "{{ $currency }}",
        "name": "{{ $storeName }}",
        "description": "Order #{{ $order->order_number }}",
        "order_id": "{{ $razorpayOrderId }}",
        "handler": function (response) {
            // Create form and submit
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("payment.razorpay.callback") }}';
            
            var csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            form.appendChild(csrfInput);
            
            var paymentIdInput = document.createElement('input');
            paymentIdInput.type = 'hidden';
            paymentIdInput.name = 'razorpay_payment_id';
            paymentIdInput.value = response.razorpay_payment_id;
            form.appendChild(paymentIdInput);
            
            var orderIdInput = document.createElement('input');
            orderIdInput.type = 'hidden';
            orderIdInput.name = 'razorpay_order_id';
            orderIdInput.value = response.razorpay_order_id;
            form.appendChild(orderIdInput);
            
            var signatureInput = document.createElement('input');
            signatureInput.type = 'hidden';
            signatureInput.name = 'razorpay_signature';
            signatureInput.value = response.razorpay_signature;
            form.appendChild(signatureInput);
            
            document.body.appendChild(form);
            form.submit();
        },
        "prefill": {
            "name": "{{ $order->shipping_name }}",
            "email": "{{ $order->shipping_email }}",
            "contact": "{{ $order->shipping_phone ?? '' }}"
        },
        "theme": {
            "color": "#4F46E5"
        },
        "modal": {
            "ondismiss": function() {
                window.location.href = '{{ route("payment.cancelled") }}';
            }
        }
    };
    
    var rzp = new Razorpay(options);
    
    document.getElementById('razorpay-button').onclick = function(e) {
        rzp.open();
        e.preventDefault();
    };
    
    // Auto-open on page load
    setTimeout(function() {
        rzp.open();
    }, 500);
</script>
@endsection
