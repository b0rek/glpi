<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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


$NEEDED_ITEMS=array("computer","peripheral","printer","networking","reservation","tracking","document","user","group","link","phone","enterprise","infocom","contract");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if(!isset($_GET["ID"])) $_GET["ID"] = "";
if(!isset($_GET["sort"])) $_GET["sort"] = "";
if(!isset($_GET["order"])) $_GET["order"] = "";
if(!isset($_GET["withtemplate"])) $_GET["withtemplate"] = "";

$netdevice=new Netdevice();
if (isset($_POST["add"]))
{
	checkRight("networking","w");
	$newID=$netdevice->add($_POST);
	logEvent($newID, "networking", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][20]." :  ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["delete"]))
{
	checkRight("networking","w");
	if (!empty($_POST["withtemplate"]))
		$netdevice->delete($_POST,1);
	else $netdevice->delete($_POST);

	logEvent($_POST["ID"], "networking", 4, "inventory", $_SESSION["glpiname"] ." ".$LANG["log"][22]);
	if(!empty($_POST["withtemplate"])) 
		glpi_header($CFG_GLPI["root_doc"]."/front/setup.templates.php");
	else 
		glpi_header($CFG_GLPI["root_doc"]."/front/networking.php");
}
else if (isset($_POST["restore"]))
{
	checkRight("networking","w");
	$netdevice->restore($_POST);
	logEvent($_POST["ID"], "networking", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][23]);
	glpi_header($CFG_GLPI["root_doc"]."/front/networking.php");
}
else if (isset($_POST["purge"]) || isset($_GET["purge"]))
{
	checkRight("networking","w");

	if (isset($_POST["purge"]))
		$input["ID"]=$_POST["ID"];
	else
		$input["ID"] = $_GET["ID"];	
	
	$netdevice->delete($input,1);
	logEvent($input["ID"], "networking", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][24]);
	glpi_header($CFG_GLPI["root_doc"]."/front/networking.php");
}
else if (isset($_POST["update"]))
{
	checkRight("networking","w");
	$netdevice->update($_POST);
	logEvent($_POST["ID"], "networking", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else
{
	checkRight("networking","r");

	if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
	if (isset($_GET['onglet'])) {
		$_SESSION['glpi_onglet']=$_GET['onglet'];
		//		glpi_header($_SERVER['HTTP_REFERER']);
	}

	commonHeader($LANG["title"][6],$_SERVER['PHP_SELF'],"inventory","networking");

	if (!empty($_GET["withtemplate"])) {

		if ($netdevice->showForm($_SERVER['PHP_SELF'],$_GET["ID"], $_GET["withtemplate"])){
			if ($_GET["ID"]>0){
				switch($_SESSION['glpi_onglet']){
					case 4 :
						showInfocomForm($CFG_GLPI["root_doc"]."/front/infocom.form.php",NETWORKING_TYPE,$_GET["ID"],1,$_GET["withtemplate"]);
						showContractAssociated(NETWORKING_TYPE,$_GET["ID"],$_GET["withtemplate"]);
						break;
					case 5 :
						showDocumentAssociated(NETWORKING_TYPE,$_GET["ID"],$_GET["withtemplate"]);		
						break;

					default :
						if (!displayPluginAction(NETWORKING_TYPE,$_GET["ID"],$_SESSION['glpi_onglet'],$_GET["withtemplate"])){
							showPorts($_GET["ID"], NETWORKING_TYPE,$_GET["withtemplate"]);
							if ($_GET["withtemplate"]!=2) showPortsAdd($_GET["ID"],NETWORKING_TYPE);
						}
						break;
				}



			}
		}

	} else {

		if ($netdevice->showForm ($_SERVER['PHP_SELF'],$_GET["ID"])){
			switch($_SESSION['glpi_onglet']){
				case -1:
					showPortsAdd($_GET["ID"],NETWORKING_TYPE);
					showPorts($_GET["ID"],NETWORKING_TYPE);
					showInfocomForm($CFG_GLPI["root_doc"]."/front/infocom.form.php",NETWORKING_TYPE,$_GET["ID"]);
					showContractAssociated(NETWORKING_TYPE,$_GET["ID"]);
					showDocumentAssociated(NETWORKING_TYPE,$_GET["ID"],$_GET["withtemplate"]);
					showJobListForItem($_SESSION["glpiname"],NETWORKING_TYPE,$_GET["ID"],$_GET["sort"],$_GET["order"]);
					showOldJobListForItem($_SESSION["glpiname"],NETWORKING_TYPE,$_GET["ID"],$_GET["sort"],$_GET["order"]);
					showLinkOnDevice(NETWORKING_TYPE,$_GET["ID"]);
					displayPluginAction(NETWORKING_TYPE,$_GET["ID"],$_SESSION['glpi_onglet'],$_GET["withtemplate"]);
					break;
				case 4 :
					showInfocomForm($CFG_GLPI["root_doc"]."/front/infocom.form.php",NETWORKING_TYPE,$_GET["ID"]);
					showContractAssociated(NETWORKING_TYPE,$_GET["ID"]);
					break;
				case 5 :
					showDocumentAssociated(NETWORKING_TYPE,$_GET["ID"],$_GET["withtemplate"]);
					break;
				case 6 :
					showJobListForItem($_SESSION["glpiname"],NETWORKING_TYPE,$_GET["ID"],$_GET["sort"],$_GET["order"]);
					showOldJobListForItem($_SESSION["glpiname"],NETWORKING_TYPE,$_GET["ID"],$_GET["sort"],$_GET["order"]);
					break;
				case 7 :
					showLinkOnDevice(NETWORKING_TYPE,$_GET["ID"]);
					break;	
				case 10 :
					showNotesForm($_SERVER['PHP_SELF'],NETWORKING_TYPE,$_GET["ID"]);
					break;			
				case 11 :
					showDeviceReservations($_SERVER['PHP_SELF'],NETWORKING_TYPE,$_GET["ID"]);
					break;
				case 12 :
					showHistory(NETWORKING_TYPE,$_GET["ID"]);
					break;
				default :
					if (!displayPluginAction(NETWORKING_TYPE,$_GET["ID"],$_SESSION['glpi_onglet'],$_GET["withtemplate"])){
						showPortsAdd($_GET["ID"],NETWORKING_TYPE);
						showPorts($_GET["ID"],NETWORKING_TYPE);
					}
					break;
			}
		}
	}
	commonFooter();
}



?>
