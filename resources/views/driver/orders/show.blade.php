@extends('layouts.driver')

@section('title', 'Order ' . $order->order_number)

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <a href="{{ route('driver.orders.index') }}" class="text-gray-500 hover:text-gray-700 mb-2 inline-block">
            <i class="fas fa-arrow-left mr-1"></i> Back to Deliveries
        </a>
        <h1 class="text-2xl font-bold text-gray-800">{{ $order->order_number }}</h1>
    </div>
    <div class="flex gap-2">
        @if($order->shipping_phone)
            <a href="tel:{{ $order->shipping_phone }}" class="px-4 py-2 bg-driver-600 text-white rounded-lg hover:bg-driver-700">
                <i class="fas fa-phone mr-1"></i> Call Customer
            </a>
        @endif
        <a href="https://maps.google.com/?q={{ urlencode($order->shipping_address . ', ' . $order->shipping_city . ', ' . $order->shipping_country) }}" 
           target="_blank" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <i class="fas fa-directions mr-1"></i> Navigate
        </a>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <!-- Status Update Card -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-bold text-lg mb-4">Update Delivery Status</h3>
            
            <!-- Progress Steps -->
            <div class="flex items-center justify-between mb-6">
                @php
                    $steps = [
                        ['key' => 'assigned', 'label' => 'Assigned', 'icon' => 'fa-clipboard-check'],
                        ['key' => 'picked_up', 'label' => 'Picked Up', 'icon' => 'fa-hand-holding-box'],
                        ['key' => 'shipped', 'label' => 'In Transit', 'icon' => 'fa-truck'],
                        ['key' => 'delivered', 'label' => 'Delivered', 'icon' => 'fa-box-open'],
                    ];
                    $currentStep = 0;
                    if ($order->driver_assigned_at) $currentStep = 1;
                    if ($order->picked_up_at) $currentStep = 2;
                    if ($order->status === 'shipped') $currentStep = 2;
                    if ($order->status === 'delivered') $currentStep = 4;
                @endphp
                
                @foreach($steps as $index => $step)
                    <div class="flex flex-col items-center flex-1">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center 
                            {{ $index < $currentStep ? 'bg-green-500 text-white' : ($index === $currentStep ? 'bg-driver-500 text-white ring-4 ring-driver-200' : 'bg-gray-200 text-gray-400') }}">
                            @if($index < $currentStep)
                                <i class="fas fa-check"></i>
                            @else
                                <i class="fas {{ $step['icon'] }}"></i>
                            @endif
                        </div>
                        <span class="text-xs mt-2 {{ $index <= $currentStep ? 'text-gray-700 font-medium' : 'text-gray-400' }}">
                            {{ $step['label'] }}
                        </span>
                    </div>
                    @if($index < count($steps) - 1)
                        <div class="flex-1 h-1 mx-2 {{ $index < $currentStep ? 'bg-green-500' : 'bg-gray-200' }}"></div>
                    @endif
                @endforeach
            </div>

            @if($order->status !== 'delivered')
                @if(!$order->picked_up_at)
                    <form action="{{ route('driver.orders.update-status', $order) }}" method="POST" class="mb-4">
                        @csrf
                        @method('PATCH')
                        <button type="submit" name="status" value="picked_up" 
                                class="w-full p-4 border-2 border-yellow-300 bg-yellow-50 rounded-xl hover:bg-yellow-100 text-center">
                            <i class="fas fa-hand-holding-box text-2xl text-yellow-600 mb-2"></i>
                            <p class="font-medium text-yellow-700">Mark Picked Up</p>
                        </button>
                    </form>
                @else
                    <!-- Complete Delivery Form with Proof -->
                    <form action="{{ route('driver.orders.complete', $order) }}" method="POST" enctype="multipart/form-data" 
                          x-data="deliveryProof()" class="space-y-4">
                        @csrf
                        
                        <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                            <h4 class="font-medium text-green-800 mb-3">
                                <i class="fas fa-camera mr-1"></i> Delivery Proof (Optional)
                            </h4>
                            
                            <!-- Photo Upload -->
                            <div class="mb-4">
                                <label class="block text-sm text-gray-700 mb-2">Photo of Delivered Package</label>
                                <div class="relative">
                                    <input type="file" name="proof_photo" accept="image/*" capture="environment"
                                           @change="previewPhoto($event)"
                                           class="hidden" id="proof_photo">
                                    <label for="proof_photo" 
                                           class="flex items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-driver-500 bg-white">
                                        <template x-if="!photoPreview">
                                            <div class="text-center">
                                                <i class="fas fa-camera text-2xl text-gray-400 mb-2"></i>
                                                <p class="text-sm text-gray-500">Tap to take photo</p>
                                            </div>
                                        </template>
                                        <template x-if="photoPreview">
                                            <img :src="photoPreview" class="h-full object-contain rounded-lg">
                                        </template>
                                    </label>
                                </div>
                            </div>

                            <!-- Signature Pad -->
                            <div class="mb-4">
                                <label class="block text-sm text-gray-700 mb-2">Customer Signature</label>
                                <div class="border rounded-lg bg-white p-2">
                                    <canvas id="signaturePad" class="w-full h-24 border rounded" 
                                            style="touch-action: none;"></canvas>
                                    <input type="hidden" name="signature" x-model="signatureData">
                                    <button type="button" @click="clearSignature()" 
                                            class="mt-2 text-sm text-gray-500 hover:text-gray-700">
                                        <i class="fas fa-eraser mr-1"></i> Clear Signature
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Note (Optional)</label>
                            <textarea name="driver_notes" rows="2" placeholder="e.g., Left at front door..."
                                      class="w-full px-4 py-2 border rounded-lg focus:ring-driver-500 focus:border-driver-500"></textarea>
                        </div>

                        <button type="submit" @click="saveSignature()"
                                class="w-full p-4 bg-green-600 text-white rounded-xl hover:bg-green-700 font-medium">
                            <i class="fas fa-check-circle mr-2"></i> Complete Delivery
                        </button>
                    </form>
                @endif
            @else
                <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-center">
                    <i class="fas fa-check-circle text-3xl text-green-500 mb-2"></i>
                    <p class="font-medium text-green-700">This order has been delivered</p>
                    <p class="text-sm text-green-600">{{ $order->delivered_at->format('M d, Y h:i A') }}</p>
                </div>
                
                @if($order->delivery_proof_photo || $order->delivery_signature)
                    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <h4 class="font-medium text-gray-700 mb-3">Delivery Proof</h4>
                        @if($order->delivery_proof_photo)
                            <div class="mb-3">
                                <p class="text-sm text-gray-500 mb-1">Photo</p>
                                <img src="{{ Storage::url($order->delivery_proof_photo) }}" 
                                     class="w-full max-w-xs rounded-lg border">
                            </div>
                        @endif
                        @if($order->delivery_signature)
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Signature</p>
                                <img src="{{ $order->delivery_signature }}" 
                                     class="bg-white border rounded-lg p-2 max-w-xs">
                            </div>
                        @endif
                    </div>
                @endif
            @endif
        </div>

        <!-- Order Items -->
        <div class="bg-white rounded-xl shadow-sm">
            <div class="p-4 border-b">
                <h3 class="font-bold">Order Items ({{ $order->items->count() }})</h3>
            </div>
            <div class="divide-y">
                @foreach($order->items as $item)
                    <div class="p-4 flex gap-4">
                        <div class="w-16 h-16 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                            @if($item->product && $item->product->image)
                                <img src="{{ $item->product->image_url }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <i class="fas fa-box text-gray-400"></i>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1">
                            <p class="font-medium">{{ $item->product_name }}</p>
                            <p class="text-sm text-gray-500">Qty: {{ $item->quantity }}</p>
                        </div>
                        <p class="font-semibold">{{ format_currency($item->total) }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Driver Notes -->
        @if($order->driver_notes)
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-bold mb-3">Delivery Notes</h3>
                <div class="bg-gray-50 rounded-lg p-4 text-sm whitespace-pre-wrap">{{ $order->driver_notes }}</div>
            </div>
        @endif
    </div>

    <div class="space-y-6">
        <!-- Customer Info -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-bold mb-4">Customer Information</h3>
            <div class="space-y-3">
                <div>
                    <p class="text-sm text-gray-500">Name</p>
                    <p class="font-medium">{{ $order->shipping_name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Phone</p>
                    <a href="tel:{{ $order->shipping_phone }}" class="font-medium text-driver-600 hover:underline">
                        {{ $order->shipping_phone }}
                    </a>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Email</p>
                    <p class="font-medium">{{ $order->shipping_email }}</p>
                </div>
            </div>
        </div>

        <!-- Delivery Address -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-bold mb-4">Delivery Address</h3>
            <div class="text-gray-700">
                <p>{{ $order->shipping_address }}</p>
                <p>{{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_zip }}</p>
                <p>{{ $order->shipping_country }}</p>
            </div>
            <a href="https://maps.google.com/?q={{ urlencode($order->shipping_address . ', ' . $order->shipping_city) }}" 
               target="_blank"
               class="mt-4 w-full block text-center px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200">
                <i class="fas fa-map-marked-alt mr-1"></i> Open in Maps
            </a>
        </div>

        <!-- Payment Info -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-bold mb-4">Payment</h3>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-500">Method</span>
                    <span class="font-medium">
                        @if($order->payment_method === 'cod')
                            <span class="text-orange-600"><i class="fas fa-money-bill mr-1"></i>Cash on Delivery</span>
                        @else
                            <span class="text-green-600"><i class="fas fa-credit-card mr-1"></i>Card (Paid)</span>
                        @endif
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Total</span>
                    <span class="font-bold text-lg">{{ format_currency($order->total) }}</span>
                </div>
                @if($order->payment_method === 'cod')
                    <div class="mt-3 p-3 bg-orange-50 border border-orange-200 rounded-lg">
                        <p class="text-sm text-orange-700 font-medium">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            Collect {{ format_currency($order->total) }} from customer
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Order Notes -->
        @if($order->notes)
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-bold mb-3">Customer Notes</h3>
                <p class="text-gray-600 text-sm">{{ $order->notes }}</p>
            </div>
        @endif

        <!-- Earnings Info -->
        @if($order->status === 'delivered' && $order->driver_earnings > 0)
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-bold mb-3">Your Earnings</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Delivery Commission</span>
                        <span class="font-medium">{{ format_currency($order->driver_earnings - ($order->driver_tip ?? 0)) }}</span>
                    </div>
                    @if($order->driver_tip > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-500">Tip</span>
                            <span class="font-medium text-purple-600">{{ format_currency($order->driver_tip) }}</span>
                        </div>
                    @endif
                    <hr>
                    <div class="flex justify-between font-bold">
                        <span>Total</span>
                        <span class="text-green-600">{{ format_currency($order->driver_earnings) }}</span>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
function deliveryProof() {
    return {
        photoPreview: null,
        signatureData: '',
        signaturePad: null,
        
        init() {
            const canvas = document.getElementById('signaturePad');
            if (canvas) {
                this.signaturePad = new SignaturePad(canvas, {
                    backgroundColor: 'rgb(255, 255, 255)'
                });
                
                // Resize canvas
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                canvas.getContext("2d").scale(ratio, ratio);
            }
        },
        
        previewPhoto(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.photoPreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },
        
        clearSignature() {
            if (this.signaturePad) {
                this.signaturePad.clear();
            }
        },
        
        saveSignature() {
            if (this.signaturePad && !this.signaturePad.isEmpty()) {
                this.signatureData = this.signaturePad.toDataURL();
            }
        }
    }
}
</script>
@endpush
@endsection
