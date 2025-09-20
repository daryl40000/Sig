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

// SECTION RÉSUMÉ : Indicateurs de trésorerie
$total_year_ca = sig_get_total_turnover_for_year($db, $year);
$total_year_ca_prevu = sig_get_total_expected_turnover_for_year($db, $year);
$total_impaye = sig_get_total_unpaid_invoices($db, $year);

print '<div style="display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">';

// CA Encaissé
print '<div class="info-box" style="background: #e8f5e8; border: 1px solid #4CAF50; border-radius: 5px; padding: 15px; flex: 1; min-width: 250px;">';
print '<div style="text-align: center;">';
print '<h3 style="margin: 0; color: #2E7D32;">CA Encaissé</h3>';
print '<div style="font-size: 20px; font-weight: bold; color: #1B5E20; margin-top: 8px;">'.price($total_year_ca).'</div>';
print '<div style="font-size: 12px; color: #666; margin-top: 3px;">Factures payées</div>';
print '</div>';
print '</div>';

// CA à Encaisser
print '<div class="info-box" style="background: #fff3e0; border: 1px solid #FF9800; border-radius: 5px; padding: 15px; flex: 1; min-width: 250px;">';
print '<div style="text-align: center;">';
print '<h3 style="margin: 0; color: #F57C00;">CA à Encaisser</h3>';
print '<div style="font-size: 20px; font-weight: bold; color: #E65100; margin-top: 8px;">'.price($total_impaye).'</div>';
print '<div style="font-size: 12px; color: #666; margin-top: 3px;">Factures impayées</div>';
print '</div>';
print '</div>';

// CA Potentiel
print '<div class="info-box" style="background: #e3f2fd; border: 1px solid #2196F3; border-radius: 5px; padding: 15px; flex: 1; min-width: 250px;">';
print '<div style="text-align: center;">';
print '<h3 style="margin: 0; color: #1565C0;">CA Potentiel</h3>';
print '<div style="font-size: 20px; font-weight: bold; color: #0D47A1; margin-top: 8px;">'.price($total_year_ca_prevu).'</div>';
print '<div style="font-size: 12px; color: #666; margin-top: 3px;">Devis signés</div>';
print '</div>';
print '</div>';

print '</div>';

