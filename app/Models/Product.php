<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['product_type_id', 'reference', 'name', 'unit', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function productType() { return $this->belongsTo(ProductType::class); }
    public function stockSnapshots() { return $this->hasMany(StockSnapshot::class); }

    public function latestStock(): ?StockSnapshot
    {
        return $this->stockSnapshots()->latest('snapshot_at')->first();
    }
}
