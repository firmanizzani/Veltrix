<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BranchAccess
{
    public function scope(
        Builder $query,
        User $user,
        string $column = 'branch_id',
    ): Builder {
        return $user->isOwner() ? $query : $query->where($column, $user->branch_id);
    }

    public function selected(Request $request): ?int
    {
        if (!$request->user()->isOwner()) {
            if ($request->filled('branch_id')) {
                abort_unless(
                    (int) $request->branch_id === $request->user()->branch_id,
                    403,
                );
            }

            return $request->user()->branch_id;
        }
        if ($request->filled('branch_id')) {
            return (int) $request->validate([
                'branch_id' => 'required|integer|exists:branches,id',
            ])['branch_id'];
        }

        return null;
    }

    public function require(User $user, int $branchId): void
    {
        abort_unless($user->canAccessBranch($branchId), 403);
    }
}
