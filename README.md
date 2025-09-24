# Module SIG - Pilotage de trésorerie et SIG (Version 0.8)

## 📋 Description

Le module SIG (Solde Intermédiaire de Gestion) est un module Dolibarr complet qui offre un **cockpit de pilotage financier intégré**. Il combine un tableau de bord avec suivi d'objectifs, l'analyse du chiffre d'affaires, la gestion prévisionnelle de trésorerie avec projections d'incertitude, et l'analyse complète des **Soldes Intermédiaires de Gestion** pour une vision 360° de la performance d'entreprise.

## 🎯 Fonctionnalités principales

### 🚀 **Nouveautés Version 0.8**

#### 🏠 **Tableau de bord avec suivi d'objectifs**
- **Suivi mensuel** : CA réalisé vs objectifs avec écarts automatiques
- **Saisie d'objectifs** : Interface simple pour définir les cibles mensuelles
- **Indicateurs visuels** : Codes couleurs pour identifier rapidement les performances
- **Taux de réalisation** : Pourcentage d'atteinte des objectifs en temps réel
- **Sauvegarde persistante** : Objectifs conservés par année en base de données

#### 🧭 **Navigation améliorée**
- **Menu de gauche intégré** : "Tableau de bord" en première position
- **Réorganisation des pages** : `index.php` → Tableau de bord, `ca.php` → Pilotage CA
- **Architecture optimisée** : Fonctions communes dans `lib/sig_functions.php`

### 🏆 **Fonctionnalités Version 0.7**

#### 📊 **Page Soldes Intermédiaires de Gestion (SIG)**
- **Tableau SIG complet** : 11 indicateurs clés du CA au résultat courant
- **Calculs automatiques** : Marge commerciale, Valeur ajoutée, EBE, Résultat d'exploitation
- **Ratios d'analyse** : 4 ratios clés avec seuils d'alerte visuels (vert/orange/rouge)
- **Graphique interactif** : Visualisation en barres de tous les postes SIG

#### ⚙️ **Configuration des charges sociales**
- **Taux configurable** : Pourcentage de charges sociales appliqué aux salaires (défaut: 55%)
- **Calcul automatique** : Charges sociales = Salaires × Taux configuré
- **Interface d'administration** : Configuration native Dolibarr

### 📊 Tableau de trésorerie prévisionnel
- **Vue mensuelle** : Affichage mois par mois des mouvements de trésorerie
- **Graphique interactif** : Visualisation de l'évolution des soldes de fin de mois avec Chart.js
- **Données exactes** : Pour les mois écoulés, utilisation des données réelles du module banque Dolibarr
- **Calcul automatique** : Solde fin théorique basé sur les encaissements et décaissements prévus
- **Totaux annuels** : Synthèse des mouvements de l'année
- **Interface épurée** : Page focalisée sur l'essentiel, suppression des sections redondantes

### ⚙️ Configuration avancée
- **Compte bancaire** : Sélection du compte bancaire à analyser
- **Taux de marge** : Configuration du taux de marge théorique
- **Délai de paiement** : Délai moyen entre livraison et paiement
- **Options d'inclusion** : Contrôle granulaire des éléments à inclure

### 🎛️ Options de configuration

#### 1. Inclure les factures fournisseurs
- **Description** : Inclut les factures fournisseurs et charges fiscales/sociales dans les factures fournisseurs
- **Impact** : Ajoute les factures fournisseurs impayées dans les décaissements
- **Constante** : `SIG_INCLUDE_SUPPLIER_INVOICES`

#### 2. Inclure les charges sociales
- **Description** : Inclut les charges sociales du module Sociales (URSSAF, retraite, etc.)
- **Impact** : Ajoute les charges sociales impayées dans les décaissements
- **Constante** : `SIG_INCLUDE_SOCIAL_CHARGES`
- **Sources** : Table `llx_chargesociales` et autres tables du module Sociales

#### 3. Inclure les devis signés
- **Description** : Inclut les devis signés dans les encaissements à venir (marge avec délai de paiement)
- **Impact** : Ajoute la marge prévue des devis signés dans les encaissements
- **Constante** : `SIG_INCLUDE_SIGNED_QUOTES`
- **Calcul** : CA prévu × taux de marge configuré

