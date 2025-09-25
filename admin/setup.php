<?php
/* Copyright (C) 2025
 * Module SIG - Configuration
 */

require_once __DIR__ . '/../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

if (empty($user->rights->sig)) {
	$user->rights->sig = new stdClass();
	$user->rights->sig->read = 1;
}

$langs->loadLangs(array('admin', 'other', 'banks', 'sig@sig'));

if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'aZ09');

/*
 * Actions
 */

// Action pour sauvegarder la configuration générale
if ($action == 'save_general' && $user->admin) {
    $db->begin();
    
    $account_id = GETPOSTINT('SIG_BANK_ACCOUNT');
    $margin_rate = GETPOST('SIG_MARGIN_RATE', 'alphanohtml');
    $payment_delay = GETPOSTINT('SIG_PAYMENT_DELAY');
    
    $result1 = dolibarr_set_const($db, 'SIG_BANK_ACCOUNT', $account_id, 'chaine', 0, '', $conf->entity);
    $result2 = dolibarr_set_const($db, 'SIG_MARGIN_RATE', $margin_rate, 'chaine', 0, '', $conf->entity);
    $result3 = dolibarr_set_const($db, 'SIG_PAYMENT_DELAY', $payment_delay, 'chaine', 0, '', $conf->entity);
    
    if ($result1 > 0 && $result2 > 0 && $result3 > 0) {
        $db->commit();
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    } else {
        $db->rollback();
        setEventMessages($langs->trans("Error"), null, 'errors');
    }
}

// Action pour sauvegarder la configuration de trésorerie
if ($action == 'save_treasury' && $user->admin) {
    $db->begin();
    
    // Gestion de la case à cocher pour inclure les factures fournisseurs
    $include_supplier_invoices = GETPOST('SIG_INCLUDE_SUPPLIER_INVOICES', 'int') ? 1 : 0;
    
    // Gestion de la case à cocher pour inclure les charges sociales
    $include_social_charges = GETPOST('SIG_INCLUDE_SOCIAL_CHARGES', 'int') ? 1 : 0;
    
    // Gestion de la case à cocher pour inclure les devis signés
    $include_signed_quotes = GETPOST('SIG_INCLUDE_SIGNED_QUOTES', 'int') ? 1 : 0;
    
    // Gestion de la case à cocher pour inclure les factures client impayées
    $include_customer_invoices = GETPOST('SIG_INCLUDE_CUSTOMER_INVOICES', 'int') ? 1 : 0;
    
    // Gestion de la case à cocher pour inclure les salaires impayés
    $include_unpaid_salaries = GETPOST('SIG_INCLUDE_UNPAID_SALARIES', 'int') ? 1 : 0;
    
    // Gestion de la case à cocher pour inclure les factures modèle client
    $include_customer_template_invoices = GETPOST('SIG_INCLUDE_CUSTOMER_TEMPLATE_INVOICES', 'int') ? 1 : 0;
    
    // Gestion de la valeur d'incertitude pour les projections
    $projection_uncertainty = GETPOSTINT('SIG_PROJECTION_UNCERTAINTY');
    if (empty($projection_uncertainty) || $projection_uncertainty < 0) {
        $projection_uncertainty = 1000; // Valeur par défaut
    }
    
    $result1 = dolibarr_set_const($db, 'SIG_INCLUDE_SUPPLIER_INVOICES', $include_supplier_invoices, 'yesno', 0, '', $conf->entity);
    $result2 = dolibarr_set_const($db, 'SIG_INCLUDE_SOCIAL_CHARGES', $include_social_charges, 'yesno', 0, '', $conf->entity);
    $result3 = dolibarr_set_const($db, 'SIG_INCLUDE_SIGNED_QUOTES', $include_signed_quotes, 'yesno', 0, '', $conf->entity);
    $result4 = dolibarr_set_const($db, 'SIG_INCLUDE_CUSTOMER_INVOICES', $include_customer_invoices, 'yesno', 0, '', $conf->entity);
    $result5 = dolibarr_set_const($db, 'SIG_INCLUDE_UNPAID_SALARIES', $include_unpaid_salaries, 'yesno', 0, '', $conf->entity);
    $result6 = dolibarr_set_const($db, 'SIG_INCLUDE_CUSTOMER_TEMPLATE_INVOICES', $include_customer_template_invoices, 'yesno', 0, '', $conf->entity);
    $result7 = dolibarr_set_const($db, 'SIG_PROJECTION_UNCERTAINTY', $projection_uncertainty, 'chaine', 0, '', $conf->entity);
    
    if ($result1 > 0 && $result2 > 0 && $result3 > 0 && $result4 > 0 && $result5 > 0 && $result6 > 0 && $result7 > 0) {
        $db->commit();
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    } else {
        $db->rollback();
        setEventMessages($langs->trans("Error"), null, 'errors');
    }
}

