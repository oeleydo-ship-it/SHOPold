<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;

/**
 * Settings that hold credentials (API keys, passwords, private keys).
 *
 * They are stored encrypted with the application key (Crypt::encryptString) and must be read back
 * through Secrets::decrypt(), which also tolerates a not-yet-encrypted value so nothing breaks
 * while old rows are being migrated (php artisan settings:encrypt-secrets).
 */
class Secrets
{
    /** Every setting key that must be stored encrypted. */
    public const KEYS = [
        // payment gateways
        'payment.stripe_secret_key',
        'payment.stripe_webhook_secret',
        'payment.paypal_client_secret',
        'payment.razorpay_key_secret',
        'payment.paytm_merchant_key',
        'payment.instamojo_auth_token',
        'payment.payfast_merchant_key',
        'payment.payfast_passphrase',
        'payment.mercadopago_access_token',
        'payment.authorizenet_transaction_key',
        'payment.iyzico_secret_key',
        'payment.paystack_secret_key',
        'payment.flutterwave_secret_key',
        'payment.flutterwave_encryption_key',
        'payment.sslcommerz_store_password',
        'payment.bkash_app_secret',
        'payment.bkash_password',
        'payment.nagad_private_key',
        // mail
        'email.smtp_password',
        'email.resend_api_key',
        // AI
        'ai.api_key',
        'features.ai_api_key',
    ];

    public static function isSecret(string $key): bool
    {
        return in_array($key, self::KEYS, true);
    }

    /** True only if the value really is a payload produced by Crypt::encryptString with this app key. */
    public static function isEncrypted(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        try {
            Crypt::decryptString($value);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /** Encrypt unless it already is (safe to call repeatedly). Plaintext that merely looks like a token is still encrypted. */
    public static function encrypt(string $value): string
    {
        return self::isEncrypted($value) ? $value : Crypt::encryptString($value);
    }

    /** Plaintext for a stored value; a value that is not encrypted is returned unchanged. */
    public static function decrypt(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }
}
