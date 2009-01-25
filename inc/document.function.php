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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------


if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}




/**
 * Move an uploadd document (files in GLPI_DOC_DIR."/_uploads" dir)
 *
 * @param $filename filename to move
 * @param $old_file old file name to replace : to unlink it
 * @return nothing
 **/
function moveUploadedDocument($filename,$old_file=''){
	global $CFG_GLPI,$LANG;

	if (is_dir(GLPI_DOC_DIR."/_uploads")){
		if (is_file(GLPI_DOC_DIR."/_uploads/".$filename)){
			$dir=isValidDoc($filename);
			$new_path=getUploadFileValidLocationName($dir,$filename,0);
			if (!empty($new_path)){

				// Delete old file
				if(!empty($old_file)&& is_file(GLPI_DOC_DIR."/".$old_file)&& !is_dir(GLPI_DOC_DIR."/".$old_file)) {
					if (unlink(GLPI_DOC_DIR."/".$old_file))
						addMessageAfterRedirect($LANG["document"][24]." ".GLPI_DOC_DIR."/".$old_file);
					else 
						addMessageAfterRedirect($LANG["document"][25]." ".GLPI_DOC_DIR."/".$old_file);
				}

				// D�lacement si droit
				if (is_writable (GLPI_DOC_DIR."/_uploads/".$filename)){
					if (rename(GLPI_DOC_DIR."/_uploads/".$filename,GLPI_DOC_DIR."/".$new_path)){
						addMessageAfterRedirect($LANG["document"][39]);
						return $new_path;
					}
					else {
						addMessageAfterRedirect($LANG["document"][40]);
					}
				} else { // Copi sinon
					if (copy(GLPI_DOC_DIR."/_uploads/".$filename,GLPI_DOC_DIR."/".$new_path)){
						addMessageAfterRedirect($LANG["document"][41]);
						return $new_path;
					}
					else addMessageAfterRedirect($LANG["document"][40]);
				}
			}

		} else addMessageAfterRedirect($LANG["document"][38].": ".GLPI_DOC_DIR."/_uploads/".$filename);

	} else addMessageAfterRedirect($LANG["document"][35]);

	return "";	
}

/**
 * Upload a new file
 *
 * @param $FILEDESC FILE descriptor
 * @param $old_file old file name to replace : to unlink it
 * @return nothing
 **/
function uploadDocument($FILEDESC,$old_file=''){
	global $CFG_GLPI,$LANG;

	// Is a file uploaded ?
	if (count($FILEDESC)>0&&!empty($FILEDESC['name'])){
		// Clean is name
		$filename=cleanFilenameDocument($FILEDESC['name']);
		$force=0;
		// Is it a valid file ?
		$dir=isValidDoc($filename);
		if (!empty($old_file)&&$dir."/".$filename==$old_file) $force=1;

		$new_path=getUploadFileValidLocationName($dir,$filename,$force);

		if (!empty($new_path)){
			// Delete old file
			if(!empty($old_file)&& is_file(GLPI_DOC_DIR."/".$old_file)&& !is_dir(GLPI_DOC_DIR."/".$old_file)) {
				if (unlink(GLPI_DOC_DIR."/".$old_file))
					addMessageAfterRedirect($LANG["document"][24]." ".GLPI_DOC_DIR."/".$old_file);
				else 
					addMessageAfterRedirect($LANG["document"][25]." ".GLPI_DOC_DIR."/".$old_file);
			}

			// Move uploaded file
			if (rename($FILEDESC['tmp_name'],GLPI_DOC_DIR."/".$new_path)) {
				addMessageAfterRedirect($LANG["document"][26]);
				return $new_path;
			} else {
				addMessageAfterRedirect($LANG["document"][27]);
			}
		}


	} 
	return "";	
}

/**
 * Find a valid path for the new file
 *
 * @param $dir dir to search a free path for the file
 * @param $filename new filename
 * @param $force may replace an existing doc ?
 * @return nothing
 **/
