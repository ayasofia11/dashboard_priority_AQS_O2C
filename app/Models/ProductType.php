<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductType extends Model
{
    protected $fillable = ['code', 'name', 'priority_score'];
    public function products() { return $this->hasMany(Product::class); }
}
