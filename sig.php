<?php
/* Copyright (C) 2025
 * Module SIG - Page Soldes Intermédiaires de Gestion
 */

require_once __DIR__ . '/../../main.inc.php';
require_once __DIR__ . '/lib/sig_functions.php';

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

llxHeader('', 'SIG - '.$langs->trans('SigDashboard'));

print load_fiche_titre('Soldes Intermédiaires de Gestion (SIG)', '', 'fa-calculator');

// Menu de navigation
print '<div class="tabBar" style="margin-bottom: 20px;">';
print '<a class="tabTitle" style="color: #666; background: #fff; padding: 8px 15px; margin-right: 5px; text-decoration: none; border-radius: 3px; border: 1px solid #ddd;" href="index.php?year='.$year.'">🏠 Tableau de bord</a>';
print '<a class="tabTitle" style="color: #666; background: #fff; padding: 8px 15px; margin-right: 5px; text-decoration: none; border-radius: 3px; border: 1px solid #ddd;" href="ca.php?year='.$year.'">📊 Pilotage CA</a>';
print '<a class="tabTitle" style="color: #666; background: #fff; padding: 8px 15px; margin-right: 5px; text-decoration: none; border-radius: 3px; border: 1px solid #ddd;" href="tresorerie.php?year='.$year.'">💰 Trésorerie</a>';
print '<a class="tabTitle" style="color: #666; background: #f0f0f0; padding: 8px 15px; margin-right: 5px; text-decoration: none; border-radius: 3px;" href="'.$_SERVER['PHP_SELF'].'?year='.$year.'">📈 SIG</a>';
print '</div>';

print '<form method="GET" action="'.$_SERVER['PHP_SELF'].'">';
print $langs->trans('Year') . ': ';
print '<input type="number" name="year" value="'.$year.'" min="2000" max="2100" /> ';
print '<input class="button" type="submit" value="'.$langs->trans('Refresh').'" />';
print '</form>';

print '<br />';

// Calcul des données SIG
$sig_data = sig_calculate_sig_data($db, $year);

// SECTION 1: Tableau des SIG
print load_fiche_titre('Tableau des Soldes Intermédiaires de Gestion', '', 'fa-table');

print '<div class="div-table-responsive">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<th style="text-align:left; width: 300px;">Indicateur</th>';
print '<th style="text-align:right; width: 150px;">Montant (€)</th>';
print '<th style="text-align:right; width: 120px;">% CA</th>';
print '<th style="text-align:left;">Description</th>';
print '</tr>';

// Ligne 1: Chiffre d'affaires
$ca_ht = $sig_data['ca_ht'];
print '<tr class="oddeven">';
print '<td><strong>Chiffre d\'affaires HT</strong></td>';
print '<td style="text-align:right; font-weight: bold; color: #2E7D32;">'.price($ca_ht).'</td>';
print '<td style="text-align:right;">100,0%</td>';
print '<td>Ventes de biens et services (factures, avoirs et remplacements validés)</td>';
print '</tr>';

// Ligne 2: Achats consommés
$achats = $sig_data['achats'];
print '<tr class="oddeven">';
print '<td>- Achats consommés</td>';
print '<td style="text-align:right; color: #C62828;">'.price(-$achats).'</td>';
print '<td style="text-align:right;">'.($ca_ht > 0 ? number_format(($achats / $ca_ht) * 100, 1) : '0,0').'%</td>';
print '<td>Factures fournisseurs (comptes '.getDolGlobalString('SIG_ACCOUNT_START', '601').' à '.getDolGlobalString('SIG_ACCOUNT_END', '609').')</td>';
print '</tr>';

// Ligne 3: Marge commerciale
$marge_commerciale = $ca_ht - $achats;
$marge_color = $marge_commerciale >= 0 ? '#2E7D32' : '#C62828';
print '<tr style="background-color: #f5f5f5; border-top: 2px solid #ddd;">';
print '<td><strong>= Marge commerciale</strong></td>';
print '<td style="text-align:right; font-weight: bold; color: '.$marge_color.';">'.price($marge_commerciale).'</td>';
print '<td style="text-align:right; font-weight: bold;">'.($ca_ht > 0 ? number_format(($marge_commerciale / $ca_ht) * 100, 1) : '0,0').'%</td>';
print '<td>Différence entre ventes et achats</td>';
print '</tr>';

