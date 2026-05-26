<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryGrid extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'category', 'level', 'min_salary', 'max_salary',
        'base_salary', 'transport_allowance', 'housing_allowance',
        'meal_allowance', 'is_active', 'description',
    ];

    protected $casts = [
        'min_salary'          => 'decimal:2',
        'max_salary'          => 'decimal:2',
        'base_salary'         => 'decimal:2',
        'transport_allowance' => 'decimal:2',
        'housing_allowance'   => 'decimal:2',
        'meal_allowance'      => 'decimal:2',
        'is_active'           => 'boolean',
    ];

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getLevelLabelAttribute(): string
    {
        return match ($this->level) {
            1 => 'Niveau 1 — Employés',
            2 => 'Niveau 2 — Employés qualifiés',
            3 => 'Niveau 3 — Agents de maîtrise',
            4 => 'Niveau 4 — Cadres',
            5 => 'Niveau 5 — Cadres supérieurs',
            default => "Niveau {$this->level}",
        };
    }
}
