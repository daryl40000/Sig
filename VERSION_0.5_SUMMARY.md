# Module SIG - Version 0.5 - Résumé technique

## 🚀 **Nouvelle fonctionnalité majeure : Factures modèle client**

### 📊 **Fonctionnalité ajoutée**
- **Inclusion des factures modèle client** dans les encaissements prévisionnels de trésorerie
- **Gestion de la fréquence** : Mensuelle, trimestrielle, semestrielle, annuelle
- **Calcul de marge automatique** basé sur le taux configuré
- **Configuration granulaire** via une option dédiée dans la section Trésorerie

---

## 🔧 **Implémentation technique**

### 📁 **Fichiers modifiés**

#### `admin/setup.php`
- **Nouvelle option** : "Inclure les factures modèle client"
- **Type** : Case à cocher native Dolibarr (`yesno`)
- **Constante** : `SIG_INCLUDE_CUSTOMER_TEMPLATE_INVOICES`
- **Gestion POST** : Traitement de la nouvelle option dans le formulaire

#### `langs/fr_FR/sig.lang`
- **Traductions ajoutées** :
  - `SigIncludeCustomerTemplateInvoices` : "Inclure les factures modèle client"
  - `SigIncludeCustomerTemplateInvoicesHelp` : Description de l'option

#### `tresorerie.php`
- **Nouvelle fonction** : `sig_get_customer_template_invoices_for_month()`
- **Fonction de fréquence** : `sig_should_generate_template_invoice_for_month()`
- **Fonction de détails** : `sig_get_customer_template_invoices_details_for_month()`
- **Intégration** : Calculs intégrés dans la boucle principale de trésorerie
- **Affichage** : Code couleur orange (#FF9800) pour les factures modèle

#### `core/modules/modSig.class.php`
- **Version** : Mise à jour de `0.41` à `0.5`

---

## 🗃️ **Base de données**

### Table utilisée : `llx_facture_rec`

```sql
-- Colonnes importantes pour la fonctionnalité
`rowid` INT             -- ID de la facture modèle
`titre` VARCHAR(255)    -- Titre/description de la facture modèle
`total_ht` DOUBLE       -- Montant HT de la facture modèle
`frequency` INT         -- Fréquence (1, 3, 6, 12...)
`unit_frequency` CHAR   -- Unité ('m'=mois, 'y'=année)
`date_when` DATE        -- Date de référence pour la génération
`suspended` TINYINT     -- 0=active, 1=suspendue
```

### Logique de fréquence

```sql
-- Exemples de requêtes selon la fréquence
-- Mensuelle : frequency=1, unit_frequency='m'
-- Trimestrielle : frequency=3, unit_frequency='m' 
-- Annuelle : frequency=1, unit_frequency='y'
```

---

## 🎨 **Interface utilisateur**

### Configuration (admin/setup.php)
```php
// Case à cocher native Dolibarr
<input type="checkbox" name="SIG_INCLUDE_CUSTOMER_TEMPLATE_INVOICES" value="1" class="flat">
```

### Affichage dans le tableau de trésorerie
- **Couleur distinctive** : Orange (#FF9800)
- **Libellé** : "Factures modèle: +X€"
- **Intégration** : Dans la colonne encaissements avec détail
- **Totaux** : Pris en compte dans tous les calculs de totaux

---

## 🧮 **Logique de calcul**

### Formule de base
```
Marge facture modèle = Montant HT × Taux de marge configuré
```

### Gestion de la fréquence

#### **Factures mensuelles** (frequency=1, unit='m')
- **Génération** : Tous les mois
- **Marge** : Comptée chaque mois
- **Exemple** : 100€ HT × 20% = 20€ de marge par mois

#### **Factures trimestrielles** (frequency=3, unit='m')
- **Génération** : Janvier, Avril, Juillet, Octobre
- **Marge** : Comptée 4 fois par an
- **Exemple** : 300€ HT × 20% = 60€ de marge par trimestre

#### **Factures annuelles** (frequency=1, unit='y')
- **Génération** : Une fois par an (selon date_when)
- **Marge** : Comptée une seule fois
- **Exemple** : 1200€ HT × 20% = 240€ de marge par an

### Règles temporelles
- **Mois passés** : Pas de factures modèle (données réelles seulement)
- **Mois actuel et futurs** : Inclusion des factures modèle selon fréquence
- **Date de référence** : Utilise `date_when` si définie, sinon règles par défaut

---

## 🔄 **Corrections apportées**

### Correction de la logique de délai
- **Problème initial** : Prise en compte des factures du mois précédent (déjà facturées)
- **Solution** : Marge comptée le mois de génération de la facture
- **Délai de 30 jours** : Affecte l'encaissement réel, pas le calcul prévisionnel

### Restriction aux mois futurs
- **Logique** : Factures modèle appliquées seulement à partir du mois en cours
- **Mois passés** : Données bancaires réelles uniquement
- **Cohérence** : Séparation claire entre réel (passé) et prévisionnel (futur)

---

## 📈 **Impact sur les calculs de trésorerie**

### Nouvelle formule du solde de fin de mois
```
Solde fin = Solde début 
          + Encaissements effectués 
          + Factures client impayées 
          + Factures modèle (NOUVEAU)
          + Marge mois précédent 
          - Décaissements 
          - Salaires impayés
```

### Totaux annuels
- **Encaissements prévisionnels** : Incluent les factures modèle
- **Affichage détaillé** : Ligne séparée pour les factures modèle
- **Couleur** : Orange pour la distinction visuelle

---

## ⚙️ **Configuration requise**

### Activation de la fonctionnalité
1. **Configuration** : Aller dans Configuration > Modules > SIG
2. **Option** : Cocher "Inclure les factures modèle client"
3. **Taux de marge** : Configurer le pourcentage de marge (défaut : 20%)
4. **Sauvegarde** : Enregistrer la configuration

### Prérequis techniques
- **Table** : `llx_facture_rec` doit exister (module factures récurrentes Dolibarr)
- **Données** : Factures modèle configurées avec fréquence
- **Permissions** : Droits de lecture sur les factures récurrentes

---

## 🎯 **Cas d'usage**

### Entreprise avec abonnements mensuels
- **Factures modèle** : Abonnements clients récurrents
- **Fréquence** : Mensuelle (frequency=1, unit='m')
- **Prévision** : Marge mensuelle prévisible
- **Avantage** : Planification de trésorerie précise

### Entreprise avec contrats trimestriels
- **Factures modèle** : Contrats de maintenance trimestriels
- **Fréquence** : Trimestrielle (frequency=3, unit='m')
- **Prévision** : 4 pics de trésorerie par an
- **Avantage** : Anticipation des flux trimestriels

### Entreprise avec contrats annuels
- **Factures modèle** : Licences annuelles
- **Fréquence** : Annuelle (frequency=1, unit='y')
- **Prévision** : Un pic de trésorerie par an
- **Avantage** : Planification des gros encaissements

---

## 🔍 **Fonctions techniques ajoutées**

### `sig_get_customer_template_invoices_for_month()`
- **Rôle** : Calcule la marge des factures modèle pour un mois
- **Paramètres** : Base de données, année, mois
- **Retour** : Montant de la marge (float)
- **Logique** : Vérifie la fréquence et calcule la marge

### `sig_should_generate_template_invoice_for_month()`
- **Rôle** : Détermine si une facture doit être générée ce mois
- **Paramètres** : Objet facture modèle, année, mois
- **Retour** : Booléen (true/false)
- **Logique** : Analyse la fréquence et la date de référence

### `sig_get_customer_template_invoices_details_for_month()`
- **Rôle** : Récupère les détails des factures modèle (fonction de support)
- **Paramètres** : Base de données, année, mois
- **Retour** : Tableau de détails
- **Usage** : Support pour les fonctionnalités de diagnostic

---

## 🏷️ **Informations de version**

- **Version précédente** : 0.41
- **Version actuelle** : 0.5
- **Date de release** : Septembre 2024
- **Type de mise à jour** : Fonctionnalité majeure
- **Compatibilité** : Rétrocompatible avec les versions précédentes
- **Migration** : Aucune action requise, activation par configuration
