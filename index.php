<?php
/* Copyright (C) 2025
 * Module SIG - Pilotage de trésorerie et SIG
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

llxHeader('', $langs->trans('SigDashboard'));

print load_fiche_titre($langs->trans('SigDashboard'), '', 'generic');

print '<form method="GET" action="'.$_SERVER['PHP_SELF'].'">';
print $langs->trans('Year') . ': ';
print '<input type="number" name="year" value="'.$year.'" min="2000" max="2100" /> ';
print '<input class="button" type="submit" value="'.$langs->trans('Refresh').'" />';
print '</form>';

print '<br />';

// SECTION RÉSUMÉ : Chiffre d'affaires réalisé et prévu
$total_year_ca = sig_get_total_turnover_for_year($db, $year);
$total_year_ca_prevu = sig_get_total_expected_turnover_for_year($db, $year);

print '<div style="display: flex; gap: 20px; margin-bottom: 20px;">';

// Chiffre d'affaires réalisé
print '<div class="info-box" style="background: #e8f5e8; border: 1px solid #4CAF50; border-radius: 5px; padding: 15px; flex: 1;">';
print '<div style="text-align: center;">';
print '<h2 style="margin: 0; color: #2E7D32;">CA Réalisé '.$year.'</h2>';
print '<div style="font-size: 24px; font-weight: bold; color: #1B5E20; margin-top: 10px;">'.price($total_year_ca).'</div>';
print '<div style="font-size: 14px; color: #666; margin-top: 5px;">Factures validées et payées</div>';
print '</div>';
print '</div>';

// Chiffre d'affaires prévu
print '<div class="info-box" style="background: #e3f2fd; border: 1px solid #2196F3; border-radius: 5px; padding: 15px; flex: 1;">';
print '<div style="text-align: center;">';
print '<h2 style="margin: 0; color: #1565C0;">CA Prévu '.$year.'</h2>';
print '<div style="font-size: 24px; font-weight: bold; color: #0D47A1; margin-top: 10px;">'.price($total_year_ca_prevu).'</div>';
print '<div style="font-size: 14px; color: #666; margin-top: 5px;">Devis signés</div>';
print '</div>';
print '</div>';

print '</div>';

// SECTION 1: Tableau du Chiffre d'Affaires par mois
print load_fiche_titre($langs->trans('SigCurrentTurnover'), '', 'generic');

// Test de diagnostic pour vérifier les données
$diagnostic_info = sig_diagnostic_invoices($db, $year);
if (!empty($diagnostic_info)) {
	echo "<!-- DIAGNOSTIC: ".$diagnostic_info." -->\n";
}

print '<div class="div-table-responsive">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<th style="text-align:left; width: 120px;">Type</th>';
for ($m = 1; $m <= 12; $m++) {
	print '<th style="text-align:center">'.dol_print_date(dol_mktime(12, 0, 0, $m, 1, $year), '%B').'</th>';
}
print '<th style="text-align:right; width: 120px;">Total</th>';
print '</tr>';

// Ligne CA Réalisé (factures)
print '<tr>';
print '<td style="font-weight: bold; color: #2E7D32;">CA Réalisé</td>';
$total_realise = 0;
for ($m = 1; $m <= 12; $m++) {
	$total_ht = sig_get_turnover_for_month($db, $year, $m);
	$total_realise += $total_ht;
	$cell_style = $total_ht > 0 ? 'background-color: #f0f8f0;' : '';
	print '<td style="text-align:right; padding:8px; '.$cell_style.'">'.price($total_ht).'</td>';
}
print '<td style="text-align:right; padding:8px; font-weight: bold; background-color: #e8f5e8;">'.price($total_realise).'</td>';
print '</tr>';

// Ligne CA Prévu (devis signés)
print '<tr>';
print '<td style="font-weight: bold; color: #1565C0;">CA Prévu</td>';
$total_prevu = 0;
for ($m = 1; $m <= 12; $m++) {
	$total_ht_prevu = sig_get_expected_turnover_for_month($db, $year, $m);
	$total_prevu += $total_ht_prevu;
	$cell_style = $total_ht_prevu > 0 ? 'background-color: #f0f4ff;' : '';
	print '<td style="text-align:right; padding:8px; '.$cell_style.'">'.price($total_ht_prevu).'</td>';
}
print '<td style="text-align:right; padding:8px; font-weight: bold; background-color: #e3f2fd;">'.price($total_prevu).'</td>';
print '</tr>';

// Ligne Total (CA Réalisé + CA Prévu)
print '<tr style="border-top: 2px solid #ddd;">';
print '<td style="font-weight: bold; color: #333;">Total (Réalisé + Prévu)</td>';
$total_global = 0;
for ($m = 1; $m <= 12; $m++) {
	$total_ht_realise = sig_get_turnover_for_month($db, $year, $m);
	$total_ht_prevu = sig_get_expected_turnover_for_month($db, $year, $m);
	$total_mois = $total_ht_realise + $total_ht_prevu;
	$total_global += $total_mois;
	
	$cell_style = '';
	if ($total_mois > 0) {
		$cell_style = 'background-color: #fff3e0; border-left: 3px solid #FF9800;';
	}
	print '<td style="text-align:right; padding:8px; font-weight: bold; '.$cell_style.'">'.price($total_mois).'</td>';
}
print '<td style="text-align:right; padding:8px; font-weight: bold; background-color: #fff3e0; border: 2px solid #FF9800;">'.price($total_global).'</td>';
print '</tr>';

// Ligne CA Année N-1 (année précédente)
$year_previous = $year - 1;
print '<tr style="border-top: 1px solid #ccc; background-color: #fafafa;">';
print '<td style="font-weight: bold; color: #666; font-style: italic;">CA '.$year_previous.' (référence)</td>';
$total_previous_year = 0;
for ($m = 1; $m <= 12; $m++) {
	// Essayer de récupérer les données réelles de l'année précédente
	$total_ht_previous = sig_get_turnover_for_month($db, $year_previous, $m);
	
	// Si pas de données réelles, utiliser les données manuelles de configuration
	if ($total_ht_previous == 0) {
		$total_ht_previous = sig_get_manual_turnover_for_month($year_previous, $m);
	}
	
	$total_previous_year += $total_ht_previous;
	$cell_style = $total_ht_previous > 0 ? 'background-color: #f8f8f8;' : '';
	print '<td style="text-align:right; padding:8px; color: #666; font-style: italic; '.$cell_style.'">'.price($total_ht_previous).'</td>';
}
print '<td style="text-align:right; padding:8px; font-weight: bold; background-color: #f8f8f8; color: #666; font-style: italic;">'.price($total_previous_year).'</td>';
print '</tr>';

// Ligne Différence (Année N - Année N-1)
print '<tr style="border-top: 2px solid #333; background-color: #f0f0f0;">';
print '<td style="font-weight: bold; color: #333;">Différence ('.$year.' - '.$year_previous.')</td>';
$total_difference = 0;
$monthly_current_data = array(); // Pour le graphique
$monthly_previous_data = array(); // Pour le graphique
$monthly_labels = array(); // Pour le graphique

for ($m = 1; $m <= 12; $m++) {
	// Récupérer les données de l'année actuelle
	$total_ht_current_realise = sig_get_turnover_for_month($db, $year, $m);
	$total_ht_current_prevu = sig_get_expected_turnover_for_month($db, $year, $m);
	$total_current_month = $total_ht_current_realise + $total_ht_current_prevu;
	
	// Récupérer les données de l'année précédente
	$total_ht_previous = sig_get_turnover_for_month($db, $year_previous, $m);
	if ($total_ht_previous == 0) {
		$total_ht_previous = sig_get_manual_turnover_for_month($year_previous, $m);
	}
	
	// Calculer la différence
	$difference_month = $total_current_month - $total_ht_previous;
	$total_difference += $difference_month;
	
	// Stocker pour le graphique
	$monthly_current_data[] = round($total_current_month, 2);
	$monthly_previous_data[] = round($total_ht_previous, 2);
	$monthly_labels[] = dol_print_date(dol_mktime(0, 0, 0, $m, 1, $year), '%b');
	
	// Style selon la différence
	$cell_style = '';
	$text_color = '#333';
	if ($difference_month > 0) {
		$cell_style = 'background-color: #e8f5e8; border-left: 3px solid #4CAF50;';
		$text_color = '#2E7D32';
	} elseif ($difference_month < 0) {
		$cell_style = 'background-color: #ffebee; border-left: 3px solid #F44336;';
		$text_color = '#C62828';
	}
	
	print '<td style="text-align:right; padding:8px; font-weight: bold; color: '.$text_color.'; '.$cell_style.'">'.price($difference_month).'</td>';
}

// Style du total selon la différence globale
$total_cell_style = '';
$total_text_color = '#333';
if ($total_difference > 0) {
	$total_cell_style = 'background-color: #c8e6c9; border: 2px solid #4CAF50;';
	$total_text_color = '#1B5E20';
} elseif ($total_difference < 0) {
	$total_cell_style = 'background-color: #ffcdd2; border: 2px solid #F44336;';
	$total_text_color = '#B71C1C';
}

print '<td style="text-align:right; padding:8px; font-weight: bold; color: '.$total_text_color.'; '.$total_cell_style.'">'.price($total_difference).'</td>';
print '</tr>';

print '</table>';
print '</div>';

// GRAPHIQUE COMPARATIF
print '<br>';
print load_fiche_titre('Comparaison '.$year.' vs '.$year_previous, '', 'fa-chart-line');

print '<div style="margin-bottom: 30px; height: 400px;">';
print '<canvas id="comparisonChart"></canvas>';
print '</div>';

// Script JavaScript pour le graphique comparatif
?>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Vérifier que Chart.js est chargé et que l'élément existe
document.addEventListener('DOMContentLoaded', function() {
    const canvasElement = document.getElementById('comparisonChart');
    if (!canvasElement) {
        console.error('Canvas comparisonChart non trouvé');
        return;
    }
    
    if (typeof Chart === 'undefined') {
        console.error('Chart.js non chargé');
        return;
    }
    
    const ctxComparison = canvasElement.getContext('2d');
    const comparisonChart = new Chart(ctxComparison, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($monthly_labels); ?>,
        datasets: [
            {
                label: 'CA <?php echo $year; ?> (Réalisé + Prévu)',
                data: <?php echo json_encode($monthly_current_data); ?>,
                borderColor: '#2196F3',
                backgroundColor: 'rgba(33, 150, 243, 0.1)',
                borderWidth: 3,
                fill: false,
                tension: 0.4,
                pointBackgroundColor: '#2196F3',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8
            },
            {
                label: 'CA <?php echo $year_previous; ?> (Référence)',
                data: <?php echo json_encode($monthly_previous_data); ?>,
                borderColor: '#666666',
                backgroundColor: 'rgba(102, 102, 102, 0.1)',
                borderWidth: 2,
                borderDash: [5, 5],
                fill: false,
                tension: 0.4,
                pointBackgroundColor: '#666666',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            title: {
                display: true,
                text: 'Évolution comparative du chiffre d\'affaires',
                font: {
                    size: 16,
                    weight: 'bold'
                }
            },
            legend: {
                display: true,
                position: 'bottom'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        label += new Intl.NumberFormat('fr-FR', {
                            style: 'currency',
                            currency: 'EUR',
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 0
                        }).format(context.parsed.y);
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
                    }
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        },
        interaction: {
            intersect: false,
            mode: 'index'
        }
    }
});
});
</script>
<?php



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
 * Retourne le CA HT manuel pour un mois et une année donnés (données de configuration).
 *
 * @param int $year
 * @param int $month
 * @return float
 */
