<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('customer_solde_snapshots', function (Blueprint $table) {
        $table->id();
        $table->foreignId('customer_id')->constrained();
        $table->decimal('outstanding_solde', 18, 2);
        $table->char('currency', 3)->default('DZD');
        $table->dateTime('snapshot_at');
        $table->timestamps();
        $table->index(['customer_id', 'snapshot_at']);
    });
}
public function down(): void { Schema::dropIfExists('customer_solde_snapshots'); }
};
