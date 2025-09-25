# 📝 Changelog - Module SIG

Toutes les modifications notables de ce projet seront documentées dans ce fichier.

---

## [0.81] - 2025-09-25

### 🔄 **Changements Majeurs**
- **Refonte complète du système de marges** : Passage d'un calcul basé sur le module Margin natif à un calcul simplifié basé sur le CA réalisé
- **Suppression de la dépendance** au module Margin de Dolibarr
- **Simplification de l'interface** : Suppression de la section diagnostic complexe

### ✨ **Nouveautés**
- **Calcul de marge simplifié** : `Marge = CA Réalisé × Taux de Marge Configuré`
- **Configuration centralisée** : Un seul paramètre `SIG_MARGIN_RATE` à configurer
- **Performance optimisée** : Réduction significative du nombre de requêtes SQL

### 🗑️ **Suppressions**
- Section "DIAGNOSTIC - Factures et Marges du mois en cours" dans `index.php`
- Fonction `sig_diagnose_margins()` 
- Tableau explicatif de la méthode de calcul
- Toutes les requêtes SQL de debug

### 🔧 **Modifications Techniques**
- **`sig_get_margin_for_month()`** : Refonte complète de l'algorithme
- **Interface utilisateur** : Épuration du tableau de suivi des marges
- **Documentation** : Ajout de `VERSION_0.81_SUMMARY.md`

### 🐛 **Corrections**
- Résolution du problème de tableau diagnostic vide
- Amélioration de la fiabilité des calculs de marge
- Optimisation des performances

---

## [0.8] - 2025-09-20

### ✨ **Nouveautés**
- **Tableau de bord avec suivi d'objectifs** : Interface complète pour définir et suivre les objectifs de CA
- **Suivi des marges réalisées** : Intégration avec le module Margin natif de Dolibarr
- **Navigation améliorée** : Réorganisation des menus et des pages
- **Sauvegarde persistante** : Objectifs conservés par année en base de données

### 🔧 **Améliorations**
- **Architecture optimisée** : Fonctions communes dans `lib/sig_functions.php`
- **Interface utilisateur** : Codes couleurs et indicateurs visuels
- **Calculs automatiques** : Taux de réalisation et écarts en temps réel

---

## [0.7] - 2025-09-15

### ✨ **Nouveautés**
- **Page Soldes Intermédiaires de Gestion (SIG)** : Tableau SIG complet avec 11 indicateurs
- **Graphique interactif** : Visualisation en barres des postes SIG
- **Ratios d'analyse** : 4 ratios clés avec seuils d'alerte visuels
- **Configuration des charges sociales** : Taux configurable pour les charges

### 🔧 **Améliorations**
- **Calculs automatiques** : Marge commerciale, Valeur ajoutée, EBE, Résultat d'exploitation
- **Interface d'administration** : Configuration native Dolibarr

---

## [0.6] - 2025-09-10

### ✨ **Nouveautés**
- **Tableau de trésorerie prévisionnel** : Vue mensuelle des mouvements
- **Graphique interactif** : Visualisation avec Chart.js
- **Configuration avancée** : Options d'inclusion granulaires

### 🔧 **Améliorations**
- **Données exactes** : Utilisation des données réelles du module banque
- **Interface épurée** : Suppression des sections redondantes
- **Totaux annuels** : Synthèse des mouvements

---

## [0.5] - 2025-09-05

### ✨ **Nouveautés**
- **Module de base** : Structure initiale du module SIG
- **Configuration de base** : Paramètres essentiels
- **Menus intégrés** : Navigation dans Dolibarr

### 🔧 **Fonctionnalités**
- Calculs de base pour CA et trésorerie
- Interface d'administration
- Droits utilisateur

---

## 📋 **Légende**

- ✨ **Nouveautés** : Nouvelles fonctionnalités
- 🔧 **Améliorations** : Améliorations de fonctionnalités existantes
- 🐛 **Corrections** : Corrections de bugs
- 🗑️ **Suppressions** : Fonctionnalités supprimées
- 🔄 **Changements** : Modifications importantes
- 📚 **Documentation** : Mises à jour de documentation