// Ligne 4: Charges de personnel
$charges_personnel = $sig_data['charges_personnel'];
print '<tr class="oddeven">';
print '<td>- Charges de personnel</td>';
print '<td style="text-align:right; color: #C62828;">'.price(-$charges_personnel).'</td>';
print '<td style="text-align:right;">'.($ca_ht > 0 ? number_format(($charges_personnel / $ca_ht) * 100, 1) : '0,0').'%</td>';
// Configuration des comptes pour affichage dynamique
$personnel_account_start_display = getDolGlobalString('SIG_PERSONNEL_ACCOUNT_START', '640000');
$personnel_account_end_display = getDolGlobalString('SIG_PERSONNEL_ACCOUNT_END', '648000');

$taux_charges_display = getDolGlobalString('SIG_SOCIAL_CHARGES_RATE', '55');
print '<td>Charges de personnel (salaires + '.$taux_charges_display.'% charges sociales)</td>';
print '</tr>';

// Ligne 5: Valeur ajoutée
$valeur_ajoutee = $marge_commerciale - $charges_personnel;
$va_color = $valeur_ajoutee >= 0 ? '#2E7D32' : '#C62828';
print '<tr style="background-color: #f5f5f5; border-top: 2px solid #ddd;">';
print '<td><strong>= Valeur ajoutée</strong></td>';
print '<td style="text-align:right; font-weight: bold; color: '.$va_color.';">'.price($valeur_ajoutee).'</td>';
print '<td style="text-align:right; font-weight: bold;">'.($ca_ht > 0 ? number_format(($valeur_ajoutee / $ca_ht) * 100, 1) : '0,0').'%</td>';
print '<td>Richesse créée par l\'entreprise</td>';
print '</tr>';

// Ligne 6: Autres charges externes
$autres_charges = $sig_data['autres_charges'];
print '<tr class="oddeven">';
print '<td>- Autres charges externes</td>';
print '<td style="text-align:right; color: #C62828;">'.price(-$autres_charges).'</td>';
print '<td style="text-align:right;">'.($ca_ht > 0 ? number_format(($autres_charges / $ca_ht) * 100, 1) : '0,0').'%</td>';
print '<td>Loyers, assurances, services externes</td>';
print '</tr>';

// Ligne 7: Excédent Brut d'Exploitation (EBE)
$ebe = $valeur_ajoutee - $autres_charges;
$ebe_color = $ebe >= 0 ? '#2E7D32' : '#C62828';
print '<tr style="background-color: #e8f5e8; border-top: 2px solid #4CAF50;">';
print '<td><strong>= Excédent Brut d\'Exploitation (EBE)</strong></td>';
print '<td style="text-align:right; font-weight: bold; color: '.$ebe_color.';">'.price($ebe).'</td>';
print '<td style="text-align:right; font-weight: bold;">'.($ca_ht > 0 ? number_format(($ebe / $ca_ht) * 100, 1) : '0,0').'%</td>';
print '<td>Capacité d\'autofinancement avant amortissements</td>';
print '</tr>';

print '</table>';
print '</div>';

// SECTION 2: Graphique en cascade
print '<br>';
print load_fiche_titre('Analyse graphique des SIG', '', 'fa-chart-bar');

print '<div style="margin-bottom: 30px; height: 500px;">';
print '<canvas id="sigChart"></canvas>';
print '</div>';

// SECTION 3: Ratios d'analyse
print '<br>';
print load_fiche_titre('Ratios d\'analyse financière', '', 'fa-percent');

print '<div style="display: flex; gap: 20px; margin-bottom: 20px;">';

