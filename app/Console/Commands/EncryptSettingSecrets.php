<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Support\Secrets;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EncryptSettingSecrets extends Command
{
    protected $signature = 'settings:encrypt-secrets {--dry-run : Show what would change without writing anything}';

    protected $description = 'Encrypt any credential settings (API keys, passwords) that are still stored as plain text';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $rows = DB::table('settings')->whereIn('key', Secrets::KEYS)->get(['key', 'value']);

        $encrypted = [];
        $already = 0;
        $empty = 0;

        foreach ($rows as $row) {
            if ($row->value === null || $row->value === '') {
                $empty++;
            } elseif (Secrets::isEncrypted($row->value)) {
                $already++;
            } else {
                $encrypted[] = $row->key;
                if (!$dry) {
                    DB::table('settings')->where('key', $row->key)->update(['value' => Secrets::encrypt($row->value)]);
                }
            }
        }

        if (!$dry && $encrypted) {
            Setting::clearCache();
        }

        // Key names only: values are never printed.
        $this->info(($dry ? '[dry run] would encrypt ' : 'Encrypted ') . count($encrypted) . ' setting(s)'
            . ", {$already} already encrypted, {$empty} empty, " . (count(Secrets::KEYS) - $rows->count()) . ' not set.');
        foreach ($encrypted as $key) {
            $this->line("  - {$key}");
        }

        return self::SUCCESS;
    }
}
