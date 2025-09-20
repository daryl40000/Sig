# Module SIG - Pilotage de Trésorerie et Solde Intermédiaire de Gestion

## 📋 Description

Le module SIG (Solde Intermédiaire de Gestion) est un module Dolibarr dédié au pilotage financier et à la gestion de trésorerie. Il permet de suivre le chiffre d'affaires réalisé et prévu, ainsi que d'analyser la trésorerie avec des projections basées sur les devis signés.

## 🚀 Fonctionnalités

### Version 0.2

#### 📊 Tableau de bord CA
- **CA Réalisé** : Chiffre d'affaires des factures validées
- **CA Prévu** : Chiffre d'affaires des devis signés
- **Répartition mensuelle** : Affichage détaillé par mois
- **Gestion des dates de livraison** : Affectation automatique au bon mois

#### 💰 Trésorerie prévisionnelle
- **Mouvements bancaires** : Suivi des encaissements et décaissements
- **Marge prévue** : Calcul automatique basé sur le CA prévu et un taux configurable
- **Délai de paiement** : Prise en compte du délai entre livraison et paiement
- **Solde théorique** : Projection du solde en incluant les marges futures

#### ⚙️ Configuration avancée
- **Taux de marge** : Configuration personnalisable (par défaut 20%)
- **Délai de paiement** : Délai moyen configurable (par défaut 30 jours)
- **Compte bancaire** : Sélection du compte principal pour les analyses

## 📦 Installation

### Prérequis
- Dolibarr 16.0 ou supérieur
- PHP 7.4 ou supérieur
- Module Comptabilité activé (pour les mouvements bancaires)

### Installation
1. Copier le dossier `sig` dans `htdocs/custom/`
2. Aller dans **Home → Setup → Modules/Applications**
3. Rechercher "SIG" et cliquer sur **Activer**
4. Configurer le module dans **Configuration → Module SIG**

## 🔧 Configuration

### Paramètres principaux

#### Taux de marge théorique
- **Valeur par défaut** : 20%
- **Utilisation** : Calcul de la marge prévue à partir du CA des devis signés
- **Configuration** : Admin → Configuration → Module SIG

#### Délai moyen de paiement
- **Valeur par défaut** : 30 jours
- **Utilisation** : Décalage de la marge dans le temps (livraison → paiement)
- **Configuration** : Admin → Configuration → Module SIG

#### Compte bancaire principal
- **Utilisation** : Compte utilisé pour les analyses de trésorerie
- **Option** : "Tous les comptes" pour une vue globale
- **Configuration** : Admin → Configuration → Module SIG

## 📈 Utilisation

### Tableau de bord CA
1. Aller dans **Pilotage → Chiffre d'affaires actuel**
2. Consulter le résumé en haut de page
3. Analyser le tableau détaillé par mois

### Trésorerie
1. Aller dans **Pilotage → Trésorerie**
2. Sélectionner l'année à analyser
3. Consulter les mouvements et soldes théoriques

### Configuration
1. Aller dans **Pilotage → Configuration**
2. Ajuster les paramètres selon vos besoins
3. Sauvegarder les modifications

## 🧮 Logique de calcul

### CA Prévu
```
CA Prévu = Somme des devis signés (statut = 2)
- Si date de livraison renseignée → affecté au mois de livraison
- Si pas de date de livraison → affecté au mois courant
```

### Marge prévue
```
Marge = CA Prévu × Taux de marge configuré
```

### Marge avec délai de paiement
```
Marge décalée = Marge des livraisons qui seront payées ce mois-ci
Date de paiement = Date de livraison + Délai configuré
```

### Solde fin théorique
```
Solde fin = Solde début + Encaissements - Décaissements + Marge avec délai
```

## 🔍 Diagnostics

Le module inclut des diagnostics détaillés accessibles via les commentaires HTML :
- `<!-- DIAGNOSTIC MARGINS: ... -->` : Calculs de marge
- `<!-- DIAGNOSTIC SOLDE DEBUT: ... -->` : Solde de début
- `<!-- DIAGNOSTIC SOLDE FIN: ... -->` : Solde fin théorique
- `<!-- DIAGNOSTIC MARGIN DELAY: ... -->` : Marge avec délai

## 🛠️ Développement

### Structure du module
```
sig/
├── admin/
│   └── setup.php          # Configuration
├── core/
│   └── modules/
│       └── modSig.class.php # Définition du module
├── langs/
│   └── fr_FR/
│       └── sig.lang       # Traductions
├── index.php              # Tableau de bord CA
├── tresorerie.php         # Page trésorerie
├── CHANGELOG.md           # Historique des versions
└── README.md              # Documentation
```

### Fonctions principales
- `sig_get_expected_turnover_for_month()` : CA prévu par mois
- `sig_get_expected_margin_for_month()` : Marge prévue par mois
- `sig_get_expected_margin_with_delay_for_month()` : Marge avec délai
- `sig_get_bank_balance_at_date()` : Solde bancaire à une date

## 📝 Changelog

### Version 0.2
- ✅ Configuration du taux de marge théorique
- ✅ Configuration du délai de paiement
- ✅ Trésorerie prévisionnelle avec marges décalées
- ✅ Solde fin théorique corrigé
- ✅ Élimination du double comptage des marges
- ✅ Validation des paramètres et robustesse

### Version 0.1
- ✅ Tableau de bord CA de base
- ✅ Trésorerie avec mouvements bancaires
- ✅ Configuration du compte bancaire

## 🤝 Support

Pour toute question ou problème :
1. Vérifier les diagnostics dans les commentaires HTML
2. Consulter les logs Dolibarr
3. Vérifier la configuration du module

## 📄 Licence

Ce module est développé pour Dolibarr et suit les mêmes conditions d'utilisation.

---

**Module SIG v0.2** - Pilotage de trésorerie et SIG pour Dolibarr