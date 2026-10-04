{{-- Checkout: order notes field. Shared by every theme; field names and scripts must stay as they are. Replace at themes/<slug>/checkout/blocks/notes.blade.php --}}
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-bold mb-4">Order Notes (Optional)</h3>
                    <textarea name="notes" rows="3" placeholder="Special instructions for your order..." class="w-full px-4 py-2 border rounded-lg focus:ring-primary-500 focus:border-primary-500">{{ old('notes') }}</textarea>
                </div>
