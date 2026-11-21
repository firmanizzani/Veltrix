<?php
namespace App\Models;
class Order extends \Illuminate\Database\Eloquent\Model
{
    protected $guarded = [];
    protected function casts(): array { return ['completed_at' => 'datetime']; }
    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(OrderItem::class); }
    public function payment(): \Illuminate\Database\Eloquent\Relations\HasOne { return $this->hasOne(Payment::class); }
    public function refund(): \Illuminate\Database\Eloquent\Relations\HasOne { return $this->hasOne(Refund::class); }
    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Branch::class); }
    public function cashier(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(User::class, 'cashier_id'); }
}
