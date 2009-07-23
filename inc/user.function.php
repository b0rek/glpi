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


/**  Simple add user form for external auth
* @param $target where to go on action
*/
function showAddExtAuthUserForm($target){
	global $LANG;

	if (!haveRight("user","w")) return false;


	echo "<div class='center'>\n";
	echo "<form method='get' action=\"$target\">\n";

	echo "<table class='tab_cadre'>\n";
	echo "<tr><th colspan='4'>".$LANG['setup'][126]."</th></tr>\n";
	echo "<tr class='tab_bg_1'><td>".$LANG['login'][6]."</td>\n";
	echo "<td>";
	echo "<input type='text' name='login'>";
	echo "</td>";
	echo "<td align='center' class='tab_bg_2'>\n";
	echo "<input type='hidden' name='ext_auth' value='1'>\n";
	echo "<input type='submit' name='add_ext_auth_ldap' value=\"".$LANG['buttons'][8]." ".$LANG['login'][2]."\" class='submit'>\n";
	echo "</td>";
	echo "<td align='center' class='tab_bg_2'>\n";
	echo "<input type='submit' name='add_ext_auth_simple' value=\"".$LANG['buttons'][8]." ".$LANG['common'][62]."\" class='submit'>\n";
	echo "</td>";

	echo "</tr>\n";

	echo "</table>";
	echo "</form>\n";

	echo "</div>\n";

}
/**  Show items of a user
* @param $ID user ID
*/
function showDeviceUser($ID){
	global $DB,$CFG_GLPI, $LANG, $LINK_ID_TABLE,$INFOFORM_PAGES;

	$group_where="";
	$groups=array();
	$query="SELECT glpi_users_groups.FK_groups, glpi_groups.name 
		FROM glpi_users_groups 
		LEFT JOIN glpi_groups ON (glpi_groups.ID = glpi_users_groups.FK_groups) 
		WHERE glpi_users_groups.FK_users='$ID';";
	$result=$DB->query($query);
	if ($DB->numrows($result)>0){
		$first=true;
		while ($data=$DB->fetch_array($result)){
			if ($first){
				$first=false;
			} else {
				$group_where.=" OR ";
			}
			$group_where.=" FK_groups = '".$data["FK_groups"]."' ";
			$groups[$data["FK_groups"]]=$data["name"];
		}
	}


	$ci=new CommonItem();
	echo "<div class='center'><table class='tab_cadre_fixe'><tr><th>".$LANG['common'][17]
		."</th><th>".$LANG['entity'][0]
		."</th><th>".$LANG['common'][16]
		."</th><th>".$LANG['common'][19]
		."</th><th>".$LANG['common'][20]
		."</th><th>&nbsp;</th></tr>";

	foreach ($CFG_GLPI["linkuser_types"] as $type){
		if (haveTypeRight($type,'r')){
			$query="SELECT * FROM ".$LINK_ID_TABLE[$type]." WHERE FK_users='$ID'";

			if (in_array($LINK_ID_TABLE[$type],$CFG_GLPI["template_tables"])){
				$query.=" AND is_template=0 ";
			}
			if (in_array($LINK_ID_TABLE[$type],$CFG_GLPI["deleted_tables"])){
				$query.=" AND deleted=0 ";
			}

			$result=$DB->query($query);
			if ($DB->numrows($result)>0){
				$ci->setType($type,true);
				$type_name=$ci->getType();
				while ($data=$DB->fetch_array($result)){
					$cansee=$ci->obj->can($data["ID"],"r");
					$link=$data["name"];
					if ($cansee) {
						$link="<a href='".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$type]."?ID=".$data["ID"]."'>".
							$link.(($_SESSION["glpiview_ID"]||empty($link))?" (".$data["ID"].")":"")."</a>";	
					}
					$linktype="";
					if ($data["FK_users"]==$ID){
						$linktype=$LANG['common'][34];
					}
					echo "<tr class='tab_bg_1'><td class='center'>$type_name</td>" 
						."<td class='center'>".getDropdownName("glpi_entities",$data["FK_entities"])."</td>"
						."<td class='center'>$link</td>";
					echo "<td class='center'>";
					if (isset($data["serial"])&&!empty($data["serial"])){
						echo $data["serial"];
					} else echo '&nbsp;';
					echo "</td><td class='center'>";
					if (isset($data["otherserial"])&&!empty($data["otherserial"])) {
						echo $data["otherserial"];
					} else echo '&nbsp;';

					echo "<td class='center'>$linktype</td></tr>";
				}
			}
		}
	}
	echo "</table></div><br>";

	if (!empty($group_where)){
		echo "<div class='center'><table class='tab_cadre_fixe'><tr><th>"
			.$LANG['common'][17]."</th><th>"
			.$LANG['entity'][0]."</th><th>"
			.$LANG['common'][16]."</th><th>"
			.$LANG['common'][19]."</th><th>"
			.$LANG['common'][20]."</th><th>&nbsp;</th></tr>";
	
		foreach ($CFG_GLPI["linkgroup_types"] as $type){
			$query="SELECT * FROM ".$LINK_ID_TABLE[$type]." WHERE $group_where";

			if (in_array($LINK_ID_TABLE[$type],$CFG_GLPI["template_tables"])){
				$query.=" AND is_template=0 ";
			}
			if (in_array($LINK_ID_TABLE[$type],$CFG_GLPI["deleted_tables"])){
				$query.=" AND deleted=0 ";
			}

			$result=$DB->query($query);
			if ($DB->numrows($result)>0){
				$ci->setType($type,true);
				$type_name=$ci->getType();
				while ($data=$DB->fetch_array($result)){
					$cansee=$ci->obj->can($data["ID"],"r");
					$link=$data["name"];
					if ($cansee) $link="<a href='".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$type]."?ID=".$data["ID"]."'>".$link.(($_SESSION["glpiview_ID"]||empty($link))?" (".$data["ID"].")":"")."</a>";
					$linktype="";
					if (isset($groups[$data["FK_groups"]])){
						$linktype=$LANG['common'][35]." ".$groups[$data["FK_groups"]];
					}
					echo "<tr class='tab_bg_1'><td class='center'>$type_name</td>"
						."<td class='center'>".getDropdownName("glpi_entities",$data["FK_entities"])."</td>"
						."<td class='center'>$link</td>";
						
					echo "<td class='center'>";
					if (isset($data["serial"])&&!empty($data["serial"])){
						echo $data["serial"];
					} else echo '&nbsp;';
					echo "</td><td class='center'>";
					if (isset($data["otherserial"])&&!empty($data["otherserial"])) {
						echo $data["otherserial"];
					} else echo '&nbsp;';
					echo "</td><td class='center'>$linktype</td></tr>";
				}
			}
	
		}
		echo "</table></div><br>";
	}
}

