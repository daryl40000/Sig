<?php
// Script pour réactiver le module SIG et mettre à jour les menus
require_once __DIR__ . '/../../main.inc.php';

if (!$user->admin) {
    die('Accès refusé - Admin requis');
}

echo "<h2>Mise à jour du module SIG</h2>";
echo "<p>Utilisateur connecté : " . $user->login . " (Admin: " . ($user->admin ? 'Oui' : 'Non') . ")</p>";

// Vérifier l'état actuel du module
$module_enabled = getDolGlobalString('MAIN_MODULE_SIG');
echo "<p>État actuel du module : " . ($module_enabled ? 'Activé' : 'Désactivé') . "</p>";

// Désactiver le module
dolibarr_del_const($db, 'MAIN_MODULE_SIG', -1);
echo "✓ Module désactivé<br>";

// Nettoyer les anciens menus
$sql = "DELETE FROM ".MAIN_DB_PREFIX."menu WHERE module = 'sig'";
$result = $db->query($sql);
if ($result) {
    echo "✓ Anciens menus supprimés (" . $db->affected_rows($result) . " entrées)<br>";
} else {
    echo "✗ Erreur lors de la suppression des menus : " . $db->lasterror() . "<br>";
}

// Nettoyer les anciens droits
$sql = "DELETE FROM ".MAIN_DB_PREFIX."rights_def WHERE module = 'sig'";
$result = $db->query($sql);
if ($result) {
    echo "✓ Anciens droits supprimés (" . $db->affected_rows($result) . " entrées)<br>";
} else {
    echo "✗ Erreur lors de la suppression des droits : " . $db->lasterror() . "<br>";
}

// Réactiver le module
dolibarr_set_const($db, 'MAIN_MODULE_SIG', '1', 'chaine', 0, '', 0);
echo "✓ Module réactivé<br>";

// Charger la classe du module pour recréer les menus
require_once __DIR__ . '/core/modules/modSig.class.php';
$module = new modSig($db);

// Afficher les menus qui vont être créés
echo "<h3>Menus à créer :</h3>";
foreach ($module->menu as $menu) {
    echo "- " . $menu['titre'] . " (" . $menu['type'] . ") -> " . $menu['url'];
    if (isset($menu['mainmenu'])) echo " [mainmenu: " . $menu['mainmenu'] . "]";
    if (isset($menu['leftmenu'])) echo " [leftmenu: " . $menu['leftmenu'] . "]";
    echo "<br>";
}

// Initialiser le module
echo "<h3>Initialisation du module...</h3>";
$result = $module->init();

if ($result >= 0) {
    echo "✓ Module initialisé avec succès<br>";
    
    // Forcer la création des menus manuellement si nécessaire
    echo "<h3>Création manuelle des menus...</h3>";
    
    foreach ($module->menu as $menu_config) {
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."menu (";
        $sql .= "module, enabled, position, fk_menu, menutype, fk_mainmenu, fk_leftmenu, ";
        $sql .= "url, titre, level, langs, perms, target, usertype, entity";
        $sql .= ") VALUES (";
        $sql .= "'sig', ";
        $sql .= "'" . $db->escape($menu_config['enabled']) . "', ";
        $sql .= "'" . (int)$menu_config['position'] . "', ";
        $sql .= "'" . $db->escape($menu_config['fk_menu']) . "', ";
        $sql .= "'" . $db->escape($menu_config['type']) . "', ";
        $sql .= "'" . (isset($menu_config['mainmenu']) ? $db->escape($menu_config['mainmenu']) : '') . "', ";
        $sql .= "'" . (isset($menu_config['leftmenu']) ? $db->escape($menu_config['leftmenu']) : '') . "', ";
        $sql .= "'" . $db->escape($menu_config['url']) . "', ";
        $sql .= "'" . $db->escape($menu_config['titre']) . "', ";
        $sql .= "0, ";
        $sql .= "'" . $db->escape($menu_config['langs']) . "', ";
        $sql .= "'" . $db->escape($menu_config['perms']) . "', ";
        $sql .= "'" . $db->escape($menu_config['target']) . "', ";
        $sql .= "'" . (int)$menu_config['user'] . "', ";
        $sql .= "1";
        $sql .= ")";
        
        $result_menu = $db->query($sql);
        if ($result_menu) {
            echo "✓ Menu '" . $menu_config['titre'] . "' créé<br>";
        } else {
            echo "✗ Erreur création menu '" . $menu_config['titre'] . "': " . $db->lasterror() . "<br>";
        }
    }
    
    // Vérifier que les menus ont bien été créés
    $sql = "SELECT * FROM ".MAIN_DB_PREFIX."menu WHERE module = 'sig' ORDER BY position";
    $resql = $db->query($sql);
    if ($resql) {
        $nb_menus = $db->num_rows($resql);
        echo "<h3>Menus créés en base (" . $nb_menus . ") :</h3>";
        while ($obj = $db->fetch_object($resql)) {
            echo "- ID: " . $obj->rowid . ", Titre: " . $obj->titre . ", Type: " . $obj->menutype . ", URL: " . $obj->url;
            if ($obj->fk_mainmenu) echo " [mainmenu: " . $obj->fk_mainmenu . "]";
            echo "<br>";
        }
        $db->free($resql);
    }
    
    echo "<br><strong style='color: green;'>Le module SIG a été mis à jour avec succès !</strong><br>";
    echo "<p>Vous devriez maintenant voir :</p>";
    echo "<ul>";
    echo "<li>Le menu 'Pilotage' en haut de la page avec l'icône tableau de bord</li>";
    echo "<li>Les sous-menus dans le menu de gauche :</li>";
    echo "<ul>";
    echo "<li>'Chiffre d'affaires actuel' (icône graphique en barres)</li>";
    echo "<li>'Trésorerie' (icône pièces)</li>";
    echo "</ul>";
    echo "</ul>";
    echo "<p><strong>Nouvelles icônes :</strong></p>";
    echo "<ul>";
    echo "<li>Module : fa-tachometer-alt (tableau de bord)</li>";
    echo "<li>Menu principal : fa-tachometer-alt</li>";
    echo "<li>CA Actuel : fa-chart-bar</li>";
    echo "<li>Trésorerie : fa-coins</li>";
    echo "</ul>";
    echo "<p><a href='index.php'>→ Aller à la page d'accueil du module</a></p>";
} else {
    echo "✗ Erreur lors de la réinitialisation du module (code: " . $result . ")<br>";
}
?> 