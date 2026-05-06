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
        Schema::create('parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('identifier'); // e.g., 'temperature'
            $table->string('name'); // e.g., 'Temperature'
            $table->string('unit')->nullable(); // e.g., '°C'
            $table->string('type')->default('numeric'); // numeric, boolean, string
            $table->json('thresholds')->nullable(); // { "high": 30, "low": 10 }
            $table->timestamps();

            $table->unique(['device_id', 'identifier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parameters');
    }
};
