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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
$NEEDED_ITEMS=array("central","tracking","computer","printer","monitor","peripheral","networking","software","user","group","setup","planning","phone","reminder","enterprise","contract");
include (GLPI_ROOT."/inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
header_nocache();

	checkCentralAccess();

	if (!isset($_POST['glpi_tab'])){
		$_POST['glpi_tab']="my";
	}

	switch ($_POST['glpi_tab']){
		case "my" :
			showCentralMyView();
			break;
		case "global" :
			showCentralGlobalView();
			break;
		case "group" :
			showCentralGroupView();
			break;
		case -1 : // all
			showCentralMyView();
			echo "<br>";
			showCentralGroupView();
			echo "<br>";
			showCentralGlobalView();
			echo "<br>";
			displayPluginAction("central","",$_POST['glpi_tab'],"");
			break;
		default :
			if (!displayPluginAction("central","",$_POST['glpi_tab'],""))
				showCentralMyView();		
			break;
	}
	ajaxFooter();

?>
