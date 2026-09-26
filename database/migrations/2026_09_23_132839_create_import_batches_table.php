<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('import_batches', function (Blueprint $table) {
        $table->id();
        $table->string('source_type', 20);
        $table->string('file_name');
        $table->foreignId('imported_by')->nullable()->constrained('users');
        $table->integer('total_rows');
        $table->integer('success_rows')->default(0);
        $table->integer('error_rows')->default(0);
        $table->string('status', 20)->default('PROCESSING');
        $table->timestamp('imported_at')->nullable();
        $table->timestamps();
    });
}
public function down(): void { Schema::dropIfExists('import_batches'); }
};
