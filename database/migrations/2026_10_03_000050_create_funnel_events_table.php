<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Server-side mirror of the reporting events in docs/architecture/12.9. No names, emails or
        // application numbers: application_hash is sha256(application_number), visitor_hash is a keyed
        // hash of the session id, so the table can be read for funnel analysis without touching PII.
        Schema::create('funnel_events', function (Blueprint $table) {
            $table->id();
            $table->string('name', 48);
            $table->timestamp('occurred_at');
            $table->string('visitor_hash', 64)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('application_hash', 64)->nullable();
            $table->string('tier', 8)->nullable();
            $table->unsignedSmallInteger('intake_year')->nullable();
            $table->string('source_page')->nullable();
            $table->json('utm')->nullable();
            $table->json('properties')->nullable();
            $table->index(['name', 'occurred_at']);
            $table->index('application_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funnel_events');
    }
};
