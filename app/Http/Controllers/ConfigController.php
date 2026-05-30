<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ConfigController extends Controller
{
    public function index()
    {
        $systemInfo = [
            'php_version'     => PHP_VERSION,
            'laravel_version' => app()->version(),
            'db_connection'   => config('database.default'),
            'cache_driver'    => config('cache.default'),
            'disk_free'       => $this->diskUsage(),
        ];

        // ── Lire depuis la table settings en priorité ─────────────
        $cfg = $this->getSettings();

        return view('config.index', compact('cfg', 'systemInfo'));
    }

    public function updateGeneral(Request $request)
    {
        $this->logEntry();
        $validated = $request->validate([
            'company_name'          => 'required|string|max:100',
            'company_initials'      => 'nullable|string|max:3',
            'company_address'       => 'nullable|string|max:200',
            'company_phone'         => 'nullable|string|max:30',
            'company_email'         => 'nullable|email',
            'company_website'       => 'nullable|url|max:100',
            'default_language'      => 'required|in:fr,en',
            'currency'              => 'required|string|max:10',
            'working_days_per_week' => 'required|integer|in:5,6',
        ]);

        $this->saveSettings($validated);

        return back()->with('success', 'Paramètres généraux enregistrés.');
    }

    public function updatePayroll(Request $request)
    {
        $this->logEntry();
        $validated = $request->validate([
            'cnps_employer_rate'  => 'required|numeric|min:0|max:30',
            'cnps_employee_rate'  => 'required|numeric|min:0|max:30',
            'transport_allowance' => 'required|numeric|min:0',
            'housing_allowance'   => 'required|numeric|min:0',
            'payroll_day'         => 'required|integer|min:1|max:31',
        ]);

        $this->saveSettings($validated);

        return back()->with('success', 'Paramètres de paie enregistrés.');
    }

    public function updateLeaves(Request $request)
    {
        $this->logEntry();
        $validated = $request->validate([
            'annual_leave_days'        => 'required|integer|min:1|max:90',
            'sick_leave_days'          => 'required|integer|min:1',
            'exceptional_leave_days'   => 'required|integer|min:1',
            'max_permission_per_month' => 'required|integer|min:1',
        ]);

        $this->saveSettings($validated);

        return back()->with('success', 'Paramètres de congés enregistrés.');
    }

    // ── Lire tous les settings (DB en priorité, config() en fallback) ──
    private function getSettings(): array
    {
        // Récupérer depuis la base avec cache 1h
        $dbSettings = Cache::remember('gescolab_settings', 3600, function () {
            return DB::table('settings')->pluck('value', 'key')->toArray();
        });

        // Fusionner avec les valeurs par défaut de config/gescolab.php
        return array_merge([
            // Généraux
            'company_name'          => config('gescolab.company_name',     'GES-COLAB'),
            'company_initials'      => config('gescolab.company_initials', ''),
            'company_address'       => config('gescolab.company_address',  ''),
            'company_phone'         => config('gescolab.company_phone',    ''),
            'company_email'         => config('gescolab.company_email',    ''),
            'company_website'       => config('gescolab.company_website',  ''),
            'default_language'      => config('gescolab.default_language', 'fr'),
            'currency'              => config('gescolab.currency',         'FCFA'),
            'working_days_per_week' => config('gescolab.working_days_per_week', 5),

            // Paie
            'cnps_employer_rate'    => config('gescolab.cnps_employer_rate',  12),
            'cnps_employee_rate'    => config('gescolab.cnps_employee_rate',   6.3),
            'transport_allowance'   => config('gescolab.transport_allowance', 30000),
            'housing_allowance'     => config('gescolab.housing_allowance',   25000),
            'payroll_day'           => config('gescolab.payroll_day',         25),

            // Congés
            'annual_leave_days'        => config('gescolab.annual_leave_days',        30),
            'sick_leave_days'          => config('gescolab.sick_leave_days',          15),
            'exceptional_leave_days'   => config('gescolab.exceptional_leave_days',    5),
            'max_permission_per_month' => config('gescolab.max_permission_per_month',  2),
        ], $dbSettings); // $dbSettings écrase les valeurs par défaut
    }

    // ── Sauvegarder dans la table settings + vider le cache ───────
    private function saveSettings(array $values): void
    {
        foreach ($values as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now()]
            );
        }

        // Vider le cache pour forcer le rechargement
        Cache::forget('gescolab_settings');
    }

    private function diskUsage(): string
    {
        $bytes = @disk_free_space(base_path());
        if ($bytes === false) return 'N/A';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pow   = min(floor(log($bytes) / log(1024)), count($units) - 1);
        return round($bytes / (1024 ** $pow), 2) . ' ' . $units[$pow] . ' libres';
    }
}
