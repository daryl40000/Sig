<?php
/* Copyright (C) 2025
 * Module SIG - Page Trésorerie
 */

require_once __DIR__ . '/../../main.inc.php';

// Accès interdit si le module SIG n'est pas activé
if (empty($conf->global->MAIN_MODULE_SIG)) {
	accessforbidden();
}

// Chargement des droits du module
if (empty($user->rights->sig)) {
	$user->rights->sig = new stdClass();
}
if (!isset($user->rights->sig->read)) {
	$user->rights->sig->read = 1;
}

// Vérification des permissions
if (!$user->rights->sig->read) accessforbidden();

$langs->loadLangs(array('companies', 'bills', 'sig@sig'));

$year = GETPOSTINT('year');
if (empty($year)) $year = (int) dol_print_date(dol_now(), '%Y');

llxHeader('', 'Trésorerie - '.$langs->trans('SigDashboard'));

print load_fiche_titre('Analyse de Trésorerie', '', 'fa-coins');

// Menu de navigation
print '<div class="tabBar" style="margin-bottom: 20px;">';
print '<a class="tabTitle" style="color: #666; background: #fff; padding: 8px 15px; margin-right: 5px; text-decoration: none; border-radius: 3px; border: 1px solid #ddd;" href="index.php?year='.$year.'">🏠 Tableau de bord</a>';
print '<a class="tabTitle" style="color: #666; background: #fff; padding: 8px 15px; margin-right: 5px; text-decoration: none; border-radius: 3px; border: 1px solid #ddd;" href="ca.php?year='.$year.'">📊 Pilotage CA</a>';
print '<a class="tabTitle" style="color: #666; background: #f0f0f0; padding: 8px 15px; margin-right: 5px; text-decoration: none; border-radius: 3px;" href="'.$_SERVER['PHP_SELF'].'?year='.$year.'">💰 Trésorerie</a>';
print '<a class="tabTitle" style="color: #666; background: #fff; padding: 8px 15px; margin-right: 5px; text-decoration: none; border-radius: 3px; border: 1px solid #ddd;" href="sig.php?year='.$year.'">📈 SIG</a>';
print '</div>';

print '<form method="GET" action="'.$_SERVER['PHP_SELF'].'">';
print $langs->trans('Year') . ': ';
print '<input type="number" name="year" value="'.$year.'" min="2000" max="2100" /> ';
print '<input class="button" type="submit" value="'.$langs->trans('Refresh').'" />';
print '</form>';

print '<br />';

// Afficher le compte bancaire configuré
$selected_bank_account = getDolGlobalInt('SIG_BANK_ACCOUNT');
if ($selected_bank_account > 0) {
    // Récupérer les informations du compte sélectionné
    $sql = "SELECT rowid, label, number, bank, currency_code FROM ".MAIN_DB_PREFIX."bank_account";
    $sql .= " WHERE rowid = ".(int)$selected_bank_account;
    $sql .= " AND entity IN (".getEntity('bank_account').")";
    
    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql) > 0) {
        $account = $db->fetch_object($resql);
        print '<div style="background: #e8f4fd; border: 1px solid #2196F3; border-radius: 5px; padding: 10px; margin-bottom: 20px;">';
        print '<p><strong>Compte bancaire configuré :</strong> '.$account->label;
        if ($account->number) {
            print ' ('.$account->number.')';
        }
        if ($account->currency_code) {
            print ' - Devise : '.$account->currency_code;
        }
        print ' <a href="admin/setup.php" style="margin-left: 10px;">[Modifier]</a></p>';
        print '</div>';
        $db->free($resql);
    } else {
        print '<div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 5px; padding: 10px; margin-bottom: 20px;">';
        print '<p><strong>Attention :</strong> Le compte bancaire configuré est introuvable. <a href="admin/setup.php">Vérifier la configuration</a></p>';
        print '</div>';
        $selected_bank_account = 0; // Reset pour éviter les erreurs plus loin
    }
} else {
    print '<div style="background: #f8f9fa; border: 1px solid #6c757d; border-radius: 5px; padding: 10px; margin-bottom: 20px;">';
    print '<p><strong>Configuration :</strong> Analyse sur tous les comptes bancaires. <a href="admin/setup.php">Configurer un compte spécifique</a></p>';
    print '</div>';
}

// SECTION RÉSUMÉ : Indicateurs de trésorerie supprimés (disponibles sur leur page dédiée)