/**  Show groups of a user
* @param $ID user ID
* @param $target where to go on action
*/
function showGroupAssociated($target,$ID){
	global $DB,$CFG_GLPI, $LANG;

	if (!haveRight("user","r")||!haveRight("group","r"))	return false;

	$canedit=haveRight("user","w");
	$strict_entities=getUserEntities($ID,true);
	if (!haveAccessToOneOfEntities($strict_entities)&&!isViewAllEntities()){
		$canedit=false;
	}

	$rand=mt_rand();
	$nb_per_line=3;
	if ($canedit) {
		$headerspan=$nb_per_line*2;	
		echo "<form name='groupuser_form$rand' id='groupuser_form$rand' method='post' action=\"$target\">";
	} else {
		$headerspan=$nb_per_line;
	}

	echo "<div class='center'><table class='tab_cadrehov'><tr><th colspan='$headerspan'>".$LANG['Menu'][36]."</th></tr>";
	$query="SELECT glpi_groups.*, glpi_users_groups.ID AS IDD, glpi_users_groups.ID as linkID 
		FROM glpi_users_groups 
		LEFT JOIN glpi_groups ON (glpi_groups.ID = glpi_users_groups.FK_groups) 
		WHERE glpi_users_groups.FK_users='$ID' 
		ORDER BY glpi_groups.name";

	$result=$DB->query($query);
	$used=array();
	if ($DB->numrows($result)>0){
		$i=0;

		while ($data=$DB->fetch_array($result)){
			$used[]=$data["ID"];
			if ($i%$nb_per_line==0) {
				if ($i!=0) echo "</tr>";
				echo "<tr class='tab_bg_1'>";
			}

			if ($canedit){
				echo "<td width='10'>";
				$sel="";
				if (isset($_GET["select"])&&$_GET["select"]=="all") $sel="checked";
				echo "<input type='checkbox' name='item[".$data["linkID"]."]' value='1' $sel>";
				echo "</td>";
			}

			echo "<td><a href='".$CFG_GLPI["root_doc"]."/front/group.form.php?ID=".$data["ID"]."'>".$data["name"].($_SESSION["glpiview_ID"]?" (".$data["ID"].")":"")."</a>";
			echo "&nbsp;";

			echo "</td>";
			$i++;
		}
		while ($i%$nb_per_line!=0){
			if ($canedit){
				echo "<td>&nbsp;</td>";
			}
			echo "<td>&nbsp;</td>";
			$i++;
		}
		echo "</tr>";
	} else {
		echo "<tr class='tab_bg_1'><td colspan='$headerspan' class='center'>".$LANG['common'][49]."</td></tr>";
	}

	echo "</table></div>";

	if ($canedit){
		echo "<div class='center'>";
		
		if (count($used)) {	
			echo "<table width='80%' class='tab_glpi'>";
			echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td><td class='center'><a onclick= \"if ( markCheckboxes('groupuser_form$rand') ) return false;\" href='".$_SERVER['PHP_SELF']."?ID=$ID&amp;select=all'>".$LANG['buttons'][18]."</a></td>";
	
			echo "<td>/</td><td class='center'><a onclick= \"if ( unMarkCheckboxes('groupuser_form$rand') ) return false;\" href='".$_SERVER['PHP_SELF']."?ID=$ID&amp;select=none'>".$LANG['buttons'][19]."</a>";
			echo "</td><td align='left' width='80%'>";
			echo "<input type='submit' name='deletegroup' value=\"".$LANG['buttons'][6]."\" class='submit'>";
			echo "</td></tr>";
			echo "</table>";
		} else {
			echo "<br>";
		}

		echo "<table  class='tab_cadre_fixe'>";
		echo "<tr class='tab_bg_1'><th colspan='2'>".$LANG['setup'][604]."</tr><tr><td class='tab_bg_2' align='center'>";
		echo "<input type='hidden' name='FK_users' value='$ID'>";
		if (countElementsInTableForEntity("glpi_groups",$strict_entities) > count($used)) {
			
			dropdownValue("glpi_groups", "FK_groups", "", 1, $strict_entities, "", $used);	
			echo "</td><td align='center' class='tab_bg_2'>";
			echo "<input type='submit' name='addgroup' value=\"".$LANG['buttons'][8]."\" class='submit'>";
	
		} else {
			echo $LANG['common'][49];
		}
		echo "</td></tr>";
		echo "</table></div></form>";
	}

}

