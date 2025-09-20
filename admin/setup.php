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

if ($action == 'setvalue' && $user->admin) {
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
print '<input type="hidden" name="action" value="setvalue">';

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

print '</div>';

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