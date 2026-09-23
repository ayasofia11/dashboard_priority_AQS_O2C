<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportError extends Model
{
    protected $fillable = ['batch_id', 'row_number', 'column_name', 'error_message'];
    public function batch() { return $this->belongsTo(ImportBatch::class, 'batch_id'); }
}
