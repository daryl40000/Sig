# Changelog - Module SIG

## Version 0.4 (2025-09-21)

### 🚀 Refonte majeure de la page trésorerie

#### Page de trésorerie épurée et optimisée
- ✅ **Suppression des sections redondantes** : Suppression des boîtes "CA Encaissé" et "CA Potentiel" (disponibles sur leur page dédiée)
- ✅ **Suppression de la section "Solde Bancaire"** : Informations redondantes avec le tableau principal
- ✅ **Interface simplifiée** : Page focalisée uniquement sur l'évolution de la trésorerie

#### Graphique interactif des soldes
- ✅ **Nouveau graphique Chart.js** : Visualisation de l'évolution des soldes de fin de mois sur l'année
- ✅ **Design moderne** : Graphique en ligne avec zone remplie, couleurs cohérentes avec l'interface
- ✅ **Interactivité** : Survol pour voir les valeurs exactes, formatage en euros
- ✅ **Responsive** : Adaptation automatique à la taille de l'écran

#### Amélioration des données pour les mois passés
- ✅ **Données exactes du module banque** : Pour les mois écoulés, utilisation des données réelles du module banque Dolibarr
- ✅ **Fonction dédiée** : `sig_get_bank_movements_real_for_month()` pour récupérer uniquement les mouvements bancaires réels
- ✅ **Soldes cohérents** : Les soldes de fin des mois passés correspondent exactement au module banque

#### Correction de la logique prévisionnelle
- ✅ **Mois en cours corrigé** : Inclusion des factures qui arrivent à échéance dans le mois en cours (et pas seulement celles en retard)
- ✅ **Roll-over des retards** : Les factures/salaires en retard des mois passés sont reportés au mois actuel
- ✅ **Calcul prévisionnel optimisé** : Nouvelle formule pour le solde fin = Solde début + Encaissements + Factures client + Marge mois précédent - Décaissements

#### Simplification de l'affichage
- ✅ **Colonne "Solde Fin" simplifiée** : Affichage direct du solde prévisionnel sans détails des composants
- ✅ **Code nettoyé** : Suppression de toutes les lignes de diagnostic temporaires

### 🔧 Améliorations techniques

#### Nouvelles fonctions
- ✅ **`sig_get_bank_movements_real_for_month()`** : Récupération des mouvements bancaires réels uniquement
- ✅ **Collecte de données pour graphique** : Tableaux `$mois_labels` et `$soldes_data`

#### Fonctions supprimées (optimisation)
- ❌ **`sig_get_total_turnover_for_year()`** : Fonction non utilisée supprimée
- ❌ **`sig_get_total_expected_turnover_for_year()`** : Fonction non utilisée supprimée

#### Logique améliorée
- ✅ **Distinction mois écoulés/futurs** : Logique claire avec `$is_past_month`
- ✅ **Affichage conditionnel** : Les encaissements/décaissements utilisent les bonnes données selon le type de mois
- ✅ **Correction des dates d'échéance** : Pour le mois en cours, inclusion de toutes les factures jusqu'à la fin du mois

### 📊 Interface utilisateur
- ✅ **Graphique de 400px de hauteur** : Bonne lisibilité des tendances
- ✅ **Titre dynamique** : "Évolution des Soldes de Fin de Mois - [Année]"
- ✅ **Formatage français** : Devise en euros avec séparateurs appropriés
- ✅ **CDN Chart.js** : Chargement moderne de la bibliothèque graphique

---

## Version 0.35 (2025-09-21)

### ✅ Nouvelles fonctionnalités

