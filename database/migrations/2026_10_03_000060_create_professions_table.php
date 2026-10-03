<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The healthcare course universe (master taxonomy, data/healthcare/subjects.json). A profession's status uses the
 * SEO decision-engine vocabulary and decides whether a public page may exist; it never carries student-facing facts,
 * which stay in reference_facts with their own verification status.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professions', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name');
            $table->boolean('flagship')->default(false);
            $table->string('official_terminology', 512)->nullable();
            $table->json('alternative_names')->nullable();
            $table->string('regulator', 255)->nullable();
            $table->text('register_route')->nullable();
            $table->string('professional_body', 255)->nullable();
            $table->string('undergraduate_entry', 64)->nullable();
            $table->string('typical_length_years', 32)->nullable();
            $table->text('international_availability')->nullable();
            $table->string('nigerian_relevance', 255)->nullable();
            $table->text('waec_neco_relevance')->nullable();
            $table->text('a_level_requirements')->nullable();
            $table->text('foundation_routes')->nullable();
            $table->text('graduate_routes')->nullable();
            $table->text('admissions_test')->nullable();
            $table->string('application_route', 255)->nullable();
            $table->text('fees')->nullable();
            $table->text('english_language')->nullable();
            $table->text('nigeria_statement')->nullable();
            $table->text('deadlines')->nullable();
            $table->text('career_pathway')->nullable();
            $table->text('nigerian_search_demand')->nullable();
            $table->text('long_tail_demand')->nullable();
            $table->string('commercial_intent', 255)->nullable();
            $table->text('competition')->nullable();
            $table->json('sources')->nullable();
            $table->json('decision_register_ids')->nullable();
            $table->string('status', 16)->default('RESEARCH');
            $table->text('status_reason')->nullable();
            $table->date('last_researched')->nullable();
            $table->timestamps();
            $table->index('status');
        });
        Schema::table('courses', function (Blueprint $table) {
            $table->string('profession', 64)->default('medicine')->index()->after('university_id');
        });
    }

    public function down(): void
    {
        Schema::table('courses', fn (Blueprint $table) => $table->dropColumn('profession'));
        Schema::dropIfExists('professions');
    }
};
