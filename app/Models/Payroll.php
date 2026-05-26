<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'period', 'base_salary', 'seniority_bonus',
        'seniority_rate', 'transport_allowance', 'housing_allowance',
        'meal_allowance', 'gross_salary', 'cnps_employee', 'cnps_employer',
        'igr', 'other_deductions', 'net_salary', 'worked_days', 'leave_days',
    ];

    protected $casts = [
        'base_salary'         => 'decimal:2',
        'seniority_bonus'     => 'decimal:2',
        'gross_salary'        => 'decimal:2',
        'cnps_employee'       => 'decimal:2',
        'cnps_employer'       => 'decimal:2',
        'igr'                 => 'decimal:2',
        'net_salary'          => 'decimal:2',
        'transport_allowance' => 'decimal:2',
        'housing_allowance'   => 'decimal:2',
    ];

    // ── Relations ────────────────────────────────────────────
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // ── Scopes ───────────────────────────────────────────────
    public function scopeForPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    // ── Calculs IGR (barème Côte d'Ivoire) ───────────────────
    public static function calculateIGR(float $imposable): float
    {
        $tranches = [
            [0,      75000,  0],
            [75001,  240000, 0.02],
            [240001, 800000, 0.075],
            [800001, 2400000,0.115],
            [2400001,8000000,0.15],
            [8000001,PHP_INT_MAX, 0.16],
        ];

        $igr = 0;
        foreach ($tranches as [$min, $max, $taux]) {
            if ($imposable <= $min) break;
            $igr += (min($imposable, $max) - $min) * $taux;
        }

        return round($igr);
    }

    // ── Calcul ancienneté ────────────────────────────────────
    public static function seniorityRate(int $years): float
    {
        return match (true) {
            $years >= 25 => 25,
            $years >= 20 => 20,
            $years >= 15 => 15,
            $years >= 10 => 10,
            $years >= 5  => 5,
            $years >= 2  => 2,
            default      => 0,
        };
    }
}