// Action pour sauvegarder la configuration SIG
if ($action == 'save_sig' && $user->admin) {
    $db->begin();
    
    // Gestion des comptes SIG pour achats consommés
    $sig_account_start = GETPOST('SIG_ACCOUNT_START', 'alphanohtml');
    $sig_account_end = GETPOST('SIG_ACCOUNT_END', 'alphanohtml');
    
    // Gestion du taux de charges sociales
    $social_charges_rate = GETPOST('SIG_SOCIAL_CHARGES_RATE', 'alphanohtml');
    if (empty($social_charges_rate) || $social_charges_rate < 0) {
        $social_charges_rate = 55; // Valeur par défaut 55%
    }
    
    $result1 = dolibarr_set_const($db, 'SIG_ACCOUNT_START', $sig_account_start, 'chaine', 0, '', $conf->entity);
    $result2 = dolibarr_set_const($db, 'SIG_ACCOUNT_END', $sig_account_end, 'chaine', 0, '', $conf->entity);
    $result3 = dolibarr_set_const($db, 'SIG_SOCIAL_CHARGES_RATE', $social_charges_rate, 'chaine', 0, '', $conf->entity);
    
    if ($result1 > 0 && $result2 > 0 && $result3 > 0) {
        $db->commit();
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    } else {
        $db->rollback();
        setEventMessages($langs->trans("Error"), null, 'errors');
    }
}

if ($action == 'save_manual_ca' && $user->admin) {
    $db->begin();
    
    $manual_year = GETPOSTINT('manual_year');
    if ($manual_year > 0) {
        // Récupérer les données des 12 mois
        $ca_data = array();
        for ($m = 1; $m <= 12; $m++) {
            $ca_month = GETPOST('ca_month_'.$m, 'alphanohtml');
            if (!empty($ca_month) && is_numeric($ca_month)) {
                $ca_data[(string)$m] = (float) $ca_month;
            }
        }
        
        // Sauvegarder en JSON
        $config_key = 'SIG_MANUAL_CA_'.$manual_year;
        $json_data = json_encode($ca_data);
        
        $result = dolibarr_set_const($db, $config_key, $json_data, 'chaine', 0, '', $conf->entity);
        
        if ($result > 0) {
            $db->commit();
            setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
        } else {
            $db->rollback();
            setEventMessages($langs->trans("Error"), null, 'errors');
        }
    }
}

// Action pour recalculer les marges des factures
if ($action == 'recalculate_margins' && $user->admin && !empty($conf->margin->enabled)) {
    require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
    
    $db->begin();
    $nb_updated = 0;
    $nb_errors = 0;
    
    // Récupérer toutes les factures validées et payées
    $sql = 'SELECT f.rowid FROM '.MAIN_DB_PREFIX.'facture as f';
    $sql .= ' WHERE f.entity IN ('.getEntity('invoice', 1).')';
    $sql .= ' AND f.fk_statut IN (1,2)'; // Validées et payées
    
    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $invoice = new Facture($db);
            if ($invoice->fetch($obj->rowid) > 0) {
                // Recalculer les marges
                $result = $invoice->update_price(1); // 1 = recalculate margins
                if ($result > 0) {
                    $nb_updated++;
                } else {
                    $nb_errors++;
                }
            }
        }
        $db->free($resql);
        
        if ($nb_errors == 0) {
            $db->commit();
            setEventMessages("Marges recalculées avec succès pour $nb_updated facture(s).", null, 'mesgs');
        } else {
            $db->rollback();
            setEventMessages("Erreur lors du recalcul des marges : $nb_errors erreur(s) sur ".($nb_updated + $nb_errors)." facture(s).", null, 'errors');
        }
    } else {
        $db->rollback();
        setEventMessages("Erreur lors de la récupération des factures.", null, 'errors');
    }
}

