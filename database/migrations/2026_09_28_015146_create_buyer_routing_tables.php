<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buyers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedTinyInteger('tier');
            $table->unsignedInteger('price_cents');
            $table->unsignedInteger('daily_cap')->nullable();
            $table->boolean('active')->default(true);

            $table->json('states')->nullable();
            $table->json('zip_prefixes')->nullable();
            $table->json('loan_types')->nullable();
            $table->unsignedInteger('min_loan_amount')->nullable();
            $table->unsignedInteger('max_loan_amount')->nullable();
            $table->string('min_credit_rating', 20)->nullable();
            $table->timestamps();

            $table->index(['tier', 'price_cents']);
        });

        Schema::create('routing_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('buyer_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedInteger('price_cents')->nullable();
            $table->json('evaluations');
            $table->timestamps();

            $table->unique(['lead_id', 'buyer_id']);
            $table->index(['buyer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routing_attempts');
        Schema::dropIfExists('buyers');
    }
};
