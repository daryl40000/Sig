<?php
/* Script de diagnostic pour vérifier les avoirs dans Dolibarr */

require_once __DIR__ . '/../../main.inc.php';

// Accès interdit si le module SIG n'est pas activé
if (empty($conf->global->MAIN_MODULE_SIG)) {
    accessforbidden();
}

$year = GETPOSTINT('year');
if (empty($year)) $year = (int) dol_print_date(dol_now(), '%Y');

llxHeader('', 'Diagnostic Avoirs - Module SIG');

print '<h1>🔍 Diagnostic des Avoirs - Année '.$year.'</h1>';

print '<form method="GET" action="'.$_SERVER['PHP_SELF'].'">';
print 'Année : <input type="number" name="year" value="'.$year.'" min="2000" max="2100" /> ';
print '<input class="button" type="submit" value="Analyser" />';
print '</form>';

print '<br/>';

// 1. Compter toutes les factures par type
print '<h2>📊 Répartition des factures par type</h2>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<th>Type</th><th>Description</th><th>Nombre</th><th>Total HT</th></tr>';

$types = array(
    0 => 'Factures standard',
    1 => 'Avoirs (Credit notes)', 
    2 => 'Factures de remplacement'
);

foreach ($types as $type_id => $type_name) {
    $sql = 'SELECT COUNT(*) as nb, SUM(total_ht) as total_ht';
    $sql .= ' FROM '.MAIN_DB_PREFIX.'facture as f';
    $sql .= ' WHERE f.entity IN ('.getEntity('invoice', 1).')';
    $sql .= ' AND f.type = '.(int)$type_id;
    $sql .= ' AND YEAR(f.datef) = '.(int)$year;
    
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        $nb = $obj->nb ? (int)$obj->nb : 0;
        $total = $obj->total_ht ? (float)$obj->total_ht : 0;
        
        $color = ($type_id == 1 && $nb > 0) ? 'style="background-color: #fff3cd;"' : '';
        
        print '<tr '.$color.'>';
        print '<td><strong>'.$type_id.'</strong></td>';
        print '<td>'.$type_name.'</td>';
        print '<td style="text-align: right;">'.$nb.'</td>';
        print '<td style="text-align: right;">'.price($total).'</td>';
        print '</tr>';
        
        $db->free($resql);
    }
}
print '</table>';

// 2. Détail des avoirs validés
print '<h2>📋 Détail des avoirs validés (Type 1)</h2>';

$sql = 'SELECT f.rowid, f.ref, f.datef, f.total_ht, f.fk_statut';
$sql .= ' FROM '.MAIN_DB_PREFIX.'facture as f';
$sql .= ' WHERE f.entity IN ('.getEntity('invoice', 1).')';
$sql .= ' AND f.type = 1'; // Avoirs uniquement
$sql .= ' AND f.fk_statut IN (1,2)'; // Validés et payés
$sql .= ' AND YEAR(f.datef) = '.(int)$year;
$sql .= ' ORDER BY f.datef DESC';

$resql = $db->query($sql);
if ($resql) {
    $nb_avoirs = $db->num_rows($resql);
    
    if ($nb_avoirs > 0) {
        print '<p><strong>✅ '.$nb_avoirs.' avoir(s) validé(s) trouvé(s) pour '.$year.'</strong></p>';
        
        print '<table class="noborder" width="100%">';
        print '<tr class="liste_titre">';
        print '<th>Référence</th><th>Date</th><th>Montant HT</th><th>Statut</th></tr>';
        
        $total_avoirs = 0;
        while ($obj = $db->fetch_object($resql)) {
            $total_avoirs += (float)$obj->total_ht;
            
            $statut_label = '';
            switch ($obj->fk_statut) {
                case 1: $statut_label = 'Validé'; break;
                case 2: $statut_label = 'Payé'; break;
                default: $statut_label = 'Statut '.$obj->fk_statut;
            }
            
            print '<tr>';
            print '<td>'.$obj->ref.'</td>';
            print '<td>'.dol_print_date($db->jdate($obj->datef), 'day').'</td>';
            print '<td style="text-align: right; color: #d32f2f;">'.price($obj->total_ht).'</td>';
            print '<td>'.$statut_label.'</td>';
            print '</tr>';
        }
        
        print '<tr style="background-color: #f5f5f5; font-weight: bold;">';
        print '<td colspan="2">TOTAL AVOIRS</td>';
        print '<td style="text-align: right; color: #d32f2f;">'.price($total_avoirs).'</td>';
        print '<td></td>';
        print '</tr>';
        print '</table>';
        
    } else {
        print '<p><strong>⚠️ Aucun avoir validé trouvé pour l\'année '.$year.'</strong></p>';
        print '<p>Cela peut expliquer pourquoi vous ne voyez pas de différence dans vos calculs de CA.</p>';
    }
    
    $db->free($resql);
}

