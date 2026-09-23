<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('order_priority_evaluations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sales_order_id')->constrained();
        $table->foreignId('priority_model_id')->constrained();
        $table->dateTime('evaluated_at');
        $table->decimal('total_remaining_qty', 15, 3);
        $table->decimal('final_score', 5, 2)->nullable();
        $table->string('priority_level', 20);
        $table->string('reason')->nullable();
        $table->timestamps();
        $table->index(['sales_order_id', 'evaluated_at']);
    });
}
public function down(): void { Schema::dropIfExists('order_priority_evaluations'); }
};
