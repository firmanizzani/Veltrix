<?php
namespace App\Models;
class Employee extends \Illuminate\Database\Eloquent\Model
{
    protected $guarded = [];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Branch::class); }
}
