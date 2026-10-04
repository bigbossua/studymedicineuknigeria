<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ledger of applied verification decision files (smukn:facts-import). ops/deploy.sh replays every committed
 * decisions-*.csv on each deploy; without a ledger a replay reversed later reviews (a fact moved to SOURCE_CHANGED or
 * REVIEW_DUE went back to VERIFIED with the old value). A file is applied once per environment, keyed by content hash.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fact_imports', function (Blueprint $table) {
            $table->id();
            $table->string('file', 255);
            $table->char('sha256', 64)->unique();
            $table->unsignedInteger('applied')->default(0);
            $table->unsignedInteger('skipped')->default(0);
            $table->unsignedInteger('unknown')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fact_imports');
    }
};
