<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('import_errors', function (Blueprint $table) {
        $table->id();
        $table->foreignId('batch_id')->constrained('import_batches');
        $table->integer('row_number');
        $table->string('column_name')->nullable();
        $table->string('error_message');
        $table->timestamps();
    });
}
public function down(): void { Schema::dropIfExists('import_errors'); }
};
