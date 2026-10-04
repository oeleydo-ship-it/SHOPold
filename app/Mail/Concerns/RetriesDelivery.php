<?php

namespace App\Mail\Concerns;

/**
 * Retry policy for queued emails: three attempts, waiting 1 and then 5 minutes, so a short mail-server outage
 * doesn't lose the message. After the last attempt the job lands in Admin > Settings > Email > Failed emails.
 */
trait RetriesDelivery
{
    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [60, 300];
}
