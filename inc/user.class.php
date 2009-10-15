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
// And Marco Gaiarin for ldap features

if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}

class User extends CommonDBTM {

	/**
	* Compute preferences for the current user mixing config and user data
	**/
	function computePreferences (){
		global $CFG_GLPI;
		if (isset($this->fields['ID'])){
			foreach ($CFG_GLPI['user_pref_field'] as $f){
				if (is_null($this->fields[$f]) ){
					$this->fields[$f]=$CFG_GLPI[$f];
				}
			}
		}
	}

	/**
	 * Constructor
	**/
	function __construct() {
		global $CFG_GLPI;

		$this->table = "glpi_users";
		$this->type = USER_TYPE;
		$this->dohistory = true;
		$this->history_blacklist = array('last_login');

		$this->fields['tracking_order'] = 0;
		if (isset ($CFG_GLPI["language"])){
			$this->fields['language'] = $CFG_GLPI["language"];
		} else {
			$this->fields['language'] = "en_GB";
		}

	}
	function defineTabs($ID,$withtemplate) {
		global $LANG;

		$ong=array();
		// No add process
		if ($ID>0){
			$ong[1] = $LANG['Menu'][35]; // principal

			$ong[4]=$LANG['Menu'][36];

			$ong[2] = $LANG['common'][1]; // materiel
			if (haveRight("show_all_ticket", "1")){
				$ong[3] = $LANG['title'][28]; // tickets
			}
			if (haveRight("reservation_central", "r")){
				$ong[11] = $LANG['Menu'][17];
			}
			if (haveRight("user_auth_method", "w")){
				$ong[12] = $LANG['ldap'][12];
			}
			$ong[13]=$LANG['title'][38];
		} else { // New item
			$ong[1]=$LANG['title'][26];
		}

		return $ong;
	}

	function post_getEmpty () {
		$this->fields["active"]=1;
	}

	function pre_deleteItem($ID){
		global $LANG,$DB;

		$entities=getUserEntities($ID);
		$view_all=isViewAllEntities();
		// Have right on all entities ?
		$all=true;
		if (!$view_all){
			foreach ($entities as $ent){
				if (!haveAccessToEntity($ent)){
					$all=false;
				}
			}
		}
		if ($all){ // Mark as deleted
			return true;
		} else { // only delete profile
			foreach ($entities as $ent){
				if (haveAccessToEntity($ent)){
					$all=false;
					$query = "DELETE FROM glpi_users_profiles
						WHERE FK_users = '$ID' AND FK_entities='$ent'";
					$DB->query($query);
				}
			}
			return false;
		}
	}
	function cleanDBonMarkDeleted($ID) {
	}

	function cleanDBonPurge($ID) {
		global $DB;

		$query = "DELETE FROM glpi_users_profiles WHERE (FK_users = '$ID')";
		$DB->query($query);

		$query = "DELETE FROM glpi_users_groups WHERE FK_users = '$ID'";
		$DB->query($query);

		$query = "DELETE FROM glpi_display WHERE FK_users = '$ID'";
		$DB->query($query);
		$query = "DELETE FROM glpi_display_default WHERE FK_users = '$ID'";
		$DB->query($query);

		// Delete private reminder
		$query = "DELETE FROM glpi_reminder WHERE FK_users = '$ID' AND private=1";
		$DB->query($query);
		// Set no user to public reminder
		$query = "UPDATE glpi_reminder SET FK_users = 0 WHERE FK_users = '$ID'";
		$DB->query($query);
		// Delete private bookmark
		$query = "DELETE FROM glpi_bookmark WHERE FK_users = '$ID' AND private=1";
		$DB->query($query);
		// Set no user to public bookmark
		$query = "UPDATE glpi_bookmark SET FK_users = 0 WHERE FK_users = '$ID'";
		$DB->query($query);
	}