/*
 * View
 */

llxHeader('', $langs->trans('SigSetup'));

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans('SigSetup'), $linkback, 'title_setup');

print '<br>';

// Configuration du compte bancaire
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="save_general">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '</tr>';

// Sélection du compte bancaire
print '<tr class="oddeven">';
print '<td width="200">'.$langs->trans("SigBankAccount").'</td>';
print '<td>';

// Récupérer la liste des comptes bancaires
$sql = "SELECT rowid, label, number, bank FROM ".MAIN_DB_PREFIX."bank_account";
$sql .= " WHERE entity IN (".getEntity('bank_account').")";
$sql .= " AND clos = 0"; // Seulement les comptes ouverts
$sql .= " ORDER BY label";

$resql = $db->query($sql);

print '<select name="SIG_BANK_ACCOUNT" class="flat">';
print '<option value="0">'.$langs->trans("AllBankAccounts").'</option>';

if ($resql) {
    $num = $db->num_rows($resql);
    if ($num > 0) {
        while ($obj = $db->fetch_object($resql)) {
            $selected = '';
            if ($obj->rowid == getDolGlobalString('SIG_BANK_ACCOUNT')) {
                $selected = ' selected="selected"';
            }
            $account_display = $obj->label;
            if ($obj->number) {
                $account_display .= ' ('.$obj->number.')';
            }
            if ($obj->bank) {
                $account_display .= ' - '.$obj->bank;
            }
            print '<option value="'.$obj->rowid.'"'.$selected.'>'.$account_display.'</option>';
        }
    } else {
        print '<option value="0">'.$langs->trans("NoAccountAvailable").'</option>';
    }
    $db->free($resql);
} else {
    print '<option value="0">Erreur: '.$db->lasterror().'</option>';
}

print '</select>';
print '</td>';
print '<td>'.$langs->trans("SigBankAccountHelp").'</td>';
print '</tr>';

// Configuration du taux de marge théorique
print '<tr class="oddeven">';
print '<td width="200">'.$langs->trans("SigMarginRate").'</td>';
print '<td>';

$current_margin_rate = getDolGlobalString('SIG_MARGIN_RATE');
if (empty($current_margin_rate)) $current_margin_rate = '20'; // Valeur par défaut 20%

print '<input type="number" name="SIG_MARGIN_RATE" value="'.$current_margin_rate.'" min="0" max="100" step="0.1" class="flat" style="width: 80px;"> %';
print '</td>';
print '<td>'.$langs->trans("SigMarginRateHelp").'</td>';
print '</tr>';

// Configuration du délai moyen de paiement
print '<tr class="oddeven">';
print '<td width="200">'.$langs->trans("SigPaymentDelay").'</td>';
print '<td>';

$current_payment_delay = getDolGlobalString('SIG_PAYMENT_DELAY');
if (empty($current_payment_delay)) $current_payment_delay = '30'; // Valeur par défaut 30 jours

print '<input type="number" name="SIG_PAYMENT_DELAY" value="'.$current_payment_delay.'" min="0" max="365" step="1" class="flat" style="width: 80px;"> jours';
print '</td>';
print '<td>'.$langs->trans("SigPaymentDelayHelp").'</td>';
print '</tr>';

print '</table>';

print '<br>';
print '<div class="center">';
print '<input type="submit" class="button button-save" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';

// Section Trésorerie
print '<br>';
print '<h3>'.$langs->trans("SigTreasurySection").'</h3>';

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="save_treasury">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '</tr>';

