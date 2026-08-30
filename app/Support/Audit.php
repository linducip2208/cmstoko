<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class Audit
{
    /** Keys that must never be written to the audit trail. */
    protected const REDACTED_KEYS = [
        'password', 'password_confirmation', 'token', 'remember_token',
        'secret', 'server_key', 'client_key', 'api_key', 'private_key', 'signature',
    ];

    public static function record(
        string $action,
        ?Model $subject = null,
        array $before = [],
        array $after = [],
    ): ?AuditLog {
        try {
            $user = auth()->user();

            return AuditLog::create([
                'user_id' => $user?->id,
                'user_name' => $user?->name,
                'action' => $action,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject?->getKey(),
                'before' => static::redact($before) ?: null,
                'after' => static::redact($after) ?: null,
                'ip' => request()?->ip(),
                'user_agent' => static::userAgent(),
            ]);
        } catch (\Throwable) {
            // Auditing must never break the business action it observes.
            return null;
        }
    }

    protected static function userAgent(): ?string
    {
        $agent = request()?->userAgent();

        return $agent ? mb_substr($agent, 0, 500) : null;
    }

    /**
     * Strip secret-bearing keys recursively; values become "[redacted]".
     */
    public static function redact(array $data, int $depth = 0): array
    {
        if ($depth > 5) {
            return [];
        }

        $out = [];

        foreach ($data as $key => $value) {
            $key = (string) $key;

            if (preg_match('/('.implode('|', static::REDACTED_KEYS).')/i', $key)) {
                $out[$key] = '[redacted]';

                continue;
            }

            if (is_array($value)) {
                $out[$key] = static::redact($value, $depth + 1);

                continue;
            }

            if (! is_scalar($value) && $value !== null) {
                $out[$key] = '[complex]';

                continue;
            }

            $out[$key] = $value;
        }

        return $out;
    }
}
