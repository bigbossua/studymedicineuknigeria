<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The audit trail of every published-relevant change to a reference fact: who or what changed it (an admin, the
 * worksheet import, the source watcher, the reference sync), and the values before and after. Written by the model,
 * so no route into reference_facts can skip it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fact_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reference_fact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('via', 96);
            $table->json('before');
            $table->json('after');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['reference_fact_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fact_changes');
    }
};