/**  Show rights of a user
* @param $ID user ID
* @param $target where to go on action
*/
function showUserRights($target,$ID){
	global $DB,$CFG_GLPI, $LANG;

	if (!haveRight("user","r"))	return false;

	$canedit=haveRight("user","w");

	$strict_entities=getUserEntities($ID,false);
	if (!haveAccessToOneOfEntities($strict_entities)&&!isViewAllEntities()){
		$canedit=false;
	}

	$canshowentity=haveRight("entity","r");

	$rand=mt_rand();
	echo "<form name='entityuser_form$rand' id='entityuser_form$rand' method='post' action=\"$target\">";

	if ($canedit){
		echo "<div class='center'>";
		echo "<table  class='tab_cadre_fixe'>";

		echo "<tr class='tab_bg_1'><th colspan='4'>".$LANG['entity'][3]."</tr><tr class='tab_bg_2'><td class='center'>";
		echo "<input type='hidden' name='FK_users' value='$ID'>";

		dropdownValue("glpi_entities","FK_entities",0,1,$_SESSION['glpiactiveentities']);
		echo "</td><td class='center'>";

		echo $LANG['profiles'][22].":";
		dropdownUnderProfiles("FK_profiles");
		echo "</td><td class='center'>";
		echo $LANG['profiles'][28].":";
		dropdownYesNo("recursive",0);
		echo "</td><td class='center'>";
		echo "<input type='submit' name='addright' value=\"".$LANG['buttons'][8]."\" class='submit'>";
		echo "</td></tr>";

		echo "</table></div><br>";
	}

	echo "<div class='center'><table class='tab_cadrehov'><tr><th colspan='2'>".$LANG['Menu'][37]."</th><th>".$LANG['profiles'][22]." (D=".$LANG['profiles'][29].", R=".$LANG['profiles'][28].")</th></tr>";

	$query="SELECT DISTINCT glpi_users_profiles.ID as linkID, glpi_profiles.ID, glpi_profiles.name, glpi_users_profiles.recursive,
			glpi_users_profiles.dynamic, glpi_entities.completename, glpi_users_profiles.FK_entities
			FROM glpi_users_profiles 
			LEFT JOIN glpi_profiles ON (glpi_users_profiles.FK_profiles = glpi_profiles.ID)
			LEFT JOIN glpi_entities ON (glpi_users_profiles.FK_entities = glpi_entities.ID)
			WHERE glpi_users_profiles.FK_users='$ID'
			ORDER BY glpi_profiles.name, glpi_entities.completename;";

	$result=$DB->query($query);
	if ($DB->numrows($result)>0){
		$i=0;

		while ($data=$DB->fetch_array($result)){
			echo "<tr class='tab_bg_1'>";
			
			echo "<td width='10'>";
			if ($canedit&&in_array($data["FK_entities"],$_SESSION['glpiactiveentities'])){
				$sel="";
				if (isset($_GET["select"])&&$_GET["select"]=="all") $sel="checked";
				echo "<input type='checkbox' name='item[".$data["linkID"]."]' value='1' $sel>";
			} else {
				echo "&nbsp;";
			}
			echo "</td>";

			if ($data["FK_entities"]==0) {
				$data["completename"]=$LANG['entity'][2];
			}
			echo "<td>";
			if ($canshowentity){
				echo "<a href='".$CFG_GLPI["root_doc"]."/front/entity.form.php?ID=".$data["FK_entities"]."'>";
			}
			echo $data["completename"].($_SESSION["glpiview_ID"]?" (".$data["FK_entities"].")":"");
			if ($canshowentity){
				echo "</a>";
			}
			echo "</td>";
			echo "<td>".$data["name"];
			if ($data["dynamic"]||$data["recursive"]){
				echo "<strong>&nbsp;(";
				if ($data["dynamic"]) echo "D";
				if ($data["dynamic"]&$data["recursive"]) echo ", ";
				if ($data["recursive"]) echo "R";
				echo ")</strong>";
			}

			echo "</td>";
			$i++;
		}
		echo "</tr>";
	}

	echo "</table></div>";

	if ($canedit){
		echo "<div class='center'>";
		echo "<table width='80%' class='tab_glpi'>";
		echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td><td class='center'><a onclick= \"if ( markCheckboxes('entityuser_form$rand') ) return false;\" href='".$_SERVER['PHP_SELF']."?ID=$ID&amp;select=all'>".$LANG['buttons'][18]."</a></td>";

		echo "<td>/</td><td class='center'><a onclick= \"if ( unMarkCheckboxes('entityuser_form$rand') ) return false;\" href='".$_SERVER['PHP_SELF']."?ID=$ID&amp;select=none'>".$LANG['buttons'][19]."</a>";
		echo "</td><td align='left' width='80%'>";
		echo "<input type='submit' name='deleteright' value=\"".$LANG['buttons'][6]."\" class='submit'>";
		echo "</td></tr>";
		echo "</table>";

		echo "</div>";

	}

	echo "</form>";

}



