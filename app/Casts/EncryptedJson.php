<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * An array stored encrypted (APP_KEY) as text. Reading also accepts legacy plain JSON, so rows written before
 * encryption keep working until `php artisan vendors:encrypt-payment-info` (or the next save) encrypts them.
 * Used for payout details: bank account numbers, PayPal email, Stripe account id.
 */
class EncryptedJson implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        $json = $value;
        if (!static::isPlainJson($value)) {
            try {
                $json = Crypt::decryptString($value);
            } catch (\Throwable $e) {
                report($e); // wrong APP_KEY or damaged value: treat as empty instead of breaking the page
                return null;
            }
        }

        $data = json_decode($json, true);

        return is_array($data) ? $data : null;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null || $value === [] || $value === '') {
            return [$key => null];
        }

        return [$key => Crypt::encryptString(json_encode($value, JSON_UNESCAPED_UNICODE))];
    }

    public static function isPlainJson(string $value): bool
    {
        $t = ltrim($value);

        return $t !== '' && ($t[0] === '{' || $t[0] === '[');
    }
}
