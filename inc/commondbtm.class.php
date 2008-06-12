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

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

/// Common DataBase Table Manager Class
class CommonDBTM {

	/// Data of the Item
	var $fields	= array();
	/// Table name
	var $table="";
	/// GLPI Item type
	var $type=-1;
	/// Make an history of the changes
	var $dohistory=false;
	/// Is an item specific to entity
	var $entity_assign=false;
	/// Is an item that can be recursivly assign to an entity
	var $may_be_recursive=false;
	/// Is an item that can be private or assign to an entity
	var $may_be_private=false;
	/// Black list fields for date_mod updates
	var $date_mod_blacklist	= array();

	/// set false to desactivate automatic message on action
	var $auto_message_on_action=true;

	/**
	 * Constructor
	 **/
	function CommonDBTM () {

	}

	/**
	 * Clean cache used by the item $ID
	 *
	 *@param $ID ID of the item
	 *@return nothing
	 *
	 **/
	function cleanCache($ID){
		global $CFG_GLPI;
		cleanAllItemCache($ID,"GLPI_".$this->type);
		cleanAllItemCache("comments_".$ID,"GLPI_".$this->type);
		$CFG_GLPI["cache"]->remove("data_".$ID,"GLPI_".$this->table,true);
		cleanRelationCache($this->table);
	}

