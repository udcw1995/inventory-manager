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
        Schema::create('grns', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('delivery_person_name');
            $table->string('delivery_person_contact');
            $table->string('vehicle_no');
            $table->dateTime('delivered_at');
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->integer('total_items')->default(0);
            $table->integer('total_refillable')->default(0);
            $table->integer('total_non_refillable')->default(0);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grns');
    }
};
