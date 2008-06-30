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

// Based on cacti plugin system
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}



global $PLUGIN_HOOKS;
$PLUGIN_HOOKS = array();
global $CFG_GLPI_PLUGINS;
$CFG_GLPI_PLUGINS = array();


/**
 * Init plugins list reading plugins directory
 * @return nothing
 */
function initPlugins(){

	$_SESSION["glpi_plugins"]=array();
	$dirplug=GLPI_ROOT."/plugins";
	$dh  = opendir($dirplug);
	while (false !== ($filename = readdir($dh))) {
		if ($filename!=".svn"&&$filename!="."&&$filename!=".."&&is_dir($dirplug."/".$filename)){
			$_SESSION["glpi_plugins"][]=$filename;
		}
	}

}
/**
 * Init a plugin including setup.php file 
 * launching plugin_init_NAME function  after checking compatibility 
 * 
 * @param $name Name of hook to use
 * @return nothing
 */
function usePlugin ($name) {
	global $CFG_GLPI, $PLUGIN_HOOKS;

	if (file_exists(GLPI_ROOT . "/plugins/$name/setup.php")) {
		include_once(GLPI_ROOT . "/plugins/$name/setup.php");

		$function = "plugin_version_$name";
		if (function_exists($function)) {
			$info=$function();
			
			if ((isset($info["minGlpiVersion"]) && $info["minGlpiVersion"]>GLPI_VERSION) ||
				(isset($info["maxGlpiVersion"]) && $info["maxGlpiVersion"]<GLPI_VERSION)) {
				
				// Note this to have the plugin listed (as incompatible) on config page.
				$PLUGIN_HOOKS["config_page"][$name]=false;
				
				return false;
			}
			
		}
		$function = "plugin_init_$name";

		if (function_exists($function)) {
			$function();
		}
	}
}

/**
 * This function executes a hook.
 * @param $name Name of hook to fire
 * @param $param Parameters if needed
 * @return mixed $data
 */
function doHook ($name,$param=NULL) {
	global $PLUGIN_HOOKS;
	if ($param==NULL){
		$data = func_get_args();
	} else {
		$data=$param;
	}

	if (isset($PLUGIN_HOOKS[$name]) && is_array($PLUGIN_HOOKS[$name])) {
		foreach ($PLUGIN_HOOKS[$name] as $function) {
			if (function_exists($function)) {
				$function($data);
			}
		}
	}

	/* Variable-length argument lists have a slight problem when */
	/* passing values by reference. Pity. This is a workaround.  */
	return $data;
}
/**
 * This function executes a hook.
 * @param $name Name of hook to fire
 * @param $parm Parameters
 * @return mixed $data
 */
function doHookFunction($name,$parm=NULL) {
	global $PLUGIN_HOOKS;
	$ret = $parm;

	if (isset($PLUGIN_HOOKS[$name])
			&& is_array($PLUGIN_HOOKS[$name])) {
		foreach ($PLUGIN_HOOKS[$name] as $function) {
			if (function_exists($function)) {
				$ret = $function($ret);
			}
		}
	}

	/* Variable-length argument lists have a slight problem when */
	/* passing values by reference. Pity. This is a workaround.  */
	return $ret;
}

/**
 * Display plugin actions for a device type
 * @param $type ID of the device type
 * @param $ID ID of the item
 * @param $onglet Heading corresponding of the datas to display
 * @param $withtemplate is the item display like a template ?
 * @return true if display have been done
 */
function displayPluginAction($type,$ID,$onglet,$withtemplate=0){
	global $PLUGIN_HOOKS;
	// Show all Case
	if ($onglet==-1){
		if (isset($PLUGIN_HOOKS["headings_action"])&&is_array($PLUGIN_HOOKS["headings_action"])&&count($PLUGIN_HOOKS["headings_action"]))	
			foreach ($PLUGIN_HOOKS["headings_action"] as $plug => $function)
				if (function_exists($function)){

					$actions=$function($type);

					if (is_array($actions)&&count($actions))
						foreach ($actions as $key => $action){
							if (function_exists($action)){
								echo "<br>";
								$action($type,$ID,$withtemplate);
							}	

						}
				}
		return true;

	} else {
		$split=split("_",$onglet);
		if (count($split)==2){
			list($plug,$ID_onglet)=$split;
			if (isset($PLUGIN_HOOKS["headings_action"][$plug])){
				$function=$PLUGIN_HOOKS["headings_action"][$plug];
				if (function_exists($function)){

					$actions=$function($type);

					if (isset($actions[$ID_onglet])&&function_exists($actions[$ID_onglet])){
						$function=$actions[$ID_onglet];
						$function($type,$ID,$withtemplate);
						return true;
					}	
				}
			}

		}
	}
	return false;
}
/**
 * Display plugin headgsin for a device type
 * @param $target page to link including ID
 * @param $type ID of the device type
 * @param $withtemplate is the item display like a template ?
 * @param $actif active onglet
 * @return true if display have been done
 */
