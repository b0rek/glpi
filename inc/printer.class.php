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

// CLASSES Printers


class Printer  extends CommonDBTM {

	/**
	 * Constructor
	**/
	function __construct () {
		$this->table="glpi_printers";
		$this->type=PRINTER_TYPE;
		$this->dohistory=true;
		$this->entity_assign=true;
		$this->may_be_recursive=true;
	}	

	function defineTabs($ID,$withtemplate){
		global $LANG,$CFG_GLPI;

		$ong=array();
		if ($ID > 0 ){
			if (haveRight("cartridge","r"))	{
				$ong[1]=$LANG['Menu'][21];
			}
			if (haveRight("networking","r")||haveRight("computer","r")){
				$ong[3]=$LANG['title'][27];
			}
			if (haveRight("contract","r") || haveRight("infocom","r")){
				$ong[4]=$LANG['Menu'][26];
			}
			if (haveRight("document","r")){
				$ong[5]=$LANG['Menu'][27];
			}
	
			if(empty($withtemplate)){
				if (haveRight("show_all_ticket","1")){
					$ong[6]=$LANG['title'][28];
				}
				if (haveRight("link","r")){
					$ong[7]=$LANG['title'][34];
				}
				if (haveRight("notes","r")){
					$ong[10]=$LANG['title'][37];
				}
				if (haveRight("reservation_central","r")){
					$ong[11]=$LANG['Menu'][17];
				}
					
				$ong[12]=$LANG['title'][38];
	
			}	
		} else { // New item
			$ong[1]=$LANG['title'][26];
		}
		return $ong;
	}

	/**
	 * Can I change recusvive flag to false
	 * check if there is "linked" object in another entity
	 * 
	 * Overloaded from CommonDBTM
	 *
	 * @return booleen
	 **/
	function canUnrecurs () {

		global $DB, $CFG_GLPI, $LINK_ID_TABLE;
		
		$ID  = $this->fields['ID'];

		if ($ID<0 || !$this->fields['recursive']) {
			return true;
		}

		if (!parent::canUnrecurs()) {
			return false;
		}
		$entities = "(".$this->fields['FK_entities'];
		foreach (getEntityAncestors($this->fields['FK_entities']) as $papa) {
			$entities .= ",$papa";
		}
		$entities .= ")";

		// RELATION : printers -> _port -> _wire -> _port -> device

		// Evaluate connection in the 2 ways
		for ($tabend=array("end1"=>"end2","end2"=>"end1");list($enda,$endb)=each($tabend);) {
			
			$sql="SELECT device_type, GROUP_CONCAT(DISTINCT on_device) AS ids " .
				"FROM glpi_networking_wire, glpi_networking_ports " .
				"WHERE glpi_networking_wire.$endb = glpi_networking_ports.ID " .
				"AND   glpi_networking_wire.$enda IN (SELECT ID FROM glpi_networking_ports 
									WHERE device_type=".PRINTER_TYPE." AND on_device='$ID') " .
				"GROUP BY device_type;";

			$res = $DB->query($sql);
			if ($res) while ($data = $DB->fetch_assoc($res)) {

				// For each device_type which are entity dependant
				if (isset($LINK_ID_TABLE[$data["device_type"]]) && 
					in_array($table=$LINK_ID_TABLE[$data["device_type"]], $CFG_GLPI["specif_entities_tables"])) {
	
					if (countElementsInTable("$table", "ID IN (".$data["ids"].") AND FK_entities NOT IN $entities")>0) {
							return false;						
					}
				}			
			}
		}
		
