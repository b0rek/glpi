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



$NEEDED_ITEMS=array("user","profile","group","setup","tracking","computer","printer","networking","peripheral","monitor","software","enterprise","phone", "reservation","ldap","entity","rulesengine","rule.right");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if(empty($_GET["ID"])) $_GET["ID"] = "";

if (!isset($_GET["start"])) {
	$_GET["start"]=0;
}

if (!isset($_GET["sort"])) $_GET["sort"]="";
if (!isset($_GET["order"])) $_GET["order"]="";



$user=new User();
if (empty($_GET["ID"])&&isset($_GET["name"])){

	$user->getFromDBbyName($_GET["name"]);
	glpi_header($CFG_GLPI["root_doc"]."/front/user.form.php?ID=".$user->fields['ID']);
}

if(empty($_GET["name"])) $_GET["name"] = "";

if (isset($_POST["add"])) {
	checkRight("user","w");

	// Pas de nom pas d'ajout	
	if (!empty($_POST["name"])){
		$newID=$user->add($_POST);
		logEvent($newID, "users", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][20]." ".$_POST["name"].".");
	}
	glpi_header($_SERVER['HTTP_REFERER']);
} else if (isset($_POST["delete"])) {
	checkRight("user","w");

	$user->delete($_POST);
	logEvent(0,"users", 4, "setup", $_SESSION["glpiname"]."  ".$LANG['log'][22]." ".$_POST["ID"].".");
	glpi_header($CFG_GLPI["root_doc"]."/front/user.php");
} else if (isset($_POST["restore"]))
{
	checkRight("user","w");
	$user->restore($_POST);
	logEvent($_POST["ID"],"users", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][23]);
	glpi_header($CFG_GLPI["root_doc"]."/front/user.php");
}
else if (isset($_POST["purge"]))
{
	checkRight("user","w");
	$user->delete($_POST,1);
	
	logEvent($_POST["ID"], "users", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][24]);
	glpi_header($CFG_GLPI["root_doc"]."/front/user.php");

} else if (isset ($_POST["force_ldap_resynch"]))
{
	checkSeveralRightsAnd(array("user"=>"w", "user_auth_method"=>"w"));
	
	$user->getFromDB($_POST["ID"]);
	ldapImportUserByServerId($user->fields["name"],true,$user->fields["id_auth"],true);
	glpi_header($_SERVER['HTTP_REFERER']);
} else if (isset($_POST["update"])) {
	checkRight("user","w");

	$user->update($_POST);
	logEvent(0,"users", 5, "setup", $_SESSION["glpiname"]."  ".$LANG['log'][21]."  ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
} else if (isset($_POST["addgroup"]))
{
	checkRight("user","w");

	addUserGroup($_POST["FK_users"],$_POST["FK_groups"]);

	logEvent($_POST["FK_users"], "users", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][48]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["deletegroup"]))
{
	checkRight("user","w");
	if (count($_POST["item"]))
		foreach ($_POST["item"] as $key => $val)
			deleteUserGroup($key);

	logEvent($_POST["FK_users"], "users", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][49]);
	glpi_header($_SERVER['HTTP_REFERER']);
} else if (isset($_POST["addright"]))
{
	checkRight("user","w");

	$prof=new Profile();
	if ($prof->currentUserHaveMoreRightThan(array($_POST['FK_profiles']=>$_POST['FK_profiles']))){
		addUserProfileEntity($_POST);
		logEvent($_POST["FK_users"], "users", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][61]);
	}
	
	glpi_header($_SERVER['HTTP_REFERER']);
} else if (isset($_POST["deleteright"]))
{
	checkRight("user","w");

	if (isset($_POST["item"])&&count($_POST["item"])){
		foreach ($_POST["item"] as $key => $val){
			deleteUserProfileEntity($key);
		}
		logEvent($_POST["FK_users"], "users", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][62]);
	}

	glpi_header($_SERVER['HTTP_REFERER']);
}elseif (isset($_POST["switch_auth_internal"]))
{
	checkSeveralRightsAnd(array("user"=>"w", "user_auth_method"=>"w"));
	
	$user = new User;
	$input["ID"]=$_POST["ID"];
	$input["auth_method"]=AUTH_DB_GLPI;
	$input["id_auth"]=0;
	$user->update($input);
	glpi_header($_SERVER['HTTP_REFERER']);
}elseif (isset($_POST["switch_auth_ldap"]))
{
	checkSeveralRightsAnd(array("user"=>"w", "user_auth_method"=>"w"));
	
	$user = new User;
	$input["ID"]=$_POST["ID"];
	$input["auth_method"]=AUTH_LDAP;
	$input["id_auth"]=$_POST["id_auth"];
	$user->update($input);
	glpi_header($_SERVER['HTTP_REFERER']);	
}elseif (isset($_POST["switch_auth_mail"]))
{
	checkSeveralRightsAnd(array("user"=>"w", "user_auth_method"=>"w"));
	
	$user = new User;
	$input["ID"]=$_POST["ID"];
	$input["auth_method"]=AUTH_MAIL;
	$input["id_auth"]=$_POST["id_auth"];
	$user->update($input);
	glpi_header($_SERVER['HTTP_REFERER']);	
} else {


	if (!isset($_SESSION['glpi_tab'])) $_SESSION['glpi_tab']=1;
	if (isset($_GET['onglet'])) {
		$_SESSION['glpi_tab']=$_GET['onglet'];
	}


	if (!isset($_GET["ext_auth"])){
		checkRight("user","r");

		commonHeader($LANG['title'][13],$_SERVER['PHP_SELF'],"admin","user");

		$user->showForm($_SERVER['PHP_SELF'],$_GET["ID"]);

		commonFooter();
	} else {
		checkRight("user","w");
		
		if (isset($_GET['add_ext_auth_ldap'])){
			if (isset($_GET['login'])&&!empty($_GET['login'])){
				import_user_from_ldap_servers($_GET['login']);
			}
			glpi_header($_SERVER['HTTP_REFERER']);
		}
		if (isset($_GET['add_ext_auth_simple'])){
			if (isset($_GET['login'])&&!empty($_GET['login'])){
				$newID=$user->add(array('name'=>$_GET['login'],'_extauth'=>1,'add'=>1));
				logEvent($newID, "users", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][20]." ".$_GET['login'].".");
			}
			glpi_header($_SERVER['HTTP_REFERER']);
		}

		commonHeader($LANG['title'][13],$_SERVER['PHP_SELF'],"admin","user");
		showAddExtAuthUserForm($_SERVER['PHP_SELF']);
		commonFooter();
	}
}





?>
