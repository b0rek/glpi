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

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

/// Computer class
class Computer extends CommonDBTM {


	///Device container - format $device = array(ID,"device type","ID in device table","specificity value")
	var $devices	= array();

	/**
	 * Constructor
	**/
	function __construct () {
		$this->table="glpi_computers";
		$this->type=COMPUTER_TYPE;
		$this->dohistory=true;
		$this->entity_assign=true;

	}

	function defineTabs($ID,$withtemplate){
		global $LANG,$CFG_GLPI;

		if ($ID > 0){
			$ong[1]=$LANG['title'][30];
			$ong[20]=$LANG['computers'][8];
			if (haveRight("software","r"))	{
				$ong[2]=$LANG['Menu'][4];
			}
			if (haveRight("networking","r")||haveRight("printer","r")||haveRight("monitor","r")||haveRight("peripheral","r")||haveRight("phone","r")){
				$ong[3]=$LANG['title'][27];
			}
			if (haveRight("contract","r") || haveRight("infocom","r")){
				$ong[4]=$LANG['Menu'][26];
			}
			if (haveRight("document","r")){
				$ong[5]=$LANG['Menu'][27];
			}

			if(empty($withtemplate)){
				if ($CFG_GLPI["ocs_mode"]){
					$ong[14]=$LANG['title'][43];
				}
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

				if ($CFG_GLPI["ocs_mode"]&&(haveRight("sync_ocsng","w")||haveRight("computer","w"))){
					$ong[13]=$LANG['Menu'][33];
				}
			}
		} else { // New item
			$ong[1]=$LANG['title'][26];
		}
		return $ong;
	}
	/**
	 * Retrieve an item from the database with device associated
	 *
	 *@param $ID ID of the item to get
	 *@return true if succeed else false
	**/
	function getFromDBwithDevices ($ID) {

		global $DB;

		if ($this->getFromDB($ID)){
			$query = "SELECT count(*) AS NB, ID, device_type, FK_device, specificity
				FROM glpi_computer_device
				WHERE FK_computers = '$ID'
				GROUP BY device_type, FK_device, specificity
				ORDER BY device_type, ID";
			if ($result = $DB->query($query)) {
				if ($DB->numrows($result)>0) {
					$i = 0;
					while($data = $DB->fetch_array($result)) {
						$this->devices[$i] = array("compDevID"=>$data["ID"],"devType"=>$data["device_type"],"devID"=>$data["FK_device"],"specificity"=>$data["specificity"],"quantity"=>$data["NB"]);
						$i++;
					}
				}
				return true;
			}
		}
		return false;
	}

