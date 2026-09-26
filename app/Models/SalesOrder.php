<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class SalesOrder extends Model
{
    use HasFactory;
    protected $fillable = ['customer_id', 'last_import_batch_id', 'order_number', 'order_date', 'requested_delivery_date', 'confirmed_delivery_date', 'observation'];
    protected $casts = ['order_date' => 'date', 'requested_delivery_date' => 'date', 'confirmed_delivery_date' => 'date'];

    public function customer() { return $this->belongsTo(Customer::class); }
    public function items() { return $this->hasMany(SalesOrderItem::class); }
    public function priorityEvaluations() { return $this->hasMany(OrderPriorityEvaluation::class); }

    public function latestPriorityEvaluation()
    {
        //return $this->priorityEvaluations()->latest('evaluated_at')->first();
        return $this->hasOne(OrderPriorityEvaluation::class, 'sales_order_id')->latestOfMany('evaluated_at');
    }


    public function lastImportBatch()
    {
        return $this->belongsTo(ImportBatch::class, 'last_import_batch_id');
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
