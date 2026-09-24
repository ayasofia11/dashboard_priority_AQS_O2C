<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory;
    protected $fillable = ['customer_type_id', 'customer_code', 'name', 'distance_km', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function customerType() { return $this->belongsTo(CustomerType::class); }
    public function orders() { return $this->hasMany(SalesOrder::class); }
    public function soldeSnapshots() { return $this->hasMany(CustomerSoldeSnapshot::class); }

    public function latestSolde(): ?CustomerSoldeSnapshot
    {
        return $this->soldeSnapshots()->latest('snapshot_at')->first();
    }
}