function getUploadFileValidLocationName($dir,$filename,$force){

	global $CFG_GLPI,$LANG;

	if (!empty($dir)){
		// Test existance repertoire DOCS
		if (is_dir(GLPI_DOC_DIR)){
			// Test existance sous-repertoire type dans DOCS -> sinon cr�tion
			if (!is_dir(GLPI_DOC_DIR."/".$dir)){
				addMessageAfterRedirect($LANG["document"][34]." ".GLPI_DOC_DIR."/".$dir);
				@mkdir(GLPI_DOC_DIR."/".$dir);
			}
			// Copy du fichier upload�si r�ertoire existe
			if (is_dir(GLPI_DOC_DIR."/".$dir)){
				if (!$force){
					if (is_file(GLPI_DOC_DIR."/".$dir."/".$filename)){
						$original_split=explode('.',$filename);
						$where_to_add=count($original_split)-2;
						$splitted=$original_split;
						$number=2;
						$splitted[$where_to_add]=preg_replace('/_[0-9]*$/','',$splitted[$where_to_add])."_".$number;
						$filename=implode('.',$splitted);
						// Rename file if exists
						while (is_file(GLPI_DOC_DIR."/".$dir."/".$filename)){
							$number++;
							$splitted=$original_split;
							
							$splitted[$where_to_add]=preg_replace('/_[0-9]*$/','',$splitted[$where_to_add])."_".$number;
							$filename=implode('.',$splitted);
						}
					}
				}
				if ($force||!is_file(GLPI_DOC_DIR."/".$dir."/".$filename)){
					return $dir."/".$filename;
				} else addMessageAfterRedirect($LANG["document"][28]);

			} else addMessageAfterRedirect($LANG["document"][29]." ".GLPI_DOC_DIR."/".$dir." ".$LANG["document"][30]);

		} else addMessageAfterRedirect($LANG["document"][31]." ".GLPI_DOC_DIR);

	} else addMessageAfterRedirect($LANG["document"][32]);

	return "";
}

/**
 * Show devices links to a document
 *
 * @param $instID document ID
 * @return nothing
 **/