/**  Generate vcard for an user
* @param $ID user ID
*/
function generateUserVcard($ID){

	$user = new User;
	$user->getFromDB($ID);

	// build the Vcard

	$vcard = new vCard();

	if (!empty($user->fields["realname"])||!empty($user->fields["firstname"])) $vcard->setName($user->fields["realname"], $user->fields["firstname"], "", ""); 
	else $vcard->setName($user->fields["name"], "", "", "");

	$vcard->setPhoneNumber($user->fields["phone"], "PREF;WORK;VOICE");
	$vcard->setPhoneNumber($user->fields["phone2"], "HOME;VOICE");
	$vcard->setPhoneNumber($user->fields["mobile"], "WORK;CELL");

	//if ($user->birthday) $vcard->setBirthday($user->birthday);

	$vcard->setEmail($user->fields["email"]);

	$vcard->setNote($user->fields["comments"]);

	// send the  VCard 

	$output = $vcard->getVCard();


	$filename =$vcard->getFileName();      // "xxx xxx.vcf"

	@Header("Content-Disposition: attachment; filename=\"$filename\"");
	@Header("Content-Length: ".strlen($output));
	@Header("Connection: close");
	@Header("content-type: text/x-vcard; charset=UTF-8");

	echo $output;

}

/**  Get entities for which a user have a right
* @param $ID user ID
* @param $recursive check also using recurisve rights
*/
function getUserEntities($ID,$recursive=true){
	global $DB;

	$query="SELECT DISTINCT FK_entities, recursive
			FROM glpi_users_profiles 
			WHERE FK_users='$ID';";
	$result=$DB->query($query);
	if ($DB->numrows($result)>0){
		$entities=array();
		while ($data=$DB->fetch_assoc($result)){
			if ($data['recursive']&&$recursive){
				$tab=getSonsOfTreeItem('glpi_entities',$data['FK_entities']);
				$entities=array_merge($tab,$entities);
			} else {
				$entities[]=$data['FK_entities'];
			}
		}
		return array_unique($entities);
	} 

	return array();
}

