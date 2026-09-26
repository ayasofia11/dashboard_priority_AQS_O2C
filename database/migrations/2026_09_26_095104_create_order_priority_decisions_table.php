<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_priority_decisions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('evaluation_id')->constrained('order_priority_evaluations');
        $table->string('decision', 20);   // 'accepted' ou 'overridden'
        $table->string('final_priority_level', 20)->nullable();   // rempli seulement si overridden
        $table->text('comment')->nullable();
        $table->foreignId('decided_by')->constrained('users');
        $table->dateTime('decided_at');
        $table->timestamps();

        $table->index(['evaluation_id', 'decided_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_priority_decisions');
    }
};