	/**
	 * Retrieve an item from the database using its login
	 *
	 *@param $name login of the user
	 *@return true if succeed else false
	 *
	**/
	function getFromDBbyName($name) {
		global $DB;
		$query = "SELECT * FROM glpi_users WHERE (name = '" . $name . "')";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result) != 1) {
				return false;
			}
			$this->fields = $DB->fetch_assoc($result);
			if (is_array($this->fields) && count($this->fields)) {
				return true;
			} else {
				return false;
			}
		}
		return false;
	}

	function prepareInputForAdd($input) {
		global $CFG_GLPI,$DB,$LANG;


		// Check if user does not exists
		$query="SELECT * FROM glpi_users WHERE name='".$input['name']."';";
		$result=$DB->query($query);
		if ($DB->numrows($result)>0){
			addMessageAfterRedirect($LANG['setup'][606],false,ERROR);
			return false;
		}

		if (isset ($input["password"])) {
			if (empty ($input["password"])) {
				unset ($input["password"]);
			} else {
				$input["password_md5"] = md5(unclean_cross_side_scripting_deep(stripslashes($input["password"])));
				$input["password"] = "";
			}
		}
		if (isset ($input["_extauth"])) {
			$input["password"] = "";
			$input["password_md5"] = "";
		}
		// change email_form to email (not to have a problem with preselected email)
		if (isset ($input["email_form"])) {
			$input["email"] = $input["email_form"];
			unset ($input["email_form"]);
		}

		// Force DB default values : not really needed
		if (!isset($input["active"])){
			$input["active"]=1;
		}

		if (!isset($input["deleted"])){
			$input["deleted"]=0;
		}

		if (!isset($input["FK_entities"])){
			$input["FK_entities"]=0;
		}

		if (!isset($input["FK_profiles"])){
			$input["FK_profiles"]=0;
		}

		if (!isset($input["auth_method"])){
			$input["auth_method"]=-1;
		}


		return $input;
	}

	function post_addItem($newID, $input) {
		global $DB;

		$input["ID"]=$newID;

		$this->syncLdapGroups($input);
		$rulesplayed = $this->applyRightRules($input);

		// Add default profile
		if (!$rulesplayed){
			$sql_default_profile = "SELECT ID FROM glpi_profiles WHERE is_default=1";
			$result = $DB->query($sql_default_profile);
			if ($DB->numrows($result)){
				$right=$DB->result($result,0,0);
				if (isset($input["FK_entities"])){
					$affectation["FK_entities"] = $input["FK_entities"];
				} else if (isset($_SESSION['glpiactive_entity'])){
					$affectation["FK_entities"] = $_SESSION['glpiactive_entity'];
				} else {
					$affectation["FK_entities"] = 0;
				}
				$affectation["FK_profiles"] = $DB->result($result,0,0);
				$affectation["FK_users"] = $input["ID"];
				$affectation["recursive"] = 0;
				$affectation["dynamic"] = 0;
				addUserProfileEntity($affectation);
			}
		}
	}

	function prepareInputForUpdate($input) {
		global  $LANG,$CFG_GLPI;

		if (isset ($input["password"])){
			// Empty : do not update
			if (empty($input["password"])){
				unset($input["password"]);
			} else {
				// Check right : my password of user with lesser rights
				if (isset($input['ID']) &&
					((isset($_SESSION['glpiID']) && $input['ID']==$_SESSION['glpiID'])
						|| $this->currentUserHaveMoreRightThan($input['ID']) )){
					$input["password_md5"] = md5(unclean_cross_side_scripting_deep(stripslashes($input["password"])));
					$input["password"] = "";
				} else {
					unset($input["password"]);
				}

			}

		}

		// change email_form to email (not to have a problem with preselected email)
		if (isset ($input["email_form"])) {
			$input["email"] = $input["email_form"];
			unset ($input["email_form"]);
		}

		// Update User in the database
		if (!isset ($input["ID"]) && isset ($input["name"])) {
			if ($this->getFromDBbyName($input["name"]))
				$input["ID"] = $this->fields["ID"];
		}


		if (isset ($_SESSION["glpiID"]) && isset ($input["FK_entities"]) && $_SESSION["glpiID"] == $input['ID']) {
			$_SESSION["glpidefault_entity"] = $input["FK_entities"];
		}

		// Manage preferences fields
		if (isset ($_SESSION["glpiID"]) && $_SESSION["glpiID"] == $input['ID']) {
			if (isset($input['use_mode']) && $_SESSION['glpi_use_mode']!=$input['use_mode']){
				$_SESSION['glpi_use_mode']=$input['use_mode'];
				//loadLanguage();
			}

			foreach ($CFG_GLPI['user_pref_field'] as $f){
				if (isset($input[$f])){
					if ($_SESSION["glpi$f"] != $input[$f]){
						$_SESSION["glpi$f"] = $input[$f];
					}
					if ($input[$f] == $CFG_GLPI[$f]){
						$input[$f]="NULL";
					}
					//if ($_SESSION["glpi$f"] != $input[$f] && $f=="language"){
						//loadLanguage();
					//}
				}
			}
		}
      // Get auth method fo sync ldap groups if needed
      /// TODO : review it : maybe do it on post actions
      if (!isset($input["auth_method"])){
         $this->getFromDB($input['ID']);
         $input["auth_method"]=$this->fields['auth_method'];
         if (!isset($input["id_auth"])){
            $input["id_auth"]=$this->fields['id_auth'];
         }
      }

		$this->syncLdapGroups($input);

		$this->applyRightRules($input);

		return $input;
	}


	function post_updateItem($input, $updates, $history=1) {
		global $CFG_GLPI;
		// Clean header cache for the user
		if (in_array("language", $updates) && isset ($input["ID"])) {
			cleanCache("GLPI_HEADER_".$input["ID"]);
		}
	}

	// SPECIFIC FUNCTIONS
	/**
	 * Apply rules to determine dynamic rights of the user
	 *
	 *@param $input data used to apply rules
	 *
	 *@return boolean : true if we play the Rule Engine
	**/
	function applyRightRules($input){
		global $DB;
		if (isset($input["auth_method"])&&($input["auth_method"] == AUTH_LDAP || $input["auth_method"]== AUTH_MAIL|| isAlternateAuthWithLdap($input["auth_method"])))
		if (isset ($input["ID"]) &&$input["ID"]>0&& isset ($input["_ldap_rules"]) && count($input["_ldap_rules"])) {

			//TODO : do not erase all the dynamic rights, but compare it with the ones in DB

			//and add/update/delete only if it's necessary !
			if (isset($input["_ldap_rules"]["rules_entities_rights"]))
				$entities_rules = $input["_ldap_rules"]["rules_entities_rights"];
			else
				$entities_rules = array();

			if (isset($input["_ldap_rules"]["rules_entities"]))
				$entities = $input["_ldap_rules"]["rules_entities"];
			else
				$entities = array();

			if (isset($input["_ldap_rules"]["rules_rights"]))
				$rights = $input["_ldap_rules"]["rules_rights"];
			else
				$rights = array();

			//purge dynamic rights
			$this->purgeDynamicProfiles();

			//For each affectation -> write it in DB
			foreach($entities_rules as $entity){
				//Multiple entities assignation
            if (is_array($entity[0])) {
               foreach ($entity[0] as $tmp => $ent) {
                  $affectation["FK_entities"] = $ent[0];
                  $affectation["FK_profiles"] = $entity[1];
                  $affectation["recursive"] = $entity[2];
                  $affectation["FK_users"] = $input["ID"];
                  $affectation["dynamic"] = 1;
                  addUserProfileEntity($affectation);
   				}
            }
            else {
               $affectation["FK_entities"] = $entity[0];
               $affectation["FK_profiles"] = $entity[1];
               $affectation["recursive"] = $entity[2];
               $affectation["FK_users"] = $input["ID"];
               $affectation["dynamic"] = 1;
               addUserProfileEntity($affectation);
            }
			}

			if (count($entities)>0&&count($rights)==0){
				//If no dynamics profile is provided : get the profil by default if no existing profile
				//$exist_profile = "SELECT ID FROM glpi_users_profiles WHERE FK_users='".$input["ID"]."'";
            
            //$result = $DB->query($exist_profile);
				//if ($DB->numrows($result)==0){
					$sql_default_profile = "SELECT ID FROM glpi_profiles WHERE is_default=1";

					$result = $DB->query($sql_default_profile);
					if ($DB->numrows($result))
					{
						$rights[]=$DB->result($result,0,0);
					}
				//}
			}

			if (count($rights)>0&&count($entities)>0){
            foreach($entities as $entity_tab){
                  foreach ($entity_tab as $tmp => $entity) {
                  foreach($rights as $right){
                     $affectation["FK_entities"] = $entity[0];
                     $affectation["FK_profiles"] = $right;
                     $affectation["FK_users"] = $input["ID"];
                     $affectation["recursive"] = $entity[1];
                     $affectation["dynamic"] = 1;
                     addUserProfileEntity($affectation);
                     }
                  }
				}
			}

			//Unset all the temporary tables
			unset($input["_ldap_rules"]);

			return true;
		}
		return false;

	}
	/**
	 * Synchronise LDAP group of the user
	 *
	 *@param $input data used to sync
	**/
	function syncLdapGroups($input){
		global $DB;

		if (isset($input["auth_method"])&&($input["auth_method"]==AUTH_LDAP || isAlternateAuthWithLdap($input['auth_method']))){
			if (isset ($input["ID"]) && $input["ID"]>0) {
				$auth_method = getAuthMethodsByID($input["auth_method"], $input["id_auth"]);

				if (count($auth_method)){
					if (!isset($input["_groups"])){
						$input["_groups"]=array();
					}
					// Clean groups
					$input["_groups"] = array_unique ($input["_groups"]);


					$WHERE = "";
					switch ($auth_method["ldap_search_for_groups"]) {
						case 0 : // user search
							$WHERE = "AND (glpi_groups.ldap_field <> '' AND glpi_groups.ldap_field IS NOT NULL AND glpi_groups.ldap_value<>'' AND glpi_groups.ldap_value IS NOT NULL )";
							break;
						case 1 : // group search
							$WHERE = "AND (ldap_group_dn<>'' AND ldap_group_dn IS NOT NULL )";
							break;
						case 2 : // user+ group search
							$WHERE = "AND ((glpi_groups.ldap_field <> '' AND glpi_groups.ldap_field IS NOT NULL
									AND glpi_groups.ldap_value<>'' AND glpi_groups.ldap_value IS NOT NULL)
								OR (ldap_group_dn<>'' AND ldap_group_dn IS NOT NULL) )";
							break;

					}
					// Delete not available groups like to LDAP
					$query = "SELECT glpi_users_groups.ID, glpi_users_groups.FK_groups
						FROM glpi_users_groups
						LEFT JOIN glpi_groups ON (glpi_groups.ID = glpi_users_groups.FK_groups)
						WHERE glpi_users_groups.FK_users='" . $input["ID"] . "' $WHERE";

					$result = $DB->query($query);
					if ($DB->numrows($result) > 0) {
						while ($data = $DB->fetch_array($result)){
							if (!in_array($data["FK_groups"], $input["_groups"])) {
								deleteUserGroup($data["ID"]);
							} else {
								// Delete found item in order not to add it again
								unset($input["_groups"][array_search($data["FK_groups"], $input["_groups"])]);
							}
						}
					}

					//If the user needs to be added to one group or more
					if (count($input["_groups"])>0)
					{
						foreach ($input["_groups"] as $group) {
							addUserGroup($input["ID"], $group);
						}
						unset ($input["_groups"]);
					}
				}
			}
		}
	}

	/**
	 * Get the name of the current user
	 * @return string containing name of the user
	**/
	function getName() {
		return formatUserName($this->fields["ID"],$this->fields["name"],$this->fields["realname"],$this->fields["firstname"]);
	}

   	/**
   * Function that try to load from LDAP the user membership
   * by searching in the attribute of the User
   *
   * @param $ldap_connection ldap connection descriptor
   * @param $ldap_method LDAP method
   * @param $userdn Basedn of the user
   * @param $login User Login
   * @param $password User Password
   *
   * @return String : basedn of the user / false if not founded
      */
   private function getFromLDAPGroupVirtual($ldap_connection, $ldap_method, $userdn, $login, $password) {
      global $DB,$CFG_GLPI;

		/// Search in DB the ldap_field we need to search for in LDAP
      $query="SELECT DISTINCT `ldap_field` FROM `glpi_groups` WHERE `ldap_field`!='' ORDER BY `ldap_field`";
      $group_fields = array ();
      foreach ($DB->request($query) as $data) {
         $group_fields[] = strtolower($data["ldap_field"]);
      }
      if (count($group_fields)) {
			///Need to sort the array because edirectory don't like it!
         sort($group_fields);
         //logInFile("debug","Champs de recherche : ".print_r($group_fields,true));

			/// If the groups must be retrieve from the ldap user object
         $sr = @ ldap_read($ldap_connection, $userdn, "objectClass=*", $group_fields);
         $v = ldap_get_entries($ldap_connection, $sr);

         for ($i=0;$i<count($v['count']);$i++) {

				///Try to find is DN in present and needed: if yes, then extract only the OU from it
            if (($ldap_method["ldap_field_group"]=='dn' || in_array('ou',$group_fields))
                  && isset($v[$i]['dn'])) {
               $v[$i]['ou'] = array();
               for ($tmp=$v[$i]['dn'] ; count($tmptab=explode(',',$tmp,2))==2 ; $tmp=$tmptab[1]) {
                  $v[$i]['ou'][] = $tmptab[1];
               }

					/// Search in DB for group with ldap_group_dn
               if ($ldap_method["ldap_field_group"]=='dn' && count($v[$i]['ou'])>0) {

                  $query="SELECT ID FROM `glpi_groups`
                        WHERE `ldap_group_dn` IN ('".implode("','",addslashes_deep($v[$i]['ou']))."')";

                  foreach ($DB->request($query) as $group) {
                     $this->fields["_groups"][]=$group['ID'];
                  }
               }

					/// searching with ldap_field='OU' and ldap_value is also possible
                     $v[$i]['ou']['count'] = count($v[$i]['ou']);
            }
            //logInFile("debug","Groupes virtuels LDAP (avec OU) : ".print_r($v[$i],true));

				/// For each attribute retrieve from LDAP, search in the DB
            foreach ($group_fields as $field) {
               if (isset($v[$i][$field]) && isset($v[$i][$field]['count']) && $v[$i][$field]['count']>0) {
                  unset($v[$i][$field]['count']);
                  $query="SELECT ID FROM `glpi_groups`
                        WHERE `ldap_field`='$field'
                        AND `ldap_value` IN ('".implode("','",addslashes_deep($v[$i][$field]))."')";

                  foreach ($DB->request($query) as $group) {
                     $this->fields["_groups"][]=$group['ID'];
                  }
               }
            }
         } // for each ldapresult
      } // count($group_fields)
   }

	/**
   * Function that try to load from LDAP the user membership
   * by searching in the attribute of the Groups
   *
   * @param $ldap_connection ldap connection descriptor
   * @param $ldap_method LDAP method
   * @param $userdn Basedn of the user
   * @param $login User Login
   * @param $password User Password
   *
   * @return String : basedn of the user / false if not founded
   */
   private function getFromLDAPGroupDiscret($ldap_connection, $ldap_method, $userdn, $login, $password) {
      global $DB,$CFG_GLPI;

      if ($ldap_method["use_dn"]) {
         $user_tmp = $userdn;
      } else {
			///$user_tmp = $ldap_method["ldap_login"]."=".$login;
			///Don't add $ldap_method["ldap_login"]."=", because sometimes it may not work (for example with posixGroup)
         $user_tmp = $login;
      }

      $v = $this->ldap_get_user_groups($ldap_connection, $ldap_method["ldap_basedn"], $user_tmp, $ldap_method["ldap_group_condition"], $ldap_method["ldap_field_group_member"],$ldap_method["use_dn"],$ldap_method["ldap_login"]);
      //logInFile("debug","Groupes discrets LDAP : ".print_r($v,true));

      foreach ($v as $result) {
         if (isset($result[$ldap_method["ldap_field_group_member"]])
             && is_array($result[$ldap_method["ldap_field_group_member"]])
             && count($result[$ldap_method["ldap_field_group_member"]])>0) {

            $query="SELECT ID FROM `glpi_groups`
                  WHERE `ldap_group_dn` IN ('".implode("','",addslashes_deep($result[$ldap_method["ldap_field_group_member"]]))."')";

            foreach ($DB->request($query) as $group) {
               $this->fields["_groups"][]=$group['ID'];
            }
         }
      }
   }

	/**
	 * Function that try to load from LDAP the user information...
	 *
	 * @param $ldap_connection ldap connection descriptor
	 * @param $ldap_method LDAP method
	 * @param $userdn Basedn of the user
	 * @param $login User Login
	 * @param $password User Password
	 *
	 * @return String : basedn of the user / false if not founded
	 */
	function getFromLDAP($ldap_connection,$ldap_method, $userdn, $login, $password = "") {
		global $DB,$CFG_GLPI;

		// we prevent some delay...
		if (empty ($ldap_method["ldap_host"])) {
			return false;
		}

		if ($ldap_connection) {
			//Set all the search fields
			$this->fields['password'] = "";
			$this->fields['password_md5'] = "";

			$fields=getLDAPSyncFields($ldap_method);
			$fields = array_filter($fields);
			$f = array_values($fields);

			$sr = @ ldap_read($ldap_connection, $userdn, "objectClass=*", $f);
			$v = ldap_get_entries($ldap_connection, $sr);

			if (!is_array($v) || count($v) == 0 || empty ($v[0][$fields['name']][0]))
				return false;
         foreach ($fields as $k => $e) {
            if (empty($v[0][$e][0])){
               switch ($k){
                  case "language":
                     // Not set value : managed but user class
                     break;
                  case "title":
                  case "type":
                     $this->fields[$k] = 0;
                     break;
                  default:
                     $this->fields[$k] = "";
                     break;
                   }
               } else {
							switch ($k)
							{
								case "language":
									$language = getUserLanguage($v[0][$e][0]);
									if ($language != ''){
										$this->fields[$k]=$language;
                           }
									break;
								case "title":
								case "type":
									$this->fields[$k] =
externalImportDropdown("glpi_dropdown_user_".$k."s",addslashes($v[0][$e][0]),-1,array(),'',true);
									break;
								break;
								default:
								if (!empty($v[0][$e][0]))
								 $this->fields[$k] = addslashes($v[0][$e][0]);
								else
								$this->fields[$k] = "";
								break;
							}
					}
			}


			///The groups are retrieved by looking into an ldap user object
         if ($ldap_method["ldap_search_for_groups"] == 0
             || $ldap_method["ldap_search_for_groups"] == 2) {
            $this->getFromLDAPGroupVirtual($ldap_connection, $ldap_method, $userdn, $login, $password);
         }


			///The groups are retrived by looking into an ldap group object
         if ($ldap_method["ldap_search_for_groups"] == 1
                 || $ldap_method["ldap_search_for_groups"] == 2) {
            $this->getFromLDAPGroupDiscret($ldap_connection, $ldap_method, $userdn, $login, $password);
         }

			///Only process rules if working on the master database
			if (!$DB->isSlave()){
				///Instanciate the affectation's rule
				$rule = new RightRuleCollection();

				///Process affectation rules :
				///we don't care about the function's return because all the datas are stored in session temporary
				if (isset($this->fields["_groups"]))
					$groups = $this->fields["_groups"];
				else
					$groups = array();

				$this->fields=$rule->processAllRules($groups,$this->fields,array("type"=>"LDAP","ldap_server"=>$ldap_method["ID"],"connection"=>$ldap_connection,"userdn"=>$userdn));

				///Hook to retrieve more informations for ldap
				$this->fields = doHookFunction("retrieve_more_data_from_ldap", $this->fields);
			}
			return true;
		}
		return false;

	} /// getFromLDAP()

	/**
	 * Get all the group a user belongs to
	 *
	 * @param $ds ldap connection
	 * @param $ldap_base_dn Basedn used
	 * @param $user_dn Basedn of the user
	 * @param $group_condition group search condition
	 * @param $group_field_member group field member in a user object
	 *
	 * @return String : basedn of the user / false if not founded
	 */
	function ldap_get_user_groups($ds, $ldap_base_dn, $user_dn, $group_condition, $group_field_member,$use_dn,$login_field) {

		$groups = array ();
		$listgroups = array ();

		//Only retrive cn and member attributes from groups
		$attrs = array (
			"dn"
		);

		if (!$use_dn)
			$filter = "(& $group_condition (|($group_field_member=$user_dn)($group_field_member=$login_field=$user_dn)))";
		else
			$filter = "(& $group_condition ($group_field_member=$user_dn))";

		//Perform the search
		$sr = ldap_search($ds, $ldap_base_dn, $filter, $attrs);

		//Get the result of the search as an array
		$info = ldap_get_entries($ds, $sr);
		//Browse all the groups
		for ($i = 0; $i < count($info); $i++) {
			//Get the cn of the group and add it to the list of groups
			if (isset ($info[$i]["dn"]) && $info[$i]["dn"] != '')
				$listgroups[$i] = $info[$i]["dn"];
		}

		//Create an array with the list of groups of the user
		$groups[0][$group_field_member] = $listgroups;
		//Return the groups of the user
		return $groups;
	}

	/**
	 * Function that try to load from IMAP the user information...
	 *
	 * @param $mail_method mail method description array
	 * @param $name login of the user
	 */
	function getFromIMAP($mail_method, $name) {
		global $DB;

		// we prevent some delay..
		if (empty ($mail_method["imap_host"])) {
			return false;
		}

		// some defaults...
		$this->fields['password'] = "";
		$this->fields['password_md5'] = "";
		if (strpos($name,"@")){
			$this->fields['email'] = $name;
		} else {
			$this->fields['email'] = $name . "@" . $mail_method["imap_host"];
		}

		$this->fields['name'] = $name;

		if (!$DB->isSlave())
		{
			//Instanciate the affectation's rule
			$rule = new RightRuleCollection();

			//Process affectation rules :
			//we don't care about the function's return because all the datas are stored in session temporary
			if (isset($this->fields["_groups"]))
				$groups = $this->fields["_groups"];
			else
				$groups = array();
			$this->fields=$rule->processAllRules($groups,$this->fields,array("type"=>"MAIL","mail_server"=>$mail_method["ID"],"email"=>$this->fields["email"]));
		}
		return true;

	} // getFromIMAP()

	/**
	 * Blank passwords field of a user in the DB
	 * needed for external auth users
	 **/
	function blankPassword() {
		global $DB;
		if (!empty ($this->fields["name"])) {

			$query = "UPDATE glpi_users SET password='' , password_md5='' WHERE name='" . $this->fields["name"] . "'";
			$DB->query($query);
		}
	}

	/**
	 * Print a good title for user pages
	 *
	 *@return nothing (display)
	 **/
	function title() {
		global $LANG, $CFG_GLPI;

		$buttons = array ();
		$title = $LANG['Menu'][14];
		if (haveRight("user", "w")) {
			$buttons["user.form.php?new=1"] = $LANG['setup'][2];
			$title = "";

			if (haveRight("user_auth_method", "w")) {
				if (useAuthLdap()) {
					$buttons["user.form.php?new=1&amp;ext_auth=1"] = $LANG['setup'][125];
					$buttons["ldap.php"] = $LANG['setup'][3];

				} else if (useAuthExt()) {
					$buttons["user.form.php?new=1&amp;ext_auth=1"] = $LANG['setup'][125];
				}
			}
		}

		displayTitle($CFG_GLPI["root_doc"] . "/pics/users.png", $LANG['Menu'][14], $title, $buttons);
	}


	/**
	 * Is the current user have more right than the current one ?
	 *
	 *@param $ID Integer : Id of the user
	 *
	 *@return boolean : true if currrent user have the same right or more right
	 **/
	function currentUserHaveMoreRightThan($ID) {
		$user_prof=$this->getUserProfiles($ID);
		$prof=new Profile();
		return $prof->currentUserHaveMoreRightThan($user_prof);
	}

	/**
	 * Get user profiles (no entity association)
	 *
	 *@param $ID Integer : Id of the user
	 *
	 *@return array of the IDs of the profiles
	 **/
	function getUserProfiles($ID){
		global $DB;
		$prof=array();
		$query="SELECT DISTINCT glpi_users_profiles.FK_profiles
				FROM glpi_users_profiles
				WHERE glpi_users_profiles.FK_users='$ID'";

		$result=$DB->query($query);
		if ($DB->numrows($result)>0){
			while ($data=$DB->fetch_assoc($result)){
				$prof[$data['FK_profiles']]=$data['FK_profiles'];
			}
		}

		return $prof;
	}

	/**
	 * Print the user form
	 *
	 *@param $target form target
	 *@param $ID Integer : Id of the user
	 *@param $withtemplate boolean : template or basic item
	 *
	 *@return boolean : user found
	 **/
	function showForm($target, $ID, $withtemplate = '') {

		// Affiche un formulaire User
		global $CFG_GLPI, $LANG;

		if ($ID != $_SESSION["glpiID"] && !haveRight("user", "r"))
			return false;

		$canedit = haveRight("user", "w");
		$canread = haveRight("user", "r");

		$caneditpassword=$this->currentUserHaveMoreRightThan($ID);

		$spotted = false;
		$use_cache=true;
		if (empty ($ID)) {
			$use_cache=false;
			if ($this->getEmpty()){
				$spotted = true;
			}
		} else {
			if ($this->getFromDB($ID)){
				$entities=getUserEntities($ID);
				$view_all=isViewAllEntities();
				if (haveAccessToOneOfEntities($entities)||$view_all){
					$spotted = true;
				}
				$strict_entities=getUserEntities($ID,false);
				if (!haveAccessToOneOfEntities($strict_entities)&&!$view_all){
					$canedit=false;
				}
			}
		}
		if ($spotted) {

			$extauth = ! ($this->fields["auth_method"]==AUTH_DB_GLPI
				|| ($this->fields["auth_method"]==NOT_YET_AUTHENTIFIED
						&& (!empty ($this->fields["password"]) || !empty ($this->fields["password_md5"])))
				);

			$this->showTabs($ID, $withtemplate,$_SESSION['glpi_tab']);

			echo "<form method='post' name=\"user_manager\" action=\"$target\">";
			echo "<div class='center' id='tabsbody' >";

			if (empty ($ID)) {
				echo "<input type='hidden' name='FK_entities' value='" . $_SESSION["glpiactive_entity"] . "'>";
				echo "<input type='hidden' name='auth_method' value='1'>";
			}
			echo "<table class='tab_cadre_fixe'>";
			echo "<tr><th colspan='4'>" . $LANG['common'][34] . " : " . $this->fields["name"] . "&nbsp;";
			echo "<a href='" . $CFG_GLPI["root_doc"] . "/front/user.vcard.php?ID=$ID'>" . $LANG['common'][46] . "</a>";
			echo "</th></tr>";
			echo "<tr class='tab_bg_1'>";
			echo "<td class='center'>" . $LANG['setup'][18] . "</td>";
			// si on est dans le cas d'un ajout , cet input ne doit plus �re hiden
			if ($this->fields["name"] == "") {
				echo "<td><input  name='name' value=\"" . $this->fields["name"] . "\">";
				echo "</td>";
				// si on est dans le cas d'un modif on affiche la modif du login si ce n'est pas une auth externe
			} else {
				if (!empty ($this->fields["password_md5"])||$this->fields["auth_method"]==AUTH_DB_GLPI) {
					echo "<td>";
					autocompletionTextField("name", "glpi_users", "name", $this->fields["name"], 40);
				} else {
					echo "<td class='center'><strong>" . $this->fields["name"] . "</strong>";
					echo "<input type='hidden' name='name' value=\"" . $this->fields["name"] . "\">";
				}

				echo "<input type='hidden' name='ID' value=\"" . $this->fields["ID"] . "\">";

				echo "</td>";
			}


			//do some rights verification
			if (haveRight("user", "w")) {
				if ( (!$extauth || empty($ID)) && $caneditpassword) {

					echo "<td class='center'>" . $LANG['setup'][19] . ":</td><td><input type='password' name='password' value='' size='20'></td></tr>";
				} else {
					echo "<td colspan='2'>&nbsp;</td></tr>";
				}
			} else
				echo "<td colspan='2'>&nbsp;</td></tr>";

			if (!$use_cache||!($CFG_GLPI["cache"]->start($ID . "_" . $_SESSION['glpilanguage'], "GLPI_" . $this->type))) {
				echo "<tr class='tab_bg_1'><td class='center'>" . $LANG['common'][48] . ":</td><td>";
				autocompletionTextField("realname", "glpi_users", "realname", $this->fields["realname"], 40);
				echo "</td>";
				echo "<td class='center'>" . $LANG['common'][43] . ":</td><td>";
				autocompletionTextField("firstname", "glpi_users", "firstname", $this->fields["firstname"], 40);
				echo "</td></tr>";

				echo "<tr class='tab_bg_1'><td class='center'>" . $LANG['common'][42] . ":</td><td>";
				autocompletionTextField("mobile", "glpi_users", "mobile", $this->fields["mobile"], 40);
				echo "</td>";
				echo "<td class='center'>" . $LANG['setup'][14] . ":</td><td>";
				autocompletionTextField("email_form", "glpi_users", "email", $this->fields["email"], 40);
				if (!empty($ID)&&!isValidEmail($this->fields["email"])){
					echo "<span class='red'>&nbsp;".$LANG['mailing'][110]."</span>";
				}
				echo "</td></tr>";

				echo "<tr class='tab_bg_1'><td class='center'>" . $LANG['help'][35] . ":</td><td>";
				autocompletionTextField("phone", "glpi_users", "phone", $this->fields["phone"], 40);
				echo "</td>";
				echo "<td class='center'>" . $LANG['help'][35] . " 2:</td><td>";
				autocompletionTextField("phone2", "glpi_users", "phone2", $this->fields["phone2"], 40);
				echo "</td></tr>";

				echo "<tr class='tab_bg_1'><td class='center'>" . $LANG['common'][15] . ":</td><td>";
				if (!empty($ID)){
					if (count($entities)>0){
						dropdownValue("glpi_dropdown_locations", "location", $this->fields["location"],1,$entities);
					} else {
						echo "&nbsp;";
					}
				} else {
					if (!isMultiEntitiesMode()){
						// Display all locations : only one entity
						dropdownValue("glpi_dropdown_locations", "location", $this->fields["location"],1);
					} else {
						echo "&nbsp;";
					}
				}
				echo "</td>";
				echo "<td class='center'>".$LANG['common'][60]."</td><td>";
				dropdownYesNo('active',$this->fields['active']);
				echo "</td></tr>";

				echo "<tr class='tab_bg_1'><td class='center'>" . $LANG['users'][1] . "</td><td>";
					dropdownValue("glpi_dropdown_user_titles","title",$this->fields["title"],1,-1);

				echo "<td class='center'>" . $LANG['users'][2] . "</td><td>";
					dropdownValue("glpi_dropdown_user_types","type",$this->fields["type"],1,-1);
				echo "</td></tr>";

				echo "<tr class='tab_bg_1' align='center'><td>" . $LANG['common'][25] . ":</td><td colspan='3'><textarea  cols='70' rows='3' name='comments' >" . $this->fields["comments"] . "</textarea></td>";
				echo "</tr>";

				//Authentications informations : auth method used and server used
				//don't display is creation of a new user'
				if (!empty ($ID)) {
					if (haveRight("user_auth_method", "r")){
						echo "<tr class='tab_bg_1' align='center'><td>" . $LANG['login'][10] . ":</td><td class='center'>";

						echo getAuthMethodName($this->fields["auth_method"], $this->fields["id_auth"], 1);

						echo "</td><td align='center' colspan='2'></td>";
						echo "</tr>";
					}

					echo "<tr class='tab_bg_1' align='center'><td>" . $LANG['login'][24] . ":</td><td class='center'>";
					if (!empty($this->fields["date_mod"])){
						echo convDateTime($this->fields["date_mod"]);
					}
					echo "</td><td>" . $LANG['login'][0] . ":</td><td>";

					if (!empty($this->fields["last_login"])){
						echo convDateTime($this->fields["last_login"]);
					}

					echo "</td>";
					echo "</tr>";

				}
				if ($use_cache){
					$CFG_GLPI["cache"]->end();
				}
			}

			if ($canedit){
				if ($this->fields["name"] == "") {
					echo "<tr>";
					echo "<td class='tab_bg_2' valign='top' colspan='4' align='center'>";
					echo "<input type='submit' name='add' value=\"" . $LANG['buttons'][8] . "\" class='submit'>";
					echo "</td>";
					echo "</tr>";
				} else {
					echo "<tr>";
					echo "<td class='tab_bg_2' valign='top' align='center' colspan='2'>";
					echo "<input type='submit' name='update' value=\"" . $LANG['buttons'][7] . "\" class='submit' >";
					echo "</td>";
					echo "<td class='tab_bg_2' valign='top' align='center' colspan='2'>\n";
					if (!$this->fields["deleted"]){
						echo "<input type='submit' name='delete' onclick=\"return confirm('" . $LANG['common'][50] . "')\" value=\"".$LANG['buttons'][6]."\" class='submit'>";
					 }else {
						echo "<input type='submit' name='restore' value=\"".$LANG['buttons'][21]."\" class='submit'>";

						echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$LANG['buttons'][22]."\" class='submit'>";
					}

					echo "</td>";
					echo "</tr>";
				}
			}
			echo "</table></div></form>";

			echo "<div id='tabcontent'></div>";
			echo "<script type='text/javascript'>loadDefaultTab();</script>";
			return true;
		} else {
			echo "<div class='center'><strong>".$LANG['common'][54]."</strong></div>";
			return false;
		}
	}

	/**
	 * Print the user preference form
	 *
	 *@param $target form target
	 *@param $ID Integer : Id of the user
	 *
	 *@return boolean : user found
	 **/
	function showMyForm($target, $ID) {

		// Affiche un formulaire User
		global $CFG_GLPI, $LANG,$PLUGIN_HOOKS;

		if ($ID != $_SESSION["glpiID"])
			return false;

		if ($this->getFromDB($ID)) {

			$auth_method = $this->getAuthMethodsByID();

			$extauth = ! ($this->fields["auth_method"]==AUTH_DB_GLPI
				|| ($this->fields["auth_method"]==NOT_YET_AUTHENTIFIED
						&& (!empty ($this->fields["password"]) || !empty ($this->fields["password_md5"])))
				);


			// No autocopletion :
			$save_autocompletion=$CFG_GLPI["ajax_autocompletion"];
			$CFG_GLPI["ajax_autocompletion"]=false;

			echo "<div class='center'>";
			echo "<form method='post' name=\"user_manager\" action=\"$target\"><table class='tab_cadre_fixe'>";
			echo "<tr><th colspan='2'>" . $LANG['common'][34] . " : " . $this->fields["name"] . "</th></tr>";

			echo "<tr class='tab_bg_1'>";
			echo "<td class='center'>" . $LANG['setup'][18] . "</td>";
			echo "<td class='center'><strong>" . $this->fields["name"] . "</strong>";
			echo "<input type='hidden' name='name' value=\"" . $this->fields["name"] . "\">";
			echo "<input type='hidden' name='ID' value=\"" . $this->fields["ID"] . "\">";
			echo "</td></tr>";

			//do some rights verification
			if (!$extauth && haveRight("password_update", "1")) {
				echo "<tr class='tab_bg_1'><td class='center'>" . $LANG['setup'][19] . "</td><td><input type='password' name='password' value='' size='30' /></td></tr>";
			}

			echo "<tr class='tab_bg_1'><td class='center'>" . $LANG['common'][48] . "</td><td>";
			if ($extauth && isset ($auth_method['ldap_field_email']) && !empty ($auth_method['ldap_field_realname'])) {
				echo $this->fields["realname"];
			} else {
				autocompletionTextField("realname", "glpi_users", "realname", $this->fields["realname"], 40);
			}
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td class='center'>" . $LANG['common'][43] . "</td><td>";
			if ($extauth && isset ($auth_method['ldap_field_firstname']) && !empty ($auth_method['ldap_field_firstname'])) {
				echo $this->fields["firstname"];
			} else {
				autocompletionTextField("firstname", "glpi_users", "firstname", $this->fields["firstname"], 40);
			}
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td class='center'>" . $LANG['setup'][14] . "</td><td>";
			if ($extauth && isset ($auth_method['ldap_field_email']) && !empty ($auth_method['ldap_field_email'])) {
				echo $this->fields["email"];
			} else {
				autocompletionTextField("email_form", "glpi_users", "email", $this->fields["email"], 40);
				if (!isValidEmail($this->fields["email"])){
					echo "<span class='red'>".$LANG['mailing'][110]."</span>";
				}
			}

			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td class='center'>" . $LANG['help'][35] . "</td><td>";
			if ($extauth && isset ($auth_method['ldap_field_phone']) && !empty ($auth_method['ldap_field_phone'])) {
				echo $this->fields["phone"];
			} else {
				autocompletionTextField("phone", "glpi_users", "phone", $this->fields["phone"], 40);
			}
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td class='center'>" . $LANG['help'][35] . " 2</td><td>";
			if ($extauth && isset ($auth_method['ldap_field_phone2']) && !empty ($auth_method['ldap_field_phone2'])) {
				echo $this->fields["phone2"];
			} else {
				autocompletionTextField("phone2", "glpi_users", "phone2", $this->fields["phone2"], 40);
			}
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td class='center'>" . $LANG['common'][42] . "</td><td>";
			if ($extauth && isset ($auth_method['ldap_field_mobile']) && !empty ($auth_method['ldap_field_mobile'])) {
				echo $this->fields["mobile"];
			} else {
				autocompletionTextField("mobile", "glpi_users", "mobile", $this->fields["mobile"], 40);
			}
			echo "</td></tr>";

         if (! GLPI_DEMO_MODE){
            echo "<tr class='tab_bg_1'><td class='center'>" . $LANG['setup'][41] . " </td><td>";
			   /// Use sesion variable because field in table may be null if same of the global config
			   dropdownLanguages("language", $_SESSION["glpilanguage"]);
   			echo "</td></tr>";
         }


			if (count($_SESSION['glpiprofiles'])>1){
				echo "<tr class='tab_bg_1'><td class='center'>" . $LANG['profiles'][13] . "</td><td>";
				$options=array(0=>'----');

				foreach ($_SESSION['glpiprofiles'] as $ID => $prof){
					$options[$ID]=$prof['name'];
				}
				dropdownArrayValues("FK_profiles",$options,$this->fields["FK_profiles"]);
				echo "</td></tr>";
			}

			if (count($_SESSION['glpiactiveentities'])>1){
				echo "<tr class='tab_bg_1'><td class='center'>" . $LANG['profiles'][37] . "</td><td>";
				dropdownValue("glpi_entities","FK_entities",$_SESSION["glpidefault_entity"],1,$_SESSION['glpiactiveentities']);
				echo "</td></tr>";
			}

			if (haveRight("config", "w")){
				echo "<tr class='tab_bg_1'>";

				echo "<td class='center'>" . $LANG['setup'][138] . " </td><td><select name=\"use_mode\">";
				echo "<option value=\"" . NORMAL_MODE . "\" " . ($this->fields["use_mode"] == NORMAL_MODE ? " selected " : "") . " >" . $LANG['setup'][135] . " </option>";
				echo "<option value=\"" . TRANSLATION_MODE . "\" " . ($this->fields["use_mode"] == TRANSLATION_MODE ? " selected " : "") . " >" . $LANG['setup'][136] . " </option>";
				echo "<option value=\"" . DEBUG_MODE . "\" " . ($this->fields["use_mode"] == DEBUG_MODE ? " selected " : "") . " >" . $LANG['setup'][137] . " </option>";
				echo "</select></td>";
				echo "</tr>";
			}

			echo "<tr>";
			echo "<td class='tab_bg_2' valign='top' align='center' colspan='2'>";
			echo "<input type='submit' name='update' value=\"" . $LANG['buttons'][7] . "\" class='submit' >";
			echo "</td>";
			echo "</tr>";

			echo "</table></form></div>";
			$CFG_GLPI["ajax_autocompletion"]=$save_autocompletion;
			return true;
		}

		return false;
	}

	///Get all the authentication method parameters for the current user
	function getAuthMethodsByID() {
		return getAuthMethodsByID($this->fields["auth_method"], $this->fields["id_auth"]);
	}


	function pre_updateInDB($input,$updates,$oldvalues=array()) {
      global $DB,$LANG;

      if (($key=array_search('name',$updates))!==false){
         /// Check if user does not exists
         $query="SELECT * FROM glpi_users WHERE name='".$input['name']."' AND ID <> '".$input['ID']."';";
         $result=$DB->query($query);
         if ($DB->numrows($result)>0){
            unset($updates[$key]);
            /// For displayed message
            $this->fields['name']=$oldvalues['name'];
            addMessageAfterRedirect($LANG['setup'][614],false,ERROR);
         }
      }


		// Security system except for login update
		if (isset ($_SESSION["glpiID"]) && !haveRight("user", "w") && !strpos($_SERVER['PHP_SELF'],"login.php")) {
			if ($_SESSION["glpiID"] == $input['ID']) {
				$ret = $updates;

				if (isset($this->fields["auth_method"])){
					// extauth ldap case
					if ($_SESSION["glpiextauth"] && ($this->fields["auth_method"] == AUTH_LDAP || isAlternateAuthWithLdap($this->fields["auth_method"]))) {
						$auth_method = getAuthMethodsByID($this->fields["auth_method"], $this->fields["id_auth"]);
						if (count($auth_method)){
							$fields=getLDAPSyncFields($auth_method);
							foreach ($fields as $key => $val){
								if (!empty ($val)){
									unset ($ret[$key]);
								}
							}
						}
					}
					// extauth imap case
					if (isset($this->fields["auth_method"])&&$this->fields["auth_method"] == AUTH_MAIL){
						unset ($ret["email"]);
					}

					unset ($ret["active"]);
					unset ($ret["comments"]);
				}

				return array($input,$ret);
			} else {
				return array($input,array());
			}
		}

		return array($input,$updates);
	}


	/**
	 * Delete dynamic profiles for the current user
	 **/
	function purgeDynamicProfiles()
	{
		global $DB;

		//Purge only in case of connection to the master mysql server
		if (!$DB->isSlave())
		{
			$sql = "DELETE FROM glpi_users_profiles WHERE FK_users='".$this->fields["ID"]."' AND dynamic=1";
			$DB->query($sql);
		}
	}
}


?>
