# 📊 Intégration avec le Module Margin de Dolibarr

## 🎯 Vue d'ensemble

Le module SIG s'intègre parfaitement avec le module **Margin** natif de Dolibarr pour offrir un suivi complet des marges réalisées et prévisionnelles.

## 🏗️ Architecture

### Champs utilisés

- **`llx_facture.total_margin`** : Marge totale de la facture (calculée automatiquement)
- **`llx_facturedet.buy_price_ht`** : Prix d'achat HT unitaire
- **`llx_facturedet.marge_tx`** : Taux de marge (%)
- **`llx_facturedet.marque_tx`** : Taux de marque (%)

### Calculs automatiques

Le module Margin de Dolibarr calcule automatiquement :
- **Marge unitaire** = Prix de vente HT - Prix d'achat HT
- **Marge totale ligne** = Marge unitaire × Quantité
- **Taux de marge** = (Marge / Prix d'achat) × 100
- **Taux de marque** = (Marge / Prix de vente) × 100

## ✅ Fonctionnalités du Module SIG

### 1. Tableau de Suivi des Marges (`index.php`)

- **Marges réalisées** : Calcul mensuel des marges des factures validées/payées
- **Objectifs de marge** : Saisie et suivi des objectifs mensuels
- **Écarts et taux de réalisation** : Comparaison automatique
- **Diagnostic en temps réel** : Détection des problèmes de configuration

### 2. Diagnostic Automatique

Le système détecte automatiquement :
- ❌ Module Margin désactivé
- ⚠️ Factures sans marge calculée
- 📋 Produits sans prix d'achat
- 🔍 Problèmes de configuration

### 3. Utilitaires de Maintenance (`admin/setup.php`)

- **Vérification du statut** : Contrôle de l'activation du module Margin
- **Recalcul des marges** : Mise à jour des marges des factures existantes
- **Diagnostic complet** : Analyse des problèmes potentiels

## 🚀 Installation et Configuration

### Prérequis

1. **Activer le module Margin** dans `Configuration > Modules > Marges`
2. **Renseigner les prix d'achat** de tous vos produits/services
3. **Vérifier les paramètres** dans `Configuration > Modules > SIG`

### Configuration recommandée

```php
// Configuration automatique
$conf->margin->enabled = 1;  // Module Margin activé

// Données produits
// Tous les produits doivent avoir un prix d'achat (cost_price) renseigné
```

## 📈 Utilisation

### 1. Suivi des Marges Réalisées

```sql
-- Requête utilisée par le module
SELECT SUM(f.total_margin) as total_margin
FROM llx_facture as f
WHERE f.entity IN (getEntity('invoice', 1))
  AND f.fk_statut IN (1,2)  -- Validées et payées
  AND f.type IN (0, 1, 2)   -- Standard, avoirs, remplacements
  AND f.datef BETWEEN 'date_debut' AND 'date_fin'
```

### 2. Calcul des Objectifs

- Saisie manuelle des objectifs mensuels
- Comparaison automatique avec les réalisations
- Calcul des écarts et pourcentages de réalisation

### 3. Diagnostic des Problèmes

Le système vérifie automatiquement :
- Statut du module Margin
- Cohérence des données
- Complétude des prix d'achat

## 🔧 Maintenance

### Recalcul des Marges

En cas de problème ou après activation du module Margin :

1. Aller dans `Configuration > Modules > SIG`
2. Section "Utilitaires Marges"
3. Cliquer sur "🔄 Recalculer les marges des factures"

### Résolution des Problèmes Courants

| Problème | Solution |
|----------|----------|
| Marges à 0€ | Vérifier les prix d'achat des produits |
| Module désactivé | Activer le module Margin dans Configuration > Modules |
| Données incohérentes | Utiliser l'outil de recalcul des marges |
| Factures anciennes | Recalculer après activation du module |

## 📊 Indicateurs Disponibles

### Marges Réalisées
- Par mois, trimestre, année
- Toutes factures validées/payées
- Inclut avoirs et remplacements

### Objectifs et Écarts
- Saisie libre des objectifs
- Calcul automatique des écarts
- Taux de réalisation en %

### Diagnostic
- Nombre de factures sans marge
- Produits sans prix d'achat
- Statut des modules requis

## 🎯 Bonnes Pratiques

1. **Renseigner tous les prix d'achat** avant d'utiliser le suivi des marges
2. **Activer le module Margin** dès le début pour éviter les recalculs
3. **Vérifier régulièrement** le diagnostic automatique
4. **Utiliser les objectifs** pour piloter la performance
5. **Recalculer les marges** après toute modification de prix d'achat

## 🔗 Intégration avec les Autres Modules

- **Comptabilité** : Cohérence avec les écritures comptables
- **Produits/Services** : Utilisation des prix d'achat
- **Factures** : Calcul automatique lors de la validation
- **Statistiques** : Données consolidées pour le reporting

---

*Cette intégration respecte l'architecture native de Dolibarr et utilise les APIs standards pour garantir la compatibilité et la fiabilité.* 