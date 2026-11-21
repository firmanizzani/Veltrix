<?php
namespace App\Models;
class PurchaseOrder extends \Illuminate\Database\Eloquent\Model
{
    protected $guarded = [];
    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(PurchaseOrderItem::class); }
    public function supplier(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Supplier::class); }
    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Branch::class); }
}
