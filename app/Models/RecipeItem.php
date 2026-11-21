<?php
namespace App\Models;
class RecipeItem extends \Illuminate\Database\Eloquent\Model
{
    public $timestamps = false;
    protected $guarded = [];
    public function ingredient(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Ingredient::class); }
}