// SECTION: Évolution de la Trésorerie par Mois (seulement si un compte est configuré)
if ($selected_bank_account > 0) {
    print load_fiche_titre('Évolution de la Trésorerie par Mois', '', 'fa-chart-line');

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
    
    // Nouvelle approche : calculer le solde à la fin de chaque mois directement
    $solde_fin_precedent = 0;
    
    for ($month = 1; $month <= 12; $month++) {
        // Le solde de début est le solde de fin théorique du mois précédent
        if ($month == 1) {
            // Pour janvier, calculer le solde fin théorique de décembre de l'année précédente
            $solde_bancaire_decembre = sig_get_bank_balance_at_date($db, $selected_bank_account, $year - 1, 12, 31);
            $marge_decembre = sig_get_expected_margin_with_delay_for_month($db, $year - 1, 12);
            $solde_debut_mois = $solde_bancaire_decembre + $marge_decembre;
        } else {
            $solde_debut_mois = $solde_fin_precedent;
        }
        
        // Récupérer les mouvements détaillés du mois
        $mouvements_details = sig_get_bank_movements_details_for_month($db, $selected_bank_account, $year, $month);
        $encaissements_mois = $mouvements_details['encaissements'];
        $decaissements_mois = abs($mouvements_details['decaissements']); // Valeur absolue pour l'affichage
        
        // Calculer le solde fin théorique : Solde début + Encaissements - Décaissements + Marge avec délai
        // La marge avec délai est ajoutée ici, pas dans les encaissements pour éviter le double comptage
        $marge_avec_delai = sig_get_expected_margin_with_delay_for_month($db, $year, $month);
        $solde_fin_mois = $solde_debut_mois + $encaissements_mois - $decaissements_mois + $marge_avec_delai;
    
    // Diagnostic pour le premier mois (pour debug)
    if ($month == 1) {
        $debug_info = sig_diagnostic_bank_movements($db, $selected_bank_account, $year, $month);
        if (!empty($debug_info)) {
            echo "<!-- DIAGNOSTIC BANCAIRE: ".$debug_info." -->\n";
        }
        
        // Diagnostic des tables disponibles
        $debug_tables = sig_diagnostic_bank_tables($db, $selected_bank_account);
        if (!empty($debug_tables)) {
            echo "<!-- DIAGNOSTIC TABLES: ".$debug_tables." -->\n";
        }
        
        // Diagnostic détaillé des filtres
        $debug_filters = sig_diagnostic_bank_filters($db, $selected_bank_account, $year, $month);
        if (!empty($debug_filters)) {
            echo "<!-- DIAGNOSTIC FILTERS: ".$debug_filters." -->\n";
        }
        
        // Diagnostic des marges prévues
        $debug_margins = sig_diagnostic_expected_margins($db, $year, $month);
        if (!empty($debug_margins)) {
            echo "<!-- DIAGNOSTIC MARGINS: ".$debug_margins." -->\n";
        }
        
        // Diagnostic de la marge avec délai
        $marge_avec_delai_debug = sig_get_expected_margin_with_delay_for_month($db, $year, $month);
        echo "<!-- DIAGNOSTIC MARGIN DELAY: Marge avec délai mois ".$month."/".$year.": ".number_format($marge_avec_delai_debug, 2)." -->\n";
        
        // Diagnostic du solde de début
        if ($month == 1) {
            $solde_bancaire_decembre = sig_get_bank_balance_at_date($db, $selected_bank_account, $year - 1, 12, 31);
            $marge_decembre = sig_get_expected_margin_with_delay_for_month($db, $year - 1, 12);
            echo "<!-- DIAGNOSTIC SOLDE DEBUT: Solde bancaire déc ".($year-1).": ".number_format($solde_bancaire_decembre, 2)." + Marge déc: ".number_format($marge_decembre, 2)." = Solde début janv: ".number_format($solde_bancaire_decembre + $marge_decembre, 2)." -->\n";
        } else {
            echo "<!-- DIAGNOSTIC SOLDE DEBUT: Solde début mois ".$month."/".$year.": ".number_format($solde_debut_mois, 2)." (solde fin théorique mois précédent) -->\n";
        }
        
        // Diagnostic du solde fin théorique
        echo "<!-- DIAGNOSTIC SOLDE FIN: Solde début: ".number_format($solde_debut_mois, 2)." + Encaissements bruts: ".number_format($encaissements_bruts, 2)." - Décaissements: ".number_format($decaissements_mois, 2)." + Marge délai: ".number_format($marge_avec_delai, 2)." = Solde fin: ".number_format($solde_fin_mois, 2)." -->\n";
    }
        
        // Calculer la variation du mois
        $variation_mois = $solde_fin_mois - $solde_debut_mois;
        
        $total_encaissements += $encaissements_mois;
        $total_decaissements += $decaissements_mois;
        
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
        // Calculer la marge prévue pour l'affichage
        $marge_prevue_affichage = 0;
        if ($year >= $current_year && $month >= $current_month) {
            $marge_prevue_affichage = sig_get_expected_margin_for_month($db, $year, $month);
        }
        
        // Afficher les encaissements avec indication de la marge prévue (information uniquement)
        $encaissements_bruts = $mouvements_details['encaissements'];
        if ($marge_prevue_affichage > 0) {
            print '<td style="text-align: right; color: #2E7D32;">'.($encaissements_bruts > 0 ? '+'.price($encaissements_bruts) : price($encaissements_bruts));
            print '<br><small style="color: #4CAF50;">+ Marge prévue: +'.price($marge_prevue_affichage).'</small>';
            print '<br><strong>Total: +'.price($encaissements_bruts + $marge_prevue_affichage).'</strong></td>';
        } else {
            print '<td style="text-align: right; color: #2E7D32;">'.($encaissements_bruts > 0 ? '+'.price($encaissements_bruts) : price($encaissements_bruts)).'</td>';
        }
        
        print '<td style="text-align: right; color: #C62828;">'.($decaissements_mois > 0 ? '-'.price($decaissements_mois) : price($decaissements_mois)).'</td>';
        
        // Afficher le solde fin avec indication de la marge avec délai
        if ($marge_avec_delai > 0) {
            $solde_sans_marge = $solde_fin_mois - $marge_avec_delai;
            print '<td style="text-align: right; font-weight: bold;">'.price($solde_sans_marge);
            print '<br><small style="color: #4CAF50;">+ Marge (délai): +'.price($marge_avec_delai).'</small>';
            print '<br><strong>Total: '.price($solde_fin_mois).'</strong></td>';
        } else {
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

    // Calculer le total de la marge prévue pour l'année
    $total_marge_prevue = 0;
    for ($m = $current_month; $m <= 12; $m++) {
        if ($year >= $current_year) {
            $total_marge_prevue += sig_get_expected_margin_for_month($db, $year, $m);
        }
    }

    // Ligne de total
    $solde_debut_annee = sig_get_bank_balance_at_date($db, $selected_bank_account, $year - 1, 12, 31);
    $solde_fin_annee = $solde_precedent;
    $variation_totale = $solde_fin_annee - $solde_debut_annee;
    
    print '<tr class="liste_total">';
    print '<td><strong>TOTAL ANNÉE '.$year.'</strong></td>';
    print '<td style="text-align: right; font-weight: bold;">'.price($solde_debut_annee).'</td>';
    
    if ($total_marge_prevue > 0) {
        print '<td style="text-align: right; font-weight: bold; color: #2E7D32;">+'.price($total_encaissements);
        print '<br><small style="color: #4CAF50;">+ Marge prévue: +'.price($total_marge_prevue).'</small>';
        print '<br><strong>Total: +'.price($total_encaissements + $total_marge_prevue).'</strong></td>';
    } else {
    print '<td style="text-align: right; font-weight: bold; color: #2E7D32;">+'.price($total_encaissements).'</td>';
    }
    
    print '<td style="text-align: right; font-weight: bold; color: #C62828;">-'.price($total_decaissements).'</td>';
    print '<td style="text-align: right; font-weight: bold;">'.price($solde_fin_annee).'</td>';
    $variation_color = $variation_totale >= 0 ? '#2E7D32' : '#C62828';
    $variation_sign = $variation_totale > 0 ? '+' : '';
    print '<td style="text-align: right; font-weight: bold; color: '.$variation_color.';">'.$variation_sign.price($variation_totale).'</td>';
    print '</tr>';

    print '</table>';
    print '<br>';
} else {
    // Message si aucun compte n'est configuré
    print load_fiche_titre('Évolution de la Trésorerie par Mois', '', 'fa-chart-line');
    print '<div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 5px; padding: 15px; margin-bottom: 20px;">';
    print '<p><strong>Information :</strong> Pour afficher l\'évolution de la trésorerie par mois, veuillez <a href="admin/setup.php">configurer un compte bancaire principal</a>.</p>';
    print '</div>';
}

// SECTION: Informations bancaires si un compte est configuré
if ($selected_bank_account > 0 && isset($account) && $account->rowid > 0) {
    print load_fiche_titre('Solde Bancaire - '.$account->label, '', 'fa-university');
    
    // Calculer le solde actuel
    $solde_actuel = sig_get_bank_balance($db, $selected_bank_account);
    $mouvements_mois = sig_get_bank_movements_for_month($db, $selected_bank_account, $year, date('n'));
    
    print '<div style="display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">';
    
    // Solde actuel
    print '<div class="info-box" style="background: #e8f5e8; border: 1px solid #4CAF50; border-radius: 5px; padding: 15px; flex: 1; min-width: 250px;">';
    print '<div style="text-align: center;">';
    print '<h3 style="margin: 0; color: #2E7D32;">Solde Actuel</h3>';
    print '<div style="font-size: 20px; font-weight: bold; color: #1B5E20; margin-top: 8px;">'.price($solde_actuel).'</div>';
    print '<div style="font-size: 12px; color: #666; margin-top: 3px;">'.date('d/m/Y').'</div>';
    print '</div>';
    print '</div>';
    
    // Mouvements du mois
    print '<div class="info-box" style="background: #e3f2fd; border: 1px solid #2196F3; border-radius: 5px; padding: 15px; flex: 1; min-width: 250px;">';
    print '<div style="text-align: center;">';
    print '<h3 style="margin: 0; color: #1565C0;">Mouvements '.date('M Y').'</h3>';
    print '<div style="font-size: 20px; font-weight: bold; color: #0D47A1; margin-top: 8px;">'.price($mouvements_mois).'</div>';
    print '<div style="font-size: 12px; color: #666; margin-top: 3px;">Crédit - Débit</div>';
    print '</div>';
    print '</div>';
    
    print '</div>';
}

print '<br /><br />';

// SECTION: Liste des factures impayées
print load_fiche_titre('Factures Impayées', '', 'fa-exclamation-triangle');

print '<div class="div-table-responsive">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<th class="center">' . $langs->trans('Ref') . '</th>';
print '<th class="center">' . $langs->trans('Date') . '</th>';
print '<th class="center">' . $langs->trans('ThirdParty') . '</th>';
print '<th class="right">' . $langs->trans('TotalHT') . '</th>';
print '<th class="center">Retard (jours)</th>';
print '</tr>';

$unpaid_invoices = sig_get_unpaid_invoices_list($db, $year);

if (empty($unpaid_invoices)) {
    print '<tr><td colspan="5" class="center">Aucune facture impayée</td></tr>';
} else {
    foreach ($unpaid_invoices as $invoice) {
        // Calcul du retard
        $date_echeance = $invoice->date_lim_reglement ? $invoice->date_lim_reglement : $invoice->datef;
        $retard = (dol_now() - $date_echeance) / (24 * 3600); // en jours
        
        $row_style = '';
        $retard_text = '';
        if ($retard > 0) {
            $retard_text = '+'.round($retard).' j';
            if ($retard > 30) {
                $row_style = 'background-color: #ffebee;'; // Rouge léger
            } elseif ($retard > 7) {
                $row_style = 'background-color: #fff3e0;'; // Orange léger
            }
        } else {
            $retard_text = 'OK';
        }
        
        print '<tr style="'.$row_style.'">';
        print '<td class="center">' . $invoice->ref . '</td>';
        print '<td class="center">' . dol_print_date($invoice->datef, 'day') . '</td>';
        print '<td class="center">' . ($invoice->nom_client ?: 'N/A') . '</td>';
        print '<td class="right">' . price($invoice->total_ht) . '</td>';
        print '<td class="center">' . $retard_text . '</td>';
        print '</tr>';
    }
}

print '</table>';
print '</div>';

llxFooter();
$db->close();

/**
 * Retourne le CA HT total de l'année à partir des factures validées et payées.
 *
 * @param DoliDB $db
 * @param int $year
 * @return float
 */
function sig_get_total_turnover_for_year(DoliDB $db, int $year): float
{
	global $conf;

	// Calcul des timestamps (début et fin d'année)
	$firstday_timestamp = dol_mktime(0, 0, 0, 1, 1, $year);
	$lastday_timestamp = dol_mktime(23, 59, 59, 12, 31, $year);

	$sql = 'SELECT SUM(f.total_ht) as total_ht';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'facture as f';
	$sql .= ' WHERE f.entity IN ('.getEntity('invoice', 1).')';
	$sql .= ' AND f.fk_statut IN (1,2)'; // Seulement les factures Validées (statut 1) et Payées (statut 2)
	$sql .= ' AND f.type IN (0, 1)'; // Factures standard (0) et avoirs (1) - exclut les remplacements (2)
	$sql .= " AND f.datef BETWEEN '".$db->idate($firstday_timestamp)."' AND '".$db->idate($lastday_timestamp)."'";

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
 * Retourne le CA HT prévu de l'année à partir des devis signés.
 * Les devis signés correspondent au statut 2 dans Dolibarr.
 * Utilise la même logique que le tableau : date de livraison si renseignée, sinon mois courant.
 *
 * @param DoliDB $db
 * @param int $year
 * @return float
 */
function sig_get_total_expected_turnover_for_year(DoliDB $db, int $year): float
{
	global $conf;

	// Calculer le total en utilisant la même logique que le tableau
	$total = 0.0;
	
	// Somme tous les mois de l'année
	for ($m = 1; $m <= 12; $m++) {
		$total += sig_get_expected_turnover_for_month($db, $year, $m);
	}
	
	return (float) $total;
}

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
 * Retourne le montant des factures impayées pour un mois donné
 */
function sig_get_unpaid_invoices_for_month(DoliDB $db, int $year, int $month): float
{
	global $conf;

	$date_start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
	$last_day = date('t', mktime(0, 0, 0, $month, 1, $year));
	$date_end = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $last_day);

	$sql = 'SELECT SUM(f.total_ht) as total_ht';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'facture as f';
	$sql .= ' WHERE f.entity IN ('.getEntity('invoice', 1).')';
	$sql .= ' AND f.fk_statut = 1'; // Statut 1 = Validée mais non payée
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
 * Retourne le montant total des factures impayées pour une année
 */
function sig_get_total_unpaid_invoices(DoliDB $db, int $year): float
{
	global $conf;

	$firstday_timestamp = dol_mktime(0, 0, 0, 1, 1, $year);
	$lastday_timestamp = dol_mktime(23, 59, 59, 12, 31, $year);

	$sql = 'SELECT SUM(f.total_ht) as total_ht';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'facture as f';
	$sql .= ' WHERE f.entity IN ('.getEntity('invoice', 1).')';
	$sql .= ' AND f.fk_statut = 1'; // Statut 1 = Validée mais non payée
	$sql .= ' AND f.type IN (0, 1)';
	$sql .= " AND f.datef BETWEEN '".$db->idate($firstday_timestamp)."' AND '".$db->idate($lastday_timestamp)."'";

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
 * Retourne la liste des factures impayées
 */
function sig_get_unpaid_invoices_list(DoliDB $db, int $year): array
{
	global $conf;

	$firstday_year_timestamp = dol_mktime(0, 0, 0, 1, 1, $year);
	$lastday_year_timestamp = dol_mktime(23, 59, 59, 12, 31, $year);

	$sql = 'SELECT f.rowid, f.ref, f.datef, f.date_lim_reglement, f.total_ht, s.nom as nom_client';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'facture as f';
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe as s ON f.fk_soc = s.rowid';
	$sql .= ' WHERE f.entity IN ('.getEntity('invoice', 1).')';
	$sql .= ' AND f.fk_statut = 1'; // Statut 1 = Validée mais non payée
	$sql .= ' AND f.type IN (0, 1)';
	$sql .= " AND f.datef BETWEEN '".$db->idate($firstday_year_timestamp)."' AND '".$db->idate($lastday_year_timestamp)."'";
	$sql .= ' ORDER BY f.date_lim_reglement ASC, f.datef ASC';

	$invoices = array();
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			if ($obj) $invoices[] = $obj;
			$i++;
		}
		$db->free($resql);
	}
	return $invoices;
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
    
    return array(
        'encaissements' => $encaissements,
        'decaissements' => $decaissements
    );
}

/**
 * Fonction de diagnostic pour analyser les mouvements bancaires
 */
function sig_diagnostic_bank_movements($db, $bank_account_id, $year, $month) {
    $diagnostic = array();
    
    // 1. Compter toutes les écritures du compte
    $sql = 'SELECT COUNT(*) as nb_total, MIN(datev) as date_min, MAX(datev) as date_max, SUM(amount) as total_amount';
    $sql .= ' FROM '.MAIN_DB_PREFIX.'bank as b';
    $sql .= ' WHERE b.fk_account = '.(int)$bank_account_id;
    $sql .= ' AND b.entity IN ('.getEntity('bank_account').')';
    
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        if ($obj) {
            $diagnostic[] = "Total écritures compte: ".$obj->nb_total;
            $diagnostic[] = "Période: ".$obj->date_min." à ".$obj->date_max;
            $diagnostic[] = "Montant total: ".number_format($obj->total_amount, 2);
        }
        $db->free($resql);
    }
    
    // 2. Compter les écritures du mois spécifique
    $sql = 'SELECT COUNT(*) as nb_mois, SUM(amount) as total_mois';
    $sql .= ' FROM '.MAIN_DB_PREFIX.'bank as b';
    $sql .= ' WHERE b.fk_account = '.(int)$bank_account_id;
    $sql .= ' AND b.entity IN ('.getEntity('bank_account').')';
    $sql .= ' AND YEAR(b.datev) = '.(int)$year;
    $sql .= ' AND MONTH(b.datev) = '.(int)$month;
    
    // 2b. Diagnostic : voir tous les mois disponibles pour l'année
    $sql_months = 'SELECT DISTINCT MONTH(b.datev) as mois, COUNT(*) as nb FROM '.MAIN_DB_PREFIX.'bank as b';
    $sql_months .= ' WHERE b.fk_account = '.(int)$bank_account_id;
    $sql_months .= ' AND b.entity IN ('.getEntity('bank_account').')';
    $sql_months .= ' AND YEAR(b.datev) = '.(int)$year;
    $sql_months .= ' GROUP BY MONTH(b.datev) ORDER BY mois ASC';
    $resql_months = $db->query($sql_months);
    if ($resql_months) {
        $months_data = array();
        while ($obj_months = $db->fetch_object($resql_months)) {
            $months_data[] = $obj_months->mois.": ".$obj_months->nb." écritures";
        }
        if (!empty($months_data)) {
            $diagnostic[] = "Mois ".$year." disponibles: ".implode(", ", $months_data);
        }
        $db->free($resql_months);
    }
    
    // 2b. Diagnostic : voir toutes les années disponibles
    $sql_years = 'SELECT DISTINCT YEAR(b.datev) as annee, COUNT(*) as nb FROM '.MAIN_DB_PREFIX.'bank as b';
    $sql_years .= ' WHERE b.fk_account = '.(int)$bank_account_id;
    $sql_years .= ' AND b.entity IN ('.getEntity('bank_account').')';
    $sql_years .= ' GROUP BY YEAR(b.datev) ORDER BY annee DESC';
    $resql_years = $db->query($sql_years);
    if ($resql_years) {
        $years_data = array();
        while ($obj_years = $db->fetch_object($resql_years)) {
            $years_data[] = $obj_years->annee.": ".$obj_years->nb." écritures";
        }
        if (!empty($years_data)) {
            $diagnostic[] = "Années disponibles: ".implode(", ", $years_data);
        }
        $db->free($resql_years);
    }
    
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        if ($obj) {
            $diagnostic[] = "Écritures mois ".$month."/".$year.": ".$obj->nb_mois;
            $diagnostic[] = "Montant mois: ".number_format($obj->total_mois, 2);
        }
        $db->free($resql);
    }
    
    // 3. Vérifier les statuts des écritures
    $sql = 'SELECT fk_statut, COUNT(*) as nb FROM '.MAIN_DB_PREFIX.'bank as b';
    $sql .= ' WHERE b.fk_account = '.(int)$bank_account_id;
    $sql .= ' AND b.entity IN ('.getEntity('bank_account').')';
    $sql .= ' GROUP BY fk_statut ORDER BY fk_statut';
    
    $statuts = array();
    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $statuts[] = "Statut ".$obj->fk_statut.": ".$obj->nb;
        }
        $db->free($resql);
    }
    
    if (!empty($statuts)) {
        $diagnostic[] = "Répartition statuts: ".implode(", ", $statuts);
    }
    
    return implode(" | ", $diagnostic);
}

