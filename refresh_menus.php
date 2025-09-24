<?php
/* Copyright (C) 2025
 * Script utilitaire pour rafraîchir les menus du module SIG
 */

// Inclusion des fichiers Dolibarr
require_once __DIR__ . '/../../main.inc.php';

// Vérification des droits administrateur
if (!$user->admin) {
    accessforbidden('Vous devez être administrateur pour exécuter ce script');
}

print "<!DOCTYPE html>\n";
print "<html>\n";
print "<head>\n";
print "<title>Rafraîchissement des menus - Module SIG</title>\n";
print "<meta charset='UTF-8'>\n";
print "<style>\n";
print "body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }\n";
print ".container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 800px; }\n";
print ".success { color: #4CAF50; background: #e8f5e8; padding: 10px; border-radius: 4px; margin: 10px 0; }\n";
print ".error { color: #f44336; background: #ffebee; padding: 10px; border-radius: 4px; margin: 10px 0; }\n";
print ".info { color: #2196F3; background: #e3f2fd; padding: 10px; border-radius: 4px; margin: 10px 0; }\n";
print ".step { margin: 15px 0; padding: 10px; border-left: 4px solid #2196F3; background: #f8f9fa; }\n";
print "</style>\n";
print "</head>\n";
print "<body>\n";

print "<div class='container'>\n";
print "<h1>🔄 Rafraîchissement des menus - Module SIG</h1>\n";

print "<div class='info'>\n";
print "<strong>Information :</strong> Ce script va rafraîchir les menus du module SIG pour prendre en compte les nouvelles entrées.\n";
print "</div>\n";

// Étape 1: Supprimer les anciens menus
print "<div class='step'>\n";
print "<h3>Étape 1: Suppression des anciens menus</h3>\n";

$sql = "DELETE FROM ".MAIN_DB_PREFIX."menu WHERE module = 'sig' OR mainmenu = 'sig'";
$result = $db->query($sql);

if ($result) {
    $nb_deleted = $db->affected_rows($result);
    print "<div class='success'>✅ $nb_deleted anciens menus supprimés avec succès</div>\n";
} else {
    print "<div class='error'>❌ Erreur lors de la suppression des anciens menus: " . $db->lasterror() . "</div>\n";
}
print "</div>\n";

// Étape 2: Recharger le descripteur du module
print "<div class='step'>\n";
print "<h3>Étape 2: Rechargement du descripteur du module</h3>\n";

require_once __DIR__ . '/core/modules/modSig.class.php';
$module = new modSig($db);

print "<div class='success'>✅ Descripteur du module rechargé (version " . $module->version . ")</div>\n";
print "</div>\n";

// Étape 3: Recréer les menus
print "<div class='step'>\n";
print "<h3>Étape 3: Recréation des menus</h3>\n";

$menus_created = 0;
$errors = 0;

foreach ($module->menu as $menu_item) {
    // Construction de la requête d'insertion
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."menu (";
    $sql .= "module, enabled, menu_handler, type, mainmenu, leftmenu, fk_menu, ";
    $sql .= "url, titre, prefix, langs, position, perms, target, user, entity";
    $sql .= ") VALUES (";
    $sql .= "'sig', '" . $menu_item['enabled'] . "', 'all', '" . $menu_item['type'] . "', ";
    $sql .= "'" . $menu_item['mainmenu'] . "', '" . (isset($menu_item['leftmenu']) ? $menu_item['leftmenu'] : '') . "', ";
    $sql .= "'" . (isset($menu_item['fk_menu']) ? $menu_item['fk_menu'] : '') . "', ";
    $sql .= "'" . $menu_item['url'] . "', '" . $menu_item['titre'] . "', '', ";
    $sql .= "'" . $menu_item['langs'] . "', " . $menu_item['position'] . ", ";
    $sql .= "'" . $menu_item['perms'] . "', '" . (isset($menu_item['target']) ? $menu_item['target'] : '') . "', ";
    $sql .= $menu_item['user'] . ", " . $conf->entity;
    $sql .= ")";
    
    $result = $db->query($sql);
    if ($result) {
        $menus_created++;
        print "<div class='success'>✅ Menu créé: " . $menu_item['titre'] . " (" . $menu_item['type'] . ")</div>\n";
    } else {
        $errors++;
        print "<div class='error'>❌ Erreur lors de la création du menu " . $menu_item['titre'] . ": " . $db->lasterror() . "</div>\n";
    }
}

print "</div>\n";

// Résumé final
print "<div class='step'>\n";
print "<h3>📊 Résumé</h3>\n";

if ($errors == 0) {
    print "<div class='success'>\n";
    print "<strong>🎉 Succès !</strong><br>\n";
    print "• $menus_created menus créés avec succès<br>\n";
    print "• Aucune erreur détectée<br>\n";
    print "• Les nouveaux menus sont maintenant disponibles dans l'interface\n";
    print "</div>\n";
} else {
    print "<div class='error'>\n";
    print "<strong>⚠️ Terminé avec des erreurs</strong><br>\n";
    print "• $menus_created menus créés<br>\n";
    print "• $errors erreurs détectées\n";
    print "</div>\n";
}

print "</div>\n";

print "<div class='info'>\n";
print "<strong>Prochaines étapes :</strong><br>\n";
print "1. Rafraîchissez votre navigateur (F5)<br>\n";
print "2. Vérifiez que les nouveaux menus apparaissent dans le menu de gauche<br>\n";
print "3. Testez la navigation entre les différentes pages du module\n";
print "</div>\n";

print "<p style='text-align: center; margin-top: 30px;'>\n";
print "<a href='index.php' style='background: #2196F3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>🏠 Retour au module SIG</a>\n";
print "</p>\n";

print "</div>\n";
print "</body>\n";
print "</html>\n";

$db->close();
?> 