function showDeviceDocument($instID) {
	global $DB,$CFG_GLPI, $LANG,$INFOFORM_PAGES,$LINK_ID_TABLE;

	if (!haveRight("document","r"))	return false;

	$doc=new Document();
	if ($doc->getFromDB($instID)){
		$canedit=$doc->can($instID,'w');

		// for a document,
		// don't show here others documents associated to this one,
		// it's done for both directions in showDocumentAssociated
		$query = "SELECT DISTINCT device_type FROM glpi_doc_device WHERE glpi_doc_device.FK_doc = '$instID' AND glpi_doc_device.device_type != ".DOCUMENT_TYPE." ORDER BY device_type";
		
		$result = $DB->query($query);
		$number = $DB->numrows($result);
		$i = 0;
	
		echo "<form method='post' name='document_form' id='document_form'  action=\"".$CFG_GLPI["root_doc"]."/front/document.form.php\">";
	
		echo "<br><br><div class='center'><table class='tab_cadre_fixe'>";
		echo "<tr><th colspan='".($canedit?6:5)."'>".$LANG["document"][19].":</th></tr><tr>";
		if ($canedit) {
			echo "<th>&nbsp;</th>";
		}
		echo "<th>".$LANG["common"][17]."</th>";
		echo "<th>".$LANG["common"][16]."</th>";
		echo "<th>".$LANG["entity"][0]."</th>";
		echo "<th>".$LANG["common"][19]."</th>";
		echo "<th>".$LANG["common"][20]."</th>";
		echo "</tr>";
		$ci=new CommonItem();
		while ($i < $number) {
			$type=$DB->result($result, $i, "device_type");
			if (haveTypeRight($type,"r")){
				$column="name";
				if ($type==TRACKING_TYPE) $column="ID";
				if ($type==KNOWBASE_TYPE) $column="question";
	
				$query = "SELECT ".$LINK_ID_TABLE[$type].".*, glpi_doc_device.ID AS IDD, glpi_entities.ID AS entity "
					." FROM glpi_doc_device, ".$LINK_ID_TABLE[$type]
					." LEFT JOIN glpi_entities ON (glpi_entities.ID=".$LINK_ID_TABLE[$type].".FK_entities) "
					." WHERE ".$LINK_ID_TABLE[$type].".ID = glpi_doc_device.FK_device  AND glpi_doc_device.device_type='$type' AND glpi_doc_device.FK_doc = '$instID' "
					. getEntitiesRestrictRequest(" AND ",$LINK_ID_TABLE[$type],'','',isset($CFG_GLPI["recursive_type"][$type])); 
				if (in_array($LINK_ID_TABLE[$type],$CFG_GLPI["template_tables"])){
					$query.=" AND ".$LINK_ID_TABLE[$type].".is_template='0'";
				}
				$query.=" ORDER BY glpi_entities.completename, ".$LINK_ID_TABLE[$type].".$column";
				
				if ($result_linked=$DB->query($query))
					if ($DB->numrows($result_linked)){
						$ci->setType($type);
						while ($data=$DB->fetch_assoc($result_linked)){
							$ID="";
							if ($type==TRACKING_TYPE) $data["name"]=$LANG["job"][38]." ".$data["ID"];
							if ($type==KNOWBASE_TYPE) $data["name"]=$data["question"];
							
							if($CFG_GLPI["view_ID"]||empty($data["name"])) $ID= " (".$data["ID"].")";
							$name= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$type]."?ID=".$data["ID"]."\">"
								.$data["name"]."$ID</a>";
	
							echo "<tr class='tab_bg_1'>";

							if ($canedit){
								echo "<td width='10'>";
								$sel="";
								if (isset($_GET["select"])&&$_GET["select"]=="all") $sel="checked";
								echo "<input type='checkbox' name='item[".$data["IDD"]."]' value='1' $sel>";
								echo "</td>";
							}
							echo "<td class='center'>".$ci->getType()."</td>";
							
							echo "<td ".(isset($data['deleted'])&&$data['deleted']?"class='tab_bg_2_2'":"").">".$name."</td>";
							echo "<td class='center'>".getDropdownName("glpi_entities",$data['entity'])."</td>";
							echo "<td class='center'>".(isset($data["serial"])? "".$data["serial"]."" :"-")."</td>";
							echo "<td class='center'>".(isset($data["otherserial"])? "".$data["otherserial"]."" :"-")."</td>";
							
							echo "</tr>";
						}
					}
			}
			$i++;
		}
	
		if ($canedit)	{
			echo "<tr class='tab_bg_1'><td colspan='4' class='center'>";
	
			echo "<input type='hidden' name='conID' value='$instID'>";
			echo "<input type='hidden' name='right' value='doc'>";
			$types=$CFG_GLPI["state_types"];
			$types[]=ENTERPRISE_TYPE;
			$types[]=CARTRIDGE_TYPE;
			$types[]=CONSUMABLE_TYPE;
			$types[]=CONTRACT_TYPE;

			dropdownAllItems("item",0,0,($doc->fields['recursive']?-1:$doc->fields['FK_entities']),$types);
			
			echo "</td>";
			echo "<td colspan='2' class='center'>";
			echo "<input type='submit' name='additem' value=\"".$LANG["buttons"][8]."\" class='submit'>";
			echo "</td></tr>";
			echo "</table></div>" ;
			
			echo "<div class='center'>";
			echo "<table width='950px'>";
			echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td><td class='center'><a onclick= \"if ( markAllRows('document_form') ) return false;\" href='".$_SERVER['PHP_SELF']."?ID=$instID&amp;select=all'>".$LANG["buttons"][18]."</a></td>";
		
			echo "<td>/</td><td class='center'><a onclick= \"if ( unMarkAllRows('document_form') ) return false;\" href='".$_SERVER['PHP_SELF']."?ID=$instID&amp;select=none'>".$LANG["buttons"][19]."</a>";
			echo "</td><td align='left' width='80%'>";
			echo "<input type='submit' name='deleteitem' value=\"".$LANG["buttons"][6]."\" class='submit'>";
			echo "</td>";
			echo "</table>";
		
			echo "</div>";


		}else{
	
			echo "</table></div>"    ;
		}
		echo "</form>";
	}

}