/**
 * Fonction de diagnostic pour identifier les bonnes tables bancaires
 */
function sig_diagnostic_bank_tables($db, $bank_account_id) {
    $diagnostic = array();
    
    // 1. Vérifier la table bank
    $sql = 'SELECT COUNT(*) as nb_bank FROM '.MAIN_DB_PREFIX.'bank as b';
    $sql .= ' WHERE b.fk_account = '.(int)$bank_account_id;
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        if ($obj) {
            $diagnostic[] = "Table bank: ".$obj->nb_bank." écritures";
        }
        $db->free($resql);
    }
    
    // 2. Vérifier la table bank_account_lines (si elle existe)
    $sql = 'SELECT COUNT(*) as nb_lines FROM '.MAIN_DB_PREFIX.'bank_account_lines as bal';
    $sql .= ' WHERE bal.fk_account = '.(int)$bank_account_id;
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        if ($obj) {
            $diagnostic[] = "Table bank_account_lines: ".$obj->nb_lines." écritures";
        }
        $db->free($resql);
    } else {
        $diagnostic[] = "Table bank_account_lines: n'existe pas";
    }
    
    // 3. Vérifier la table bank_class (si elle existe)
    $sql = 'SELECT COUNT(*) as nb_class FROM '.MAIN_DB_PREFIX.'bank_class as bc';
    $sql .= ' WHERE bc.fk_account = '.(int)$bank_account_id;
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        if ($obj) {
            $diagnostic[] = "Table bank_class: ".$obj->nb_class." écritures";
        }
        $db->free($resql);
    } else {
        $diagnostic[] = "Table bank_class: n'existe pas";
    }
    
    // 4. Lister toutes les tables qui contiennent "bank"
    $sql = "SHOW TABLES LIKE '".MAIN_DB_PREFIX."bank%'";
    $resql = $db->query($sql);
    if ($resql) {
        $tables = array();
        while ($obj = $db->fetch_object($resql)) {
            $tables[] = $obj->{'Tables_in_'.$db->database_name.' ('.MAIN_DB_PREFIX.'bank%)'};
        }
        if (!empty($tables)) {
            $diagnostic[] = "Tables bank disponibles: ".implode(", ", $tables);
        }
        $db->free($resql);
    }
    
    return implode(" | ", $diagnostic);
}

