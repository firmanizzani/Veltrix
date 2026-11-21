<?php
namespace App\Models;
class PriceRequest extends \Illuminate\Database\Eloquent\Model
{
    protected $guarded = [];
    public function branchProduct(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(BranchProduct::class); }
}
