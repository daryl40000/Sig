<?php
// Script pour réactiver le module SIG et mettre à jour les menus
require_once __DIR__ . '/../../main.inc.php';

if (!$user->admin) {
    die('Accès refusé - Admin requis');
}

echo "<h2>Mise à jour du module SIG</h2>";

// Désactiver le module
dolibarr_del_const($db, 'MAIN_MODULE_SIG', -1);
echo "✓ Module désactivé<br>";

// Nettoyer les anciens menus
$sql = "DELETE FROM ".MAIN_DB_PREFIX."menu WHERE module = 'sig'";
$db->query($sql);
echo "✓ Anciens menus supprimés<br>";

// Nettoyer les anciens droits
$sql = "DELETE FROM ".MAIN_DB_PREFIX."rights_def WHERE module = 'sig'";
$db->query($sql);
echo "✓ Anciens droits supprimés<br>";

// Réactiver le module
dolibarr_set_const($db, 'MAIN_MODULE_SIG', '1', 'chaine', 0, '', 0);
echo "✓ Module réactivé<br>";

// Charger la classe du module pour recréer les menus
require_once __DIR__ . '/core/modules/modSig.class.php';
$module = new modSig($db);
$result = $module->init();

if ($result >= 0) {
    echo "✓ Menus et droits recréés avec succès<br>";
    echo "<br><strong>Le module SIG a été mis à jour !</strong><br>";
    echo "Vous pouvez maintenant voir le nouveau menu 'Trésorerie' dans le menu de gauche.";
} else {
    echo "✗ Erreur lors de la réinitialisation du module<br>";
}
?> 