<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class CustomerSoldeSnapshot extends Model
{
    use HasFactory;
    protected $fillable = ['customer_id', 'outstanding_solde', 'currency', 'snapshot_at'];
    protected $casts = ['snapshot_at' => 'datetime'];
    public function customer() { return $this->belongsTo(Customer::class); }
}
