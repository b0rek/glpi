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

/** Determine if a table is a dropdown one for stat
* @param $table string : table name
* @return boolean 
*/
function is_dropdown_stat($table) {
	$dropdowns = array ("locations","os","model");
	if(in_array(str_replace("glpi_dropdown_","",$table),$dropdowns)) return true;
	elseif(strcmp("glpi_type_computers",$table) == 0) return true;
	else return false; 
}


function getStatsItems($date1,$date2,$type){
	global $CFG_GLPI,$DB;
	$val=array();

	switch ($type){
		case "technicien":
			$val = getNbIntervTech($date1,$date2);
		break;
		case "technicien_followup":
			$val = getNbIntervTechFollowup($date1,$date2);
		break;
		case "enterprise":
			$val = getNbIntervEnterprise($date1,$date2);

		break;
		case "user":
			$val = getNbIntervAuthor($date1,$date2);
		break;
		case "recipient":
			$val = getNbIntervRecipient($date1,$date2);
		break;
		case "category":
			$val = getNbIntervCategory($date1,$date2);
		break;
		case "group":
			$val = getNbIntervGroup($date1,$date2);
		break;
		case "assign_group":
			$val = getNbIntervAssignGroup($date1,$date2);
		break;

		case "priority":
			$val = getNbIntervPriority($date1,$date2);

		break;
		case "request_type":
			$val = getNbIntervRequestType($date1,$date2);
		
		break;
		
		case "title":
			$val = getNbIntervTitleOrType($date1,$date2,true);
		
		break;
		case "type":
			$val = getNbIntervTitleOrType($date1,$date2,false);
		
		break;
		
		case "glpi_type_computers":
		case "glpi_dropdown_model":
		case "glpi_dropdown_os":
		case "glpi_dropdown_locations":
			$val = getNbIntervDropdown($type);

		break;
		// DEVICE CASE
		default :
		$device_table = getDeviceTable($type);

		//select devices IDs (table row)
		$query = "SELECT ID, designation FROM ".$device_table." ORDER BY designation";
		$result = $DB->query($query);

		if($DB->numrows($result) >=1) {
			$i = 0;
			while($line = $DB->fetch_assoc($result)) {
				$val[$i]['ID'] = $line['ID'];
				$val[$i]['link'] = $line['designation'];
				$i++;
			}
		}

		break;
	}
	return $val;
}

function displayStats($type,$field,$date1,$date2,$start,$value,$value2=""){
	global $LANG,$CFG_GLPI;

	// Set display type for export if define
	$output_type=HTML_OUTPUT;
	if (isset($_GET["display_type"]))
		$output_type=$_GET["display_type"];

	if ($output_type==HTML_OUTPUT) // HTML display
		echo "<div align ='center'>";

	if (is_array($value)){


		$end_display=$start+$_SESSION['glpilist_limit'];
		$numrows=count($value);
		if (isset($_GET['export_all'])) {
			$start=0;
			$end_display=$numrows;
		}
		$nbcols=8;
		if ($output_type!=HTML_OUTPUT) // not HTML display
			$nbcols--;
		echo displaySearchHeader($output_type,$end_display-$start+1,$nbcols);
		echo displaySearchNewLine($output_type);
		$header_num=1;
		echo displaySearchHeaderItem($output_type,"&nbsp;",$header_num);
		if ($output_type==HTML_OUTPUT){ // HTML display
			echo displaySearchHeaderItem($output_type,"",$header_num);
		}
		echo displaySearchHeaderItem($output_type,$LANG['stats'][13],$header_num);
		echo displaySearchHeaderItem($output_type,$LANG['stats'][11],$header_num);
		echo displaySearchHeaderItem($output_type,$LANG['stats'][15],$header_num);
		echo displaySearchHeaderItem($output_type,$LANG['stats'][25],$header_num);
		echo displaySearchHeaderItem($output_type,$LANG['stats'][27],$header_num);
		echo displaySearchHeaderItem($output_type,$LANG['stats'][30],$header_num);
		// End Line for column headers		
		echo displaySearchEndLine($output_type);
		$row_num=1;
		for ($i=$start;$i< $numrows && $i<($end_display);$i++){
			$row_num++;
			$item_num=1;
			echo displaySearchNewLine($output_type,$i%2);
			echo displaySearchItem($output_type,$value[$i]['link'],$item_num,$row_num);
			if ($output_type==HTML_OUTPUT){ // HTML display
				
				$link="";
				if ($value[$i]['ID']>0){
					$link="<a href='stat.graph.php?ID=".$value[$i]['ID']."&amp;date1=$date1&amp;date2=$date2&amp;type=$type".(!empty($value2)?"&amp;champ=$value2":"")."'><img src=\"".$CFG_GLPI["root_doc"]."/pics/stats_item.png\" alt='' title=''></a>";
				}
				echo displaySearchItem($output_type,$link,$item_num,$row_num);
			}

			//le nombre d'intervention
			//the number of intervention

			$opened=constructEntryValues("inter_total",$date1,$date2,$type,$value[$i]["ID"],$value2);
			$nb_opened=array_sum($opened);
			echo displaySearchItem($output_type,$nb_opened,$item_num,$row_num);
			//le nombre d'intervention resolues
			//the number of resolved intervention
			$solved=constructEntryValues("inter_solved",$date1,$date2,$type,$value[$i]["ID"],$value2);
			$nb_solved=array_sum($solved);
			echo displaySearchItem($output_type,$nb_solved,$item_num,$row_num);
			//Le temps moyen de resolution
			//The average time to resolv
			$data=constructEntryValues("inter_avgsolvedtime",$date1,$date2,$type,$value[$i]["ID"],$value2);
			foreach ($data as $key2 => $val2){
				$data[$key2]*=$solved[$key2];
			}
			if ($nb_solved>0)
				$nb=array_sum($data)/$nb_solved;
			else $nb=0;

			$timedisplay=$nb*HOUR_TIMESTAMP;
			if ($output_type==HTML_OUTPUT || $output_type==PDF_OUTPUT_LANDSCAPE || $output_type==PDF_OUTPUT_PORTRAIT){
				$timedisplay=timestampToString($timedisplay,0);
			}
			echo displaySearchItem($output_type,$timedisplay,$item_num,$row_num);
			//Le temps moyen de l'intervention r�lle
			//The average realtime to resolv
			$data=constructEntryValues("inter_avgrealtime",$date1,$date2,$type,$value[$i]["ID"],$value2);
			
			foreach ($data as $key2 => $val2){
				if (isset($solved[$key2])){
					$data[$key2]*=$solved[$key2];
				} else {
					$data[$key2]*=0;
				}
			}
			$total_realtime=array_sum($data);
			if ($nb_solved>0)
				$nb=$total_realtime/$nb_solved;
			else $nb=0;

			$timedisplay=$nb*MINUTE_TIMESTAMP;
			if ($output_type==HTML_OUTPUT || $output_type==PDF_OUTPUT_LANDSCAPE || $output_type==PDF_OUTPUT_PORTRAIT){
				$timedisplay=timestampToString($timedisplay,0);
			}
			echo displaySearchItem($output_type,$timedisplay,$item_num,$row_num);
			//Le temps total de l'intervention r�lle
			//The total realtime to resolv
			$timedisplay=$total_realtime*MINUTE_TIMESTAMP;
			if ($output_type==HTML_OUTPUT || $output_type==PDF_OUTPUT_LANDSCAPE || $output_type==PDF_OUTPUT_PORTRAIT){
				$timedisplay=timestampToString($timedisplay,0);
			}
			echo displaySearchItem($output_type,$timedisplay,$item_num,$row_num);				
			//Le temps moyen de prise en compte du ticket
			//The average time to take a ticket into account
			$data=constructEntryValues("inter_avgtakeaccount",$date1,$date2,$type,$value[$i]["ID"],$value2);

			foreach ($data as $key2 => $val2){
				$data[$key2]*=$solved[$key2];
			}
			if ($nb_solved>0)
				$nb=array_sum($data)/$nb_solved;
			else $nb=0;

			$timedisplay=$nb*HOUR_TIMESTAMP;
			if ($output_type==HTML_OUTPUT || $output_type==PDF_OUTPUT_LANDSCAPE || $output_type==PDF_OUTPUT_PORTRAIT){
				$timedisplay=timestampToString($timedisplay,0);
			}
			echo displaySearchItem($output_type,$timedisplay,$item_num,$row_num);

			echo displaySearchEndLine($output_type);
		}
		// Display footer
		echo displaySearchFooter($output_type);
	} else {
		echo $LANG['stats'][23];
	}
	if ($output_type==HTML_OUTPUT) // HTML display
		echo "</div>";
}


