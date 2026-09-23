<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('order_priority_factor_scores', function (Blueprint $table) {
        $table->id();
        $table->foreignId('evaluation_id')->constrained('order_priority_evaluations');
        $table->string('factor_code', 30);
        $table->decimal('raw_value', 18, 4)->nullable();
        $table->decimal('normalized_score', 5, 2);
        $table->decimal('weighted_score', 5, 2);
        $table->text('explanation')->nullable();
        $table->timestamps();
    });
}
public function down(): void { Schema::dropIfExists('order_priority_factor_scores'); }
};