// SECTION: Évolution de la Trésorerie par Mois (seulement si un compte est configuré)
if ($selected_bank_account > 0) {
    print load_fiche_titre('Évolution de la Trésorerie par Mois', '', 'fa-chart-line');
    
    // GRAPHIQUE : Évolution des soldes de fin de mois
    print '<div style="margin-bottom: 30px; height: 400px;">';
    print '<canvas id="tresorerieChart"></canvas>';
    print '</div>';

    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<th>Mois</th>';
    print '<th style="text-align: right;">Solde Début</th>';
    print '<th style="text-align: right;">Encaissements</th>';
    print '<th style="text-align: right;">Décaissements</th>';
    print '<th style="text-align: right;">Solde Fin</th>';
    print '<th style="text-align: right;">Variation</th>';
    print '</tr>';

    $total_encaissements = 0;
    $total_decaissements = 0;
    
    // Données pour le graphique
    $mois_labels = [];
    $soldes_data = [];
    
    // Nouvelle approche : calculer le solde à la fin de chaque mois directement
    $solde_fin_precedent = 0;
    
    for ($month = 1; $month <= 12; $month++) {
        // Déterminer si c'est un mois écoulé ou en cours/futur
        $current_date = new DateTime();
        $current_year = (int) $current_date->format('Y');
        $current_month = (int) $current_date->format('n');
        $is_past_month = ($year < $current_year) || ($year == $current_year && $month < $current_month);
        
        if ($is_past_month) {
            // ========== MOIS ÉCOULÉS : DONNÉES EXACTES DU MODULE BANQUE ==========
            
            // Solde début = solde réel à la fin du mois précédent
        if ($month == 1) {
                $solde_debut_mois = sig_get_bank_balance_at_date($db, $selected_bank_account, $year - 1, 12, 31);
        } else {
                $solde_debut_mois = sig_get_bank_balance_at_date($db, $selected_bank_account, $year, $month - 1, 31);
            }
            
            // Mouvements réels du mois depuis le module banque
            $mouvements_reels = sig_get_bank_movements_real_for_month($db, $selected_bank_account, $year, $month);
            $encaissements_mois = $mouvements_reels['encaissements'];
            $decaissements_mois = abs($mouvements_reels['decaissements']);
            
            
            // Solde fin = solde réel à la fin du mois
            $solde_fin_mois = sig_get_bank_balance_at_date($db, $selected_bank_account, $year, $month, 31);
            
            // Aucun élément prévisionnel pour les mois écoulés
            $marge_avec_delai = 0;
            $factures_client_mois = 0;
            $salaires_impayes_mois = 0;
            
        } else {
            // ========== MOIS ACTUEL + FUTURS : TRÉSORERIE PRÉVISIONNELLE ==========
            
            // Solde début
            if ($month == 1) {
                $solde_debut_mois = sig_get_bank_balance_at_date($db, $selected_bank_account, $year - 1, 12, 31);
            } else {
                $solde_debut_mois = $solde_fin_precedent;
            }
            
            // Mouvements bancaires + éléments prévisionnels
            $mouvements_details = sig_get_bank_movements_details_for_month($db, $selected_bank_account, $year, $month);
            $encaissements_mois = $mouvements_details['encaissements'];
            $decaissements_mois = abs($mouvements_details['decaissements']);
            
            // Éléments prévisionnels
            $marge_avec_delai = sig_get_expected_margin_with_delay_for_month($db, $year, $month);
            
            $factures_client_mois = 0;
            $include_customer_invoices = getDolGlobalString('SIG_INCLUDE_CUSTOMER_INVOICES');
            if (!empty($include_customer_invoices) && $include_customer_invoices == '1') {
                $factures_client_mois = sig_get_customer_invoices_for_month($db, $year, $month);
            }
            
            $factures_template_mois = 0;
            // Les factures modèle ne s'appliquent qu'aux mois actuels et futurs, pas aux mois passés
            $include_template_invoices = getDolGlobalString('SIG_INCLUDE_CUSTOMER_TEMPLATE_INVOICES');
            if (!empty($include_template_invoices) && $include_template_invoices == '1' && !$is_past_month) {
                $factures_template_mois = sig_get_customer_template_invoices_for_month($db, $year, $month);
            }
            
            $salaires_impayes_mois = 0;
            $include_unpaid_salaries = getDolGlobalString('SIG_INCLUDE_UNPAID_SALARIES');
            if (!empty($include_unpaid_salaries) && $include_unpaid_salaries == '1') {
                $salaires_impayes_mois = sig_get_unpaid_salaries_for_month($db, $year, $month);
            }
            
            // Calcul prévisionnel du solde fin selon la formule demandée
            // Solde début + Encaissements effectués + Factures client impayées + Marge mois précédent - Ensemble décaissements - Salaires impayés
            
            // Marge du mois précédent (si applicable)
            $marge_mois_precedent = 0;
            if ($month > 1) {
                $marge_mois_precedent = sig_get_expected_margin_with_delay_for_month($db, $year, $month - 1);
            } elseif ($year > 2024) {
                $marge_mois_precedent = sig_get_expected_margin_with_delay_for_month($db, $year - 1, 12);
            }
            
            // Calcul selon votre formule (CORRIGÉ : ajout des salaires impayés + factures modèle)
            $solde_fin_mois = $solde_debut_mois + $encaissements_mois + $factures_client_mois + $factures_template_mois + $marge_mois_precedent - $decaissements_mois - $salaires_impayes_mois;
            
        }
    
        
        // Calculer la variation du mois
        $variation_mois = $solde_fin_mois - $solde_debut_mois;
        
        // Collecter les données pour le graphique
        $mois_labels[] = dol_print_date(dol_mktime(0, 0, 0, $month, 1, $year), '%b %Y');
        $soldes_data[] = round($solde_fin_mois, 2);
        
        // Marquer l'index du mois en cours pour les projections
        if ($year == $current_year && $month == $current_month) {
            $current_month_index = count($soldes_data) - 1;
        }
        
        $total_encaissements += $encaissements_mois;
        $total_decaissements += $decaissements_mois;
        $total_salaires_impayes_annee += $salaires_impayes_mois;
        
        // Style de la ligne selon la variation
        $row_style = '';
        if ($variation_mois > 0) {
            $row_style = 'background-color: #e8f5e8;'; // Vert clair pour positif
        } elseif ($variation_mois < 0) {
            $row_style = 'background-color: #ffebee;'; // Rouge clair pour négatif
        }
        
        print '<tr class="oddeven" style="'.$row_style.'">';
        print '<td><strong>'.dol_print_date(dol_mktime(0, 0, 0, $month, 1, $year), '%B %Y').'</strong></td>';
        print '<td style="text-align: right;">'.price($solde_debut_mois).'</td>';
        // Calculer la marge prévue pour l'affichage (seulement si l'option est activée)
        $marge_prevue_affichage = 0;
        $include_signed_quotes = getDolGlobalString('SIG_INCLUDE_SIGNED_QUOTES');
        if ($year >= $current_year && $month >= $current_month && !empty($include_signed_quotes) && $include_signed_quotes == '1') {
            $marge_prevue_affichage = sig_get_expected_margin_for_month($db, $year, $month);
        }
        
        // Calculer les factures client impayées pour l'affichage (seulement si l'option est activée)
        $factures_client_affichage = 0;
        $include_customer_invoices = getDolGlobalString('SIG_INCLUDE_CUSTOMER_INVOICES');
        if (!empty($include_customer_invoices) && $include_customer_invoices == '1') {
            $factures_client_affichage = sig_get_customer_invoices_for_month($db, $year, $month);
        }
        
        // Calculer les factures modèle client pour l'affichage (seulement si l'option est activée et mois non passé)
        $factures_template_affichage = 0;
        $include_template_invoices = getDolGlobalString('SIG_INCLUDE_CUSTOMER_TEMPLATE_INVOICES');
        if (!empty($include_template_invoices) && $include_template_invoices == '1' && !$is_past_month) {
            $factures_template_affichage = sig_get_customer_template_invoices_for_month($db, $year, $month);
        }
        
        // Afficher les encaissements avec indication des montants à venir
        // Pour les mois écoulés : utiliser les encaissements réels
        // Pour les mois futurs : utiliser les encaissements prévisionnels
        if ($is_past_month) {
            $encaissements_bruts = $encaissements_mois; // Données réelles du module banque
        } else {
            $encaissements_bruts = $mouvements_details['encaissements']; // Données prévisionnelles
        }
        $total_encaissements_prevus = $encaissements_bruts + $marge_prevue_affichage + $factures_client_affichage + $factures_template_affichage;
        
        if ($marge_prevue_affichage > 0 || $factures_client_affichage > 0 || $factures_template_affichage > 0) {
            print '<td style="text-align: right; color: #2E7D32;">'.($encaissements_bruts > 0 ? '+'.price($encaissements_bruts) : price($encaissements_bruts));
            
            if ($marge_prevue_affichage > 0) {
            print '<br><small style="color: #4CAF50;">+ Marge prévue: +'.price($marge_prevue_affichage).'</small>';
            }
            
            if ($factures_client_affichage > 0) {
                print '<br><small style="color: #2196F3;">+ Factures client: +'.price($factures_client_affichage).'</small>';
            }
            
            if ($factures_template_affichage > 0) {
                print '<br><small style="color: #FF9800;">+ Factures modèle: +'.price($factures_template_affichage).'</small>';
            }
            
            print '<br><strong>Total: +'.price($total_encaissements_prevus).'</strong></td>';
        } else {
            print '<td style="text-align: right; color: #2E7D32;">'.($encaissements_bruts > 0 ? '+'.price($encaissements_bruts) : price($encaissements_bruts)).'</td>';
        }
        
        // Afficher les décaissements avec indication des factures fournisseur, charges et salaires
        if ($is_past_month) {
            // Mois écoulés : pas de factures fournisseur ni charges prévisionnelles
            $factures_fournisseur = 0;
            $charges_fiscales_sociales = 0;
            $decaissements_bancaires = $decaissements_mois; // Tous les décaissements sont bancaires
        } else {
            // Mois futurs : utiliser les données prévisionnelles
            $factures_fournisseur = $mouvements_details['factures_fournisseur'];
            $charges_fiscales_sociales = $mouvements_details['charges_fiscales_sociales'];
            $decaissements_bancaires = $decaissements_mois - $factures_fournisseur - $charges_fiscales_sociales;
        }
        $total_decaissements_prevus = $decaissements_mois + $salaires_impayes_mois;
        
        
        if ($factures_fournisseur > 0 || $charges_fiscales_sociales > 0 || $salaires_impayes_mois > 0) {
            print '<td style="text-align: right; color: #C62828;">'.($decaissements_bancaires > 0 ? '-'.price($decaissements_bancaires) : price($decaissements_bancaires));
            
            if ($factures_fournisseur > 0) {
                print '<br><small style="color: #FF5722;">+ Factures fournisseur: -'.price($factures_fournisseur).'</small>';
            }
            
            if ($charges_fiscales_sociales > 0) {
                print '<br><small style="color: #E91E63;">+ Charges fiscales/sociales: -'.price($charges_fiscales_sociales).'</small>';
            }
            
            if ($salaires_impayes_mois > 0) {
                print '<br><small style="color: #9C27B0;">+ Salaires impayés: -'.price($salaires_impayes_mois).'</small>';
            }
            
            print '<br><strong>Total: -'.price($total_decaissements_prevus).'</strong></td>';
        } else {
            print '<td style="text-align: right; color: #C62828;">'.($decaissements_mois > 0 ? '-'.price($decaissements_mois) : price($decaissements_mois)).'</td>';
        }
        
        // Afficher le solde fin
        if ($is_past_month) {
            // Mois écoulés : solde réel simple
            print '<td style="text-align: right; font-weight: bold;">'.price($solde_fin_mois).'</td>';
        } else {
            // Mois actuels/futurs : solde prévisionnel simple
            print '<td style="text-align: right; font-weight: bold;">'.price($solde_fin_mois).'</td>';
        }
        
        // Affichage de la variation avec couleur
        $variation_color = $variation_mois >= 0 ? '#2E7D32' : '#C62828';
        $variation_sign = $variation_mois > 0 ? '+' : '';
        print '<td style="text-align: right; font-weight: bold; color: '.$variation_color.';">'.$variation_sign.price($variation_mois).'</td>';
        
        print '</tr>';
        
        // Sauvegarder le solde fin théorique pour le mois suivant
        $solde_fin_precedent = $solde_fin_mois;
    }

    // Calculer les vrais totaux des colonnes
    // Solde de début = solde de début de janvier
    $solde_debut_annee = sig_get_bank_balance_at_date($db, $selected_bank_account, $year - 1, 12, 31);
    $marge_decembre = sig_get_expected_margin_with_delay_for_month($db, $year - 1, 12);
    $factures_client_decembre = 0;
    $include_customer_invoices = getDolGlobalString('SIG_INCLUDE_CUSTOMER_INVOICES');
    if (!empty($include_customer_invoices) && $include_customer_invoices == '1') {
        $factures_client_decembre = sig_get_customer_invoices_for_month($db, $year - 1, 12);
    }
    $salaires_impayes_decembre = 0;
    $include_unpaid_salaries = getDolGlobalString('SIG_INCLUDE_UNPAID_SALARIES');
    // Calculé dans la boucle principale pour éviter les requêtes multiples
    $total_solde_debut = $solde_debut_annee + $marge_decembre + $factures_client_decembre - $salaires_impayes_decembre;
    
    // Totaux des mouvements de l'année
    $total_encaissements_reel = 0;
    $total_decaissements_reel = 0;
    $total_marge_avec_delai = 0;
    $total_factures_client_annee = 0;
    $total_salaires_impayes_annee = 0;
    
    for ($m = 1; $m <= 12; $m++) {
        $mouvements_mois = sig_get_bank_movements_details_for_month($db, $selected_bank_account, $year, $m);
        $total_encaissements_reel += $mouvements_mois['encaissements'];
        $total_decaissements_reel += abs($mouvements_mois['decaissements']);
        $total_marge_avec_delai += sig_get_expected_margin_with_delay_for_month($db, $year, $m);
        $include_customer_invoices = getDolGlobalString('SIG_INCLUDE_CUSTOMER_INVOICES');
        if (!empty($include_customer_invoices) && $include_customer_invoices == '1') {
            $total_factures_client_annee += sig_get_customer_invoices_for_month($db, $year, $m);
        }
        // Calculé dans la boucle principale pour éviter les requêtes multiples
    }
    
    // Solde fin = solde de fin de décembre (dernier mois calculé)
    $total_solde_fin = $solde_fin_precedent;
    
    // Variation totale = solde fin - solde début
    $total_variation = $total_solde_fin - $total_solde_debut;
    
    // Calculer le total de la marge prévue pour l'affichage
    $total_marge_prevue = 0;
    $include_signed_quotes = getDolGlobalString('SIG_INCLUDE_SIGNED_QUOTES');
    if (!empty($include_signed_quotes) && $include_signed_quotes == '1') {
    for ($m = $current_month; $m <= 12; $m++) {
        if ($year >= $current_year) {
            $total_marge_prevue += sig_get_expected_margin_for_month($db, $year, $m);
            }
        }
    }
    
    // Calculer le total des factures client pour l'affichage
    $total_factures_client = 0;
    $include_customer_invoices = getDolGlobalString('SIG_INCLUDE_CUSTOMER_INVOICES');
    if (!empty($include_customer_invoices) && $include_customer_invoices == '1') {
        for ($m = 1; $m <= 12; $m++) {
            $total_factures_client += sig_get_customer_invoices_for_month($db, $year, $m);
        }
    }
    
    // Calculer le total des factures modèle client pour l'affichage (seulement à partir du mois actuel)
    $total_factures_template = 0;
    $include_template_invoices = getDolGlobalString('SIG_INCLUDE_CUSTOMER_TEMPLATE_INVOICES');
    if (!empty($include_template_invoices) && $include_template_invoices == '1') {
        for ($m = 1; $m <= 12; $m++) {
            // Déterminer si c'est un mois passé
            $is_past_month_total = ($year < $current_year) || ($year == $current_year && $m < $current_month);
            
            // Ne prendre en compte que les mois actuels et futurs
            if (!$is_past_month_total) {
                $total_factures_template += sig_get_customer_template_invoices_for_month($db, $year, $m);
            }
        }
    }
    
    // Calculé dans la boucle principale pour éviter les requêtes multiples
    
    print '<tr class="liste_total">';
    print '<td><strong>TOTAL ANNÉE '.$year.'</strong></td>';
    print '<td style="text-align: right; font-weight: bold;">'.price($total_solde_debut).'</td>';
    
    $total_encaissements_prevus = $total_encaissements_reel + $total_marge_prevue + $total_factures_client + $total_factures_template;
    $total_decaissements_prevus = $total_decaissements_reel + $total_salaires_impayes_annee;
    
    if ($total_marge_prevue > 0 || $total_factures_client > 0 || $total_factures_template > 0) {
        print '<td style="text-align: right; font-weight: bold; color: #2E7D32;">+'.price($total_encaissements_reel);
        
        if ($total_marge_prevue > 0) {
        print '<br><small style="color: #4CAF50;">+ Marge prévue: +'.price($total_marge_prevue).'</small>';
        }
        
        if ($total_factures_client > 0) {
            print '<br><small style="color: #2196F3;">+ Factures client: +'.price($total_factures_client).'</small>';
        }
        
        if ($total_factures_template > 0) {
            print '<br><small style="color: #FF9800;">+ Factures modèle: +'.price($total_factures_template).'</small>';
        }
        
        print '<br><strong>Total: +'.price($total_encaissements_prevus).'</strong></td>';
    } else {
        print '<td style="text-align: right; font-weight: bold; color: #2E7D32;">+'.price($total_encaissements_reel).'</td>';
    }
    
    // Afficher les totaux des décaissements
    if ($total_salaires_impayes_annee > 0) {
        print '<td style="text-align: right; font-weight: bold; color: #C62828;">-'.price($total_decaissements_reel);
        print '<br><small style="color: #9C27B0;">+ Salaires impayés: -'.price($total_salaires_impayes_annee).'</small>';
        print '<br><strong>Total: -'.price($total_decaissements_prevus).'</strong></td>';
    } else {
    print '<td style="text-align: right; font-weight: bold; color: #C62828;">-'.price($total_decaissements_reel).'</td>';
    }
    
    print '<td style="text-align: right; font-weight: bold;">'.price($total_solde_fin).'</td>';
    $variation_color = $total_variation >= 0 ? '#2E7D32' : '#C62828';
    $variation_sign = $total_variation > 0 ? '+' : '';
    print '<td style="text-align: right; font-weight: bold; color: '.$variation_color.';">'.$variation_sign.price($total_variation).'</td>';
    print '</tr>';

    print '</table>';
    print '<br>';
    
    // Script JavaScript pour le graphique
    ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    const ctx = document.getElementById('tresorerieChart').getContext('2d');
    
    // Préparer les données
    const soldesData = <?php echo json_encode($soldes_data); ?>;
    const labels = <?php echo json_encode($mois_labels); ?>;
    const currentMonthIndex = <?php echo isset($current_month_index) ? $current_month_index : -1; ?>;
    const currentYear = <?php echo $current_year; ?>;
    const selectedYear = <?php echo $year; ?>;
    
    // Créer des datasets pour les zones avec une logique simplifiée
    const zeroLine = new Array(soldesData.length).fill(0);
    
    // Créer les données de projection (seulement si on est dans l'année en cours)
    let projectionOptimiste = [];
    let projectionPessimiste = [];
    
    if (selectedYear >= currentYear && currentMonthIndex >= 0) {
        // Partir du solde du mois en cours
        const baseSolde = soldesData[currentMonthIndex];
        
        // Variables pour stocker les soldes calculés mois par mois
        let soldeOptimistePrecedent = baseSolde;
        let soldePessimistePrecedent = baseSolde;
        
        // Créer les projections à partir du mois en cours
        for (let i = 0; i < soldesData.length; i++) {
            if (i < currentMonthIndex) {
                // Avant le mois en cours : pas de projection
                projectionOptimiste.push(null);
                projectionPessimiste.push(null);
            } else if (i === currentMonthIndex) {
                // Mois en cours : même valeur pour les trois courbes (point de départ)
                projectionOptimiste.push(soldesData[i]);
                projectionPessimiste.push(soldesData[i]);
            } else {
                // Après le mois en cours : projections avec divergence cumulative
                const moisDepuisCurrent = i - currentMonthIndex;
                
                // Calcul de l'incertitude : valeur configurable qui augmente linéairement
                // Mois 1: ±valeur€, Mois 2: ±(valeur*2)€, Mois 3: ±(valeur*3)€, etc.
                const incertitudeBase = <?php echo getDolGlobalInt('SIG_PROJECTION_UNCERTAINTY', 1000); ?>; // Valeur de base configurée
                const incertitudeCeMois = incertitudeBase * moisDepuisCurrent;
                
                // Prendre le solde réel de ce mois comme base
                const soldeReelCeMois = soldesData[i];
                
                // Calculer les nouveaux soldes : solde réel ± incertitude cumulative
                soldeOptimistePrecedent = soldeReelCeMois + incertitudeCeMois;
                soldePessimistePrecedent = soldeReelCeMois - incertitudeCeMois;
                
                // Ajouter aux données de projection
                projectionOptimiste.push(soldeOptimistePrecedent);
                projectionPessimiste.push(soldePessimistePrecedent);
            }
        }
    }
    
    const tresorerieChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                // Dataset principal avec remplissage conditionnel
                {
                    label: 'Solde de fin de mois (€)',
                    data: soldesData,
                    borderColor: '#2196F3', // Bleu comme avant
                    backgroundColor: function(context) {
                        const chart = context.chart;
                        const {ctx, chartArea} = chart;
                        if (!chartArea) return null;
                        
                        // Créer un gradient vertical
                        const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                        
                        // Trouver la position de zéro sur l'axe Y
                        const yScale = chart.scales.y;
                        const zeroPixel = yScale.getPixelForValue(0);
                        const topPixel = chartArea.top;
                        const bottomPixel = chartArea.bottom;
                        
                        // Calculer les positions relatives pour le gradient
                        const zeroPosition = (zeroPixel - topPixel) / (bottomPixel - topPixel);
                        
                        // Appliquer les couleurs selon la position par rapport à zéro
                        gradient.addColorStop(0, 'rgba(76, 175, 80, 0.3)'); // Vert en haut (positif)
                        gradient.addColorStop(Math.min(Math.max(zeroPosition, 0), 1), 'rgba(76, 175, 80, 0.1)'); // Transition à zéro
                        gradient.addColorStop(Math.min(Math.max(zeroPosition, 0), 1), 'rgba(244, 67, 54, 0.1)'); // Transition à zéro
                        gradient.addColorStop(1, 'rgba(244, 67, 54, 0.3)'); // Rouge en bas (négatif)
                        
                        return gradient;
                    },
                    borderWidth: 3,
                    fill: 'origin', // Remplir jusqu'à zéro
                    tension: 0.4,
                    pointBackgroundColor: '#2196F3',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    order: 1 // Au premier plan
                },
                // Dataset pour la projection optimiste (ligne pointillée verte)
                {
                    label: 'Projection optimiste',
                    data: projectionOptimiste,
                    borderColor: '#4CAF50',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    borderDash: [10, 5], // Ligne pointillée
                    fill: false,
                    tension: 0.4,
                    pointBackgroundColor: '#4CAF50',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 1,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    order: 3,
                    hidden: selectedYear < currentYear // Masquer si année passée
                },
                // Dataset pour la projection pessimiste (ligne pointillée rouge)
                {
                    label: 'Projection pessimiste',
                    data: projectionPessimiste,
                    borderColor: '#F44336',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    borderDash: [10, 5], // Ligne pointillée
                    fill: false,
                    tension: 0.4,
                    pointBackgroundColor: '#F44336',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 1,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    order: 3,
                    hidden: selectedYear < currentYear // Masquer si année passée
                },
                // Zone d'incertitude entre les projections
                {
                    label: 'Zone d\'incertitude',
                    data: projectionOptimiste,
                    borderColor: 'transparent',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)', // Jaune très transparent
                    fill: {
                        target: 4, // Remplir jusqu'au dataset pessimiste (index 4)
                        above: 'rgba(255, 193, 7, 0.1)'
                    },
                    pointRadius: 0,
                    pointHoverRadius: 0,
                    tension: 0.4,
                    order: 4,
                    hidden: selectedYear < currentYear // Masquer si année passée
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
                            plugins: {
                    title: {
                        display: true,
                        text: 'Évolution des Soldes de Fin de Mois - <?php echo $year; ?>',
                        font: {
                            size: 16,
                            weight: 'bold'
                        }
                    },
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            filter: function(item, chart) {
                                // Afficher toutes les lignes sauf les zones de remplissage et la zone d'incertitude
                                return !item.text.includes('Zone') && !item.text.includes('incertitude');
                            }
                        }
                    },
                    tooltip: {
                        filter: function(tooltipItem) {
                            // Afficher les tooltips pour toutes les lignes sauf les zones de remplissage
                            return tooltipItem.datasetIndex >= 2;
                        },
                        callbacks: {
                            title: function(context) {
                                return context[0].label;
                            },
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('fr-FR', {
                                        style: 'currency',
                                        currency: 'EUR',
                                        minimumFractionDigits: 0,
                                        maximumFractionDigits: 0
                                    }).format(context.parsed.y);
                                    
                                    // Ajouter une indication pour les projections
                                    if (context.dataset.label.includes('Projection')) {
                                        label += ' (estimation)';
                                    }
                                }
                                return label;
                            }
                        }
                    }
                },
            scales: {
                y: {
                    beginAtZero: false,
                    ticks: {
                        callback: function(value, index, values) {
                            return new Intl.NumberFormat('fr-FR', {
                                style: 'currency',
                                currency: 'EUR',
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            }).format(value);
                        },
                        color: function(context) {
                            return context.tick.value === 0 ? '#666666' : '#666666';
                        }
                    },
                    grid: {
                        color: function(context) {
                            return context.tick.value === 0 ? '#666666' : 'rgba(0, 0, 0, 0.1)';
                        },
                        lineWidth: function(context) {
                            return context.tick.value === 0 ? 2 : 1;
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            elements: {
                point: {
                    hoverBackgroundColor: '#1976D2'
                }
            },
            plugins: {
                // Ajouter une ligne verticale au mois en cours
                afterDraw: function(chart) {
                    if (currentMonthIndex >= 0 && selectedYear >= currentYear) {
                        const ctx = chart.ctx;
                        const xAxis = chart.scales.x;
                        const yAxis = chart.scales.y;
                        
                        // Position x du mois en cours
                        const xPos = xAxis.getPixelForValue(currentMonthIndex);
                        
                        ctx.save();
                        ctx.strokeStyle = '#FF9800'; // Orange
                        ctx.lineWidth = 2;
                        ctx.setLineDash([5, 5]);
                        ctx.beginPath();
                        ctx.moveTo(xPos, yAxis.top);
                        ctx.lineTo(xPos, yAxis.bottom);
                        ctx.stroke();
                        
                        // Ajouter un label
                        ctx.fillStyle = '#FF9800';
                        ctx.font = '12px Arial';
                        ctx.textAlign = 'center';
                        ctx.fillText('Mois actuel', xPos, yAxis.top - 5);
                        ctx.restore();
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
    </script>
    <?php
} else {
    // Message si aucun compte n'est configuré
    print load_fiche_titre('Évolution de la Trésorerie par Mois', '', 'fa-chart-line');
    print '<div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 5px; padding: 15px; margin-bottom: 20px;">';
    print '<p><strong>Information :</strong> Pour afficher l\'évolution de la trésorerie par mois, veuillez <a href="admin/setup.php">configurer un compte bancaire principal</a>.</p>';
    print '</div>';
}

// Section "Solde Bancaire" supprimée (informations redondantes avec le tableau principal)


llxFooter();
$db->close();


/**
 * Retourne le CA HT prévu du mois à partir des devis signés.
 * Les devis signés correspondent au statut 2 dans Dolibarr.
 * Utilise la date de livraison si renseignée, sinon le mois courant.
 *
 * @param DoliDB $db
 * @param int $year
 * @param int $month
 * @return float
 */
function sig_get_expected_turnover_for_month(DoliDB $db, int $year, int $month): float
{
	global $conf;

	$sql = 'SELECT SUM(p.total_ht) as total_ht';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'propal as p';
	$sql .= ' WHERE p.entity IN ('.getEntity('propal', 1).')';
	$sql .= ' AND p.fk_statut = 2'; // Statut 2 = Devis signé/accepté
	$sql .= ' AND (';
	
	// Condition 1: Si date_livraison est renseignée, utiliser cette date
	$sql .= ' (p.date_livraison IS NOT NULL AND YEAR(p.date_livraison) = '.(int)$year.' AND MONTH(p.date_livraison) = '.(int)$month.')';
	
	// Condition 2: Si date_livraison n'est pas renseignée, affecter au mois courant
	$current_month = (int) date('n'); // Mois actuel (1-12)
	$current_year = (int) date('Y');  // Année actuelle
	$sql .= ' OR (p.date_livraison IS NULL AND '.$current_month.' = '.(int)$month.' AND '.$current_year.' = '.(int)$year.')';
	
	$sql .= ' )';

	$total = 0.0;
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj) {
			if (!empty($obj->total_ht)) $total = (float) $obj->total_ht;
		}
		$db->free($resql);
	}
	return (float) $total;
}

/**
 * Retourne le montant des factures payées pour un mois donné
 */
function sig_get_paid_invoices_for_month(DoliDB $db, int $year, int $month): float
{
	global $conf;

	$date_start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
	$last_day = date('t', mktime(0, 0, 0, $month, 1, $year));
	$date_end = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $last_day);

	$sql = 'SELECT SUM(f.total_ht) as total_ht';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'facture as f';
	$sql .= ' WHERE f.entity IN ('.getEntity('invoice', 1).')';
	$sql .= ' AND f.fk_statut = 2'; // Statut 2 = Payée
	$sql .= ' AND f.type IN (0, 1)';
	$sql .= " AND f.datef >= '".$db->escape($date_start)."'";
	$sql .= " AND f.datef <= '".$db->escape($date_end)."'";

	$total = 0.0;
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj && !empty($obj->total_ht)) $total = (float) $obj->total_ht;
		$db->free($resql);
	}
	return (float) $total;
}


/**
 * Retourne le montant des factures fournisseur impayées pour un mois donné
 * Basé sur la date de paiement prévue (date_lim_reglement)
 */
function sig_get_supplier_invoices_for_month(DoliDB $db, int $year, int $month): float
{
	global $conf;

	// Déterminer si on est dans le mois actuel ou futur
	$current_date = new DateTime();
	$current_year = (int) $current_date->format('Y');
	$current_month = (int) $current_date->format('n');
	$is_current_month = ($year == $current_year && $month == $current_month);
	$is_future_month = ($year > $current_year) || ($year == $current_year && $month > $current_month);

	$sql = 'SELECT SUM(f.total_ht) as total_ht';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as f';
	$sql .= ' WHERE f.entity IN ('.getEntity('supplier_invoice', 1).')';
	$sql .= ' AND f.fk_statut = 1'; // Statut 1 = Validée mais non payée

	if ($is_current_month) {
		// Mois actuel : TOUTES les factures fournisseur en retard + celles qui arrivent à échéance ce mois-ci
		$last_day = date('t', mktime(0, 0, 0, $month, 1, $year));
		$date_end = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $last_day);
		$sql .= " AND f.date_lim_reglement <= '".$db->escape($date_end)."'";
	} elseif ($is_future_month) {
		// Mois futur : seulement les factures avec date limite dans ce mois
		$date_start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
		$last_day = date('t', mktime(0, 0, 0, $month, 1, $year));
		$date_end = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $last_day);
	$sql .= " AND f.date_lim_reglement >= '".$db->escape($date_start)."'";
	$sql .= " AND f.date_lim_reglement <= '".$db->escape($date_end)."'";
	} else {
		// Mois passé : aucune facture prévisionnelle
		return 0.0;
	}

	$total = 0.0;
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj && !empty($obj->total_ht)) $total = (float) $obj->total_ht;
		$db->free($resql);
	}
	return (float) $total;
}

