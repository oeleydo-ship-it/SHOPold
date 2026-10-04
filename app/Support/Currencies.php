<?php

namespace App\Support;

/** Currency code => name and symbol, used by the payment settings screen. */
class Currencies
{
    public static function all(): array
    {
        return [
                    'USD' => ['name' => 'US Dollar', 'symbol' => '$'],
                    'EUR' => ['name' => 'Euro', 'symbol' => '€'],
                    'GBP' => ['name' => 'British Pound', 'symbol' => '£'],
                    'AED' => ['name' => 'UAE Dirham', 'symbol' => 'د.إ'],
                    'INR' => ['name' => 'Indian Rupee', 'symbol' => '₹'],
                    'AUD' => ['name' => 'Australian Dollar', 'symbol' => 'A$'],
                    'CAD' => ['name' => 'Canadian Dollar', 'symbol' => 'C$'],
                    'JPY' => ['name' => 'Japanese Yen', 'symbol' => '¥'],
                    'CNY' => ['name' => 'Chinese Yuan', 'symbol' => '¥'],
                    'CHF' => ['name' => 'Swiss Franc', 'symbol' => 'CHF'],
                    'SGD' => ['name' => 'Singapore Dollar', 'symbol' => 'S$'],
                    'HKD' => ['name' => 'Hong Kong Dollar', 'symbol' => 'HK$'],
                    'NZD' => ['name' => 'New Zealand Dollar', 'symbol' => 'NZ$'],
                    'SEK' => ['name' => 'Swedish Krona', 'symbol' => 'kr'],
                    'NOK' => ['name' => 'Norwegian Krone', 'symbol' => 'kr'],
                    'DKK' => ['name' => 'Danish Krone', 'symbol' => 'kr'],
                    'ZAR' => ['name' => 'South African Rand', 'symbol' => 'R'],
                    'MXN' => ['name' => 'Mexican Peso', 'symbol' => '$'],
                    'BRL' => ['name' => 'Brazilian Real', 'symbol' => 'R$'],
                    'PLN' => ['name' => 'Polish Zloty', 'symbol' => 'zł'],
                    'THB' => ['name' => 'Thai Baht', 'symbol' => '฿'],
                    'MYR' => ['name' => 'Malaysian Ringgit', 'symbol' => 'RM'],
                    'PHP' => ['name' => 'Philippine Peso', 'symbol' => '₱'],
                    'IDR' => ['name' => 'Indonesian Rupiah', 'symbol' => 'Rp'],
                    'KRW' => ['name' => 'South Korean Won', 'symbol' => '₩'],
                    'TRY' => ['name' => 'Turkish Lira', 'symbol' => '₺'],
                    'RUB' => ['name' => 'Russian Ruble', 'symbol' => '₽'],
                    'SAR' => ['name' => 'Saudi Riyal', 'symbol' => '﷼'],
                    'QAR' => ['name' => 'Qatari Riyal', 'symbol' => '﷼'],
                    'KWD' => ['name' => 'Kuwaiti Dinar', 'symbol' => 'د.ك'],
                    'BHD' => ['name' => 'Bahraini Dinar', 'symbol' => '.د.ب'],
                    'OMR' => ['name' => 'Omani Rial', 'symbol' => '﷼'],
                    'EGP' => ['name' => 'Egyptian Pound', 'symbol' => '£'],
                    'PKR' => ['name' => 'Pakistani Rupee', 'symbol' => '₨'],
                    'BDT' => ['name' => 'Bangladeshi Taka', 'symbol' => '৳'],
                    'NGN' => ['name' => 'Nigerian Naira', 'symbol' => '₦'],
                    'KES' => ['name' => 'Kenyan Shilling', 'symbol' => 'KSh'],
                    'GHS' => ['name' => 'Ghanaian Cedi', 'symbol' => '₵'],
        ];
    }
}
