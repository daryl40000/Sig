<?php
/* Copyright (C) 2025
 * Module SIG - Pilotage de trésorerie et SIG
 */

// This file must be in custom/sig/core/modules/modSig.class.php

require_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modSig extends DolibarrModules
{
public function __construct($db)
{
global $langs, $conf;

$this->db = $db;

// Module identification
$this->numero = 104001; // Free id range for external modules
$this->family = 'financial';
$this->name = 'sig';
$this->description = 'Pilotage de trésorerie et Solde Intermédiaire de Gestion';
		// Module version
		$this->version = '0.8';
$this->const_name = 'MAIN_MODULE_SIG';
$this->picto = 'fa-tachometer-alt'; // Icône tableau de bord FontAwesome

// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
$this->name = preg_replace('/^mod/i', '', get_class($this));
// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
$this->description = "Module de pilotage financier complet : Tableau de bord avec suivi d'objectifs, Chiffre d'affaires, Trésorerie prévisionnelle et Soldes Intermédiaires de Gestion";

// Module directories automatically created on enable
$this->dirs = array('/sig/temp');

// Config page (appears in setup of the module)
$this->config_page_url = array('setup.php@sig');

// Dependencies
$this->depends = array();
$this->requiredby = array();
$this->conflictwith = array();
$this->phpmin = array(7, 4);
$this->need_dolibarr_version = array(16, -1); // 16+

// Constants to add on module enable
$this->const = array();

// Droits du module
$this->rights = array();
$this->rights_class = 'sig';
$r = 0;
$this->rights[$r][0] = 104001; // id droit
$this->rights[$r][1] = 'Lire les pages du module SIG';
$this->rights[$r][2] = 'r';
$this->rights[$r][3] = 1; // autorisé par défaut
$this->rights[$r][4] = 'read';
$r++;

// Menus
$this->menu = array();

// Top menu: Pilotage
$this->menu[] = array(
    'fk_menu' => '',
    'type' => 'top',
    'titre' => 'Pilotage',
    'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
    'mainmenu' => 'sig',
    'url' => '/custom/sig/index.php',
    'langs' => 'sig@sig',
    'position' => 100,
    'enabled' => '1',
    'perms' => '$user->rights->sig->read',
    'target' => '',
    'user' => 2
);

// Left menu: Tableau de bord
$this->menu[] = array(
    'fk_menu' => 'fk_mainmenu=sig',
    'type' => 'left',
    'titre' => 'Tableau de bord',
    'mainmenu' => 'sig',
    'leftmenu' => 'sig_dashboard',
    'url' => '/custom/sig/index.php',
    'langs' => 'sig@sig',
    'position' => 100,
    'enabled' => '1',
    'perms' => '$user->rights->sig->read',
    'target' => '',
    'user' => 2,
    'picto' => 'fa-tachometer-alt'
);

// Left menu: Accueil CA
$this->menu[] = array(
    'fk_menu' => 'fk_mainmenu=sig',
    'type' => 'left',
    'titre' => 'ChiffreAffairesActuel',
    'mainmenu' => 'sig',
    'leftmenu' => 'sig_ca',
    'url' => '/custom/sig/ca.php',
    'langs' => 'sig@sig',
    'position' => 101,
    'enabled' => '1',
    'perms' => '$user->rights->sig->read',
    'target' => '',
    'user' => 2,
    'picto' => 'fa-chart-bar'
);

// Left menu: Trésorerie
$this->menu[] = array(
    'fk_menu' => 'fk_mainmenu=sig',
    'type' => 'left',
    'titre' => 'Trésorerie',
    'mainmenu' => 'sig',
    'leftmenu' => 'sig_tresorerie',
    'url' => '/custom/sig/tresorerie.php',
    'langs' => 'sig@sig',
    'position' => 102,
    'enabled' => '1',
    'perms' => '$user->rights->sig->read',
    'target' => '',
    'user' => 2,
    'picto' => 'fa-coins'
);

// Left menu: SIG (Soldes Intermédiaires de Gestion)
$this->menu[] = array(
    'fk_menu' => 'fk_mainmenu=sig',
    'type' => 'left',
    'titre' => 'Soldes Intermédiaires de Gestion',
    'mainmenu' => 'sig',
    'leftmenu' => 'sig_sig',
    'url' => '/custom/sig/sig.php',
    'langs' => 'sig@sig',
    'position' => 103,
    'enabled' => '1',
    'perms' => '$user->rights->sig->read',
    'target' => '',
    'user' => 2,
    'picto' => 'fa-calculator'
);

// Left menu: Configuration
$this->menu[] = array(
    'fk_menu' => 'fk_mainmenu=sig',
    'type' => 'left',
    'titre' => 'Configuration',
    'mainmenu' => 'sig',
    'leftmenu' => 'sig_config',
    'url' => '/custom/sig/admin/setup.php',
    'langs' => 'sig@sig',
    'position' => 104,
    'enabled' => '1',
    'perms' => '$user->admin',
    'target' => '',
    'user' => 2,
    'picto' => 'fa-cogs'
);
}

public function init($options = '')
{
global $db, $conf;

// Créer les tables si présentes dans /sig/sql/
$result = $this->_load_tables('/sig/sql/');
if ($result < 0) return -1;

// Initialisation générique (constantes, etc.)
$sql = array();
$result = $this->_init($sql, $options);
if ($result < 0) return -1;

// Force la création des menus si ils n'existent pas
$sql_check = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."menu WHERE module = 'sig'";
$resql = $this->db->query($sql_check);
if ($resql) {
    $obj = $this->db->fetch_object($resql);
    if ($obj->nb == 0) {
        // Aucun menu trouvé, les créer manuellement
        foreach ($this->menu as $menu_config) {
            $sql_menu = "INSERT INTO ".MAIN_DB_PREFIX."menu (";
            $sql_menu .= "module, enabled, position, fk_menu, menutype, fk_mainmenu, fk_leftmenu, ";
            $sql_menu .= "url, titre, level, langs, perms, target, usertype, entity";
            $sql_menu .= ") VALUES (";
            $sql_menu .= "'sig', ";
            $sql_menu .= "'" . $this->db->escape($menu_config['enabled']) . "', ";
            $sql_menu .= "'" . (int)$menu_config['position'] . "', ";
            $sql_menu .= "'" . $this->db->escape(isset($menu_config['fk_menu']) ? $menu_config['fk_menu'] : '') . "', ";
            $sql_menu .= "'" . $this->db->escape($menu_config['type']) . "', ";
            $sql_menu .= "'" . (isset($menu_config['mainmenu']) ? $this->db->escape($menu_config['mainmenu']) : '') . "', ";
            $sql_menu .= "'" . (isset($menu_config['leftmenu']) ? $this->db->escape($menu_config['leftmenu']) : '') . "', ";
            $sql_menu .= "'" . $this->db->escape($menu_config['url']) . "', ";
            $sql_menu .= "'" . $this->db->escape($menu_config['titre']) . "', ";
            $sql_menu .= "0, ";
            $sql_menu .= "'" . $this->db->escape($menu_config['langs']) . "', ";
            $sql_menu .= "'" . $this->db->escape($menu_config['perms']) . "', ";
            $sql_menu .= "'" . $this->db->escape(isset($menu_config['target']) ? $menu_config['target'] : '') . "', ";
            $sql_menu .= "'" . (int)$menu_config['user'] . "', ";
            $sql_menu .= "1";
            $sql_menu .= ")";
            
            $this->db->query($sql_menu);
        }
    }
    $this->db->free($resql);
}

return 1;
}

public function remove($options = '')
{
global $db;

// Supprimer les entrées de menu du module
$sql = "DELETE FROM ".MAIN_DB_PREFIX."menu WHERE module = 'sig'";
$this->db->query($sql);

// Supprimer les droits du module
$sql = "DELETE FROM ".MAIN_DB_PREFIX."rights_def WHERE module = 'sig'";
$this->db->query($sql);

// Supprimer toutes les constantes du module
$constants_to_remove = array(
    'SIG_BANK_ACCOUNT',
    'SIG_MARGIN_RATE',
    'SIG_PAYMENT_DELAY',
    'SIG_INCLUDE_SUPPLIER_INVOICES',
    'SIG_INCLUDE_SOCIAL_CHARGES',
    'SIG_INCLUDE_SIGNED_QUOTES',
    'SIG_INCLUDE_CUSTOMER_INVOICES',
    'SIG_INCLUDE_CUSTOMER_TEMPLATE_INVOICES',
    'SIG_INCLUDE_UNPAID_SALARIES',
    'SIG_PROJECTION_UNCERTAINTY',
    'SIG_ACCOUNT_START',
    'SIG_ACCOUNT_END',
    'SIG_SOCIAL_CHARGES_RATE'
);

foreach ($constants_to_remove as $const_name) {
    dolibarr_del_const($db, $const_name, -1);
}

// Appel de la méthode parent
$result = $this->_remove($options);

// Supprimer explicitement la constante d'activation en dernier
dolibarr_del_const($db, 'MAIN_MODULE_SIG', -1);

return $result;
}
}
