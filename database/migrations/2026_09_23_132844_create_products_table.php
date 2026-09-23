<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->foreignId('product_type_id')->nullable()->constrained('product_types');
        $table->string('reference', 50)->unique();
        $table->string('name', 200);
        $table->string('unit', 20);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}
public function down(): void { Schema::dropIfExists('products'); }
};