function sig_get_manual_turnover_for_month(int $year, int $month): float
{
	// Récupérer la configuration pour cette année
	$config_key = 'SIG_MANUAL_CA_'.$year;
	$manual_data = getDolGlobalString($config_key);
	
	if (empty($manual_data)) {
		return 0.0;
	}
	
	// Les données sont stockées sous format JSON : {"1": 1000, "2": 1500, ...}
	$data_array = json_decode($manual_data, true);
	if (!is_array($data_array) || !isset($data_array[(string)$month])) {
		return 0.0;
	}
	
	return (float) $data_array[(string)$month];
}

/**
 * Retourne le CA HT du mois à partir des factures validées et payées.
 * Les factures de type avoir (type=1) sont incluses.
 * Seules les factures au statut Validée (fk_statut=1) ou Payée (fk_statut=2) sont prises en compte.
 *
 * @param DoliDB $db
 * @param int $year
 * @param int $month
 * @return float
 */
function sig_get_turnover_for_month(DoliDB $db, int $year, int $month): float
{
	global $conf;

	// Méthode plus robuste pour les calculs de dates
	// Premier jour du mois à 00:00:00
	$date_start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
	
	// Dernier jour du mois à 23:59:59
	$last_day = date('t', mktime(0, 0, 0, $month, 1, $year)); // Nombre de jours dans le mois
	$date_end = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $last_day);

	$sql = 'SELECT SUM(f.total_ht) as total_ht';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'facture as f';
	$sql .= ' WHERE f.entity IN ('.getEntity('invoice', 1).')';
	$sql .= ' AND f.fk_statut IN (1,2)'; // Seulement les factures Validées (statut 1) et Payées (statut 2)
	$sql .= ' AND f.type IN (0, 1)'; // Factures standard (0) et avoirs (1) - exclut les remplacements (2)
	$sql .= " AND f.datef >= '".$db->escape($date_start)."'";
	$sql .= " AND f.datef <= '".$db->escape($date_end)."'";

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
 * Fonction de diagnostic pour analyser les données de factures
 *
 * @param DoliDB $db
 * @param int $year
 * @return string
 */