#### 4. Inclure les factures client impayées
- **Description** : Inclut les factures client impayées dans les encaissements à venir
- **Impact** : Ajoute les factures client impayées dans les encaissements
- **Constante** : `SIG_INCLUDE_CUSTOMER_INVOICES`
- **Critères** : Basé sur la date de règlement prévue (`date_lim_reglement`)

## 🚀 Installation

### Prérequis
- Dolibarr 16.0 ou supérieur
- PHP 7.4 ou supérieur
- Module comptabilité activé (recommandé)

### Installation
1. Copier le dossier `Sig` dans `htdocs/custom/`
2. Aller dans **Configuration > Modules** dans Dolibarr
3. Activer le module **SIG**
4. Configurer les paramètres dans **Configuration > Modules > SIG**

## ⚙️ Configuration

### Paramètres de base
- **Compte bancaire** : Sélectionner le compte bancaire à analyser
- **Taux de marge théorique** : Pourcentage de marge à appliquer au CA prévu (défaut : 20%)
- **Délai de paiement** : Délai en jours entre livraison et paiement (défaut : 30 jours)

### Options d'inclusion
- **Factures fournisseurs** : Inclure/exclure les factures fournisseurs
- **Charges sociales** : Inclure/exclure les charges sociales
- **Salaires impayés** : Inclure/exclure les salaires impayés dans les décaissements
- **Devis signés** : Inclure/exclure les devis signés
- **Factures client** : Inclure/exclure les factures client impayées
- **Factures modèle client** : Inclure/exclure les factures modèle client (récurrentes)

## 📊 Utilisation

### Accès au module
1. Aller dans **Pilotage > SIG** dans le menu principal
2. Sélectionner l'année à analyser
3. Le tableau de trésorerie s'affiche automatiquement

### Interprétation du tableau

#### Colonnes
- **Mois** : Mois de l'année
- **Solde Début** : Solde de début de mois
- **Encaissements** : Mouvements positifs + prévisions
- **Décaissements** : Mouvements négatifs + prévisions
- **Solde Fin** : Solde fin théorique
- **Variation** : Variation du mois

#### Couleurs
- **Vert** : Montants positifs (encaissements, variations positives)
- **Rouge** : Montants négatifs (décaissements, variations négatives)
- **Bleu** : Factures client impayées
- **Vert clair** : Marge prévue des devis signés

### Scénarios d'utilisation

#### 1. Analyse complète (toutes options activées)
- Vision optimiste avec tous les éléments prévisionnels
- Utile pour la planification à long terme

#### 2. Analyse réaliste (toutes options désactivées)
- Vision basée uniquement sur les mouvements bancaires réels
- Utile pour l'analyse de la situation actuelle

#### 3. Analyse partielle
- Combinaisons selon les besoins
- Exemple : seulement factures client + devis signés

#### 4. Analyse conservatrice
- Seulement factures fournisseurs et charges sociales
- Vision prudente des décaissements

## 🔧 Fonctionnalités techniques

### Sources de données
- **Mouvements bancaires** : Table `llx_bank`
- **Devis signés** : Table `llx_propal` (statut = 2)
- **Factures client** : Table `llx_facture` (statut = 1)
- **Factures fournisseurs** : Table `llx_facture_fourn` (statut = 1)
- **Charges sociales** : Table `llx_chargesociales` et module Sociales
- **Salaires impayés** : Table `llx_salary` avec `paye = 0` et `dateep` pour la répartition mensuelle

### Calculs automatiques
- **Solde fin théorique** : Solde début + Encaissements - Décaissements + Prévisions
- **Marge prévue** : CA prévu × Taux de marge configuré
- **Marge avec délai** : Marge des livraisons qui seront payées ce mois-ci
- **Totaux annuels** : Somme des mouvements de l'année

### Performance
- **Requêtes optimisées** : Index sur les colonnes de date et statut
- **Cache** : Calculs mis en cache pour éviter les requêtes répétées
- **Filtres d'entité** : Respect des filtres d'entité Dolibarr

## 📝 Changelog

