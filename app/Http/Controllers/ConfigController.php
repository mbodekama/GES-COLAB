<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

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

        return view('config.index', [
            'cfg'        => config('gescolab'),
            'systemInfo' => $systemInfo,
        ]);
    }

//    public function updateGeneral(Request $request)
//    {
//        $validated = $request->validate([
//            'company_name'          => 'required|string|max:100',
//            'company_address'       => 'nullable|string|max:200',
//            'company_phone'         => 'nullable|string|max:30',
//            'company_email'         => 'nullable|email',
//            'default_language'      => 'required|in:fr,en',
//            'currency'              => 'required|string|max:10',
//            'working_days_per_week' => 'required|integer|in:5,6',
//        ]);
//
//        $this->writeEnv([
//            'COMPANY_NAME'            => $validated['company_name'],
//            'APP_LOCALE'              => $validated['default_language'],
//            'GESCOLAB_CURRENCY'       => $validated['currency'],
//            'GESCOLAB_WORKING_DAYS'   => $validated['working_days_per_week'],
//        ]);
//
//        Artisan::call('config:clear');
//
//        return back()->with('success', 'Paramètres généraux enregistrés.');
//    }
//
//    public function updatePayroll(Request $request)
//    {
//        $validated = $request->validate([
//            'cnps_employer_rate'  => 'required|numeric|min:0|max:30',
//            'cnps_employee_rate'  => 'required|numeric|min:0|max:30',
//            'transport_allowance' => 'required|numeric|min:0',
//            'housing_allowance'   => 'required|numeric|min:0',
//            'payroll_day'         => 'required|integer|min:1|max:31',
//        ]);
//
//        $this->writeEnv([
//            'GESCOLAB_CNPS_EMPLOYER_RATE'  => $validated['cnps_employer_rate'],
//            'GESCOLAB_CNPS_EMPLOYEE_RATE'  => $validated['cnps_employee_rate'],
//            'GESCOLAB_TRANSPORT_ALLOWANCE' => $validated['transport_allowance'],
//            'GESCOLAB_HOUSING_ALLOWANCE'   => $validated['housing_allowance'],
//            'GESCOLAB_PAYROLL_DAY'         => $validated['payroll_day'],
//        ]);
//
//        Artisan::call('config:clear');
//
//        return back()->with('success', 'Paramètres de paie enregistrés.');
//    }
//
//    public function updateLeaves(Request $request)
//    {
//        $validated = $request->validate([
//            'annual_leave_days'        => 'required|integer|min:1|max:90',
//            'sick_leave_days'          => 'required|integer|min:1',
//            'exceptional_leave_days'   => 'required|integer|min:1',
//            'max_permission_per_month' => 'required|integer|min:1',
//        ]);
//
//        $this->writeEnv([
//            'GESCOLAB_ANNUAL_LEAVE_DAYS'        => $validated['annual_leave_days'],
//            'GESCOLAB_SICK_LEAVE_DAYS'          => $validated['sick_leave_days'],
//            'GESCOLAB_EXCEPTIONAL_LEAVE_DAYS'   => $validated['exceptional_leave_days'],
//            'GESCOLAB_MAX_PERMISSION_PER_MONTH' => $validated['max_permission_per_month'],
//        ]);
//
//        Artisan::call('config:clear');
//
//        return back()->with('success', 'Paramètres de congés enregistrés.');
//    }

    // ── Helpers ──────────────────────────────────────────────


    private function diskUsage(): string
    {
        $bytes = @disk_free_space(base_path());
        if ($bytes === false) return 'N/A';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pow   = min(floor(log($bytes) / log(1024)), count($units) - 1);
        return round($bytes / (1024 ** $pow), 2) . ' ' . $units[$pow] . ' libres';
    }


    public function updateGeneral(Request $request)
    {
        $validated = $request->validate([
            'company_name'          => 'required|string|max:100',
            'company_address'       => 'nullable|string|max:200',
            'company_phone'         => 'nullable|string|max:30',
            'company_email'         => 'nullable|email',
            'default_language'      => 'required|in:fr,en',
            'currency'              => 'required|string|max:10',
            'working_days_per_week' => 'required|integer|in:5,6',
        ]);

        foreach ($validated as $key => $value) {
            \DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now()]
            );
        }

        // Vider le cache pour recharger les valeurs
        \Cache::forget('gescolab_settings');

        return back()->with('success', 'Paramètres généraux enregistrés.');
    }

    public function updatePayroll(Request $request)
    {
        $validated = $request->validate([
            'cnps_employer_rate'  => 'required|numeric|min:0|max:30',
            'cnps_employee_rate'  => 'required|numeric|min:0|max:30',
            'transport_allowance' => 'required|numeric|min:0',
            'housing_allowance'   => 'required|numeric|min:0',
            'payroll_day'         => 'required|integer|min:1|max:31',
        ]);

        foreach ($validated as $key => $value) {
            \DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now()]
            );
        }

        \Cache::forget('gescolab_settings');

        return back()->with('success', 'Paramètres de paie enregistrés.');
    }

    public function updateLeaves(Request $request)
    {
        $validated = $request->validate([
            'annual_leave_days'        => 'required|integer|min:1|max:90',
            'sick_leave_days'          => 'required|integer|min:1',
            'exceptional_leave_days'   => 'required|integer|min:1',
            'max_permission_per_month' => 'required|integer|min:1',
        ]);

        foreach ($validated as $key => $value) {
            \DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now()]
            );
        }

        \Cache::forget('gescolab_settings');

        return back()->with('success', 'Paramètres de congés enregistrés.');
    }
}
