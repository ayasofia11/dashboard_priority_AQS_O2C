<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportBatch extends Model
{
    protected $fillable = ['source_type', 'file_name', 'imported_by', 'total_rows', 'success_rows', 'error_rows', 'status', 'imported_at'];
    protected $casts = ['imported_at' => 'datetime'];

    public function importer() { return $this->belongsTo(User::class, 'imported_by'); }
    public function errors() { return $this->hasMany(ImportError::class, 'batch_id'); }
}
