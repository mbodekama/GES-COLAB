# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

GES-COLAB is a Laravel 13 / PHP 8.3 HR management application (employees, contracts, leaves, payroll, internal messaging) tailored to Côte d'Ivoire: French UI throughout, FCFA currency, CNPS social charges, and IGR (Ivorian income-tax) computation built into the payroll model. Default DB is SQLite (`database/database.sqlite`); a `docker-compose.yml` is provided to run against MySQL 8 + nginx.

## Common commands

- `composer setup` — first-time install (composer install, copy .env, key:generate, migrate, npm install, npm run build).
- `composer dev` — runs `php artisan serve`, `queue:listen`, `pail` (logs), and `npm run dev` concurrently via `npx concurrently`. Preferred dev entry point.
- `composer test` — clears config then runs `php artisan test` (PHPUnit, tests in `tests/Unit` and `tests/Feature`, in-memory SQLite per `phpunit.xml`).
- `php artisan test --filter=SomeTest` — run a single test.
- `php artisan migrate:fresh --seed` — rebuild schema and load demo data (postes → roles/permissions → users/employees/leaves/payrolls; see `DatabaseSeeder`).
- `./vendor/bin/pint` — Laravel Pint code style.
- `npm run dev` / `npm run build` — Vite assets only (Tailwind v4 + Bootstrap 5 via CDN + Alpine.js).
- `docker compose up -d` — full stack on `:8000` with MySQL 8 (`gescolab` / `laravel`/`root`).

## Architecture

### Domain model (`app/Models`)

- **Employee** — central HR entity. Holds `supervisor_id` (self-referential N+1 link), `poste_id`, `leave_balance`, soft deletes. Exposes accessors used heavily in Blade (`full_name`, `initials`, `seniority_label`, `status_badge`, `position_label`). `Employee::generateMatricule()` produces `EMP-####`. `getPossibleN1()` returns employees whose poste is flagged `can_be_n1` and at a strictly higher `level`.
- **Poste** — job-position catalog with `level` (1–9+) and `can_be_n1`. Drives both hierarchy display and leave-workflow eligibility.
- **Contract** — `cdi|cdd|internship|consulting`, tied to a `SalaryGrid`. Numbers like `CTR-YYYY-###`.
- **Leave** — leave/permission requests with a two-step workflow (see below). `workflow_step` ∈ {`pending_n1`, `pending_rh`, `approved`, `rejected`}. Constants `Leave::NEEDS_N1` and `Leave::DIRECT_RH` decide initial step via `Leave::initialWorkflowStep($type)`.
- **Payroll** — one row per (employee, period `YYYY-MM`). `Payroll::calculateIGR()` implements Ivorian income-tax brackets; `Payroll::seniorityRate()` returns the seniority-bonus percentage. Always recompute through these helpers — do not duplicate the brackets elsewhere.
- **SalaryGrid** — 5 levels (G1 Cadres supérieurs … G5 Stagiaires) seeded in `PermissionSeeder`.
- **User** — Laravel auth user; uses Spatie `HasRoles`; `hasOne(Employee::class)` links the account to its HR record.
- **Message** — direct user-to-user messaging with `read_at`.

### Roles & permissions (Spatie)

Roles seeded in `database/seeders/PermissionSeeder.php`: `superadmin`, `admin`, `rh`, `comptable`, `informaticien`, `user`. Permission names are French (e.g. `voir employés`, `valider congés`, `générer fiches de paie`). Route protection uses `role:` middleware (see `routes/web.php`), e.g. payroll/salary-grids require `superadmin|admin|comptable|rh`; postes require `superadmin|admin|rh`; roles management requires `superadmin`. Most authorization inside controllers is done by inline `$user->hasRole([...])` checks rather than Policies.

### Leave workflow (the non-obvious part)

`LeaveController` enforces a two-tier validation:

