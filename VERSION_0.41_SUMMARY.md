# Module SIG - Version 0.41 - Résumé technique

## 🐛 **Correction critique : Calcul du solde de fin de mois**

### 📊 **Problème corrigé**
- **Erreur identifiée** : Les salaires impayés n'étaient pas pris en compte dans le calcul du solde de fin de mois
- **Impact** : Le solde de fin de mois était surévalué car les salaires impayés étaient affichés mais pas déduits du calcul
- **Statut** : ✅ **CORRIGÉ**

---

## 🔧 **Détail de la correction**

### 📁 **Fichier modifié : `tresorerie.php`**

#### **Avant (ligne 179)** :
```php
// Calcul selon votre formule
$solde_fin_mois = $solde_debut_mois + $encaissements_mois + $factures_client_mois + $marge_mois_precedent - $decaissements_mois;
```

#### **Après (ligne 179)** :
```php
// Calcul selon votre formule (CORRIGÉ : ajout des salaires impayés)
$solde_fin_mois = $solde_debut_mois + $encaissements_mois + $factures_client_mois + $marge_mois_precedent - $decaissements_mois - $salaires_impayes_mois;
```

### 📋 **Formule complète corrigée**
```
Solde fin = Solde début 
          + Encaissements effectués 
          + Factures client impayées 
          + Marge mois précédent 
          - Décaissements 
          - Salaires impayés  ← **AJOUTÉ**
```

---

## 🧪 **Vérification de la correction**

### ✅ **Points vérifiés**
1. **Option de configuration** : `SIG_INCLUDE_UNPAID_SALARIES` existe et fonctionne
2. **Récupération des données** : `sig_get_unpaid_salaries_for_month()` fonctionne correctement
3. **Affichage** : Les salaires impayés sont affichés dans la colonne décaissements
4. **Calcul** : Les salaires impayés sont maintenant correctement soustraits du solde

### 📊 **Base de données utilisée**
- **Table** : `llx_salary`
- **Critères** : `paye = 0` (salaires impayés)
- **Date de référence** : `dateep` (date de fin de période)
- **Montant** : `amount` (montant du salaire)

---

## 🎯 **Impact de la correction**

### **Avant la correction**
- ❌ Solde de fin de mois **surévalué**
- ❌ Salaires impayés affichés mais **non déduits**
- ❌ Prévisions de trésorerie **inexactes**

### **Après la correction**
- ✅ Solde de fin de mois **exact**
- ✅ Salaires impayés **correctement déduits**
- ✅ Prévisions de trésorerie **précises**

---

## 📝 **Configuration requise**

Pour que la correction soit active, l'option suivante doit être activée :

**Configuration > Modules > SIG > "Inclure les salaires impayés"**
- **Constante** : `SIG_INCLUDE_UNPAID_SALARIES`
- **Type** : Case à cocher (yesno)
- **Emplacement** : `admin/setup.php`

---

## 🔄 **Migration depuis la version 0.35**

### **Mise à jour automatique**
- ✅ Aucune modification de base de données requise
- ✅ Configuration existante préservée
- ✅ Correction appliquée immédiatement

### **Actions recommandées après mise à jour**
1. Vérifier que l'option "Inclure les salaires impayés" est activée
2. Contrôler les nouveaux calculs de solde de fin de mois
3. Comparer avec les anciens résultats pour valider la correction

---

## 📈 **Fonctionnement technique**

### **Logique conditionnelle**
```php
$salaires_impayes_mois = 0;
$include_unpaid_salaries = getDolGlobalString('SIG_INCLUDE_UNPAID_SALARIES');
if (!empty($include_unpaid_salaries) && $include_unpaid_salaries == '1') {
    $salaires_impayes_mois = sig_get_unpaid_salaries_for_month($db, $year, $month);
}

// Application dans le calcul du solde
$solde_fin_mois = $solde_debut_mois + $encaissements_mois + $factures_client_mois 
                + $marge_mois_precedent - $decaissements_mois - $salaires_impayes_mois;
```

### **Requête SQL utilisée**
```sql
SELECT SUM(amount) as total_amount 
FROM llx_salary 
WHERE paye = 0 
AND dateep >= 'YYYY-MM-01' 
AND dateep <= 'YYYY-MM-31'
```

---

## 🏷️ **Informations de version**

- **Version précédente** : 0.35
- **Version actuelle** : 0.41
- **Date de correction** : Décembre 2024
- **Type de correction** : Critique - Calcul financier
- **Compatibilité** : Rétrocompatible avec les versions précédentes
