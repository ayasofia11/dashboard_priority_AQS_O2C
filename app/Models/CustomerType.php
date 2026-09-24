<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class CustomerType extends Model
{
    use HasFactory;
    protected $fillable = ['code', 'name'];
    public function customers() { return $this->hasMany(Customer::class); }
}