// Option pour inclure les factures fournisseurs (case à cocher native)
print '<tr class="oddeven">';
print '<td width="200">'.$langs->trans("SigIncludeSupplierInvoices").'</td>';
print '<td>';

$current_include_supplier = getDolGlobalString('SIG_INCLUDE_SUPPLIER_INVOICES');
$checked = '';
if (!empty($current_include_supplier) && $current_include_supplier == '1') {
    $checked = ' checked="checked"';
}

print '<input type="checkbox" name="SIG_INCLUDE_SUPPLIER_INVOICES" value="1"'.$checked.' class="flat">';
print '</td>';
print '<td>'.$langs->trans("SigIncludeSupplierInvoicesHelp").'</td>';
print '</tr>';

// Option pour inclure les charges sociales (case à cocher native)
print '<tr class="oddeven">';
print '<td width="200">'.$langs->trans("SigIncludeSocialCharges").'</td>';
print '<td>';

$current_include_social_charges = getDolGlobalString('SIG_INCLUDE_SOCIAL_CHARGES');
$checked_social = '';
if (!empty($current_include_social_charges) && $current_include_social_charges == '1') {
    $checked_social = ' checked="checked"';
}

print '<input type="checkbox" name="SIG_INCLUDE_SOCIAL_CHARGES" value="1"'.$checked_social.' class="flat">';
print '</td>';
print '<td>'.$langs->trans("SigIncludeSocialChargesHelp").'</td>';
print '</tr>';

// Option pour inclure les devis signés (case à cocher native)
print '<tr class="oddeven">';
print '<td width="200">'.$langs->trans("SigIncludeSignedQuotes").'</td>';
print '<td>';

$current_include_signed_quotes = getDolGlobalString('SIG_INCLUDE_SIGNED_QUOTES');
$checked_quotes = '';
if (!empty($current_include_signed_quotes) && $current_include_signed_quotes == '1') {
    $checked_quotes = ' checked="checked"';
}

print '<input type="checkbox" name="SIG_INCLUDE_SIGNED_QUOTES" value="1"'.$checked_quotes.' class="flat">';
print '</td>';
print '<td>'.$langs->trans("SigIncludeSignedQuotesHelp").'</td>';
print '</tr>';

// Option pour inclure les factures client impayées (case à cocher native)
print '<tr class="oddeven">';
print '<td width="200">'.$langs->trans("SigIncludeCustomerInvoices").'</td>';
print '<td>';

$current_include_customer_invoices = getDolGlobalString('SIG_INCLUDE_CUSTOMER_INVOICES');
$checked_customer = '';
if (!empty($current_include_customer_invoices) && $current_include_customer_invoices == '1') {
    $checked_customer = ' checked="checked"';
}

print '<input type="checkbox" name="SIG_INCLUDE_CUSTOMER_INVOICES" value="1"'.$checked_customer.' class="flat">';
print '</td>';
print '<td>'.$langs->trans("SigIncludeCustomerInvoicesHelp").'</td>';
print '</tr>';

// Option pour inclure les salaires impayés (case à cocher native)
print '<tr class="oddeven">';
print '<td width="200">'.$langs->trans("SigIncludeUnpaidSalaries").'</td>';
print '<td>';

$current_include_unpaid_salaries = getDolGlobalString('SIG_INCLUDE_UNPAID_SALARIES');
$checked_salaries = '';
if (!empty($current_include_unpaid_salaries) && $current_include_unpaid_salaries == '1') {
    $checked_salaries = ' checked="checked"';
}

print '<input type="checkbox" name="SIG_INCLUDE_UNPAID_SALARIES" value="1"'.$checked_salaries.' class="flat">';
print '</td>';
print '<td>'.$langs->trans("SigIncludeUnpaidSalariesHelp").'</td>';
print '</tr>';

// Option pour inclure les factures modèle client (case à cocher native)
print '<tr class="oddeven">';
print '<td width="200">'.$langs->trans("SigIncludeCustomerTemplateInvoices").'</td>';
print '<td>';