// Ratio de marge commerciale
$ratio_marge = $ca_ht > 0 ? ($marge_commerciale / $ca_ht) * 100 : 0;
$marge_style = $ratio_marge >= 20 ? '#4CAF50' : ($ratio_marge >= 10 ? '#FF9800' : '#F44336');
print '<div class="info-box" style="background: #f8f9fa; border-left: 4px solid '.$marge_style.'; padding: 15px; flex: 1;">';
print '<div style="text-align: center;">';
print '<h3 style="margin: 0; color: '.$marge_style.';">Taux de marge</h3>';
print '<div style="font-size: 24px; font-weight: bold; color: '.$marge_style.'; margin-top: 10px;">'.number_format($ratio_marge, 1).'%</div>';
print '<div style="font-size: 12px; color: #666; margin-top: 5px;">Marge commerciale / CA</div>';
print '</div>';
print '</div>';

// Ratio de valeur ajoutée
$ratio_va = $ca_ht > 0 ? ($valeur_ajoutee / $ca_ht) * 100 : 0;
$va_style = $ratio_va >= 30 ? '#4CAF50' : ($ratio_va >= 15 ? '#FF9800' : '#F44336');
print '<div class="info-box" style="background: #f8f9fa; border-left: 4px solid '.$va_style.'; padding: 15px; flex: 1;">';
print '<div style="text-align: center;">';
print '<h3 style="margin: 0; color: '.$va_style.';">Taux de VA</h3>';
print '<div style="font-size: 24px; font-weight: bold; color: '.$va_style.'; margin-top: 10px;">'.number_format($ratio_va, 1).'%</div>';
print '<div style="font-size: 12px; color: #666; margin-top: 5px;">Valeur ajoutée / CA</div>';
print '</div>';
print '</div>';

// Ratio d'EBE
$ratio_ebe = $ca_ht > 0 ? ($ebe / $ca_ht) * 100 : 0;
$ebe_style = $ratio_ebe >= 15 ? '#4CAF50' : ($ratio_ebe >= 8 ? '#FF9800' : '#F44336');
print '<div class="info-box" style="background: #f8f9fa; border-left: 4px solid '.$ebe_style.'; padding: 15px; flex: 1;">';
print '<div style="text-align: center;">';
print '<h3 style="margin: 0; color: '.$ebe_style.';">Taux d\'EBE</h3>';
print '<div style="font-size: 24px; font-weight: bold; color: '.$ebe_style.'; margin-top: 10px;">'.number_format($ratio_ebe, 1).'%</div>';
print '<div style="font-size: 12px; color: #666; margin-top: 5px;">EBE / CA</div>';
print '</div>';
print '</div>';



print '</div>';

// Script JavaScript pour le graphique
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const canvasElement = document.getElementById('sigChart');
    if (!canvasElement || typeof Chart === 'undefined') {
        console.error('Impossible de charger le graphique SIG');
        return;
    }
    
    const ctx = canvasElement.getContext('2d');
    const sigChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [
                'CA HT',
                'Achats',
                'Marge comm.',
                'Charges pers.',
                'Valeur ajoutée',
                'Autres charges',
                'EBE'
            ],
            datasets: [{
                label: 'Montants (€)',
                data: [
                    <?php echo $ca_ht; ?>,
                    <?php echo -$achats; ?>,
                    <?php echo $marge_commerciale; ?>,
                    <?php echo -$charges_personnel; ?>,
                    <?php echo $valeur_ajoutee; ?>,
                    <?php echo -$autres_charges; ?>,
                    <?php echo $ebe; ?>
                ],
                backgroundColor: function(context) {
                    const value = context.parsed.y;
                    const index = context.dataIndex;
                    
                    // Couleurs spéciales pour les indicateurs clés
                    if (index === 0) return '#2196F3'; // CA
                    if (index === 2) return '#4CAF50'; // Marge commerciale
                    if (index === 4) return '#8BC34A'; // Valeur ajoutée
                    if (index === 6) return '#4CAF50'; // EBE
                    
                    // Couleurs selon positif/négatif pour les autres
                    return value >= 0 ? '#66BB6A' : '#EF5350';
                },
                borderColor: '#333',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Analyse des Soldes Intermédiaires de Gestion - <?php echo $year; ?>',
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                },
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return new Intl.NumberFormat('fr-FR', {
                                style: 'currency',
                                currency: 'EUR',
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            }).format(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    ticks: {
                        callback: function(value) {
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