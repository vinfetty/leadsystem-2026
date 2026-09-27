<?php

use App\Enums\LeadStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->string('website')->nullable();
            $table->string('intake_token_hash', 64)->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_source_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default(LeadStatus::New->value);

            $table->string('first_name', 75);
            $table->string('last_name', 75);
            $table->string('email');
            $table->string('phone', 20);
            $table->string('phone_secondary', 20)->nullable();
            $table->string('address');
            $table->string('city', 50);
            $table->string('state', 2);
            $table->string('zip', 10);
            $table->string('timezone', 40);

            $table->unsignedInteger('property_value');
            $table->unsignedInteger('loan_amount');
            $table->string('loan_type', 30);
            $table->string('credit_rating', 20);
            $table->unsignedInteger('yearly_income')->nullable();
            $table->string('best_time_to_call', 50)->nullable();

            $table->timestamp('consent_at');
            $table->string('consent_ip', 45);
            $table->timestamp('follow_up_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'assigned_to']);
            $table->index(['lead_source_id', 'email']);
            $table->index('state');
        });

        Schema::create('lead_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_actions');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('lead_sources');
    }
};
