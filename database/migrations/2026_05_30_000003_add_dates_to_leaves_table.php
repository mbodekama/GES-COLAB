<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->date('date_approbation')->nullable()->after('approved_at');
            $table->date('date_rejet')->nullable()->after('date_approbation');
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropColumn(['date_approbation', 'date_rejet']);
        });
    }
};
