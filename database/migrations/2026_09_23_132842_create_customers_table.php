<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('customers', function (Blueprint $table) {
        $table->id();
        $table->foreignId('customer_type_id')->nullable()->constrained('customer_types');
        $table->string('customer_code', 30)->unique();
        $table->string('name', 150);
        $table->decimal('distance_km', 8, 2)->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}
public function down(): void { Schema::dropIfExists('customers'); }
};
