<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->date('date_renouvellement')->nullable()->after('signed_at');
            $table->date('date_resiliation')->nullable()->after('date_renouvellement');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['date_renouvellement', 'date_resiliation']);
        });
    }
};