$current_include_customer_template_invoices = getDolGlobalString('SIG_INCLUDE_CUSTOMER_TEMPLATE_INVOICES');
$checked_template = '';
if (!empty($current_include_customer_template_invoices) && $current_include_customer_template_invoices == '1') {
    $checked_template = ' checked="checked"';
}

print '<input type="checkbox" name="SIG_INCLUDE_CUSTOMER_TEMPLATE_INVOICES" value="1"'.$checked_template.' class="flat">';
print '</td>';
print '<td>'.$langs->trans("SigIncludeCustomerTemplateInvoicesHelp").'</td>';
print '</tr>';

// Configuration de l'incertitude pour les projections
print '<tr class="oddeven">';
print '<td width="200">'.$langs->trans("SigProjectionUncertainty").'</td>';
print '<td>';

$current_uncertainty = getDolGlobalString('SIG_PROJECTION_UNCERTAINTY');
if (empty($current_uncertainty)) $current_uncertainty = '1000'; // Valeur par défaut 1000€

print '<input type="number" name="SIG_PROJECTION_UNCERTAINTY" value="'.$current_uncertainty.'" min="0" max="100000" step="100" class="flat" style="width: 100px;"> €';
print '</td>';
print '<td>'.$langs->trans("SigProjectionUncertaintyHelp").'</td>';
print '</tr>';

print '</table>';

print '<br>';
print '<div class="center">';
print '<input type="submit" class="button button-save" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';

// Section SIG (Soldes Intermédiaires de Gestion)
print '<br>';
print '<h3>'.$langs->trans("SigSIGSection").'</h3>';

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="save_sig">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '</tr>';

// Configuration des comptes pour achats consommés
print '<tr class="oddeven">';
print '<td width="200">'.$langs->trans("SigAccountStart").'</td>';
print '<td>';

$current_account_start = getDolGlobalString('SIG_ACCOUNT_START');
if (empty($current_account_start)) $current_account_start = '601'; // Valeur par défaut

print '<input type="text" name="SIG_ACCOUNT_START" value="'.$current_account_start.'" maxlength="10" class="flat" style="width: 100px;">';
print '</td>';
print '<td>'.$langs->trans("SigAccountStartHelp").'</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td width="200">'.$langs->trans("SigAccountEnd").'</td>';
print '<td>';

$current_account_end = getDolGlobalString('SIG_ACCOUNT_END');
if (empty($current_account_end)) $current_account_end = '609'; // Valeur par défaut

print '<input type="text" name="SIG_ACCOUNT_END" value="'.$current_account_end.'" maxlength="10" class="flat" style="width: 100px;">';
print '</td>';
print '<td>'.$langs->trans("SigAccountEndHelp").'</td>';
print '</tr>';

// Configuration du taux de charges sociales
print '<tr class="oddeven">';
print '<td width="200">'.$langs->trans("SigSocialChargesRate").'</td>';
print '<td>';

$current_social_rate = getDolGlobalString('SIG_SOCIAL_CHARGES_RATE');
if (empty($current_social_rate)) $current_social_rate = '55'; // Valeur par défaut

print '<input type="number" name="SIG_SOCIAL_CHARGES_RATE" value="'.$current_social_rate.'" min="0" max="100" step="0.1" class="flat" style="width: 80px;"> %';
print '</td>';
print '<td>'.$langs->trans("SigSocialChargesRateHelp").'</td>';
print '</tr>';

print '</table>';

print '<br>';
print '<div class="center">';
print '<input type="submit" class="button button-save" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';

// Section pour la saisie manuelle des CA des années précédentes
print '<br>';
print '<h3>'.$langs->trans("SigManualTurnoverSection").'</h3>';

// Formulaire pour saisir les données de l'année précédente
$current_year = (int) date('Y');
$previous_year = $current_year - 1;

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" id="manual_ca_form">';
print '<input type="hidden" name="action" value="save_manual_ca">';
print '<input type="hidden" name="manual_year" value="'.$previous_year.'">';

print '<p>'.$langs->trans("SigManualTurnoverHelp", $previous_year).'</p>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="13">'.$langs->trans("SigManualTurnoverTitle", $previous_year).'</td>';
print '</tr>';

