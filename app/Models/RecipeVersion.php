<?php
namespace App\Models;
class RecipeVersion extends \Illuminate\Database\Eloquent\Model
{
    protected $guarded = [];
    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(RecipeItem::class); }
    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Product::class); }
}