/**
 * Retourne le montant des charges fiscales et sociales impayées pour un mois donné
 * Basé sur la date de paiement prévue (date_lim_reglement)
 */
function sig_get_tax_social_charges_for_month(DoliDB $db, int $year, int $month): float
{
	global $conf;

	// Déterminer si on est dans le mois actuel ou futur
	$current_date = new DateTime();
	$current_year = (int) $current_date->format('Y');
	$current_month = (int) $current_date->format('n');
	$is_current_month = ($year == $current_year && $month == $current_month);
	$is_future_month = ($year > $current_year) || ($year == $current_year && $month > $current_month);

	// Si mois passé, aucune charge prévisionnelle
	if (!$is_current_month && !$is_future_month) {
		return 0.0;
	}

	$total = 0.0;

	// 1. Charges sociales (URSSAF, retraite, etc.)
	$sql_social = 'SELECT SUM(f.total_ht) as total_ht';
	$sql_social .= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as f';
	$sql_social .= ' WHERE f.entity IN ('.getEntity('supplier_invoice', 1).')';
	$sql_social .= ' AND f.fk_statut = 1'; // Statut 1 = Validée mais non payée

	if ($is_current_month) {
		// Mois actuel : TOUTES les charges en retard + celles qui arrivent à échéance ce mois-ci
		$last_day = date('t', mktime(0, 0, 0, $month, 1, $year));
		$date_end = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $last_day);
		$sql_social .= " AND f.date_lim_reglement <= '".$db->escape($date_end)."'";
	} else {
		// Mois futur : seulement les charges avec date limite dans ce mois
		$date_start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
		$last_day = date('t', mktime(0, 0, 0, $month, 1, $year));
		$date_end = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $last_day);
	$sql_social .= " AND f.date_lim_reglement >= '".$db->escape($date_start)."'";
	$sql_social .= " AND f.date_lim_reglement <= '".$db->escape($date_end)."'";
	}

	$sql_social .= " AND (f.ref LIKE '%URSSAF%' OR f.ref LIKE '%RETRAITE%' OR f.ref LIKE '%SECU%' OR f.ref LIKE '%CHOMAGE%' OR f.ref LIKE '%PREVOYANCE%' OR f.ref LIKE '%MUTUELLE%')";

	$resql = $db->query($sql_social);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj && !empty($obj->total_ht)) $total += (float) $obj->total_ht;
		$db->free($resql);
	}

	// 2. Charges fiscales (TVA, IS, etc.)
	$sql_tax = 'SELECT SUM(f.total_ht) as total_ht';
	$sql_tax .= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as f';
	$sql_tax .= ' WHERE f.entity IN ('.getEntity('supplier_invoice', 1).')';
	$sql_tax .= ' AND f.fk_statut = 1'; // Statut 1 = Validée mais non payée
	$sql_tax .= " AND f.date_lim_reglement >= '".$db->escape($date_start)."'";
	$sql_tax .= " AND f.date_lim_reglement <= '".$db->escape($date_end)."'";
	$sql_tax .= " AND (f.ref LIKE '%TVA%' OR f.ref LIKE '%IS%' OR f.ref LIKE '%IMPOT%' OR f.ref LIKE '%FISCAL%' OR f.ref LIKE '%DGFIP%')";

	$resql = $db->query($sql_tax);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj && !empty($obj->total_ht)) $total += (float) $obj->total_ht;
		$db->free($resql);
	}

	return (float) $total;
}