### Version 0.8 (2025-09-24)
- ✅ **Tableau de bord avec suivi d'objectifs** : Page d'accueil dédiée au suivi CA vs objectifs mensuels
- ✅ **Saisie d'objectifs intégrée** : Interface simple pour définir et sauvegarder les cibles
- ✅ **Indicateurs visuels avancés** : Écarts automatiques et taux de réalisation avec codes couleurs
- ✅ **Navigation améliorée** : Nouveau menu "Tableau de bord" en première position
- ✅ **Architecture modulaire** : Réorganisation des fichiers et fonctions communes
- ✅ **Corrections techniques** : Gestion d'erreurs et inclusion des dépendances

### Version 0.7 (2025-09-21)
- ✅ **Page SIG complète** : 11 indicateurs des Soldes Intermédiaires de Gestion
- ✅ **Graphique SIG interactif** : Visualisation en barres avec Chart.js
- ✅ **Ratios d'analyse** : 4 ratios clés avec seuils d'alerte visuels
- ✅ **Configuration charges sociales** : Taux configurable pour calcul automatique

### Version 0.35 (2025-09-21)
- ✅ **Gestion des salaires impayés** : Ajout d'une option pour inclure les salaires impayés dans les décaissements
- ✅ **Fonction optimisée** : Requête propre utilisant `paye = 0` et `dateep` pour la répartition mensuelle
- ✅ **Affichage amélioré** : Code couleur violet pour les salaires impayés dans les décaissements
- ✅ **Configuration** : Option "Inclure les salaires impayés" dans la section Trésorerie

### Version 0.3 (2025-01-XX)
- ✅ Configuration avancée avec 4 options d'inclusion
- ✅ Support des factures client impayées
- ✅ Support des charges sociales du module Sociales
- ✅ Nettoyage du code (suppression des diagnostics)
- ✅ Interface de configuration native Dolibarr

### Version 0.2 (2025-01-XX)
- ✅ Tableau de trésorerie de base
- ✅ Intégration des devis signés
- ✅ Configuration de base

### Version 0.1 (2025-01-XX)
- ✅ Version initiale du module

## 🐛 Dépannage

### Problèmes courants

#### Le tableau ne s'affiche pas
- Vérifier que le module est activé
- Vérifier les droits d'accès
- Vérifier la configuration du compte bancaire

#### Les montants ne correspondent pas
- Vérifier les options d'inclusion dans la configuration
- Vérifier les statuts des factures et devis
- Vérifier les dates de règlement prévues

#### Performance lente
- Vérifier les index sur les tables de base
- Réduire le nombre d'options activées
- Vérifier la taille de la base de données

### Logs et diagnostics
- Les erreurs sont loggées dans les logs Dolibarr
- Utiliser le mode debug pour plus d'informations
- Vérifier les permissions sur les tables

## 🤝 Support

### Documentation
- Changelog détaillé dans `CHANGELOG.md`
- Code commenté pour faciliter la maintenance
- Interface utilisateur intuitive

### Maintenance
- Module compatible avec les mises à jour Dolibarr
- Code respectant les standards Dolibarr
- Tests effectués sur Dolibarr 16.0+

## 📄 Licence

Ce module est développé pour Dolibarr et respecte la licence GPL v3.

## 🆕 Nouveautés version 0.4

### 📈 Graphique interactif
- **Visualisation moderne** : Graphique Chart.js de l'évolution des soldes
- **Interactivité** : Survol pour voir les valeurs exactes
- **Design responsive** : Adaptation à tous les écrans

### 🎯 Interface épurée
- **Suppression des redondances** : Sections CA déplacées vers leur page dédiée
- **Focus sur l'essentiel** : Page concentrée sur la trésorerie uniquement
- **Meilleure lisibilité** : Interface simplifiée et plus claire

### ⚡ Données optimisées
- **Mois écoulés** : Données exactes du module banque Dolibarr
- **Logique corrigée** : Prise en compte des factures arrivant à échéance dans le mois en cours
- **Roll-over intelligent** : Report des retards au mois actuel

## 👥 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à :
- Signaler des bugs
- Proposer des améliorations
- Contribuer au code
- Améliorer la documentation

---

**Module SIG v0.4** - Pilotage de trésorerie et SIG pour Dolibarr