<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Vendor payout details (bank account numbers, PayPal email, Stripe account id) are encrypted at rest.
 * - vendors.payment_info and vendor_payouts.payment_details become TEXT (a JSON column can't hold ciphertext)
 *   and existing values are encrypted.
 * - Payout requests used to copy the bank details into vendor_payouts.notes as plain text; those
 *   auto-generated notes ("Bank: ...\nAccount: ...\nNumber: ...", "PayPal: ...", "Stripe: ...") are moved
 *   into the encrypted payment_details. Notes typed by an admin are left alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', fn (Blueprint $t) => $t->text('payment_info')->nullable()->change());
        Schema::table('vendor_payouts', fn (Blueprint $t) => $t->text('payment_details')->nullable()->change());

        foreach (DB::table('vendors')->whereNotNull('payment_info')->get(['id', 'payment_info']) as $row) {
            if ($this->isPlain($row->payment_info)) {
                DB::table('vendors')->where('id', $row->id)->update(['payment_info' => Crypt::encryptString($row->payment_info)]);
            }
        }

        foreach (DB::table('vendor_payouts')->get(['id', 'notes', 'payment_details']) as $row) {
            $update = [];
            $details = $row->payment_details && $this->isPlain($row->payment_details) ? (json_decode($row->payment_details, true) ?: []) : null;

            if ($moved = $this->parseGeneratedNote((string) $row->notes)) {
                $details = array_merge($details ?? [], $moved);
                $update['notes'] = null;
            }
            if ($details !== null) {
                $update['payment_details'] = $details ? Crypt::encryptString(json_encode($details)) : null;
            }
            if ($update) {
                DB::table('vendor_payouts')->where('id', $row->id)->update($update);
            }
        }
    }

    public function down(): void
    {
        // Decrypt back to plain JSON so the old json columns can hold it again.
        foreach (['vendors' => 'payment_info', 'vendor_payouts' => 'payment_details'] as $table => $column) {
            foreach (DB::table($table)->whereNotNull($column)->get(['id', $column]) as $row) {
                if (!$this->isPlain($row->$column)) {
                    try {
                        DB::table($table)->where('id', $row->id)->update([$column => Crypt::decryptString($row->$column)]);
                    } catch (\Throwable) {
                        DB::table($table)->where('id', $row->id)->update([$column => null]);
                    }
                }
            }
            Schema::table($table, fn (Blueprint $t) => $t->json($column)->nullable()->change());
        }
    }

    private function isPlain(string $value): bool
    {
        $t = ltrim($value);

        return $t !== '' && ($t[0] === '{' || $t[0] === '[');
    }

    /** Recognise the notes SettingController::requestPayout used to write; anything else is an admin's note. */
    private function parseGeneratedNote(string $notes): ?array
    {
        $notes = str_replace("\r\n", "\n", trim($notes));
        if (preg_match('/^Bank: (.*)\nAccount: (.*)\nNumber: (.*)$/', $notes, $m)) {
            return ['bank' => ['name' => $m[1], 'account_name' => $m[2], 'account_number' => $m[3]]];
        }
        if (preg_match('/^PayPal: (.*)$/', $notes, $m)) {
            return ['paypal' => ['email' => $m[1]]];
        }
        if (preg_match('/^Stripe: (.*)$/', $notes, $m)) {
            return ['stripe' => ['account_id' => $m[1]]];
        }

        return null;
    }
};
