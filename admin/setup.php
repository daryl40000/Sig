<?php
require_once __DIR__ . '/../../../main.inc.php';

if (empty($user->rights->sig)) {
	$user->rights->sig = new stdClass();
	$user->rights->sig->read = 1;
}

$langs->loadLangs(array('admin', 'other', 'sig@sig'));

if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'aZ09');

llxHeader('', $langs->trans('SigSetup'));
print load_fiche_titre($langs->trans('SigSetup'), '', 'title_setup');

print $langs->trans('SigSetupHelp');

llxFooter(); 