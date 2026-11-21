<?php
namespace App\Models;
class StockTransfer extends \Illuminate\Database\Eloquent\Model
{
    protected $guarded = [];
    public function source(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Branch::class, 'source_branch_id'); }
    public function destination(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Branch::class, 'destination_branch_id'); }
    public function ingredient(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Ingredient::class); }
}
