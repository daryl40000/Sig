<?php
// Script de diagnostic des menus du module SIG
require_once __DIR__ . '/../../main.inc.php';

if (!$user->admin) {
    die('Accès refusé - Admin requis');
}

echo "<h2>Diagnostic des menus du module SIG</h2>";

// Vérifier l'état du module
$module_enabled = getDolGlobalString('MAIN_MODULE_SIG');
echo "<p><strong>Module SIG activé :</strong> " . ($module_enabled ? 'OUI' : 'NON') . "</p>";

if (!$module_enabled) {
    echo "<p style='color: red;'>Le module SIG n'est pas activé ! Activez-le d'abord.</p>";
    exit;
}

// Vérifier les droits de l'utilisateur
echo "<h3>Droits de l'utilisateur</h3>";
echo "<p>Utilisateur : " . $user->login . " (Admin: " . ($user->admin ? 'Oui' : 'Non') . ")</p>";
echo "<p>Droits SIG : " . (isset($user->rights->sig->read) && $user->rights->sig->read ? 'OUI' : 'NON') . "</p>";

// Vérifier les menus en base de données
echo "<h3>Menus en base de données</h3>";
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."menu WHERE module = 'sig' ORDER BY type, position";
$resql = $db->query($sql);

if ($resql) {
    $nb_menus = $db->num_rows($resql);
    echo "<p>Nombre de menus trouvés : <strong>" . $nb_menus . "</strong></p>";
    
    if ($nb_menus > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Type</th><th>Titre</th><th>URL</th><th>MainMenu</th><th>LeftMenu</th><th>Enabled</th><th>Perms</th></tr>";
        
        while ($obj = $db->fetch_object($resql)) {
            echo "<tr>";
            echo "<td>" . $obj->rowid . "</td>";
            echo "<td>" . $obj->type . "</td>";
            echo "<td>" . $obj->titre . "</td>";
            echo "<td>" . $obj->url . "</td>";
            echo "<td>" . $obj->mainmenu . "</td>";
            echo "<td>" . $obj->leftmenu . "</td>";
            echo "<td>" . $obj->enabled . "</td>";
            echo "<td>" . $obj->perms . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>Aucun menu trouvé ! Le module n'a pas été correctement initialisé.</p>";
    }
    $db->free($resql);
} else {
    echo "<p style='color: red;'>Erreur SQL : " . $db->lasterror() . "</p>";
}

// Vérifier les droits en base
echo "<h3>Droits en base de données</h3>";
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."rights_def WHERE module = 'sig'";
$resql = $db->query($sql);

if ($resql) {
    $nb_rights = $db->num_rows($resql);
    echo "<p>Nombre de droits trouvés : <strong>" . $nb_rights . "</strong></p>";
    
    if ($nb_rights > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Libellé</th><th>Module</th><th>Perms</th></tr>";
        
        while ($obj = $db->fetch_object($resql)) {
            echo "<tr>";
            echo "<td>" . $obj->id . "</td>";
            echo "<td>" . $obj->libelle . "</td>";
            echo "<td>" . $obj->module . "</td>";
            echo "<td>" . $obj->perms . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    $db->free($resql);
}

// Vérifier les constantes
echo "<h3>Constantes du module</h3>";
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."const WHERE name LIKE '%SIG%'";
$resql = $db->query($sql);

if ($resql) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Nom</th><th>Valeur</th><th>Type</th></tr>";
    
    while ($obj = $db->fetch_object($resql)) {
        echo "<tr>";
        echo "<td>" . $obj->name . "</td>";
        echo "<td>" . $obj->value . "</td>";
        echo "<td>" . $obj->type . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    $db->free($resql);
}

echo "<h3>Actions recommandées</h3>";
if ($nb_menus == 0) {
    echo "<p style='color: red;'>Problème détecté : Aucun menu en base de données.</p>";
    echo "<p><a href='refresh_module.php' style='background: #007cba; color: white; padding: 10px; text-decoration: none;'>→ Exécuter le script de réparation</a></p>";
} else {
    echo "<p style='color: green;'>Les menus semblent correctement configurés en base.</p>";
    echo "<p>Si les menus ne s'affichent toujours pas :</p>";
    echo "<ul>";
    echo "<li>Vérifiez que vous cliquez bien sur 'Pilotage' dans le menu du haut</li>";
    echo "<li>Videz le cache de Dolibarr (dossier documents/admin/temp/)</li>";
    echo "<li>Déconnectez-vous et reconnectez-vous</li>";
    echo "<li>Vérifiez les droits utilisateur dans Configuration > Utilisateurs</li>";
    echo "</ul>";
}

echo "<p><a href='index.php'>→ Retour à l'accueil du module</a></p>";
?> 