	function post_updateItem($input,$updates,$history=1) {
		global $DB,$LANG,$CFG_GLPI;

		// Manage changes for OCS if more than 1 element (date_mod)
		// Need dohistory==1 if dohistory==2 no locking fields
		if ($this->fields["ocs_import"]&&$history==1&&count($updates)>1){
			mergeOcsArray($this->fields["ID"],$updates,"computer_update");
		}

		if (isset($input["_auto_update_ocs"])){
			$query="UPDATE glpi_ocs_link
				SET auto_update='".$input["_auto_update_ocs"]."'
				WHERE glpi_id='".$input["ID"]."'";
			$DB->query($query);
		}

		for ($i=0; $i < count($updates); $i++) {

			// Update contact of attached items

			if (($updates[$i]=="contact" ||$updates[$i]=="contact_num")&&$CFG_GLPI["autoupdate_link_contact"]){
				$items=array(PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE);
				$ci=new CommonItem();
				$update_done=false;
				$updates3[0]="contact";
				$updates3[1]="contact_num";

				foreach ($items as $t){
					$query = "SELECT *
						FROM glpi_connect_wire
						WHERE end2='".$this->fields["ID"]."' AND type='".$t."'";
					if ($result=$DB->query($query)) {
						$resultnum = $DB->numrows($result);
						if ($resultnum>0) {
							for ($j=0; $j < $resultnum; $j++) {
								$tID = $DB->result($result, $j, "end1");
								$ci->getFromDB($t,$tID);
								if (!$ci->getField('is_global')){
									if ($ci->getField('contact')!=$this->fields['contact']||$ci->getField('contact_num')!=$this->fields['contact_num']){
										$tmp["ID"]=$ci->getField('ID');
										$tmp['contact']=$this->fields['contact'];
										$tmp['contact_num']=$this->fields['contact_num'];
										$ci->obj->update($tmp);
										$update_done=true;
									}
								}
							}
						}
					}
				}

				if ($update_done) {
					addMessageAfterRedirect($LANG['computers'][49],true);
				}

			}

			// Update users and groups of attached items
			if (($updates[$i]=="FK_users" && $this->fields["FK_users"]!=0 && $CFG_GLPI["autoupdate_link_user"])||($updates[$i]=="FK_groups" && $this->fields["FK_groups"]!=0 && $CFG_GLPI["autoupdate_link_group"])){
				$items=array(PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE);
				$ci=new CommonItem();
				$update_done=false;
				$updates4[0]="FK_users";
				$updates4[1]="FK_groups";

				foreach ($items as $t){
					$query = "SELECT *
						FROM glpi_connect_wire
						WHERE end2='".$this->fields["ID"]."' AND type='".$t."'";

					if ($result=$DB->query($query)) {
						$resultnum = $DB->numrows($result);

						if ($resultnum>0) {
							for ($j=0; $j < $resultnum; $j++) {
								$tID = $DB->result($result, $j, "end1");

								$ci->getFromDB($t,$tID);
								if (!$ci->getField('is_global')){
									if ($ci->getField('FK_users')!=$this->fields["FK_users"]||$ci->getField('FK_groups')!=$this->fields["FK_groups"]){
										$tmp["ID"]=$ci->getField('ID');
										if ($CFG_GLPI["autoupdate_link_user"]){
											$tmp["FK_users"]=$this->fields["FK_users"];
										}
										if ($CFG_GLPI["autoupdate_link_group"]){
											$tmp["FK_groups"]=$this->fields["FK_groups"];
										}
										$ci->obj->update($tmp);
										$update_done=true;
									}
								}
							}
						}
					}
				}
				if ($update_done) {
					addMessageAfterRedirect($LANG['computers'][50],true);
				}

			}

			// Update state of attached items
			if ($updates[$i]=="state" && $CFG_GLPI["autoupdate_link_state"]<0){
				$items=array(PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE);
				$ci=new CommonItem();
				$update_done=false;

				foreach ($items as $t){
					$query = "SELECT *
						FROM glpi_connect_wire
						WHERE end2='".$this->fields["ID"]."' AND type='".$t."'";

					if ($result=$DB->query($query)) {
						$resultnum = $DB->numrows($result);

						if ($resultnum>0) {
							for ($j=0; $j < $resultnum; $j++) {
								$tID = $DB->result($result, $j, "end1");

								$ci->getFromDB($t,$tID);
								if (!$ci->getField('is_global')){
									if ($ci->getField('state')!=$this->fields["state"]){
										$tmp["ID"]=$ci->getField('ID');
										$tmp["state"]=$this->fields["state"];
										$ci->obj->update($tmp);
										$update_done=true;
									}
								}
							}
						}
					}
				}
				if ($update_done) {
					addMessageAfterRedirect($LANG['computers'][56],true);
				}

			}


			// Update loction of attached items
			if ($updates[$i]=="location" && $this->fields["location"]!=0 && $CFG_GLPI["autoupdate_link_location"]){
				$items=array(PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,PHONE_TYPE);
				$ci=new CommonItem();
				$update_done=false;
				$updates2[0]="location";

				foreach ($items as $t){
					$query = "SELECT *
						FROM glpi_connect_wire
						WHERE end2='".$this->fields["ID"]."' AND type='".$t."'";

					if ($result=$DB->query($query)) {
						$resultnum = $DB->numrows($result);

						if ($resultnum>0) {
							for ($j=0; $j < $resultnum; $j++) {
								$tID = $DB->result($result, $j, "end1");

								$ci->getFromDB($t,$tID);
								if (!$ci->getField('is_global')){
									if ($ci->getField('location')!=$this->fields["location"]){
										$tmp["ID"]=$ci->getField('ID');
										$tmp["location"]=$this->fields["location"];
										$ci->obj->update($tmp);
										$update_done=true;
									}
								}
							}
						}
					}
				}
				if ($update_done) {
					addMessageAfterRedirect($LANG['computers'][48],true);
				}

			}

		}



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
			// ADD Devices
			$this->getFromDBwithDevices($input["_oldID"]);
			foreach($this->devices as $key => $val) {
				for ($i=0;$i<$val["quantity"];$i++){
					compdevice_add($newID,$val["devType"],$val["devID"],$val["specificity"],0);
				}
			}

			// ADD Infocoms
			$ic= new Infocom();
			if ($ic->getFromDBforDevice(COMPUTER_TYPE,$input["_oldID"])){
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

       			// ADD volumes
			$query="SELECT ID
				FROM glpi_computerdisks
				WHERE FK_computers='".$input["_oldID"]."'";
			$result=$DB->query($query);
			if ($DB->numrows($result)>0){
				while ($data=$DB->fetch_array($result)){
                                  $disk=new ComputerDisk();
                                  $disk->getfromDB($data['ID']);
                                  unset($disk->fields["ID"]);
                                  $disk->fields["FK_computers"]=$newID;
                                  $disk->addToDB();
                                }

			}

			// ADD software
			$query="SELECT vID
				FROM glpi_inst_software
				WHERE cID='".$input["_oldID"]."'";
			$result=$DB->query($query);
			if ($DB->numrows($result)>0){
				while ($data=$DB->fetch_array($result))
					installSoftwareVersion($newID,$data['vID']);
			}

			// ADD Contract
			$query="SELECT FK_contract
				FROM glpi_contract_device
				WHERE FK_device='".$input["_oldID"]."' AND device_type='".COMPUTER_TYPE."';";
			$result=$DB->query($query);
			if ($DB->numrows($result)>0){
				while ($data=$DB->fetch_array($result))
					addDeviceContract($data["FK_contract"],COMPUTER_TYPE,$newID);
			}

			// ADD Documents
			$query="SELECT FK_doc
				FROM glpi_doc_device
				WHERE FK_device='".$input["_oldID"]."' AND device_type='".COMPUTER_TYPE."';";
			$result=$DB->query($query);
			if ($DB->numrows($result)>0){
				while ($data=$DB->fetch_array($result))
					addDeviceDocument($data["FK_doc"],COMPUTER_TYPE,$newID);
			}

			// ADD Ports
			$query="SELECT ID
				FROM glpi_networking_ports
				WHERE on_device='".$input["_oldID"]."' AND device_type='".COMPUTER_TYPE."';";
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

			// Add connected devices
			$query="SELECT *
				FROM glpi_connect_wire
				WHERE end2='".$input["_oldID"]."';";

			$result=$DB->query($query);
			if ($DB->numrows($result)>0){
				while ($data=$DB->fetch_array($result)){
					Connect($data["end1"],$newID,$data["type"]);
				}
			}
		}

	}