print '<tr class="liste_titre">';
print '<td>Mois</td>';
for ($m = 1; $m <= 12; $m++) {
    print '<td style="text-align:center">'.dol_print_date(dol_mktime(12, 0, 0, $m, 1, $previous_year), '%b').'</td>';
}
print '</tr>';

print '<tr>';
print '<td><strong>CA '.$previous_year.' (€)</strong></td>';

// Récupérer les données existantes
$config_key = 'SIG_MANUAL_CA_'.$previous_year;
$existing_data = getDolGlobalString($config_key);
$data_array = array();
if (!empty($existing_data)) {
    $data_array = json_decode($existing_data, true);
    if (!is_array($data_array)) {
        $data_array = array();
    }
}

for ($m = 1; $m <= 12; $m++) {
    $value = isset($data_array[(string)$m]) ? $data_array[(string)$m] : '';
    print '<td><input type="number" name="ca_month_'.$m.'" value="'.$value.'" min="0" step="0.01" class="flat" style="width: 80px; text-align: right;"></td>';
}
print '</tr>';

print '</table>';

print '<br>';
print '<div class="center">';
print '<input type="submit" class="button button-save" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';

print '<br><br>';

// Informations sur la configuration actuelle
print '<div class="info">';
print '<h3>'.$langs->trans("CurrentConfiguration").'</h3>';

$current_account = getDolGlobalString('SIG_BANK_ACCOUNT');
if ($current_account > 0) {
    // Récupérer les informations du compte sélectionné
    $sql = "SELECT rowid, label, number, bank, currency_code FROM ".MAIN_DB_PREFIX."bank_account";
    $sql .= " WHERE rowid = ".(int)$current_account;
    $sql .= " AND entity IN (".getEntity('bank_account').")";
    
    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql) > 0) {
        $obj = $db->fetch_object($resql);
        print '<p><strong>'.$langs->trans("SelectedBankAccount").' :</strong> '.$obj->label;
        if ($obj->number) {
            print ' ('.$obj->number.')';
        }
        print '</p>';
        if ($obj->bank) {
            print '<p><strong>'.$langs->trans("Bank").' :</strong> '.$obj->bank.'</p>';
        }
        if ($obj->currency_code) {
            print '<p><strong>'.$langs->trans("Currency").' :</strong> '.$obj->currency_code.'</p>';
        }
        $db->free($resql);
    } else {
        print '<p style="color: orange;"><strong>'.$langs->trans("Warning").' :</strong> '.$langs->trans("AccountNotFound").'</p>';
    }
} else {
    print '<p><strong>'.$langs->trans("SelectedBankAccount").' :</strong> '.$langs->trans("AllBankAccounts").'</p>';
}

// Afficher le taux de marge configuré
$current_margin_rate = getDolGlobalString('SIG_MARGIN_RATE');
if (empty($current_margin_rate)) $current_margin_rate = '20';
print '<p><strong>'.$langs->trans("SigMarginRate").' :</strong> '.$current_margin_rate.'%</p>';

// Afficher le délai de paiement configuré
$current_payment_delay = getDolGlobalString('SIG_PAYMENT_DELAY');
if (empty($current_payment_delay)) $current_payment_delay = '30';
print '<p><strong>'.$langs->trans("SigPaymentDelay").' :</strong> '.$current_payment_delay.' jours</p>';

// Afficher la configuration de l'inclusion des factures fournisseurs
$current_include_supplier = getDolGlobalString('SIG_INCLUDE_SUPPLIER_INVOICES');
$include_status = (!empty($current_include_supplier) && $current_include_supplier == '1') ? $langs->trans("Yes") : $langs->trans("No");
print '<p><strong>'.$langs->trans("SigIncludeSupplierInvoices").' :</strong> '.$include_status.'</p>';

