<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

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

include ("_relpos.php");

include ($phproot . "/inc/includes.php");

checkRight("reports","r");

commonHeader($lang["title"][16],$_SERVER['PHP_SELF']);



# Titre


echo "<form name='form' method='post' action='report.year.list.php'>";

echo "<div align='center'>";
echo "<table class='tab_cadre' >";
echo "<tr><th align='center' colspan='2' ><big><b>".$lang["reports"][58]."</b></big></th></tr>";

# 3. Selection d'affichage pour generer la liste

echo "<tr class='tab_bg_2'>";
echo "<td width='150'  align='center'>";
echo "<p><b>".$lang["reports"][12]."</b></p> ";
echo "<p><select name='item_type[]' size='8'  multiple>";
echo "<option value='0' selected>".$lang["reports"][16]."</option>";
echo "<option value='".COMPUTER_TYPE."'>".$lang["reports"][6]."</option>";
echo "<option value='".PRINTER_TYPE."'>".$lang["reports"][7]."</option>";
echo "<option value='".NETWORKING_TYPE."'>".$lang["reports"][8]."</option>";
echo "<option value='".MONITOR_TYPE."'>".$lang["reports"][9]."</option>";
echo "<option value='".PERIPHERAL_TYPE."'>".$lang["reports"][29]."</option>";
echo "<option value='".SOFTWARE_TYPE."'>".$lang["reports"][55]."</option>";
echo "<option value='".PHONE_TYPE."'>".$lang["reports"][64]."</option>";
echo "</select> </p></td>";

echo "<td width='150' align='center'><p><b>".$lang["reports"][23]."</b></p> ";
echo "<p> <select name='annee[]'  size='8' multiple>";
echo " <option value='toutes' selected>".$lang["reports"][16]."</option>";
$y = date("Y");
for ($i=$y-10;$i<$y+10;$i++)
{
	echo " <option value='$i'>$i</option>";
}
echo "</select></p></td></tr>";

echo "<tr class='tab_bg_2'><td colspan='2'  align='center'><p><input type='submit' value='".$lang["reports"][15]."' class='submit'></p></td></tr>";


echo "</table>";
echo "</div>";
echo "</form>";



commonFooter();

?>
