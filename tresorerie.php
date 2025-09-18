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
    $solde_precedent = 0;
    
    for ($month = 1; $month <= 12; $month++) {
        // Calculer le solde cumulé jusqu'à la fin de ce mois
        $solde_fin_mois = sig_get_bank_balance_at_date($db, $selected_bank_account, $year, $month, 31);
        
        // Le solde de début est le solde de fin du mois précédent
        if ($month == 1) {
            // Pour janvier, prendre le solde à la fin de décembre de l'année précédente
            $solde_debut_mois = sig_get_bank_balance_at_date($db, $selected_bank_account, $year - 1, 12, 31);
        } else {
            $solde_debut_mois = $solde_precedent;
        }
        
        // Récupérer les mouvements détaillés du mois
        $mouvements_details = sig_get_bank_movements_details_for_month($db, $selected_bank_account, $year, $month);
        $encaissements_mois = $mouvements_details['encaissements'];
        $decaissements_mois = abs($mouvements_details['decaissements']); // Valeur absolue pour l'affichage
        
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
        print '<td style="text-align: right; color: #2E7D32;">'.($encaissements_mois > 0 ? '+'.price($encaissements_mois) : price($encaissements_mois)).'</td>';
        print '<td style="text-align: right; color: #C62828;">'.($decaissements_mois > 0 ? '-'.price($decaissements_mois) : price($decaissements_mois)).'</td>';
        print '<td style="text-align: right; font-weight: bold;">'.price($solde_fin_mois).'</td>';
        
        // Affichage de la variation avec couleur
        $variation_color = $variation_mois >= 0 ? '#2E7D32' : '#C62828';
        $variation_sign = $variation_mois > 0 ? '+' : '';
        print '<td style="text-align: right; font-weight: bold; color: '.$variation_color.';">'.$variation_sign.price($variation_mois).'</td>';
        
        print '</tr>';
        
        // Sauvegarder le solde pour le mois suivant
        $solde_precedent = $solde_fin_mois;
    }

    // Ligne de total
    $solde_debut_annee = sig_get_bank_balance_at_date($db, $selected_bank_account, $year - 1, 12, 31);
    $solde_fin_annee = $solde_precedent;
    $variation_totale = $solde_fin_annee - $solde_debut_annee;
    
    print '<tr class="liste_total">';
    print '<td><strong>TOTAL ANNÉE '.$year.'</strong></td>';
    print '<td style="text-align: right; font-weight: bold;">'.price($solde_debut_annee).'</td>';
    print '<td style="text-align: right; font-weight: bold; color: #2E7D32;">+'.price($total_encaissements).'</td>';
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
 *
 * @param DoliDB $db
 * @param int $year
 * @return float
 */
function sig_get_total_expected_turnover_for_year(DoliDB $db, int $year): float
{
	global $conf;

	// Calcul des timestamps (début et fin d'année)
	$firstday_timestamp = dol_mktime(0, 0, 0, 1, 1, $year);
	$lastday_timestamp = dol_mktime(23, 59, 59, 12, 31, $year);

	$sql = 'SELECT SUM(p.total_ht) as total_ht';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'propal as p';
	$sql .= ' WHERE p.entity IN ('.getEntity('propal', 1).')';
	$sql .= ' AND p.fk_statut = 2'; // Statut 2 = Devis signé/accepté
	$sql .= " AND p.datep BETWEEN '".$db->idate($firstday_timestamp)."' AND '".$db->idate($lastday_timestamp)."'";

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
	$sql .= ' AND b.entity IN ('.getEntity('bank_account').')';
	$sql .= " AND b.datev <= '".$db->escape($date_limit)."'";

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
	$sql .= ' AND b.entity IN ('.getEntity('bank_account', 1).')';
	$sql .= " AND DATE(b.datev) >= '".$db->escape($date_start)."'";
	$sql .= " AND DATE(b.datev) <= '".$db->escape($date_end)."'";

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
 */
function sig_get_recent_bank_movements(DoliDB $db, int $account_id, int $limit = 10): array
{
	global $conf;

	$sql = 'SELECT b.rowid, b.datev, b.amount, b.label, b.num_chq';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'bank as b';
	$sql .= ' WHERE b.fk_account = '.(int)$account_id;
	$sql .= ' AND b.entity IN ('.getEntity('bank_account', 1).')';
	$sql .= ' ORDER BY b.datev DESC, b.rowid DESC';
	$sql .= ' LIMIT '.(int)$limit;

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
    $sql_encaissements .= " AND b.entity IN (".getEntity('bank_account').")";
    
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
    $sql_decaissements .= " AND b.entity IN (".getEntity('bank_account').")";
    
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