function sig_diagnostic_invoices(DoliDB $db, int $year): string
{
	global $conf;
	
	$diagnostic = array();
	
	// 1. Compter toutes les factures de l'année
	$sql = 'SELECT COUNT(*) as nb_total, MIN(datef) as date_min, MAX(datef) as date_max';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'facture as f';
	$sql .= ' WHERE f.entity IN ('.getEntity('invoice', 1).')';
	$sql .= ' AND YEAR(f.datef) = '.(int)$year;
	
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj) {
			$diagnostic[] = "Total factures ".$year.": ".$obj->nb_total;
			$diagnostic[] = "Période: ".$obj->date_min." à ".$obj->date_max;
		}
		$db->free($resql);
	}
	
	// 2. Compter les factures validées/payées
	$sql = 'SELECT COUNT(*) as nb_valid, SUM(total_ht) as total_ht';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'facture as f';
	$sql .= ' WHERE f.entity IN ('.getEntity('invoice', 1).')';
	$sql .= ' AND f.fk_statut IN (1,2)';
	$sql .= ' AND f.type IN (0, 1)';
	$sql .= ' AND YEAR(f.datef) = '.(int)$year;
	
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj) {
			$diagnostic[] = "Factures validées/payées: ".$obj->nb_valid." (Total HT: ".number_format($obj->total_ht, 2).")";
		}
		$db->free($resql);
	}
	
	// 3. Répartition par statut
	$sql = 'SELECT fk_statut, COUNT(*) as nb FROM '.MAIN_DB_PREFIX.'facture as f';
	$sql .= ' WHERE f.entity IN ('.getEntity('invoice', 1).')';
	$sql .= ' AND YEAR(f.datef) = '.(int)$year;
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
		$diagnostic[] = "Répartition: ".implode(", ", $statuts);
	}
	
	return implode(" | ", $diagnostic);
} 