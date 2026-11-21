<?php
namespace App\Models;
class Payroll extends \Illuminate\Database\Eloquent\Model
{
    protected $guarded = [];
    public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Employee::class); }
    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Branch::class); }
}
