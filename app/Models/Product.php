<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Product extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Category::class); }
    public function recipes(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(RecipeVersion::class); }
}