1. On `store`, `Leave::initialWorkflowStep($type)` puts `permission` requests at `pending_n1`; everything else (`annual`, `sick`, `exceptional`, `maternity`, `paternity`) goes straight to `pending_rh`.
2. `approveN1` / `rejectN1` — only callable while `workflow_step === 'pending_n1'`. Authorization compares `$leave->employee->supervisor_id` to the current user's `employee->id` (`isN1OfEmployee`); `superadmin`/`admin` bypass. Approving moves the row to `pending_rh`.
3. `approve` / `reject` (RH) — requires `rh|admin|superadmin` AND `workflow_step === 'pending_rh'`. On approval, `leave_balance` is decremented and (for `annual|maternity|paternity`) the employee status flips to `on_leave`.
4. Visibility (`index`, `authorizeView`): admins see all; RH sees `pending_rh` + closed; an N+1 (employee whose poste has `can_be_n1=true`) sees own + direct subordinates' (`supervisor_id = $employee->id`); standard users see only their own.

When changing this workflow, keep the `status` and `workflow_step` columns in sync — the UI badges read from both (`status_badge`, `workflow_badge`).

### Settings layer

`app/Helpers/settings.php` registers a global `setting($key, $default)` helper (autoloaded via composer.json `files`). Lookup order: `settings` DB table (cached 1h under `gescolab_settings`) → `config/gescolab.php` → `$default`. When adding configurable HR/payroll constants, prefer this layer over hardcoding so admins can override via `/config` (`ConfigController`). After writing to the `settings` table, clear the `gescolab_settings` cache key.

### Routing & views

All routes live in `routes/web.php` (no `routes/api.php`; the JSON endpoints under `/api/*` are inside the auth-protected web group). Auth scaffold is Laravel Breeze (`routes/auth.php`). Blade views use French folder names that don't match controller names: `resources/views/conges` (leaves), `resources/views/paie` (payroll). A stale `resources/views-1` exists — ignore it unless explicitly asked.

### Logging

Le canal `daily` (défaut via `LOG_CHANNEL`) n'est **pas** le driver Laravel standard : il pointe vers `App\Logging\DailyPathLogger` (`config/logging.php`). Ce logger custom écrit dans `storage/logs/YYYY/MM/DD/laravel.log` (arborescence hiérarchique par date), au lieu du fichier plat `laravel-YYYY-MM-DD.log`. À vérifier avant de changer le format/chemin des logs ou d'ajouter un outil qui parse `storage/logs/`.

La classe de base `Controller` expose `$this->logEntry(array $extra = [])` : à appeler en **première ligne** de toute action à tracer. Elle auto-détecte `ClassName@method` via `debug_backtrace` et écrit un `Log::debug("[TRACE] …")` avec `http_method`, `url`, `user_id`, `ip` + les données métier passées dans `$extra`. Voir `EmployeeController::store` et `EmployeeController::destroy` pour des exemples d'utilisation.

### Conventions UI — filtres des vues index

Toutes les vues `index` avec un formulaire de filtre (`conges`, `employees`, `contracts`, `paie`) suivent la même convention pour le bloc boutons :

```html
<div class="col-12 col-md-auto ms-auto d-flex justify-content-end gap-2">
    <button class="btn btn-primary btn-sm">
        <i class="bi bi-search me-1"></i> Lancer la recherche
    </button>
    <a href="{{ route('....index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-counterclockwise me-1"></i> Réinitialiser
    </a>
</div>
```

Règles : `ms-auto` pousse le bloc à droite de la grille Bootstrap, `justify-content-end` aligne les boutons en son sein, `gap-2` assure l'espacement. Pas de `w-100` sur le bouton de soumission. Icône `bi-search` sur "Lancer la recherche", `bi-arrow-counterclockwise` sur "Réinitialiser".

### Docker — permissions fichiers (UID mapping)

Le container `app` tourne sous `www-data` remappé sur `uid=1000` (utilisateur hôte `mbodekama`). Les valeurs sont lues depuis `.env` (`USER_ID=1000` / `GROUP_ID=1000`) et injectées au build via `docker-compose.yml` → `Dockerfile` (`ARG USER_ID / GROUP_ID` + `usermod/groupmod`).

**Conséquence** : tous les fichiers créés par le container ont `uid=1000` = l'utilisateur hôte. Toi, l'IDE, Claude et Docker partagent le même propriétaire — aucun `chmod`/`sudo` nécessaire au quotidien.

**Si des fichiers `storage/` ou `bootstrap/cache/` appartiennent à `www-data` (uid=33)** — symptôme : erreur "Permission denied" depuis l'hôte — corriger avec :

