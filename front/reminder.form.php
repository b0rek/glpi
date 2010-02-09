<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------



$NEEDED_ITEMS=array("reminder","tracking","user");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if(!isset($_GET["ID"])) $_GET["ID"] = "";

$remind=new Reminder();
checkCentralAccess();
if (isset($_POST["add"]))
{
	/// TODO : Not do a getEmpty / check to do in add process : set fields and check rights to add (private case ...) 
	$remind->getEmpty();
	$remind->check(-1,'w',$_POST['FK_entities']);

	$newID=$remind->add($_POST);
	logEvent($newID, "reminder", 4, "tools", $_SESSION["glpiname"]." added ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
} 
else if (isset($_POST["delete"]))
{
	$remind->check($_POST["ID"],'w');	

	$remind->delete($_POST);
	logEvent($_POST["ID"], "reminder", 4, "tools", $_SESSION["glpiname"]." ".$LANG['log'][22]);
	glpi_header($CFG_GLPI["root_doc"]."/front/reminder.php");
}
else if (isset($_POST["update"]))
{
	$remind->check($_POST["ID"],'w');	

	$remind->update($_POST);
	logEvent($_POST["ID"], "reminder", 4, "tools", $_SESSION["glpiname"]." ".$LANG['log'][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else
{
	commonHeader($LANG['title'][40],$_SERVER['PHP_SELF'],"utils","reminder");
	$remind->showForm($_SERVER['PHP_SELF'],$_GET["ID"]);

	commonFooter();
}

?>
