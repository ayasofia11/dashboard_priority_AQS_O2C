<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class StockSnapshot extends Model
{
    use HasFactory;
    protected $fillable = ['product_id', 'plant_code', 'storage_location', 'available_qty', 'snapshot_at'];
    protected $casts = ['snapshot_at' => 'datetime'];
    public function product() { return $this->belongsTo(Product::class); }
}