		return true;
	}


	function prepareInputForAdd($input) {

		if (isset($input["ID"])&&$input["ID"]>0){
			$input["_oldID"]=$input["ID"];
		}
		unset($input['ID']);
		unset($input['withtemplate']);

		return $input;
	}

	function post_addItem($newID,$input) {
		global $DB;

		// Manage add from template
		if (isset($input["_oldID"])){
			// ADD Infocoms
			$ic= new Infocom();
			if ($ic->getFromDBforDevice(PRINTER_TYPE,$input["_oldID"])){
				$ic->fields["FK_device"]=$newID;
				unset ($ic->fields["ID"]);
				if (isset($ic->fields["num_immo"])) {
					$ic->fields["num_immo"] = autoName($ic->fields["num_immo"], "num_immo", 1, INFOCOM_TYPE,$input['FK_entities']);
				}
				if (empty($ic->fields['use_date'])){
					unset($ic->fields['use_date']);
				}
				if (empty($ic->fields['buy_date'])){
					unset($ic->fields['buy_date']);
				}
	
				$ic->addToDB();
			}
	
			// ADD Ports
			$query="SELECT ID 
				FROM glpi_networking_ports 
				WHERE on_device='".$input["_oldID"]."' AND device_type='".PRINTER_TYPE."';";
			$result=$DB->query($query);
			if ($DB->numrows($result)>0){
	
				while ($data=$DB->fetch_array($result)){
					$np= new Netport();
					$np->getFromDB($data["ID"]);
					unset($np->fields["ID"]);
					unset($np->fields["ifaddr"]);
					unset($np->fields["ifmac"]);
					unset($np->fields["netpoint"]);
					$np->fields["on_device"]=$newID;
               $portid=$np->addToDB();
               foreach ($DB->request('glpi_networking_vlan', array('FK_port'=>$data["ID"])) as $vlan) {
                  assignVlan($portid, $vlan['FK_vlan']);
               }
				}
			}
	
			// ADD Contract				
			$query="SELECT FK_contract 
				FROM glpi_contract_device 
				WHERE FK_device='".$input["_oldID"]."' AND device_type='".PRINTER_TYPE."';";
			$result=$DB->query($query);
			if ($DB->numrows($result)>0){
	
				while ($data=$DB->fetch_array($result))
					addDeviceContract($data["FK_contract"],PRINTER_TYPE,$newID);
			}
	
			// ADD Documents			
			$query="SELECT FK_doc 
				FROM glpi_doc_device 
				WHERE FK_device='".$input["_oldID"]."' AND device_type='".PRINTER_TYPE."';";
			$result=$DB->query($query);
			if ($DB->numrows($result)>0){
	
				while ($data=$DB->fetch_array($result))
					addDeviceDocument($data["FK_doc"],PRINTER_TYPE,$newID);
			}
		}

	}


	function cleanDBonPurge($ID) {
		global $DB,$CFG_GLPI;


		$job =new Job();
		$query = "SELECT * 
			FROM glpi_tracking 
			WHERE computer = '$ID'  AND device_type='".PRINTER_TYPE."'";
		$result = $DB->query($query);

		if ($DB->numrows($result))
			while ($data=$DB->fetch_array($result)) {
				if ($CFG_GLPI["keep_tracking_on_delete"]==1){
					$query = "UPDATE glpi_tracking SET computer = '0', device_type='0' WHERE ID='".$data["ID"]."';";
					$DB->query($query);
				} else $job->delete(array("ID"=>$data["ID"]));
			}


		$query = "SELECT ID 
			FROM glpi_networking_ports 
			WHERE on_device = '$ID' AND device_type = '".PRINTER_TYPE."'";
		$result = $DB->query($query);
		while ($data = $DB->fetch_array($result)){
			$q = "DELETE FROM glpi_networking_wire WHERE end1 = '".$data["ID"]."' OR end2 = '".$data["ID"]."'";
			$result2 = $DB->query($q);					
		}

		$query2 = "DELETE FROM glpi_networking_ports WHERE on_device = '$ID' AND device_type = '".PRINTER_TYPE."'";
		$result2 = $DB->query($query2);

		$query="SELECT * FROM glpi_connect_wire WHERE type='".PRINTER_TYPE."' AND end1='$ID'";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result)>0) {
				while ($data = $DB->fetch_array($result)){
					// Disconnect without auto actions
					Disconnect($data["ID"],1,false);
				}
			}
		}


		$query="SELECT * FROM glpi_reservation_item WHERE device_type='".PRINTER_TYPE."' AND id_device='$ID'";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result)>0){
				$rr=new ReservationItem();
				$rr->delete(array("ID"=>$DB->result($result,0,"ID")));
			}
		}

		$query = "DELETE FROM glpi_infocoms WHERE FK_device = '$ID' AND device_type='".PRINTER_TYPE."'";
		$result = $DB->query($query);

		$query = "DELETE FROM glpi_contract_device WHERE FK_device = '$ID' AND device_type='".PRINTER_TYPE."'";
		$result = $DB->query($query);

		$query = "UPDATE glpi_cartridges SET FK_glpi_printers = NULL WHERE FK_glpi_printers='$ID'";
		$result = $DB->query($query);

      $query = "DELETE FROM `glpi_doc_device` WHERE (FK_device = '$ID' AND device_type='".$this->type."')";
      $result = $DB->query($query);
   }

	/**
	 * Print the printer form
	 *
	 *@param $target string: where to go when done.
	 *@param $ID integer: Id of the item to print
	 *@param $withtemplate integer: template or basic item
	 *
	  *@return boolean item found
	 **/
	function showForm ($target,$ID,$withtemplate='') {

		global $CFG_GLPI, $LANG;
		if (!haveRight("printer","r")) return false;

		$use_cache=true;

		if ($ID > 0){
			$this->check($ID,'r');
		} else {
			// Create item 
			$this->check(-1,'w');
			$use_cache=false;
			$this->getEmpty();
		} 

		$canedit=$this->can($ID,'w');

		$this->showTabs($ID, $withtemplate,$_SESSION['glpi_tab']);

		if(!empty($withtemplate) && $withtemplate == 2) {
			$use_cache=false;
			$template = "newcomp";
			$datestring = $LANG['computers'][14].": ";
			$date = convDateTime($_SESSION["glpi_currenttime"]);
		} elseif(!empty($withtemplate) && $withtemplate == 1) { 
			$use_cache=false;
			$template = "newtemplate";
			$datestring = $LANG['computers'][14].": ";
			$date = convDateTime($_SESSION["glpi_currenttime"]);
		} else {
			$datestring = $LANG['common'][26].": ";
			$date = convDateTime($this->fields["date_mod"]);
			$template = false;
		}


		echo "<div align='center' id='tabsbody'>";
		
		if ($canedit) {
			echo "<form method='post' name='form' action=\"$target\">\n";
			echo "<input type='hidden' name='FK_entities' value='".$this->fields["FK_entities"]."'>";		
		}
		echo "<table class='tab_cadre_fixe' cellpadding='2'>\n";
		$this->showFormHeader($ID,$withtemplate);
		/*
		 echo "<tr><th align='center' >\n";
		if(!$template) {
			echo $LANG['common'][2]." ".$this->fields["ID"];
		}elseif (strcmp($template,"newcomp") === 0) {
			echo $LANG['printers'][28].": ".$this->fields["tplname"];
			echo "<input type='hidden' name='tplname' value='".$this->fields["tplname"]."'>";
		}elseif (strcmp($template,"newtemplate") === 0) {
			echo $LANG['common'][6]."&nbsp;: ";
			autocompletionTextField("tplname","glpi_printers","tplname",$this->fields["tplname"],40,$this->fields["FK_entities"]);
		}
		if (isMultiEntitiesMode()){
			echo "&nbsp;(".getDropdownName("glpi_entities",$this->fields["FK_entities"]).")";
		}

		echo "</th><th  align='center'>".$datestring.$date;
		if (!$template&&!empty($this->fields['tplname']))
			echo "&nbsp;&nbsp;&nbsp;(".$LANG['common'][13].": ".$this->fields['tplname'].")";
		echo "</th></tr>\n";
		*/
		if (!$use_cache||!($CFG_GLPI["cache"]->start($ID."_".$_SESSION['glpilanguage'],"GLPI_".$this->type))) {
			echo "<tr><td class='tab_bg_1' valign='top'>\n";

			// table identification
			echo "<table cellpadding='1' cellspacing='0' border='0'>\n";
			echo "<tr><td>".$LANG['common'][16].($template?"*":"").":	</td>\n";
			echo "<td>";
			$objectName = autoName($this->fields["name"], "name", ($template === "newcomp"), PRINTER_TYPE,$this->fields["FK_entities"]);
			autocompletionTextField("name","glpi_printers","name",$objectName,40,$this->fields["FK_entities"]);
			echo "</td></tr>\n";

			echo "<tr><td>".$LANG['common'][15].": 	</td><td>\n";
			dropdownValue("glpi_dropdown_locations", "location", $this->fields["location"],1,$this->fields["FK_entities"]);
			echo "</td></tr>\n";

			echo "<tr class='tab_bg_1'><td>".$LANG['common'][5].": 	</td><td colspan='2'>\n";
			dropdownValue("glpi_dropdown_manufacturer","FK_glpi_enterprise",$this->fields["FK_glpi_enterprise"]);
			echo "</td></tr>\n";

			echo "<tr class='tab_bg_1'><td>".$LANG['common'][10].": 	</td><td colspan='2'>\n";
			dropdownUsersID("tech_num", $this->fields["tech_num"],"interface",1,$this->fields["FK_entities"]);
			echo "</td></tr>\n";

			echo "<tr><td>".$LANG['common'][18].":	</td><td>\n";
			autocompletionTextField("contact","glpi_printers","contact",$this->fields["contact"],40,$this->fields["FK_entities"]);
			echo "</td></tr>\n";

			echo "<tr><td>".$LANG['common'][21].":	</td><td>\n";
			autocompletionTextField("contact_num","glpi_printers","contact_num",$this->fields["contact_num"],40,$this->fields["FK_entities"]);
			echo "</td></tr>\n";


			echo "<tr><td>".$LANG['common'][34].": 	</td><td>";
			dropdownAllUsers("FK_users", $this->fields["FK_users"],1,$this->fields["FK_entities"]);
			echo "</td></tr>";

			echo "<tr><td>".$LANG['common'][35].": 	</td><td>";
			dropdownValue("glpi_groups", "FK_groups", $this->fields["FK_groups"],1,$this->fields["FK_entities"]);
			echo "</td></tr>";


			

			echo "<tr><td>".$LANG['setup'][88].": 	</td><td>\n";
			dropdownValue("glpi_dropdown_network", "network", $this->fields["network"]);
			echo "</td></tr>\n";

			echo "<tr><td>".$LANG['setup'][89].": 	</td><td>\n";
			dropdownValue("glpi_dropdown_domain", "domain", $this->fields["domain"]);
			echo "</td></tr>\n";

			echo "<tr><td>$datestring</td><td>$date\n";
			if (!$template&&!empty($this->fields['tplname'])) {
				echo "&nbsp;&nbsp;&nbsp;(".$LANG['common'][13].": ".$this->fields['tplname'].")";
			}
			echo "</td></tr>\n";

			echo "</table>"; // fin table indentification

			echo "</td>\n";	
			echo "<td class='tab_bg_1' valign='top'>\n";

			// table type,serial..
			echo "<table cellpadding='1' cellspacing='0' border='0'>\n";

			echo "<tr><td>".$LANG['state'][0].":</td><td>\n";
			dropdownValue("glpi_dropdown_state", "state",$this->fields["state"]);
			echo "</td></tr>\n";

			echo "<tr><td>".$LANG['common'][17].": 	</td><td>\n";
			dropdownValue("glpi_type_printers", "type", $this->fields["type"]);
			echo "</td></tr>\n";

			echo "<tr><td>".$LANG['common'][22].": 	</td><td>";
			dropdownValue("glpi_dropdown_model_printers", "model", $this->fields["model"]);
			echo "</td></tr>";

			echo "<tr><td>".$LANG['common'][19].":	</td><td>\n";
			autocompletionTextField("serial","glpi_printers","serial",$this->fields["serial"],40,$this->fields["FK_entities"]);	
			echo "</td></tr>\n";

			echo "<tr><td>".$LANG['common'][20].($template?"*":"").":</td><td>\n";
			$objectName = autoName($this->fields["otherserial"], "otherserial", ($template === "newcomp"), PRINTER_TYPE,$this->fields["FK_entities"]);
			autocompletionTextField("otherserial","glpi_printers","otherserial",$objectName,40,$this->fields["FK_entities"]);
			echo "</td></tr>\n";

			echo "<tr><td>".$LANG['printers'][18].": </td><td>\n";

			// serial interface?
			echo "<table border='0' cellpadding='2' cellspacing='0'><tr>\n";
			echo "<td>".$LANG['printers'][14]."</td>\n";
			echo "<td>";
			dropdownYesNo("flags_serial",$this->fields["flags_serial"]);
			echo "</td>";
			echo "</tr></table>\n";

			// parallel interface?
			echo "<table border='0' cellpadding='2' cellspacing='0'><tr>\n";
			echo "<td>".$LANG['printers'][15]."</td>\n";
			echo "<td>";
			dropdownYesNo("flags_par",$this->fields["flags_par"]);
			echo "</td>";

			echo "</tr></table>\n";

			// USB ?
			echo "<table border='0' cellpadding='2' cellspacing='0'><tr>\n";
			echo "<td>".$LANG['printers'][27]."</td>\n";
			echo "<td>";
			dropdownYesNo("flags_usb",$this->fields["flags_usb"]);
			echo "</td>";

			echo "</tr></table>\n";

			// Ram ?
			echo "<tr><td>".$LANG['devices'][6].":</td><td>\n";
			autocompletionTextField("ramSize","glpi_printers","ramSize",$this->fields["ramSize"],40,$this->fields["FK_entities"]);
			echo "</td></tr>\n";
			// Initial count pages ?
			echo "<tr><td>".$LANG['printers'][30].":</td><td>\n";
			autocompletionTextField("initial_pages","glpi_printers","initial_pages",$this->fields["initial_pages"],40,$this->fields["FK_entities"]);		
			echo "</td></tr>\n";


			echo "<tr><td>".$LANG['peripherals'][33].":</td><td>";
			if ($canedit) {
				globalManagementDropdown($target,$withtemplate,$this->fields["ID"],$this->fields["is_global"],$CFG_GLPI["printers_management_restrict"]);
			} else {
				// Use printers_management_restrict to disallow change this
				globalManagementDropdown($target,$withtemplate,$this->fields["ID"],$this->fields["is_global"],$this->fields["is_global"]);				
			}
			echo "</td></tr>";

			echo "</table>\n";
			echo "</td>\n";	
			echo "</tr>\n";

			echo "<tr>\n";
			echo "<td class='tab_bg_1' valign='top' colspan='2'>\n";

			// table commentaires
			echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'><tr><td valign='top'>\n";
			echo $LANG['common'][25].":	</td>\n";
			echo "<td class='center'><textarea cols='35' rows='4' name='comments' >".$this->fields["comments"]."</textarea>\n";
			echo "</td></tr></table>\n";

			echo "</td>\n";
			echo "</tr>\n";
			if ($use_cache){
				$CFG_GLPI["cache"]->end();
			}
		}



		if ($canedit){
			echo "<tr>\n";

			if ($template) {

				if (empty($ID)||$withtemplate==2){
					echo "<td class='tab_bg_2' align='center' colspan='2'>\n";
					echo "<input type='hidden' name='ID' value=$ID>";
					echo "<input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'>";
					echo "</td>\n";
				} else {
					echo "<td class='tab_bg_2' align='center' colspan='2'>\n";
					echo "<input type='hidden' name='ID' value=$ID>";
					echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit'>";
					echo "</td>\n";
				}

			} else {

				echo "<td class='tab_bg_2' valign='top' align='center'>";
				echo "<input type='hidden' name='ID' value=\"$ID\">\n";
				echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit'>";
				echo "</td>\n\n";
				echo "<td class='tab_bg_2' valign='top' align='center'>\n";
				echo "<div class='center'>";
				if (!$this->fields["deleted"])
					echo "<input type='submit' name='delete' value=\"".$LANG['buttons'][6]."\" class='submit'>";
				else {
					echo "<input type='submit' name='restore' value=\"".$LANG['buttons'][21]."\" class='submit'>";

					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$LANG['buttons'][22]."\" class='submit'>";
				}
				echo "</div>";
				echo "</td>";

			}
			echo "</tr>";
			echo "</table></form></div>\n";
		}else { // ! $canedit
			echo "</table></div>\n";
		}

		echo "<div id='tabcontent'></div>";
		echo "<script type='text/javascript'>loadDefaultTab();</script>";

		return true;	

	}




}

?>