// Afficher la configuration de l'inclusion des charges sociales
$current_include_social_charges = getDolGlobalString('SIG_INCLUDE_SOCIAL_CHARGES');
$include_social_status = (!empty($current_include_social_charges) && $current_include_social_charges == '1') ? $langs->trans("Yes") : $langs->trans("No");
print '<p><strong>'.$langs->trans("SigIncludeSocialCharges").' :</strong> '.$include_social_status.'</p>';

// Afficher la configuration de l'inclusion des devis signés
$current_include_signed_quotes = getDolGlobalString('SIG_INCLUDE_SIGNED_QUOTES');
$include_quotes_status = (!empty($current_include_signed_quotes) && $current_include_signed_quotes == '1') ? $langs->trans("Yes") : $langs->trans("No");
print '<p><strong>'.$langs->trans("SigIncludeSignedQuotes").' :</strong> '.$include_quotes_status.'</p>';

// Afficher la configuration de l'inclusion des factures client impayées
$current_include_customer_invoices = getDolGlobalString('SIG_INCLUDE_CUSTOMER_INVOICES');
$include_customer_status = (!empty($current_include_customer_invoices) && $current_include_customer_invoices == '1') ? $langs->trans("Yes") : $langs->trans("No");
print '<p><strong>'.$langs->trans("SigIncludeCustomerInvoices").' :</strong> '.$include_customer_status.'</p>';

// Afficher la configuration de l'inclusion des salaires impayés
$current_include_unpaid_salaries = getDolGlobalString('SIG_INCLUDE_UNPAID_SALARIES');
$include_salaries_status = (!empty($current_include_unpaid_salaries) && $current_include_unpaid_salaries == '1') ? $langs->trans("Yes") : $langs->trans("No");
print '<p><strong>'.$langs->trans("SigIncludeUnpaidSalaries").' :</strong> '.$include_salaries_status.'</p>';

// Afficher la configuration de l'inclusion des factures modèle client
$current_include_customer_template_invoices = getDolGlobalString('SIG_INCLUDE_CUSTOMER_TEMPLATE_INVOICES');
$include_template_status = (!empty($current_include_customer_template_invoices) && $current_include_customer_template_invoices == '1') ? $langs->trans("Yes") : $langs->trans("No");
print '<p><strong>'.$langs->trans("SigIncludeCustomerTemplateInvoices").' :</strong> '.$include_template_status.'</p>';

// Section utilitaires pour les marges
print '<br><div class="titre">'.$langs->trans("SigMarginUtilities").'</div>';
print '<div class="info">'.$langs->trans("SigMarginUtilitiesDesc").'</div>';

// Vérifier le statut du module margin
$margin_enabled = !empty($conf->margin->enabled);
if ($margin_enabled) {
    print '<div style="background: #d4edda; border: 1px solid #28a745; border-radius: 5px; padding: 10px; margin: 10px 0;">';
    print '<strong>✅ Module Margin activé</strong><br>';
    print 'Le module Margin est activé et fonctionnel.';
    print '</div>';
    
    // Bouton pour recalculer les marges
    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" style="margin: 10px 0;">';
    print '<input type="hidden" name="action" value="recalculate_margins">';
    print '<input type="submit" class="button" value="🔄 Recalculer les marges des factures" onclick="return confirm(\'Êtes-vous sûr de vouloir recalculer toutes les marges ? Cette opération peut prendre du temps.\');">';
    print '</form>';
    
} else {
    print '<div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 5px; padding: 10px; margin: 10px 0;">';
    print '<strong>⚠️ Module Margin désactivé</strong><br>';
    print 'Pour utiliser le suivi des marges dans le module SIG, activez le module "Marges" dans Configuration > Modules.';
    print '</div>';
}

print '<br>';

// Aide et documentation
print '<div class="info">';
print '<h3>'.$langs->trans("Help").'</h3>';
print '<p>'.$langs->trans('SigSetupHelp').'</p>';
print '<ul>';
print '<li>'.$langs->trans("SigSetupHelp1").'</li>';
print '<li>'.$langs->trans("SigSetupHelp2").'</li>';
print '<li>'.$langs->trans("SigSetupHelp3").'</li>';
print '</ul>';
print '</div>';

llxFooter(); 