	/**
	 * Retrieve an item from the database
	 *
	 *@param $ID ID of the item to get
	 *@return true if succeed else false
	 *@todo Specific ones : Reservation Item
	 * 
	**/	
	function getFromDB ($ID) {

		// Make new database object and fill variables
		global $DB,$CFG_GLPI;
		// != 0 because 0 is consider as empty
		if (strlen($ID)==0) return false;
		
		$query = "SELECT * FROM ".$this->table." WHERE (".$this->getIndexName()." = $ID)";
		
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result)==1){
				$this->fields = $DB->fetch_assoc($result);
				return true;
			} 
		} 
		return false;;

	}
	/**
	 * Get the name of the index field
	 *
	 *@return name of the index field
	 *
	 **/
	function getIndexName(){
		return "ID";
	}
	/**
	 * Get an empty item
	 *
	 *@return true if succeed else false
	 *
	 **/
	function getEmpty () {
		//make an empty database object
		global $DB;
		if ($fields = $DB->list_fields($this->table)){
			foreach ($fields as $key => $val){
				$this->fields[$key] = "";
			}
		} else {
			return false;
		}
		if (isset($this->fields['FK_entities'])&&isset($_SESSION["glpiactive_entity"])){
			$this->fields['FK_entities']=$_SESSION["glpiactive_entity"];
		}
		$this->post_getEmpty();
		return true;
	}
	/**
	 * Actions done at the end of the getEmpty function
	 *
	 *@return nothing
	 *
	 **/
	function post_getEmpty () {
	}
	/**
	 * Update the item in the database
	 * 
	 *  @param $updates fields to update
 	 *  @param $oldvalues old values of the updated fields
	 *@return nothing
	 *
	 **/
	function updateInDB($updates,$oldvalues=array())  {

		global $DB,$CFG_GLPI;

		for ($i=0; $i < count($updates); $i++) {
			$query  = "UPDATE `".$this->table."` SET `";
			$query .= $updates[$i]."`";

			if ($this->fields[$updates[$i]]=="NULL"){
				$query .= " = ";
				$query .= $this->fields[$updates[$i]];
			} else {
				$query .= " = '";
				$query .= $this->fields[$updates[$i]]."'";
			}
			$query .= " WHERE ID ='";
			$query .= $this->fields["ID"];	
			$query .= "'";
			if (!$DB->query($query)){
				if (isset($oldvalues[$updates[$i]])){
					unset($oldvalues[$updates[$i]]);
				}
			}
		}

		if(count($oldvalues)){
			constructHistory($this->fields["ID"],$this->type,$oldvalues,$this->fields);
		}

		
		$this->cleanCache($this->fields["ID"]);
		return true;
	}

	/**
	 * Add an item to the database
	 *
	 *@return new ID of the item is insert successfull else false
	 *
	 **/
	function addToDB() {

		global $DB;
		//unset($this->fields["ID"]);
		$nb_fields=count($this->fields);
		if ($nb_fields>0){		

			// Build query
			$query = "INSERT INTO ".$this->table." (";
			$i=0;
			foreach ($this->fields as $key => $val) {
				$fields[$i] = $key;
				$values[$i] = $val;
				$i++;
			}		

			for ($i=0; $i < $nb_fields; $i++) {
				$query .= "`".$fields[$i]."`";
				if ($i!=$nb_fields-1) {
					$query .= ",";
				}
			}
			$query .= ") VALUES (";
			for ($i=0; $i < $nb_fields; $i++) {
				$query .= "'".$values[$i]."'";
				if ($i!=$nb_fields-1) {
					$query .= ",";
				}
			}
			$query .= ")";
			if ($result=$DB->query($query)) {
				$this->fields["ID"]=$DB->insert_id();
				cleanRelationCache($this->table);
				return $this->fields["ID"];
			} else {
				return false;
			}
		} else return false;
	}

	/**
	 * Restore item = set deleted flag to 0
	 *
	 *@param $ID ID of the item
	 *
	 *
	 *@return true if succeed else false
	 *
	 **/
	function restoreInDB($ID) {
		global $DB,$CFG_GLPI;
		if (in_array($this->table,$CFG_GLPI["deleted_tables"])){
			$query = "UPDATE ".$this->table." SET deleted='0' WHERE (ID = '$ID')";
			if ($result = $DB->query($query)) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	/**
	 * Mark deleted or purge an item in the database
	 *
	 *@param $ID ID of the item
	 *@param $force force the purge of the item (not used if the table do not have a deleted field)
	 *
	 *@return true if succeed else false
	 *
	 **/
	function deleteFromDB($ID,$force=0) {

		global $DB,$CFG_GLPI;

		if ($force==1||!in_array($this->table,$CFG_GLPI["deleted_tables"])){

			$this->cleanDBonPurge($ID);

			$this->cleanHistory($ID);

			$this->cleanRelationData($ID);

			$query = "DELETE from ".$this->table." WHERE ID = '$ID'";

			if ($result = $DB->query($query)) {
				$this->post_deleteFromDB($ID);
				$this->cleanCache($ID);
				return true;
			} else {
				return false;
			}
		}else {
			$query = "UPDATE ".$this->table." SET deleted='1' WHERE ID = '$ID'";		
			$this->cleanDBonMarkDeleted($ID);

			if ($result = $DB->query($query)){
				$this->cleanCache($ID);
				return true;
			} else {
				return false;
			}
		}
	}

	/**
	 * Clean data in the tables which have linked the deleted item
	 *
	 *@param $ID ID of the item
	 *
	 *
	 *@return nothing
	 *
	 **/
	function cleanHistory($ID){
		global $DB;
		if ($this->dohistory){
			$query = "DELETE FROM glpi_history WHERE ( device_type = '".$this->type."' AND FK_glpi_device = '$ID')";
			$DB->query($query);
		}
	}

	/**
	 * Clean data in the tables which have linked the deleted item
	 *
	 *@param $ID ID of the item
	 *
	 *
	 *@return nothing
	 *
	 **/
	function cleanRelationData($ID){
		global $DB;
		$RELATION=getDbRelations();
		if (isset($RELATION[$this->table])){
			foreach ($RELATION[$this->table] as $tablename => $field){
				if ($tablename[0]!='_'){
					if (!is_array($field)){
						$query="UPDATE `$tablename` SET `$field` = 0 WHERE `$field`='$ID' ";
						$DB->query($query);
					} else {
						foreach ($field as $f){
							$query="UPDATE `$tablename` SET `$f` = 0 WHERE `$f`='$ID' ";
							$DB->query($query);
						}
					}
				}
			}
		}
	}
	/**
	 * Actions done after the DELETE of the item in the database
	 *
	 *@param $ID ID of the item
	 *
	 *@return nothing
	 *
	 **/
	function post_deleteFromDB($ID){
	}

	/**
	 * Actions done when item is deleted from the database
	 *
	 *@param $ID ID of the item
	 *
	 *@return nothing
	 **/
	function cleanDBonPurge($ID) {
	}
	/**
	 * Actions done when item flag deleted is set to an item
	 *
	 *@param $ID ID of the item
	 *
	 *@return nothing
	 *
	 **/
	function cleanDBonMarkDeleted($ID) {
	}
	// Common functions

	/**
	 * Add an item in the database with all it's items.
	 *
	 *@param $input array : the _POST vars returned bye the item form when press add
	 *
	 *@return integer the new ID of the added item
	 *@todo specific ones : reservationresa , planningtracking
	 * 
	**/
	function add($input) {
		global $DB;
		
		$addMessAfterRedirect = false;

		if ($DB->isSlave()) {
			return false;
		}
			
		$input['_item_type_']=$this->type;
		$input=doHookFunction("pre_item_add",$input);

		if (isset($input['add'])){
			$input['_add']=$input['add'];
			unset($input['add']);
		}
		$input=$this->prepareInputForAdd($input);

		if ($input&&is_array($input)){
			$this->fields=array();
			$table_fields=$DB->list_fields($this->table);

			// fill array for udpate
			foreach ($input as $key => $val) {
				if ($key[0]!='_'&& isset($table_fields[$key])&&(!isset($this->fields[$key]) || $this->fields[$key] != $input[$key])) {
					$this->fields[$key] = $input[$key];
				}
			}
			// Auto set date_mod if exsist
			if (isset($table_fields['date_mod'])){
				$this->fields['date_mod']=$_SESSION["glpi_currenttime"];
			}

			if ($newID= $this->addToDB()){
				$this->addMessageOnAddAction($input);
				$this->post_addItem($newID,$input);
				doHook("item_add",array("type"=>$this->type, "ID" => $newID));
				return $newID;
			} else {
				return false;
			}

		} else {
			return false;
		}	
	}

	/**
	 * Add a message on add action
	 *
	 *@param $input array : the _POST vars returned bye the item form when press add
	 *
	**/
	function addMessageOnAddAction($input){
		global $INFOFORM_PAGES, $CFG_GLPI, $LANG;

		if (!isset($INFOFORM_PAGES[$this->type])){
			return;
		}

		$addMessAfterRedirect=false;
		if (isset($input['_add'])){
			$addMessAfterRedirect=true;
		}
		if (isset($input['_no_message']) || !$this->auto_message_on_action){
			$addMessAfterRedirect=false;
		}

		if ($addMessAfterRedirect) {
			addMessageAfterRedirect($LANG["common"][70] . 
			": <a href='" . $CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$this->type] . "?ID=" . $this->fields['ID'] . (isset($input['is_template'])?"&amp;withtemplate=1":"")."'>" .
			(isset($this->fields["name"]) && !empty($this->fields["name"]) ? stripslashes($this->fields["name"]) : "(".$this->fields['ID'].")") . "</a>");
		} 
	}

	/**
	 * Prepare input datas for adding the item
	 *
	 *@param $input datas used to add the item
	 *
	 *@return the modified $input array
	 *
	 **/
	function prepareInputForAdd($input) {
		return $input;
	}
	/**
	 * Actions done after the ADD of the item in the database
	 * 
	 *@param $newID ID of the new item 
	 *@param $input datas used to add the item
	 *
	 * @return nothing 
	 * 
	**/
	function post_addItem($newID,$input) {
	}


	/**
	 * Update some elements of an item in the database.
	 *
	 *@param $input array : the _POST vars returned bye the item form when press update
	 *@param $history boolean : do history log ?
	 *
	 *
	 *@return Nothing (call to the class member)
	 *@todo specific ones : reservationresa, planningtracking
	 *
	**/
    function update($input,$history=1) {
		global $DB;
		if ($DB->isSlave())
			return false;

		$input['_item_type_']=$this->type;
		$input=doHookFunction("pre_item_update",$input);

		$input=$this->prepareInputForUpdate($input);


		if (isset($input['update'])){
			$input['_update']=$input['update'];
			unset($input['update']);
		}

		if ($this->getFromDB($input[$this->getIndexName()])){

			// Fill the update-array with changes
			$x=0;
			$updates=array();
			$oldvalues=array();
			foreach ($input as $key => $val) {
				if (array_key_exists($key,$this->fields)){
					// Secu for null values on history
					// TODO : Int with NULL default value in DB -> default value 0
/*					if (is_null($this->fields[$key])){
						if (is_int($input[$key])||$input[$key]=='0') 	$this->fields[$key]=0;
					}
*/
					if ($this->fields[$key] != stripslashes($input[$key])) {
						if ($key!="ID"){
							// Do logs
							if ($this->dohistory&&$history){
								$oldvalues[$key]=$this->fields[$key];
							}
							$this->fields[$key] = $input[$key];
							$updates[$x] = $key;
							$x++;
						}
					}
				}
			}

			if(count($updates)){
				if (isset($this->fields['date_mod'])){
					// is a non blacklist field exists
					if (count(array_diff($updates,$this->date_mod_blacklist)) > 0){
						$this->fields['date_mod']=$_SESSION["glpi_currenttime"];
						$updates[$x++] = 'date_mod';
					}
				}

				list($input,$updates)=$this->pre_updateInDB($input,$updates);
				

				if ($this->updateInDB($updates,$oldvalues)){
					$this->addMessageOnUpdateAction($input);
					doHook("item_update",array("type"=>$this->type, "ID" => $input["ID"]));
				}
			} 
			$this->post_updateItem($input,$updates,$history);
		}
	}

	/**
	 * Add a message on update action
	 *
	 *@param $input array : the _POST vars returned bye the item form when press add
	 *
	**/
	function addMessageOnUpdateAction($input){
		global $INFOFORM_PAGES, $CFG_GLPI, $LANG;

		if (!isset($INFOFORM_PAGES[$this->type])){
			return;
		}

		$addMessAfterRedirect=false;
		if (isset($input['_update'])){
			$addMessAfterRedirect=true;
		}
		if (isset($input['_no_message']) || !$this->auto_message_on_action){
			$addMessAfterRedirect=false;
		}

		if ($addMessAfterRedirect) {
			addMessageAfterRedirect($LANG["common"][71].": ".(isset($this->fields["name"]) && !empty($this->fields["name"]) ? stripslashes($this->fields["name"]) : "(".$this->fields['ID'].")"));
		} 
	}

	/**
	 * Prepare input datas for updating the item
	 *
	 *@param $input datas used to update the item
	 * 
	 *@return the modified $input array 
	 * 
	**/
	function prepareInputForUpdate($input) {
		return $input;
	}

	/**
	 * Actions done after the UPDATE of the item in the database
	 *
	 *@param $input datas used to update the item
	 *@param $updates array of the updated fields 
	 *@param $history store changes history ? 
	 * 
	 *@return nothing 
	 * 
	**/
	function post_updateItem($input,$updates,$history=1) {
	}

	/**
	 * Actions done before the UPDATE of the item in the database
	 *
	 *@param $input datas used to update the item
	 *@param $updates array of the updated fields
	 * 
	 *@return nothing
	 * 
	**/
	function pre_updateInDB($input,$updates) {
		return array($input,$updates);
	}

	/**
	 * Delete an item in the database.
	 *
	 *@param $input array : the _POST vars returned bye the item form when press delete
	 *@param $force boolean : force deletion
	 *@param $history boolean : do history log ?
	 *
	 *
	 *@return Nothing ()
	 *
	 **/
	function delete($input,$force=0,$history=1) {
		global $DB;
		
		if ($DB->isSlave())
			return false;

		$input['_item_type_']=$this->type;
		if ($force){
			$input=doHookFunction("pre_item_purge",$input);
			if (isset($input['purge'])){
				$input['_purge']=$input['purge'];
				unset($input['purge']);
			}
		} else {
			$input=doHookFunction("pre_item_delete",$input);
			if (isset($input['delete'])){
				$input['_delete']=$input['delete'];
				unset($input['delete']);
			}
		}

		if ($this->getFromDB($input[$this->getIndexName()])){
			if ($this->pre_deleteItem($this->fields["ID"])){
				if ($this->deleteFromDB($this->fields["ID"],$force)){
					if ($force){
						$this->addMessageOnPurgeAction($input);
						doHook("item_purge",array("type"=>$this->type, "ID" => $this->fields["ID"]));
					} else {
						$this->addMessageOnDeleteAction($input);

						if ($this->dohistory&&$history){
							$changes[0] = 0;
							$changes[1] = $changes[2] = "";
				
							historyLog ($this->fields["ID"],$this->type,$changes,0,HISTORY_DELETE_ITEM);
						}
						doHook("item_delete",array("type"=>$this->type, "ID" => $this->fields["ID"]));
					}
				}
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}

	}

	/**
	 * Add a message on delete action
	 *
	 *@param $input array : the _POST vars returned bye the item form when press add
	 *
	**/
	function addMessageOnDeleteAction($input){
		global $INFOFORM_PAGES, $CFG_GLPI, $LANG;

		if (!isset($INFOFORM_PAGES[$this->type])){
			return;
		}

		if (!in_array($this->table,$CFG_GLPI["deleted_tables"])){
			return;
		}

		$addMessAfterRedirect=false;
		if (isset($input['_delete'])){
			$addMessAfterRedirect=true;
		}
		if (isset($input['_no_message']) || !$this->auto_message_on_action){
			$addMessAfterRedirect=false;
		}

		if ($addMessAfterRedirect) {
			addMessageAfterRedirect($LANG["common"][72] . 
			": <a href='" . $CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$this->type] . "?ID=" . $this->fields['ID'] . "'>" .
			(isset($this->fields["name"]) && !empty($this->fields["name"]) ? stripslashes($this->fields["name"]) : "(".$this->fields['ID'].")") . "</a>");
		} 
	}

	/**
	 * Add a message on purge action
	 *
	 *@param $input array : the _POST vars returned bye the item form when press add
	 *
	**/
	function addMessageOnPurgeAction($input){
		global $INFOFORM_PAGES, $CFG_GLPI, $LANG;

		if (!isset($INFOFORM_PAGES[$this->type])){
			return;
		}

		$addMessAfterRedirect=false;
		if (isset($input['_purge'])){
			$addMessAfterRedirect=true;
		}
		if (isset($input['_no_message']) || !$this->auto_message_on_action){
			$addMessAfterRedirect=false;
		}

		if ($addMessAfterRedirect) {
			addMessageAfterRedirect($LANG["common"][73].": ".(isset($this->fields["name"]) && !empty($this->fields["name"]) ? stripslashes($this->fields["name"]) : "(".$this->fields['ID'].")"));
		} 
	}


	
	/**
	 * Actions done before the DELETE of the item in the database / Maybe used to add another check for deletion 
	 *
	 *@param $ID ID of the item to delete
	 * 
	 *@return bool : true if item need to be deleted else false
	 * 
	**/
	function pre_deleteItem($ID) {
		return true;
	}
	/** 
	 * Restore an item trashed in the database. 
	 * 
	 *@param $input array : the _POST vars returned bye the item form when press restore 
	 *@param $history boolean : do history log ?
	 * 
	 *@return Nothing () 
	 *@todo specific ones : cartridges / consumables 
	 * 
	**/ 
	// specific ones : cartridges / consumables
	function restore($input,$history=1) {

		if (isset($input['restore'])){
			$input['_restore']=$input['restore'];
			unset($input['restore']);
		}

		$input['_item_type_']=$this->type;
		$input=doHookFunction("pre_item_restore",$input);

		if ($this->getFromDB($input[$this->getIndexName()])){
			if ($this->restoreInDB($input["ID"])){
				$this->addMessageOnRestoreAction($input);

				if ($this->dohistory&&$history){
					$changes[0] = 0;
					$changes[1] = $changes[2] = "";
	
					historyLog ($input["ID"],$this->type,$changes,0,HISTORY_RESTORE_ITEM);
				}
	
				doHook("item_restore",array("type"=>$this->type, "ID" => $input["ID"]));
			}
		}
	}

	/**
	 * Add a message on restore action
	 *
	 *@param $input array : the _POST vars returned bye the item form when press add
	 *
	**/
	function addMessageOnRestoreAction($input){
		global $INFOFORM_PAGES, $CFG_GLPI, $LANG;

		if (!isset($INFOFORM_PAGES[$this->type])){
			return;
		}

		$addMessAfterRedirect=false;
		if (isset($input['_restore'])){
			$addMessAfterRedirect=true;
		}
		if (isset($input['_no_message']) || !$this->auto_message_on_action){
			$addMessAfterRedirect=false;
		}

		if ($addMessAfterRedirect) {
			addMessageAfterRedirect($LANG["common"][74] . 
			": <a href='" . $CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$this->type] . "?ID=" . $this->fields['ID'] . "'>" .
			(isset($this->fields["name"]) && !empty($this->fields["name"]) ? stripslashes($this->fields["name"]) : "(".$this->fields['ID'].")") . "</a>");
		} 
	}

	/**
	 * Reset fields of the item 
	 *
	**/
	function reset(){
		$this->fields=array();

	}

	/**
	 * Define onglets to display
	 *
	 *@param $withtemplate is a template view ?
	 * 
	 *@return array containing the onglets
	 * 
	**/
	function defineOnglets($withtemplate){
		return array();
	}

	/**
	 * Show onglets
	 *
	 *@param $ID ID of the item to display
	 *@param $withtemplate is a template view ?
	 *@param $actif active onglet
	 *@param $nextprevcondition condition used to find next/previous items
	 *@param $nextprev_item field used to define next/previous items
	 *@param $addurlparam parameters to add to the URLs 
	 * 
	 *@return Nothing () 
	 *  
	**/
	function showOnglets($ID,$withtemplate,$actif,$nextprevcondition="",$nextprev_item="",$addurlparam=""){
		global $LANG,$CFG_GLPI;

		$target=$_SERVER['PHP_SELF']."?ID=".$ID;
	
		$template="";
		if(!empty($withtemplate)){
			$template="&amp;withtemplate=$withtemplate";
		}
	
		echo "<div id='barre_onglets'><ul id='onglet'>";
	
		if (count($onglets=$this->defineOnglets($withtemplate))){
			//if (empty($withtemplate)&&haveRight("reservation_central","r")&&function_exists("isReservable")){
			//	$onglets[11]=$LANG["Menu"][17];
			//	ksort($onglets);
			//}
			foreach ($onglets as $key => $val ) {
				echo "<li "; if ($actif==$key){ echo "class='actif'";} echo  "><a href='$target&amp;onglet=$key$template$addurlparam'>".$val."</a></li>";
			}
			if(empty($withtemplate)){
				echo "<li class='invisible'>&nbsp;</li>";
				echo "<li "; if ($actif=="-1") {echo "class='actif'";} echo "><a href='$target&amp;onglet=-1$template$addurlparam'>".$LANG["common"][66]."</a></li>";
			}
		}
	
	
	
		displayPluginHeadings($target,$this->type,$withtemplate,$actif);
	
		echo "<li class='invisible'>&nbsp;</li>";
	
		if (empty($withtemplate)&&preg_match("/\?ID=([0-9]+)/",$target,$ereg)){
			$ID=$ereg[1];
			$next=getNextItem($this->table,$ID,$nextprevcondition,$nextprev_item);
			$prev=getPreviousItem($this->table,$ID,$nextprevcondition,$nextprev_item);
			$cleantarget=preg_replace("/\?ID=([0-9]+)/","",$target);
			if ($prev>0) {
				echo "<li><a href='$cleantarget?ID=$prev$addurlparam'><img src=\"".$CFG_GLPI["root_doc"]."/pics/left.png\" alt='".$LANG["buttons"][12]."' title='".$LANG["buttons"][12]."'></a></li>";
			}
			if ($next>0) {
				echo "<li><a href='$cleantarget?ID=$next$addurlparam'><img src=\"".$CFG_GLPI["root_doc"]."/pics/right.png\" alt='".$LANG["buttons"][11]."' title='".$LANG["buttons"][11]."'></a></li>";
			}
		}
	
		echo "</ul></div>";
	} 

	/**
	 * Have I the right to "write" the Object
	 * 
	 * @return Array of can_edit (can write) + can_recu (can make recursive)
	**/
