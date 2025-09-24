<?php
/* Copyright (C) 2025
 * Module SIG - Tableau de bord simple
 */

require_once __DIR__ . '/../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

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

// Gestion de la sauvegarde des objectifs
if (GETPOST('action') == 'save_objectives') {
    $objectives = array();
    for ($m = 1; $m <= 12; $m++) {
        $obj_value = GETPOSTFLOAT('objective_'.$m);
        if ($obj_value > 0) {
            $objectives[$m] = $obj_value;
        }
    }
    
    // Sauvegarder en JSON dans la configuration
    $config_key = 'SIG_OBJECTIVES_'.$year;
    $objectives_json = json_encode($objectives);
    dolibarr_set_const($db, $config_key, $objectives_json, 'chaine', 0, '', $conf->entity);
    
    setEventMessages("Objectifs sauvegardés pour l'année ".$year, null, 'mesgs');
}

llxHeader('', 'Tableau de bord SIG');

print load_fiche_titre('🏠 Tableau de bord - Suivi CA vs Objectifs', '', 'fa-tachometer-alt');

// Menu de navigation
print '<div class="tabBar" style="margin-bottom: 20px;">';
print '<a class="tabTitle" style="color: #666; background: #f0f0f0; padding: 8px 15px; margin-right: 5px; text-decoration: none; border-radius: 3px;" href="'.$_SERVER['PHP_SELF'].'?year='.$year.'">🏠 Tableau de bord</a>';
print '<a class="tabTitle" style="color: #666; background: #fff; padding: 8px 15px; margin-right: 5px; text-decoration: none; border-radius: 3px; border: 1px solid #ddd;" href="ca.php?year='.$year.'">📊 Pilotage CA</a>';
print '<a class="tabTitle" style="color: #666; background: #fff; padding: 8px 15px; margin-right: 5px; text-decoration: none; border-radius: 3px; border: 1px solid #ddd;" href="tresorerie.php?year='.$year.'">💰 Trésorerie</a>';
print '<a class="tabTitle" style="color: #666; background: #fff; padding: 8px 15px; margin-right: 5px; text-decoration: none; border-radius: 3px; border: 1px solid #ddd;" href="sig.php?year='.$year.'">📈 SIG</a>';
print '</div>';

print '<form method="GET" action="'.$_SERVER['PHP_SELF'].'">';
print $langs->trans('Year') . ': ';
print '<input type="number" name="year" value="'.$year.'" min="2000" max="2100" /> ';
print '<input class="button" type="submit" value="'.$langs->trans('Refresh').'" />';
print '</form>';

print '<br />';

// Récupérer les objectifs sauvegardés
$config_key = 'SIG_OBJECTIVES_'.$year;
$objectives_json = getDolGlobalString($config_key);
$objectives = array();
if (!empty($objectives_json)) {
    $objectives = json_decode($objectives_json, true);
    if (!is_array($objectives)) $objectives = array();
}

print '<div class="div-table-responsive">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<th style="text-align:left; width: 150px;">Mois</th>';
for ($m = 1; $m <= 12; $m++) {
    print '<th style="text-align:center">'.dol_print_date(dol_mktime(12, 0, 0, $m, 1, $year), '%b').'</th>';
}
print '<th style="text-align:right; width: 120px;">Total</th>';
print '</tr>';

// Ligne CA Réalisé
print '<tr>';
print '<td style="font-weight: bold; color: #2E7D32; background-color: #e8f5e8;">CA Réalisé</td>';
$total_realise = 0;
$monthly_realise = array();
for ($m = 1; $m <= 12; $m++) {
    $ca_mois = sig_get_turnover_for_month($db, $year, $m);
    $total_realise += $ca_mois;
    $monthly_realise[$m] = $ca_mois;
    
    $cell_style = $ca_mois > 0 ? 'background-color: #f0f8f0;' : 'background-color: #fafafa;';
    print '<td style="text-align:right; padding:8px; '.$cell_style.'">'.price($ca_mois).'</td>';
}
print '<td style="text-align:right; padding:8px; font-weight: bold; background-color: #c8e6c9; color: #1B5E20;">'.price($total_realise).'</td>';
print '</tr>';

// Ligne Objectifs (formulaire)
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?year='.$year.'">';
print '<input type="hidden" name="action" value="save_objectives">';
print '<tr>';
print '<td style="font-weight: bold; color: #1565C0; background-color: #e3f2fd;">Objectif</td>';
$total_objectif = 0;
for ($m = 1; $m <= 12; $m++) {
    $objectif_mois = isset($objectives[$m]) ? $objectives[$m] : 0;
    $total_objectif += $objectif_mois;
    
    print '<td style="text-align:center; padding:4px; background-color: #f0f4ff;">';
    print '<input type="number" name="objective_'.$m.'" value="'.($objectif_mois > 0 ? $objectif_mois : '').'" ';
    print 'style="width: 80px; text-align: right; border: 1px solid #ddd; padding: 2px;" step="0.01" min="0">';
    print '</td>';
}
print '<td style="text-align:right; padding:8px; font-weight: bold; background-color: #bbdefb; color: #0D47A1;">'.price($total_objectif).'</td>';
print '</tr>';

