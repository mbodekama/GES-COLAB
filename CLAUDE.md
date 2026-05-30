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

**Stack :** `setasign/fpdf` + `matthew-elisha/fpdf-easytable` (classes globales `\exFPDF`, `\easyTable`) + `bacon/bacon-qr-code` (matrice QR vectorielle, sans GD). DomPDF et easy-pdf ont été retirés.

> `setasign/fpdf` doit rester en dépendance directe : `fpdf-easytable` l'exige mais ne le déclare pas dans son `composer.json`.

---

#### Architecture — `BasePdf` (classe abstraite)

Tous les documents héritent de `App\Pdf\BasePdf extends \exFPDF`. Ne jamais hériter directement de `\exFPDF` ou `\FPDF`.

**Ce que BasePdf fournit :**

| Méthode / constante | Description |
|---|---|
| `ML = 15.0`, `UW = 180.0` | Marge gauche et largeur utile (mm) |
| `FOOTER_H = 24.0` | Hauteur réservée au pied de page |
| `BLUE`, `DARK`, `MUTED`, `GRAY_BG`, `BORDER`, `WHITE`, `GREEN`, `RED` | Palette partagée `[R,G,B]` |
| `drawHeader(label, ?value)` | En-tête avec badge initiales + raison sociale + séparateur bleu. `value=null` → boîte bleue pleine (style **titre**) ; `value` fourni → boîte bordée label/valeur (style **référence**) |
| `drawBanner(title, subtitle='')` | Bandeau bleu sous l'en-tête |
| `drawDate(city='Abidjan')` | Date alignée à droite, utilise `$data['generated_date']` |
| `Footer()` | Appelé automatiquement par FPDF : séparateur bleu, QR code vectoriel, message de vérification, numéro de page, URL, téléphone |
| `drawQrCode(content, x, y, size)` | QR code dessiné en `Rect()` FPDF, sans GD |
| `fc/tc/dc` | Couleur remplissage / texte / trait |
| `n(size)` / `b(size)` | Font Helvetica normal sombre / gras bleu |
| `e(text)` | UTF-8 → ISO-8859-1 (obligatoire pour tout texte passé à FPDF) |
| `roundedRect`, `ellipse`, `bezierArc`, `SetDash` | Helpers géométriques |

---

#### Contrat de données (`$data`)

Clés lues par `BasePdf` (toujours requises) :

| Clé | Utilisé par |
|---|---|
| `company_name` | `drawHeader`, `Footer` |
| `company_initials` | `drawHeader` — badge. Fallback : 2 premières initiales du nom |
| `company_address` | `drawHeader` |
| `company_phone` | `Footer` |
| `company_website` | `Footer` |
| `generated_date` | `drawDate` |

Clés optionnelles lues par `Footer()` (fallback gracieux si absentes) :

| Clé | Rôle |
|---|---|
| `verification_url` | Contenu du QR code (priorité 1) |
| `reference` | Contenu du QR code (priorité 2) |

Toutes ces valeurs sont configurables via `/config` (table `settings`, helper `setting()`).

---

#### Règle d'or : easyTable pour tout le contenu

Tout le contenu des documents (grilles, tableaux, textes) est rendu avec `\easyTable`. Le positionnement absolu `SetXY` + `Cell` est réservé aux éléments graphiques fixes (`drawHeader`, `drawIdentityBlock`).

**Structure type d'une section :**

```php
private function drawMaSection(): void
{
    $yBefore = $this->GetY();

    $table = new \easyTable($this, '%{22,28,22,28}',
        'width:180; font-family:Helvetica; border:0;');

    // En-tête de section
    $table->rowStyle('bgcolor:#f1f5f9; min-height:10;');
    $table->easyCell('TITRE SECTION',
        'colspan:4; font-style:B; font-size:8; font-color:#475569; paddingX:5; border:0;');
    $table->printRow();

    // Ligne de données
    $table->rowStyle('min-height:11;');
    $table->easyCell($this->e('LIBELLÉ'),   'bgcolor:#f1f5f9; font-color:#94a3b8; font-style:B; font-size:7.5; paddingY:3; paddingX:4; border:B; border-color:#e2e8f0;');
    $table->easyCell($this->e($valeur),     'font-style:B; font-size:10; paddingY:3; paddingX:4; border:B; border-color:#e2e8f0;');
    // ... autres colonnes
    $table->printRow();

    $table->endTable(0);

    // Bordure arrondie englobante (optionnelle)
    $this->SetLineWidth(0.35);
    $this->dc(self::BORDER);
    $this->roundedRect(self::ML, $yBefore, self::UW, $this->GetY() - $yBefore, 3, 'D');

    $this->SetY($this->GetY() + 5); // marge après section
}
```

