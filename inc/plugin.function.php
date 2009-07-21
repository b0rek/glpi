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
	//return;
	$plugin=new Plugin();

	$plugin->checkStates();
	$plugins=$plugin->find('state='.PLUGIN_ACTIVATED);
	
	$_SESSION["glpi_plugins"]=array();

	if (count($plugins)){
		foreach ($plugins as $ID => $plug){
			$_SESSION["glpi_plugins"][$ID]=$plug['directory'];
		}
	}
}
/**
 * Init a plugin including setup.php file 
 * launching plugin_init_NAME function  after checking compatibility 
 * 
 * @param $name Name of hook to use
 * @param $withhook boolean to load hook functions
 * 
 * @return nothing
 */
function usePlugin ($name, $withhook=false) {
	global $CFG_GLPI, $PLUGIN_HOOKS,$LANG,$LOADED_PLUGINS;

	if (file_exists(GLPI_ROOT . "/plugins/$name/setup.php")) {
		include_once(GLPI_ROOT . "/plugins/$name/setup.php");
		if (!isset($LOADED_PLUGINS[$name])){
			loadPluginLang($name);

			$function = "plugin_init_$name";
	
			if (function_exists($function)) {
				$function();
				$LOADED_PLUGINS[$name]=$name;
			}
		}
	}
	if ($withhook && file_exists(GLPI_ROOT . "/plugins/$name/hook.php")) {
		include_once(GLPI_ROOT . "/plugins/$name/hook.php");
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
		foreach ($PLUGIN_HOOKS[$name] as $plug => $function) {
			if (file_exists(GLPI_ROOT . "/plugins/$plug/hook.php")) {
				include_once(GLPI_ROOT . "/plugins/$plug/hook.php");
			}
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

		foreach ($PLUGIN_HOOKS[$name] as $plug => $function) {
			if (file_exists(GLPI_ROOT . "/plugins/$plug/hook.php")) {
				include_once(GLPI_ROOT . "/plugins/$plug/hook.php");
			}
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
 * This function executes a hook for 1 plugin.
 * @param $plugname Name of the plugin
 * @param $suffixe_of_function to be called
 * @param other params passed to the function
 * 
 * @return mixed $data
 */
function doOneHook() {
	
	$args=func_get_args();
	$plugname = array_shift($args);
	$function = "plugin_" . $plugname . "_" . array_shift($args);

	if (file_exists(GLPI_ROOT . "/plugins/$plugname/hook.php")) {
		include_once(GLPI_ROOT . "/plugins/$plugname/hook.php");
	}
	if (function_exists($function)) {
		return call_user_func_array($function,$args);
	}	
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
		if (isset($PLUGIN_HOOKS["headings_action"])&&is_array($PLUGIN_HOOKS["headings_action"])&&count($PLUGIN_HOOKS["headings_action"])){
			foreach ($PLUGIN_HOOKS["headings_action"] as $plug => $function){
				if (file_exists(GLPI_ROOT . "/plugins/$plug/hook.php")) {
					include_once(GLPI_ROOT . "/plugins/$plug/hook.php");
				}

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
			}
		}
		return true;

	} else {
		//$split=explode("_",$onglet);
		//if (count($split)==2){
		if (preg_match("/^(.*)_([0-9]*)$/",$onglet,$split)) {
			$plug = $split[1];
			$ID_onglet = $split[2];

			if (isset($PLUGIN_HOOKS["headings_action"][$plug])){
				if (file_exists(GLPI_ROOT . "/plugins/$plug/hook.php")) {
					include_once(GLPI_ROOT . "/plugins/$plug/hook.php");
				}

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
 * Display plugin headgsin for a device type / WILL BE DELETED : use displayPluginTabs instead
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
			if (file_exists(GLPI_ROOT . "/plugins/$plug/hook.php")) {
				include_once(GLPI_ROOT . "/plugins/$plug/hook.php");
			}

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

			echo "<li".(strstr($actif,$plug)?" class='actif'":"")." style='position:relative;'  onmouseout=\"cleanhide('onglet_plugins')\" onmouseover=\"cleandisplay('onglet_plugins')\"><a href='#'>".$LANG['common'][29]."</a>";

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
 * Display plugin headgsin for a device type
 * @param $target page to link 
 * @param $type ID of the device type or "central" or "prefs"
 * @param $ID ID of the device
 * @param $withtemplate is the item display like a template ? 
 * @return Array of tabs (sorted)
 */
function getPluginTabs($target,$type,$ID,$withtemplate){
	global $PLUGIN_HOOKS,$LANG,$INFOFORM_PAGES,$CFG_GLPI;
	$template="";
	if(!empty($withtemplate)){
		$template="&withtemplate=$withtemplate";
	}
	$display_onglets=array();

	switch ($type){
		case "central":
			$tabpage="ajax/central.tabs.php";
		break;
		case "prefs":
			$tabpage="ajax/preference.tabs.php";
		break;
		case "profile":
			$tabpage="ajax/profile.tabs.php";
		break;
		case "mailing":
			$tabpage="ajax/mailing.tabs.php";
		break;
		default:
			$patterns[0] = '/front/';
			$patterns[1] = '/form/';
			$replacements[0] = 'ajax';
			$replacements[1] = 'tabs';
			$tabpage=preg_replace($patterns, $replacements, $INFOFORM_PAGES[$type]);
		break;
	}
	$active=false;
	$tabid=0;
	$tabs=array();
	$order=array();
	if (isset($PLUGIN_HOOKS["headings"]) && is_array($PLUGIN_HOOKS["headings"])) {
		foreach ($PLUGIN_HOOKS["headings"] as $plug => $function) {
			if (file_exists(GLPI_ROOT . "/plugins/$plug/hook.php")) {
				include_once(GLPI_ROOT . "/plugins/$plug/hook.php");
			}

			if (function_exists($function)) {
				$onglet=$function($type,$ID,$withtemplate);				

				if (is_array($onglet)&&count($onglet)){
					foreach ($onglet as $key => $val){
						$key=$plug."_".$key;

						$tabs[$key]=array('title'=>$val,
						'url'=>$CFG_GLPI['root_doc']."/$tabpage",
						'params'=>"target=$target&type=".$type."&glpi_tab=$key&ID=$ID$template");
						$order[$key]=$val;
					}
				}
			}
		}
		// Order plugin tab
		if (count($tabs)){
			asort($order);
			foreach ($order as $key => $val){
				$order[$key]=$tabs[$key];
			}
		}
	}

	return $order;

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
			
			$tab = doOneHook($plug,'getDropdown');
			if (is_array($tab)){ 
				$function="plugin_version_$plug";			
				$name=$function();
				$dps=array_merge($dps,array($name['name']=>$tab));
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
			if (file_exists(GLPI_ROOT . "/plugins/$plug/hook.php")) {
				include_once(GLPI_ROOT . "/plugins/$plug/hook.php");
			}

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
			if (file_exists(GLPI_ROOT . "/plugins/$plug/hook.php")) {
				include_once(GLPI_ROOT . "/plugins/$plug/hook.php");
			}

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
 * DEPRECATED Define a new device type used in a plugin
 * 
 * TODO : to delete ASAP (all plugin switch to registerPluginType)
 * @param $plugin plugin of the device type
 * @param $name name of the device_type to define the constant
 * @param $ID number used as constant
 * @param $class class defined for manipulate this device type
 * @param $table table describing the device
 * @param $formpage Form page for the item
 * @param $typename string defining the name of the new type (used in CommonItem)
 * @param $recursive boolean
 * 
 * @return nothing
function pluginNewType($plugin,$name,$ID,$class,$table,$formpage='',$typename='',$recursive=false){
	global $PLUGIN_HOOKS,$LINK_ID_TABLE,$INFOFORM_PAGES,$CFG_GLPI; 

	trigger_error("pluginNewType is deprecated, use registerPluginType instead");
	
	if (!defined($name)) {
		define($name,$ID);
		$LINK_ID_TABLE[$ID]=$table;
		if (!empty($formpage)) {
			$INFOFORM_PAGES[$ID]="plugins/$plugin/$formpage";
		}
		$PLUGIN_HOOKS['plugin_types'][$ID]=$plugin;
		$PLUGIN_HOOKS['plugin_typenames'][$ID]=$typename;
		$PLUGIN_HOOKS['plugin_classes'][$ID]=$class;
		
		if ($recursive) {
			$CFG_GLPI["recursive_type"][$ID]=$table;
		}
	}
}
 */

/**
 * Define a new device type used in a plugin
 * 
 * @param $plugin plugin of the device type
 * @param $name name of the device_type to define the constant
 * @param $ID number used as constant
 * @param $attrib Array of attributes, a hashtable with index in
 * 	(classname, tablename, typename, formpage, searchpage, reservation_types,
 *   deleted_tables, specif_entities_tables, recursive_type, template_tables)
 * 
 * @return nothing
 */
function registerPluginType($plugin,$name,$ID,$attrib){
	global $PLUGIN_HOOKS,$LINK_ID_TABLE,$INFOFORM_PAGES,$SEARCH_PAGES,$CFG_GLPI;
	
	if (!defined($name)) {

		define($name,$ID);
		$PLUGIN_HOOKS['plugin_types'][$ID]=$plugin;
		
		if (isset($attrib['classname']) && !empty($attrib['classname'])) {
			$PLUGIN_HOOKS['plugin_classes'][$ID]=$attrib['classname'];
		}
		if (isset($attrib['typename']) && !empty($attrib['typename'])) {
			$PLUGIN_HOOKS['plugin_typenames'][$ID]=$attrib['typename'];
		}
		if (isset($attrib['formpage']) && !empty($attrib['formpage'])) {
			$INFOFORM_PAGES[$ID]="plugins/$plugin/".$attrib['formpage'];
		}
		if (isset($attrib['searchpage']) && !empty($attrib['searchpage'])) {
			$SEARCH_PAGES[$ID]="plugins/$plugin/".$attrib['searchpage'];
		}
		if (isset($attrib['tablename']) && !empty($attrib['tablename'])) {
			$LINK_ID_TABLE[$ID] = $attrib['tablename'];

			if (isset($attrib['recursive_type']) && $attrib['recursive_type']) {
				$CFG_GLPI["recursive_type"][$ID] = $attrib['tablename'];
			}
			if (isset($attrib['deleted_tables']) && $attrib['deleted_tables']) {
				array_push($CFG_GLPI["deleted_tables"], $attrib['tablename']);
			}
			if (isset($attrib['specif_entities_tables']) && $attrib['specif_entities_tables']) {
				array_push($CFG_GLPI["specif_entities_tables"], $attrib['tablename']);
			}
			if (isset($attrib['template_tables']) && $attrib['template_tables']) {
				array_push($CFG_GLPI["template_tables"], $attrib['tablename']);
			}
		} // is set tablename
		
		foreach (array('reservation_types','infocom_types','linkuser_types','linkgroup_types',
					'massiveaction_noupdate_types','massiveaction_nodelete_types', 'doc_types','helpdesk_visible_types') as $att) {
			if (isset($attrib[$att]) && $attrib[$att]) {
				array_push($CFG_GLPI[$att], $ID);
			}
		}
	} // not already defined
} 

function loadPluginLang($name){
	global $CFG_GLPI,$LANG;

	if (isset($_SESSION['glpilanguage']) 
		&&file_exists(GLPI_ROOT . "/plugins/$name/locales/".$CFG_GLPI["languages"][$_SESSION['glpilanguage']][1])){
		include_once (GLPI_ROOT . "/plugins/$name/locales/".$CFG_GLPI["languages"][$_SESSION['glpilanguage']][1]);
	} else if (file_exists(GLPI_ROOT . "/plugins/$name/locales/".$CFG_GLPI["languages"][$CFG_GLPI["language"]][1])){
		include_once (GLPI_ROOT . "/plugins/$name/locales/".$CFG_GLPI["languages"][$CFG_GLPI["language"]][1]);
	} else if (file_exists(GLPI_ROOT . "/plugins/$name/locales/en_GB.php")){
		include_once (GLPI_ROOT . "/plugins/$name/locales/en_GB.php");
	} else if (file_exists(GLPI_ROOT . "/plugins/$name/locales/fr_FR.php")){
		include_once (GLPI_ROOT . "/plugins/$name/locales/fr_FR.php");
	}
}

?>
