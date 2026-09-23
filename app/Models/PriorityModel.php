<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriorityModel extends Model
{
    protected $fillable = ['name', 'version', 'is_active', 'thresholds'];
    protected $casts = ['is_active' => 'boolean', 'thresholds' => 'array'];
    public function factors() { return $this->hasMany(PriorityFactor::class); }
}