/** Get all the authentication methods parameters for a specific auth_method and id_auth and return it as an array 
* @param $auth_method Authentication method
* @param $id_auth Authentication method ID
*/
function getAuthMethodsByID($auth_method, $id_auth) {
	global $DB;

	$auth_methods = array ();
	$sql = "";

	switch ($auth_method) {
		case AUTH_X509 :
		case AUTH_EXTERNAL :
		case AUTH_CAS :
			if ($id_auth>0){
				//Get all the ldap directories
				$sql = "SELECT * FROM glpi_auth_ldap WHERE ID='".$id_auth."'";
			}
			break;
		case AUTH_LDAP :
			//Get all the ldap directories
			$sql = "SELECT * FROM glpi_auth_ldap WHERE ID='".$id_auth."'";
			break;
		case AUTH_MAIL :
			//Get all the pop/imap servers
			$sql = "SELECT * FROM glpi_auth_mail WHERE ID='".$id_auth."'";
			break;
	}

	if ($sql != "") {
		$result = $DB->query($sql);
		if ($DB->numrows($result) > 0) {
			$auth_methods = $DB->fetch_array($result);
		}
	}
	//Return all the authentication methods in an array
	return $auth_methods;
}

/** Get name of an authentication method
* @param $auth_method Authentication method
* @param $id_auth Authentication method ID
* @param $link show links to config page ?
* @param $name override the name if not empty
*/
function getAuthMethodName($auth_method, $id_auth, $link=0,$name=''){
	global $LANG,$CFG_GLPI;
	switch ($auth_method) {
		case AUTH_LDAP :
			if (empty($name)){
				$method = getAuthMethodsByID($auth_method,$id_auth);
				if (isset($method["name"])){
					$name=$method["name"];
				} else {
					$name="???";
					$link=false;
				}
			}
			$out= $LANG['login'][2];
			if ($link && haveRight("config", "w")){
				return  $out."&nbsp " . $LANG['common'][52] . " <a href=\"" . $CFG_GLPI["root_doc"] . "/front/auth.ldap.php?next=extauth_ldap&amp;ID=" . $id_auth . "\">" . $name . "</a>";
			} else {
				return  $out."&nbsp " . $LANG['common'][52] . " " . $name;
			}
		break;
		case AUTH_MAIL :
			if (empty($name)){
				$method = getAuthMethodsByID($auth_method,$id_auth);

				if (isset($method["name"])){
					$name=$method["name"];
				} else {
					$name="???";
					$link=false;
				}
			}
			$out= $LANG['login'][3];
			if ($link && haveRight("config", "w")){
				return  $out. "&nbsp " . $LANG['common'][52] . " <a href=\"" . $CFG_GLPI["root_doc"] . "/front/auth.imap.php?next=extauth_mail&amp;ID=" . $id_auth . "\">" . $name . "</a>";
			} else {
				return  $out. "&nbsp " . $LANG['common'][52] . " " . $name;
			}
		break;
		case AUTH_CAS :
			return  $LANG['login'][4];
			break;
		case AUTH_X509 :
			return  $LANG['setup'][190];
			break;
		case AUTH_EXTERNAL :
			return  $LANG['common'][62];
			break;
		case AUTH_DB_GLPI :
			return $LANG['login'][18];
		break;
		case NOT_YET_AUTHENTIFIED :
			return $LANG['login'][9];
			break;
	}
}

