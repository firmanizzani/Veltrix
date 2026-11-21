<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    protected $guarded = [];
    protected $appends = ['stock_value'];

    public function getStockValueAttribute(): int
    {
        return (int) round((float) $this->quantity * (float) $this->average_unit_cost);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
