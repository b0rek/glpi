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

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}


// CLASSES peripherals


class Typedoc  extends CommonDBTM {


	/**
	 * Constructor
	**/
	function __construct () {
		$this->table="glpi_type_docs";
		$this->type=TYPEDOC_TYPE;
	}


	function defineTabs($ID,$withtemplate){
		global $LANG;

		$ong=array();

		$ong[1]=$LANG['title'][26];

		return $ong;
	}

	/**
	 * Print the typedoc form
	 *
	 *@param $target form target
	 *@param $ID Integer : Id of the typedoc
	 *
	 *@return boolean : typedoc found
	 **/
	function showForm ($target,$ID) {

		global $CFG_GLPI, $LANG;

		if (!haveRight("typedoc","r")) return false;

		if ($ID > 0){
			$this->check($ID,'r');
		} else {
			// Create item 
			$this->check(-1,'w');
			$use_cache=false;
			$this->getEmpty();
		} 

		echo "<div class='center'>";
		$this->showTabs($ID, '',$_SESSION['glpi_tab']);

		echo "<form method='post' name=form action=\"$target\">";

		echo "<table class='tab_cadre_fixe' cellpadding='2'>";

		echo "<tr><th align='center'  colspan='2'>";
		if (empty($ID)){
			echo $LANG['document'][17];
		} else {
			echo $LANG['common'][2]." ".$this->fields["ID"];
		}

		echo "</th><th  align='center'  colspan='2'>".$LANG['common'][26]." : ".convDateTime($this->fields["date_mod"]);
		echo "</th></tr>";

		echo "<tr class='tab_bg_1'><td>".$LANG['common'][16].":	</td><td>";
		autocompletionTextField("name","glpi_type_docs","name",$this->fields["name"],40);
		echo "</td>";

		echo "<td>".$LANG['document'][9].":	</td><td>";
		autocompletionTextField("ext","glpi_type_docs","ext",$this->fields["ext"],40);

		echo "</td></tr>";

		echo "<tr class='tab_bg_1'><td>".$LANG['document'][10].":	</td><td>";
		dropdownIcons("icon",$this->fields["icon"],$CFG_GLPI["typedoc_icon_dir"]);
		if (!empty($this->fields["icon"])) echo "&nbsp;<img style='vertical-align:middle;' alt='' src='".$CFG_GLPI["typedoc_icon_dir"]."/".$this->fields["icon"]."'>";
		echo "</td>";

		echo "<td>".$LANG['document'][4].":	</td><td>";
		autocompletionTextField("mime","glpi_type_docs","mime",$this->fields["mime"],40);
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'><td>".$LANG['document'][11].":	</td><td>";
		dropdownYesNo("upload",$this->fields["upload"]);
		echo "</td><td colspan='2'>&nbsp;</td></tr>";

		if (haveRight("typedoc","w")) {
			echo "<tr>";
			if(empty($ID)){

				echo "<td class='tab_bg_2' valign='top' colspan='4'>";
				echo "<div class='center'><input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'></div>";
				echo "</td>";

			} else {

				echo "<td class='tab_bg_2' colspan='2' valign='top' align='center'>";
				echo "<input type='hidden' name='ID' value=\"$ID\">\n";
				echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit'>";
				echo "</td>";
				echo "<td class='tab_bg_2' colspan='2' valign='top'>\n";
				echo "<div class='center'>";
				echo "<input type='submit' name='delete' value=\"".$LANG['buttons'][6]."\" class='submit'>";
				echo "</div>";
				echo "</td>";
			}
			echo "</tr>";
		}

		echo "</table></form></div>";

		return true;	

	}

}

?>
