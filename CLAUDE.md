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

La classe de base `Controller` expose `$this->logEntry(array $extra = [])` : à appeler en **première ligne** de toute action à tracer. Elle auto-détecte `ClassName@method` via `debug_backtrace` et écrit un `Log::debug("[TRACE] …")` avec `http_method`, `url`, `user_id`, `ip` + les données métier passées dans `$extra`. `logEntry` est intégré dans **tous les contrôleurs** (`EmployeeController`, `ContractController`, `LeaveController`, `PayrollController`, `RoleController`, `PosteController`, `SalaryGridController`, `ConfigController`, `MessageController`, `ProfileController`). Toute nouvelle action doit l'appeler en première ligne.

### Feuille de styles applicative (`public/css/gescolab.css`)

`public/css/gescolab.css` est la feuille de styles centralisée pour tous les composants custom. Elle est chargée dans le layout principal. Tout nouveau style spécifique à l'application doit y être ajouté — **ne pas utiliser de styles inline**. Elle contient actuellement : `.form-sticky-actions`, `#toast-container`, `.toast-item`.

### Composants Blade réutilisables (`resources/views/components`)

| Composant | Usage |
|---|---|
| `<x-sort-th>` | En-têtes triables (déjà documenté ci-dessous) |
| `<x-breadcrumb>` | Fil d'Ariane dans la navbar |
| `<x-date>` | Input date avec Flatpickr |
| `<x-select>` | Select Bootstrap avec gestion `old()` et collections |
| `<x-avatar>` | Badge initiales / photo employé |

#### `<x-breadcrumb>`

Injecté dans le layout via `@section('breadcrumb')`. Remplace le `page-title` quand la section est définie.

```blade
@section('breadcrumb')
<x-breadcrumb :items="[
    ['label' => 'Employés', 'url' => route('employees.index')],
    ['label' => $employee->full_name],
]" />
@endsection
```

Le dernier item (sans `url`) devient le titre actif. Tous les items précédents sont des liens cliquables.

#### `<x-date>`

Input date avec Flatpickr (locale FR, affichage `d/m/Y` via `altInput`, valeur soumise `Y-m-d`). Charge Flatpickr par CDN via `@push('styles'/'scripts')` — une seule fois par page grâce à `@once`.

```blade
<x-date name="start_date" label="Date de début" :value="$contract->start_date" required />
{{-- Avec contraintes --}}
<x-date name="end_date" label="Date de fin" :value="$contract->end_date" min="{{ now()->format('Y-m-d') }}" />
```

Supporte les attributs `min`, `max` (transmis à Flatpickr). Gère `old()`, `is-invalid`, et le label avec `*` si `required`.

#### `<x-select>`

Select Bootstrap avec gestion automatique de `old()`, validation `is-invalid`, et deux modes d'alimentation :

```blade
{{-- Tableau associatif simple --}}
<x-select name="type" label="Type" :options="['cdi' => 'CDI', 'cdd' => 'CDD']" :value="$contract->type" />

{{-- Collection Eloquent --}}
<x-select name="poste_id" label="Poste" :options="$postes"
    option-value="id" option-label="label"
    placeholder="— Sélectionner —" required />
```

### Sections du layout (`resources/views/layouts/app.blade.php`)

Le layout expose ces `@yield` / `@push` à utiliser dans les vues :

| Section | Rôle |
|---|---|
| `@section('title')` | Titre de l'onglet navigateur |
| `@section('breadcrumb')` | Fil d'Ariane dans la navbar (remplace `page-title`) |
| `@section('header-actions')` | Boutons à droite de la barre supérieure (export, create…) |
| `@section('content')` | Corps principal de la page |
| `@push('styles')` | CSS supplémentaires dans `<head>` (utilisé par `x-date`) |
| `@push('scripts')` | JS supplémentaires avant `</body>` (utilisé par `x-date`) |

Toutes les flash sessions sont automatiquement transformées en toasts : `session('success')`, `session('error')`, `session('warning')`.

### Système de toasts

`window.showToast(message, type, duration)` est disponible globalement (défini dans le layout). Ne pas créer d'alertes inline pour les retours d'action — utiliser `redirect()->with('success', '...')` qui déclenche le toast automatiquement.

- **Types** : `success` (vert), `error` (rouge), `warning` (orange), `info` (bleu)
- **Durée par défaut** : 7 000 ms
- **Position** : fixe haut-droite (`top:24px; right:24px`)
- **Appel JS direct** : `showToast('Message', 'success')` ou `showToast('Erreur', 'error', 5000)`

### Barre d'actions sticky (`form-sticky-actions`)

