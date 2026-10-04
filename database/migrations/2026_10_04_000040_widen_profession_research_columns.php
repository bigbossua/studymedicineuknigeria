<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Research prose in the healthcare taxonomy outgrew its varchar limits. SQLite ignores those limits, MySQL rejects
 * the row, so the first MySQL deploy's reference sync failed (found in the 2026-10-04 MariaDB rehearsal).
 */
return new class extends Migration
{
    private const COLUMNS = ['official_terminology', 'regulator', 'professional_body', 'undergraduate_entry', 'typical_length_years', 'nigerian_relevance', 'application_route', 'commercial_intent'];

    public function up(): void
    {
        Schema::table('professions', function (Blueprint $table) {
            foreach (self::COLUMNS as $column) {
                $table->text($column)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('professions', function (Blueprint $table) {
            foreach (self::COLUMNS as $column) {
                $table->string($column, 255)->nullable()->change();
            }
        });
    }
};
