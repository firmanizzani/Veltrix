<?php
namespace App\Models;
class Payment extends \Illuminate\Database\Eloquent\Model
{
    public $timestamps = false; protected $guarded = [];
    public function order(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Order::class); }
}
