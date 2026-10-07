<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Google Search Console performance rows for the site's own property (smukn:gsc-sync): aggregated search data,
        // no visitor identifiers. Google already withholds rare (anonymised) queries. Hashes keep the unique key short on MySQL.
        Schema::create('search_performance', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('query', 512);
            $table->string('page', 512);
            $table->string('country', 3);
            $table->char('row_hash', 40)->unique();
            $table->unsignedInteger('clicks');
            $table->unsignedInteger('impressions');
            $table->decimal('position', 7, 2);
            $table->index(['date', 'country']);
        });
        // Google's own answer for each sitemap URL (URL Inspection API): indexed or not, and why.
        Schema::create('search_index_status', function (Blueprint $table) {
            $table->id();
            $table->string('url', 512);
            $table->char('url_hash', 40)->unique();
            $table->string('verdict', 24)->nullable();
            $table->string('coverage_state', 160)->nullable();
            $table->string('indexing_state', 48)->nullable();
            $table->string('page_fetch_state', 48)->nullable();
            $table->string('google_canonical', 512)->nullable();
            $table->timestamp('last_crawl_at')->nullable();
            $table->timestamp('inspected_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_index_status');
        Schema::dropIfExists('search_performance');
    }
};