/** Get users which have intervention assigned to  between 2 dates
* @param $date1 date : begin date
* @param $date2 date : end date
* @return array contains the distinct users which have any intervention assigned to.
*/
function getNbIntervTech($date1,$date2){
	global $DB;
	$query = "SELECT distinct glpi_tracking.assign as assign, glpi_users.name as name, glpi_users.realname as realname, glpi_users.firstname as firstname";
	$query.= " FROM glpi_tracking ";
	$query.= " LEFT JOIN glpi_users  ON (glpi_users.ID=glpi_tracking.assign) ";

	$query.=getEntitiesRestrictRequest("WHERE","glpi_tracking");

   if (!empty($date1)||!empty($date2)) {
      $query .= " AND (".getDateRequest("glpi_tracking.`date`",$date1,$date2);
      $query .= " OR ".getDateRequest("glpi_tracking.`closedate`",$date1,$date2).") ";
   }

	$query.= " ORDER BY realname, firstname, name";
	$result = $DB->query($query);
	$tab=array();

	if($DB->numrows($result) >=1) {
		while($line = $DB->fetch_assoc($result)) {
			$tmp['ID']= $line["assign"];
			$tmp['link']=formatUserName($line["assign"],$line["name"],$line["realname"],$line["firstname"],1);
			$tab[]=$tmp;
		}
	}
	return $tab;
}

/** Get users which have followup assigned to  between 2 dates
* @param $date1 date : begin date
* @param $date2 date : end date
* @return array contains the distinct users which have any followup assigned to.
*/
function getNbIntervTechFollowup($date1,$date2){
	global $DB;
	$query = "SELECT DISTINCT glpi_followups.author as author, glpi_users.name as name, glpi_users.realname as realname, glpi_users.firstname as firstname";
	$query.= " FROM glpi_tracking ";
	$query.= " LEFT JOIN glpi_followups ON (glpi_tracking.ID = glpi_followups.tracking) ";
	$query.= " LEFT JOIN glpi_users  ON (glpi_users.ID=glpi_followups.author) ";

	$query.=getEntitiesRestrictRequest("WHERE","glpi_tracking");
   if (!empty($date1)||!empty($date2)) {
      $query .= " AND (".getDateRequest("glpi_tracking.`date`",$date1,$date2);
      $query .= " OR ".getDateRequest("glpi_tracking.`closedate`",$date1,$date2).") ";
   }
	$query.=" AND glpi_followups.author <> 0 AND glpi_followups.author IS NOT NULL";

	$query.= " ORDER BY realname,firstname, name";
	$result = $DB->query($query);
	$tab=array();

	if($DB->numrows($result) >=1) {
		while($line = $DB->fetch_assoc($result)) {
			$tmp['ID']= $line["author"];
			$tmp['link']=formatUserName($line["author"],$line["name"],$line["realname"],$line["firstname"],1);
			$tab[]=$tmp;
		}
	}
	return $tab;
}


