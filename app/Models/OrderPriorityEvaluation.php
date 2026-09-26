<?php

namespace App\Models;

use App\Enums\PriorityLevel;
use Illuminate\Database\Eloquent\Model;

class OrderPriorityEvaluation extends Model
{
    protected $fillable = ['sales_order_id', 'priority_model_id', 'evaluated_at', 'total_remaining_qty', 'final_score', 'priority_level', 'reason'];

    protected function casts(): array
    {
        return ['evaluated_at' => 'datetime', 'priority_level' => PriorityLevel::class];
    }

    public function order() { return $this->belongsTo(SalesOrder::class, 'sales_order_id'); }
    public function priorityModel() { return $this->belongsTo(PriorityModel::class); }
    public function factorScores() { return $this->hasMany(OrderPriorityFactorScore::class, 'evaluation_id'); }
    public function decision() { return $this->hasOne(OrderPriorityDecision::class, 'evaluation_id'); }

    // La priorité "effective" à afficher : celle décidée par l'humain si elle existe, sinon le calcul automatique.
    public function getEffectivePriorityAttribute(): \App\Enums\PriorityLevel
    {
        if ($this->decision && $this->decision->decision === 'overridden') {
            return \App\Enums\PriorityLevel::from($this->decision->final_priority_level);
        }
        return $this->priority_level;
    }
}
