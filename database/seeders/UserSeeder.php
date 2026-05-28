<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Contract;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\Poste;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ── Récupérer les postes créés par PosteSeeder ─────────
        $postes = Poste::all()->keyBy('code');

        if ($postes->isEmpty()) {
            $this->command->warn('⚠️  Aucun poste trouvé. Lancez PosteSeeder d\'abord.');
            return;
        }

        // ── Définition des employés par poste ──────────────────
        // Structure : poste_code → données employé
        $users = [
            'DG' => [
                'name'       => 'Kouamé Adjobi',
                'email'      => 'admin@gescolab.ci',
                'role'       => 'superadmin',
                'first_name' => 'Kouamé',
                'last_name'  => 'Adjobi',
                'department' => 'Direction',
                'salary'     => 2500000,
                'supervisor' => null, // Pas de N+1 pour le DG
            ],
            'DGO' => [
                'name'       => 'Adama Diallo',
                'email'      => 'dgo@gescolab.ci',
                'role'       => 'admin',
                'first_name' => 'Adama',
                'last_name'  => 'Diallo',
                'department' => 'Direction',
                'salary'     => 2000000,
                'supervisor' => 'DG',
            ],
            'CHEF_AGC' => [
                'name'       => 'Yao Kouassi',
                'email'      => 'chef.agence@gescolab.ci',
                'role'       => 'admin',
                'first_name' => 'Yao',
                'last_name'  => 'Kouassi',
                'department' => 'Direction',
                'salary'     => 1500000,
                'supervisor' => 'DGO',
            ],
            'RESP_DIST' => [
                'name'       => 'Mamadou Koné',
                'email'      => 'resp.dist@gescolab.ci',
                'role'       => 'admin',
                'first_name' => 'Mamadou',
                'last_name'  => 'Koné',
                'department' => 'Commercial',
                'salary'     => 1200000,
                'supervisor' => 'CHEF_AGC',
            ],
            'RESP_RH' => [
                'name'       => 'Awa Mbaye',
                'email'      => 'rh@gescolab.ci',
                'role'       => 'rh',
                'first_name' => 'Awa',
                'last_name'  => 'Mbaye',
                'department' => 'Ressources Humaines',
                'salary'     => 1000000,
                'supervisor' => 'DGO',
            ],
            'CHEF_SVC' => [
                'name'       => 'Kofi Otieno',
                'email'      => 'chef.service@gescolab.ci',
                'role'       => 'admin',
                'first_name' => 'Kofi',
                'last_name'  => 'Otieno',
                'department' => 'Informatique',
                'salary'     => 950000,
                'supervisor' => 'CHEF_AGC',
            ],
            'CHEF_IT' => [
                'name'       => 'Issouf Traoré',
                'email'      => 'chef.it@gescolab.ci',
                'role'       => 'informaticien',
                'first_name' => 'Issouf',
                'last_name'  => 'Traoré',
                'department' => 'Informatique',
                'salary'     => 900000,
                'supervisor' => 'CHEF_SVC',
            ],
            'SUPERV' => [
                'name'       => 'Fatou Camara',
                'email'      => 'superviseur@gescolab.ci',
                'role'       => 'admin',
                'first_name' => 'Fatou',
                'last_name'  => 'Camara',
                'department' => 'Commercial',
                'salary'     => 800000,
                'supervisor' => 'RESP_DIST',
            ],
            'CPT_SR' => [
                'name'       => 'Yao Traoré',
                'email'      => 'comptable@gescolab.ci',
                'role'       => 'comptable',
                'first_name' => 'Yao',
                'last_name'  => 'Traoré',
                'department' => 'Finance & Comptabilité',
                'salary'     => 780000,
                'supervisor' => 'DGO',
            ],
            'DEV_WEB' => [
                'name'       => 'Salimata Coulibaly',
                'email'      => 'dev@gescolab.ci',
                'role'       => 'informaticien',
                'first_name' => 'Salimata',
                'last_name'  => 'Coulibaly',
                'department' => 'Informatique',
                'salary'     => 650000,
                'supervisor' => 'CHEF_IT',
            ],
            'ASST_RH' => [
                'name'       => 'Mariame Sanogo',
                'email'      => 'asst.rh@gescolab.ci',
                'role'       => 'user',
                'first_name' => 'Mariame',
                'last_name'  => 'Sanogo',
                'department' => 'Ressources Humaines',
                'salary'     => 420000,
                'supervisor' => 'RESP_RH',
            ],
            'COMM' => [
                'name'       => 'Mamadou Bah',
                'email'      => 'commercial@gescolab.ci',
                'role'       => 'user',
                'first_name' => 'Mamadou',
                'last_name'  => 'Bah',
                'department' => 'Commercial',
                'salary'     => 500000,
                'supervisor' => 'SUPERV',
            ],
            'STAGE' => [
                'name'       => 'Jean Kouassi',
                'email'      => 'stagiaire@gescolab.ci',
                'role'       => 'user',
                'first_name' => 'Jean',
                'last_name'  => 'Kouassi',
                'department' => 'Commercial',
                'salary'     => 120000,
                'supervisor' => 'SUPERV',
            ],
            // Compte de démo pour l'employé standard
            'EMPLOYE_DEMO' => [
                'poste_code' => 'COMM', // Poste partagé
                'name'       => 'Demo Employé',
                'email'      => 'employe@gescolab.ci',
                'role'       => 'user',
                'first_name' => 'Demo',
                'last_name'  => 'Employé',
                'department' => 'Commercial',
                'salary'     => 450000,
                'supervisor' => 'SUPERV',
            ],
        ];

        // ── Créer d'abord tous les users/employés sans supervisor ──
        // (pour pouvoir résoudre les références ensuite)
        $createdEmployees = [];

        foreach ($users as $posteCode => $data) {
            // Gérer les postes partagés (ex: EMPLOYE_DEMO utilise COMM)
            $realPosteCode = $data['poste_code'] ?? $posteCode;
            $poste         = $postes->get($realPosteCode);

            if (!$poste) {
                $this->command->warn("⚠️  Poste « {$realPosteCode} » introuvable, ignoré.");
                continue;
            }

            // Créer l'utilisateur
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'     => $data['name'],
                    'password' => Hash::make('password'),
                ]
            );
            $user->syncRoles([$data['role']]);

            // Créer l'employé (sans supervisor_id pour l'instant)
            $employee = Employee::firstOrCreate(
                ['email' => $data['email']],
                [
                    'user_id'        => $user->id,
                    'matricule'      => Employee::generateMatricule(),
                    'first_name'     => $data['first_name'],
                    'last_name'      => $data['last_name'],
                    'email'          => $data['email'],
                    'position'       => $poste->title,
                    'poste_id'       => $poste->id,
                    'department'     => $data['department'],
                    'hire_date'      => $this->randomHireDate($poste->level),
                    'nationality'    => 'Ivoirienne',
                    'marital_status' => 'single',
                    'leave_balance'  => 30,
                    'status'         => 'active',
                    'supervisor_id'  => null, // Rempli après
                ]
            );

            // Créer le contrat si absent
            if ($employee->contracts()->doesntExist()) {
                $isIntern = $poste->code === 'STAGE';
                $employee->contracts()->create([
                    'contract_number' => Contract::generateNumber(),
                    'type'            => $isIntern ? 'internship' : 'cdi',
                    'start_date'      => $employee->hire_date,
                    'end_date'        => $isIntern
                        ? now()->addMonths(6)->format('Y-m-d')
                        : null,
                    'position'        => $poste->title,
                    'department'      => $data['department'],
                    'base_salary'     => $data['salary'],
                    'status'          => 'active',
                    'signed_at'       => now(),
                ]);
            }

            // Stocker pour résolution du supervisor_id
            $createdEmployees[$posteCode] = [
                'employee'       => $employee,
                'supervisor_code' => $data['supervisor'],
            ];
        }

        $this->command->info('✅ Employés créés ('.count($createdEmployees).').');

        // ── Résoudre les supervisor_id ─────────────────────────
        // Maintenant que tous les employés existent, on peut lier les N+1
        foreach ($createdEmployees as $posteCode => $data) {
            $employee      = $data['employee'];
            $supervisorCode = $data['supervisor_code'];

            if (!$supervisorCode) continue;

            // Trouver le supervisor dans les employés créés
            $supervisorData = $createdEmployees[$supervisorCode] ?? null;
            if (!$supervisorData) continue;

            $supervisor = $supervisorData['employee'];

            if ($employee->supervisor_id !== $supervisor->id) {
                $employee->update(['supervisor_id' => $supervisor->id]);
            }
        }

        $this->command->info('✅ Liens hiérarchiques N+1 configurés.');

        // ── Congés de démo ─────────────────────────────────────
        $employees = Employee::all();

        if ($employees->count() > 0 && Leave::count() === 0) {
            $rhUser = User::role('rh')->first();

            // Congé annuel approuvé — Chef IT
            $emp1 = Employee::whereHas('poste', fn($q) => $q->where('code', 'CHEF_IT'))
                ->first();
            if ($emp1) {
                Leave::create([
                    'employee_id'   => $emp1->id,
                    'leave_number'  => Leave::generateNumber(),
                    'type'          => 'annual',
                    'start_date'    => now()->addDays(5),
                    'end_date'      => now()->addDays(15),
                    'duration_days' => 10,
                    'reason'        => 'Congé annuel planifié',
                    'status'        => 'approved',
                    'workflow_step' => 'approved',
                    'approved_by'   => $rhUser?->id,
                    'approved_at'   => now(),
                ]);
            }

            // Permission en attente N+1 — Commercial
            $emp2 = Employee::whereHas('poste', fn($q) => $q->where('code', 'COMM'))
                ->where('email', 'commercial@gescolab.ci')
                ->first();
            if ($emp2) {
                Leave::create([
                    'employee_id'   => $emp2->id,
                    'leave_number'  => Leave::generateNumber(),
                    'type'          => 'permission',
                    'start_date'    => now()->addDay(),
                    'end_date'      => now()->addDay(),
                    'duration_days' => 1,
                    'reason'        => 'Rendez-vous médical',
                    'status'        => 'pending',
                    'workflow_step' => 'pending_n1',
                ]);
            }

            // Congé en attente RH — Assistante RH
            $emp3 = Employee::whereHas('poste', fn($q) => $q->where('code', 'ASST_RH'))
                ->first();
            if ($emp3) {
                Leave::create([
                    'employee_id'   => $emp3->id,
                    'leave_number'  => Leave::generateNumber(),
                    'type'          => 'annual',
                    'start_date'    => now()->addDays(3),
                    'end_date'      => now()->addDays(8),
                    'duration_days' => 5,
                    'reason'        => 'Congé annuel',
                    'status'        => 'pending',
                    'workflow_step' => 'pending_rh',
                ]);
            }

            // Congé refusé — Stagiaire
            $emp4 = Employee::whereHas('poste', fn($q) => $q->where('code', 'STAGE'))
                ->first();
            if ($emp4) {
                Leave::create([
                    'employee_id'      => $emp4->id,
                    'leave_number'     => Leave::generateNumber(),
                    'type'             => 'exceptional',
                    'start_date'       => now()->subDays(3),
                    'end_date'         => now()->subDays(2),
                    'duration_days'    => 2,
                    'reason'           => 'Événement familial',
                    'status'           => 'rejected',
                    'workflow_step'    => 'rejected',
                    'approved_by'      => $rhUser?->id,
                    'rejection_reason' => 'Période de forte activité',
                ]);
            }

            $this->command->info('✅ Congés de démo créés.');
        }

        // ── Fiches de paie de démo ─────────────────────────────
        if (Payroll::count() === 0) {
            $period = now()->format('Y-m');
            $count  = 0;

            foreach (Employee::with('activeContract')->get() as $employee) {
                $baseSalary     = $employee->activeContract?->base_salary ?? 300000;
                $seniorityYears = $employee->seniority_years;
                $seniorityRate  = Payroll::seniorityRate($seniorityYears);
                $seniorityBonus = round($baseSalary * $seniorityRate / 100);
                $transport      = 30000;
                $housing        = 25000;
                $gross          = $baseSalary + $seniorityBonus + $transport + $housing;
                $cnpsEmp        = round($gross * 6.3  / 100);
                $cnpsEmpr       = round($gross * 12   / 100);
                $igr            = Payroll::calculateIGR($gross - $cnpsEmp);
                $net            = $gross - $cnpsEmp - $igr;

                Payroll::firstOrCreate(
                    ['employee_id' => $employee->id, 'period' => $period],
                    [
                        'base_salary'         => $baseSalary,
                        'seniority_bonus'     => $seniorityBonus,
                        'seniority_rate'      => $seniorityRate,
                        'transport_allowance' => $transport,
                        'housing_allowance'   => $housing,
                        'gross_salary'        => $gross,
                        'cnps_employee'       => $cnpsEmp,
                        'cnps_employer'       => $cnpsEmpr,
                        'igr'                 => $igr,
                        'net_salary'          => $net,
                        'worked_days'         => 26,
                        'leave_days'          => 0,
                    ]
                );
                $count++;
            }

            $this->command->info("✅ Fiches de paie de démo créées ({$count}).");
        }

        // ── Résumé final ───────────────────────────────────────
        $this->command->info('');
        $this->command->info('─────────────────────────────────────────');
        $this->command->info('  Comptes de connexion disponibles :');
        $this->command->info('─────────────────────────────────────────');
        $this->command->info('  Superadmin  : admin@gescolab.ci');
        $this->command->info('  DGO         : dgo@gescolab.ci');
        $this->command->info('  RH          : rh@gescolab.ci');
        $this->command->info('  Comptable   : comptable@gescolab.ci');
        $this->command->info('  IT          : chef.it@gescolab.ci');
        $this->command->info('  Employé     : employe@gescolab.ci');
        $this->command->info('  Mot de passe: password (pour tous)');
        $this->command->info('─────────────────────────────────────────');
    }

    // ── Date d'embauche aléatoire selon le niveau ─────────────
    private function randomHireDate(int $level): string
    {
        // Plus le niveau est élevé, plus l'ancienneté est grande
        $yearsBack = match (true) {
            $level >= 9 => rand(8, 15),
            $level >= 7 => rand(5, 10),
            $level >= 5 => rand(3, 7),
            $level >= 3 => rand(1, 4),
            default     => rand(0, 2),
        };

        return now()
            ->subYears($yearsBack)
            ->subMonths(rand(0, 11))
            ->format('Y-m-d');
    }
}