/**
 * Fonction de diagnostic pour analyser les filtres appliqués
 */
function sig_diagnostic_bank_filters($db, $bank_account_id, $year, $month) {
    $diagnostic = array();
    
    // 1. Test sans filtre d'entité
    $sql = 'SELECT COUNT(*) as nb_sans_entity, SUM(amount) as total_sans_entity';
    $sql .= ' FROM '.MAIN_DB_PREFIX.'bank as b';
    $sql .= ' WHERE b.fk_account = '.(int)$bank_account_id;
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        if ($obj) {
            $diagnostic[] = "Sans filtre entity: ".$obj->nb_sans_entity." écritures, total: ".number_format($obj->total_sans_entity, 2);
        }
        $db->free($resql);
    }
    
    // 2. Test avec filtre d'entité
    $sql = 'SELECT COUNT(*) as nb_avec_entity, SUM(amount) as total_avec_entity';
    $sql .= ' FROM '.MAIN_DB_PREFIX.'bank as b';
    $sql .= ' WHERE b.fk_account = '.(int)$bank_account_id;
    $sql .= ' AND b.entity IN ('.getEntity('bank_account').')';
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        if ($obj) {
            $diagnostic[] = "Avec filtre entity: ".$obj->nb_avec_entity." écritures, total: ".number_format($obj->total_avec_entity, 2);
        }
        $db->free($resql);
    }
    
    // 3. Test pour le mois spécifique sans filtre d'entité
    $sql = 'SELECT COUNT(*) as nb_mois_sans_entity, SUM(amount) as total_mois_sans_entity';
    $sql .= ' FROM '.MAIN_DB_PREFIX.'bank as b';
    $sql .= ' WHERE b.fk_account = '.(int)$bank_account_id;
    $sql .= ' AND YEAR(b.datev) = '.(int)$year;
    $sql .= ' AND MONTH(b.datev) = '.(int)$month;
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        if ($obj) {
            $diagnostic[] = "Mois ".$month."/".$year." sans entity: ".$obj->nb_mois_sans_entity." écritures, total: ".number_format($obj->total_mois_sans_entity, 2);
        }
        $db->free($resql);
    }
    
    // 4. Vérifier les valeurs d'entité
    $sql = 'SELECT DISTINCT b.entity FROM '.MAIN_DB_PREFIX.'bank as b';
    $sql .= ' WHERE b.fk_account = '.(int)$bank_account_id;
    $sql .= ' LIMIT 5';
    $resql = $db->query($sql);
    if ($resql) {
        $entities = array();
        while ($obj = $db->fetch_object($resql)) {
            $entities[] = $obj->entity;
        }
        if (!empty($entities)) {
            $diagnostic[] = "Entités trouvées: ".implode(", ", $entities);
        }
        $db->free($resql);
    }
    
    // 5. Vérifier getEntity('bank_account')
    $entity_filter = getEntity('bank_account');
    $diagnostic[] = "getEntity('bank_account') retourne: ".$entity_filter;
    
    return implode(" | ", $diagnostic);
}

