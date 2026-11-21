<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;

class Audit
{
    public static function record(User $actor, string $action, string $type, ?int $id, array $changes = [], ?int $branchId = null): void
    {
        AuditLog::create(['actor_id' => $actor->id, 'branch_id' => $branchId ?? $actor->branch_id, 'action' => $action, 'entity_type' => $type, 'entity_id' => $id, 'changes' => collect($changes)->except(['password', 'remember_token'])->all()]);
    }
}
