{{-- Checkout: shipping information form (name, email, phone, address, city, state, ZIP, country). Shared by every theme; field names and scripts must stay as they are. Replace at themes/<slug>/checkout/blocks/shipping-info.blade.php --}}
                <div class="bg-white rounded-xl shadow-sm p-6" :class="{ 'opacity-50 pointer-events-none': {{ ($guestOtpRequired ?? false) ? 'true' : 'false' }} && !emailVerified }">
                    <h3 class="text-lg font-bold mb-4">Shipping Information</h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                            <input type="text" name="shipping_name" value="{{ old('shipping_name', auth()->user()->name ?? '') }}" required class="w-full px-4 py-2 border rounded-lg focus:ring-primary-500 focus:border-primary-500">
                            @error('shipping_name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <input type="email" name="shipping_email" 
                                   value="{{ old('shipping_email', auth()->user()->email ?? session('guest_verified_email', '')) }}" 
                                   required 
                                   @if($guestOtpRequired ?? false) x-model="guestEmail" :readonly="emailVerified" @endif
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-primary-500 focus:border-primary-500 @if($guestOtpRequired ?? false) read-only:bg-gray-100 @endif">
                            @error('shipping_email')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                            <input type="text" name="shipping_phone" value="{{ old('shipping_phone', auth()->user()->phone ?? '') }}" class="w-full px-4 py-2 border rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address *</label>
                            <textarea name="shipping_address" rows="2" required class="w-full px-4 py-2 border rounded-lg focus:ring-primary-500 focus:border-primary-500">{{ old('shipping_address', auth()->user()->address ?? '') }}</textarea>
                            @error('shipping_address')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">City *</label>
                            <input type="text" name="shipping_city" value="{{ old('shipping_city', auth()->user()->city ?? '') }}" required class="w-full px-4 py-2 border rounded-lg focus:ring-primary-500 focus:border-primary-500">
                            @error('shipping_city')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                            <input type="text" name="shipping_state" value="{{ old('shipping_state', auth()->user()->state ?? '') }}" class="w-full px-4 py-2 border rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ZIP Code *</label>
                            <input type="text" name="shipping_zip" value="{{ old('shipping_zip', auth()->user()->zip_code ?? '') }}" required class="w-full px-4 py-2 border rounded-lg focus:ring-primary-500 focus:border-primary-500">
                            @error('shipping_zip')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Country *</label>
                            @php
                                $singleCountryMode = setting('shipping.single_country_mode');
                                $defaultCountry = setting('shipping.default_country', 'US');
                                $allowedCountries = json_decode(setting('shipping.allowed_countries', '[]'), true) ?: [];
                                $countries = [
                                    'AF' => 'Afghanistan', 'AL' => 'Albania', 'DZ' => 'Algeria', 'AD' => 'Andorra',
                                    'AO' => 'Angola', 'AR' => 'Argentina', 'AM' => 'Armenia', 'AU' => 'Australia', 
                                    'AT' => 'Austria', 'AZ' => 'Azerbaijan', 'BS' => 'Bahamas', 'BH' => 'Bahrain', 
                                    'BD' => 'Bangladesh', 'BB' => 'Barbados', 'BY' => 'Belarus', 'BE' => 'Belgium', 
                                    'BZ' => 'Belize', 'BJ' => 'Benin', 'BT' => 'Bhutan', 'BO' => 'Bolivia', 
                                    'BA' => 'Bosnia and Herzegovina', 'BW' => 'Botswana', 'BR' => 'Brazil', 
                                    'BN' => 'Brunei', 'BG' => 'Bulgaria', 'BF' => 'Burkina Faso', 'BI' => 'Burundi', 
                                    'KH' => 'Cambodia', 'CM' => 'Cameroon', 'CA' => 'Canada', 'CV' => 'Cape Verde', 
                                    'CF' => 'Central African Republic', 'TD' => 'Chad', 'CL' => 'Chile', 'CN' => 'China', 
                                    'CO' => 'Colombia', 'KM' => 'Comoros', 'CG' => 'Congo', 'CR' => 'Costa Rica', 
                                    'HR' => 'Croatia', 'CU' => 'Cuba', 'CY' => 'Cyprus', 'CZ' => 'Czech Republic', 
                                    'DK' => 'Denmark', 'DJ' => 'Djibouti', 'DM' => 'Dominica', 'DO' => 'Dominican Republic', 
                                    'EC' => 'Ecuador', 'EG' => 'Egypt', 'SV' => 'El Salvador', 'GQ' => 'Equatorial Guinea', 
                                    'ER' => 'Eritrea', 'EE' => 'Estonia', 'ET' => 'Ethiopia', 'FJ' => 'Fiji', 
                                    'FI' => 'Finland', 'FR' => 'France', 'GA' => 'Gabon', 'GM' => 'Gambia', 
                                    'GE' => 'Georgia', 'DE' => 'Germany', 'GH' => 'Ghana', 'GR' => 'Greece', 
                                    'GD' => 'Grenada', 'GT' => 'Guatemala', 'GN' => 'Guinea', 'GW' => 'Guinea-Bissau', 
                                    'GY' => 'Guyana', 'HT' => 'Haiti', 'HN' => 'Honduras', 'HK' => 'Hong Kong', 
                                    'HU' => 'Hungary', 'IS' => 'Iceland', 'IN' => 'India', 'ID' => 'Indonesia', 
                                    'IR' => 'Iran', 'IQ' => 'Iraq', 'IE' => 'Ireland', 'IL' => 'Israel', 'IT' => 'Italy', 
                                    'JM' => 'Jamaica', 'JP' => 'Japan', 'JO' => 'Jordan', 'KZ' => 'Kazakhstan', 
                                    'KE' => 'Kenya', 'KI' => 'Kiribati', 'KP' => 'North Korea', 'KR' => 'South Korea', 
                                    'KW' => 'Kuwait', 'KG' => 'Kyrgyzstan', 'LA' => 'Laos', 'LV' => 'Latvia', 
                                    'LB' => 'Lebanon', 'LS' => 'Lesotho', 'LR' => 'Liberia', 'LY' => 'Libya', 
                                    'LI' => 'Liechtenstein', 'LT' => 'Lithuania', 'LU' => 'Luxembourg', 'MO' => 'Macau', 
                                    'MK' => 'North Macedonia', 'MG' => 'Madagascar', 'MW' => 'Malawi', 'MY' => 'Malaysia', 
                                    'MV' => 'Maldives', 'ML' => 'Mali', 'MT' => 'Malta', 'MH' => 'Marshall Islands', 
                                    'MR' => 'Mauritania', 'MU' => 'Mauritius', 'MX' => 'Mexico', 'FM' => 'Micronesia', 
                                    'MD' => 'Moldova', 'MC' => 'Monaco', 'MN' => 'Mongolia', 'ME' => 'Montenegro', 
                                    'MA' => 'Morocco', 'MZ' => 'Mozambique', 'MM' => 'Myanmar', 'NA' => 'Namibia', 
                                    'NR' => 'Nauru', 'NP' => 'Nepal', 'NL' => 'Netherlands', 'NZ' => 'New Zealand', 
                                    'NI' => 'Nicaragua', 'NE' => 'Niger', 'NG' => 'Nigeria', 'NO' => 'Norway', 
                                    'OM' => 'Oman', 'PK' => 'Pakistan', 'PW' => 'Palau', 'PS' => 'Palestine', 
                                    'PA' => 'Panama', 'PG' => 'Papua New Guinea', 'PY' => 'Paraguay', 'PE' => 'Peru', 
                                    'PH' => 'Philippines', 'PL' => 'Poland', 'PT' => 'Portugal', 'PR' => 'Puerto Rico', 
                                    'QA' => 'Qatar', 'RO' => 'Romania', 'RU' => 'Russia', 'RW' => 'Rwanda', 
                                    'SA' => 'Saudi Arabia', 'SN' => 'Senegal', 'RS' => 'Serbia', 'SC' => 'Seychelles', 
                                    'SL' => 'Sierra Leone', 'SG' => 'Singapore', 'SK' => 'Slovakia', 'SI' => 'Slovenia', 
                                    'SB' => 'Solomon Islands', 'SO' => 'Somalia', 'ZA' => 'South Africa', 
                                    'SS' => 'South Sudan', 'ES' => 'Spain', 'LK' => 'Sri Lanka', 'SD' => 'Sudan', 
                                    'SR' => 'Suriname', 'SZ' => 'Eswatini', 'SE' => 'Sweden', 'CH' => 'Switzerland', 
                                    'SY' => 'Syria', 'TW' => 'Taiwan', 'TJ' => 'Tajikistan', 'TZ' => 'Tanzania', 
                                    'TH' => 'Thailand', 'TL' => 'Timor-Leste', 'TG' => 'Togo', 'TO' => 'Tonga', 
                                    'TT' => 'Trinidad and Tobago', 'TN' => 'Tunisia', 'TR' => 'Turkey', 
                                    'TM' => 'Turkmenistan', 'TV' => 'Tuvalu', 'UG' => 'Uganda', 'UA' => 'Ukraine', 
                                    'AE' => 'United Arab Emirates', 'GB' => 'United Kingdom', 'US' => 'United States', 
                                    'UY' => 'Uruguay', 'UZ' => 'Uzbekistan', 'VU' => 'Vanuatu', 'VA' => 'Vatican City', 
                                    'VE' => 'Venezuela', 'VN' => 'Vietnam', 'YE' => 'Yemen', 'ZM' => 'Zambia', 'ZW' => 'Zimbabwe'
                                ];
                                asort($countries);
                                
                                // Get user's current country or default
                                $userCountry = auth()->user()->country ?? null;
                                $selectedCountry = old('shipping_country', $userCountry);
                                
                                // If no user country, use default
                                if (!$selectedCountry) {
                                    $selectedCountry = $countries[$defaultCountry] ?? 'United States';
                                }
                            @endphp
                            
                            @if($singleCountryMode)
                                {{-- Single country mode - locked to default country --}}
                                <input type="text" name="shipping_country" value="{{ $countries[$defaultCountry] ?? 'United States' }}" 
                                       readonly class="w-full px-4 py-2 border rounded-lg bg-gray-100 text-gray-600 cursor-not-allowed">
                                <p class="text-xs text-gray-500 mt-1"><i class="fas fa-info-circle mr-1"></i>We currently only ship within {{ $countries[$defaultCountry] ?? 'United States' }}</p>
                            @else
                                {{-- Multiple countries allowed --}}
                                <select name="shipping_country" required class="w-full px-4 py-2 border rounded-lg focus:ring-primary-500 focus:border-primary-500">
                                    @if(empty($allowedCountries))
                                        {{-- All countries allowed --}}
                                        @foreach($countries as $code => $name)
                                            <option value="{{ $name }}" {{ $selectedCountry == $name ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    @else
                                        {{-- Only allowed countries --}}
                                        @foreach($countries as $code => $name)
                                            @if(in_array($code, $allowedCountries))
                                                <option value="{{ $name }}" {{ $selectedCountry == $name ? 'selected' : '' }}>{{ $name }}</option>
                                            @endif
                                        @endforeach
                                    @endif
                                </select>
                            @endif
                            @error('shipping_country')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
