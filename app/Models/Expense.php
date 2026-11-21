<?php
namespace App\Models;
class Expense extends \Illuminate\Database\Eloquent\Model
{
    protected $guarded = [];
    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Branch::class); }
}
