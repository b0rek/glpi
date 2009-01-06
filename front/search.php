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

$NEEDED_ITEMS=array("computer","search","software");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkCentralAccess();
commonHeader($LANG["search"][0],$_SERVER['PHP_SELF']);

if (isset($_GET["globalsearch"])){
	$_GET["reset_before"]=1;
	$_GET["display_type"]=GLOBAL_SEARCH;
	$types=array(COMPUTER_TYPE,MONITOR_TYPE,SOFTWARE_TYPE,NETWORKING_TYPE,PERIPHERAL_TYPE,PRINTER_TYPE,PHONE_TYPE);

	$ci=new CommonItem();
	
	foreach($types as $type){
		if (haveTypeRight($type,'r')){
			$page=ereg_replace('front/','',ereg_replace('.form','',$INFOFORM_PAGES[$type]));

			manageGetValuesInSearch($type,false,false);
			$_GET["contains"][0]=$_GET["globalsearch"];
			showList($type,$page,$_GET["field"],$_GET["contains"],$_GET["sort"],$_GET["order"],$_GET["start"],$_GET["deleted"],$_GET["link"],$_GET["distinct"],$_GET["link2"],$_GET["contains2"],$_GET["field2"],$_GET["type2"]);
			echo "<hr>";
		}
	}

}

commonFooter();
?>
