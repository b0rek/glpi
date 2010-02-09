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

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}


///Class Devices
class Device extends CommonDBTM {
	/// Current device type
	var $devtype=0;

	/**
	 * Constructor
	 * @param $dev_type device type
	**/
	function __construct($dev_type) {
		$this->devtype=$dev_type;
		$this->table=getDeviceTable($dev_type);
	}


	function prepareInputForAdd($input) {
		if (isset($input['device_type'])){
			switch ($input['device_type']){
				case PROCESSOR_DEVICE :
					if (isset($input['frequence'])){
						if (!is_numeric($input['frequence'])){
							$input['frequence']=0;
						}
					}
					break;
			}
		}

		return $input;
	}

	function cleanDBonPurge($ID) {
		global $DB;
		$query="SELECT FK_computers 
			FROM glpi_computer_device 
			WHERE FK_device = '$ID' AND device_type='".$this->devtype."'";
		$result=$DB->query($query);
		if ($DB->numrows($result)){
			while ($data=$DB->fetch_assoc($result)){
				cleanAllItemCache("device_".$data["FK_computers"],"GLPI_".COMPUTER_TYPE);
			}
		}
		$query2 = "DELETE FROM glpi_computer_device 
			WHERE FK_device = '$ID' AND device_type='".$this->devtype."'";
		$DB->query($query2);
	}

	function post_updateItem($input,$updates,$history=1) {
		global $DB;
		if (count($updates)){
			$query="SELECT FK_computers 
				FROM glpi_computer_device 
				WHERE (FK_device = '".$input["ID"]."' AND device_type='".$input["device_type"]."')";
			$result=$DB->query($query);
			if ($DB->numrows($result)){
				while ($data=$DB->fetch_assoc($result)){
					cleanAllItemCache("device_".$data["FK_computers"],"GLPI_".COMPUTER_TYPE);
				}
			}
		}
	}

	function canView () {
		return haveRight("device","r");
	}

	function canCreate () {
		return haveRight("device","w");
	}
	// SPECIFIC FUNCTIONS
	/**
	 * Connect the current device to a computer
	 *
	 *@param $compID computer ID
	 *@param $device_type device type
	 *@param $specificity value of the specificity
	 *@return boolean : success ?
	**/
	function computer_link($compID,$device_type,$specificity='') {
		global $DB;
		$query = "INSERT INTO glpi_computer_device (device_type,FK_device,FK_computers,specificity) 
			VALUES ('".$device_type."','".$this->fields["ID"]."','".$compID."','".$specificity."')";
		if($DB->query($query)) {
			return $DB->insert_id();
		} else { 
			return false;
		}
	}

}
?>
