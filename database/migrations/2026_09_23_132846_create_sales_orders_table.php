<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('sales_orders', function (Blueprint $table) {
        $table->id();
        $table->foreignId('customer_id')->constrained();
        $table->foreignId('last_import_batch_id')->nullable()->constrained('import_batches');
        $table->string('order_number', 30)->unique();
        $table->date('order_date');
        $table->date('requested_delivery_date')->nullable();
        $table->date('confirmed_delivery_date')->nullable();
        $table->string('status', 20)->default('OPEN');
        $table->text('observation')->nullable();
        $table->timestamps();
    });
}
public function down(): void { Schema::dropIfExists('sales_orders'); }
};