/** Get enterprises which have followup assigned to between 2 dates
* @param $date1 date : begin date
* @param $date2 date : end date
* @return array contains the distinct enterprises which have any tickets assigned to.
*/
function getNbIntervEnterprise($date1,$date2){
	global $DB,$CFG_GLPI;
	$query = "SELECT distinct glpi_tracking.assign_ent as assign_ent, glpi_enterprises.name as name";
	$query.= " FROM glpi_tracking ";
	$query.= " LEFT JOIN glpi_enterprises  ON (glpi_enterprises.ID=glpi_tracking.assign_ent) ";

	$query.=getEntitiesRestrictRequest("WHERE","glpi_tracking");
   if (!empty($date1)||!empty($date2)) {
      $query .= " AND (".getDateRequest("glpi_tracking.`date`",$date1,$date2);
      $query .= " OR ".getDateRequest("glpi_tracking.`closedate`",$date1,$date2).") ";
   }
	$query.= " ORDER BY name";
	$tab=array();
	$result = $DB->query($query);
	if($DB->numrows($result) >0) {

		while($line = $DB->fetch_assoc($result)) {
			$tmp["ID"]=$line["assign_ent"];
			$tmp["link"]="<a href='".$CFG_GLPI["root_doc"]."/front/enterprise.form.php?ID=".$line["assign_ent"]."'>";
			$tmp["link"].=$line["name"];
			$tmp["link"].="</a>";
			$tab[]=$tmp;
		}
		
	}
	return $tab;
}

/** Get dropdown values 
* @param $dropdown string : table name
* @return array contains the distinct dropdown values
*/
function getNbIntervDropdown($dropdown){
	global $DB,$CFG_GLPI;
	$field="name";

	if (in_array($dropdown,$CFG_GLPI["dropdowntree_tables"])) {
		$field="completename";
	}
	$where=" ";
	$order=" ORDER BY $field";
	if (in_array($dropdown,$CFG_GLPI["specif_entities_tables"])){
		$where=getEntitiesRestrictRequest(" WHERE",$dropdown);
		$order=" ORDER BY FK_entities, $field";	
	}

	$query = "SELECT * FROM ". $dropdown.$where.$order;
	$tab=array();
	$result = $DB->query($query);
	if($DB->numrows($result) >0) {
		while($line = $DB->fetch_assoc($result)) {
			$tmp['ID']= $line["ID"];
			$tmp['link']=$line[$field];
			$tab[]=$tmp;
		}
		
	}
	return $tab;

}

/** Get authors of tickets between 2 dates
* @param $date1 date : begin date
* @param $date2 date : end date
* @return array contains the distinct authors which have tickets
*/
function getNbIntervAuthor($date1,$date2){	
	global $DB;
	$query = "SELECT DISTINCT glpi_tracking.author as ID, glpi_users.name as name, glpi_users.realname as realname, glpi_users.firstname as firstname FROM glpi_tracking INNER JOIN glpi_users ON (glpi_users.ID=glpi_tracking.author)";
	$query.=getEntitiesRestrictRequest("WHERE","glpi_tracking");

   if (!empty($date1)||!empty($date2)) {
      $query .= " AND (".getDateRequest("glpi_tracking.`date`",$date1,$date2);
      $query .= " OR ".getDateRequest("glpi_tracking.`closedate`",$date1,$date2).") ";
   }

	$query.= " ORDER BY realname, firstname, name";
	$result = $DB->query($query);
	$tab=array();
	if($DB->numrows($result) >=1) {
		while($line = $DB->fetch_assoc($result)) {
			$tmp['ID']= $line["ID"];
			$tmp['link']=formatUserName($line["ID"],$line["name"],$line["realname"],$line["firstname"],1);
			$tab[]=$tmp;
		}
	}
	return $tab;

}

/** Get recipient of tickets between 2 dates
* @param $date1 date : begin date
* @param $date2 date : end date
* @param title : indicates if stat if by title (true) or type (false)
* @return array contains the distinct recipents which have tickets
*/
function getNbIntervTitleOrType($date1,$date2,$title=true){	
	global $DB;
	if ($title) {
		$table = "glpi_dropdown_user_titles";
		$field = "title";	
	}
		
	else
	{
		$table = "glpi_dropdown_user_types";
		$field = "type";
	}
	
	$query = "SELECT DISTINCT glpi_users.$field FROM glpi_tracking INNER JOIN glpi_users ON (glpi_users.ID=glpi_tracking.author)
		LEFT JOIN  $table ON ($table.ID=glpi_users.$field)";
	$query.=getEntitiesRestrictRequest("WHERE","glpi_tracking");
   if (!empty($date1)||!empty($date2)) {
      $query .= " AND (".getDateRequest("glpi_tracking.`date`",$date1,$date2);
      $query .= " OR ".getDateRequest("glpi_tracking.`closedate`",$date1,$date2).") ";
   }
	$query.=" ORDER BY glpi_users.$field";
	
	$result = $DB->query($query);
	$tab=array();
	if($DB->numrows($result) >=1) {
		while($line = $DB->fetch_assoc($result)) {
			$tmp['ID']= $line[$field];
			$tmp['link']=getDropdownName($table,$line[$field]);
			$tab[]=$tmp;
		}
	}
	return $tab;

}

