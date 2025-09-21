# Changelog - Module SIG

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