<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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

/**
 *  Connection class used to connect computer to peripherals, printers and monitors
 */
class Connection {

	//! Connection ID
	var $ID				= 0;
	//! Computer ID
	var $end1			= 0;
	//! Connected Item  ID
	var $end2			= 0;
	//! Connected Item Type
	var $type			= 0;
	//! Name of the computer
	var $device_name	= "";
	//! ID of the computer
	var $device_ID		= 0;
	//! Is the computer Deleted
	var $deleted ='0';
	//! Is the computer a template
	var $is_template ='0';

	/**
	 * Get computers connected to a item
	 *
	 * $type must set before
	 *
	 * @param $ID ID of the computer
         * @param $type type of the items searched
	 * @return array of ID of connected items
	 */
	function getComputersContact ($type,$ID) {
		global $DB;
		$query = "SELECT glpi_connect_wire.ID as connectID, glpi_connect_wire.end2 as end2, glpi_computers.* 
			FROM glpi_connect_wire 
			INNER JOIN glpi_computers ON (glpi_computers.ID = glpi_connect_wire.end2)
			 WHERE (glpi_connect_wire.end1 = '$ID' AND glpi_connect_wire.type = '$type' 
				AND glpi_computers.is_template = '0')";
		if ($result=$DB->query($query)) {
			if ($DB->numrows($result)==0) return false;
			$ret=array();
			while ($data = $DB->fetch_array($result)){
				if (isset($data["end2"])) {
					$ret[$data["connectID"]] = $data;
				}
			}
			return $ret;
		} else {
			return false;
		}
	}

	/**
	 * Delete connection
	 *
	 * @param $ID Connection ID
	 * @return boolean
	 */
	function deleteFromDB($ID) {

		global $DB;

		$query = "DELETE from glpi_connect_wire WHERE (ID = '$ID')";
		if ($result = $DB->query($query)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Add a connection
	 *
	 * end1, end2 and type must be set
	 *
	 * @return integer : ID of added connection
	 */
	function addToDB() {
		global $DB;

		// Build query
		$query = "INSERT INTO glpi_connect_wire (end1,end2,type) VALUES ('$this->end1','$this->end2','$this->type')";
		$result=$DB->query($query);
		return $DB->insert_id();
	}

}
?>