**Pièges easyTable :**
- Ne jamais utiliser `font-style:N` — FPDF cherche `helvetican.php` qui n'existe pas. Omettre l'attribut ou ne pas le spécifier pour le style normal.
- Après un bloc en positionnement absolu (`SetXY`), appeler `$this->SetY($yApres)` avant le premier easyTable — le curseur FPDF n'avance pas avec `SetXY`.

---

#### Créer un nouveau document

```php
// app/Pdf/MonDocument.php
class MonDocument extends BasePdf
{
    public function __construct(array $data)
    {
        // autoPageBreak: false  → document 1 page (bulletins, attestations, contrats)
        // autoPageBreak: true   → document multi-page (fiches avec historique long)
        parent::__construct($data, autoPageBreak: false);
    }

    public function build(): static
    {
        // 1. En-tête (toujours en premier)
        $this->drawHeader('TITRE');               // style titre (boîte bleue)
        // ou : $this->drawHeader('RÉFÉRENCE', $ref); // style référence (boîte bordée)

        // 2. Bandeau + date (documents formels)
        $this->drawBanner('MON DOCUMENT', 'sous-titre');
        $this->drawDate();

        // 3. Positionner le curseur après le bloc en-tête
        $this->SetXY(self::ML, 56);

        // 4. Sections en easyTable
        $this->drawMaSection();
        // ...

        return $this;
    }
}
```

**Appel depuis le contrôleur (pattern identique pour tous les documents) :**

```php
public function printDesign(MonModele $model)
{
    $model->load(['relation1', 'relation2']);

    $data = [
        'company_name'     => setting('company_name', 'GES-COLAB'),
        'company_initials' => setting('company_initials', ''),
        'company_address'  => setting('company_address', ''),
        'company_phone'    => setting('company_phone', ''),
        'company_website'  => setting('company_website', ''),
        'reference'        => $model->reference_number,
        'generated_date'   => now()->isoFormat('D MMMM YYYY'),
        'generated_at'     => now()->format('d/m/Y à H:i'),
        // ... données spécifiques au document
    ];

    ob_start();
    $content = (new \App\Pdf\MonDocument($data))->build()->Output('S', '');
    ob_end_clean();

    return response()->make($content, 200, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => "inline; filename=\"mon-doc-{$model->id}.pdf\"",
        'Content-Length'      => strlen($content),
        'Cache-Control'       => 'private, max-age=0, must-revalidate',
        'Pragma'              => 'public',
    ]);
}
```

---

#### Documents existants

| Classe | Route | Style header | Pages | Notes |
|---|---|---|---|---|
| `CongeAttestation` | `leaves.print.design` | Référence (N° congé) | 1 | Document de référence graphique |
| `BulletinPaie` | `payroll.print.design` | Titre | 1 | Toujours 1 page ; `autoPageBreak: false` |
| `ContratTravail` | `contracts.print.design` | Référence (N° contrat) | 1 | Section notes conditionnelle |
| `EmployeeFiche` | `employees.print.design` | Titre | 1+ | `autoPageBreak: true` ; `SetY(47)` requis après `drawIdentityBlock()` |

### Schema notes

The main domain tables (`employees`, `contracts`, `leaves`, `payrolls`, `salary_grids`, `messages`) are created in a single migration `0001_01_01_000003_create_gescolab_tables.php`. The N+1 workflow columns on `leaves` (`workflow_step`, `n1_validator_id`, `n1_validated_at`, `n1_comment`) come from a later migration (`2026_05_27_231548_add_workflow_to_leaves_table.php`) — those fields are not in the base create migration.