/**
 * Add a document to an item
 *
 * @param $docID document ID
 * @param $ID item ID
 * @param $type item type
 **/
function addDeviceDocument($docID,$type,$ID){
	global $DB;
	if ($docID>0&&$ID>0&&$type>0){
		// Do not insert auto link for document
		if ($type==DOCUMENT_TYPE && $ID == $docID){
			return;
		}
		$query="INSERT INTO glpi_doc_device (FK_doc,FK_device, device_type) VALUES ('$docID','$ID','$type');";
		$result = $DB->query($query);
	}
}
/**
 * Delete a document to an item
 *
 * @param $ID doc_device ID
 **/
function deleteDeviceDocument($ID){

	global $DB;
	$query="DELETE FROM glpi_doc_device WHERE ID= '$ID';";
	$result = $DB->query($query);
}

/**
 * Show documents associated to an item
 *
 * @param $device_type item type
 * @param $ID item ID
 * @param $withtemplate if 3 -> view via helpdesk -> no links
 **/
function showDocumentAssociated($device_type,$ID,$withtemplate=''){

	global $DB, $CFG_GLPI, $LANG, $LINK_ID_TABLE;

	if ($device_type!=KNOWBASE_TYPE)
		if (!haveRight("document","r")||!haveTypeRight($device_type,"r"))	return false;

	if (empty($withtemplate)) $withtemplate=0;

	$ci=new CommonItem();
	$ci->getFromDB($device_type,$ID);
	$canread=$ci->obj->can($ID,'r');
	$canedit=$ci->obj->can($ID,'w');

	$needed_fields=array('ID','name','filename','mime','rubrique','link','deleted','FK_entities','recursive');


	$query = "SELECT glpi_doc_device.ID AS assocID, glpi_entities.ID AS entity, 
			glpi_docs.name AS assocName, glpi_docs.* FROM glpi_doc_device
			LEFT JOIN glpi_docs ON (glpi_doc_device.FK_doc=glpi_docs.ID) 
			LEFT JOIN glpi_entities ON (glpi_docs.FK_entities=glpi_entities.ID)
			WHERE glpi_doc_device.FK_device = '$ID' AND glpi_doc_device.device_type = '$device_type' ";

	if (isset($_SESSION["glpiID"])){
		$query .= getEntitiesRestrictRequest(" AND","glpi_docs",'','',true);
	} else {
		// Anonymous access from FAQ
		$query .= " AND glpi_docs.FK_entities=0 ";
	}
	
	// Document : search links in both order using union
	if ($device_type==DOCUMENT_TYPE){
		$query .= "UNION 
			SELECT glpi_doc_device.ID as assocID, glpi_entities.ID AS entity, 
				glpi_docs.name AS assocName, glpi_docs.* FROM glpi_doc_device
				LEFT JOIN glpi_docs ON (glpi_doc_device.FK_device=glpi_docs.ID)
				LEFT JOIN glpi_entities ON (glpi_docs.FK_entities=glpi_entities.ID)
				WHERE glpi_doc_device.FK_doc = '$ID' AND glpi_doc_device.device_type = '$device_type' ";
		if (isset($_SESSION["glpiID"])){
			$query .= getEntitiesRestrictRequest(" AND","glpi_docs",'','',true);
		} else {
			// Anonymous access from FAQ
			$query .= " AND glpi_docs.FK_entities=0 ";
		}
	} 
	$query .= " ORDER BY assocName";
		
	//echo $query;
	
	$result = $DB->query($query);
	$number = $DB->numrows($result);
	$i = 0;

	if ($withtemplate!=2) {
		echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/document.form.php\" enctype=\"multipart/form-data\">";
	}
	echo "<br><br><div class='center'><table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='7'>".$LANG["document"][21].":</th></tr>";
	echo "<tr><th>".$LANG["common"][16]."</th>";
	echo "<th>".$LANG["entity"][0]."</th>";
	echo "<th width='100px'>".$LANG["document"][2]."</th>";
	echo "<th>".$LANG["document"][33]."</th>";
	echo "<th>".$LANG["document"][3]."</th>";
	echo "<th>".$LANG["document"][4]."</th>";
	if ($withtemplate<2)echo "<th>&nbsp;</th>";
	echo "</tr>";
	$used=array();
	if ($number){
		while ($data=$DB->fetch_assoc($result)) {
			$docID=$data["ID"];
			$used[$docID]=$docID;
			$assocID=$data["assocID"];
	
			echo "<tr class='tab_bg_1".($data["deleted"]?"_2":"")."'>";
			if ($withtemplate!=3 && $canread 
				&& (in_array($data['FK_entities'],$_SESSION['glpiactiveentities']) || $data["recursive"])
			){
				echo "<td class='center'><a href='".$CFG_GLPI["root_doc"]."/front/document.form.php?ID=$docID'><strong>".$data["name"];
				if ($CFG_GLPI["view_ID"]) echo " (".$docID.")";
				echo "</strong></a></td>";
			} else {
				echo "<td class='center'><strong>".$data["name"];
				if ($CFG_GLPI["view_ID"]) echo " (".$docID.")";
				echo "</strong></td>";
			}
			echo "<td class='center'>".getDropdownName("glpi_entities",$data['entity'])."</td>";
			
			echo "<td align='center'  width='100px'>".getDocumentLink($data["filename"])."</td>";
	
			echo "<td class='center'>";
			if (!empty($data["link"]))
				echo "<a target=_blank href='".$data["link"]."'>".$data["link"]."</a>";
			else echo "&nbsp;";
			echo "</td>";
			echo "<td class='center'>".getDropdownName("glpi_dropdown_rubdocs",$data["rubrique"])."</td>";
			echo "<td class='center'>".$data["mime"]."</td>";
	
			if ($withtemplate<2) {
				echo "<td align='center' class='tab_bg_2'>";
				if ($canedit)
					echo "<a href='".$CFG_GLPI["root_doc"]."/front/document.form.php?deleteitem=deleteitem&amp;ID=$assocID&amp;devtype=$device_type&amp;devid=$ID&amp;docid=$docID'><strong>".$LANG["buttons"][6]."</strong></a>";
				else echo "&nbsp;";
				echo "</td>";
			}
			echo "</tr>";
			$i++;
		}
	}

	if ($canedit){
		// Restrict entity for knowbase
		$ci=new CommonItem();
		$entities="";
		$entity=$_SESSION["glpiactive_entity"];
		if ($ci->getFromDB($device_type,$ID) && isset($ci->obj->fields["FK_entities"])) {		
			$entity=$ci->getField('FK_entities');
			
			if (isset($ci->obj->fields["recursive"]) && $ci->obj->fields["recursive"]) {
				$entities = getEntitySons($ci->obj->fields["FK_entities"]);
			} else {
				$entities = $ci->obj->fields["FK_entities"];
			}
		}
		$limit = getEntitiesRestrictRequest(" AND ","glpi_docs",'',$entities,true);
		$q="SELECT count(*) FROM glpi_docs WHERE deleted='0' $limit";
			
		$result = $DB->query($q);
		$nb = $DB->result($result,0,0);
	
		if ($withtemplate<2){
	
			echo "<tr class='tab_bg_1'><td align='center' colspan='3'>" .
				"<input type='hidden' name='FK_entities' value='$entity'>" .
				"<input type='hidden' name='item' value='$ID'>" .
				"<input type='hidden' name='type' value='$device_type'>" .
				"<input type='file' name='filename' size='25'>&nbsp;&nbsp;" .
				"<input type='submit' name='add' value=\"".$LANG["buttons"][8]."\" class='submit'>" .
				"</td>";

			if ($device_type==DOCUMENT_TYPE){
				$used[$ID]=$ID;
			}

			if ($nb>count($used)) {
				echo "<td align='left' colspan='2'>";
				echo "<div class='software-instal'>";
				echo "<input type='hidden' name='right' value='item'>";
				dropdownDocument("conID",$entities,$used);
				echo "</div></td><td class='center'>";
				echo "<input type='submit' name='additem' value=\"".$LANG["buttons"][8]."\" class='submit'>";
				echo "</td><td>&nbsp;</td>";
			}
			else {
				echo "<td colspan='4'>&nbsp;</td>";
			}	
			echo "</tr>";
		}
	}

	echo "</table></div>"    ;
	echo "</form>";

}