```bash
sudo chown -R mbodekama:mbodekama storage/ bootstrap/cache/
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

**Après un changement du `Dockerfile`**, toujours rebuilder :

```bash
docker compose down && docker compose build app && docker compose up -d
# Vérification : docker compose exec app id  → doit afficher uid=1000
```

Ne jamais utiliser `chmod 777` ni ajouter l'hôte au groupe `www-data` — ces contournements cassent la sécurité ou échouent en équipe.

### Génération PDF (`app/Pdf`)

Stack : **FPDF** (`setasign/fpdf`) + **fpdf-easytable** (`matthew-elisha/fpdf-easytable`, alias `\exFPDF` + `\easyTable`). DomPDF a été retiré du projet.

#### Classe de base `BasePdf`

`App\Pdf\BasePdf` est la classe abstraite dont héritent tous les documents PDF. Elle fournit :

- **Palette et constantes** : `ML` (marge 15 mm), `UW` (largeur utile 180 mm), constantes de couleur `BLUE`, `DARK`, `MUTED`, `GRAY_BG`, `BORDER`, `WHITE`, `GREEN`, `RED`.
- **`drawHeader(string $rightLabel, ?string $rightValue = null)`** — en-tête commun à tous les documents (badge entreprise, raison sociale, adresse). La boîte droite prend deux formes :
  - `$rightValue` fourni → boîte bordée bleue avec libellé grisé + valeur en bleu (**style référence**, ex. numéro de document)
  - `$rightValue` null → fond bleu plein avec libellé blanc (**style titre**, ex. "FICHE EMPLOYÉ")
- **`drawBanner(string $title, string $subtitle = '')`** — bandeau bleu paramétrable sous l'en-tête.
- **`drawDate(string $city = 'Abidjan')`** — date alignée à droite, utilise `$data['generated_date']`.
- Helpers couleur/fonte : `fc`, `tc`, `dc`, `n` (normal sombre), `b` (gras bleu), `e` (UTF-8 → ISO-8859-1).
- Helpers géométriques : `roundedRect`, `ellipse`, `bezierArc`, `SetDash`.

#### Contrat de données minimal (`$data`)

Toute classe fille doit recevoir dans son tableau `$data` :

| Clé | Utilisé par |
|---|---|
| `company_name` | `drawHeader`, `BasePdf` |
| `company_address` | `drawHeader`, `BasePdf` |
| `company_initials` | `drawHeader` — badge (2 lettres). Fallback : 2 premières lettres de `company_name`. |
| `generated_date` | `drawDate` |
| `reference` | `drawHeader` en mode référence |

#### Document de référence : `CongeAttestation`

`App\Pdf\CongeAttestation` est le **patron graphique de référence** pour tous les nouveaux documents. Son en-tête (badge, raison sociale, boîte de référence, bandeau, date) doit être reproduit à l'identique via `BasePdf`. Route : `GET /leaves/{leave}/print-design` (`leaves.print.design`).

Pour créer un nouveau document PDF :

```php
class MonDocument extends BasePdf
{
    public function __construct(array $data)
    {
        parent::__construct($data); // ou (data, autoPageBreak: true) si multi-page
    }

    public function build(): static
    {
        $this->drawHeader('MON LIBELLÉ');                 // style titre
        // ou : $this->drawHeader('RÉFÉRENCE', $ref);    // style référence
        $this->drawBanner('TITRE DU DOCUMENT', 'sous-titre optionnel');
        $this->drawDate();
        // ... sections spécifiques
        return $this;
    }
}
```

Appel depuis le contrôleur :

```php
ob_start();
$content = (new MonDocument($data))->build()->Output('S', '');
ob_end_clean();
return response()->make($content, 200, ['Content-Type' => 'application/pdf', ...]);
```

#### `EmployeeFiche`

`App\Pdf\EmployeeFiche` hérite également de `BasePdf`. Elle utilise `\easyTable` pour les grilles de données et active le saut de page automatique (`autoPageBreak: true`). Route : `GET /employees/{employee}/print-design` (`employees.print.design`).

### Schema notes

The main domain tables (`employees`, `contracts`, `leaves`, `payrolls`, `salary_grids`, `messages`) are created in a single migration `0001_01_01_000003_create_gescolab_tables.php`. The N+1 workflow columns on `leaves` (`workflow_step`, `n1_validator_id`, `n1_validated_at`, `n1_comment`) come from a later migration (`2026_05_27_231548_add_workflow_to_leaves_table.php`) — those fields are not in the base create migration.