/** Get recipient of tickets between 2 dates
* @param $date1 date : begin date
* @param $date2 date : end date
* @return array contains the distinct recipents which have tickets
*/
function getNbIntervRecipient($date1,$date2){	
	global $DB;
	$query = "SELECT DISTINCT glpi_tracking.recipient as ID, glpi_users.name as name, glpi_users.realname as realname, glpi_users.firstname as firstname FROM glpi_tracking LEFT JOIN glpi_users ON (glpi_users.ID=glpi_tracking.recipient)";
	$query.=getEntitiesRestrictRequest("WHERE","glpi_tracking");
   if (!empty($date1)||!empty($date2)) {
      $query .= " AND (".getDateRequest("glpi_tracking.`date`",$date1,$date2);
      $query .= " OR ".getDateRequest("glpi_tracking.`closedate`",$date1,$date2).") ";
   }
	$query.= " ORDER BY realname, firstname, name";
	$result = $DB->query($query);
	$tab=array();
	if($DB->numrows($result) >=1) {
		while($line = $DB->fetch_assoc($result)) {
			$tmp['ID']= $line["ID"];
			$tmp['link']=formatUserName($line["ID"],$line["name"],$line["realname"],$line["firstname"],1);
			$tab[]=$tmp;
		}
	}
	return $tab;

}

/** Get priorities of tickets between 2 dates
* @param $date1 date : begin date
* @param $date2 date : end date
* @return array contains the distinct priorities of tickets
*/
function getNbIntervPriority($date1,$date2){	
	global $DB;

	$query = "SELECT DISTINCT priority 
		FROM glpi_tracking ".getEntitiesRestrictRequest("WHERE","glpi_tracking");
   if (!empty($date1)||!empty($date2)) {
      $query .= " AND (".getDateRequest("glpi_tracking.`date`",$date1,$date2);
      $query .= " OR ".getDateRequest("glpi_tracking.`closedate`",$date1,$date2).") ";
   }
	$query.=" ORDER BY priority";
	
	$result = $DB->query($query);
	$tab=array();
	if($DB->numrows($result) >=1) {
		$i = 0;
		while($line = $DB->fetch_assoc($result)) {
			$tmp['ID']= $line["priority"];
			$tmp['link']=getPriorityName($line["priority"]);
			$tab[]=$tmp;
		}
	}
	return $tab;	

}

/** Get request types of tickets between 2 dates
* @param $date1 date : begin date
* @param $date2 date : end date
* @return array contains the distinct request types of tickets
*/
function getNbIntervRequestType($date1,$date2){	
	global $DB;
	$query = "SELECT DISTINCT request_type 
		FROM glpi_tracking ".getEntitiesRestrictRequest("WHERE","glpi_tracking");
   if (!empty($date1)||!empty($date2)) {
      $query .= " AND (".getDateRequest("glpi_tracking.`date`",$date1,$date2);
      $query .= " OR ".getDateRequest("glpi_tracking.`closedate`",$date1,$date2).") ";
   }
	$query.=" ORDER BY request_type";
	$result = $DB->query($query);
	$tab=array();
	if($DB->numrows($result) >=1) {
		while($line = $DB->fetch_assoc($result)) {
			$tmp['ID']= $line["request_type"];
			$tmp['link']=getRequestTypeName($line["request_type"]);
			$tab[]=$tmp;
		}
	}

	return $tab;
}

/** Get categories of tickets between 2 dates
* @param $date1 date : begin date
* @param $date2 date : end date
* @return array contains the distinct categories of tickets
*/
function getNbIntervCategory($date1,$date2){	
	global $DB;
	
	// Get all tracking categories for tree merge management

	$query="SELECT DISTINCT glpi_dropdown_tracking_category.ID,   glpi_dropdown_tracking_category.completename AS category
		FROM glpi_dropdown_tracking_category ";

/*	$query = "SELECT DISTINCT glpi_dropdown_tracking_category.ID,   glpi_dropdown_tracking_category.completename AS category
		FROM glpi_tracking 
		LEFT JOIN glpi_dropdown_tracking_category ON (glpi_tracking.category = glpi_dropdown_tracking_category.ID) ";
	$query.=getEntitiesRestrictRequest(" WHERE","glpi_tracking");
	if (!empty($date1)) $query.= " AND glpi_tracking.date >= '". $date1 ."' ";
	if (!empty($date2)) $query.= " AND glpi_tracking.date <= adddate( '". $date2 ."' , INTERVAL 1 DAY ) ";
*/		
	$query.=" ORDER BY glpi_dropdown_tracking_category.completename";

	$result = $DB->query($query);
	$tab=array();
	if($DB->numrows($result) >=1) {
		while($line = $DB->fetch_assoc($result)) {
			$tmp['ID']= $line["ID"];
			$tmp['link']=$line["category"];
			$tab[]=$tmp;
		}
	}
	return $tab;
}


/** Get groups which have tickets between 2 dates
* @param $date1 date : begin date
* @param $date2 date : end date
* @return array contains the distinct groups of tickets
*/
function getNbIntervGroup($date1,$date2){	
	global $DB;
	$query = "SELECT DISTINCT glpi_groups.id AS ID,glpi_groups.name FROM glpi_tracking 
		LEFT JOIN  glpi_groups ON (glpi_tracking.FK_group = glpi_groups.ID)";
	$query.=getEntitiesRestrictRequest(" WHERE","glpi_tracking");

   if (!empty($date1)||!empty($date2)) {
      $query .= " AND (".getDateRequest("glpi_tracking.`date`",$date1,$date2);
      $query .= " OR ".getDateRequest("glpi_tracking.`closedate`",$date1,$date2).") ";
   }
	$query.=" ORDER BY glpi_groups.name";
	
	$result = $DB->query($query);
	$tab=array();

	if($DB->numrows($result) >=1) {
		while($line = $DB->fetch_assoc($result)) {
			$tmp['ID']= $line["ID"];
			$tmp['link']=$line["name"];
			
			$tab[]=$tmp;
		}
	}
	return $tab;
}