/**
 * Retourne le montant des charges sociales du module Sociales pour un mois donné
 * Basé sur la date d'échéance des charges
 */
function sig_get_sociales_charges_for_month(DoliDB $db, int $year, int $month): float
{
	global $conf;

	$date_start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
	$last_day = date('t', mktime(0, 0, 0, $month, 1, $year));
	$date_end = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $last_day);

	$total = 0.0;

	// Essayer différentes tables possibles pour les charges sociales
	$possible_tables = array(
		'sociales_charges',
		'sociales_contributions',
		'sociales_charges_sociales',
		'sociales_contributions_sociales',
		'sociales_charges_urssaf',
		'sociales_contributions_urssaf',
		'salaries_charges',
		'salaries_contributions',
		'charges_sociales',
		'contributions_sociales',
		'urssaf_charges',
		'urssaf_contributions',
		'payroll_charges',
		'payroll_contributions'
	);

	foreach ($possible_tables as $table) {
		// Vérifier si la table existe
		$sql_check = "SHOW TABLES LIKE '".MAIN_DB_PREFIX.$table."'";
		$resql_check = $db->query($sql_check);
		if ($resql_check && $db->num_rows($resql_check) > 0) {
			$db->free($resql_check);
			
			// Essayer différentes colonnes de date possibles
			$date_columns = array('date_ech', 'date_echeance', 'date_lim_reglement', 'date_paiement', 'date_creation', 'datev');
			
			foreach ($date_columns as $date_col) {
				// Vérifier si la colonne existe
				$sql_col_check = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX.$table." LIKE '".$date_col."'";
				$resql_col = $db->query($sql_col_check);
				if ($resql_col && $db->num_rows($resql_col) > 0) {
					$db->free($resql_col);
					
					// Essayer différentes colonnes de montant possibles
					$amount_columns = array('amount', 'montant', 'total_ht', 'total', 'montant_ht');
					
					foreach ($amount_columns as $amount_col) {
						$sql_amount_check = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX.$table." LIKE '".$amount_col."'";
						$resql_amount = $db->query($sql_amount_check);
						if ($resql_amount && $db->num_rows($resql_amount) > 0) {
							$db->free($resql_amount);
							
							// Essayer la requête avec filtre sur les charges non payées
							$sql = 'SELECT SUM('.$amount_col.') as total_amount';
							$sql .= ' FROM '.MAIN_DB_PREFIX.$table;
							$sql .= ' WHERE 1=1';
							$sql .= " AND ".$date_col." >= '".$db->escape($date_start)."'";
							$sql .= " AND ".$date_col." <= '".$db->escape($date_end)."'";
							
							// Ajouter un filtre pour les charges non payées si la colonne existe
							$sql_paye_check = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX.$table." LIKE 'paye'";
							$resql_paye = $db->query($sql_paye_check);
							if ($resql_paye && $db->num_rows($resql_paye) > 0) {
								$db->free($resql_paye);
								$sql .= " AND paye = 0"; // Seulement les charges non payées
							}
							$db->free($resql_paye);
							
							$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
								if ($obj && !empty($obj->total_amount)) {
									$total += (float) $obj->total_amount;
		}
		$db->free($resql);
							}
							break; // Si on trouve une colonne de montant qui fonctionne, on s'arrête
						}
						$db->free($resql_amount);
					}
					break; // Si on trouve une colonne de date qui fonctionne, on s'arrête
				}
				$db->free($resql_col);
			}
		}
		$db->free($resql_check);
	}

	return (float) $total;
}

