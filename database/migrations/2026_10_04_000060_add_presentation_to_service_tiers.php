<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Short positioning line and an optional badge ("Most popular") for each service, kept as data, not template copy. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_tiers', function (Blueprint $table) {
            $table->string('tagline', 120)->nullable()->after('name');
            $table->string('badge', 32)->nullable()->after('tagline');
        });
    }

    public function down(): void
    {
        Schema::table('service_tiers', fn (Blueprint $table) => $table->dropColumn(['tagline', 'badge']));
    }
};