/** Get groups assigned to tickets between 2 dates
* @param $date1 date : begin date
* @param $date2 date : end date
* @return array contains the distinct groups assigned to a tickets
*/
function getNbIntervAssignGroup($date1,$date2){	
	global $DB;
	$query = "SELECT DISTINCT glpi_groups.id AS ID,glpi_groups.name FROM glpi_tracking 
		LEFT JOIN  glpi_groups ON (glpi_tracking.assign_group = glpi_groups.ID)";
	$query.=getEntitiesRestrictRequest(" WHERE","glpi_tracking");
   if (!empty($date1)||!empty($date2)) {
      $query .= " AND (".getDateRequest("glpi_tracking.`date`",$date1,$date2);
      $query .= " OR ".getDateRequest("glpi_tracking.`closedate`",$date1,$date2).") ";
   }
	$query.=" ORDER BY glpi_groups.name";
	
	$result = $DB->query($query);
	$tab=array();

	if($DB->numrows($result) >=1) {
		while($line = $DB->fetch_assoc($result)) {
			$tmp['ID']= $line["ID"];
			$tmp['link']=$line["name"];
			
			$tab[]=$tmp;
		}
	}
	return $tab;
}

function constructEntryValues($type,$begin="",$end="",$param="",$value="",$value2=""){
	global $DB;

	$query="";

	$WHERE=getEntitiesRestrictRequest("WHERE","glpi_tracking");
	if (empty($WHERE)){
		$WHERE=" WHERE 1 ";
	}
	$LEFTJOIN="";
	switch ($param){

		case "technicien":
			$WHERE.=" AND glpi_tracking.assign='$value'";
		break;
		case "technicien_followup":
			$WHERE.=" AND glpi_followups.author='$value'";
			$LEFTJOIN= "LEFT JOIN glpi_followups ON (glpi_followups.tracking = glpi_tracking.ID)";
		break;	
		case "enterprise":
			$WHERE.=" AND glpi_tracking.assign_ent='$value'";
		break;
		case "user":
			$WHERE.=" AND glpi_tracking.author='$value'";
		break;
		case "recipient":
			$WHERE.=" AND glpi_tracking.recipient='$value'";
		break;
		case "category":
			if (!empty($value)){
				$categories=getSonsOfTreeItem("glpi_dropdown_tracking_category",$value);
				$condition=implode("','",$categories);
	
				$WHERE.=" AND glpi_tracking.category IN ('$condition')";
			} else {
				$WHERE.=" AND glpi_tracking.category = '$value' ";
			}
		break;
		case "group":
			$WHERE.=" AND glpi_tracking.FK_group='$value'";
		break;
		case "assign_group":
			$WHERE.=" AND glpi_tracking.assign_group='$value'";
		break;
		case "priority":
			$WHERE.=" AND glpi_tracking.priority='$value'";
		break;
		case "request_type":
			$WHERE.=" AND glpi_tracking.request_type='$value'";
		break;
		case "title":
			$LEFTJOIN= "INNER JOIN glpi_users ON (glpi_users.ID = glpi_tracking.author AND glpi_users.title=$value)";
		break;
		case "type":
			$LEFTJOIN= "INNER JOIN glpi_users ON (glpi_users.ID = glpi_tracking.author AND glpi_users.type=$value)";
		break;

		case "device":
			//select computers IDs that are using this device;

			$LEFTJOIN= "INNER JOIN glpi_computers ON (glpi_computers.ID = glpi_tracking.computer AND glpi_tracking.device_type='".COMPUTER_TYPE."') INNER JOIN glpi_computer_device ON ( glpi_computers.ID = glpi_computer_device.FK_computers AND glpi_computer_device.device_type = '".$value2."' AND glpi_computer_device.FK_device = '".$value."' )";

			$WHERE.=" AND glpi_computers.is_template <> '1' ";

		break;
		case "comp_champ":
			$LEFTJOIN= "INNER JOIN glpi_computers ON (glpi_computers.ID = glpi_tracking.computer AND glpi_tracking.device_type='".COMPUTER_TYPE."')";

			$WHERE.=" AND glpi_computers.$value2='$value' AND glpi_computers.is_template <> '1'";
		break;
	}
	switch($type)	{

		case "inter_total": 
			if (!empty($begin)) $WHERE.= " AND glpi_tracking.date >= '$begin' ";
			if (!empty($end)) $WHERE.= " AND glpi_tracking.date <= adddate('$end' , INTERVAL 1 DAY )";

			$query="SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(glpi_tracking.date),'%Y-%m') AS date_unix, COUNT(glpi_tracking.ID) AS total_visites  FROM glpi_tracking ".$LEFTJOIN.$WHERE.
			" GROUP BY date_unix ORDER BY glpi_tracking.date";
		break;
		case "inter_solved": 
			$WHERE.=" AND ( glpi_tracking.status = 'old_done' OR glpi_tracking.status = 'old_notdone') AND glpi_tracking.closedate IS NOT NULL ";
			if (!empty($begin)) $WHERE.= " AND glpi_tracking.closedate >= '$begin' ";
			if (!empty($end)) $WHERE.= " AND glpi_tracking.closedate <= adddate('$end' , INTERVAL 1 DAY ) ";

			$query="SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(glpi_tracking.closedate),'%Y-%m') AS date_unix, COUNT(glpi_tracking.ID) AS total_visites  FROM glpi_tracking ".$LEFTJOIN.$WHERE.
			" GROUP BY date_unix ORDER BY glpi_tracking.closedate";
		break;
		case "inter_avgsolvedtime" :
			$WHERE.=" AND ( glpi_tracking.status = 'old_done' OR glpi_tracking.status = 'old_notdone') AND glpi_tracking.closedate IS NOT NULL ";
		if (!empty($begin)) $WHERE.= " AND glpi_tracking.closedate >= '$begin' ";
		if (!empty($end)) $WHERE.= " AND glpi_tracking.closedate <= adddate('$end' , INTERVAL 1 DAY ) ";

		$query="SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(glpi_tracking.closedate),'%Y-%m') AS date_unix,
 			AVG(TIME_TO_SEC( TIMEDIFF(glpi_tracking.closedate,glpi_tracking.date))/".HOUR_TIMESTAMP.") AS total_visites  FROM glpi_tracking ".
			$LEFTJOIN.$WHERE.
			" GROUP BY date_unix ORDER BY glpi_tracking.closedate";
		break;
		case "inter_avgrealtime" :
			if ($param=="technicien_followup")
				$realtime_table="glpi_followups";
			else $realtime_table="glpi_tracking";
			$WHERE.=" AND $realtime_table.realtime > '0' ";
			if (!empty($begin)) $WHERE.= " AND glpi_tracking.closedate >= '$begin' ";
			if (!empty($end)) $WHERE.= " AND glpi_tracking.closedate <= adddate('$end' , INTERVAL 1 DAY ) ";

			$query="SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(glpi_tracking.closedate),'%Y-%m') AS date_unix, ".MINUTE_TIMESTAMP."*AVG($realtime_table.realtime) AS total_visites  FROM glpi_tracking ".
				$LEFTJOIN.$WHERE.
				" GROUP BY date_unix ORDER BY glpi_tracking.closedate";
			break;
		case "inter_avgtakeaccount" :
			$WHERE.=" AND ( glpi_tracking.status = 'old_done' OR glpi_tracking.status = 'old_notdone') AND glpi_tracking.closedate IS NOT NULL ";
			if (!empty($begin)) $WHERE.= " AND glpi_tracking.closedate >= '$begin' ";
			if (!empty($end)) $WHERE.= " AND glpi_tracking.closedate <= adddate('$end' , INTERVAL 1 DAY ) ";

			$query="SELECT glpi_tracking.ID AS ID, FROM_UNIXTIME(UNIX_TIMESTAMP(glpi_tracking.closedate),'%Y-%m') AS date_unix, MIN(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date)) AS OPEN, MIN(UNIX_TIMESTAMP(glpi_followups.date)-UNIX_TIMESTAMP(glpi_tracking.date)) AS FIRST FROM glpi_tracking LEFT JOIN glpi_followups ON (glpi_followups.tracking = glpi_tracking.ID) ";
			if (!strstr($LEFTJOIN,"glpi_followups")){
				$query.=$LEFTJOIN;
			}
				$query.=$WHERE." GROUP BY glpi_tracking.ID";
			break;


	}

	$entrees=array();
	$count=array();
	if (empty($query)) return array();
	
	$result=$DB->query($query);
	if ($result&&$DB->numrows($result)>0)
		while ($row = $DB->fetch_array($result)) {
			$date = $row['date_unix'];
			if ($type=="inter_avgtakeaccount"){
				$min=$row["OPEN"];	
				if (!empty($row["FIRST"])&&!is_null($row["FIRST"])&&$row["FIRST"]<$min) $min=$row["FIRST"];
				if (!isset($entrees["$date"])) {$entrees["$date"]=$min; $count["$date"]=1;}
				else {$entrees["$date"]+=$min;$count["$date"]++;}
			} else {
				$visites = round($row['total_visites']);
				$entrees["$date"] = $visites;
			}
		}



	if ($type=="inter_avgtakeaccount"){

		foreach ($entrees as $key => $val){
			$entrees[$key]=round($entrees[$key]/$count[$key]/HOUR_TIMESTAMP);

		}
	}

	// Remplissage de $entrees pour les mois ou il n'y a rien

	$min=-1;		
	$max=0;		
	if (count($entrees)==0) return $entrees;

	foreach ($entrees as $key => $val){
		$time=strtotime($key."-01");
		if ($min>$time||$min<0) $min=$time;
		if ($max<$time) $max=$time;
	}

	$end_time=strtotime(date("Y-m",strtotime($end))."-01");
	$begin_time=strtotime(date("Y-m",strtotime($begin))."-01");

	if ($max<$end_time) $max=$end_time;
	if ($min>$begin_time) $min=$begin_time;
	$current=$min;
	
	while ($current<=$max){
		$curentry=date("Y-m",$current);
		if (!isset($entrees["$curentry"])) $entrees["$curentry"]=0;
		$month=date("m",$current);
		$year=date("Y",$current);

		$current=mktime(0,0,0,intval($month)+1,1,intval($year));
	}
	
	return $entrees;
}



