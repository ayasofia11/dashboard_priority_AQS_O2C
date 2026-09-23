<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('product_types', function (Blueprint $table) {
        $table->id();
        $table->string('code', 30)->unique();
        $table->string('name', 100);
        $table->decimal('priority_score', 5, 2)->default(50);
        $table->timestamps();
    });
}
public function down(): void { Schema::dropIfExists('product_types'); }
};