/**
 * Retourne le montant des charges sociales depuis la table llx_chargesociales
 * Basé sur la date d'échéance (date_ech) et le statut de paiement (paye)
 */
function sig_get_chargesociales_for_month(DoliDB $db, int $year, int $month): float
{
	global $conf;

	$date_start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
	$last_day = date('t', mktime(0, 0, 0, $month, 1, $year));
	$date_end = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $last_day);
	
	$total = 0.0;

	// Vérifier si la table existe
	$sql_check = "SHOW TABLES LIKE '".MAIN_DB_PREFIX."chargesociales'";
	$resql_check = $db->query($sql_check);
	if ($resql_check && $db->num_rows($resql_check) > 0) {
		$db->free($resql_check);
		
		// Récupérer les charges sociales non payées pour ce mois
		$sql = 'SELECT SUM(amount) as total_amount';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'chargesociales';
		$sql .= ' WHERE 1=1';
		$sql .= " AND date_ech >= '".$db->escape($date_start)."'";
		$sql .= " AND date_ech <= '".$db->escape($date_end)."'";
		$sql .= " AND paye = 0"; // Seulement les charges non payées
		
		$resql = $db->query($sql);
	if ($resql) {
			$obj = $db->fetch_object($resql);
			if ($obj && !empty($obj->total_amount)) {
				$total = (float) $obj->total_amount;
		}
		$db->free($resql);
		}
	}
	
	return (float) $total;
}




/**
 * Retourne le solde actuel d'un compte bancaire
 * Inclut toutes les écritures, même celles non transférées en comptabilité
 */
function sig_get_bank_balance(DoliDB $db, int $account_id): float
{
	global $conf;

	$sql = 'SELECT SUM(b.amount) as balance';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'bank as b';
	if ($account_id > 0) {
		$sql .= ' WHERE b.fk_account = '.(int)$account_id;
		$sql .= ' AND b.entity IN ('.getEntity('bank_account').')';
	} else {
		$sql .= ' WHERE b.entity IN ('.getEntity('bank_account').')';
	}
	// Pas de filtre sur le statut pour inclure toutes les écritures

	$balance = 0.0;
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj) {
			$balance = $obj->balance ? floatval($obj->balance) : 0;
		}
		$db->free($resql);
	}
	return $balance;
}

/**
 * Retourne le solde d'un compte bancaire à une date donnée (solde cumulé depuis le début)
 * Inclut toutes les écritures, même celles non transférées en comptabilité
 */
function sig_get_bank_balance_at_date(DoliDB $db, int $account_id, int $year, int $month, int $day): float
{
	global $conf;

	// Calculer la date limite (fin du mois demandé)
	$last_day_of_month = date('t', mktime(0, 0, 0, $month, 1, $year)); // Nombre de jours dans le mois
	$date_limit = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, min($day, $last_day_of_month));

	$sql = 'SELECT SUM(b.amount) as balance';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'bank as b';
	$sql .= ' WHERE b.fk_account = '.(int)$account_id;
	// Suppression temporaire du filtre d'entité pour test
	// $sql .= ' AND b.entity IN ('.getEntity('bank_account').')';
	$sql .= " AND b.datev <= '".$db->escape($date_limit)."'";
	// Pas de filtre sur le statut pour inclure toutes les écritures

	$balance = 0.0;
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj) {
			$balance = $obj->balance ? floatval($obj->balance) : 0;
		}
		$db->free($resql);
	}
	return $balance;
}

/**
 * Retourne les mouvements bancaires d'un mois pour un compte donné
 * Inclut toutes les écritures, même celles non transférées en comptabilité
 */
