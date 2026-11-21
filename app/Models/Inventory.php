<?php
namespace App\Models;
class Inventory extends \Illuminate\Database\Eloquent\Model
{
    protected $guarded = [];
    public function ingredient(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Ingredient::class); }
    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Branch::class); }
}
