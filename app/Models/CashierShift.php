<?php
namespace App\Models;
class CashierShift extends \Illuminate\Database\Eloquent\Model
{
    protected $guarded = [];
    public function cashier(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(User::class, 'cashier_id'); }
    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Branch::class); }
}