Pour les formulaires longs (create/edit), la barre d'actions est sticky en bas de page. Pattern à reproduire sur tout nouveau formulaire long :

```html
<div class="form-sticky-actions">
    <span class="form-sticky-hint">
        <i class="bi bi-info-circle me-1"></i> Les champs marqués * sont obligatoires
    </span>
    <div class="d-flex gap-2">
        <a href="{{ route('....index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-x-circle me-1"></i> Annuler
        </a>
        <button type="submit" class="btn btn-primary btn-sm">
            <i class="bi bi-check-circle me-2" aria-hidden="true"></i>Enregistrer
        </button>
    </div>
</div>
```

Le hint (texte à gauche) est masqué sur mobile (`display:none`). Le style est défini dans `gescolab.css`.

### Export CSV

Routes d'export disponibles, accessibles depuis les datatables concernés :

| Route | Contrôleur | Accès |
|---|---|---|
| `employees.export` | `EmployeeController@export` | `admin\|rh\|superadmin` |
| `contracts.export` | `ContractController@export` | `admin\|rh\|superadmin` |
| `leaves.export` | `LeaveController@export` | `admin\|rh\|superadmin` |
| `payroll.export` | `PayrollController@export` | `superadmin\|admin\|comptable\|rh` |

Les boutons d'export sont dans `@section('header-actions')` de chaque vue index.

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

### Conventions UI — boutons d'actions des datatables

Tous les datatables utilisent le même bloc d'actions :

```html
<div class="btn-group btn-group-md d-flex justify-content-start gap-2">
    <div>
        <a href="..." class="btn btn-outline-secondary" title="Voir">
            <i class="bi bi-eye"></i> &nbsp; Voir
        </a>
    </div>
    <div>
        <a href="..." class="btn btn-outline-primary" title="Modifier">
            <i class="bi bi-pencil"></i> &nbsp; Modifier
        </a>
    </div>
    <div>
        <a href="..." class="btn btn-primary" target="_blank" title="PDF">
            <i class="bi bi-file-earmark-richtext"></i> &nbsp; PDF
        </a>
    </div>
</div>
```

Règles : `btn-group-md` (pas `btn-sm`), `justify-content-start gap-2`, chaque bouton dans son propre `<div>`, icône + `&nbsp;` + texte court. Les boutons POST (approve, reject, delete) restent dans un `<form class="d-inline">` à l'intérieur d'un `<div>`.

**Exception** : les datatables `employees` et `contracts` n'ont **pas** de bouton "Modifier" — l'édition se fait depuis la vue de détail (`show`). Ne pas rajouter ce bouton sur ces deux vues.

### Conventions UI — en-têtes triables des datatables

Utiliser le composant `<x-sort-th column="col_db" label="Libellé" />` pour tout en-tête de colonne triable. Il génère un `<th>` avec lien clic, chevron inactif (`bi-chevron-expand`) ou flèche active (`bi-caret-up/down-fill`), et préserve tous les filtres existants via `fullUrlWithQuery()`.

Dans le contrôleur correspondant, ajouter une whitelist des colonnes autorisées :

```php
$allowed = ['col1', 'col2', ...];
$sortBy  = in_array($request->get('sort_by'), $allowed) ? $request->get('sort_by') : 'default_col';
$sortDir = $request->get('sort_dir') === 'asc' ? 'asc' : 'desc';
$query   = Model::orderBy($sortBy, $sortDir);
```

Toute valeur `sort_by` hors whitelist retombe sur la colonne par défaut (protection injection SQL).

### Conventions UI — pagination

La pagination est configurée globalement dans `AppServiceProvider::boot()` :

```php
Paginator::useBootstrapFive();   // Laravel 11 utilise tailwind par défaut — forcer BS5
Carbon::setLocale('fr');
```

La vue de pagination est publiée et personnalisée dans `resources/views/vendor/pagination/bootstrap-5.blade.php` :
- Pas de texte "Showing X to Y of Z results" — le compteur est dans le card-footer de chaque vue
- Chevrons via Bootstrap Icons (`bi-chevron-left` / `bi-chevron-right`) au lieu de `&lsaquo;`/`&rsaquo;`
- `pagination-sm mb-0` pour l'alignement dans les card-footers

Pattern card-footer standard :

```html
<div class="card-footer d-flex justify-content-between align-items-center py-2">
    <small class="text-muted">
        Affichage {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }}
        sur {{ $items->total() }}
    </small>
    {{ $items->links() }}
</div>
```

Ne jamais appeler `Paginator::defaultView('tailwind')` ni utiliser `->links('pagination::tailwind')` — l'app est Bootstrap 5, pas Tailwind.

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
