<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Fingerprints of the official pages behind verified facts (smukn:sources-check). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('source_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('url', 1024);
            $table->char('url_hash', 64)->unique();
            $table->char('content_hash', 64)->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamp('changed_at')->nullable();
            $table->string('last_error', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_snapshots');
    }
};
