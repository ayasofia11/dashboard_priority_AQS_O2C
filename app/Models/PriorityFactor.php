<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriorityFactor extends Model
{
    protected $fillable = ['priority_model_id', 'code', 'weight', 'config_json'];
    protected $casts = ['config_json' => 'array'];
    public function priorityModel() { return $this->belongsTo(PriorityModel::class); }
}