function sig_get_bank_movements_for_month(DoliDB $db, int $account_id, int $year, int $month): float
{
	global $conf;

	$date_start = sprintf('%04d-%02d-01', $year, $month);
	$last_day = date('t', mktime(0, 0, 0, $month, 1, $year));
	$date_end = sprintf('%04d-%02d-%02d', $year, $month, $last_day);

	$sql = 'SELECT SUM(b.amount) as movements';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'bank as b';
	$sql .= ' WHERE b.fk_account = '.(int)$account_id;
	// Suppression temporaire du filtre d'entité pour test
	// $sql .= ' AND b.entity IN ('.getEntity('bank_account').')';
	$sql .= " AND DATE(b.datev) >= '".$db->escape($date_start)."'";
	$sql .= " AND DATE(b.datev) <= '".$db->escape($date_end)."'";
	// Pas de filtre sur le statut pour inclure toutes les écritures

	$movements = 0.0;
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj && !empty($obj->movements)) $movements = (float) $obj->movements;
		$db->free($resql);
	}
	return (float) $movements;
}

/**
 * Retourne UNIQUEMENT les mouvements bancaires réels pour un mois donné
 * SANS aucun élément prévisionnel - directement depuis la table bank
 */
function sig_get_bank_movements_real_for_month(DoliDB $db, int $account_id, int $year, int $month): array
{
    $date_start = sprintf('%04d-%02d-01', $year, $month);
    $last_day = date('t', mktime(0, 0, 0, $month, 1, $year));
    $date_end = sprintf('%04d-%02d-%02d', $year, $month, $last_day);

    // Encaissements (montants positifs)
    $sql_enc = "SELECT SUM(b.amount) as total";
    $sql_enc .= " FROM ".MAIN_DB_PREFIX."bank as b";
    $sql_enc .= " WHERE b.fk_account = ".(int)$account_id;
    $sql_enc .= " AND DATE(b.datev) >= '".$db->escape($date_start)."'";
    $sql_enc .= " AND DATE(b.datev) <= '".$db->escape($date_end)."'";
    $sql_enc .= " AND b.amount > 0";
    
    // Décaissements (montants négatifs)
    $sql_dec = "SELECT SUM(b.amount) as total";
    $sql_dec .= " FROM ".MAIN_DB_PREFIX."bank as b";
    $sql_dec .= " WHERE b.fk_account = ".(int)$account_id;
    $sql_dec .= " AND DATE(b.datev) >= '".$db->escape($date_start)."'";
    $sql_dec .= " AND DATE(b.datev) <= '".$db->escape($date_end)."'";
    $sql_dec .= " AND b.amount < 0";
    
    $encaissements = 0.0;
    $decaissements = 0.0;
    
    // Récupérer encaissements
    $resql = $db->query($sql_enc);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        if ($obj && $obj->total) $encaissements = (float) $obj->total;
        $db->free($resql);
    }
    
    // Récupérer décaissements
    $resql = $db->query($sql_dec);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        if ($obj && $obj->total) $decaissements = (float) $obj->total;
        $db->free($resql);
    }
    
    return array(
        'encaissements' => $encaissements,
        'decaissements' => $decaissements
    );
}

/**
 * Retourne les derniers mouvements bancaires d'un compte
 * Inclut toutes les écritures, même celles non transférées en comptabilité
 */
function sig_get_recent_bank_movements(DoliDB $db, int $account_id, int $limit = 10): array
{
	global $conf;

	$sql = 'SELECT b.rowid, b.datev, b.amount, b.label, b.num_chq';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'bank as b';
	$sql .= ' WHERE b.fk_account = '.(int)$account_id;
	$sql .= ' AND b.entity IN ('.getEntity('bank_account').')'; // Suppression du paramètre 1
	$sql .= ' ORDER BY b.datev DESC, b.rowid DESC';
	$sql .= ' LIMIT '.(int)$limit;
	// Pas de filtre sur le statut pour inclure toutes les écritures

	$movements = array();
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			if ($obj) $movements[] = $obj;
			$i++;
		}
		$db->free($resql);
	}
	return $movements;
} 

/**
 * Récupère les mouvements bancaires pour une année complète
 */
function sig_get_bank_movements_for_year($db, $bank_account_id, $year) {
    $sql = "SELECT SUM(b.amount) as total_movements";
    $sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
    if ($bank_account_id > 0) {
        $sql .= " WHERE b.fk_account = ".(int)$bank_account_id;
        $sql .= " AND";
    } else {
        $sql .= " WHERE";
    }
    $sql .= " YEAR(b.datev) = ".(int)$year;
    $sql .= " AND b.entity IN (".getEntity('bank_account').")";
    
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        $db->free($resql);
        return $obj->total_movements ? floatval($obj->total_movements) : 0;
    }
    return 0;
}

/**
 * Récupère les mouvements bancaires détaillés pour un mois (encaissements et décaissements séparés)
 */
function sig_get_bank_movements_details_for_month($db, $bank_account_id, $year, $month) {
    // Encaissements (montants positifs)
    $sql_encaissements = "SELECT SUM(b.amount) as total_encaissements";
    $sql_encaissements .= " FROM ".MAIN_DB_PREFIX."bank as b";
    if ($bank_account_id > 0) {
        $sql_encaissements .= " WHERE b.fk_account = ".(int)$bank_account_id;
        $sql_encaissements .= " AND";
    } else {
        $sql_encaissements .= " WHERE";
    }
    $sql_encaissements .= " YEAR(b.datev) = ".(int)$year;
    $sql_encaissements .= " AND MONTH(b.datev) = ".(int)$month;
    $sql_encaissements .= " AND b.amount > 0";
    // Suppression temporaire du filtre d'entité pour test
    // $sql_encaissements .= " AND b.entity IN (".getEntity('bank_account').")";
    
    // Décaissements (montants négatifs)
    $sql_decaissements = "SELECT SUM(b.amount) as total_decaissements";
    $sql_decaissements .= " FROM ".MAIN_DB_PREFIX."bank as b";
    if ($bank_account_id > 0) {
        $sql_decaissements .= " WHERE b.fk_account = ".(int)$bank_account_id;
        $sql_decaissements .= " AND";
    } else {
        $sql_decaissements .= " WHERE";
    }
    $sql_decaissements .= " YEAR(b.datev) = ".(int)$year;
    $sql_decaissements .= " AND MONTH(b.datev) = ".(int)$month;
    $sql_decaissements .= " AND b.amount < 0";
    // Suppression temporaire du filtre d'entité pour test
    // $sql_decaissements .= " AND b.entity IN (".getEntity('bank_account').")";
    
    $encaissements = 0;
    $decaissements = 0;
    
    // Récupérer les encaissements
    $resql = $db->query($sql_encaissements);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        $encaissements = $obj->total_encaissements ? floatval($obj->total_encaissements) : 0;
        $db->free($resql);
    }
    
    // Récupérer les décaissements
    $resql = $db->query($sql_decaissements);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        $decaissements = $obj->total_decaissements ? floatval($obj->total_decaissements) : 0;
        $db->free($resql);
    }
    
    // Ajouter les factures fournisseur impayées pour ce mois (si l'option est activée)
    $factures_fournisseur = 0;
    $include_supplier_invoices = getDolGlobalString('SIG_INCLUDE_SUPPLIER_INVOICES');
    
    if (!empty($include_supplier_invoices) && $include_supplier_invoices == '1') {
    $factures_fournisseur = sig_get_supplier_invoices_for_month($db, $year, $month);
    $decaissements += $factures_fournisseur;
    }
    
    // Ajouter les charges fiscales et sociales impayées pour ce mois (si l'option est activée)
    $charges_fiscales_sociales = 0;
    $include_social_charges = getDolGlobalString('SIG_INCLUDE_SOCIAL_CHARGES');
    
    if (!empty($include_supplier_invoices) && $include_supplier_invoices == '1') {
        // 1. Charges depuis les factures fournisseurs (méthode actuelle)
    $charges_fiscales_sociales = sig_get_tax_social_charges_for_month($db, $year, $month);
    $decaissements += $charges_fiscales_sociales;
    }
    
    // 2. Charges sociales depuis le module Sociales (option séparée)
    if (!empty($include_social_charges) && $include_social_charges == '1') {
        // Charges depuis le module Sociales (nouvelle méthode)
        $charges_sociales_module = sig_get_sociales_charges_for_month($db, $year, $month);
        $charges_fiscales_sociales += $charges_sociales_module;
        
        // Charges depuis la table chargesociales (méthode spécifique)
        $charges_chargesociales = sig_get_chargesociales_for_month($db, $year, $month);
        $charges_fiscales_sociales += $charges_chargesociales;
        
        $decaissements += $charges_sociales_module + $charges_chargesociales;
    }
    
    return array(
        'encaissements' => $encaissements,
        'decaissements' => $decaissements,
        'factures_fournisseur' => $factures_fournisseur,
        'charges_fiscales_sociales' => $charges_fiscales_sociales
    );
}





/**
 * Retourne la marge prévue des devis signés pour un mois donné
 * Calcule la marge à partir du CA prévu et du taux de marge configuré
 * Utilise la date de livraison si renseignée, sinon le mois courant
 */
function sig_get_expected_margin_for_month($db, $year, $month) {
    global $conf;
    
    // Vérifier que les paramètres sont valides
    if (empty($month) || !is_numeric($month) || $month < 1 || $month > 12) {
        return 0.0;
    }
    
    // Vérifier si l'option d'inclusion des devis signés est activée
    $include_signed_quotes = getDolGlobalString('SIG_INCLUDE_SIGNED_QUOTES');
    if (empty($include_signed_quotes) || $include_signed_quotes != '1') {
        return 0.0; // Retourner 0 si l'option est désactivée
    }
    
    $month = (int) $month;
    $year = (int) $year;
    
    $current_month = (int) date('n'); // Mois actuel (1-12)
    $current_year = (int) date('Y');  // Année actuelle
    
    // Récupérer le taux de marge configuré
    $margin_rate = getDolGlobalString('SIG_MARGIN_RATE');
    if (empty($margin_rate)) $margin_rate = 20; // Valeur par défaut 20%
    $margin_rate = (float) $margin_rate / 100; // Convertir en décimal (20% = 0.20)
    
    // Récupérer le CA prévu pour ce mois
    $ca_prevue = sig_get_expected_turnover_for_month($db, $year, $month);
    
    // Calculer la marge : CA prévu × taux de marge
    $total_margin = $ca_prevue * $margin_rate;
    
    return (float) $total_margin;
}