// 3. Calcul du CA avec et sans avoirs pour comparaison
print '<h2>🧮 Comparaison CA avec/sans avoirs</h2>';

// CA sans avoirs (ancienne méthode)
$sql_sans_avoirs = 'SELECT SUM(f.total_ht) as total_ht';
$sql_sans_avoirs .= ' FROM '.MAIN_DB_PREFIX.'facture as f';
$sql_sans_avoirs .= ' WHERE f.entity IN ('.getEntity('invoice', 1).')';
$sql_sans_avoirs .= ' AND f.fk_statut IN (1,2)';
$sql_sans_avoirs .= ' AND f.type = 0'; // Factures standard uniquement
$sql_sans_avoirs .= ' AND YEAR(f.datef) = '.(int)$year;

// CA avec avoirs (nouvelle méthode)
$sql_avec_avoirs = 'SELECT SUM(f.total_ht) as total_ht';
$sql_avec_avoirs .= ' FROM '.MAIN_DB_PREFIX.'facture as f';
$sql_avec_avoirs .= ' WHERE f.entity IN ('.getEntity('invoice', 1).')';
$sql_avec_avoirs .= ' AND f.fk_statut IN (1,2)';
$sql_avec_avoirs .= ' AND f.type IN (0,1)'; // Factures standard + avoirs
$sql_avec_avoirs .= ' AND YEAR(f.datef) = '.(int)$year;

$ca_sans_avoirs = 0;
$ca_avec_avoirs = 0;

$resql = $db->query($sql_sans_avoirs);
if ($resql) {
    $obj = $db->fetch_object($resql);
    if ($obj && !empty($obj->total_ht)) {
        $ca_sans_avoirs = (float)$obj->total_ht;
    }
    $db->free($resql);
}

$resql = $db->query($sql_avec_avoirs);
if ($resql) {
    $obj = $db->fetch_object($resql);
    if ($obj && !empty($obj->total_ht)) {
        $ca_avec_avoirs = (float)$obj->total_ht;
    }
    $db->free($resql);
}

$difference = $ca_avec_avoirs - $ca_sans_avoirs;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<th>Méthode de calcul</th><th>Montant CA HT</th></tr>';

print '<tr>';
print '<td>CA sans avoirs (ancienne méthode)</td>';
print '<td style="text-align: right; font-weight: bold;">'.price($ca_sans_avoirs).'</td>';
print '</tr>';

print '<tr>';
print '<td>CA avec avoirs (nouvelle méthode)</td>';
print '<td style="text-align: right; font-weight: bold;">'.price($ca_avec_avoirs).'</td>';
print '</tr>';

$color = ($difference != 0) ? 'color: #d32f2f;' : 'color: #666;';
print '<tr style="background-color: #f5f5f5;">';
print '<td><strong>Différence</strong></td>';
print '<td style="text-align: right; font-weight: bold; '.$color.'">'.price($difference).'</td>';
print '</tr>';
print '</table>';

if ($difference == 0) {
    print '<p><strong>ℹ️ La différence est de 0€, ce qui signifie :</strong></p>';
    print '<ul>';
    print '<li>Soit vous n\'avez pas d\'avoirs validés pour cette année</li>';
    print '<li>Soit vos avoirs ont un montant total de 0€</li>';
    print '</ul>';
} else {
    print '<p><strong>✅ La différence de '.price($difference).' confirme que les avoirs sont pris en compte.</strong></p>';
}

// 4. Instructions
print '<h2>📝 Que faire maintenant ?</h2>';
print '<div style="background-color: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;">';
print '<p><strong>Si vous ne voyez toujours pas les avoirs pris en compte :</strong></p>';
print '<ol>';
print '<li><strong>Vérifiez vos avoirs :</strong> Assurez-vous qu\'ils sont bien validés (statut 1 ou 2)</li>';
print '<li><strong>Vérifiez les dates :</strong> Les avoirs doivent avoir une date dans l\'année sélectionnée</li>';
print '<li><strong>Vérifiez le type :</strong> Les avoirs doivent avoir le type = 1 dans Dolibarr</li>';
print '<li><strong>Actualisez la page :</strong> Videz le cache de votre navigateur et actualisez</li>';
print '</ol>';
print '</div>';

print '<p><a href="index.php?year='.$year.'" class="button">🏠 Retour au tableau de bord</a> ';
print '<a href="ca.php?year='.$year.'" class="button">📊 Aller à la page CA</a> ';
print '<a href="sig.php?year='.$year.'" class="button">📈 Aller aux SIG</a></p>';

llxFooter();
$db->close();
?> 