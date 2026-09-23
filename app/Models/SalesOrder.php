<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    protected $fillable = ['customer_id', 'order_number', 'order_date', 'requested_delivery_date', 'confirmed_delivery_date', 'observation'];
    protected $casts = ['order_date' => 'date', 'requested_delivery_date' => 'date', 'confirmed_delivery_date' => 'date'];

    public function customer() { return $this->belongsTo(Customer::class); }
    public function items() { return $this->hasMany(SalesOrderItem::class); }
    public function priorityEvaluations() { return $this->hasMany(OrderPriorityEvaluation::class); }

    public function latestPriorityEvaluation(): ?OrderPriorityEvaluation
    {
        return $this->priorityEvaluations()->latest('evaluated_at')->first();
    }

    public function recalculateStatus(): void
    {
        $statuses = $this->items()->pluck('status');

        $this->status = match (true) {
            $statuses->contains('BLOCKED') => 'BLOCKED',
            $statuses->every(fn ($s) => $s === 'DELIVERED') => 'DELIVERED',
            $statuses->contains('DELIVERED') || $statuses->contains('PARTIAL') => 'PARTIAL',
            default => 'OPEN',
        };

        $this->save();
    }
}
