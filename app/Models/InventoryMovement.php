<?php
namespace App\Models;
class InventoryMovement extends \Illuminate\Database\Eloquent\Model
{
    protected $guarded = [];
    public function inventory(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Inventory::class); }
    public function actor(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
}
