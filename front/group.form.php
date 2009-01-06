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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

$NEEDED_ITEMS=array("group","user");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if(empty($_GET["ID"])) $_GET["ID"] = "";

$group=new Group;
if (isset($_POST["add"]))
{
	checkRight("group","w");

	$newID=$group->add($_POST);
	logEvent($newID, "groups", 4, "setup", $_SESSION["glpiname"]." ".$LANG["log"][20]." ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["delete"]))
{
	checkRight("group","w");

	$group->delete($_POST);
	logEvent($_POST["ID"], "groups", 4, "setup", $_SESSION["glpiname"]." ".$LANG["log"][22]);
	glpi_header($CFG_GLPI["root_doc"]."/front/group.php");
}
else if (isset($_POST["update"]))
{
	checkRight("group","w");

	$group->update($_POST);
	logEvent($_POST["ID"], "groups", 4, "setup", $_SESSION["glpiname"]." ".$LANG["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["adduser"]))
{
	checkRight("group","w");

	addUserGroup($_POST["FK_users"],$_POST["FK_groups"]);

	logEvent($_POST["FK_groups"], "groups", 4, "setup", $_SESSION["glpiname"]." ".$LANG["log"][48]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["deleteuser"]))
{
	checkRight("group","w");

	if (count($_POST["item"]))
		foreach ($_POST["item"] as $key => $val)
			deleteUserGroup($key);

	logEvent($_POST["FK_groups"], "groups", 4, "setup", $_SESSION["glpiname"]." ".$LANG["log"][49]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else
{
	checkRight("group","r");

	if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
	if (isset($_GET['onglet'])) {
		$_SESSION['glpi_onglet']=$_GET['onglet'];
	}

	commonHeader($LANG["Menu"][36],$_SERVER['PHP_SELF'],"admin","group");

	if ($group->showForm($_SERVER['PHP_SELF'],$_GET["ID"])) {
		if (!empty($_GET['ID'])){
			switch($_SESSION['glpi_onglet']){
				case -1 :	
					showGroupUsers($_SERVER['PHP_SELF'],$_GET["ID"]);
					showGroupDevice($_GET["ID"]);
					displayPluginAction(GROUP_TYPE,$_GET["ID"],$_SESSION['glpi_onglet']);
					break;
				case 2 : 
					showGroupDevice($_GET["ID"]);
					break;

				default :
					if (!displayPluginAction(GROUP_TYPE,$_GET["ID"],$_SESSION['glpi_onglet'])){
						showGroupUsers($_SERVER['PHP_SELF'],$_GET["ID"]);
					}
					break;
			}
		}
	}	

	commonFooter();
}


?>