/**
 * Retourne le montant des factures client impayées pour un mois donné
 * Basé sur la date de règlement prévue (date_lim_reglement)
 */
function sig_get_customer_invoices_for_month(DoliDB $db, int $year, int $month): float
{
    global $conf;
    
    // Vérifier que les paramètres sont valides
    if (empty($month) || !is_numeric($month) || $month < 1 || $month > 12) {
        return 0.0;
    }
    
    $month = (int) $month;
    $year = (int) $year;
    
    // Déterminer si on est dans le mois actuel ou futur
    $current_date = new DateTime();
    $current_year = (int) $current_date->format('Y');
    $current_month = (int) $current_date->format('n');
    $is_current_month = ($year == $current_year && $month == $current_month);
    $is_future_month = ($year > $current_year) || ($year == $current_year && $month > $current_month);
    
    // Requête pour récupérer les factures client impayées
    $sql = 'SELECT SUM(f.total_ht) as total_ht';
    $sql .= ' FROM '.MAIN_DB_PREFIX.'facture as f';
    $sql .= ' WHERE f.entity IN ('.getEntity('invoice', 1).')';
    $sql .= ' AND f.fk_statut = 1'; // Statut 1 = Validée mais non payée
    
    if ($is_current_month) {
        // Mois actuel : TOUTES les factures en retard + celles qui arrivent à échéance ce mois-ci
        $last_day = date('t', mktime(0, 0, 0, $month, 1, $year));
        $date_end = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $last_day);
        $sql .= ' AND f.date_lim_reglement <= "'.$db->escape($date_end).'"';
    } elseif ($is_future_month) {
        // Mois futur : seulement les factures avec date limite dans ce mois
        $date_start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $last_day = date('t', mktime(0, 0, 0, $month, 1, $year));
        $date_end = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $last_day);
        $sql .= ' AND f.date_lim_reglement >= "'.$db->escape($date_start).'"';
        $sql .= ' AND f.date_lim_reglement <= "'.$db->escape($date_end).'"';
    } else {
        // Mois passé : aucune facture prévisionnelle
        return 0.0;
    }
    
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        if ($obj && $obj->total_ht) {
            $db->free($resql);
            return (float) $obj->total_ht;
        }
        $db->free($resql);
    }
    
        return 0.0;
}

/**
 * Retourne la marge prévue avec délai de paiement pour un mois donné
 * Calcule la marge des livraisons qui seront payées ce mois-ci (en tenant compte du délai)
 */
function sig_get_expected_margin_with_delay_for_month($db, $year, $month) {
    global $conf;
    
    // Vérifier que les paramètres sont valides
    if (empty($month) || !is_numeric($month) || $month < 1 || $month > 12) {
        return 0.0;
    }
    
    // Vérifier si l'option d'inclusion des devis signés est activée
    $include_signed_quotes = getDolGlobalString('SIG_INCLUDE_SIGNED_QUOTES');
    if (empty($include_signed_quotes) || $include_signed_quotes != '1') {
        return 0.0; // Retourner 0 si l'option est désactivée
    }
    
    $month = (int) $month;
    $year = (int) $year;
    
    $current_month = (int) date('n'); // Mois actuel (1-12)
    $current_year = (int) date('Y');  // Année actuelle
    
    // Récupérer le taux de marge configuré
    $margin_rate = getDolGlobalString('SIG_MARGIN_RATE');
    if (empty($margin_rate)) $margin_rate = 20; // Valeur par défaut 20%
    $margin_rate = (float) $margin_rate / 100; // Convertir en décimal (20% = 0.20)
    
    // Récupérer le délai de paiement configuré
    $payment_delay = getDolGlobalString('SIG_PAYMENT_DELAY');
    if (empty($payment_delay)) $payment_delay = 30; // Valeur par défaut 30 jours
    $payment_delay = (int) $payment_delay;
    
    $total_margin = 0.0;
    
    // Calculer la date de début du mois demandé
    $month_start = mktime(0, 0, 0, $month, 1, $year);
    
    // Calculer la date de fin du mois demandé
    $month_end = mktime(23, 59, 59, $month, date('t', $month_start), $year);
    
    // Parcourir tous les mois précédents pour trouver les livraisons qui seront payées ce mois-ci
    for ($check_year = $year - 1; $check_year <= $year + 1; $check_year++) {
        for ($check_month = 1; $check_month <= 12; $check_month++) {
            // Calculer la date de livraison (début du mois)
            $delivery_date = mktime(0, 0, 0, $check_month, 1, $check_year);
            
            // Calculer la date de paiement (livraison + délai)
            $payment_date = $delivery_date + ($payment_delay * 24 * 3600);
            
            // Vérifier si ce paiement tombe dans le mois demandé
            if ($payment_date >= $month_start && $payment_date <= $month_end) {
                // Récupérer le CA prévu pour ce mois de livraison
                $ca_prevue = sig_get_expected_turnover_for_month($db, $check_year, $check_month);
                
                // Ajouter la marge de ce mois de livraison
                $total_margin += $ca_prevue * $margin_rate;
            }
        }
    }
    
    return (float) $total_margin;
}

/**
 * Version alternative utilisant la table bank_account_lines (probablement la bonne)
 */
function sig_get_bank_balance_alternative(DoliDB $db, int $account_id): float
{
	global $conf;

	$sql = 'SELECT SUM(bal.amount) as balance';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'bank_account_lines as bal';
	if ($account_id > 0) {
		$sql .= ' WHERE bal.fk_account = '.(int)$account_id;
		$sql .= ' AND bal.entity IN ('.getEntity('bank_account').')';
	} else {
		$sql .= ' WHERE bal.entity IN ('.getEntity('bank_account').')';
	}

	$balance = 0.0;
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj) {
			$balance = $obj->balance ? floatval($obj->balance) : 0;
		}
		$db->free($resql);
	}
	return $balance;
}

/**
 * Version alternative pour les mouvements détaillés utilisant bank_account_lines
 */
function sig_get_bank_movements_details_for_month_alternative($db, $bank_account_id, $year, $month) {
    // Encaissements (montants positifs)
    $sql_encaissements = "SELECT SUM(bal.amount) as total_encaissements";
    $sql_encaissements .= " FROM ".MAIN_DB_PREFIX."bank_account_lines as bal";
    if ($bank_account_id > 0) {
        $sql_encaissements .= " WHERE bal.fk_account = ".(int)$bank_account_id;
        $sql_encaissements .= " AND";
    } else {
        $sql_encaissements .= " WHERE";
    }
    $sql_encaissements .= " YEAR(bal.datev) = ".(int)$year;
    $sql_encaissements .= " AND MONTH(bal.datev) = ".(int)$month;
    $sql_encaissements .= " AND bal.amount > 0";
    $sql_encaissements .= " AND bal.entity IN (".getEntity('bank_account').")";
    
    // Décaissements (montants négatifs)
    $sql_decaissements = "SELECT SUM(bal.amount) as total_decaissements";
    $sql_decaissements .= " FROM ".MAIN_DB_PREFIX."bank_account_lines as bal";
    if ($bank_account_id > 0) {
        $sql_decaissements .= " WHERE bal.fk_account = ".(int)$bank_account_id;
        $sql_decaissements .= " AND";
    } else {
        $sql_decaissements .= " WHERE";
    }
    $sql_decaissements .= " YEAR(bal.datev) = ".(int)$year;
    $sql_decaissements .= " AND MONTH(bal.datev) = ".(int)$month;
    $sql_decaissements .= " AND bal.amount < 0";
    $sql_decaissements .= " AND bal.entity IN (".getEntity('bank_account').")";
    
    $encaissements = 0;
    $decaissements = 0;
    
    // Récupérer les encaissements
    $resql = $db->query($sql_encaissements);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        $encaissements = $obj->total_encaissements ? floatval($obj->total_encaissements) : 0;
        $db->free($resql);
    }
    
    // Récupérer les décaissements
    $resql = $db->query($sql_decaissements);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        $decaissements = $obj->total_decaissements ? floatval($obj->total_decaissements) : 0;
        $db->free($resql);
    }
    
    return array(
        'encaissements' => $encaissements,
        'decaissements' => $decaissements
    );
}

/**
 * Retourne le montant des factures modèle client (récurrentes) pour un mois donné
 * Calcule la marge à partir du montant HT et du taux de marge configuré
 * LOGIQUE : Marge comptée le mois de génération de la facture (pas le mois d'encaissement)
 * Le délai de 30 jours affecte l'encaissement mais pas le calcul de la marge prévisionnelle
 */