	function cleanDBonPurge($ID) {
		global $DB,$CFG_GLPI;

		$job=new Job;

		$query = "SELECT *
			FROM glpi_tracking
			WHERE (computer = '$ID'  AND device_type='".COMPUTER_TYPE."')";
		$result = $DB->query($query);

		if ($DB->numrows($result))
			while ($data=$DB->fetch_array($result)) {
				if ($CFG_GLPI["keep_tracking_on_delete"]==1){
					$query = "UPDATE glpi_tracking
						SET computer = '0', device_type='0'
						WHERE ID='".$data["ID"]."';";
					$DB->query($query);
				} else $job->delete(array("ID"=>$data["ID"]));
			}

		$query = "DELETE FROM glpi_inst_software WHERE (cID = '$ID')";
		$result = $DB->query($query);

		$query = "DELETE FROM glpi_contract_device WHERE (FK_device = '$ID' AND device_type='".COMPUTER_TYPE."')";
		$result = $DB->query($query);

		$query = "DELETE FROM glpi_infocoms WHERE (FK_device = '$ID' AND device_type='".COMPUTER_TYPE."')";
		$result = $DB->query($query);

		$query = "SELECT ID FROM glpi_networking_ports WHERE (on_device = '$ID' AND device_type = '".COMPUTER_TYPE."')";
		$result = $DB->query($query);
		while ($data = $DB->fetch_array($result)){
			$q = "DELETE FROM glpi_networking_wire WHERE (end1 = '".$data["ID"]."' OR end2 = '".$data["ID"]."')";
			$result2 = $DB->query($q);
		}

		$query = "DELETE FROM glpi_networking_ports WHERE (on_device = '$ID' AND device_type = '".COMPUTER_TYPE."')";
		$result = $DB->query($query);


		$query="SELECT * FROM glpi_connect_wire WHERE (end2='$ID')";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result)>0) {
				while ($data = $DB->fetch_array($result)){
					// Disconnect without auto actions
					Disconnect($data["ID"],1,false);
				}
			}
		}


		$query = "DELETE FROM glpi_registry WHERE (computer_id = '$ID')";
		$result = $DB->query($query);

		$query="SELECT * FROM glpi_reservation_item WHERE (device_type='".COMPUTER_TYPE."' AND id_device='$ID')";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result)>0) {
				$rr=new ReservationItem();
				$rr->delete(array("ID"=>$DB->result($result,0,"ID")));
			}
		}

		$query = "DELETE FROM glpi_computer_device WHERE (FK_computers = '$ID')";
		$result = $DB->query($query);

		$query = "DELETE FROM glpi_ocs_link WHERE (glpi_id = '$ID')";
		$result = $DB->query($query);

		$query = "DELETE FROM glpi_computerdisks WHERE (FK_computers = '$ID')";
		$result = $DB->query($query);

      $query = "DELETE FROM `glpi_doc_device` WHERE (FK_device = '$ID' AND device_type='".$this->type."')";
      $result = $DB->query($query);
	}

	/**
	 * Print the computer form
	 *
	 * Print general computer form
	 *
	 *@param $target form target
	 *@param $ID Integer : Id of the computer or the template to print
	 *@param $withtemplate template or basic computer
	 *
	 *@return Nothing (display)
	 *
	 **/
	function showForm($target,$ID,$withtemplate='') {
		global $LANG,$CFG_GLPI,$DB;

		if (!haveRight("computer","r")) return false;

		$use_cache=true;

		if ($ID > 0){
			$this->check($ID,'r');
		} else {
			// Create item
			$this->check(-1,'w');
			$use_cache=false;
			$this->getEmpty();
		}

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

		echo "<form name='form' method='post' action=\"$target\">";
		if(strcmp($template,"newtemplate") === 0) {
			echo "<input type=\"hidden\" name=\"is_template\" value=\"1\">";
		}

		echo "<input type='hidden' name='FK_entities' value='".$this->fields["FK_entities"]."'>";

		echo "<div class='center' id='tabsbody'>";
		echo "<table class='tab_cadre_fixe' >";

		echo "<tr><th colspan ='2' align='center' >";
		if(!$template) {
			echo $LANG['common'][2]." ".$this->fields["ID"];
		}elseif (strcmp($template,"newcomp") === 0) {
			echo $LANG['computers'][12].": ".$this->fields["tplname"];
			echo "<input type='hidden' name='tplname' value='".$this->fields["tplname"]."'>";
		}elseif (strcmp($template,"newtemplate") === 0) {
			echo $LANG['common'][6].": ";
			autocompletionTextField("tplname","glpi_computers","tplname",$this->fields["tplname"],40,$this->fields["FK_entities"]);
		}
		if (isMultiEntitiesMode()){
			echo "&nbsp;(".getDropdownName("glpi_entities",$this->fields["FK_entities"]).")";
		}

		if (!$use_cache||!($CFG_GLPI["cache"]->start($ID."_".$_SESSION['glpilanguage'],"GLPI_".$this->type))) {

			echo "</th><th  colspan ='2' align='center'>".$datestring.$date;
			if (!$template&&!empty($this->fields['tplname']))
				echo "&nbsp;&nbsp;&nbsp;(".$LANG['common'][13].": ".$this->fields['tplname'].")";
			if ($this->fields["ocs_import"])
				echo "&nbsp;&nbsp;&nbsp;(".$LANG['ocsng'][7].")";

			echo "</th></tr>";

			echo "<tr class='tab_bg_1'><td>".$LANG['common'][16].($template?"*":"").":		</td>";

			echo "<td>";

			$objectName = autoName($this->fields["name"], "name", ($template === "newcomp"), COMPUTER_TYPE,$this->fields["FK_entities"]);
			autocompletionTextField("name","glpi_computers","name",$objectName,40,$this->fields["FK_entities"]);

			echo "</td>";

			echo "<td>".$LANG['common'][18].":	</td><td>";
			autocompletionTextField("contact","glpi_computers","contact",$this->fields["contact"],40,$this->fields["FK_entities"]);

			echo "</td></tr>";


			echo "<tr class='tab_bg_1'>";
			echo "<td >".$LANG['common'][17].": 	</td>";
			echo "<td >";
			dropdownValue("glpi_type_computers", "type", $this->fields["type"]);
			echo "</td>";


			echo "<td>".$LANG['common'][21].":		</td><td>";
			autocompletionTextField("contact_num","glpi_computers","contact_num",$this->fields["contact_num"],40,$this->fields["FK_entities"]);
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'>";
			echo "<td >".$LANG['common'][22].": 	</td>";
			echo "<td >";
			dropdownValue("glpi_dropdown_model", "model", $this->fields["model"]);
			echo "</td>";

			echo "<td >".$LANG['common'][34].": 	</td>";
			echo "<td >";
			dropdownAllUsers("FK_users", $this->fields["FK_users"],1,$this->fields["FK_entities"]);
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'>";
			echo "<td >".$LANG['common'][15].": 	</td>";
			echo "<td >";
			dropdownValue("glpi_dropdown_locations", "location", $this->fields["location"],1,$this->fields["FK_entities"]);
			echo "</td>";

			echo "<td>".$LANG['common'][35].":</td><td>";
			dropdownValue("glpi_groups", "FK_groups", $this->fields["FK_groups"],1,$this->fields["FK_entities"]);
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'>";
			echo "<td>".$LANG['common'][5].": 	</td><td>";
			dropdownValue("glpi_dropdown_manufacturer","FK_glpi_enterprise",$this->fields["FK_glpi_enterprise"]);
			echo "</td>";

			echo "<td >".$LANG['common'][10].": 	</td>";
			echo "<td >";
			dropdownUsersID("tech_num",$this->fields["tech_num"],"interface",1,$this->fields["FK_entities"]);
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'>";
			echo "<td>".$LANG['computers'][9].":</td><td>";
			dropdownValue("glpi_dropdown_os", "os", $this->fields["os"]);
			echo "</td>";

			echo "<td>".$LANG['setup'][88].":</td><td>";
			dropdownValue("glpi_dropdown_network", "network", $this->fields["network"]);
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'>";
			echo "<td>".$LANG['computers'][52].":</td><td>";
			dropdownValue("glpi_dropdown_os_version", "os_version", $this->fields["os_version"]);
			echo "</td>";


			echo "<td>".$LANG['setup'][89].":</td><td>";
			dropdownValue("glpi_dropdown_domain", "domain", $this->fields["domain"]);
			echo "</td></tr>";


			echo "<tr class='tab_bg_1'>";
			echo "<td>".$LANG['computers'][53].":</td><td>";
			dropdownValue("glpi_dropdown_os_sp", "os_sp", $this->fields["os_sp"]);
			echo "</td>";

			echo "<td>".$LANG['common'][19].":	</td><td>";
			autocompletionTextField("serial","glpi_computers","serial",$this->fields["serial"],40,$this->fields["FK_entities"]);
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'>";
			echo "<td>".$LANG['computers'][10]."</td><td>";
			autocompletionTextField("os_license_number","glpi_computers","os_license_number",$this->fields["os_license_number"],40,$this->fields["FK_entities"]);
			echo"</td>";

			echo "<td>".$LANG['common'][20].($template?"*":"").":	</td><td>";
			$objectName = autoName($this->fields["otherserial"], "otherserial", ($template === "newcomp"), COMPUTER_TYPE,$this->fields["FK_entities"]);
			autocompletionTextField("otherserial","glpi_computers","otherserial",$objectName,40,$this->fields["FK_entities"]);

			echo "</td></tr>";

			echo "<tr class='tab_bg_1'>";
			echo "<td>".$LANG['computers'][11]."</td><td>";
			autocompletionTextField("os_license_id","glpi_computers","os_license_id",$this->fields["os_license_id"],40,$this->fields["FK_entities"]);
			echo"</td>";

			echo "<td>".$LANG['state'][0].":</td><td>";
			dropdownValue("glpi_dropdown_state", "state",$this->fields["state"]);
			echo "</td>";

			// Get OCS Datas :
			$dataocs=array();
			if (!empty($ID)&&$this->fields["ocs_import"]&&haveRight("view_ocsng","r")){
				$query="SELECT *
					FROM glpi_ocs_link
					WHERE glpi_id='$ID'";

				$result=$DB->query($query);
				if ($DB->numrows($result)==1){
					$dataocs=$DB->fetch_array($result);
				}

			}

			echo "<tr class='tab_bg_1'>";
			if (!empty($ID)&&$this->fields["ocs_import"]&&haveRight("view_ocsng","r")&&haveRight("sync_ocsng","w")&&count($dataocs)){
				echo "<td >".$LANG['ocsng'][6]." ".$LANG['Menu'][33].":</td>";
				echo "<td >";
				dropdownYesNo("_auto_update_ocs",$dataocs["auto_update"]);
				echo "</td>";
			} else	{
				echo "<td colspan=2></td>";
			}
			echo "<td>".$LANG['computers'][51].":</td><td>";
			dropdownValue("glpi_dropdown_auto_update", "auto_update", $this->fields["auto_update"]);
			echo "</td>";

			echo "</tr>";

			echo "<tr class='tab_bg_1'>";

			if (!empty($ID)&&$this->fields["ocs_import"]&&haveRight("view_ocsng","r")&&count($dataocs)){
				echo "<td colspan='2' align='center'>";
				echo $LANG['ocsng'][14].": ".convDateTime($dataocs["last_ocs_update"]);
				echo "<br>";
				echo $LANG['ocsng'][13].": ".convDateTime($dataocs["last_update"]);
				echo "<br>";
				if (haveRight("ocsng","r")){
					echo $LANG['common'][52]." <a href='".$CFG_GLPI["root_doc"]."/front/ocsng.form.php?ID=".getOCSServerByMachineID($ID)."'>".getOCSServerNameByID($ID)."</a>";
					$query = "SELECT ocs_agent_version, ocs_id FROM glpi_ocs_link WHERE (glpi_id = '$ID')";
					$result_agent_version = $DB->query($query);
					$data_version = $DB->fetch_array($result_agent_version);

					$ocs_config = getOcsConf(getOCSServerByMachineID($ID));

					//If have write right on OCS and ocsreports url is not empty in OCS config
					if (haveRight("ocsng","w") && $ocs_config["ocs_url"] != '')
					{
						echo ", ".getComputerLinkToOcsConsole (getOCSServerByMachineID($ID),$data_version["ocs_id"],$LANG['ocsng'][57]);
					}

					if ($data_version["ocs_agent_version"] != NULL)
						echo " , ".$LANG['ocsng'][49]." : ".$data_version["ocs_agent_version"];


				} else {
					echo $LANG['common'][52]." ".getOCSServerNameByID($ID);
					echo "</td>";
				}

			} else	{
				echo "<td colspan=2></td>";
			}
			echo "<td valign='middle'>".$LANG['common'][25].":</td><td valign='middle'><textarea  cols='50' rows='3' name='comments' >".$this->fields["comments"]."</textarea></td>";
			echo "</tr>";
			if ($use_cache){
				$CFG_GLPI["cache"]->end();
			}
		}


		if (haveRight("computer","w")) {
			echo "<tr>\n";
			if ($template) {
				if (empty($ID)||$withtemplate==2){
					echo "<td class='tab_bg_2' align='center' colspan='4'>\n";
					echo "<input type='hidden' name='ID' value=$ID>";
					echo "<input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'>";
					echo "</td>\n";
				} else {
					echo "<td class='tab_bg_2' align='center' colspan='4'>\n";
					echo "<input type='hidden' name='ID' value=$ID>";
					echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit'>";
					echo "</td>\n";
				}
			} else {
				echo "<td class='tab_bg_2' colspan='2' align='center' valign='top'>\n";
				echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit'>";
				echo "</td>\n";
				echo "<td class='tab_bg_2' colspan='2'  align='center'>\n";
				echo "<input type='hidden' name='ID' value=$ID>";
				echo "<div class='center'>";
				if (!$this->fields["deleted"]){
					echo "<input type='submit' name='delete' value=\"".$LANG['buttons'][6]."\" class='submit'>";
					}else {
					echo "<input type='submit' name='restore' value=\"".$LANG['buttons'][21]."\" class='submit'>";

					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$LANG['buttons'][22]."\" class='submit'>";
				}
				echo "</div>";
				echo "</td>";
			}
			echo "</tr>\n";
		}

		echo "</table>";
		echo "</div>";
		echo "</form>";
		echo "<div id='tabcontent'></div>";
		echo "<script type='text/javascript'>loadDefaultTab();</script>";


		return true;
	}

}



