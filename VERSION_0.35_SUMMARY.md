# Module SIG - Version 0.35 - Résumé technique

## 🎯 **Nouvelle fonctionnalité : Gestion des salaires impayés**

### 📊 **Fonctionnalité ajoutée**
- **Inclusion des salaires impayés** dans les décaissements prévisionnels de trésorerie
- **Configuration granulaire** via une option dédiée dans la section Trésorerie
- **Affichage détaillé** avec code couleur spécifique dans le tableau de trésorerie

---

## 🔧 **Implémentation technique**

### 📁 **Fichiers modifiés**

#### `tresorerie.php`
- **Nouvelle fonction** : `sig_get_unpaid_salaries_for_month(DoliDB $db, int $year, int $month): float`
- **Intégration** : Appel conditionnel basé sur `SIG_INCLUDE_UNPAID_SALARIES`
- **Affichage** : Code couleur violet (#9C27B0) pour les salaires impayés
- **Calculs** : Prise en compte dans les totaux mensuels et annuels

#### `admin/setup.php`
- **Option de configuration** : "Inclure les salaires impayés"
- **Type** : Case à cocher native Dolibarr (`yesno`)
- **Constante** : `SIG_INCLUDE_UNPAID_SALARIES`

#### `langs/fr_FR/sig.lang`
- **Traductions** : `SigIncludeUnpaidSalaries` et `SigIncludeUnpaidSalariesHelp`

#### `core/modules/modSig.class.php`
- **Version** : Mise à jour de `0.3` à `0.35`

---

## 🗃️ **Base de données**

### Table utilisée : `llx_salary`

```sql
-- Colonnes importantes pour la fonctionnalité
`amount` DOUBLE(24,8)     -- Montant du salaire
`paye` SMALLINT(6)        -- 0 = impayé, 1 = payé
`dateep` DATE             -- Date de fin de période (utilisée pour répartition mensuelle)
`datesp` DATE             -- Date de début de période
`label` VARCHAR(255)      -- Description du salaire
```

### Requête SQL utilisée

```sql
SELECT SUM(amount) as total_amount 
FROM llx_salary 
WHERE paye = 0 
AND dateep >= 'YYYY-MM-01' 
AND dateep <= 'YYYY-MM-31'
```

---

## 🎨 **Interface utilisateur**

### Configuration (admin/setup.php)
```php
// Case à cocher native Dolibarr
<input type="checkbox" name="SIG_INCLUDE_UNPAID_SALARIES" value="1" class="flat">
```

### Affichage dans le tableau de trésorerie
- **Couleur** : Violet (#9C27B0)
- **Position** : Colonne "Décaissements"
- **Format** : `+ Salaires impayés: -1 800,00`
- **Totaux** : Inclus dans les totaux mensuels et annuels

---

## 📈 **Logique métier**

### Critères d'inclusion
1. **Statut** : `paye = 0` (salaire impayé)
2. **Période** : `dateep` dans le mois concerné
3. **Configuration** : Option `SIG_INCLUDE_UNPAID_SALARIES` activée

### Calculs
- **Décaissements** : `décaissements_mois + salaires_impayés_mois`
- **Solde fin** : `solde_début + encaissements - décaissements - salaires_impayés`
- **Totaux annuels** : Somme de tous les mois

---

## 🧪 **Tests et validation**

### Données de test
```sql
-- Salaires impayés (paye = 0)
Septembre 2025: 1800€ (dateep: 2025-09-30)
Octobre 2025: 1800€ (dateep: 2025-10-31)
Novembre 2025: 1800€ (dateep: 2025-11-30)
Décembre 2025: 1800€ (dateep: 2025-12-31)

-- Salaires payés (paye = 1)
Août 2025: 1800€ + 1315.94€ (dateep: 2025-08-31)
```

### Résultats attendus
- **Août 2025** : 0€ (tous payés)
- **Septembre à Décembre 2025** : 1800€ chacun
- **Total annuel** : 7200€

---

## 🚀 **Optimisations apportées**

1. **Requête simple et efficace** : Une seule requête SQL par mois
2. **Filtrage précis** : Utilisation de `paye = 0` et `dateep`
3. **Code propre** : Suppression des diagnostics temporaires
4. **Performance** : Pas de requêtes multiples ou redondantes
5. **Intégration native** : Respect des standards Dolibarr

---

## 📝 **Documentation mise à jour**

- ✅ **CHANGELOG.md** : Ajout de la version 0.35 avec détails
- ✅ **README.md** : Documentation des nouvelles fonctionnalités
- ✅ **VERSION_0.35_SUMMARY.md** : Ce fichier de résumé technique

---

## 🎉 **Conclusion**

La version 0.35 ajoute une fonctionnalité robuste et bien intégrée pour la gestion des salaires impayés dans le pilotage de trésorerie. L'implémentation respecte les standards Dolibarr et offre une expérience utilisateur cohérente avec le reste du module.
