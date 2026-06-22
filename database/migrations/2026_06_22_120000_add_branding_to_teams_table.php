<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('brand_logo_path')->nullable()->after('subdomain');
            $table->string('brand_background_color', 7)->nullable()->after('brand_logo_path');
            $table->string('brand_foreground_color', 7)->nullable()->after('brand_background_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn([
                'brand_logo_path',
                'brand_background_color',
                'brand_foreground_color',
            ]);
        });
    }
};
