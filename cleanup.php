<?php
// Script de nettoyage des menus orphelins du module SIG
require_once __DIR__ . '/../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

if (!$user->admin) {
    die('Accès refusé - Admin requis');
}

echo "Nettoyage des menus orphelins du module SIG...\n";

// Supprimer les entrées de menu du module SIG
$sql = "DELETE FROM ".MAIN_DB_PREFIX."menu WHERE module = 'sig'";
$result = $db->query($sql);
if ($result) {
    echo "Menus supprimés: " . $db->affected_rows($result) . " entrées\n";
} else {
    echo "Erreur lors de la suppression des menus: " . $db->lasterror() . "\n";
}

// Supprimer les droits du module SIG
$sql = "DELETE FROM ".MAIN_DB_PREFIX."rights_def WHERE module = 'sig'";
$result = $db->query($sql);
if ($result) {
    echo "Droits supprimés: " . $db->affected_rows($result) . " entrées\n";
} else {
    echo "Erreur lors de la suppression des droits: " . $db->lasterror() . "\n";
}

// Supprimer la constante d'activation
dolibarr_del_const($db, 'MAIN_MODULE_SIG', -1);
echo "Constante MAIN_MODULE_SIG supprimée\n";

echo "Nettoyage terminé. Vous pouvez maintenant réactiver le module.\n";
