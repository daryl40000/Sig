# Changelog - Module SIG

## Version 0.2 (2025-01-XX)

### 🎯 Nouvelles fonctionnalités

#### Configuration avancée
- **Taux de marge théorique** : Configuration d'un taux de marge personnalisable (par défaut 20%)
- **Délai moyen de paiement** : Configuration du délai en jours entre livraison et paiement (par défaut 30 jours)
- Interface de configuration dans Admin → Configuration → Module SIG

#### Trésorerie prévisionnelle
- **Marge prévue avec délai de paiement** : La marge des devis signés est maintenant décalée dans le temps selon le délai configuré
- **Solde fin théorique** : Calcul correct du solde fin en tenant compte des marges prévisionnelles
- **Affichage détaillé** : Séparation claire entre encaissements bancaires et marges prévues

### 🔧 Améliorations techniques

#### Calculs de marge
- Nouvelle fonction `sig_get_expected_margin_for_month()` : Calcule la marge à partir du CA prévu et du taux configuré
- Nouvelle fonction `sig_get_expected_margin_with_delay_for_month()` : Applique le délai de paiement aux marges
- Validation des paramètres pour éviter les erreurs TypeError

#### Logique de trésorerie
- **Solde de début** : Utilise maintenant le solde fin théorique du mois précédent (inclut les marges)
- **Solde fin** : Calculé comme Solde début + Encaissements - Décaissements + Marge avec délai
- **Élimination du double comptage** : La marge n'est comptée qu'une seule fois, au moment du paiement effectif

#### Diagnostics et débogage
- Diagnostics détaillés pour le calcul des marges
- Diagnostics pour le solde de début et solde fin
- Traçabilité complète des calculs dans les commentaires HTML

### 🐛 Corrections de bugs

#### Cohérence des calculs
- **Correction du CA prévu** : Harmonisation entre le résumé et le tableau détaillé
- **Gestion des dates de livraison** : Les devis avec date de livraison sont affectés au bon mois
- **Filtres bancaires** : Correction des filtres d'entité pour les mouvements bancaires

#### Robustesse
- Validation des paramètres dans toutes les fonctions de calcul
- Gestion des cas où les paramètres sont NULL ou invalides
- Protection contre les erreurs de type

### 📊 Interface utilisateur

#### Affichage amélioré
- **Colonne Encaissements** : Affichage informatif de la marge prévue (sans impact sur les calculs)
- **Colonne Solde fin** : Affichage détaillé avec marge avec délai séparée
- **Codes couleur** : Vert pour les encaissements, rouge pour les décaissements
- **Formatage** : Affichage clair des totaux et sous-totaux

#### Configuration
- Interface intuitive pour la configuration des paramètres
- Validation des valeurs saisies (taux entre 0-100%, délai entre 0-365 jours)
- Affichage de la configuration actuelle

### 🔄 Migration depuis la version 0.1

#### Paramètres par défaut
- Taux de marge : 20% (configurable)
- Délai de paiement : 30 jours (configurable)
- Aucune migration de données nécessaire

#### Compatibilité
- Toutes les fonctionnalités de la version 0.1 sont conservées
- Les calculs existants restent valides
- Amélioration progressive sans rupture

---

## Version 0.1 (2025-01-XX)

### 🎯 Fonctionnalités initiales

#### Tableau de bord
- Affichage du CA réalisé et CA prévu
- Résumé annuel avec totaux
- Tableau détaillé par mois

#### Trésorerie
- Affichage des mouvements bancaires
- Calcul des soldes par mois
- Support des comptes bancaires multiples

#### Configuration
- Sélection du compte bancaire principal
- Gestion des entités Dolibarr

#### Base technique
- Architecture modulaire
- Intégration complète avec Dolibarr
- Gestion des droits utilisateur
- Interface multilingue (français)
