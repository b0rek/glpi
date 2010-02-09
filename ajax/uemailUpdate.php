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

	define('GLPI_ROOT','..');
	$AJAX_INCLUDE=1;
	include (GLPI_ROOT."/inc/includes.php");
	
	// Send UTF8 Headers
	header("Content-Type: text/html; charset=UTF-8");
	header_nocache();
	
	checkCentralAccess();
	
	$query="SELECT email from glpi_users WHERE ID='".$_POST["value"]."'";
	$email="";
	if ($result=$DB->query($query)){
		if ($DB->numrows($result)){
			$email=$DB->result($result,0,"email");
		}
	}
	echo "<input type='text' size='30' name='uemail' value='$email'>";

	echo "<script type='text/javascript' >\n";
	if (!empty($email)){
		echo "window.document.getElementById('dropdownyesno_emailupdates').value='1';";
	} else {
		echo "window.document.getElementById('dropdownyesno_emailupdates').value='0';";
	}
	echo "</script>\n";

?>
