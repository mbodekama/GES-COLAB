<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'matricule', 'first_name', 'last_name', 'email',
        'phone', 'birth_date', 'birth_place', 'nationality',
        'marital_status', 'children_count', 'address', 'cnps_number',
        'position', 'department', 'hire_date', 'leave_balance', 'status',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'hire_date'  => 'date',
    ];

    // ── Relations ────────────────────────────────────────────
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function activeContract(): HasOne
    {
        return $this->hasOne(Contract::class)->where('status', 'active')->latestOfMany();
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    // ── Scopes ───────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByDepartment($query, string $department)
    {
        return $query->where('department', $department);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name',  'like', "%{$search}%")
                ->orWhere('matricule',  'like', "%{$search}%")
                ->orWhere('position',   'like', "%{$search}%")
                ->orWhere('email',      'like', "%{$search}%");
        });
    }

    // ── Accessors ────────────────────────────────────────────
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getInitialsAttribute(): string
    {
        return strtoupper(
            substr($this->first_name, 0, 1) .
            substr($this->last_name,  0, 1)
        );
    }

    public function getSeniorityLabelAttribute(): string
    {
        $years  = $this->hire_date->diffInYears(now());
        $months = $this->hire_date->diffInMonths(now()) % 12;

        if ($years === 0) return "{$months} mois";
        if ($months === 0) return "{$years} an(s)";
        return "{$years} an(s) {$months} mois";
    }

    public function getSeniorityYearsAttribute(): int
    {
        return $this->hire_date->diffInYears(now());
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'active'     => '<span class="badge bg-success badge-status">Actif</span>',
            'on_leave'   => '<span class="badge bg-warning text-dark badge-status">En congé</span>',
            'suspended'  => '<span class="badge bg-danger badge-status">Suspendu</span>',
            'terminated' => '<span class="badge bg-secondary badge-status">Parti</span>',
            default      => '<span class="badge bg-secondary badge-status">'.$this->status.'</span>',
        };
    }

    public function getMaritalStatusLabelAttribute(): string
    {
        return match ($this->marital_status) {
            'single'   => 'Célibataire',
            'married'  => 'Marié(e)',
            'divorced' => 'Divorcé(e)',
            'widowed'  => 'Veuf/Veuve',
            default    => $this->marital_status,
        };
    }

    // ── Helpers ──────────────────────────────────────────────
    public static function generateMatricule(): string
    {
        $last = static::withTrashed()->latest('id')->first();
        $next = $last ? (intval(substr($last->matricule, 4)) + 1) : 1;
        return 'EMP-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