/**
 * Fonction de diagnostic pour analyser les marges prévues
 */
function sig_diagnostic_expected_margins($db, $year, $month) {
    $diagnostic = array();
    
    $current_month = (int) date('n'); // Mois actuel (1-12)
    $current_year = (int) date('Y');  // Année actuelle
    
    // 1. Vérifier si la table propaldet a les champs de marge
    $sql = 'SHOW COLUMNS FROM '.MAIN_DB_PREFIX.'propaldet LIKE "marge_tx"';
    $resql = $db->query($sql);
    $has_marge_tx = false;
    if ($resql) {
        $has_marge_tx = $db->num_rows($resql) > 0;
        $db->free($resql);
    }
    
    $sql = 'SHOW COLUMNS FROM '.MAIN_DB_PREFIX.'propaldet LIKE "marque_tx"';
    $resql = $db->query($sql);
    $has_marque_tx = false;
    if ($resql) {
        $has_marque_tx = $db->num_rows($resql) > 0;
        $db->free($resql);
    }
    
    // Récupérer le taux de marge configuré
    $margin_rate = getDolGlobalString('SIG_MARGIN_RATE');
    if (empty($margin_rate)) $margin_rate = 20;
    $diagnostic[] = "Taux de marge configuré: ".$margin_rate."%";
    
    // Récupérer le CA prévu pour ce mois
    $ca_prevue = sig_get_expected_turnover_for_month($db, $year, $month);
    $marge_calculee = $ca_prevue * ((float)$margin_rate / 100);
    $diagnostic[] = "CA prévu mois ".$month."/".$year.": ".number_format($ca_prevue, 2)." → Marge: ".number_format($marge_calculee, 2);
    
    // 2. Compter les devis signés pour ce mois
    $sql = 'SELECT COUNT(DISTINCT p.rowid) as nb_propals, SUM(p.total_ht) as total_ca';
    $sql .= ' FROM '.MAIN_DB_PREFIX.'propal as p';
    $sql .= ' WHERE p.entity IN ('.getEntity('propal', 1).')';
    $sql .= ' AND p.fk_statut = 2'; // Statut 2 = Devis signé/accepté
    $sql .= ' AND (';
    $sql .= ' (p.date_livraison IS NOT NULL AND YEAR(p.date_livraison) = '.(int)$year.' AND MONTH(p.date_livraison) = '.(int)$month.')';
    $sql .= ' OR (p.date_livraison IS NULL AND '.$current_month.' = '.(int)$month.' AND '.$current_year.' = '.(int)$year.')';
    $sql .= ' )';
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        if ($obj) {
            $diagnostic[] = "Devis signés mois ".$month."/".$year.": ".$obj->nb_propals." (CA: ".number_format($obj->total_ca, 2).")";
        }
        $db->free($resql);
    }
    
    return implode(" | ", $diagnostic);
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
 * Retourne la marge prévue avec délai de paiement pour un mois donné
 * Calcule la marge des livraisons qui seront payées ce mois-ci (en tenant compte du délai)
 */
function sig_get_expected_margin_with_delay_for_month($db, $year, $month) {
    global $conf;
    
    // Vérifier que les paramètres sont valides
    if (empty($month) || !is_numeric($month) || $month < 1 || $month > 12) {
        return 0.0;
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