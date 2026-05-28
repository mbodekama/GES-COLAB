<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            // Étape actuelle du workflow
            $table->enum('workflow_step', [
                'pending_n1',    // En attente validation N+1 (permissions)
                'pending_rh',    // En attente validation RH
                'approved',      // Validé
                'rejected',      // Refusé
            ])->default('pending_rh')->after('status');

            // Validateur N+1
            $table->foreignId('n1_validator_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->after('workflow_step');

            $table->timestamp('n1_validated_at')->nullable()->after('n1_validator_id');
            $table->text('n1_comment')->nullable()->after('n1_validated_at');
        });

        // Ajouter le rôle N+1 à la table employees
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('supervisor_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete()
                ->after('department');
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropForeign(['n1_validator_id']);
            $table->dropColumn(['workflow_step', 'n1_validator_id', 'n1_validated_at', 'n1_comment']);
        });
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['supervisor_id']);
            $table->dropColumn('supervisor_id');
        });
    }
};
