<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('stock_snapshots', function (Blueprint $table) {
        $table->id();
        $table->foreignId('product_id')->constrained();
        $table->string('plant_code', 10)->nullable();
        $table->string('storage_location', 20)->nullable();
        $table->decimal('available_qty', 18, 3)->default(0);
        $table->dateTime('snapshot_at');
        $table->timestamps();
        $table->index(['product_id', 'snapshot_at']);
    });
}
public function down(): void { Schema::dropIfExists('stock_snapshots'); }
};