/**
 * Get download link for a document
 *
 * @param filename filename of the document
 * @param $params additonal parameters to be added to the link
 **/
function getDocumentLink($filename,$params=""){
	global $DB,$CFG_GLPI;	
	if (empty($filename))
		return "&nbsp;";
	$out="";
	$splitter=split("/",$filename);
	if (count($splitter)==2)
		$fileout=$splitter[1];
	else $fileout=$filename;

	if (strlen($fileout)>20) $fileout=substr($fileout,0,20)."...";

	if (count($splitter)==2){

		$query="SELECT * from glpi_type_docs WHERE ext LIKE '".$splitter[0]."' AND icon <> ''";

		if ($result=$DB->query($query))
			if ($DB->numrows($result)>0){
				$icon=$DB->result($result,0,'icon');

				$out="<a href=\"".$CFG_GLPI["root_doc"]."/front/document.send.php?file=$filename$params\" target=\"_blank\">&nbsp;<img style=\"vertical-align:middle; margin-left:3px; margin-right:6px;\" alt='".$fileout."' title='".$fileout."' src=\"".$CFG_GLPI["typedoc_icon_dir"]."/$icon\" ></a>";				
			}

	}

	$out.="<a href=\"".$CFG_GLPI["root_doc"]."/front/document.send.php?file=$filename$params\" target=\"_blank\"><strong>$fileout</strong></a>";	


	return $out;
}

