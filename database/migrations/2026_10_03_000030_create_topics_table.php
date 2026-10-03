<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();   // ucas-2027, ucat-2026, student-visa, graduate-visa, gmc-registration, costs-2026
            $table->string('title');
            $table->string('cycle', 16)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('topics'); }
};
