<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceRequest extends Model
{
    protected $guarded = [];

    public function branchProduct(): BelongsTo
    {
        return $this->belongsTo(BranchProduct::class);
    }
}