/// Disk class
class ComputerDisk extends CommonDBTM {
	/**
	 * Constructor
	**/
	function __construct() {
		$this->table = "glpi_computerdisks";
		$this->type = COMPUTERDISK_TYPE;
		$this->entity_assign=true;

	}

	function prepareInputForAdd($input) {
		// Not attached to software -> not added
		if (!isset($input['FK_computers']) || $input['FK_computers'] <= 0){
			return false;
		}
		return $input;
	}

	function post_getEmpty () {
		$this->fields["totalsize"]='0';
		$this->fields["freesize"]='0';
	}

	function getEntityID () {
		if (isset($this->fields['FK_computers']) && $this->fields['FK_computers'] >0){
			$computer=new Computer();

			$computer->getFromDB($this->fields['FK_computers']);
			return $computer->fields['FK_entities'];
		}
		return  -1;
	}

	/**
	 * Print the version form
	 *
	 *@param $target form target
	 *@param $ID Integer : Id of the version or the template to print
	 *@param $cID ID of the computer for add process
	 *
	 *@return true if displayed  false if item not found or not right to display
	 **/
	function showForm($target,$ID,$cID=-1){
		global $CFG_GLPI,$LANG;

		if (!haveRight("computer","w"))	return false;

		if ($ID > 0){
			$this->check($ID,'r');
		} else {
			// Create item
			$this->check(-1,'w');
			$use_cache=false;
			$this->getEmpty();
		}

		$this->showTabs($ID, '', $_SESSION['glpi_tab']);

		echo "<form name='form' method='post' action=\"$target\" enctype=\"multipart/form-data\">";

		echo "<div class='center'><table class='tab_cadre_fixe'>";
		if ($ID>0){
			echo "<tr><th colspan='4'>".$LANG['common'][2]." $ID";
			echo " - <a href='computer.form.php?ID=".$this->fields["FK_computers"]."'>".getDropdownName("glpi_computers",$this->fields["FK_computers"])."</a>";
			echo "</th></tr>";
		} else {
			echo "<tr><th colspan='4'>".$LANG['computers'][7];
			echo " - <a href='computer.form.php?ID=".$cID."'>".getDropdownName("glpi_computers",$cID)."</a>";

			echo "</th></tr>";
			echo "<input type='hidden' name='FK_computers' value='$cID'>";
		}

		echo "<tr class='tab_bg_1'><td>".$LANG['common'][16].":		</td>";
		echo "<td>";
		autocompletionTextField("name","glpi_computerdisks","name",$this->fields["name"],40);
		echo "</td>";

		echo "<td>".$LANG['computers'][6].":		</td>";
		echo "<td>";
		autocompletionTextField("device","glpi_computerdisks","device",$this->fields["device"],40);
		echo "</td>";
		echo "</tr>";

		echo "<tr class='tab_bg_1'><td>".$LANG['computers'][5].":		</td>";
		echo "<td>";
		autocompletionTextField("mountpoint","glpi_computerdisks","mountpoint",$this->fields["mountpoint"],40);
		echo "</td>";

		echo "<td>".$LANG['computers'][4].":		</td>";
		echo "<td>";
		dropdownValue("glpi_dropdown_filesystems", "FK_filesystems", $this->fields["FK_filesystems"]);
		echo "</td>";
		echo "</tr>";

		echo "<tr class='tab_bg_1'><td>".$LANG['computers'][3].":		</td>";
		echo "<td>";
		autocompletionTextField("totalsize","glpi_computerdisks","totalsize",$this->fields["totalsize"],40);
		echo "&nbsp;".$LANG['common'][82]."</td>";

		echo "<td>".$LANG['computers'][2].":		</td>";
		echo "<td>";
		autocompletionTextField("freesize","glpi_computerdisks","freesize",$this->fields["freesize"],40);
		echo "&nbsp;".$LANG['common'][82]."</td>";
		echo "</tr>";

		echo "<tr  class='tab_bg_2'>";

		if ($ID>0) {

			echo "<td colspan='2'>";
			echo "<input type='hidden' name='ID' value=\"$ID\">\n";
			echo "<div class='center'><input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit'></div>";
			echo "</td>\n\n";
			echo "<td colspan='2'>";
			echo "<input type='hidden' name='ID' value=\"$ID\">\n";
			echo "<div class='center'><input type='submit' name='delete' value=\"".$LANG['buttons'][6]."\" class='submit'></div>";
			echo "</td>\n\n";
		} else {

			echo "<td colspan='4'>";
			echo "<div class='center'><input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'></div>";
			echo "</td></tr>";

		}
		echo "</table></div></form>";

		echo "<div id='tabcontent'></div>";
		echo "<script type='text/javascript'>loadDefaultTab();</script>";

		return true;

	}

	function defineTabs($ID,$withtemplate){
		global $LANG,$CFG_GLPI;

		$ong[1]=$LANG['title'][26];

		return $ong;
	}
}


?>
