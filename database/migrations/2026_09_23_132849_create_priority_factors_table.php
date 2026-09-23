<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('priority_factors', function (Blueprint $table) {
        $table->id();
        $table->foreignId('priority_model_id')->constrained();
        $table->string('code', 30);
        $table->decimal('weight', 5, 2);
        $table->json('config_json')->nullable();
        $table->timestamps();
        $table->unique(['priority_model_id', 'code']);
    });
}
public function down(): void { Schema::dropIfExists('priority_factors'); }
};
