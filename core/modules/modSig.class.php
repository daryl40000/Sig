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
$this->version = '1.0.0';
$this->const_name = 'MAIN_MODULE_SIG';
$this->picto = 'fa-gauge';

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
'fk_menu' => 0,
'type' => 'top',
'titre' => 'Pilotage',
'url' => '/custom/sig/index.php',
'langs' => 'sig@sig',
'position' => 100,
'enabled' => '1',
'perms' => '$user->rights->sig->read',
'target' => '',
'user' => 2,
'prefix' => 'sig',
'picto' => 'fa-gauge'
);

// Left menu: Accueil CA
$this->menu[] = array(
'fk_menu' => 'fk_mainmenu=sig',
'type' => 'left',
'titre' => 'ChiffreAffairesActuel',
'url' => '/custom/sig/index.php',
'langs' => 'sig@sig',
'position' => 101,
'enabled' => '1',
'perms' => '$user->rights->sig->read',
'target' => '',
'user' => 2,
'picto' => 'fa-gauge'
);

// Left menu: Trésorerie
$this->menu[] = array(
'fk_menu' => 'fk_mainmenu=sig',
'type' => 'left',
'titre' => 'Trésorerie',
'url' => '/custom/sig/tresorerie.php',
'langs' => 'sig@sig',
'position' => 102,
'enabled' => '1',
'perms' => '$user->rights->sig->read',
'target' => '',
'user' => 2,
'picto' => 'fa-coins'
);
}

public function init($options = '')
{
// Créer les tables si présentes dans /sig/sql/
$result = $this->_load_tables('/sig/sql/');
if ($result < 0) return -1;

// Initialisation générique (constantes, etc.)
$sql = array();
return $this->_init($sql, $options);
}

public function remove($options = '')
{
global $db;

// Supprimer explicitement la constante d'activation si elle existe
dolibarr_del_const($db, 'MAIN_MODULE_SIG', -1);

// Supprimer les entrées de menu du module
$sql = "DELETE FROM ".MAIN_DB_PREFIX."menu WHERE module = 'sig'";
$db->query($sql);

// Supprimer les droits du module
$sql = "DELETE FROM ".MAIN_DB_PREFIX."rights_def WHERE module = 'sig'";
$db->query($sql);

return $this->_remove($options);
}
}