function displayPluginHeadings($target,$type,$withtemplate,$actif){
	global $PLUGIN_HOOKS,$LANG;
	$template="";
	if(!empty($withtemplate)){
		$template="&amp;withtemplate=$withtemplate";
	}
	$display_onglets=array();
	if (isset($PLUGIN_HOOKS["headings"]) && is_array($PLUGIN_HOOKS["headings"])) {
		foreach ($PLUGIN_HOOKS["headings"] as $plug => $function) {

			if (function_exists($function)) {
				$onglet=$function($type,$withtemplate);

				if (is_array($onglet)&&count($onglet))
					foreach ($onglet as $key => $val)
						$display_onglets[$plug."_".$key]=$val;
				//echo "<li".(($actif==$plug."_".$key)?" class='actif'":"")."><a href='$target&amp;onglet=".$plug."_".$key."$template'>".$val."</a></li>";
			}
		}
		if (count($display_onglets)){
			echo "<li class='invisible'>&nbsp;</li>";

			echo "<li".(ereg($plug,$actif)?" class='actif'":"")." style='position:relative;'  onmouseout=\"cleanhide('onglet_plugins')\" onmouseover=\"cleandisplay('onglet_plugins')\"><a href='#'>".$LANG["common"][29]."</a>";

			echo "<div  id='onglet_plugins' ><dl>";
			foreach ($display_onglets as $key => $val){
				echo "<dt><a href='$target&amp;onglet=".$key.$template."'>".$val."</a></dt>";
			}
			echo "</dl></div>";
			echo "</li>";

		}

	} 

}

/**
 * Get cron jobs for plugins
 *
 * @return Array containing plugin cron jobs
 */
function getPluginsCronJobs(){ 
	global $PLUGIN_HOOKS; 
	$tasks=array(); 
	if (isset($PLUGIN_HOOKS["cron"]) && is_array($PLUGIN_HOOKS["cron"])) { 
		foreach ($PLUGIN_HOOKS["cron"] as $plug => $time) { 
			$tasks["plugin_".$plug]=$time; 
		} 
	} 
	return $tasks; 
}

/**
 * Get dropdowns for plugins
 *
 * @return Array containing plugin dropdowns
 */
function getPluginsDropdowns(){ 
	$dps=array();
	if (isset($_SESSION["glpi_plugins"]) && is_array($_SESSION["glpi_plugins"])) { 
		foreach ($_SESSION["glpi_plugins"] as  $plug) { 
			$function="plugin_version_$plug";
			$function2="plugin_".$plug."_getDropdown";
			if (function_exists($function2)) {
				$name=$function();
				$tab=$function2();
				if (is_array($tab)){ 
					$dps=array_merge($dps,array($name['name']=>$tab));
				}
			}
		} 
	} 
	return $dps;
} 
/**
 * Get database relations for plugins
 *
 * @return Array containing plugin database relations
 */
function getPluginsDatabaseRelations(){ 
	$dps=array();
	if (isset($_SESSION["glpi_plugins"]) && is_array($_SESSION["glpi_plugins"])) { 
		foreach ($_SESSION["glpi_plugins"] as $plug) { 
			$function2="plugin_".$plug."_getDatabaseRelations";
			if (function_exists($function2)) {
				$dps=array_merge($dps,$function2());
			}
		} 
	} 
	return $dps;
}

/**
 * Get search options for plugins
 *
 * @return Array containing plugin search options
 */
function getPluginSearchOption(){ 
	global $PLUGIN_HOOKS; 
	$sopt=array();
	if (isset($PLUGIN_HOOKS['plugin_types'])&&count($PLUGIN_HOOKS['plugin_types'])){
		$tab=array_unique($PLUGIN_HOOKS['plugin_types']);
		foreach ($tab as $plug){
			$function="plugin_".$plug."_getSearchOption";
			if (function_exists($function)) {
				$tmp=$function();
				if (count($tmp)){
					foreach ($tmp as $key => $val){
						if (!isset($sopt[$key])){
							$sopt[$key]=array();
						}
						$sopt[$key]+=$val;
					}
				}
			}
		}
	}
	return $sopt;
}
/**
 * Define a new device type used in a plugin
 * @param $plugin plugin of the device type
 * @param $name name of the device_type to define the constant
 * @param $ID number used as constant
 * @param $class class defined for manipulate this device type
 * @param $table table describing the device
 * @param $formpage Form page for the item
 * @param $typename string defining the name of the new type (used in CommonItem)
 * @return nothing
 */

function pluginNewType($plugin,$name,$ID,$class,$table,$formpage='',$typename=''){
	global $PLUGIN_HOOKS,$LINK_ID_TABLE,$INFOFORM_PAGES; 

	define($name,$ID);
	$LINK_ID_TABLE[$ID]=$table;
	$INFOFORM_PAGES[$ID]="plugins/$plugin/$formpage";
	$PLUGIN_HOOKS['plugin_types'][$ID]=$plugin;
	$PLUGIN_HOOKS['plugin_typenames'][$ID]=$typename;
	$PLUGIN_HOOKS['plugin_classes'][$ID]=$class;
}
?>
