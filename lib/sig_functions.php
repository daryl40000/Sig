<?php
/* Copyright (C) 2025
 * Module SIG - Fonctions communes
 */

/**
 * Calcule les données pour les Soldes Intermédiaires de Gestion
 *
 * @param DoliDB $db
 * @param int $year
 * @return array
 */
function sig_calculate_sig_data(DoliDB $db, int $year): array
{
    global $conf;
    
    // Calcul des timestamps (début et fin d'année)
    $firstday_timestamp = dol_mktime(0, 0, 0, 1, 1, $year);
    $lastday_timestamp = dol_mktime(23, 59, 59, 12, 31, $year);
    
    // Configuration des comptes pour achats consommés
    $account_start = getDolGlobalString('SIG_ACCOUNT_START', '601');
    $account_end = getDolGlobalString('SIG_ACCOUNT_END', '609');
    
    $data = array();
    
    // 1. Chiffre d'affaires HT (factures client validées + avoirs + factures de remplacement)
    $sql = 'SELECT SUM(f.total_ht) as total_ht';
    $sql .= ' FROM '.MAIN_DB_PREFIX.'facture as f';
    $sql .= ' WHERE f.entity IN ('.getEntity('invoice', 1).')';
    $sql .= ' AND f.fk_statut IN (1,2)'; // Validées et payées
    $sql .= ' AND f.type IN (0, 1, 2)'; // Factures standard (0), avoirs (1) et factures de remplacement (2)
    $sql .= " AND f.datef BETWEEN '".$db->idate($firstday_timestamp)."' AND '".$db->idate($lastday_timestamp)."'";
    
    $resql = $db->query($sql);
    $data['ca_ht'] = 0.0;
    if ($resql) {
        $obj = $db->fetch_object($resql);
        if ($obj && !empty($obj->total_ht)) {
            $data['ca_ht'] = (float) $obj->total_ht;
        }
        $db->free($resql);
    }
    
    // 2. Achats (factures fournisseurs selon configuration des comptes)
    $sql = 'SELECT SUM(fd.total_ht) as total_ht';
    $sql .= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as f';
    $sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'facture_fourn_det as fd ON f.rowid = fd.fk_facture_fourn';
    $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'accounting_account as aa ON fd.fk_code_ventilation = aa.rowid';
    $sql .= ' WHERE f.entity IN ('.getEntity('supplier_invoice', 1).')';
    $sql .= ' AND f.fk_statut IN (1,2)'; // Validées et payées
    $sql .= " AND f.datef BETWEEN '".$db->idate($firstday_timestamp)."' AND '".$db->idate($lastday_timestamp)."'";
    $sql .= " AND (aa.account_number BETWEEN '".$db->escape($account_start)."' AND '".$db->escape($account_end)."'";
    $sql .= " OR fd.fk_code_ventilation = 0 OR fd.fk_code_ventilation IS NULL)"; // Inclure les non ventilées par défaut
    
    $resql = $db->query($sql);
    $data['achats'] = 0.0;
    if ($resql) {
        $obj = $db->fetch_object($resql);
        if ($obj && !empty($obj->total_ht)) {
            $data['achats'] = (float) $obj->total_ht;
        }
        $db->free($resql);
    }
    
    // 3. Charges de personnel (salaires + charges sociales comptabilité)
    $data['charges_personnel'] = 0.0;
    $salaires_total = 0.0;
    $charges_sociales_total = 0.0;
    
    // Date actuelle pour vérifier que la période est bien terminée
    $today_timestamp = dol_now();
    
    // Salaires dont la date de fin de période est dans l'année ET antérieure à aujourd'hui
    $sql = 'SELECT SUM(s.amount) as total_amount, COUNT(*) as nb_lines';
    $sql .= ' FROM '.MAIN_DB_PREFIX.'salary as s';
    $sql .= ' WHERE s.entity IN ('.getEntity('salary', 1).')';
    $sql .= " AND s.dateep BETWEEN '".$db->idate($firstday_timestamp)."' AND '".$db->idate($lastday_timestamp)."'";
    $sql .= " AND s.dateep <= '".$db->idate($today_timestamp)."'"; // Période réellement terminée
    
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        if ($obj && $obj->total_amount > 0) {
            $salaires_total = (float) $obj->total_amount;
        }
        $db->free($resql);
    }
    
    // B. Charges sociales calculées automatiquement (% des salaires)
    $taux_charges_sociales = (float) getDolGlobalString('SIG_SOCIAL_CHARGES_RATE', '55'); // Défaut 55%
    
    if ($salaires_total > 0) {
        $charges_sociales_total = $salaires_total * ($taux_charges_sociales / 100);
    }
    
    // C. Total des charges de personnel
    $data['charges_personnel'] = $salaires_total + $charges_sociales_total;
    
    // 4. Autres charges externes (factures fournisseurs hors plage des comptes d'achats)
    $sql = 'SELECT SUM(fd.total_ht) as total_ht';
    $sql .= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as f';
    $sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'facture_fourn_det as fd ON f.rowid = fd.fk_facture_fourn';
    $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'accounting_account as aa ON fd.fk_code_ventilation = aa.rowid';
    $sql .= ' WHERE f.entity IN ('.getEntity('supplier_invoice', 1).')';
    $sql .= ' AND f.fk_statut IN (1,2)'; // Validées et payées
    $sql .= " AND f.datef BETWEEN '".$db->idate($firstday_timestamp)."' AND '".$db->idate($lastday_timestamp)."'";
    $sql .= " AND aa.account_number IS NOT NULL";
    $sql .= " AND aa.account_number NOT BETWEEN '".$db->escape($account_start)."' AND '".$db->escape($account_end)."'";
    
    $resql = $db->query($sql);
    $data['autres_charges'] = 0.0;
    if ($resql) {
        $obj = $db->fetch_object($resql);
        if ($obj && !empty($obj->total_ht)) {
            $data['autres_charges'] = (float) $obj->total_ht;
        }
        $db->free($resql);
    }
    
    // Si pas de données comptables, utiliser l'estimation (15% du CA)
    if ($data['autres_charges'] == 0) {
        $data['autres_charges'] = $data['ca_ht'] * 0.15;
    }
    
    // Calculs des SIG
    $data['chiffre_affaires'] = $data['ca_ht'];
    $data['marge_commerciale'] = $data['chiffre_affaires'] - $data['achats'];
    $data['valeur_ajoutee'] = $data['marge_commerciale']; // Simplification pour entreprise commerciale
    $data['ebe'] = $data['valeur_ajoutee'] - $data['charges_personnel'] - $data['autres_charges'];
    $data['resultat_exploitation'] = $data['ebe']; // Simplification (pas d'amortissements)
    
    return $data;
} 