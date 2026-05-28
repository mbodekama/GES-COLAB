<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PosteSeeder::class,      // ← En premier : crée les postes
            PermissionSeeder::class, // ← Permissions et rôles
            UserSeeder::class,       // ← Utilisateurs, employés, congés, paie
        ]);
    }
}
