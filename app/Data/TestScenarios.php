<?php

namespace App\Data;

class TestScenarios
{
    public static function all(): array
    {
        return [

            // ── Module 1 — Authentification ──────────────────────────────
            [
                'id'     => 'CT-AUTH-01',
                'module' => 'Authentification',
                'name'   => 'Connexion avec identifiants valides',
                'pre'    => 'Aucune session ouverte.',
                'steps'  => [
                    ['action' => 'Accéder à /login', 'expected' => 'Formulaire de connexion affiché'],
                    ['action' => 'Saisir admin@gescolab.ci / password, cliquer Se connecter', 'expected' => 'Redirection vers le tableau de bord /dashboard'],
                    ['action' => 'Vérifier la barre latérale', 'expected' => 'Tous les menus sont visibles (Employés, Contrats, Congés, Paie, Admin)'],
                ],
            ],
            [
                'id'     => 'CT-AUTH-02',
                'module' => 'Authentification',
                'name'   => 'Connexion avec mot de passe incorrect',
                'pre'    => 'Aucune session ouverte.',
                'steps'  => [
                    ['action' => 'Accéder à /login', 'expected' => 'Formulaire affiché'],
                    ['action' => 'Saisir admin@gescolab.ci / mauvais_mdp, cliquer Se connecter', 'expected' => 'Message d\'erreur rouge : "Ces identifiants ne correspondent pas…"'],
                    ['action' => 'Vérifier l\'URL', 'expected' => 'Reste sur /login — pas de redirection'],
                ],
            ],
            [
                'id'     => 'CT-AUTH-03',
                'module' => 'Authentification',
                'name'   => 'Déconnexion',
                'pre'    => 'Connecté en tant que n\'importe quel utilisateur.',
                'steps'  => [
                    ['action' => 'Cliquer sur le menu utilisateur (avatar en haut à droite)', 'expected' => 'Menu déroulant avec "Se déconnecter"'],
                    ['action' => 'Cliquer Se déconnecter', 'expected' => 'Redirection vers /login'],
                    ['action' => 'Accéder manuellement à /dashboard', 'expected' => 'Redirection vers /login (session invalidée)'],
                ],
            ],
            [
                'id'     => 'CT-AUTH-04',
                'module' => 'Authentification',
                'name'   => 'Isolation des rôles (employé standard)',
                'pre'    => 'Connecté en tant que employe@gescolab.ci.',
                'steps'  => [
                    ['action' => 'Accéder à /employees', 'expected' => 'Redirection ou page 403'],
                    ['action' => 'Accéder à /roles', 'expected' => 'Redirection ou page 403'],
                    ['action' => 'Accéder à /payroll', 'expected' => 'Redirection ou page 403'],
                    ['action' => 'Accéder à /leaves', 'expected' => 'Liste des congés affichée — uniquement ses propres congés'],
                ],
            ],

            // ── Module 2 — Employés ──────────────────────────────────────
            [
                'id'     => 'CT-EMP-01',
                'module' => 'Employés',
                'name'   => 'Création d\'un employé complet',
                'pre'    => 'Connecté en tant que admin@gescolab.ci.',
                'steps'  => [
                    ['action' => 'Menu → Employés → Nouvel employé', 'expected' => 'Formulaire de création affiché avec barre d\'actions sticky en bas'],
                    ['action' => 'Renseigner Nom, Prénom, Email, Genre, Poste, Date d\'embauche 01/01/2024', 'expected' => 'Champs remplis sans erreur'],
                    ['action' => 'Sélectionner une grille salariale', 'expected' => 'Le champ salaire de base se pré-remplit automatiquement'],
                    ['action' => 'Renseigner les champs facultatifs : Situation matrimoniale, Adresse, CNPS', 'expected' => 'Champs acceptés'],
                    ['action' => 'Cliquer Enregistrer l\'employé (barre sticky)', 'expected' => 'Toast vert "Employé créé avec succès" en haut à droite'],
                    ['action' => 'Vérifier la fiche créée', 'expected' => 'Matricule EMP-#### généré automatiquement, statut Actif'],
                ],
            ],
            [
                'id'     => 'CT-EMP-02',
                'module' => 'Employés',
                'name'   => 'Validation des champs obligatoires',
                'pre'    => 'Connecté admin, formulaire de création ouvert.',
                'steps'  => [
                    ['action' => 'Cliquer Enregistrer sans remplir aucun champ', 'expected' => 'Bordures rouges sur les champs obligatoires, messages d\'erreur sous chaque champ'],
                    ['action' => 'Vérifier qu\'aucune redirection n\'a lieu', 'expected' => 'Reste sur le formulaire'],
                ],
            ],
            [
                'id'     => 'CT-EMP-03',
                'module' => 'Employés',
                'name'   => 'Modification d\'un employé',
                'pre'    => 'Au moins un employé existant. Connecté admin.',
                'steps'  => [
                    ['action' => 'Ouvrir la fiche d\'un employé', 'expected' => 'Vue show affichée'],
                    ['action' => 'Cliquer Modifier', 'expected' => 'Formulaire d\'édition pré-rempli avec les données actuelles'],
                    ['action' => 'Changer le prénom, cliquer Enregistrer', 'expected' => 'Toast vert, modification reflétée dans la fiche'],
                ],
            ],
            [
                'id'     => 'CT-EMP-04',
                'module' => 'Employés',
                'name'   => 'Assignation d\'un supérieur hiérarchique (N+1)',
                'pre'    => 'Au moins 2 employés ; le N+1 potentiel a un poste can_be_n1=true et un niveau supérieur. Connecté admin.',
                'steps'  => [
                    ['action' => 'Ouvrir le formulaire de modification d\'un employé subalterne', 'expected' => 'Formulaire affiché'],
                    ['action' => 'Dans le champ Supérieur hiérarchique, ouvrir la liste', 'expected' => 'Seuls les employés éligibles N+1 apparaissent'],
                    ['action' => 'Sélectionner un N+1, enregistrer', 'expected' => 'Toast vert, fiche mise à jour'],
                ],
            ],
            [
                'id'     => 'CT-EMP-05',
                'module' => 'Employés',
                'name'   => 'Génération PDF de la fiche employé',
                'pre'    => 'Employé existant avec données complètes. Connecté admin.',
                'steps'  => [
                    ['action' => 'Ouvrir la fiche d\'un employé, cliquer PDF', 'expected' => 'Navigateur ouvre/télécharge un PDF inline'],
                    ['action' => 'Vérifier le contenu du PDF', 'expected' => 'En-tête société, badge initiales, données employé, QR code en pied de page'],
                ],
            ],
            [
                'id'     => 'CT-EMP-06',
                'module' => 'Employés',
                'name'   => 'Export CSV des employés',
                'pre'    => 'Au moins 2 employés. Connecté admin.',
                'steps'  => [
                    ['action' => 'Menu → Employés, cliquer Exporter CSV', 'expected' => 'Téléchargement d\'un fichier .csv'],
                    ['action' => 'Ouvrir le CSV', 'expected' => 'Entêtes de colonnes en première ligne, données cohérentes'],
                ],
            ],
            [
                'id'     => 'CT-EMP-07',
                'module' => 'Employés',
                'name'   => 'Filtres et tri sur la liste employés',
                'pre'    => 'Au moins 3 employés. Connecté admin.',
                'steps'  => [
                    ['action' => 'Saisir un nom partiel dans le filtre, cliquer Lancer la recherche', 'expected' => 'Liste filtrée, seuls les employés correspondants affichés'],
                    ['action' => 'Cliquer sur l\'en-tête de colonne Nom', 'expected' => 'Liste triée par nom ASC, chevron actif visible'],
                    ['action' => 'Cliquer à nouveau sur Nom', 'expected' => 'Tri DESC, chevron retourné'],
                    ['action' => 'Cliquer Réinitialiser', 'expected' => 'Filtres effacés, liste complète'],
                ],
            ],
            [
                'id'     => 'CT-EMP-08',
                'module' => 'Employés',
                'name'   => 'Suppression (soft delete)',
                'pre'    => 'Employé existant sans contrat actif. Connecté admin.',
                'steps'  => [
                    ['action' => 'Ouvrir la fiche de l\'employé, cliquer Supprimer', 'expected' => 'Boîte de confirmation affichée'],
                    ['action' => 'Confirmer la suppression', 'expected' => 'Toast vert, employé absent de la liste'],
                    ['action' => 'Vérifier que la ligne persiste en base avec deleted_at rempli', 'expected' => 'Soft delete confirmé (aucune perte de données)'],
                ],
            ],

            // ── Module 3 — Contrats ──────────────────────────────────────
            [
                'id'     => 'CT-CTR-01',
                'module' => 'Contrats',
                'name'   => 'Création d\'un contrat CDI',
                'pre'    => 'Au moins un employé actif, au moins une grille salariale. Connecté admin ou RH.',
                'steps'  => [
                    ['action' => 'Menu → Contrats → Nouveau contrat', 'expected' => 'Formulaire affiché'],
                    ['action' => 'Sélectionner un employé dans la liste', 'expected' => 'Champs Poste et Département se pré-remplissent automatiquement'],
                    ['action' => 'Sélectionner type CDI et une grille salariale', 'expected' => 'Salaire de base se pré-remplit'],
                    ['action' => 'Renseigner la date de début via Flatpickr', 'expected' => 'Date affichée au format jj/mm/aaaa'],
                    ['action' => 'Cliquer Enregistrer', 'expected' => 'Toast vert, contrat créé avec numéro CTR-YYYY-###'],
                ],
            ],
            [
                'id'     => 'CT-CTR-02',
                'module' => 'Contrats',
                'name'   => 'Pré-remplissage : employé avec poste non répertorié',
                'pre'    => 'Un employé dont le poste a été désactivé ou est libre. Connecté admin.',
                'steps'  => [
                    ['action' => 'Ouvrir le formulaire de création de contrat', 'expected' => 'Formulaire affiché'],
                    ['action' => 'Sélectionner l\'employé concerné', 'expected' => 'Le champ Poste revient à la valeur vide (index 0), le département se pré-remplit'],
                    ['action' => 'Vérifier que le select Poste est vide', 'expected' => 'Select en position vide — aucune valeur erronée pré-sélectionnée'],
                ],
            ],
            [
                'id'     => 'CT-CTR-03',
                'module' => 'Contrats',
                'name'   => 'Création d\'un contrat CDD avec date de fin',
                'pre'    => 'Employé actif existant. Connecté admin.',
                'steps'  => [
                    ['action' => 'Nouveau contrat, type CDD', 'expected' => 'Champ date de fin visible et obligatoire'],
                    ['action' => 'Saisir une date de fin antérieure à la date de début', 'expected' => 'Message d\'erreur de validation'],
                    ['action' => 'Corriger avec une date de fin postérieure, enregistrer', 'expected' => 'Contrat créé correctement'],
                ],
            ],
            [
                'id'     => 'CT-CTR-04',
                'module' => 'Contrats',
                'name'   => 'PDF contrat de travail',
                'pre'    => 'Contrat existant. Connecté admin.',
                'steps'  => [
                    ['action' => 'Ouvrir le détail d\'un contrat, cliquer PDF', 'expected' => 'PDF généré et affiché inline'],
                    ['action' => 'Vérifier le contenu', 'expected' => 'En-tête société, numéro de contrat en référence, données employé, salaire, poste, dates, QR code'],
                ],
            ],
            [
                'id'     => 'CT-CTR-05',
                'module' => 'Contrats',
                'name'   => 'Export CSV des contrats',
                'pre'    => 'Au moins 2 contrats. Connecté admin.',
                'steps'  => [
                    ['action' => 'Menu → Contrats, cliquer Exporter CSV', 'expected' => 'Fichier .csv téléchargé'],
                    ['action' => 'Ouvrir le fichier', 'expected' => 'Colonnes cohérentes (employé, type, salaire, dates, statut)'],
                ],
            ],

            // ── Module 4 — Congés ────────────────────────────────────────
            [
                'id'     => 'CT-CONGE-01',
                'module' => 'Congés',
                'name'   => 'Demande de congé annuel (chemin direct RH)',
                'pre'    => 'Connecté en tant que employe@gescolab.ci. Solde de congés > 0.',
                'steps'  => [
                    ['action' => 'Menu → Mes congés → Nouvelle demande', 'expected' => 'Formulaire affiché'],
                    ['action' => 'Sélectionner type Congé annuel, dates de début/fin, motif, soumettre', 'expected' => 'Toast vert, demande créée avec statut En attente RH'],
                    ['action' => 'Vérifier que le statut est pending_rh directement', 'expected' => 'Statut = En attente RH (pas de passage par N+1)'],
                ],
            ],
            [
                'id'     => 'CT-CONGE-02',
                'module' => 'Congés',
                'name'   => 'Approbation d\'un congé annuel par RH',
                'pre'    => 'Demande de congé annuel en statut pending_rh. Connecté rh@gescolab.ci.',
                'steps'  => [
                    ['action' => 'Menu → Congés, ouvrir la demande', 'expected' => 'Demande visible dans la liste RH'],
                    ['action' => 'Cliquer Approuver', 'expected' => 'Toast vert "Congé approuvé"'],
                    ['action' => 'Vérifier le statut de la demande', 'expected' => 'Statut = Approuvé (badge vert)'],
                    ['action' => 'Vérifier le solde de l\'employé', 'expected' => 'Solde décrémenté du nombre de jours demandés'],
                    ['action' => 'Vérifier le statut de l\'employé', 'expected' => 'Statut = En congé'],
                ],
            ],
            [
                'id'     => 'CT-CONGE-03',
                'module' => 'Congés',
                'name'   => 'Demande de permission — workflow N+1 → RH',
                'pre'    => 'L\'employé employe@gescolab.ci a un N+1 assigné.',
                'steps'  => [
                    ['action' => 'Connecté employé : Nouvelle demande type Permission, soumettre', 'expected' => 'Statut = En attente N+1'],
                    ['action' => 'Connecté N+1 : Menu → Congés, cliquer Valider (N+1)', 'expected' => 'Toast vert, statut passe à En attente RH'],
                    ['action' => 'Connecté rh@gescolab.ci : cliquer Approuver', 'expected' => 'Toast vert, statut = Approuvé'],
                ],
            ],
            [
                'id'     => 'CT-CONGE-04',
                'module' => 'Congés',
                'name'   => 'Rejet N+1 d\'une permission',
                'pre'    => 'Permission en statut pending_n1. Connecté en tant que N+1.',
                'steps'  => [
                    ['action' => 'Ouvrir la permission, cliquer Rejeter (N+1), saisir un commentaire', 'expected' => 'Toast "Permission rejetée"'],
                    ['action' => 'Vérifier le statut', 'expected' => 'Statut = Rejeté — la demande ne passe pas à RH'],
                    ['action' => 'Vérifier le solde de l\'employé', 'expected' => 'Solde inchangé'],
                ],
            ],
            [
                'id'     => 'CT-CONGE-05',
                'module' => 'Congés',
                'name'   => 'Bypass N+1 par superadmin',
                'pre'    => 'Permission en statut pending_n1. Connecté admin@gescolab.ci.',
                'steps'  => [
                    ['action' => 'Ouvrir la permission depuis la liste admin', 'expected' => 'Boutons Valider (N+1) et Rejeter (N+1) accessibles'],
                    ['action' => 'Cliquer Valider (N+1)', 'expected' => 'Statut passe à pending_rh — même sans être le N+1 de l\'employé'],
                ],
            ],
            [
                'id'     => 'CT-CONGE-06',
                'module' => 'Congés',
                'name'   => 'Isolation de visibilité des congés par rôle',
                'pre'    => 'Plusieurs demandes de congés pour différents employés.',
                'steps'  => [
                    ['action' => 'Connecté employe@gescolab.ci → Menu Congés', 'expected' => 'Seules ses propres demandes sont listées'],
                    ['action' => 'Connecté rh@gescolab.ci → Menu Congés', 'expected' => 'Demandes pending_rh et clôturées de tous les employés'],
                    ['action' => 'Connecté N+1 → Menu Congés', 'expected' => 'Ses demandes + celles de ses subordonnés directs uniquement'],
                ],
            ],
            [
                'id'     => 'CT-CONGE-07',
                'module' => 'Congés',
                'name'   => 'PDF attestation de congé',
                'pre'    => 'Congé en statut Approuvé. Connecté admin ou RH.',
                'steps'  => [
                    ['action' => 'Ouvrir le détail du congé, cliquer PDF', 'expected' => 'PDF généré avec numéro de congé en référence, données employé, dates, QR code'],
                ],
            ],
            [
                'id'     => 'CT-CONGE-08',
                'module' => 'Congés',
                'name'   => 'Export CSV des congés',
                'pre'    => 'Au moins 2 congés de statuts variés. Connecté admin.',
                'steps'  => [
                    ['action' => 'Menu → Congés, cliquer Exporter CSV', 'expected' => 'Téléchargement .csv avec tous les champs'],
                ],
            ],

            // ── Module 5 — Paie ──────────────────────────────────────────
            [
                'id'     => 'CT-PAIE-01',
                'module' => 'Paie',
                'name'   => 'Génération d\'une fiche de paie',
                'pre'    => 'Employé actif avec contrat actif. Connecté admin ou comptable.',
                'steps'  => [
                    ['action' => 'Menu → Paie → Générer une fiche', 'expected' => 'Formulaire de génération affiché'],
                    ['action' => 'Sélectionner l\'employé, saisir la période (ex. 2026-05)', 'expected' => 'Champs remplis'],
                    ['action' => 'Cliquer Générer', 'expected' => 'Fiche de paie créée, toast vert'],
                    ['action' => 'Ouvrir la fiche', 'expected' => 'Salaire brut, CNPS, IGR, salaire net affichés'],
                    ['action' => 'Vérifier le calcul IGR', 'expected' => 'Cohérent avec les tranches ivoiriennes'],
                    ['action' => 'Vérifier la prime d\'ancienneté', 'expected' => 'Présente si ancienneté ≥ seuil'],
                ],
            ],
            [
                'id'     => 'CT-PAIE-02',
                'module' => 'Paie',
                'name'   => 'Double génération pour la même période',
                'pre'    => 'Fiche déjà générée pour employé X, période 2026-05.',
                'steps'  => [
                    ['action' => 'Tenter de générer à nouveau pour le même employé / même période', 'expected' => 'Message d\'erreur ou toast rouge "Fiche déjà existante" — aucun doublon créé'],
                ],
            ],
            [
                'id'     => 'CT-PAIE-03',
                'module' => 'Paie',
                'name'   => 'PDF bulletin de paie',
                'pre'    => 'Fiche de paie existante.',
                'steps'  => [
                    ['action' => 'Ouvrir la fiche, cliquer PDF', 'expected' => 'Bulletin affiché inline, 1 page exactement'],
                    ['action' => 'Vérifier le contenu', 'expected' => 'En-tête société, nom employé, période, toutes les lignes de calcul, QR code'],
                ],
            ],
            [
                'id'     => 'CT-PAIE-04',
                'module' => 'Paie',
                'name'   => 'Export CSV des fiches de paie',
                'pre'    => 'Au moins 2 fiches. Connecté admin ou comptable.',
                'steps'  => [
                    ['action' => 'Menu → Paie, cliquer Exporter CSV', 'expected' => 'Fichier .csv téléchargé avec colonnes cohérentes'],
                ],
            ],
            [
                'id'     => 'CT-PAIE-05',
                'module' => 'Paie',
                'name'   => 'Accès refusé au comptable hors périmètre',
                'pre'    => 'Connecté comptable@gescolab.ci.',
                'steps'  => [
                    ['action' => 'Vérifier que le menu Paie est accessible', 'expected' => 'Menu Paie visible'],
                    ['action' => 'Accéder à /employees', 'expected' => 'Refus (403 ou redirection)'],
                ],
            ],

            // ── Module 6 — Postes ────────────────────────────────────────
            [
                'id'     => 'CT-POSTE-01',
                'module' => 'Postes',
                'name'   => 'Création d\'un poste',
                'pre'    => 'Connecté admin@gescolab.ci.',
                'steps'  => [
                    ['action' => 'Menu Admin → Postes → Nouveau poste', 'expected' => 'Formulaire affiché'],
                    ['action' => 'Saisir libellé "Directeur Technique", niveau 2, cocher Peut être N+1, département DSI', 'expected' => 'Champs remplis'],
                    ['action' => 'Enregistrer', 'expected' => 'Toast vert, poste dans la liste'],
                    ['action' => 'Ouvrir le formulaire de création d\'employé', 'expected' => 'Le nouveau poste apparaît dans le select Poste'],
                ],
            ],
            [
                'id'     => 'CT-POSTE-02',
                'module' => 'Postes',
                'name'   => 'Impact du flag can_be_n1',
                'pre'    => 'Poste A avec can_be_n1=true niveau 2. Poste B avec can_be_n1=false.',
                'steps'  => [
                    ['action' => 'Formulaire employé → champ Supérieur N+1', 'expected' => 'Liste : seuls les employés avec poste can_be_n1=true ET niveau > employé actuel apparaissent'],
                    ['action' => 'Vérifier que les employés du poste B n\'apparaissent pas', 'expected' => 'Absents de la liste'],
                ],
            ],

            // ── Module 7 — Grilles salariales ────────────────────────────
            [
                'id'     => 'CT-GRILLE-01',
                'module' => 'Grilles salariales',
                'name'   => 'Consultation des grilles',
                'pre'    => 'Connecté admin ou comptable.',
                'steps'  => [
                    ['action' => 'Menu Admin → Grilles salariales', 'expected' => '5 grilles affichées (G1 à G5) avec libellés et salaires de base'],
                    ['action' => 'Vérifier que G1 correspond aux Cadres supérieurs', 'expected' => 'Libellé et montant cohérents avec le seed'],
                ],
            ],
            [
                'id'     => 'CT-GRILLE-02',
                'module' => 'Grilles salariales',
                'name'   => 'Pré-remplissage du salaire depuis la grille dans un contrat',
                'pre'    => 'Formulaire de création de contrat ouvert.',
                'steps'  => [
                    ['action' => 'Sélectionner la grille G3', 'expected' => 'Le champ Salaire de base se remplit automatiquement avec la valeur de G3'],
                    ['action' => 'Modifier manuellement le salaire', 'expected' => 'Valeur modifiable (la grille est un point de départ, pas un verrou)'],
                ],
            ],

            // ── Module 8 — Rôles ─────────────────────────────────────────
            [
                'id'     => 'CT-ROLE-01',
                'module' => 'Rôles & Permissions',
                'name'   => 'Assignation d\'un rôle à un utilisateur',
                'pre'    => 'Connecté admin@gescolab.ci.',
                'steps'  => [
                    ['action' => 'Menu Admin → Rôles → liste des utilisateurs', 'expected' => 'Tableau avec colonne Rôle actuel'],
                    ['action' => 'Cliquer Modifier le rôle pour un utilisateur, sélectionner rh', 'expected' => 'Toast vert "Rôle assigné"'],
                    ['action' => 'Se reconnecter avec cet utilisateur', 'expected' => 'Accès RH actif'],
                ],
            ],
            [
                'id'     => 'CT-ROLE-02',
                'module' => 'Rôles & Permissions',
                'name'   => 'Accès refusé à la gestion des rôles pour non-superadmin',
                'pre'    => 'Connecté rh@gescolab.ci.',
                'steps'  => [
                    ['action' => 'Accéder à /roles', 'expected' => 'Redirection ou 403'],
                ],
            ],
            [
                'id'     => 'CT-ROLE-03',
                'module' => 'Rôles & Permissions',
                'name'   => 'Création d\'un nouveau rôle custom',
                'pre'    => 'Connecté superadmin.',
                'steps'  => [
                    ['action' => 'Menu Admin → Rôles → Nouveau rôle', 'expected' => 'Formulaire affiché'],
                    ['action' => 'Nommer le rôle "auditeur", sélectionner des permissions, enregistrer', 'expected' => 'Toast vert'],
                    ['action' => 'Assigner le rôle à un utilisateur', 'expected' => 'Utilisateur dispose des permissions choisies'],
                ],
            ],

            // ── Module 9 — Messagerie ────────────────────────────────────
            [
                'id'     => 'CT-MSG-01',
                'module' => 'Messagerie',
                'name'   => 'Envoi d\'un message',
                'pre'    => 'Au moins 2 utilisateurs. Connecté employe@gescolab.ci.',
                'steps'  => [
                    ['action' => 'Menu → Messages → Nouveau message', 'expected' => 'Formulaire avec liste des destinataires'],
                    ['action' => 'Sélectionner rh@gescolab.ci, saisir objet et corps', 'expected' => 'Champs remplis'],
                    ['action' => 'Cliquer Envoyer', 'expected' => 'Toast vert, message envoyé'],
                ],
            ],
            [
                'id'     => 'CT-MSG-02',
                'module' => 'Messagerie',
                'name'   => 'Réception et lecture d\'un message',
                'pre'    => 'Message envoyé (CT-MSG-01). Connecté rh@gescolab.ci.',
                'steps'  => [
                    ['action' => 'Menu → Messages', 'expected' => 'Message non lu visible (badge ou style non-lu)'],
                    ['action' => 'Cliquer sur le message', 'expected' => 'Contenu affiché, message marqué comme lu'],
                    ['action' => 'Vérifier le badge de notification', 'expected' => 'Compteur décrémenté'],
                ],
            ],
            [
                'id'     => 'CT-MSG-03',
                'module' => 'Messagerie',
                'name'   => 'Suppression d\'un message',
                'pre'    => 'Message reçu et lu. Connecté destinataire.',
                'steps'  => [
                    ['action' => 'Ouvrir le message, cliquer Supprimer', 'expected' => 'Confirmation demandée'],
                    ['action' => 'Confirmer', 'expected' => 'Toast vert, message absent de la liste'],
                ],
            ],

            // ── Module 10 — Configuration ────────────────────────────────
            [
                'id'     => 'CT-CONFIG-01',
                'module' => 'Configuration',
                'name'   => 'Modification des paramètres société',
                'pre'    => 'Connecté admin@gescolab.ci.',
                'steps'  => [
                    ['action' => 'Menu Admin → Configuration → Général', 'expected' => 'Formulaire avec Nom société, Adresse, Téléphone, Site web'],
                    ['action' => 'Modifier le nom de la société, enregistrer', 'expected' => 'Toast vert'],
                    ['action' => 'Générer un PDF (ex. bulletin de paie)', 'expected' => 'Le nouveau nom de société apparaît dans l\'en-tête du PDF'],
                ],
            ],
            [
                'id'     => 'CT-CONFIG-02',
                'module' => 'Configuration',
                'name'   => 'Paramètres de paie',
                'pre'    => 'Connecté admin@gescolab.ci.',
                'steps'  => [
                    ['action' => 'Menu Admin → Configuration → Paie', 'expected' => 'Paramètres CNPS et taux affichés'],
                    ['action' => 'Modifier un taux, enregistrer', 'expected' => 'Toast vert, cache vidé automatiquement'],
                    ['action' => 'Générer une fiche de paie', 'expected' => 'Calcul utilise le nouveau taux'],
                ],
            ],

            // ── Module 11 — Profil ───────────────────────────────────────
            [
                'id'     => 'CT-PROFIL-01',
                'module' => 'Profil utilisateur',
                'name'   => 'Modification du profil',
                'pre'    => 'Connecté n\'importe quel utilisateur.',
                'steps'  => [
                    ['action' => 'Menu utilisateur → Mon profil', 'expected' => 'Formulaire pré-rempli avec nom et email'],
                    ['action' => 'Modifier le nom, enregistrer', 'expected' => 'Toast vert, nom mis à jour dans la barre de navigation'],
                ],
            ],
            [
                'id'     => 'CT-PROFIL-02',
                'module' => 'Profil utilisateur',
                'name'   => 'Changement de mot de passe',
                'pre'    => 'Connecté.',
                'steps'  => [
                    ['action' => 'Section Mot de passe du profil', 'expected' => 'Champs mot de passe actuel, nouveau, confirmation'],
                    ['action' => 'Saisir un mauvais mot de passe actuel', 'expected' => 'Erreur de validation'],
                    ['action' => 'Saisir le bon mot de passe actuel et un nouveau, enregistrer', 'expected' => 'Toast vert'],
                    ['action' => 'Se déconnecter et se reconnecter avec le nouveau mot de passe', 'expected' => 'Connexion réussie'],
                ],
            ],

            // ── Module 12 — UI transverses ───────────────────────────────
            [
                'id'     => 'CT-UI-01',
                'module' => 'UI transverses',
                'name'   => 'Système de toasts',
                'pre'    => 'Connecté admin.',
                'steps'  => [
                    ['action' => 'Effectuer une action réussie (ex. créer un employé)', 'expected' => 'Toast vert apparaît en haut à droite'],
                    ['action' => 'Vérifier la position', 'expected' => 'Haut à droite, ne chevauche pas le contenu principal'],
                    ['action' => 'Attendre 7 secondes', 'expected' => 'Toast disparaît automatiquement avec animation'],
                    ['action' => 'Cliquer sur la croix d\'un toast avant les 7 secondes', 'expected' => 'Toast fermé immédiatement'],
                ],
            ],
            [
                'id'     => 'CT-UI-02',
                'module' => 'UI transverses',
                'name'   => 'Composant date Flatpickr',
                'pre'    => 'Formulaire avec champ date (ex. nouveau contrat).',
                'steps'  => [
                    ['action' => 'Cliquer sur un champ date', 'expected' => 'Calendrier Flatpickr s\'ouvre (thème bleu GES-COLAB)'],
                    ['action' => 'Sélectionner une date', 'expected' => 'Affichée en jj/mm/aaaa dans le champ visible'],
                    ['action' => 'Inspecter la valeur cachée soumise', 'expected' => 'Valeur au format YYYY-MM-DD'],
                    ['action' => 'Saisir une date manuellement dans le champ', 'expected' => 'Accepté (allowInput: true)'],
                ],
            ],
            [
                'id'     => 'CT-UI-03',
                'module' => 'UI transverses',
                'name'   => 'Breadcrumb et navigation',
                'pre'    => 'Connecté admin, sur la fiche d\'un employé.',
                'steps'  => [
                    ['action' => 'Vérifier la barre de navigation', 'expected' => 'Fil d\'Ariane : Employés > [Nom de l\'employé]'],
                    ['action' => 'Cliquer sur le lien "Employés" du breadcrumb', 'expected' => 'Retour à la liste /employees'],
                ],
            ],
            [
                'id'     => 'CT-UI-04',
                'module' => 'UI transverses',
                'name'   => 'Barre d\'actions sticky sur formulaire long',
                'pre'    => 'Formulaire de création/édition d\'employé.',
                'steps'  => [
                    ['action' => 'Faire défiler le formulaire vers le bas', 'expected' => 'La barre d\'actions (Annuler / Enregistrer) reste visible en bas de l\'écran'],
                    ['action' => 'Faire défiler vers le haut', 'expected' => 'Barre toujours visible (sticky bottom)'],
                    ['action' => 'Tester sur mobile (< 768px)', 'expected' => 'Le hint de gauche disparaît, les boutons prennent toute la largeur'],
                ],
            ],
            [
                'id'     => 'CT-UI-05',
                'module' => 'UI transverses',
                'name'   => 'Pagination',
                'pre'    => 'Plus de 15 employés (dépasser une page).',
                'steps'  => [
                    ['action' => 'Menu → Employés', 'expected' => 'Pagination affichée en bas avec chevrons Bootstrap Icons'],
                    ['action' => 'Cliquer sur la page 2', 'expected' => 'Deuxième page chargée, filtre actif préservé dans l\'URL'],
                    ['action' => 'Vérifier le compteur', 'expected' => '"Affichage X–Y sur Z" cohérent avec la page affichée'],
                ],
            ],

            // ── Module 13 — Régressions ──────────────────────────────────
            [
                'id'     => 'CT-REG-01',
                'module' => 'Régressions',
                'name'   => 'Persistance des filtres avec tri + pagination',
                'pre'    => 'Liste employés avec filtre actif et tri sur une colonne.',
                'steps'  => [
                    ['action' => 'Filtrer par département, trier par nom ASC', 'expected' => 'Résultats filtrés et triés'],
                    ['action' => 'Naviguer en page 2', 'expected' => 'Filtre et tri conservés dans l\'URL et appliqués'],
                ],
            ],
            [
                'id'     => 'CT-REG-02',
                'module' => 'Régressions',
                'name'   => 'XSS dans les toasts (vérification du correctif)',
                'pre'    => 'Connecté employe@gescolab.ci.',
                'steps'  => [
                    ['action' => 'Mon profil → changer le nom en <img src=x onerror=alert(1)>, enregistrer', 'expected' => 'Nom sauvegardé (pas de validation HTML côté serveur)'],
                    ['action' => 'Connecté admin : assigner un rôle à cet utilisateur', 'expected' => 'Toast affiché avec le nom de l\'utilisateur'],
                    ['action' => 'Vérifier que la balise <img> n\'est PAS interprétée', 'expected' => 'Toast affiche le texte littéral — aucune boîte de dialogue ne s\'ouvre'],
                ],
            ],
            [
                'id'     => 'CT-REG-03',
                'module' => 'Régressions',
                'name'   => 'Audit logging',
                'pre'    => 'Connecté admin.',
                'steps'  => [
                    ['action' => 'Créer un employé', 'expected' => 'Action tracée dans storage/logs/YYYY/MM/DD/laravel.log'],
                    ['action' => 'Ouvrir le fichier de log du jour', 'expected' => 'Entrée [TRACE] EmployeeController@store avec url, user_id, ip'],
                    ['action' => 'Supprimer un employé', 'expected' => 'Entrée [TRACE] EmployeeController@destroy présente'],
                ],
            ],
        ];
    }

    public static function find(string $id): ?array
    {
        foreach (self::all() as $scenario) {
            if ($scenario['id'] === $id) {
                return $scenario;
            }
        }
        return null;
    }

    public static function modules(): array
    {
        $modules = [];
        foreach (self::all() as $scenario) {
            $modules[$scenario['module']][] = $scenario;
        }
        return $modules;
    }

    public static function ids(): array
    {
        return array_column(self::all(), 'id');
    }
}