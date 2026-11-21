<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public const UPDATED_AT = null;
    protected $guarded = [];
    protected function casts(): array { return ['changes' => 'array']; }
    public function actor(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
}