/** Get groups assigned to tickets between 2 dates
* BASED ON SPIP DISPLAY GRAPH : www.spip.net
* @param $type string : "month" or "year"
* @param $entrees array : array containing data to displayed
* @param $titre string : title 
* @param $unit string : unit 
* @param $showtotal boolean : also show total values ?
* @return array contains the distinct groups assigned to a tickets
*/
function graphBy($entrees,$titre="",$unit="",$showtotal=1,$type="month"){

	global $DB,$CFG_GLPI,$LANG;
	ksort($entrees);
	$total="";
	if ($showtotal==1) $total=array_sum($entrees);

	echo "<p align='center'>";
	echo "<font face='verdana,arial,helvetica,sans-serif' size='2'><strong>$titre - $total $unit</strong></font>";

	echo "<div class='center'>";

	if (count($entrees)>0){

		$max = max($entrees);
		$maxgraph = substr(ceil(substr($max,0,2) / 10)."000000000000", 0, strlen($max));

		if ($maxgraph < 10) $maxgraph = 10;
		if (1.1 * $maxgraph < $max) $maxgraph.="0";	
		if (0.8*$maxgraph > $max) $maxgraph = 0.8 * $maxgraph;
		$rapport = 200 / $maxgraph;

		$largeur = floor(420 / (count($entrees)));
		if ($largeur < 1) $largeur = 1;
		if ($largeur > 50) $largeur = 50;
	}

	echo "<table cellpadding='0' cellspacing='0' border='0' class='tab_glpi'><tr><td style='background-image:url(".$CFG_GLPI["root_doc"]."/pics/fond-stats.gif)' >";
	echo "<table cellpadding='0' cellspacing='0' border='0'><tr>";
	echo "<td bgcolor='black'><img src='".$CFG_GLPI["root_doc"]."/pics/noir.png' width='1' height='200' alt=''></td>";

	// Presentation graphique
	$n = 0;
	$decal = 0;
	$tab_moyenne = "";
	$total_loc=0;
	while (list($key, $value) = each($entrees)) {
		$n++;

		if ($decal == 30) $decal = 0;
		$decal ++;
		$tab_moyenne[$decal] = $value;

		$total_loc = $total_loc + $value;
		reset($tab_moyenne);

		$moyenne = 0;
		while (list(,$val_tab) = each($tab_moyenne))
			$moyenne += $val_tab;
		$moyenne = $moyenne / count($tab_moyenne);

		$hauteur_moyenne = round($moyenne * $rapport) ;
		$hauteur = round($value * $rapport)	;
		echo "<td valign='bottom' width=".$largeur.">";

		if ($hauteur >= 0){
			if ($hauteur_moyenne > $hauteur) {
				$difference = ($hauteur_moyenne - $hauteur) -1;
				echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$CFG_GLPI["root_doc"]."/pics/moyenne.png' width=".$largeur." height='1' >";
				echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$CFG_GLPI["root_doc"]."/pics/rien.gif' width=".$largeur." height=".$difference." >";
				echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$CFG_GLPI["root_doc"]."/pics/noir.png' width=".$largeur." height='1' >";
				if (strstr($key,"-01")){ // janvier en couleur foncee
					echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$CFG_GLPI["root_doc"]."/pics/fondgraph1.png' width=".$largeur." height=".$hauteur." >";
				} 
				else {
					echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$CFG_GLPI["root_doc"]."/pics/fondgraph2.png' width=".$largeur." height=".$hauteur." >";
				}
			}
			else if ($hauteur_moyenne < $hauteur) {
				$difference = ($hauteur - $hauteur_moyenne) -1;
				echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$CFG_GLPI["root_doc"]."/pics/noir.png' width=".$largeur." height='1'>";
				if (strstr($key,"-01")){ // janvier en couleur foncee
					$couleur =  "1";
					$couleur2 =  "2";
				} 
				else {
					$couleur = "2";
					$couleur2 = "1";
				}
				echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$CFG_GLPI["root_doc"]."/pics/fondgraph$couleur.png' width=".$largeur." height=".$difference.">";
				echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$CFG_GLPI["root_doc"]."/pics/moyenne.png' width=".$largeur." height='1'>";
				echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$CFG_GLPI["root_doc"]."/pics/fondgraph$couleur.png' width=".$largeur." height=".$hauteur_moyenne.">";
			}
			else {
				echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$CFG_GLPI["root_doc"]."/pics/noir.png' width=".$largeur." height='1'>";
				if (strstr($key,"-01")){ // janvier en couleur foncee
					echo "<img alt=\"$key: $val_tab\" title=\"$key: $value\" src='".$CFG_GLPI["root_doc"]."/pics/fondgraph1.png' width=".$largeur." height=".$hauteur.">";
				} 
				else {
					echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$CFG_GLPI["root_doc"]."/pics/fondgraph2.png' width=".$largeur." height=".$hauteur.">";
				}
			}
		}

		echo "<img alt=\"$value\" title=\"$value\"  src='".$CFG_GLPI["root_doc"]."/pics/rien.gif' width=".$largeur." height='1'>";
		echo "</td>\n";

	}
	echo "<td bgcolor='black'><img src='".$CFG_GLPI["root_doc"]."/pics/noir.png' width='1' height='1' alt=''></td>";
	echo "</tr>";
	if ($largeur>10){
		echo "<tr><td></td>";
		foreach ($entrees as $key => $val){
			if ($type=="month"){
				$splitter=explode("-",$key);
				echo "<td class='center'>".utf8_substr($LANG['calendarM'][$splitter[1]-1],0,3)."</td>";
			} else if ($type=="year"){
				echo "<td class='center'>".substr($key,2,2)."</td>";
			}
		}
		echo "</tr>";
	}

	if ($maxgraph<=10) $r=2;
	else if ($maxgraph<=100) $r=1;
	else $r=0;
	echo "</table>";
	echo "</td>";
	echo "<td style='background-image:url(".$CFG_GLPI["root_doc"]."/pics/fond-stats.gif)' valign='bottom'><img src='".$CFG_GLPI["root_doc"]."/pics/rien.gif' style='background-color:black;' width='3' height='1' alt=''></td>";
	echo "<td><img src='".$CFG_GLPI["root_doc"]."/pics/rien.gif' width='5' height='1' alt=''></td>";
	echo "<td valign='top'>";
	echo "<table cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr><td height='15' valign='top'>";		
	echo "<font face='arial,helvetica,sans-serif' size='1'><strong>".formatNumber($maxgraph,false,$r)."</strong></font>";
	echo "</td></tr>";
	echo "<tr><td height='25' valign='middle'>";		
	echo "<font face='arial,helvetica,sans-serif' size='1' color='#999999'>".formatNumber(7*($maxgraph/8),false,$r)."</font>";
	echo "</td></tr>";
	echo "<tr><td height='25' valign='middle'>";		
	echo "<font face='arial,helvetica,sans-serif' size='1'>".formatNumber(3*($maxgraph/4),false,$r)."</font>";
	echo "</td></tr>";
	echo "<tr><td height='25' valign='middle'>";		
	echo "<font face='arial,helvetica,sans-serif' size='1' color='#999999'>".formatNumber(5*($maxgraph/8),false,$r)."</font>";
	echo "</td></tr>";
	echo "<tr><td height='25' valign='middle'>";		
	echo "<font face='arial,helvetica,sans-serif' size='1'><strong>".formatNumber($maxgraph/2,false,$r)."</strong></font>";
	echo "</td></tr>";
	echo "<tr><td height='25' valign='middle'>";		
	echo "<font face='arial,helvetica,sans-serif' size='1' color='#999999'>".formatNumber(3*($maxgraph/8),false,$r)."</font>";
	echo "</td></tr>";
	echo "<tr><td height='25' valign='middle'>";		
	echo "<font face='arial,helvetica,sans-serif' size='1'>".formatNumber($maxgraph/4,false,$r)."</font>";
	echo "</td></tr>";
	echo "<tr><td height='25' valign='middle'>";		
	echo "<font face='arial,helvetica,sans-serif' size='1' color='#999999'>".formatNumber(1*($maxgraph/8),false,$r)."</font>";
	echo "</td></tr>";
	echo "<tr><td height='10' valign='bottom'>";		
	echo "<font face='arial,helvetica,sans-serif' size='1'><strong>0</strong></font>";
	echo "</td>";

	echo "</tr></table>";
	echo "</td></tr></table>";
	echo "</div>";



}


