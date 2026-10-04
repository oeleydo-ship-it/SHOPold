{{-- Shop page: Page styles (pushed to the layout head). Shared by every theme; replace at themes/<slug>/shop/listing/styles.blade.php --}}
@push('styles')
<style>
    .shop-hero { background: linear-gradient(135deg, rgba(37, 99, 235, 0.03) 0%, rgba(37, 99, 235, 0.08) 100%); }
    .product-grid-enter { animation: fadeInUp 0.4s ease-out forwards; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
    
    /* Prevent image flash on page load */
    .shop-product-grid img {
        max-width: 100%;
        height: auto;
        aspect-ratio: 1/1;
        object-fit: contain;
    }
    .shop-product-grid .aspect-square {
        aspect-ratio: 1/1;
    }
</style>
@endpush
