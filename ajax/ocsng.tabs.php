<?php


/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------
$NEEDED_ITEMS = array (
	"setup",
	"ocsng",
	"user",
	"search",
	"admininfo"
);

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
header_nocache();

if (!isset ($_POST["ID"])) {
	exit ();
}

checkRight("ocsng", "w");

$ocs = new Ocsng();
switch ($_POST['glpi_tab']) {
	case -1 :
			$ocs->showDBConnectionStatus($_POST["ID"]);
			$ocs->ocsFormImportOptions($_POST['target'], $_POST["ID"]);
			$ocs->ocsFormConfig($_POST['target'], $_POST["ID"]);
			$ocs->ocsFormAutomaticLinkConfig($_POST['target'], $_POST["ID"]);
		break;	
	case 1:
		$ocs->ocsFormImportOptions($_POST['target'], $_POST["ID"]);
		break;	
	case 2:
		$ocs->ocsFormConfig($_POST['target'], $_POST["ID"]);
		break;
	case 3 :
		$ocs->ocsFormAutomaticLinkConfig($_POST['target'], $_POST["ID"]);
		break;	
	default:
		if (!displayPluginAction(OCSNG_TYPE,$_POST["ID"],$_POST['glpi_tab'],"")){
			$ocs->showDBConnectionStatus($_POST["ID"]);
		}
		break;
}

ajaxFooter();
?>