/*	function canEditAndRecurs () {
		global $CFG_GLPI;
		
		$can_edit = $this->canCreate();

		if (!isset($CFG_GLPI["recursive_type"][$this->type])) {
			$can_recu = false;
			
		} else if (!isset($this->fields["ID"])) {
			$can_recu = haveRecursiveAccessToEntity($_SESSION["glpiactive_entity"]);
				
		} else {
			if ($this->fields["recursive"]) {
				$can_edit = $can_edit && haveRecursiveAccessToEntity($this->fields["FK_entities"]);
				$can_recu = $can_edit;
			}	
			else {
				$can_recu = $can_edit && haveRecursiveAccessToEntity($this->fields["FK_entities"]);	
			}
		}
	
		return array($can_edit, $can_recu);		
	}
*/	
	/**
	 * Have I the right to "write" the Object
	 *
	 * @return bitmask : 0:no, 1:can_edit (can write), 2:can_recu (can make recursive)
	**/
/*	function canEdit () {
		list($can_edit,$can_recu)=$this->canEditAndRecurs();
		return ($can_edit?1:0)+($can_recu?2:0);
	}
*/


	/**
	 * Have I the right to "create" the Object
	 * 
	 * May be overloaded if needed (ex kbitem)
	 *
	 * @return booleen
	 **/
	function canCreate () {
		return haveTypeRight($this->type,"w");
	}

	/**
	 * Have I the right to "view" the Object
	 * 
	 * May be overloaded if needed
	 *
	 * @return booleen
	 **/
	function canView () {
		return haveTypeRight($this->type,"r");
	}

	/**
	 * Check right on an item
	 *
	 * @param $ID ID of the item (-1 if new item)
	 * @param $right Right to check : r / w / recursive
	 * @param $entity entity to check right (used for adding item)
	 *
	 * @return boolean
	**/
	function can($ID,$right,$entity=-1){

		$entity_to_check=-1;
		$recursive_state_to_check=0;
		// Get item if not already loaded
		if (empty($ID)||$ID<=0){
			$this->getEmpty($ID);
			// No entity define : adding process : use active entity
			if ($entity==-1){
				$entity_to_check=$_SESSION["glpiactive_entity"];
			} else { 
				$entity_to_check=$entity;
			}
		} else {
			if (!isset($this->fields['ID'])||$this->fields['ID']!=$ID){
				// Item not found : no right
				if (!$this->getFromDB($ID)){
					return false;
				}
			}
			if ($this->entity_assign){
				$entity_to_check=$this->fields["FK_entities"];
				if ($this->may_be_recursive){
					$recursive_state_to_check=$this->fields["recursive"];
				}
			}

		} 

//		echo $ID."_".$entity_to_check."_".$recursive_state_to_check.'<br>';
		switch ($right){
			case 'r':
				// Personnal item
				if ($this->may_be_private && $this->fields['private'] && $this->fields['FK_users']==$_SESSION["glpiID"]){
					return true;
				} else {
					// Check Global Right
					if ($this->canView()){
						// Is an item assign to an entity
						if ($this->entity_assign){
							// Can be recursive check 
							if ($this->may_be_recursive){
								return haveAccessToEntity($entity_to_check,$recursive_state_to_check);
							} else { // Non recursive item
								return haveAccessToEntity($entity_to_check);
							}
						} else { // Global item
							return true;
						}
					}
				}
				break;
			case 'w':
				// Personnal item
				if ($this->may_be_private && $this->fields['private'] && $this->fields['FK_users']==$_SESSION["glpiID"]){
					return true;
				} else {
					// Check Global Right
					if ($this->canCreate()){
						// Is an item assign to an entity
						if ($this->entity_assign){
							// Have access to entity
							return haveAccessToEntity($entity_to_check);
						} else { // Global item
							return true;
						}
					}
				}
				break;
			case 'recursive':
				if ($this->entity_assign && $this->may_be_recursive){
					if ($this->canCreate() && haveAccessToEntity($entity_to_check)){
						// Can make recursive if recursive access to entity
						return haveRecursiveAccessToEntity($entity_to_check);
					}
				}
				break;
		}
		return false;

	}
	/**
	 * Check right on an item with block
	 *
	 * @param $ID ID of the item (-1 if new item)
	 * @param $right Right to check : r / w / recursive
	 * @param $entity entity to check right (used for adding item)
	 * @return nothing
	**/
	function check($ID,$right,$entity=-1) {
		global $CFG_GLPI;
	
		if (!$this->can($ID,$right,$entity)) {
			// Gestion timeout session
			if (!isset ($_SESSION["glpiID"])) {
				glpi_header($CFG_GLPI["root_doc"] . "/index.php");
				exit ();
			}
			displayRightError();
		}
	}
}

?>