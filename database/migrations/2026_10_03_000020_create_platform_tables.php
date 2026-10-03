<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 16)->default('student')->after('password'); // student | staff | admin
            $table->string('phone', 32)->nullable();
            $table->string('whatsapp', 32)->nullable();
            $table->string('country', 2)->nullable()->default('NG');
            $table->string('nigeria_state', 64)->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->softDeletes();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('whatsapp', 32)->nullable();
            $table->string('source_page')->nullable();
            $table->json('utm')->nullable();
            $table->json('eligibility_answers')->nullable();
            $table->json('eligibility_result')->nullable();
            $table->string('status', 16)->default('new'); // new | contacted | qualified | converted | closed
            $table->boolean('consent_marketing')->default(false);
            $table->timestamps();
            $table->index(['email', 'status']);
        });

        Schema::create('service_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('name');
            $table->text('summary')->nullable();
            $table->json('deliverables');           // list of strings shown on pricing + checkout
            $table->json('exclusions')->nullable(); // what is not included
            $table->string('payment_gate', 24)->default('AT_START'); // AT_START | BEFORE_REVIEW | BEFORE_SUBMISSION
            $table->string('terms_version', 16)->default('v1');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('tier_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_tier_id')->constrained()->cascadeOnDelete();
            $table->string('component', 16)->default('full'); // full | preparation | submission
            $table->string('currency', 3)->default('GBP');
            $table->unsignedInteger('amount_minor')->nullable(); // pence; null = price not yet set
            $table->string('stripe_price_id')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_number', 24)->unique(); // SMN-2027-000123
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_tier_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('intake_year');
            $table->string('stage', 40)->default('APPLICATION_STARTED');
            $table->string('stage_override', 40)->nullable();     // staff-set judgement stage
            $table->foreignId('stage_override_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('form')->nullable();                      // all step data
            $table->json('section_status')->nullable();            // step => complete|incomplete
            $table->unsignedTinyInteger('completion_pct')->default(0);
            $table->foreignId('assigned_staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('staff_notes')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('closed_reason')->nullable();
            $table->date('hold_until')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
            $table->index(['stage', 'intake_year']);
        });

        Schema::create('application_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('type', 48);
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['application_id', 'created_at']);
        });

        Schema::create('checklist_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('predicate');      // {"field":"secondary.boards","op":"contains","value":"WAEC"} or {"always":true}
            $table->json('require_codes');  // ["WAEC"]
            $table->string('reason_text');
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('code', 24);              // PASSPORT, WAEC, ...
            $table->string('title');
            $table->string('status', 24)->default('REQUIRED'); // NOT_REQUIRED | REQUIRED | UPLOADED | UNDER_REVIEW | ACCEPTED | REJECTED | REPLACEMENT_REQUIRED
            $table->string('required_reason')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->text('staff_note')->nullable();   // latest reject/replace reason shown to the student
            $table->timestamps();
            $table->unique(['application_id', 'code']);
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            $table->string('disk', 24)->default('private');
            $table->string('path', 512);
            $table->string('original_filename');
            $table->string('mime', 128);
            $table->unsignedBigInteger('size_bytes');
            $table->string('sha256', 64);
            $table->string('scan_status', 16)->default('pending'); // pending | clean | infected | unavailable
            $table->boolean('encrypted')->default(false);
            $table->string('key_id', 32)->nullable();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamp('uploaded_at')->useCurrent();
            $table->unique(['document_id', 'version']);
        });

        Schema::create('document_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24);
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('document_access_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_version_id')->constrained('document_versions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->string('ip', 45)->nullable();
            $table->string('purpose', 32)->default('download'); // download | preview | package
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tier_price_id')->nullable()->constrained('tier_prices')->nullOnDelete();
            $table->string('status', 24)->default('REQUIRED'); // REQUIRED | INITIATED | SUCCEEDED | FAILED | EXPIRED | REFUNDED_PARTIAL | REFUNDED_FULL | DISPUTED | DISPUTE_WON | DISPUTE_LOST | MANUAL_REVIEW | REJECTED
            $table->unsignedInteger('amount_minor');
            $table->string('currency', 3)->default('GBP');
            $table->string('method', 24)->default('STRIPE'); // STRIPE | MANUAL_TRANSFER
            $table->string('stripe_checkout_session_id')->nullable()->index();
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->string('terms_version_accepted', 16)->nullable();
            $table->string('receipt_url', 1024)->nullable();
            $table->unsignedInteger('refunded_minor')->default(0);
            $table->text('note')->nullable();
            $table->timestamp('succeeded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stripe_events', function (Blueprint $table) {
            $table->id();
            $table->string('stripe_event_id')->unique();
            $table->string('type', 64);
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('university_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('intake', 32)->nullable();
            $table->string('route_code', 32)->default('UCAS_STUDENT'); // UCAS_STUDENT | UCAS_CENTRE | DIRECT_PORTAL_STUDENT | DIRECT_AGENT | PATHWAY_PROVIDER
            $table->string('status', 32)->default('PROPOSED');       // PROPOSED | AUTHORISED | PACKAGE_READY | SUBMITTED | ACKNOWLEDGED | INTERVIEW | OFFER_CONDITIONAL | OFFER_UNCONDITIONAL | REJECTED | WAITLISTED | ACCEPTED_BY_STUDENT | DECLINED | CLOSED
            $table->unsignedBigInteger('authorisation_id')->nullable();
            $table->unsignedSmallInteger('package_version')->default(0);
            $table->string('package_hash', 64)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('submitted_by', 32)->nullable(); // STUDENT | user id
            $table->string('external_reference')->nullable(); // UCAS Personal ID / university ref
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('submission_choices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label')->nullable();
            $table->unsignedTinyInteger('choice_order')->default(1);
        });

        Schema::create('submission_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('external_reference')->nullable();
            $table->unsignedBigInteger('evidence_document_version_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('authorisations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approved_by_user_id')->constrained('users');
            $table->timestamp('approved_at');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('declaration_version', 16);
            $table->string('typed_name');
            $table->string('snapshot_hash', 64);
            $table->json('snapshot');
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_reason')->nullable();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_user_id')->constrained('users');
            $table->text('body');
            $table->unsignedBigInteger('attachment_version_id')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            $table->timestamp('scheduled_for');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['scheduled_for', 'sent_at']);
        });

        Schema::create('admin_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->constrained('users');
            $table->string('action', 64);
            $table->string('target_type', 64)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['admin_actions', 'reminders', 'messages', 'authorisations', 'submission_events', 'submission_choices', 'submissions', 'stripe_events', 'payments', 'document_access_log', 'document_events', 'document_versions', 'documents', 'checklist_rules', 'application_events', 'applications', 'tier_prices', 'service_tiers', 'leads'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'phone', 'whatsapp', 'country', 'nigeria_state', 'two_factor_secret', 'two_factor_confirmed_at', 'last_login_at', 'deleted_at']);
        });
    }
};
