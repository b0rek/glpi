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

// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------



$NEEDED_ITEMS=array("computer","software","infocom","contract");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if(!isset($_GET["lID"])) $_GET["lID"] = "";
if(!isset($_GET["sID"])) $_GET["sID"] = "";
if(!isset($_GET["withtemplate"])) $_GET["withtemplate"] = 0;


/*
$lic=new License;

if (isset($_POST["add"]))
{
	checkRight("software","w");

	if ($_POST["serial"]=="free"||$_POST["serial"]=="global"){
		$number=1;
	}else {
		$number=$_POST["number"];
	}
	unset($_POST["number"]);

	for ($i=1;$i<=$number;$i++){
		unset($lic->fields["ID"]);
		$lic->add($_POST);
	}

	logEvent($_POST["sID"], "software", 4, "inventory", $_SESSION["glpiname"]." added a license.");

	glpi_header($_SERVER['PHP_SELF']."?form=add&sID=".$_POST["sID"]);

}
else if (isset($_POST["update_stock_licenses"])||isset($_POST["update_stock_licenses_x"])){
	checkRight("software","w");

	foreach ($_POST as $key => $val){
		if (ereg("stock_licenses_([0-9]+)",$key,$regs)){
			if ($val!=$_POST["nb_licenses_".$regs[1]]){
				updateNumberLicenses($regs[1],$_POST["nb_licenses_".$regs[1]],$val);
			}
		}
	}
	glpi_header($_SERVER['HTTP_REFERER']);

}	
else if (isset($_POST["update_expire"])||isset($_POST["update_expire_x"])){
	checkRight("software","w");

	$lic=new License;
	$input["expire"]=$_POST["expire"];

	foreach ($_POST as $key => $val){
		if (ereg("license_([0-9]+)",$key,$ereg)){
			$input["ID"]=$ereg[1];
			$lic->update($input);
		}
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["update_buy"])||isset($_POST["update_buy_x"])){
	checkRight("software","w");

	$input["buy"]=$_POST["buy"];	

	foreach ($_POST as $key => $val){
		if (ereg("license_([0-9]+)",$key,$ereg)){
			$input["ID"]=$ereg[1];
			$lic->update($input);
		}
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["move"])||isset($_POST["move"])){
	if ($_POST["lID"]&&$lic->getFromDB($_POST["lID"])){
		unset($lic->fields["ID"]);
		unset($lic->fields["comments"]);

		foreach ($_POST as $key => $val){
			$lic=array();
			if (ereg("license_([0-9]+)",$key,$ereg)){
				$lic[]=$ereg[1]; 
			}
			if (count($lic)){ 
				moveLicensesToLicense($lic,$_POST["lID"]);
			}
		}
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["move_to_software"])||isset($_POST["move_to_software"])){
	//print_r($_POST);
	$soft=new Software;
	foreach ($_POST as $key => $val){
		if (ereg("license_([0-9]+)",$key,$ereg)){
			moveSimilarLicensesToSoftware($ereg[1],$_POST["sID"]);
		}
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["delete_similar_license"])){
	checkRight("software","w");
	
	foreach ($_POST as $key => $val){
		if (ereg("license_([0-9]+)",$key,$ereg)){
			deleteSimilarLicenses($ereg[1]);
		}
	}	
	logEvent(0, "software", 4, "inventory", $_SESSION["glpiname"]." deleted licenses.");
	glpi_header($_SERVER['HTTP_REFERER']." ");
}
else if (isset($_POST["delete_license"])){ 
	checkRight("software","w");
	
	foreach ($_POST as $key => $val){
		if (ereg("license_([0-9]+)",$key,$ereg)){
			$lic->delete(array("ID"=>$ereg[1]));
		}
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["update"])){
	checkRight("software","w");

	$lic->update($_POST);
	logEvent(0, "software", 4, "inventory", $_SESSION["glpiname"]." update a license.");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_GET["form"])){
	checkRight("software","w");

	commonHeader($LANG['Menu'][4],$_SERVER['PHP_SELF'],"inventory","software");
	showLicenseForm($_SERVER['PHP_SELF'],$_GET['form'],$_GET["sID"],$_GET["lID"]);
	commonFooter();
}
else if (isset($_GET["delete"])){
	checkRight("software","w");

	$lic->delete(array("ID"=>$_GET["ID"]));
	logEvent(0, "software", 4, "inventory", $_SESSION["glpiname"]." deleted a license.");
	glpi_header($_SERVER['HTTP_REFERER']." ");
}
else 
*/

if (isset($_POST["install"])){
	checkRight("software","w");
	installSoftwareVersion($_POST["cID"],$_POST["vID"]);
	logEvent($_POST["cID"], "computers", 5, "inventory", $_SESSION["glpiname"]." installed software.");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_GET["install"])){
	checkRight("software","w");
	installSoftwareVersion($_GET["cID"],$_GET["vID"]);
	logEvent($_GET["cID"], "computers", 5, "inventory", $_SESSION["glpiname"]." installed software.");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_GET["uninstall"])){
	checkRight("software","w");

	uninstallSoftwareVersion($_GET["ID"]);
	logEvent($_GET["cID"], "computers", 5, "inventory", $_SESSION["glpiname"]." uninstalled software.");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["deleteinstalls"])){
	checkRight("software","w");

	foreach ($_POST["item"] as $key => $val){
		if ($val==1) {
			uninstallSoftwareVersion($key);
			logEvent($_POST["sID"], "software", 5, "inventory", $_SESSION["glpiname"]." uninstalled software for several computers.");
		}
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["moveinstalls"])){
	checkRight("software","w");
	foreach ($_POST["item"] as $key => $val){
		if ($val==1 && $_POST['versionID']>0) {
			updateInstalledVersion($key, $_POST['versionID']);
			logEvent($_POST["sID"], "software", 5, "inventory", $_SESSION["glpiname"]." change version of versions installed on computers.");
		}
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["uninstall_license"])){
	checkRight("software","w");
	foreach ($_POST as $key => $val)
		if (preg_match("/license_([0-9]+)/",$key,$ereg)){
			$input["ID"]=$ereg[1];
			uninstallSoftwareVersion($input["ID"]);
		}
	logEvent($_POST["cID"], "computers", 5, "inventory", $_SESSION["glpiname"]." uninstalled software.");	
	glpi_header($_SERVER['HTTP_REFERER']);	
}
else if (isset($_POST["install_license"]) && isset($_POST["cID"])){
	checkRight("software","w");
	foreach ($_POST as $key => $val)
		if (preg_match("/version_([0-9]+)/",$key,$ereg)){
			if ($ereg[1]>0) {
				installSoftwareVersion($_POST["cID"],$ereg[1]);
			}
		}
	logEvent($_POST["cID"], "computers", 5, "inventory", $_SESSION["glpiname"]." installed software.");
	glpi_header($_SERVER['HTTP_REFERER']);	
}
/*
else if (isset($_GET["unglobalize"])&&isset($_GET["ID"])){
	unglobalizeLicense($_GET["ID"]);
	logEvent($_GET["sID"], "software", 4, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][60]);
	glpi_header($CFG_GLPI["root_doc"]."/front/software.form.php?ID=".$_GET["sID"]);
}*/
else if (isset($_GET["back"])){
	glpi_header($_GET["back"]);
}


?>
