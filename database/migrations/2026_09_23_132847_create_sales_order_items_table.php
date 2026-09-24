<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sales_order_id')->constrained();
        $table->foreignId('product_id')->constrained();
        $table->integer('line_number');
        $table->decimal('unit_price', 15, 2);
        $table->decimal('tva_rate', 5, 4)->default(0.19);
        $table->decimal('ordered_quantity', 15, 3);
        $table->decimal('delivered_quantity', 15, 3)->default(0);
        $table->decimal('initial_delivered_quantity', 15, 3)->default(0);
        $table->string('status', 20)->default('OPEN');
        $table->text('observation')->nullable();
        $table->timestamps();

        $table->unique(['sales_order_id', 'line_number']);
        });

        // CHECK constraints ajoutées après la création de la table
        DB::statement('ALTER TABLE sales_order_items ADD CONSTRAINT chk_ordered_qty_positive CHECK (ordered_quantity > 0)');
        DB::statement('ALTER TABLE sales_order_items ADD CONSTRAINT chk_delivered_qty_valid CHECK (delivered_quantity >= 0 AND delivered_quantity <= ordered_quantity)');
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_items');
    }
};
