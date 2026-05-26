<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Contract;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\SalaryGrid;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $gridModels = SalaryGrid::orderBy('level')->get();

        $users = [
            [
                'name' => 'Adama Diallo', 'email' => 'admin@gescolab.ci',
                'role' => 'superadmin',
                'emp'  => ['first_name' => 'Adama', 'last_name' => 'Diallo', 'position' => 'Directeur Général', 'department' => 'Direction', 'hire_date' => '2020-01-01', 'salary' => 2000000, 'grid' => 5],
            ],
            [
                'name' => 'Awa Mbaye', 'email' => 'rh@gescolab.ci',
                'role' => 'rh',
                'emp'  => ['first_name' => 'Awa', 'last_name' => 'Mbaye', 'position' => 'Responsable RH', 'department' => 'Ressources Humaines', 'hire_date' => '2021-03-15', 'salary' => 920000, 'grid' => 4],
            ],
            [
                'name' => 'Yao Traoré', 'email' => 'comptable@gescolab.ci',
                'role' => 'comptable',
                'emp'  => ['first_name' => 'Yao', 'last_name' => 'Traoré', 'position' => 'Comptable Senior', 'department' => 'Finance & Comptabilité', 'hire_date' => '2021-06-01', 'salary' => 780000, 'grid' => 4],
            ],
            [
                'name' => 'Kofi Otieno', 'email' => 'it@gescolab.ci',
                'role' => 'informaticien',
                'emp'  => ['first_name' => 'Kofi', 'last_name' => 'Otieno', 'position' => 'Chef de Projet IT', 'department' => 'Informatique', 'hire_date' => '2022-01-10', 'salary' => 850000, 'grid' => 4],
            ],
            [
                'name' => 'Salimata Coulibaly', 'email' => 'employe@gescolab.ci',
                'role' => 'user',
                'emp'  => ['first_name' => 'Salimata', 'last_name' => 'Coulibaly', 'position' => 'Développeur Web', 'department' => 'Informatique', 'hire_date' => '2023-09-01', 'salary' => 650000, 'grid' => 3],
            ],
            [
                'name' => 'Mamadou Bah', 'email' => 'mbah@gescolab.ci',
                'role' => 'user',
                'emp'  => ['first_name' => 'Mamadou', 'last_name' => 'Bah', 'position' => 'Commercial', 'department' => 'Commercial', 'hire_date' => '2023-02-15', 'salary' => 500000, 'grid' => 3],
            ],
            [
                'name' => 'Fatou Sanogo', 'email' => 'fsanogo@gescolab.ci',
                'role' => 'user',
                'emp'  => ['first_name' => 'Fatou', 'last_name' => 'Sanogo', 'position' => 'Assistante RH', 'department' => 'Ressources Humaines', 'hire_date' => '2022-11-01', 'salary' => 420000, 'grid' => 3],
            ],
            [
                'name' => 'Jean Kouassi', 'email' => 'jkouassi@gescolab.ci',
                'role' => 'user',
                'emp'  => ['first_name' => 'Jean', 'last_name' => 'Kouassi', 'position' => 'Stagiaire Marketing', 'department' => 'Commercial', 'hire_date' => '2024-01-15', 'salary' => 120000, 'grid' => 1],
            ],
        ];

        foreach ($users as $data) {

            // ── Utilisateur ───────────────────────────────────
            $u = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'     => $data['name'],
                    'password' => Hash::make('password'),
                ]
            );
            $u->syncRoles([$data['role']]);

            // ── Employé ───────────────────────────────────────
            $e    = $data['emp'];
            $grid = $gridModels->firstWhere('level', $e['grid']);

            $employee = Employee::firstOrCreate(
                ['email' => $data['email']],
                [
                    'user_id'        => $u->id,
                    'matricule'      => Employee::generateMatricule(),
                    'first_name'     => $e['first_name'],
                    'last_name'      => $e['last_name'],
                    'email'          => $data['email'],
                    'position'       => $e['position'],
                    'department'     => $e['department'],
                    'hire_date'      => $e['hire_date'],
                    'nationality'    => 'Ivoirienne',
                    'marital_status' => 'single',
                    'leave_balance'  => 30,
                    'status'         => 'active',
                ]
            );

            // ── Contrat ───────────────────────────────────────
            if ($employee->contracts()->doesntExist()) {
                $isIntern = $e['grid'] === 1;
                $employee->contracts()->create([
                    'contract_number' => Contract::generateNumber(),
                    'salary_grid_id'  => $grid?->id,
                    'type'            => $isIntern ? 'internship' : 'cdi',
                    'start_date'      => $e['hire_date'],
                    'end_date'        => $isIntern ? '2024-07-15' : null,
                    'position'        => $e['position'],
                    'department'      => $e['department'],
                    'base_salary'     => $e['salary'],
                    'status'          => 'active',
                    'signed_at'       => now(),
                ]);
            }
        }

        $this->command->info('✅ Utilisateurs et employés créés.');

        // ── Congés de démo ────────────────────────────────────
        $employees = Employee::all();

        if ($employees->count() > 0 && Leave::count() === 0) {

            Leave::create([
                'employee_id'   => $employees->get(3)?->id ?? $employees->first()->id,
                'leave_number'  => Leave::generateNumber(),
                'type'          => 'annual',
                'start_date'    => now()->addDays(5),
                'end_date'      => now()->addDays(15),
                'duration_days' => 10,
                'reason'        => 'Congé annuel planifié',
                'status'        => 'approved',
                'approved_by'   => User::role('rh')->first()?->id,
                'approved_at'   => now(),
            ]);

            Leave::create([
                'employee_id'   => $employees->get(1)?->id ?? $employees->first()->id,
                'leave_number'  => Leave::generateNumber(),
                'type'          => 'permission',
                'start_date'    => now()->addDay(),
                'end_date'      => now()->addDay(),
                'duration_days' => 1,
                'reason'        => 'Rendez-vous médical',
                'status'        => 'pending',
            ]);

            Leave::create([
                'employee_id'      => $employees->get(5)?->id ?? $employees->first()->id,
                'leave_number'     => Leave::generateNumber(),
                'type'             => 'exceptional',
                'start_date'       => now()->subDays(3),
                'end_date'         => now()->subDays(2),
                'duration_days'    => 2,
                'reason'           => 'Événement familial',
                'status'           => 'rejected',
                'approved_by'      => User::role('rh')->first()?->id,
                'rejection_reason' => 'Période de forte activité',
            ]);
        }

        $this->command->info('✅ Congés de démo créés.');

        // ── Fiches de paie de démo ────────────────────────────
        if (Payroll::count() === 0) {
            $period = now()->format('Y-m');

            foreach ($employees->take(5) as $employee) {
                $baseSalary     = $employee->activeContract?->base_salary ?? 300000;
                $seniorityYears = $employee->seniority_years;
                $seniorityRate  = Payroll::seniorityRate($seniorityYears);
                $seniorityBonus = round($baseSalary * $seniorityRate / 100);
                $transport      = 30000;
                $housing        = 25000;
                $gross          = $baseSalary + $seniorityBonus + $transport + $housing;
                $cnpsEmp        = round($gross * 6.3 / 100);
                $cnpsEmpr       = round($gross * 12 / 100);
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
            }

            $this->command->info('✅ Fiches de paie de démo créées.');
        }
    }
}
