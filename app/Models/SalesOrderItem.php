<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderItem extends Model
{
    protected $fillable = [
        'sales_order_id', 'product_id', 'line_number', 'unit_price', 'tva_rate',
        'ordered_quantity', 'delivered_quantity', 'initial_delivered_quantity', 'status', 'observation',
    ];

    public function order() { return $this->belongsTo(SalesOrder::class, 'sales_order_id'); }
    public function product() { return $this->belongsTo(Product::class); }

    public function getRemainingQuantityAttribute(): float
    {
        return max(0, $this->ordered_quantity - $this->delivered_quantity);
    }

    public function getRemainingAmountAttribute(): float
    {
        return $this->remaining_quantity * $this->unit_price;
    }

    public function getTvaAmountAttribute(): float
    {
        return $this->remaining_amount * $this->tva_rate;
    }

    public function getTotalAmountAttribute(): float
    {
        return $this->remaining_amount + $this->tva_amount;
    }
}
