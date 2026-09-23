<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderPriorityFactorScore extends Model
{
    protected $fillable = ['evaluation_id', 'factor_code', 'raw_value', 'normalized_score', 'weighted_score', 'explanation'];
    public function evaluation() { return $this->belongsTo(OrderPriorityEvaluation::class, 'evaluation_id'); }
}