/**
 * Clean a filename to keep alphanum chars + -_.
 *
 * @param name filename to clean
 **/
function cleanFilenameDocument($name){
	// See http://en.wikipedia.org/wiki/Filename
	$bad_chars = array("'", "\\", ' ', '/', ':', '*', '?', '"', '<', '>', '|');
	$name = str_replace($bad_chars, '_', $name);
	$name = preg_replace("/%(\w{2})/", '_', $name);
	return preg_replace("/\\x00-\\x1f/u", '_', $name);
}

/**
 * Show dropdown of uploaded files
 *
 * @param $myname dropdown name
 **/
function showUploadedFilesDropdown($myname){
	global $CFG_GLPI,$LANG;

	if (is_dir(GLPI_DOC_DIR."/_uploads")){
		$uploaded_files=array();
		if ($handle = opendir(GLPI_DOC_DIR."/_uploads")) {
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != "..") {
					$dir=isValidDoc($file);
					if (!empty($dir))
						$uploaded_files[]=$file;
				}
			}
			closedir($handle);
		}

		if (count($uploaded_files)){
			echo "<select name='$myname'>";
			echo "<option value=''>-----</option>";
			foreach ($uploaded_files as $key => $val)
				echo "<option value=\"$val\">$val</option>";
			echo "</select>";
		} else echo $LANG["document"][37];
	} else echo $LANG["document"][35];
}

/**
 * Is this file a valid file ? check based on file extension
 *
 * @param $filename filename to clean
 **/
function isValidDoc($filename){
	global $DB;
	$splitter=split("\.",$filename);
	$ext=end($splitter);

	$query="SELECT * from glpi_type_docs where ext LIKE '$ext' AND upload='1'";
	if ($result = $DB->query($query))
		if ($DB->numrows($result)>0)
			return strtoupper($ext);

	return "";
}


?>
