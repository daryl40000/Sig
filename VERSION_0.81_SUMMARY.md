# 📊 Module SIG - Version 0.81

## 🎯 **Résumé de la Version**

Cette version apporte une **refonte complète du système de suivi des marges** avec une approche simplifiée et plus fiable, basée sur le chiffre d'affaires réalisé et un taux de marge configurable.

---

## 🆕 **Nouvelles Fonctionnalités**

### **1. Système de Marges Simplifié**
- **Calcul automatique** : Marge = CA Réalisé × Taux de Marge Configuré
- **Indépendance** : Plus de dépendance au module Margin natif de Dolibarr
- **Fiabilité** : Basé sur les données de CA déjà validées
- **Configuration** : Taux de marge paramétrable dans le module SIG

### **2. Tableau de Suivi des Marges Amélioré**
- **Interface épurée** : Suppression des éléments de diagnostic complexes
- **Calcul cohérent** : Utilise les mêmes critères que le CA (factures validées/payées, types 0,1,2)
- **Performance optimisée** : Moins de requêtes SQL, calculs plus rapides

---

## 🔄 **Modifications Techniques**

### **Fonction `sig_get_margin_for_month()` - Refonte Complète**

**Avant (v0.8) :**
```php
// Utilisait f.total_margin du module Margin natif
$sql = 'SELECT SUM(f.total_margin) as total_margin';
$sql .= ' FROM '.MAIN_DB_PREFIX.'facture as f';
// ... requête complexe avec dépendances
```

**Après (v0.81) :**
```php
// Utilise le CA réalisé avec taux de marge configuré
$margin_rate = getDolGlobalString('SIG_MARGIN_RATE') / 100;
$ca_realise = sig_get_turnover_for_month($db, $year, $month);
$marge_calculee = $ca_realise * $margin_rate;
```

### **Suppressions (`index.php`)**
- ❌ **Section DIAGNOSTIC complète** (lignes ~350-600)
- ❌ **Fonction `sig_diagnose_margins()`**
- ❌ **Tableau explicatif de la méthode de calcul**
- ❌ **Résumés de diagnostic CA/Marge du mois**
- ❌ **Toutes les requêtes SQL de debug**

### **Configuration**
- ✅ **Utilisation de `SIG_MARGIN_RATE`** : Taux configurable dans le module
- ✅ **Valeur par défaut** : 20% si non configuré
- ✅ **Cohérence** : Même logique que les autres calculs du module

---

## 📈 **Avantages de la Nouvelle Approche**

| Aspect | Avant (v0.8) | Après (v0.81) |
|--------|-------------|---------------|
| **Dépendances** | Module Margin requis | Aucune dépendance externe |
| **Complexité** | Requêtes SQL complexes | Calcul simple et direct |
| **Fiabilité** | Dépend des prix d'achat | Basé sur le CA validé |
| **Performance** | Multiples requêtes | Calcul optimisé |
| **Maintenance** | Diagnostic complexe | Interface épurée |
| **Configuration** | Paramètres multiples | Un seul taux à configurer |

---

## 🔧 **Configuration Requise**

### **Paramètre Principal**
- **`SIG_MARGIN_RATE`** : Taux de marge en pourcentage (défaut: 20%)
- **Emplacement** : Configuration > Modules > SIG > Configuration générale

### **Exemple de Configuration**
```php
// Dans admin/setup.php
$margin_rate = GETPOST('SIG_MARGIN_RATE', 'alphanohtml');
dolibarr_set_const($db, 'SIG_MARGIN_RATE', $margin_rate, 'chaine', 0, '', $conf->entity);
```

---

## 💡 **Utilisation**

### **Calcul des Marges**
```
Marge Mensuelle = CA Réalisé du Mois × Taux de Marge Configuré

Exemple :
- CA janvier : 15 000 €
- Taux configuré : 25%
- Marge calculée : 15 000 € × 25% = 3 750 €
```

### **Critères de Calcul**
- **Factures incluses** : Validées (statut 1) et Payées (statut 2)
- **Types inclus** : Standard (0), Avoirs (1), Remplacements (2)
- **Période** : Date de facture (`f.datef`)
- **Entités** : Respect des entités multi-entreprises

---

## 🎯 **Interface Utilisateur**

### **Tableau de Suivi des Marges**
- **Marges réalisées** : Calcul automatique mensuel
- **Objectifs de marge** : Saisie manuelle des objectifs
- **Écarts** : Calcul automatique (Réalisé - Objectif)
- **Taux de réalisation** : Pourcentage d'atteinte des objectifs

### **Fonctionnalités Conservées**
- ✅ Saisie des objectifs mensuels
- ✅ Calcul des écarts et taux de réalisation
- ✅ Sauvegarde des objectifs par année
- ✅ Affichage des totaux annuels

---

## 🔄 **Migration depuis v0.8**

### **Automatique**
- ✅ **Aucune action requise** : La migration est transparente
- ✅ **Objectifs conservés** : Les objectifs de marge sauvegardés sont préservés
- ✅ **Configuration existante** : Le taux de marge existant est utilisé

### **Recommandations**
1. **Vérifier le taux de marge** dans Configuration > Modules > SIG
2. **Ajuster si nécessaire** selon votre activité
3. **Tester les calculs** sur quelques mois pour validation

---

## 🐛 **Corrections**

### **Problèmes Résolus**
- ✅ **Tableau diagnostic vide** : Problème de requête SQL complexe
- ✅ **Dépendance au module Margin** : Suppression de la dépendance
- ✅ **Performance** : Réduction du nombre de requêtes SQL
- ✅ **Complexité interface** : Simplification de l'affichage

### **Améliorations**
- ✅ **Cohérence des calculs** : Même logique que le CA
- ✅ **Simplicité de configuration** : Un seul paramètre à gérer
- ✅ **Fiabilité** : Calculs basés sur des données sûres

---

## 📋 **Fichiers Modifiés**

### **`index.php`**
- **Fonction `sig_get_margin_for_month()`** : Refonte complète
- **Suppression** : Section diagnostic (~300 lignes)
- **Interface** : Simplification du tableau des marges

### **Nouveaux Fichiers**
- **`VERSION_0.81_SUMMARY.md`** : Documentation de version
- **`MARGIN_INTEGRATION.md`** : Documentation technique (conservée pour référence)

---

## 🚀 **Prochaines Versions**

### **Améliorations Envisagées**
- **Taux de marge par produit/service** : Configuration plus fine
- **Historique des taux** : Suivi des évolutions
- **Graphiques** : Visualisation des tendances
- **Export** : Données de marges en CSV/Excel

---

## 📞 **Support**

Pour toute question ou problème lié à cette version :
1. **Vérifier la configuration** du taux de marge
2. **Consulter la documentation** technique
3. **Tester sur une période** connue pour validation

---

*Version 0.81 - Simplification et Fiabilisation du Suivi des Marges*
*Date de release : Septembre 2025* 