// Bouton de sauvegarde
print '<tr>';
print '<td colspan="14" style="text-align: center; padding: 10px; background-color: #f8f8f8;">';
print '<input type="submit" class="button" value="💾 Sauvegarder les objectifs">';
print '</td>';
print '</tr>';
print '</form>';

// Ligne Écart (Réalisé - Objectif)
print '<tr style="border-top: 2px solid #333;">';
print '<td style="font-weight: bold; color: #333; background-color: #fff3e0;">Écart (Réalisé - Objectif)</td>';
$total_ecart = 0;
for ($m = 1; $m <= 12; $m++) {
    $ca_mois = $monthly_realise[$m];
    $objectif_mois = isset($objectives[$m]) ? $objectives[$m] : 0;
    $ecart_mois = $ca_mois - $objectif_mois;
    $total_ecart += $ecart_mois;
    
    // Style selon l'écart
    $cell_style = '';
    $text_color = '#333';
    if ($objectif_mois > 0) {
        if ($ecart_mois > 0) {
            $cell_style = 'background-color: #e8f5e8; border-left: 3px solid #4CAF50;';
            $text_color = '#2E7D32';
        } elseif ($ecart_mois < 0) {
            $cell_style = 'background-color: #ffebee; border-left: 3px solid #F44336;';
            $text_color = '#C62828';
        } else {
            $cell_style = 'background-color: #fff3e0;';
        }
    } else {
        $cell_style = 'background-color: #f5f5f5;';
        $text_color = '#999';
    }
    
    print '<td style="text-align:right; padding:8px; font-weight: bold; color: '.$text_color.'; '.$cell_style.'">';
    if ($objectif_mois > 0) {
        print price($ecart_mois);
    } else {
        print '<em>-</em>';
    }
    print '</td>';
}

// Style du total selon l'écart global
$total_cell_style = '';
$total_text_color = '#333';
if ($total_objectif > 0) {
    if ($total_ecart > 0) {
        $total_cell_style = 'background-color: #c8e6c9; border: 2px solid #4CAF50;';
        $total_text_color = '#1B5E20';
    } elseif ($total_ecart < 0) {
        $total_cell_style = 'background-color: #ffcdd2; border: 2px solid #F44336;';
        $total_text_color = '#B71C1C';
    } else {
        $total_cell_style = 'background-color: #fff3e0; border: 2px solid #FF9800;';
    }
} else {
    $total_cell_style = 'background-color: #f5f5f5;';
    $total_text_color = '#999';
}

print '<td style="text-align:right; padding:8px; font-weight: bold; color: '.$total_text_color.'; '.$total_cell_style.'">';
if ($total_objectif > 0) {
    print price($total_ecart);
} else {
    print '<em>-</em>';
}
print '</td>';
print '</tr>';

// Ligne Taux de réalisation (en %)
if ($total_objectif > 0) {
    print '<tr style="background-color: #fafafa; border-top: 1px solid #ddd;">';
    print '<td style="font-weight: bold; color: #666; font-style: italic;">Taux de réalisation</td>';
    for ($m = 1; $m <= 12; $m++) {
        $ca_mois = $monthly_realise[$m];
        $objectif_mois = isset($objectives[$m]) ? $objectives[$m] : 0;
        
        if ($objectif_mois > 0) {
            $taux = ($ca_mois / $objectif_mois) * 100;
            $color = $taux >= 100 ? '#2E7D32' : ($taux >= 80 ? '#F57C00' : '#C62828');
            print '<td style="text-align:right; padding:8px; color: '.$color.'; font-weight: bold; font-style: italic;">'.number_format($taux, 0).'%</td>';
        } else {
            print '<td style="text-align:center; padding:8px; color: #999; font-style: italic;">-</td>';
        }
    }
    
    $taux_total = ($total_realise / $total_objectif) * 100;
    $color_total = $taux_total >= 100 ? '#1B5E20' : ($taux_total >= 80 ? '#E65100' : '#B71C1C');
    print '<td style="text-align:right; padding:8px; font-weight: bold; color: '.$color_total.'; font-style: italic; font-size: 14px;">'.number_format($taux_total, 1).'%</td>';
    print '</tr>';
}

print '</table>';
print '</div>';

llxFooter();
$db->close();

/**
 * Retourne le CA HT du mois à partir des factures validées et payées.
 */
function sig_get_turnover_for_month(DoliDB $db, int $year, int $month): float
{
	global $conf;
	$date_start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
	$last_day = date('t', mktime(0, 0, 0, $month, 1, $year));
	$date_end = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $last_day);

	$sql = 'SELECT SUM(f.total_ht) as total_ht';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'facture as f';
	$sql .= ' WHERE f.entity IN ('.getEntity('invoice', 1).')';
	$sql .= ' AND f.fk_statut IN (1,2)';
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