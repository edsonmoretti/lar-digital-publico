<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('itinerary_items', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time']);
        });

        Schema::table('itinerary_items', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('description');
            $table->time('start_time')->nullable()->after('start_date');
            $table->date('end_date')->nullable()->after('start_time');
            $table->time('end_time')->nullable()->after('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('itinerary_items', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'start_time', 'end_date', 'end_time']);
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
        });
    }
};
