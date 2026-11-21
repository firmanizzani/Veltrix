<?php
namespace App\Models;
class WorkSchedule extends \Illuminate\Database\Eloquent\Model
{
    protected $guarded = [];
    public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(Employee::class); }
}
