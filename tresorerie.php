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

// SECTION: Évolution mensuelle de la trésorerie
print load_fiche_titre('Évolution de la Trésorerie par Mois', '', 'fa-chart-line');

print '<div class="div-table-responsive">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<th style="text-align:left; width: 120px;">Indicateur</th>';
for ($m = 1; $m <= 12; $m++) {
	print '<th style="text-align:center">'.dol_print_date(dol_mktime(12, 0, 0, $m, 1, $year), '%B').'</th>';
}
print '<th style="text-align:right; width: 120px;">Total</th>';
print '</tr>';

// Ligne Encaissements (factures payées)
print '<tr>';
print '<td style="font-weight: bold; color: #2E7D32;">Encaissements</td>';
$total_encaissements = 0;
for ($m = 1; $m <= 12; $m++) {
	$encaissements = sig_get_paid_invoices_for_month($db, $year, $m);
	$total_encaissements += $encaissements;
	$cell_style = $encaissements > 0 ? 'background-color: #f0f8f0;' : '';
	print '<td style="text-align:right; padding:8px; '.$cell_style.'">'.price($encaissements).'</td>';
}
print '<td style="text-align:right; padding:8px; font-weight: bold; background-color: #e8f5e8;">'.price($total_encaissements).'</td>';
print '</tr>';

// Ligne À encaisser (factures validées non payées)
print '<tr>';
print '<td style="font-weight: bold; color: #F57C00;">À Encaisser</td>';
$total_a_encaisser = 0;
for ($m = 1; $m <= 12; $m++) {
	$a_encaisser = sig_get_unpaid_invoices_for_month($db, $year, $m);
	$total_a_encaisser += $a_encaisser;
	$cell_style = $a_encaisser > 0 ? 'background-color: #fff8f0;' : '';
	print '<td style="text-align:right; padding:8px; '.$cell_style.'">'.price($a_encaisser).'</td>';
}
print '<td style="text-align:right; padding:8px; font-weight: bold; background-color: #fff3e0;">'.price($total_a_encaisser).'</td>';
print '</tr>';

print '</table>';
print '</div>';

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