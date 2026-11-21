<?php
namespace App\Models;
class Refund extends \Illuminate\Database\Eloquent\Model
{
    protected $guarded = [];
    public function order(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Order::class); }
}
