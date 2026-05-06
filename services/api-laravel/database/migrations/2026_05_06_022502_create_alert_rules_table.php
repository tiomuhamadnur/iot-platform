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
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('device_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('parameter_identifier');
            $table->string('operator'); // >, <, ==, !=, >=, <=
            $table->double('threshold');
            $table->integer('duration')->default(0); // seconds the condition must persist
            $table->integer('cooldown')->default(3600); // seconds to wait before re-triggering
            $table->string('severity')->default('critical'); // warning, critical
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_rules');
    }
};