function showItemStats($target,$date1,$date2,$start){
	global $DB,$CFG_GLPI,$LANG;


	$view_entities=isMultiEntitiesMode();

	if ($view_entities){
		$entities=getAllDatasFromTable('glpi_entities');
	}

	$output_type=HTML_OUTPUT;
	if (isset($_GET["display_type"]))
		$output_type=$_GET["display_type"];

	if (empty($date2)) $date2=date("Y-m-d");
	$date2.=" 23:59:59";
	// 1 an par defaut
	if (empty($date1)) $date1=date("Y-m-d",mktime(0,0,0,date("m"),date("d"),date("Y")-1));
	$date1.=" 00:00:00";


	$query="SELECT device_type,computer,COUNT(*) AS NB 
		FROM glpi_tracking 
		WHERE date<= '".$date2."' AND date>= '".$date1."' ".getEntitiesRestrictRequest("AND","glpi_tracking")." 
		GROUP BY device_type,computer ORDER BY NB DESC";
	
	$result=$DB->query($query);
	$numrows=$DB->numrows($result);

	if ($numrows>0){
		if ($output_type==HTML_OUTPUT){
			printPager($start,$numrows,$target,"date1=".$date1."&amp;date2=".$date2."&amp;type=hardwares&amp;start=$start",STAT_TYPE);
			echo "<div class='center'>";
		}

		$i=$start;
		if (isset($_GET['export_all']))
			$i=0;

		$end_display=$start+$_SESSION['glpilist_limit'];
		if (isset($_GET['export_all']))
			$end_display=$numrows;
		echo displaySearchHeader($output_type,$end_display-$start+1,2,1);
		$header_num=1;
		echo displaySearchNewLine($output_type);
		echo displaySearchHeaderItem($output_type,$LANG['common'][1],$header_num);
		if ($view_entities){
			echo displaySearchHeaderItem($output_type,$LANG['entity'][0],$header_num);
		}
		echo displaySearchHeaderItem($output_type,$LANG['stats'][13],$header_num);
		echo displaySearchEndLine($output_type);

		$DB->data_seek($result,$start);

		$ci=new CommonItem();
		while ($i < $numrows && $i<($end_display)){
			$item_num=1;
			// Get data and increment loop variables
			$data=$DB->fetch_assoc($result);
			if ($ci->getFromDB($data["device_type"],$data["computer"])){
				//echo "<tr class='tab_bg_2$del'><td>".$ci->getLink()."</td><td>".$data["NB"]."</td></tr>";
				echo displaySearchNewLine($output_type,$i%2);
				echo displaySearchItem($output_type,$ci->getType()." - ".$ci->getLink(),$item_num,$i-$start+1,"align='center'"." ".($ci->getField("deleted")?" class='deleted' ":""));
				if ($view_entities){
					$ent=$ci->getField('FK_entities');
					if ($ent==0){
						$ent=$LANG['entity'][2];
					} else {
						$ent=$entities[$ent]['completename'];
					}
					echo displaySearchItem($output_type,$ent,$item_num,$i-$start+1,"align='center'"." ".($ci->getField("deleted")?" class='deleted' ":""));
				}	
				echo displaySearchItem($output_type,$data["NB"],$item_num,$i-$start+1,"align='center'"." ".($ci->getField("deleted")?" class='deleted' ":""));
			}
			$i++;
		}

		echo displaySearchFooter($output_type);
		if ($output_type==HTML_OUTPUT)
			echo "</div>";
	}

}

?>
