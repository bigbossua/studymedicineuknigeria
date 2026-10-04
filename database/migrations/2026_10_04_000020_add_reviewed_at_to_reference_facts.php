<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * reviewed_at: set whenever a person decides on a fact (admin queue or worksheet import). Seeders and dataset imports
 * create missing facts and refresh unreviewed ones, but never touch a reviewed fact, so a deploy that re-syncs reference
 * data can never overwrite a verified value or reverse a reviewer's decision.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reference_facts', function (Blueprint $table) {
            $table->timestamp('reviewed_at')->nullable()->after('verified_at');
        });
        DB::table('reference_facts')->whereNotNull('verified_at')->update(['reviewed_at' => DB::raw('verified_at')]);
    }

    public function down(): void
    {
        Schema::table('reference_facts', fn (Blueprint $table) => $table->dropColumn('reviewed_at'));
    }
};
