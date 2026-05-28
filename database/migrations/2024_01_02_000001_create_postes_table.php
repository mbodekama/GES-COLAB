<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Table des postes ──────────────────────────────────
        Schema::create('postes', function (Blueprint $table) {
            $table->id();
            $table->string('title');                        // Intitulé du poste
            $table->string('code')->unique();               // Code court unique ex: DGO, CHEF_SVC
            $table->string('department')->nullable();       // Département rattaché
            $table->unsignedTinyInteger('level');           // Niveau hiérarchique 1 (bas) → 10 (haut)
            $table->boolean('can_be_n1')->default(false);   // Ce poste peut valider les permissions
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── Ajouter poste_id sur employees ───────────────────
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('poste_id')
                  ->nullable()
                  ->constrained('postes')
                  ->nullOnDelete()
                  ->after('department');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['poste_id']);
            $table->dropColumn('poste_id');
        });

        Schema::dropIfExists('postes');
    }
};
