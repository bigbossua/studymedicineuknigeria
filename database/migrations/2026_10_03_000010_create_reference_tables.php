<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('universities', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('medical_school_name')->nullable();
            $table->string('city')->nullable();
            $table->string('nation', 32)->nullable(); // England | Scotland | Wales | Northern Ireland
            $table->string('website_url')->nullable();
            $table->boolean('msc_member')->nullable();
            $table->string('gmc_status')->nullable(); // Awarding body | New school under review | ...
            $table->string('international_policy', 32)->nullable(); // accepts | home_only | international_only | not_published
            $table->boolean('published')->default(false); // public university page enabled
            $table->text('summary')->nullable();
            $table->timestamps();
            $table->index(['nation', 'international_policy']);
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('title');
            $table->string('award', 32)->nullable(); // MBBS, MBChB, BM BCh, MB BChir, BMBS, MBBCh
            $table->string('ucas_code', 16)->nullable();
            $table->string('entry_type', 32)->default('standard'); // standard | graduate | foundation | gateway
            $table->unsignedTinyInteger('length_years')->nullable();
            $table->string('application_route', 16)->nullable(); // UCAS | DIRECT | BOTH
            $table->string('admissions_test', 16)->nullable(); // UCAT | GAMSAT | NONE | NOT_PUBLISHED
            $table->string('official_url')->nullable();
            $table->string('status', 16)->default('active'); // active | suspended | archived
            $table->boolean('published')->default(false);
            $table->timestamps();
            $table->unique(['university_id', 'slug']);
        });

        // One row per verifiable value. subject = University or Course.
        Schema::create('reference_facts', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');
            $table->string('key', 64);                 // e.g. international_fee_gbp, ucas_code, waec_statement
            $table->text('value_text')->nullable();
            $table->decimal('value_number', 12, 2)->nullable();
            $table->boolean('value_bool')->nullable();
            $table->json('value_json')->nullable();
            $table->string('applies_to', 32)->default('international'); // international | home | all
            $table->string('academic_year', 16)->nullable();            // 2026/27, 2027 entry
            $table->string('qualification_code', 32)->nullable();        // WASSCE | NECO | ALEVEL | IB | DEGREE | ENGLISH ...
            $table->string('source_url', 1024)->nullable();
            $table->string('source_title')->nullable();
            $table->string('source_type', 24)->nullable();              // official | regulator | ucas | msc | lead
            $table->string('verification_status', 24)->default('VERIFY-ON-PAGE'); // VERIFIED | VERIFY-ON-PAGE | NOT_PUBLISHED | NOT_FOUND | REVIEW_DUE | SOURCE_CHANGED | ARCHIVED
            $table->date('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('review_due_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id', 'key']);
            $table->index(['key', 'verification_status']);
        });

        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->string('url', 1024);
            $table->string('url_hash', 64)->unique();
            $table->string('title')->nullable();
            $table->string('organisation')->nullable();
            $table->string('type', 24)->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('content_hash', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sources');
        Schema::dropIfExists('reference_facts');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('universities');
    }
};
