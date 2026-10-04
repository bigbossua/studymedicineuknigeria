<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Retention (privacy notice): when a document file was deleted and when an application's content was anonymised. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_versions', fn (Blueprint $t) => $t->timestamp('purged_at')->nullable());
        Schema::table('applications', fn (Blueprint $t) => $t->timestamp('anonymised_at')->nullable());
    }

    public function down(): void
    {
        Schema::table('document_versions', fn (Blueprint $t) => $t->dropColumn('purged_at'));
        Schema::table('applications', fn (Blueprint $t) => $t->dropColumn('anonymised_at'));
    }
};
