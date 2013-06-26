<?php
/*
 * @version $Id: dropdownAllItems.php 20785 2013-04-17 18:27:37Z yllen $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
* @since version 0.85
*/

include ('../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkCentralAccess();

// Make a select box
if ($_POST['items_id']
    && $_POST['itemtype'] && class_exists($_POST['itemtype'])) {
   $devicetype = $_POST['itemtype'];
   $linktype   = 'Item_'.$devicetype;

   if (count($linktype::getSpecificities())) {
      $name_field = "CONCAT_WS(' - ', `".implode('`, `', array_keys($linktype::getSpecificities()))."`)";
   } else {
      $name_field = "`id`";
   }
   $query = "SELECT `id`, $name_field AS name
             FROM `".$linktype::getTable()."`
             WHERE `".$devicetype::getForeignKeyField()."` = '".$_POST['items_id']."'
                    AND `itemtype` = ''";
   $result = $DB->request($query);
   if ($result->numrows() == 0) {
      echo __('No unaffected device !');
   } else {
      $devices = array();
      foreach ($result as $row) {
         $devices[$row['id']] = $row['name'];
      }
      dropdown::showFromArray($linktype::getForeignKeyField(), $devices, array('multiple' => true));
   }

}
?>