function sig_get_customer_template_invoices_for_month(DoliDB $db, int $year, int $month): float
{
    global $conf;
    
    // Vérifier que les paramètres sont valides
    if (empty($month) || !is_numeric($month) || $month < 1 || $month > 12) {
        return 0.0;
    }
    
    // Vérifier si l'option d'inclusion des factures modèle est activée
    $include_template_invoices = getDolGlobalString('SIG_INCLUDE_CUSTOMER_TEMPLATE_INVOICES');
    if (empty($include_template_invoices) || $include_template_invoices != '1') {
        return 0.0; // Retourner 0 si l'option est désactivée
    }
    
    $month = (int) $month;
    $year = (int) $year;
    
    // Récupérer le taux de marge configuré
    $margin_rate = getDolGlobalString('SIG_MARGIN_RATE');
    if (empty($margin_rate)) $margin_rate = 20; // Valeur par défaut 20%
    $margin_rate = (float) $margin_rate / 100; // Convertir en décimal (20% = 0.20)
    
    // Calculer les dates du mois demandé
    $date_start = sprintf('%04d-%02d-01', $year, $month);
    $last_day = date('t', mktime(0, 0, 0, $month, 1, $year));
    $date_end = sprintf('%04d-%02d-%02d', $year, $month, $last_day);
    
    // NOUVELLE LOGIQUE CORRIGÉE :
    // On cherche les factures qui DOIVENT être générées ce mois-ci selon leur fréquence
    // Le délai de 30 jours ne change pas le mois de génération, seulement l'encaissement
    // Exemple : facture générée en janvier = marge comptée en janvier (même si encaissée en février)
    
    $total_margin = 0.0;
    
    // Requête pour récupérer les factures modèle actives avec leur fréquence
    $sql = 'SELECT fr.total_ht, fr.frequency, fr.unit_frequency, fr.date_when';
    $sql .= ' FROM '.MAIN_DB_PREFIX.'facture_rec as fr';
    $sql .= ' WHERE fr.entity IN ('.getEntity('invoice', 1).')';
    $sql .= ' AND fr.suspended = 0'; // Seulement les modèles actifs (non suspendus)
    
    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $montant_ht = (float) $obj->total_ht;
            $frequency = (int) $obj->frequency;
            $unit_frequency = $obj->unit_frequency; // 'm' = mois, 'y' = année, etc.
            
            // Vérifier si cette facture doit être générée ce mois-ci selon sa fréquence
            if (sig_should_generate_template_invoice_for_month($obj, $year, $month)) {
                // Calculer la marge : Montant HT × taux de marge
                $total_margin += $montant_ht * $margin_rate;
            }
        }
        $db->free($resql);
    }
    
    return (float) $total_margin;
}

/**
 * Détermine si une facture modèle doit être générée pour un mois donné
 * Prend en compte la fréquence de récurrence (mensuelle, trimestrielle, annuelle)
 */
function sig_should_generate_template_invoice_for_month($template, int $year, int $month): bool
{
    $frequency = (int) $template->frequency;
    $unit_frequency = $template->unit_frequency;
    $date_when = $template->date_when;
    
    // Si pas de fréquence définie, on considère que c'est mensuel par défaut
    if (empty($frequency)) $frequency = 1;
    if (empty($unit_frequency)) $unit_frequency = 'm';
    
    // Normaliser l'unité de fréquence
    $unit_frequency = strtolower($unit_frequency);
    
    switch ($unit_frequency) {
        case 'm': // Mensuel
        case 'month':
        case 'months':
            if ($frequency == 1) {
                // Facture mensuelle : tous les mois
                return true;
            } else {
                // Facture tous les X mois : vérifier si ce mois correspond
                // Si date_when est définie, utiliser comme référence
                if ($date_when) {
                    $reference_date = new DateTime($date_when);
                    $target_date = new DateTime("$year-$month-01");
                    $diff = $reference_date->diff($target_date);
                    $months_diff = ($diff->y * 12) + $diff->m;
                    return ($months_diff % $frequency) == 0;
                } else {
                    // Sans date de référence, utiliser le mois comme base
                    return ($month % $frequency) == 1; // Janvier, puis tous les X mois
                }
            }
            break;
            
        case 'y': // Annuel
        case 'year':
        case 'years':
            if ($date_when) {
                // Vérifier si on est dans le bon mois de l'année
                $reference_date = new DateTime($date_when);
                return ((int) $reference_date->format('n')) == $month;
            } else {
                // Par défaut, facture annuelle en janvier
                return $month == 1;
            }
            break;
            
        case 'w': // Hebdomadaire (peu probable pour la trésorerie, mais on gère)
        case 'week':
        case 'weeks':
            // Pour les factures hebdomadaires, on considère qu'elles génèrent tous les mois
            return true;
            break;
            
        case 'd': // Journalier (très peu probable)
        case 'day':
        case 'days':
            // Pour les factures journalières, on considère qu'elles génèrent tous les mois
            return true;
            break;
            
        default:
            // Par défaut, considérer comme mensuel
            return true;
    }
}

/**
 * Retourne les détails des factures modèle client pour un mois donné
 * Retourne un tableau avec les informations détaillées de chaque facture modèle
 */
function sig_get_customer_template_invoices_details_for_month(DoliDB $db, int $year, int $month): array
{
    global $conf;
    
    $details = array();
    
    // Vérifier que les paramètres sont valides
    if (empty($month) || !is_numeric($month) || $month < 1 || $month > 12) {
        return $details;
    }
    
    // Vérifier si l'option d'inclusion des factures modèle est activée
    $include_template_invoices = getDolGlobalString('SIG_INCLUDE_CUSTOMER_TEMPLATE_INVOICES');
    if (empty($include_template_invoices) || $include_template_invoices != '1') {
        return $details;
    }
    
    $month = (int) $month;
    $year = (int) $year;
    
    // Récupérer le taux de marge configuré
    $margin_rate = getDolGlobalString('SIG_MARGIN_RATE');
    if (empty($margin_rate)) $margin_rate = 20; // Valeur par défaut 20%
    $margin_rate_decimal = (float) $margin_rate / 100;
    
    // Calculer les dates du mois demandé
    $date_start = sprintf('%04d-%02d-01', $year, $month);
    $last_day = date('t', mktime(0, 0, 0, $month, 1, $year));
    $date_end = sprintf('%04d-%02d-%02d', $year, $month, $last_day);
    
    // LOGIQUE CORRIGÉE : On traite les factures qui doivent être générées ce mois-ci
    // Le délai de 30 jours est informatif mais ne change pas le mois de calcul de la marge
    
    // Requête pour récupérer les détails des factures modèle actives
    $sql = 'SELECT fr.rowid, fr.titre, fr.total_ht, fr.date_when, fr.frequency, fr.unit_frequency, fr.suspended';
    $sql .= ' FROM '.MAIN_DB_PREFIX.'facture_rec as fr';
    $sql .= ' WHERE fr.entity IN ('.getEntity('invoice', 1).')';
    $sql .= ' AND fr.suspended = 0'; // Seulement les modèles actifs
    
    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            // Vérifier si cette facture doit être générée ce mois-ci selon sa fréquence
            if (sig_should_generate_template_invoice_for_month($obj, $year, $month)) {
                $montant_ht = (float) $obj->total_ht;
                $marge_calculee = $montant_ht * $margin_rate_decimal;
                
                $details[] = array(
                    'id' => $obj->rowid,
                    'titre' => $obj->titre,
                    'montant_ht' => $montant_ht,
                    'marge_calculee' => $marge_calculee,
                    'taux_marge' => $margin_rate,
                    'date_when' => $obj->date_when,
                    'frequency' => $obj->frequency,
                    'unit_frequency' => $obj->unit_frequency,
                    'suspended' => $obj->suspended
                );
            }
        }
        $db->free($resql);
    }
    
    return $details;
}

/**
 * Retourne le montant des salaires impayés pour un mois donné
 * Utilise la colonne dateep (date de fin de période) pour déterminer le mois
 * et paye = 0 pour identifier les salaires impayés
 */
function sig_get_unpaid_salaries_for_month(DoliDB $db, int $year, int $month): float
{
    global $conf;
    
    // Vérifier que les paramètres sont valides
    if (empty($month) || !is_numeric($month) || $month < 1 || $month > 12) {
        return 0.0;
    }
    
    $month = (int) $month;
    $year = (int) $year;
    
    // Déterminer si on est dans le mois actuel ou futur
    $current_date = new DateTime();
    $current_year = (int) $current_date->format('Y');
    $current_month = (int) $current_date->format('n');
    $is_current_month = ($year == $current_year && $month == $current_month);
    $is_future_month = ($year > $current_year) || ($year == $current_year && $month > $current_month);
    
    // Requête pour récupérer les salaires impayés
    $sql = 'SELECT SUM(amount) as total_amount';
    $sql .= ' FROM '.MAIN_DB_PREFIX.'salary';
    $sql .= ' WHERE paye = 0'; // Seulement les salaires impayés
    
    if ($is_current_month) {
        // Mois actuel : TOUS les salaires impayés en retard + ceux qui arrivent à échéance ce mois-ci
        $last_day = date('t', mktime(0, 0, 0, $month, 1, $year));
        $date_end = sprintf('%04d-%02d-%02d', $year, $month, $last_day);
        $sql .= ' AND dateep <= "'.$db->escape($date_end).'"';
    } elseif ($is_future_month) {
        // Mois futur : seulement les salaires avec dateep dans ce mois
        $date_start = sprintf('%04d-%02d-01', $year, $month);
        $last_day = date('t', mktime(0, 0, 0, $month, 1, $year));
        $date_end = sprintf('%04d-%02d-%02d', $year, $month, $last_day);
        $sql .= ' AND dateep >= "'.$db->escape($date_start).'"';
        $sql .= ' AND dateep <= "'.$db->escape($date_end).'"';
    } else {
        // Mois passé : aucun salaire prévisionnel
        return 0.0;
    }
    
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        if ($obj && $obj->total_amount) {
            $db->free($resql);
            return (float) $obj->total_amount;
        }
        $db->free($resql);
    }
    
    return 0.0;
} 