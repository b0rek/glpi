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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------


define('GLPI_ROOT','..');
// Include plugin if it is a plugin table
if (!strstr($_POST['table'],"plugin")){
	$AJAX_INCLUDE=1;
}
include (GLPI_ROOT."/inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
header_nocache();

checkLoginUser();

// Security
if (! TableExists($_POST['table']) || ! FieldExists($_POST['table'],$_POST['field']) ){
	exit();
}

$entity="";
if (isset($_POST['entity_restrict'])&&$_POST['entity_restrict']>=0&&in_array($_POST['table'],$CFG_GLPI["specif_entities_tables"])){
	$entity=" AND FK_entities='".$_POST['entity_restrict']."' ";
}

if (isset($_POST['user_restrict'])&&$_POST['user_restrict']>0){
	$entity=" AND FK_users='".$_POST['user_restrict']."' ";
}

$query="SELECT COUNT(`".$_POST['field']."`) 
	FROM `".$_POST['table']."` 
	WHERE `".$_POST['field']."` LIKE '".$_POST['query']."%' 
		AND `".$_POST['field']."` <> '".$_POST['query']."' $entity ";
$result=$DB->query($query);
$totnum=$DB->result($result,0,0);


$query="SELECT DISTINCT `".$_POST['field']."` AS VAL 
	FROM `".$_POST['table']."` 
	WHERE `".$_POST['field']."` LIKE '".$_POST['query']."%' AND `".$_POST['field']."` <> '".$_POST['query']."' $entity 
	ORDER BY `".$_POST['field']."` LIMIT ".intval($_POST['start']).",".intval($_POST['limit']);


if ($result=$DB->query($query))
	echo '{"totalCount":"'.$totnum.'","items":[';
	if ($DB->numrows($result)>0){
		$first=true;
		while ($data=$DB->fetch_array($result))	{
			if ($first){
				$first=false;
			} else {
				echo ',';
			}
			echo '{"value":"'.$data['VAL'].'"}';
		}
	}
	echo ']}';

?>