/** Get LDAP fields to sync to GLPI data from a glpi_auth_ldap array 
* @param $auth_method_array Authentication method config array
*/
function getLDAPSyncFields($auth_method_array){ 

	$ret=array(); 
      
	$fields=array('ldap_login'=>'name', 
			'ldap_field_email'=>'email', 
			'ldap_field_realname'=>'realname', 
 			'ldap_field_firstname'=>'firstname', 
 			'ldap_field_phone'=>'phone', 
 			'ldap_field_phone2'=>'phone2', 
 			'ldap_field_mobile'=>'mobile', 
 			'ldap_field_comments'=>'comments', 
 			'ldap_field_title'=>'title',
 			'ldap_field_type'=>'type',
 			'ldap_field_language'=>'language'		
 		); 
 	foreach ($fields as $key => $val){ 
 		if (isset($auth_method_array[$key])){ 
 			$ret[$val]=$auth_method_array[$key]; 
 		} 
 	} 
 	return $ret; 
} 

/**
 * Get language in GLPI associated with the value coming from LDAP
 * Value can be, for example : English, en_EN or en
 * @param value : the value coming from LDAP
 * @return the locale's php page in GLPI or '' is no language associated with the value
 */
function getUserLanguage($lang)
{
	global $CFG_GLPI;
	foreach ($CFG_GLPI["languages"] as $ID => $language)
		if ($lang==$ID || $lang == $language[0] || $lang == $language[2] || $lang == $language[3])
			return $ID;

	return ""; 
}

function changeUserAuthMethod($IDs=array(),$auth_method=1,$server=-1)
{
	global $DB;
	if (!empty($IDs) && in_array($auth_method,array(AUTH_DB_GLPI,AUTH_LDAP,AUTH_MAIL,AUTH_EXTERNAL)))
	{
		$where = implode(',',$IDs);
		$query="UPDATE glpi_users SET auth_method=".$auth_method.", id_auth=".$server." WHERE ID IN (".$where.")";
		$DB->query($query);
	}
}
?>
