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



$NEEDED_ITEMS=array("printer","computer","networking","reservation","tracking","cartridge","contract","infocom","document","user","group","link","enterprise","ocsng");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");


if(!isset($_GET["ID"])) $_GET["ID"] = "";
if(!isset($_GET["sort"])) $_GET["sort"] = "";
if(!isset($_GET["order"])) $_GET["order"] = "";

if(!isset($_GET["withtemplate"])) $_GET["withtemplate"] = "";

$print=new Printer();
if (isset($_POST["add"]))
{
	checkRight("printer","w");

	$newID=$print->add($_POST);
	logEvent($newID, "printers", 4, "inventory", $_SESSION["glpiname"]."  ".$LANG["log"][20]."  ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["delete"]))
{
	checkRight("printer","w");

	if (!empty($_POST["withtemplate"]))
		$print->delete($_POST,1);
	else $print->delete($_POST);
	logEvent($_POST["ID"], "printers", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][22]);
	if(!empty($_POST["withtemplate"])) 
		glpi_header($CFG_GLPI["root_doc"]."/front/setup.templates.php");
	else 
		glpi_header($CFG_GLPI["root_doc"]."/front/printer.php");
}
else if (isset($_POST["restore"]))
{
	checkRight("printer","w");
	$print->restore($_POST);
	logEvent($_POST["ID"], "printers", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][23]);
	glpi_header($CFG_GLPI["root_doc"]."/front/printer.php");
}
else if (isset($_POST["purge"]) || isset($_GET["purge"]))
{
	checkRight("printer","w");

	if (isset($_POST["purge"]))
		$input["ID"]=$_POST["ID"];
	else
		$input["ID"] = $_GET["ID"];	

	$print->delete($input,1);
	logEvent($input["ID"], "printers", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][24]);
	glpi_header($CFG_GLPI["root_doc"]."/front/printer.php");
}
else if (isset($_POST["update"]))
{
	checkRight("printer","w");

	$print->update($_POST);
	logEvent($_POST["ID"], "printers", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_GET["unglobalize"]))
{
	checkRight("printer","w");

	unglobalizeDevice(PRINTER_TYPE,$_GET["ID"]);
	logEvent($_GET["ID"], "printers", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][60]);
	glpi_header($CFG_GLPI["root_doc"]."/front/printer.form.php?ID=".$_GET["ID"]);
}
else if (isset($_GET["disconnect"]))
{
	checkRight("printer","w");
	Disconnect($_GET["ID"]);
	logEvent(0, "printers", 5, "inventory", $_SESSION["glpiname"]."  ".$LANG["log"][26]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if(isset($_POST["connect"])&&isset($_POST["item"])&&$_POST["item"]>0)
{
	checkRight("printer","w");
	Connect($_POST["sID"],$_POST["item"],PRINTER_TYPE);
	logEvent($_POST["sID"], "printers", 4, "inventory", $_SESSION["glpiname"]."  ".$LANG["log"][27]);
	glpi_header($CFG_GLPI["root_doc"]."/front/printer.form.php?ID=".$_POST["sID"]);
}
else
{
	checkRight("printer","r");

	commonHeader($LANG["Menu"][2],$_SERVER['PHP_SELF'],"inventory","printer");

	if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
	if (isset($_GET['onglet'])) {
		$_SESSION['glpi_onglet']=$_GET['onglet'];
	}	

	if (!empty($_GET["withtemplate"])) {

		if ($print->showForm($_SERVER['PHP_SELF'],$_GET["ID"], $_GET["withtemplate"])){

			if ($_GET["ID"]>0){
				switch($_SESSION['glpi_onglet']){
					case 3 :
						if ($_GET["withtemplate"]!=2)	showPortsAdd($_GET["ID"],PRINTER_TYPE);
						showPorts($_GET["ID"], PRINTER_TYPE,$_GET["withtemplate"]);
						break;

					case 4 :			
						showInfocomForm($CFG_GLPI["root_doc"]."/front/infocom.form.php",PRINTER_TYPE,$_GET["ID"],1,$_GET["withtemplate"]);	
						showContractAssociated(PRINTER_TYPE,$_GET["ID"],$_GET["withtemplate"]);
						break;
					case 5 :
						showDocumentAssociated(PRINTER_TYPE,$_GET["ID"],$_GET["withtemplate"]);	
						break;
					default :
						displayPluginAction(PRINTER_TYPE,$_GET["ID"],$_SESSION['glpi_onglet'],$_GET["withtemplate"]);
						break;
				}	
			}
		}

	} else {

		if ($print->showForm($_SERVER['PHP_SELF'],$_GET["ID"], $_GET["withtemplate"])){

			switch($_SESSION['glpi_onglet']){
				case -1:
					showCartridgeInstalled($_GET["ID"]);
					showCartridgeInstalled($_GET["ID"],1);		
					showConnect($_SERVER['PHP_SELF'],$_GET["ID"],PRINTER_TYPE);
					showPortsAdd($_GET["ID"],PRINTER_TYPE);	
					showPorts($_GET["ID"], PRINTER_TYPE,$_GET["withtemplate"]);
					showInfocomForm($CFG_GLPI["root_doc"]."/front/infocom.form.php",PRINTER_TYPE,$_GET["ID"]);
					showContractAssociated(PRINTER_TYPE,$_GET["ID"]);
					showDocumentAssociated(PRINTER_TYPE,$_GET["ID"]);
					showJobListForItem($_SESSION["glpiname"],PRINTER_TYPE,$_GET["ID"],$_GET["sort"],$_GET["order"]);
					showOldJobListForItem($_SESSION["glpiname"],PRINTER_TYPE,$_GET["ID"],$_GET["sort"],$_GET["order"]);		
					showLinkOnDevice(PRINTER_TYPE,$_GET["ID"]);
					displayPluginAction(PRINTER_TYPE,$_GET["ID"],$_SESSION['glpi_onglet'],$_GET["withtemplate"]);
					break;
				case 3 :			
					showConnect($_SERVER['PHP_SELF'],$_GET["ID"],PRINTER_TYPE);
					showPortsAdd($_GET["ID"],PRINTER_TYPE);	
					showPorts($_GET["ID"], PRINTER_TYPE,$_GET["withtemplate"]);
					break;
				case 4 :	
					showInfocomForm($CFG_GLPI["root_doc"]."/front/infocom.form.php",PRINTER_TYPE,$_GET["ID"]);
					showContractAssociated(PRINTER_TYPE,$_GET["ID"]);
					break;
				case 5 :
					showDocumentAssociated(PRINTER_TYPE,$_GET["ID"]);
					break;
				case 6 :	
					showJobListForItem($_SESSION["glpiname"],PRINTER_TYPE,$_GET["ID"],$_GET["sort"],$_GET["order"]);
					showOldJobListForItem($_SESSION["glpiname"],PRINTER_TYPE,$_GET["ID"],$_GET["sort"],$_GET["order"]);	
					break;
				case 7 :
					showLinkOnDevice(PRINTER_TYPE,$_GET["ID"]);
					break;	

				case 10 :
					showNotesForm($_SERVER['PHP_SELF'],PRINTER_TYPE,$_GET["ID"]);
					break;
				case 11 :
					showDeviceReservations($_SERVER['PHP_SELF'],PRINTER_TYPE,$_GET["ID"]);
					break;
				case 12 :
					showHistory(PRINTER_TYPE,$_GET["ID"]);
					break;
				default :
					if (!displayPluginAction(PRINTER_TYPE,$_GET["ID"],$_SESSION['glpi_onglet'],$_GET["withtemplate"])){
						showCartridgeInstalled($_GET["ID"]);		
						showCartridgeInstalled($_GET["ID"],1);
					}
					break;
			}		


		}
	}
	commonFooter();
}


?>
