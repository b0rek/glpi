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

$NEEDED_ITEMS=array("contact","enterprise","link","document");
define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if(empty($_GET["ID"])) $_GET["ID"] = -1;

$contact=new Contact;
if (isset($_POST["add"])){
	$contact->check(-1,'w',$_POST['FK_entities']);

	$newID=$contact->add($_POST);
	logEvent($newID, "contacts", 4, "financial", $_SESSION["glpiname"]." ".$LANG["log"][20]." ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["delete"]))
{
	$contact->check($_POST["ID"],'w');

	$contact->delete($_POST);
	logEvent($_POST["ID"], "contacts", 4, "financial", $_SESSION["glpiname"]." ".$LANG["log"][22]);
	glpi_header($CFG_GLPI["root_doc"]."/front/contact.php");
}
else if (isset($_POST["restore"]))
{
	$contact->check($_POST["ID"],'w');

	$contact->restore($_POST);
	logEvent($_POST["ID"], "contacts", 4, "financial", $_SESSION["glpiname"]." ".$LANG["log"][23]);
	glpi_header($CFG_GLPI["root_doc"]."/front/contact.php");
}
else if (isset($_POST["purge"]))
{
	$contact->check($_POST["ID"],'w');

	$contact->delete($_POST,1);
	logEvent($_POST["ID"], "contacts", 4, "financial", $_SESSION["glpiname"]." ".$LANG["log"][24]);
	glpi_header($CFG_GLPI["root_doc"]."/front/contact.php");
}
else if (isset($_POST["update"]))
{
	$contact->check($_POST["ID"],'w');

	$contact->update($_POST);
	logEvent($_POST["ID"], "contacts", 4, "financial", $_SESSION["glpiname"]." ".$LANG["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["addenterprise"]))
{
	$contact->check($_POST["conID"],'w');

	addContactEnterprise($_POST["entID"],$_POST["conID"]);
	logEvent($_POST["conID"], "contacts", 4, "financial", $_SESSION["glpiname"]."  ".$LANG["log"][34]);
	glpi_header($CFG_GLPI["root_doc"]."/front/contact.form.php?ID=".$_POST["conID"]);
}
else if (isset($_GET["deleteenterprise"]))
{
	$contact->check($_GET["cID"],'w');

	deleteContactEnterprise($_GET["ID"]);
	logEvent($_GET["cID"], "contacts", 4, "financial", $_SESSION["glpiname"]."  ".$LANG["log"][35]);
	glpi_header($_SERVER['HTTP_REFERER']);
}

else
{
	$contact->check($_GET["ID"],'r');

	if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
	if (isset($_GET['onglet'])) {
		$_SESSION['glpi_onglet']=$_GET['onglet'];
	}

	commonHeader($LANG["Menu"][22],$_SERVER['PHP_SELF'],"financial","contact");

	if ($contact->showForm($_SERVER['PHP_SELF'],$_GET["ID"],'')) {
		if ($_GET['ID']>0){
			switch($_SESSION['glpi_onglet']){
				case -1 :	
					showEnterpriseContact($_GET["ID"]);
					showDocumentAssociated(CONTACT_TYPE,$_GET["ID"]);
					showLinkOnDevice(CONTACT_TYPE,$_GET["ID"]);
					displayPluginAction(CONTACT_TYPE,$_GET["ID"],$_SESSION['glpi_onglet']);
					break;
				case 5 : 
					showDocumentAssociated(CONTACT_TYPE,$_GET["ID"]);
					break;
				case 7 : 
					showLinkOnDevice(CONTACT_TYPE,$_GET["ID"]);
					break;
				case 10 :
					showNotesForm($_SERVER['PHP_SELF'],CONTACT_TYPE,$_GET["ID"]);
					break;
				default :
					if (!displayPluginAction(CONTACT_TYPE,$_GET["ID"],$_SESSION['glpi_onglet'])){
						showEnterpriseContact($_GET["ID"]);
					}
					break;
			}
		}
	}	

	commonFooter();
}


?>
