<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('phone_e164')->nullable()->index();
            $table->string('phone_raw')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('source')->default('manual');
            $table->string('chatwoot_contact_id')->nullable()->index();
            $table->string('chatwoot_identifier')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('industry')->nullable();
            $table->string('size')->nullable();
            $table->string('city')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('organization_person', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->string('role')->default('otro');
            $table->timestamps();
            $table->unique(['organization_id', 'person_id']);
        });

        Schema::create('pipelines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->unsignedInteger('position')->default(0);
            $table->unsignedTinyInteger('probability_default')->default(10);
            $table->boolean('is_won')->default(false);
            $table->boolean('is_lost')->default(false);
            $table->timestamps();
            $table->unique(['pipeline_id', 'slug']);
        });

        Schema::create('offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('b2b');
            $table->text('description')->nullable();
            $table->unsignedInteger('duration_days')->nullable();
            $table->decimal('default_amount', 12, 2)->nullable();
            $table->boolean('is_retainer')->default(false);
            $table->unsignedInteger('retainer_months')->nullable();
            $table->text('kpi_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('offering_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pipeline_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained()->restrictOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->decimal('amount', 12, 2)->nullable();
            $table->unsignedTinyInteger('probability')->default(10);
            $table->date('close_date')->nullable();
            $table->string('source')->default('manual');
            $table->string('status')->default('open');
            $table->string('lost_reason')->nullable();
            $table->string('chatwoot_conversation_id')->nullable()->index();
            $table->string('diagnostic_risk_level')->nullable();
            $table->json('diagnostic_result')->nullable();
            $table->json('kpis')->nullable();
            $table->timestamp('stage_changed_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('deal_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->default('task');
            $table->string('title');
            $table->text('body')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->boolean('is_done')->default(false);
            $table->boolean('overdue_event_sent')->default(false);
            $table->timestamps();
        });

        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('name');
            $table->string('base_url')->nullable();
            $table->text('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->unique('type');
        });

        Schema::create('external_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->string('provider');
            $table->string('external_id');
            $table->timestamps();
            $table->unique(['provider', 'external_id']);
        });

        Schema::create('outbound_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id')->unique();
            $table->string('type');
            $table->json('payload');
            $table->string('status')->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('inbound_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('event_type')->nullable();
            $table->string('external_event_id')->nullable()->index();
            $table->boolean('signature_ok')->default(false);
            $table->boolean('processed')->default(false);
            $table->string('skip_reason')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbound_webhook_logs');
        Schema::dropIfExists('outbound_events');
        Schema::dropIfExists('external_identities');
        Schema::dropIfExists('integrations');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('deals');
        Schema::dropIfExists('offerings');
        Schema::dropIfExists('stages');
        Schema::dropIfExists('pipelines');
        Schema::dropIfExists('organization_person');
        Schema::dropIfExists('organizations');
        Schema::dropIfExists('people');
    }
};
