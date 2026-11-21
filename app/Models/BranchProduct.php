<?php
namespace App\Models;
class BranchProduct extends \Illuminate\Database\Eloquent\Model
{
    protected $guarded = [];
    protected function casts(): array { return ['is_available' => 'boolean']; }
    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Product::class); }
    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Branch::class); }
}
