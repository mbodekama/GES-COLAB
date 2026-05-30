@extends('layouts.app')
@section('page-title', 'Configuration')

@section('content')
    <div class="row g-3">

        {{-- PARAMÈTRES GÉNÉRAUX --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><i class="bi bi-building me-2"></i>Paramètres généraux</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('config.general') }}">
                        @csrf
                        <div class="row g-2 mb-3">
                            <div class="col-md-9">
                                <label class="form-label small fw-medium">Nom de l'entreprise</label>
                                <input type="text" name="company_name" class="form-control"
                                       value="{{ $cfg['company_name'] }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-medium">
                                    Initiales
                                    <span class="text-muted fw-normal">(badge PDF)</span>
                                </label>
                                <input type="text" name="company_initials" class="form-control"
                                       value="{{ $cfg['company_initials'] }}"
                                       maxlength="3" placeholder="Auto"
                                       style="text-transform:uppercase">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Adresse</label>
                            <input type="text" name="company_address" class="form-control"
                                   value="{{ $cfg['company_address'] }}">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Téléphone</label>
                                <input type="text" name="company_phone" class="form-control"
                                       value="{{ $cfg['company_phone'] }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Email</label>
                                <input type="email" name="company_email" class="form-control"
                                       value="{{ $cfg['company_email'] }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">
                                Site web
                                <span class="text-muted fw-normal">(affiché dans le pied de page PDF)</span>
                            </label>
                            <input type="url" name="company_website" class="form-control"
                                   value="{{ $cfg['company_website'] }}"
                                   placeholder="https://www.exemple.com">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Langue par défaut</label>
                                <select name="default_language" class="form-select">
                                    <option value="fr" {{ $cfg['default_language'] === 'fr' ? 'selected' : '' }}>
                                        Français
                                    </option>
                                    <option value="en" {{ $cfg['default_language'] === 'en' ? 'selected' : '' }}>
                                        English
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Devise</label>
                                <select name="currency" class="form-select">
                                    <option value="FCFA" {{ $cfg['currency'] === 'FCFA' ? 'selected' : '' }}>FCFA (XOF)</option>
                                    <option value="EUR"  {{ $cfg['currency'] === 'EUR'  ? 'selected' : '' }}>EUR (€)</option>
                                    <option value="USD"  {{ $cfg['currency'] === 'USD'  ? 'selected' : '' }}>USD ($)</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-medium">Jours ouvrables / semaine</label>
                            <select name="working_days_per_week" class="form-select">
                                <option value="5" {{ $cfg['working_days_per_week'] == 5 ? 'selected' : '' }}>
                                    5 jours (Lun–Ven)
                                </option>
                                <option value="6" {{ $cfg['working_days_per_week'] == 6 ? 'selected' : '' }}>
                                    6 jours (Lun–Sam)
                                </option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-floppy me-2"></i>Enregistrer
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- PARAMÈTRES PAIE + CONGÉS --}}
        <div class="col-md-6">

            {{-- PAIE --}}
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-cash-coin me-2"></i>Paramètres de paie</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('config.payroll') }}">
                        @csrf
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Taux CNPS employeur (%)</label>
                                <input type="number" name="cnps_employer_rate"
                                       step="0.1" class="form-control"
                                       value="{{ $cfg['cnps_employer_rate'] }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Taux CNPS salarié (%)</label>
                                <input type="number" name="cnps_employee_rate"
                                       step="0.1" class="form-control"
                                       value="{{ $cfg['cnps_employee_rate'] }}">
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Indemnité transport (FCFA)</label>
                                <input type="number" name="transport_allowance"
                                       class="form-control"
                                       value="{{ $cfg['transport_allowance'] }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Indemnité logement (FCFA)</label>
                                <input type="number" name="housing_allowance"
                                       class="form-control"
                                       value="{{ $cfg['housing_allowance'] }}">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-medium">Jour de génération des fiches</label>
                            <select name="payroll_day" class="form-select">
                                <option value="25" {{ $cfg['payroll_day'] == 25 ? 'selected' : '' }}>25 du mois</option>
                                <option value="28" {{ $cfg['payroll_day'] == 28 ? 'selected' : '' }}>28 du mois</option>
                                <option value="31" {{ $cfg['payroll_day'] == 31 ? 'selected' : '' }}>Dernier jour du mois</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-floppy me-2"></i>Enregistrer
                        </button>
                    </form>
                </div>
            </div>

            {{-- CONGÉS --}}
            <div class="card">
                <div class="card-header"><i class="bi bi-calendar-check me-2"></i>Paramètres congés</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('config.leaves') }}">
                        @csrf
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Congés annuels (jours)</label>
                                <input type="number" name="annual_leave_days"
                                       class="form-control" min="1"
                                       value="{{ $cfg['annual_leave_days'] }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Congés maladie (jours)</label>
                                <input type="number" name="sick_leave_days"
                                       class="form-control" min="1"
                                       value="{{ $cfg['sick_leave_days'] }}">
                            </div>
                        </div>
                        <div class="row g-2 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Congés exceptionnels (jours)</label>
                                <input type="number" name="exceptional_leave_days"
                                       class="form-control" min="1"
                                       value="{{ $cfg['exceptional_leave_days'] }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Max permissions / mois</label>
                                <input type="number" name="max_permission_per_month"
                                       class="form-control" min="1"
                                       value="{{ $cfg['max_permission_per_month'] }}">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-floppy me-2"></i>Enregistrer
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- INFOS SYSTÈME --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header"><i class="bi bi-cpu me-2"></i>Informations système</div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach([
                            ['PHP Version',      $systemInfo['php_version']],
                            ['Laravel Version',  $systemInfo['laravel_version']],
                            ['Base de données',  $systemInfo['db_connection']],
                            ['Cache driver',     $systemInfo['cache_driver']],
                            ['Espace disque',    $systemInfo['disk_free']],
                        ] as [$label, $value])
                            <div class="col-6 col-md-2">
                                <div class="text-muted"
                                     style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">
                                    {{ $label }}
                                </div>
                                <div class="fw-medium" style="font-size:13px">{{ $value }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
