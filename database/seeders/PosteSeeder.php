<?php

namespace Database\Seeders;

use App\Models\Poste;
use Illuminate\Database\Seeder;

class PosteSeeder extends Seeder
{
    public function run(): void
    {
        $postes = [
            // ── Direction ─────────────────────────────────────
            [
                'title'       => 'Directeur Général',
                'code'        => 'DG',
                'level'       => 10,
                'can_be_n1'   => true,
                'department'  => 'Direction',
                'description' => 'Responsable de la direction générale de l\'entreprise.',
            ],
            [
                'title'       => 'Directeur Général Opérations',
                'code'        => 'DGO',
                'level'       => 9,
                'can_be_n1'   => true,
                'department'  => 'Direction',
                'description' => 'Supervise l\'ensemble des opérations.',
            ],

            // ── Management supérieur ──────────────────────────
            [
                'title'       => 'Chef d\'Agence',
                'code'        => 'CHEF_AGC',
                'level'       => 8,
                'can_be_n1'   => true,
                'department'  => null,
                'description' => 'Responsable d\'une agence ou d\'un site.',
            ],
            [
                'title'       => 'Responsable de Distribution',
                'code'        => 'RESP_DIST',
                'level'       => 7,
                'can_be_n1'   => true,
                'department'  => 'Commercial',
                'description' => 'Gère le réseau de distribution.',
            ],

            // ── Management intermédiaire ──────────────────────
            [
                'title'       => 'Responsable RH',
                'code'        => 'RESP_RH',
                'level'       => 6,
                'can_be_n1'   => true,
                'department'  => 'Ressources Humaines',
                'description' => 'Gère les ressources humaines de l\'entreprise.',
            ],
            [
                'title'       => 'Chef de Service',
                'code'        => 'CHEF_SVC',
                'level'       => 6,
                'can_be_n1'   => true,
                'department'  => null,
                'description' => 'Responsable d\'un service ou département.',
            ],
            [
                'title'       => 'Chef de Projet IT',
                'code'        => 'CHEF_IT',
                'level'       => 5,
                'can_be_n1'   => true,
                'department'  => 'Informatique',
                'description' => 'Pilote les projets informatiques.',
            ],

            // ── Supervision ───────────────────────────────────
            [
                'title'       => 'Superviseur',
                'code'        => 'SUPERV',
                'level'       => 5,
                'can_be_n1'   => true,
                'department'  => null,
                'description' => 'Supervise une équipe opérationnelle.',
            ],
            [
                'title'       => 'Comptable Senior',
                'code'        => 'CPT_SR',
                'level'       => 4,
                'can_be_n1'   => false,
                'department'  => 'Finance & Comptabilité',
                'description' => 'Gère la comptabilité générale.',
            ],

            // ── Exécution ─────────────────────────────────────
            [
                'title'       => 'Développeur Web',
                'code'        => 'DEV_WEB',
                'level'       => 3,
                'can_be_n1'   => false,
                'department'  => 'Informatique',
                'description' => 'Développement et maintenance des applications.',
            ],
            [
                'title'       => 'Assistante RH',
                'code'        => 'ASST_RH',
                'level'       => 2,
                'can_be_n1'   => false,
                'department'  => 'Ressources Humaines',
                'description' => 'Assiste le responsable RH.',
            ],
            [
                'title'       => 'Commercial',
                'code'        => 'COMM',
                'level'       => 2,
                'can_be_n1'   => false,
                'department'  => 'Commercial',
                'description' => 'Gère le portefeuille clients.',
            ],
            [
                'title'       => 'Stagiaire',
                'code'        => 'STAGE',
                'level'       => 1,
                'can_be_n1'   => false,
                'department'  => null,
                'description' => 'Personnel en stage de formation.',
            ],
        ];

        foreach ($postes as $poste) {
            Poste::firstOrCreate(
                ['code' => $poste['code']],
                array_merge($poste, ['is_active' => true])
            );
        }

        $this->command->info('✅ Postes hiérarchiques créés ('.count($postes).').');
    }
}
