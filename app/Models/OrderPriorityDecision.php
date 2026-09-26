<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderPriorityDecision extends Model
{
    protected $fillable = ['evaluation_id', 'decision', 'final_priority_level', 'comment', 'decided_by', 'decided_at'];
    protected $casts = ['decided_at' => 'datetime'];

    public function evaluation() { return $this->belongsTo(OrderPriorityEvaluation::class, 'evaluation_id'); }
    public function decidedBy() { return $this->belongsTo(User::class, 'decided_by'); }
}
