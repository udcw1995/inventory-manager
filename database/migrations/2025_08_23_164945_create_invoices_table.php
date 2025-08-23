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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->dateTime('issued_at');
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->decimal('fines_total', 10, 2)->default(0);
            $table->decimal('final_total', 10, 2)->default(0);
            $table->integer('total_items');
            $table->integer('total_refillable');
            $table->integer('total_non_refillable');
            $table->integer('returned_refillable_total')->default(0);
            $table->integer('damaged_lost_total')->default(0);
            $table->foreignId('issued_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
