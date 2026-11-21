<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;

class Audit
{
    public static function record(
        User $actor,
        string $action,
        string $type,
        ?int $id,
        array $changes = [],
        ?int $branchId = null,
    ): void {
        AuditLog::create([
            'actor_id' => $actor->id,
            'branch_id' => $branchId ?? $actor->branch_id,
            'action' => $action,
            'entity_type' => $type,
            'entity_id' => $id,
            'changes' => self::withoutSecrets($changes),
        ]);
    }

    private static function withoutSecrets(array $values): array
    {
        $safe = [];
        foreach ($values as $key => $value) {
            if (
                preg_match(
                    '/password|secret|token|request_hash|session_version/i',
                    (string) $key,
                )
            ) {
                continue;
            }
            $safe[$key] = is_array($value) ? self::withoutSecrets($value) : $value;
        }
        return $safe;
    }
}
