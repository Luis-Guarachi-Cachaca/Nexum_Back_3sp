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
        Schema::table('portfolios', function (Blueprint $table) {
            $table->boolean('show_projects')->default(true)->after('global_privacy');
            $table->boolean('show_skills')->default(true)->after('show_projects');
            $table->boolean('show_experience')->default(true)->after('show_skills');
            $table->boolean('show_certifications')->default(true)->after('show_experience');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portfolios', function (Blueprint $table) {
            $table->dropColumn(['show_projects', 'show_skills', 'show_experience', 'show_certifications']);
        });
    }
};
