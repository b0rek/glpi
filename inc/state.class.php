<?php
/*
 * @version $Id$
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
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Class State
class State extends CommonTreeDropdown {

   protected $visibility_fields = array('Computer'         => 'is_visible_computer',
                                    'SoftwareVersion'  => 'is_visible_softwareversion',
                                    'Monitor'          => 'is_visible_monitor',
                                    'Printer'          => 'is_visible_printer',
                                    'Peripheral'       => 'is_visible_peripheral',
                                    'Phone'            => 'is_visible_phone',
                                    'NetworkEquipment' => 'is_visible_networkequipment');


   static function getTypeName($nb=0) {
      return _n('Status of items', 'Statuses of items', $nb);
   }



   static function canCreate() {
      return Session::haveRight('entity_dropdown', 'w');
   }


   static function canView() {
      return Session::haveRight('entity_dropdown', 'r');
   }


   function getAdditionalFields() {

      $fields   = parent::getAdditionalFields();
      $fields[] = array('label' => __('Visibility'), 'name' => 'header');

      foreach ($this->visibility_fields as $type => $field) {
         $fields[] = array('name'  => $field,
                           'label' => $type::getTypeName(),
                           'type'  => 'bool',
                           'list'  => false);
      }
      return $fields;
   }

   /**
    * Dropdown of states for behaviour config
    *
    * @param $name            select name
    * @param $lib    string   to add for -1 value (default '')
    * @param $value           default value (default 0)
   **/
   static function dropdownBehaviour($name, $lib="", $value=0) {
      global $DB;

      $elements = array("0" => __('Keep status'));

      if ($lib) {
         $elements["-1"] = $lib;
      }

      $queryStateList = "SELECT `id`, `name`
                         FROM `glpi_states`
                         ORDER BY `name`";
      $result = $DB->query($queryStateList);

      if ($DB->numrows($result) > 0) {
         while ($data = $DB->fetch_assoc($result)) {
            $elements[$data["id"]] = sprintf(__('Set status: %s'), $data["name"]);
         }
      }
      Dropdown::showFromArray($name, $elements, array('value' => $value));
   }


   static function showSummary() {
      global $DB, $CFG_GLPI;

      $state_type = $CFG_GLPI["state_types"];
      $states     = array();

      foreach ($state_type as $key=>$itemtype) {
         if ($item = getItemForItemtype($itemtype)) {
            if (!$item->canView()) {
               unset($state_type[$key]);

            } else {
               $table = getTableForItemType($itemtype);
               $query = "SELECT `states_id`, COUNT(*) AS cpt
                         FROM `$table` ".
                         getEntitiesRestrictRequest("WHERE",$table)."
                              AND `is_deleted` = '0'
                              AND `is_template` = '0'
                         GROUP BY `states_id`";

               if ($result = $DB->query($query)) {
                  if ($DB->numrows($result) > 0) {
                     while ($data = $DB->fetch_assoc($result)) {
                        $states[$data["states_id"]][$itemtype] = $data["cpt"];
                     }
                  }
               }
            }
         }
      }

      if (count($states)) {
         // Produce headline
         echo "<div class='center'><table class='tab_cadrehov'><tr>";

         // Type
         echo "<th>".__('Status')."</th>";

         foreach ($state_type as $key => $itemtype) {
            if ($item = getItemForItemtype($itemtype)) {
               echo "<th>".$item->getTypeName(2)."</th>";
               $total[$itemtype] = 0;
            } else {
               unset($state_type[$key]);
            }
         }

         echo "<th>".__('Total')."</th>";
         echo "</tr>";
         $query = "SELECT *
                   FROM `glpi_states`
                   ORDER BY `completename`";
         $result = $DB->query($query);

         // No state
         $tot = 0;
         echo "<tr class='tab_bg_2'><td>---</td>";
         foreach ($state_type as $itemtype) {
            echo "<td class='numeric'>";

            if (isset($states[0][$itemtype])) {
               echo $states[0][$itemtype];
               $total[$itemtype] += $states[0][$itemtype];
               $tot              += $states[0][$itemtype];
            } else {
               echo "&nbsp;";
            }

            echo "</td>";
         }
         echo "<td class='numeric b'>$tot</td></tr>";

         while ($data = $DB->fetch_assoc($result)) {
            $tot = 0;
            echo "<tr class='tab_bg_2'><td class='b'>";
            echo "<a href='".$CFG_GLPI['root_doc']."/front/allassets.php?reset=reset&amp;contains[0]=".
                   "$$$$".$data["id"]."&amp;searchtype[0]=contains&amp;field[0]=31&amp;sort=".
                   "1&amp;start=0'>".$data["completename"]."</a></td>";

            foreach ($state_type as $itemtype) {
               echo "<td class='numeric'>";

               if (isset($states[$data["id"]][$itemtype])) {
                  echo $states[$data["id"]][$itemtype];
                  $total[$itemtype] += $states[$data["id"]][$itemtype];
                  $tot              += $states[$data["id"]][$itemtype];
               } else {
                  echo "&nbsp;";
               }

               echo "</td>";
            }
            echo "<td class='numeric b'>$tot</td>";
            echo "</tr>";
         }
         echo "<tr class='tab_bg_2'><td class='center b'>".__('Total')."</td>";
         $tot = 0;

         foreach ($state_type as $itemtype) {
            echo "<td class='numeric b'>".$total[$itemtype]."</td>";
            $tot += $total[$itemtype];
         }

         echo "<td class='numeric b'>$tot</td></tr>";
         echo "</table></div>";

      } else {
         echo "<div class='center b'>".__('No item found')."</div>";
      }
   }


   function getEmpty() {
      parent::getEmpty();
      //initialize is_visible_* fields at true to keep the same behavior as in older versions
      foreach ($this->visibility_fields as $type => $field) {
         $this->fields[$field] = 1;
      }
   }

   function cleanDBonPurge() {
      Rule::cleanForItemCriteria($this);
   }


   function prepareInputForAdd($input) {
      $input = parent::prepareInputForAdd($input);

      $state = new self();
      // Get visibility information from parent if not set
      if (isset($input['states_id']) && $state->getFromDB($input['states_id'])) {
         foreach ($this->visibility_fields as $type => $field) {
            if (!isset($input[$field]) && isset($state->fields[$field])) {
               $input[$field] = $state->fields[$field];
            }
         }
      }
      return $input;
   }

}
?>
