# Module SIG (Pilotage) - Version 0.1

Module Dolibarr de pilotage de trésorerie et Solde Intermédiaire de Gestion.

## 🎯 Fonctionnalités

### **Page Chiffre d'Affaires**
- **Résumé annuel** : CA Réalisé et CA Prévu côte à côte
- **Tableau mensuel** : CA Réalisé, CA Prévu et Total par mois
- **Calcul automatique** des totaux avec couleurs distinctives

### **Page Trésorerie**
- **Indicateurs de trésorerie** : CA Encaissé, À Encaisser, Potentiel
- **Évolution mensuelle** : Solde début/fin, encaissements, décaissements
- **Solde bancaire** : Affichage du solde actuel et mouvements du mois
- **Factures impayées** : Liste détaillée avec calcul des retards

### **Configuration**
- **Sélection du compte bancaire** principal pour les analyses
- **Interface d'administration** complète
- **Gestion des droits** utilisateur

## 📋 Installation

1. **Copier** ce dossier dans `htdocs/custom/sig`
2. **Activer** le module dans `Accueil > Configuration > Modules` (SIG)
3. **Configurer** le compte bancaire dans `Pilotage > Configuration`

## 🔧 Navigation

- **Menu principal** : "Pilotage" avec icône tableau de bord
- **Sous-menus** :
  - ChiffreAffairesActuel : Dashboard principal
  - Trésorerie : Analyses bancaires
  - Configuration : Paramètres du module

## ⚠️ Notes Version 0.1

- **Calculs de trésorerie** : En cours d'optimisation
- **Données bancaires** : Nécessite configuration préalable
- **Interface** : Fonctionnelle avec améliorations prévues

---
*Module développé pour Dolibarr 22+* 