#### Gestion des salaires impayés
- ✅ **Option "Inclure les salaires impayés"** : Contrôle l'inclusion des salaires impayés dans les décaissements à venir
- ✅ **Fonction optimisée** : Création d'une requête propre `sig_get_unpaid_salaries_for_month()` pour récupérer les salaires impayés par mois
- ✅ **Affichage détaillé** : Les salaires impayés sont affichés dans la colonne des décaissements avec code couleur violet (#9C27B0)
- ✅ **Calculs corrects** : Utilisation de la colonne `dateep` (date de fin de période) pour affecter chaque salaire au bon mois
- ✅ **Totaux annuels** : Prise en compte des salaires impayés dans les totaux de fin d'année

### 🔧 Améliorations techniques
- ✅ **Requête SQL optimisée** : `WHERE paye = 0` pour identifier les salaires impayés
- ✅ **Filtrage par mois** : Utilisation de `dateep >= 'YYYY-MM-01' AND dateep <= 'YYYY-MM-31'` pour la répartition mensuelle
- ✅ **Intégration native** : Respect de l'option de configuration `SIG_INCLUDE_UNPAID_SALARIES`
- ✅ **Code propre** : Suppression des diagnostics temporaires et optimisation des performances

### 📊 Configuration
- ✅ **Option de configuration** : "Inclure les salaires impayés" dans la section Trésorerie (admin/setup.php)
- ✅ **Activation/désactivation** : Case à cocher native Dolibarr pour activer ou désactiver la fonctionnalité
- ✅ **Constante Dolibarr** : `SIG_INCLUDE_UNPAID_SALARIES` de type `yesno`

### 📈 Fonctionnement
- **Table utilisée** : `llx_salary`
- **Critère d'impayé** : `paye = 0`
- **Date de référence** : `dateep` (date de fin de période)
- **Montant** : `amount` (montant du salaire)
- **Affichage** : Dans la colonne "Décaissements" du tableau de trésorerie

---

## Version 0.3 (2025-01-XX)

### 🎯 Nouvelles fonctionnalités

#### Configuration avancée de la trésorerie
- ✅ **Option "Inclure les factures fournisseurs"** : Contrôle l'inclusion des factures fournisseurs et charges fiscales/sociales dans les factures fournisseurs
- ✅ **Option "Inclure les charges sociales"** : Contrôle l'inclusion des charges sociales du module Sociales (URSSAF, retraite, etc.)
- ✅ **Option "Inclure les devis signés"** : Contrôle l'inclusion des devis signés dans les encaissements à venir (marge avec délai de paiement)
- ✅ **Option "Inclure les factures client impayées"** : Contrôle l'inclusion des factures client impayées dans les encaissements à venir (basé sur la date de règlement prévue)

#### Améliorations de l'interface
- ✅ **Interface de configuration native** : Utilisation des cases à cocher Dolibarr standard
- ✅ **Affichage de l'état** : Configuration actuelle visible dans l'interface
- ✅ **Séparation des options** : Contrôle indépendant de chaque type d'élément

#### Optimisations du code
- ✅ **Suppression des diagnostics** : Nettoyage du code source (suppression de ~500 lignes de code de diagnostic)
- ✅ **Performance améliorée** : Plus d'exécution de requêtes inutiles
- ✅ **Code source plus propre** : HTML plus léger et plus rapide à charger

### 🔧 Modifications techniques

#### Nouvelles constantes de configuration
- `SIG_INCLUDE_SUPPLIER_INVOICES` : Inclure les factures fournisseurs (yesno)
- `SIG_INCLUDE_SOCIAL_CHARGES` : Inclure les charges sociales (yesno)
- `SIG_INCLUDE_SIGNED_QUOTES` : Inclure les devis signés (yesno)
- `SIG_INCLUDE_CUSTOMER_INVOICES` : Inclure les factures client impayées (yesno)

#### Nouvelles fonctions
- `sig_get_customer_invoices_for_month()` : Récupère les factures client impayées par mois
- `sig_get_sociales_charges_for_month()` : Récupère les charges sociales du module Sociales
- `sig_get_chargesociales_for_month()` : Récupère les charges sociales depuis la table chargesociales

#### Modifications des fonctions existantes
- `sig_get_expected_margin_for_month()` : Respecte l'option SIG_INCLUDE_SIGNED_QUOTES
- `sig_get_expected_margin_with_delay_for_month()` : Respecte l'option SIG_INCLUDE_SIGNED_QUOTES
- `sig_get_bank_movements_details_for_month()` : Intègre les nouvelles options de configuration

### 📊 Logique de calcul mise à jour

#### Colonne "Encaissements"
- Mouvements bancaires réels
- + Marge prévue (si devis signés activés)
- + Factures client impayées (si factures client activées)

#### Colonne "Décaissements"
- Mouvements bancaires réels
- + Factures fournisseurs (si factures fournisseurs activées)
- + Charges sociales (si charges sociales activées)

#### Colonne "Solde fin théorique"
- Solde début + Encaissements - Décaissements
- + Marge avec délai (si devis signés activés)
- + Factures client impayées (si factures client activées)

### 🎛️ Scénarios d'utilisation

1. **Analyse complète** : Toutes les options cochées
2. **Analyse réaliste** : Seulement les mouvements réels (toutes options décochées)
3. **Analyse partielle** : Combinaisons selon les besoins
4. **Analyse conservatrice** : Seulement les factures fournisseurs et charges sociales

### 🐛 Corrections de bugs

- ✅ **Affichage des devis signés** : Correction de l'affichage de la marge des devis signés dans la colonne encaissements
- ✅ **Cohérence des options** : Toutes les options respectent maintenant leur état activé/désactivé
- ✅ **Calcul des totaux** : Les totaux incluent correctement tous les éléments selon les options

### 📝 Traductions ajoutées

- `SigTreasurySection` : Section Trésorerie
- `SigIncludeSupplierInvoices` : Inclure les factures fournisseurs
- `SigIncludeSupplierInvoicesHelp` : Aide pour l'option factures fournisseurs
- `SigIncludeSocialCharges` : Inclure les charges sociales
- `SigIncludeSocialChargesHelp` : Aide pour l'option charges sociales
- `SigIncludeSignedQuotes` : Inclure les devis signés
- `SigIncludeSignedQuotesHelp` : Aide pour l'option devis signés
- `SigIncludeCustomerInvoices` : Inclure les factures client impayées
- `SigIncludeCustomerInvoicesHelp` : Aide pour l'option factures client

---

## Version 0.2 (2025-01-XX)

### 🎯 Fonctionnalités de base

- ✅ **Tableau de trésorerie** : Affichage mensuel des mouvements bancaires
- ✅ **Calcul des marges** : Intégration des devis signés avec délai de paiement
- ✅ **Configuration de base** : Compte bancaire, taux de marge, délai de paiement
- ✅ **Interface utilisateur** : Tableau responsive avec couleurs et totaux

### 🔧 Fonctionnalités techniques

- ✅ **Intégration Dolibarr** : Module natif avec droits et configuration
- ✅ **Requêtes optimisées** : Récupération efficace des données bancaires
- ✅ **Gestion des entités** : Respect des filtres d'entité Dolibarr
- ✅ **Calculs automatiques** : Solde fin théorique et variations

---

## Version 0.1 (2025-01-XX)

### 🎯 Version initiale

- ✅ **Structure du module** : Création de la base du module SIG
- ✅ **Configuration** : Interface de configuration de base
- ✅ **Fonctionnalités** : Première version du tableau de trésorerie