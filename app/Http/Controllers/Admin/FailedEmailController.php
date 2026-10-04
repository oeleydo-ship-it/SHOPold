<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

/**
 * Admin > Settings > Email > Failed emails: background emails that failed after every retry.
 * They can be sent again (back onto the queue) or removed. Super admins only (settings group).
 */
class FailedEmailController extends Controller
{
    public function index()
    {
        $rows = Schema::hasTable('failed_jobs')
            ? DB::table('failed_jobs')->orderByDesc('failed_at')->paginate(25)
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 25);

        return Inertia::render('Admin/Settings/FailedEmails', [
            'failed' => $rows->through(fn ($r) => $this->describe($r)),
            'queue' => \App\Support\MailQueue::status(),
        ]);
    }

    public function retry(Request $request)
    {
        $ids = $this->ids($request);
        if ($ids) {
            Artisan::call('queue:retry', ['id' => $ids]);
        }

        return back()->with('success', count($ids) === 1 ? 'The email is queued again.' : count($ids) . ' emails are queued again.');
    }

    public function destroy(Request $request)
    {
        $ids = $this->ids($request);
        DB::table('failed_jobs')->whereIn('uuid', $ids)->delete();

        return back()->with('success', count($ids) === 1 ? 'Failed email removed.' : count($ids) . ' failed emails removed.');
    }

    /** The chosen uuids (or every failed job for "all"), limited to ones that exist. */
    protected function ids(Request $request): array
    {
        $request->validate(['ids' => 'required_without:all|array', 'ids.*' => 'string|max:64', 'all' => 'nullable|boolean']);

        $query = DB::table('failed_jobs');
        if (!$request->boolean('all')) {
            $query->whereIn('uuid', (array) $request->input('ids', []));
        }

        return $query->pluck('uuid')->all();
    }

    /**
     * Summary of a failed job without unserialising it (no objects are created from stored data):
     * the mail class from the payload's displayName, recipients by pattern from the serialised command,
     * and the first line of the error.
     */
    protected function describe(object $r): array
    {
        $payload = json_decode($r->payload, true) ?: [];
        $class = (string) ($payload['displayName'] ?? 'Job');
        $command = (string) ($payload['data']['command'] ?? '');
        preg_match_all('/s:7:"address";s:\d+:"([^"]+)"/', $command, $m);

        return [
            'id' => $r->uuid,
            'type' => trim(preg_replace('/(?<!^)[A-Z]/', ' $0', class_basename($class))),
            'is_mail' => str_contains($class, 'Mail') || str_contains($command, 'SendQueuedMailable'),
            'to' => array_values(array_unique($m[1] ?? [])),
            'error' => \Illuminate\Support\Str::limit(strtok((string) $r->exception, "\n"), 300),
            'failed_at' => \Illuminate\Support\Carbon::parse($r->failed_at)->format('M d, Y H:i'),
        ];
    }
}
