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
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------

if(!defined('GLPI_ROOT')){
	define('GLPI_ROOT', '..');

	$NEEDED_ITEMS=array("rulesengine");
	include (GLPI_ROOT . "/inc/includes.php");
}


if (isset($_POST["rule_type"]))$rule_type=$_POST["rule_type"];
elseif (isset($_GET["rule_type"]))$rule_type=$_GET["rule_type"];
else $rule_type=0;


if (isset($_POST["rule_id"]))$rule_id=$_POST["rule_id"];
elseif (isset($_GET["rule_id"]))$rule_id=$_GET["rule_id"];
else $rule_id=0;

$rule = getRuleClass($rule_type);
checkRight($rule->right,"r");

$test_rule_output=null;

if (!ereg("popup",$_SERVER['PHP_SELF'])){
	commonHeader($LANG["common"][12],$_SERVER['PHP_SELF'],"config","display");
}

$rule->showRulePreviewCriteriasForm($_SERVER['PHP_SELF'],$rule_id,array(),array());

if (isset($_POST["test_rule"]))
{
	$params=array();
	//Unset values that must not be processed by the rule
	unset($_POST["test_rule"]);
	unset($_POST["rule_id"]);
	unset($_POST["rule_type"]);
	$rule->getRuleWithCriteriasAndActions($rule_id,1,1);
	
	// Need for RuleEngines
	foreach ($_POST as $key => $val) {
		$_POST[$key] = stripslashes($_POST[$key]);
	}
	//Add rules specific POST fields to the param array
	$params = $rule->addSpecificParamsForPreview($_POST,$params);

	$input=$rule->prepareInputDataForProcess($_POST,$params);
	$regex_results=array();
	echo "<br>";
	$rule->showRulePreviewResultsForm($_SERVER['PHP_SELF'],$input,$params);
}

if (!ereg("popup",$_SERVER['PHP_SELF'])){
	commonFooter();
}

if (!ereg("popup",$_SERVER['PHP_SELF'])){
	commonFooter();
}

?>
