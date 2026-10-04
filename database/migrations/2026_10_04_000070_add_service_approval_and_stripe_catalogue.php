<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Service fees are shown only to a student whose profile our team has approved (services_approved_at), and to staff.
 * Each price record remembers the Stripe Price it maps to, per mode, so Checkout charges a fixed catalogue price chosen
 * by the server; a payment records the Stripe Price it was taken at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->timestamp('services_approved_at')->nullable()->after('service_tier_id');
            $table->foreignId('services_approved_by')->nullable()->after('services_approved_at')->constrained('users')->nullOnDelete();
        });
        Schema::table('tier_prices', function (Blueprint $table) {
            $table->string('stripe_product_id', 64)->nullable(); // stripe_price_id exists since the platform tables
            $table->unsignedInteger('stripe_price_amount')->nullable();
            $table->boolean('stripe_livemode')->nullable();
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->string('stripe_price_id', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payments', fn (Blueprint $table) => $table->dropColumn('stripe_price_id'));
        Schema::table('tier_prices', fn (Blueprint $table) => $table->dropColumn(['stripe_product_id', 'stripe_price_amount', 'stripe_livemode']));
        Schema::table('applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('services_approved_by');
            $table->dropColumn('services_approved_at');
        });
    }
};
