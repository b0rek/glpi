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


/**
 * Rule class store all information about a GLPI rule :
 *   - description
 *   - criterias
 *   - actions
**/
class Rule extends CommonDBTM {

   public $dohistory = true;

   // Specific ones
   ///Actions affected to this rule
   var $actions   = array();
   ///Criterias affected to this rule
   var $criterias = array();
   /// Rules can be sorted ?
   var $can_sort  = false;
   /// field used to order rules
   var $orderby   = 'ranking';

   /// restrict matching to self::AND_MATCHING or self::OR_MATCHING : specify value to activate
   var $restrict_matching        = false;

   protected $rules_id_field     = 'rules_id';
   protected $ruleactionclass    = 'RuleAction';
   protected $rulecriteriaclass  = 'RuleCriteria';

   var $specific_parameters      = false;

   var $regex_results            = array();
   var $criterias_results        = array();

   static $rightname             = 'config';

   const RULE_NOT_IN_CACHE       = -1;
   const RULE_WILDCARD           = '*';

   //Generic rules engine
   const PATTERN_IS              = 0;
   const PATTERN_IS_NOT          = 1;
   const PATTERN_CONTAIN         = 2;
   const PATTERN_NOT_CONTAIN     = 3;
   const PATTERN_BEGIN           = 4;
   const PATTERN_END             = 5;
   const REGEX_MATCH             = 6;
   const REGEX_NOT_MATCH         = 7;
   const PATTERN_EXISTS          = 8;
   const PATTERN_DOES_NOT_EXISTS = 9;
   const PATTERN_FIND            = 10; // Global criteria
   const PATTERN_UNDER           = 11;
   const PATTERN_NOT_UNDER       = 12;
   const PATTERN_IS_EMPTY        = 30; // Global criteria

   const AND_MATCHING            = "AND";
   const OR_MATCHING             = "OR";


   // Temproray hack for this class
   static function getTable() {
      return 'glpi_rules';
   }


   static function getTypeName($nb=0) {
      return _n('Rule', 'Rules', $nb);
   }


   /**
    *  Get correct Rule object for specific rule
    *
    *  @since version 0.84
    *
    *  @param $rules_id ID of the rule
   **/
   static function getRuleObjectByID($rules_id) {

      $rule = new self();
      if ($rule->getFromDB($rules_id)) {
         $realrule = new $rule->fields['sub_type']();
         return $realrule;
      }
      return null;
   }


   /**
    *  @see CommonGLPI::getMenuContent()
    *
    *  @since version 0.85
   **/
   static function getMenuContent() {
      global $CFG_GLPI;

      $menu = array();

      if (Session::haveRight("rule_ldap", READ)
          || Session::haveRight("rule_ocs", READ)
          || Session::haveRight("entity_rule_ticket", READ)
          || Session::haveRight("rule_softwarecategories", READ)
          || Session::haveRight("rule_mailcollector", READ)) {

         $menu['rule']['title'] = static::getTypeName(2);
         $menu['rule']['page']  = static::getSearchURL(false);

         foreach ($CFG_GLPI["rulecollections_types"] as $rulecollectionclass) {
            $rulecollection = new $rulecollectionclass();
            if ($rulecollection->canList()) {
               $ruleclassname = $rulecollection->getRuleClassName();
               $menu['rule']['options'][$rulecollection->menu_option]['title']
                              = $rulecollection->getRuleClass()->getTitle();
               $menu['rule']['options'][$rulecollection->menu_option]['page']
                              = Toolbox::getItemTypeSearchURL($ruleclassname, false);
               $menu['rule']['options'][$rulecollection->menu_option]['links']['search']
                              = Toolbox::getItemTypeSearchURL($ruleclassname, false);
               if ($rulecollection->canCreate()) {
                  $menu['rule']['options'][$rulecollection->menu_option]['links']['add']
                              = Toolbox::getItemTypeFormURL($ruleclassname, false);
               }
            }
         }
      }

      if (Transfer::canView()
          && Session::isMultiEntitiesMode()) {

         $menu['rule']['title'] = static::getTypeName(2);
         $menu['rule']['page']  = static::getSearchURL(false);

         $menu['rule']['options']['transfer']['title']           = __('Transfer');
         $menu['rule']['options']['transfer']['page']            = "/front/transfer.php";
         $menu['rule']['options']['transfer']['links']['search'] = "/front/transfer.php";

         if (Session::haveRight("transfer","w")) {
            $menu['rule']['options']['transfer']['links']['summary']
                                                                 = "/front/transfer.action.php";
            $menu['rule']['options']['transfer']['links']['add'] = "/front/transfer.form.php";
         }
      }


      if (Session::haveRight("rule_dictionnary_dropdown", READ)
          || Session::haveRight("rule_dictionnary_software", READ)
          || Session::haveRight("rule_dictionnary_printer", READ)) {

         $menu['dictionnary']['title']    = _n('Dictionary', 'Dictionaries', 2);
         $menu['dictionnary']['shortcut'] = '';
         $menu['dictionnary']['page']     = '/front/dictionnary.php';

         $menu['dictionnary']['options']['manufacturers']['title']
                           = _n('Manufacturer', 'Manufacturers', 2);
         $menu['dictionnary']['options']['manufacturers']['page']
                           = '/front/ruledictionnarymanufacturer.php';
         $menu['dictionnary']['options']['manufacturers']['links']['search']
                           = '/front/ruledictionnarymanufacturer.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['manufacturers']['links']['add']
                              = '/front/ruledictionnarymanufacturer.form.php';
         }


         $menu['dictionnary']['options']['software']['title']
                           = _n('Software', 'Software', 2);
         $menu['dictionnary']['options']['software']['page']
                           = '/front/ruledictionnarysoftware.php';
         $menu['dictionnary']['options']['software']['links']['search']
                           = '/front/ruledictionnarysoftware.php';

         if (RuleDictionnarySoftware::canCreate()) {
            $menu['dictionnary']['options']['software']['links']['add']
                              = '/front/ruledictionnarysoftware.form.php';
         }


         $menu['dictionnary']['options']['model.computer']['title']
                           = _n('Computer model', 'Computer models', 2);
         $menu['dictionnary']['options']['model.computer']['page']
                           = '/front/ruledictionnarycomputermodel.php';
         $menu['dictionnary']['options']['model.computer']['links']['search']
                           = '/front/ruledictionnarycomputermodel.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['model.computer']['links']['add']
                              = '/front/ruledictionnarycomputermodel.form.php';
         }


         $menu['dictionnary']['options']['model.monitor']['title']
                           = _n('Monitor model', 'Monitor models', 2);
         $menu['dictionnary']['options']['model.monitor']['page']
                           = '/front/ruledictionnarymonitormodel.php';
         $menu['dictionnary']['options']['model.monitor']['links']['search']
                           = '/front/ruledictionnarymonitormodel.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['model.monitor']['links']['add']
                              = '/front/ruledictionnarymonitormodel.form.php';
         }


         $menu['dictionnary']['options']['model.printer']['title']
                           = _n('Printer model', 'Printer models', 2);
         $menu['dictionnary']['options']['model.printer']['page']
                           = '/front/ruledictionnaryprintermodel.php';
         $menu['dictionnary']['options']['model.printer']['links']['search']
                           = '/front/ruledictionnaryprintermodel.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['model.printer']['links']['add']
                              = '/front/ruledictionnaryprintermodel.form.php';
         }


         $menu['dictionnary']['options']['model.peripheral']['title']
                           = _n('Peripheral model', 'Peripheral models', 2);
         $menu['dictionnary']['options']['model.peripheral']['page']
                           = '/front/ruledictionnaryperipheralmodel.php';
         $menu['dictionnary']['options']['model.peripheral']['links']['search']
                           = '/front/ruledictionnaryperipheralmodel.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['model.peripheral']['links']['add']
                              = '/front/ruledictionnaryperipheralmodel.form.php';
         }


         $menu['dictionnary']['options']['model.networking']['title']
                           = _n('Networking equipment model', 'Networking equipment models', 2);
         $menu['dictionnary']['options']['model.networking']['page']
                           = '/front/ruledictionnarynetworkequipmentmodel.php';
         $menu['dictionnary']['options']['model.networking']['links']['search']
                           = '/front/ruledictionnarynetworkequipmentmodel.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['model.networking']['links']['add']
                              = '/front/ruledictionnarynetworkequipmentmodel.form.php';
         }


         $menu['dictionnary']['options']['model.phone']['title']
                           = _n('Phone model', 'Phone models', 2);
         $menu['dictionnary']['options']['model.phone']['page']
                           = '/front/ruledictionnaryphonemodel.php';
         $menu['dictionnary']['options']['model.phone']['links']['search']
                           = '/front/ruledictionnaryphonemodel.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['model.phone']['links']['add']
                              = '/front/ruledictionnaryphonemodel.form.php';
         }


         $menu['dictionnary']['options']['type.computer']['title']
                           = _n('Computer type', 'Computer types', 2);
         $menu['dictionnary']['options']['type.computer']['page']
                           = '/front/ruledictionnarycomputertype.php';
         $menu['dictionnary']['options']['type.computer']['links']['search']
                           = '/front/ruledictionnarycomputertype.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['type.computer']['links']['add']
                              = '/front/ruledictionnarycomputertype.form.php';
         }


         $menu['dictionnary']['options']['type.monitor']['title']
                           = _n('Monitor type', 'Monitors types', 2);
         $menu['dictionnary']['options']['type.monitor']['page']
                           = '/front/ruledictionnarymonitortype.php';
         $menu['dictionnary']['options']['type.monitor']['links']['search']
                           = '/front/ruledictionnarymonitortype.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['type.monitor']['links']['add']
                              = '/front/ruledictionnarymonitortype.form.php';
         }


         $menu['dictionnary']['options']['type.printer']['title']
                           = _n('Printer type', 'Printer types', 2);
         $menu['dictionnary']['options']['type.printer']['page']
                           = '/front/ruledictionnaryprintertype.php';
         $menu['dictionnary']['options']['type.printer']['links']['search']
                           = '/front/ruledictionnaryprintertype.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['type.printer']['links']['add']
                              = '/front/ruledictionnaryprintertype.form.php';
         }


         $menu['dictionnary']['options']['type.peripheral']['title']
                           = _n('Peripheral type', 'Peripheral types', 2);
         $menu['dictionnary']['options']['type.peripheral']['page']
                           = '/front/ruledictionnaryperipheraltype.php';
         $menu['dictionnary']['options']['type.peripheral']['links']['search']
                           = '/front/ruledictionnaryperipheraltype.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['type.peripheral']['links']['add']
                              = '/front/ruledictionnaryperipheraltype.form.php';
         }


         $menu['dictionnary']['options']['type.networking']['title']
                           = _n('Networking equipment type', 'Networking equipment types', 2);
         $menu['dictionnary']['options']['type.networking']['page']
                           = '/front/ruledictionnarynetworkequipmenttype.php';
         $menu['dictionnary']['options']['type.networking']['links']['search']
                           = '/front/ruledictionnarynetworkequipmenttype.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['type.networking']['links']['add']
                              = '/front/ruledictionnarynetworkequipmenttype.form.php';
         }


         $menu['dictionnary']['options']['type.phone']['title']
                           = _n('Phone type', 'Phone types', 2);
         $menu['dictionnary']['options']['type.phone']['page']
                           = '/front/ruledictionnaryphonetype.php';
         $menu['dictionnary']['options']['type.phone']['links']['search']
                           = '/front/ruledictionnaryphonetype.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['type.phone']['links']['add']
                              = '/front/ruledictionnaryphonetype.form.php';
         }


         $menu['dictionnary']['options']['os']['title']
                           = __('Operating system');
         $menu['dictionnary']['options']['os']['page']
                           = '/front/ruledictionnaryoperatingsystem.php';
         $menu['dictionnary']['options']['os']['links']['search']
                           = '/front/ruledictionnaryoperatingsystem.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['os']['links']['add']
                              = '/front/ruledictionnaryoperatingsystem.form.php';
         }


         $menu['dictionnary']['options']['os_sp']['title']
                           = __('Service pack');
         $menu['dictionnary']['options']['os_sp']['page']
                           = '/front/ruledictionnaryoperatingsystemservicepack.php';
         $menu['dictionnary']['options']['os_sp']['links']['search']
                           = '/front/ruledictionnaryoperatingsystemservicepack.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['os_sp']['links']['add']
                              = '/front/ruledictionnaryoperatingsystemservicepack.form.php';
         }


         $menu['dictionnary']['options']['os_version']['title']
                           = __('Version of the operating system');
         $menu['dictionnary']['options']['os_version']['page']
                           = '/front/ruledictionnaryoperatingsystemversion.php';
         $menu['dictionnary']['options']['os_version']['links']['search']
                           = '/front/ruledictionnaryoperatingsystemversion.php';

         if (RuleDictionnaryDropdown::canCreate()) {
            $menu['dictionnary']['options']['os_version']['links']['add']
                              = '/front/ruledictionnaryoperatingsystemversion.form.php';
         }

         $menu['dictionnary']['options']['printer']['title']
                           = _n('Printer', 'Printers', 2);
         $menu['dictionnary']['options']['printer']['page']
                           = '/front/ruledictionnaryprinter.php';
         $menu['dictionnary']['options']['printer']['links']['search']
                           = '/front/ruledictionnaryprinter.php';

         if (RuleDictionnaryPrinter::canCreate()) {
            $menu['dictionnary']['options']['printer']['links']['add']
                              = '/front/ruledictionnaryprinter.form.php';
         }
      }

      if (count($menu)) {
         $menu['is_multi_entries'] = true;
         return $menu;
      }

      return false;
   }


   /**
    * @since versin 0.84
   **/
   function getRuleActionClass () {
      return $this->ruleactionclass;
   }


   /**
    * @since versin 0.84
   **/
   function getRuleCriteriaClass () {
      return $this->rulecriteriaclass;
   }


   /**
    * @since versin 0.84
   **/
   function getRuleIdField () {
      return $this->rules_id_field;
   }


   function isEntityAssign() {
      return false;
   }


   function post_getEmpty() {
      $this->fields['is_active'] = 0;
   }


   /**
    * Get title used in rule
    *
    * @return Title of the rule
   **/
   function getTitle() {
      return __('Rules management');
   }


   /**
    * @since version 0.84
    *
    * @return string
   **/
   function getCollectionClassName() {
      return $this->getType().'Collection';
   }


   /**
    * @see CommonDBTM::getSpecificMassiveActions()
   **/
   function getSpecificMassiveActions($checkitem=NULL) {

      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);

      $collectiontype = $this->getCollectionClassName();
      if ($collection = getItemForItemtype($collectiontype)) {
         if ($isadmin
             && ($collection->orderby == "ranking")) {
            $actions['move_rule'] = __('Move');
         }
      }

      $actions['duplicate'] = __('Duplicate');
      $actions['export']    = __('Export');

      return $actions;
   }


   /**
    * @see CommonDBTM::showSpecificMassiveActionsParameters()
   **/
   function showSpecificMassiveActionsParameters($input=array()) {

      switch ($input['action']) {
         case "move_rule" :
            $values = array('after'  => __('After'),
                            'before' => __('Before'));
            Dropdown::showFromArray('move_type', $values, array('width' => '20%'));

            if (isset($input['entity_restrict'])) {
               $condition = $input['entity_restrict'];
            } else {
               $condition = "";
            }
            Rule::dropdown(array('sub_type'        => $input['itemtype'],
                                 'name'            => "ranking",
                                 'entity_restrict' => $condition,
                                 'width'           => '50%'));
            echo "<br><br><input type='submit' name='massiveaction' class='submit' value='".
                           _sx('button', 'Move')."'>\n";
            return true;

         case "duplicate" :
            if ($this->isEntityAssign()) {
               Entity::dropdown();
            }
            echo "<br><br><input type='submit' name='massiveaction' class='submit' value='".
                           _sx('button', 'Duplicate')."'>";
            return true;

         case "export" :
            echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value='".
                         __s('Export')."'>";
            return true;

         default :
            return parent::showSpecificMassiveActionsParameters($input);

      }
      return false;
   }


   /**
    * @see CommonDBTM::doSpecificMassiveActions()
   **/
   function doSpecificMassiveActions($input=array()) {

      $res = array('ok'      => 0,
                   'ko'      => 0,
                   'noright' => 0);

      switch ($input['action']) {
         case "move_rule" :
            $collectionname = $input['itemtype'].'Collection';
            $rulecollection = new $collectionname();
            if ($rulecollection->canUpdate()) {
               foreach ($input["item"] as $key => $val) {
                  if ($this->getFromDB($key)) {
                     if ($rulecollection->moveRule($key, $input['ranking'], $input['move_type'])) {
                        $res['ok']++;
                     } else {
                        $res['ko']++;
                        $res['messages'][] = $this->getErrorMessage(ERROR_ON_ACTION);
                     }
                  } else {
                     $res['ko']++;
                     $res['messages'][] = $this->getErrorMessage(ERROR_NOT_FOUND);
                  }
               }
            } else {
               $res['noright']++;
               $res['messages'][] = $this->getErrorMessage(ERROR_RIGHT);
            }
            break;

         case 'duplicate':
            $rulecollection = new RuleCollection();
            if (isset($input["item"]) && count($input["item"])) {
               foreach ($input["item"] as $key => $val) {
                  if ($this->getFromDB($key)) {
                     if ($rulecollection->duplicateRule($key)) {
                        $res['ok']++;
                     } else {
                        $res['ko']++;
                        $res['messages'][] = $this->getErrorMessage(ERROR_ON_ACTION);
                     }
                  } else {
                     $res['ko']++;
                     $res['messages'][] = $this->getErrorMessage(ERROR_NOT_FOUND);
                  }
               }
            }
            break;

         case 'export':
            if (isset($input["item"]) && count($input["item"])) {
               $_SESSION['exportitems'] = $input["item"];
               $res['ok']       = -1; //processed after redirection
               $res['REDIRECT'] = 'rule.backup.php?action=download&itemtype='.$this->getType();
            }
         break;

         default :
            return parent::doSpecificMassiveActions($input);
      }
      return $res;
   }


   function getSearchOptions() {

      $tab                       = array();

      $tab[1]['table']           = $this->getTable();
      $tab[1]['field']           = 'name';
      $tab[1]['name']            = __('Name');
      $tab[1]['datatype']        = 'itemlink';
      $tab[1]['massiveaction']   = false;

      $tab[3]['table']           = $this->getTable();
      $tab[3]['field']           = 'ranking';
      $tab[3]['name']            = __('Position');
      $tab[3]['datatype']        = 'number';
      $tab[3]['massiveaction']   = false;

      $tab[4]['table']           = $this->getTable();
      $tab[4]['field']           = 'description';
      $tab[4]['name']            = __('Description');
      $tab[4]['datatype']        = 'text';

      $tab[5]['table']           = $this->getTable();
      $tab[5]['field']           = 'match';
      $tab[5]['name']            = __('Logical operator');
      $tab[5]['datatype']        = 'specific';
      $tab[5]['massiveaction']   = false;

      $tab[8]['table']           = $this->getTable();
      $tab[8]['field']           = 'is_active';
      $tab[8]['name']            = __('Active');
      $tab[8]['datatype']        = 'bool';

      $tab[16]['table']          = $this->getTable();
      $tab[16]['field']          = 'comment';
      $tab[16]['name']           = __('Comments');
      $tab[16]['datatype']       = 'text';

      $tab[80]['table']          = 'glpi_entities';
      $tab[80]['field']          = 'completename';
      $tab[80]['name']           = __('Entity');
      $tab[80]['massiveaction']  = false;
      $tab[80]['datatype']       = 'dropdown';

      $tab[86]['table']          = $this->getTable();
      $tab[86]['field']          = 'is_recursive';
      $tab[86]['name']           = __('Child entities');
      $tab[86]['datatype']       = 'bool';
      $tab[86]['massiveaction']  = false;

      return $tab;
   }


   /**
    * @param  $field
    * @param  $values
    * @param  $options   array
    *
    * @return string
   **/
   static function getSpecificValueToDisplay($field, $values, array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      switch ($field) {
         case 'match' :
            switch ($values[$field]) {
               case self::AND_MATCHING :
                  return __('and');

               case self::OR_MATCHING :
                  return __('or');

               default :
                  return NOT_AVAILABLE;
            }
            break;
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * @param  $field
    * @param  $name              (default '')
    * @param  $values            (default '')
    * @param  $options   array
   **/
   static function getSpecificValueToSelect($field, $name='', $values='', array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      $options['display'] = false;
      switch ($field) {
         case 'match' :
            if (isset($values['itemtype']) && !empty($values['itemtype'])) {
               $options['value'] = $values[$field];
               $options['name']  = $name;
               $rule             = new static();
               return $rule->dropdownRulesMatch($options);
            }
            break;
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   /**
    * Show the rule
    *
    * @param $ID              ID of the rule
    * @param $options   array of possible options:
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
    *
    * @return nothing
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI;

      if (!$this->isNewID($ID)) {
         $this->check($ID, 'r');
      } else {
         // Create item
         $this->checkGlobal('w');
      }

      $canedit = $this->can(static::$rightname, UPDATE);
      $rand = mt_rand();
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td>".__('Description')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "description");
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Logical operator')."</td>";
      echo "<td>";
      $this->dropdownRulesMatch(array('value' => $this->fields["match"]));
      echo "</td>";
      echo "<td>".__('Active')."</td>";
      echo "<td>";
      Dropdown::showYesNo("is_active", $this->fields["is_active"]);
      echo"</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Comments')."</td>";
      echo "<td class='middle' colspan='3'>";
      echo "<textarea cols='110' rows='3' name='comment' >".$this->fields["comment"]."</textarea>";

      if (!$this->isNewID($ID)) {
         if ($this->fields["date_mod"]) {
            echo "<br>";
            printf(__('Last update on %s'), Html::convDateTime($this->fields["date_mod"]));
         }
      }
      echo"</td></tr>\n";

      if ($canedit) {
         echo "<input type='hidden' name='ranking' value='".$this->fields["ranking"]."'>";
         echo "<input type='hidden' name='sub_type' value='".get_class($this)."'>";

         if ($ID > 0) {
            if ($plugin = isPluginItemType($this->getType())) {
               $url = $CFG_GLPI["root_doc"]."/plugins/".strtolower($plugin['plugin']);
            } else {
               $url = $CFG_GLPI["root_doc"];
            }
            echo "<tr><td class='tab_bg_2 center' colspan='4'>";
            echo "<a class='vsubmit' href='#' onClick=\"".
                  Html::jsGetElementbyID('ruletest'.$rand).".dialog('open');\">".
                  __('Test')."</a>";
            Ajax::createIframeModalWindow('ruletest'.$rand,
                                          $url."/front/rule.test.php?".
                                          "sub_type=".$this->getType().
                                          "&rules_id=".$this->fields["id"],
                                          array('title' => __('Test')));
            echo "</td></tr>\n";
         }
      }

      $this->showFormButtons($options);

      return true;
   }


   /**
    * Display a dropdown with all the rule matching
    *
    * @since version 0.84 new proto
    *
    * @param $options      array of parameters
   **/
   function dropdownRulesMatch($options=array()) {

      $p['name']     = 'match';
      $p['value']    = '';
      $p['restrict'] = $this->restrict_matching;
      $p['display']  = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      if (!$p['restrict'] || ($p['restrict'] == self::AND_MATCHING)) {
         $elements[self::AND_MATCHING] = __('and');
      }

      if (!$p['restrict'] || ($p['restrict'] == self::OR_MATCHING)) {
         $elements[self::OR_MATCHING]  = __('or');
      }

      return Dropdown::showFromArray($p['name'], $elements, $p);
   }


   /**
    * Get all criterias for a given rule
    *
    * @param $ID              the rule_description ID
    * @param $withcriterias   1 to retrieve all the criterias for a given rule (default 0)
    * @param $withactions     1 to retrive all the actions for a given rule (default 0)
   **/
   function getRuleWithCriteriasAndActions($ID, $withcriterias=0, $withactions=0) {

      if ($ID == "") {
         return $this->getEmpty();
      }
      if ($ret = $this->getFromDB($ID)) {
         if ($withactions
             && ($RuleAction = getItemForItemtype($this->ruleactionclass))) {
            $this->actions = $RuleAction->getRuleActions($ID);
         }

         if ($withcriterias
             && ($RuleCriterias = getItemForItemtype($this->rulecriteriaclass))) {
            $this->criterias = $RuleCriterias->getRuleCriterias($ID);
         }

         return true;
      }

      return false;
   }


   /**
    * display title for action form
   **/
   function getTitleAction() {

      foreach ($this->getActions() as $key => $val) {
         if (isset($val['force_actions'])
             && (in_array('regex_result', $val['force_actions'])
                 || in_array('append_regex_result', $val['force_actions']))) {

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'><td>".
                  __('It is possible to affect the result of a regular expression using the string #0').
                 "</td></tr>\n";
            echo "</table><br>";
            return;
         }
      }
   }


   /**
    * Get maximum number of Actions of the Rule (0 = unlimited)
    *
    * @return the maximum number of actions
   **/
   function maxActionsCount() {
      // Unlimited
      return 0;
   }


   /**
    * Display all rules actions
    *
    * @param $rules_id        rule ID
    * @param $options   array of options : may be readonly
   **/
   function showActionsList($rules_id, $options=array()) {

      $rand = mt_rand();
      $p['readonly'] = false;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $canedit = $this->can($rules_id, UPDATE);
      $style   = "class='tab_cadre_fixe'";

      if ($p['readonly']) {
         $canedit = false;
         $style   = "class='tab_cadre'";
      }
      $this->getTitleAction();

      if ($canedit
          && (($this->maxActionsCount() == 0)
              || (sizeof($this->actions) < $this->maxActionsCount()))) {

         echo "<form name='actionsaddform' method='post' action='".
                Toolbox::getItemTypeFormURL(get_class($this))."'>\n";
         $this->addActionForm($rules_id);
         Html::closeForm();
      }

      $nb = count($this->actions);

      echo "<div class='spaced'>";
      if ($canedit && $nb) {
         Html::openMassiveActionsForm('mass'.$this->ruleactionclass.$rand);
         $paramsma = array('num_displayed'  => $nb,
                           'check_itemtype' => get_class($this),
                           'check_items_id' => $rules_id,
                           'container'      => 'mass'.$this->ruleactionclass.$rand);
         Html::showMassiveActions($this->ruleactionclass, $paramsma);
      }

      echo "<table $style>";
      echo "<tr>";
      echo "<th colspan='".($canedit && $nb?'4':'3')."'>" . _n('Action','Actions',2) . "</th></tr>";
      echo "<tr class='tab_bg_2'>";

      if ($canedit && $nb) {
         echo "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.$this->ruleactionclass.$rand)."</th>";
      }

      echo "<th class='center b'>"._n('Field', 'Fields', 2)."</th>";
      echo "<th class='center b'>".__('Action type')."</th>";
      echo "<th class='center b'>".__('Value')."</th>";
      echo "</tr>\n";


      foreach ($this->actions as $action) {
         $this->showMinimalActionForm($action->fields, $canedit);
      }
      echo "</table>\n";

      if ($canedit && $nb) {
         $paramsma['ontop'] = false;
         Html::showMassiveActions($this->ruleactionclass, $paramsma);
         Html::closeForm();
      }
      echo "</div>";
   }


   /**
    * Display the add action form
    *
    * @param $rules_id rule ID
   **/
   function addActionForm($rules_id) {
      // CFG_GLPI needed by ruleaction.php
      global $CFG_GLPI;

      if ($ra = getItemForItemtype($this->ruleactionclass)) {
         echo "<div class='firstbloc'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='4'>" . _n('Action', 'Actions', 1) . "</tr>";

         echo "<tr class='tab_bg_1 center'>";
         echo "<td>"._n('Action', 'Actions', 1) . "</td><td>";
         $rand   = $this->dropdownActions(array('used' => $ra->getAlreadyUsedForRuleID($rules_id, $this->getType())));
         $params = array('field'               => '__VALUE__',
                         'sub_type'            => $this->getType(),
                         $this->rules_id_field => $rules_id);

         Ajax::updateItemOnSelectEvent("dropdown_field$rand", "action_span",
                                       $CFG_GLPI["root_doc"]."/ajax/ruleaction.php", $params);


         echo "</td><td class='left' width='30%'><span id='action_span'>\n";
         echo "</span></td>\n";
         echo "<td class='tab_bg_2 left' width='80px'>";
         echo "<input type='hidden' name='".$this->rules_id_field."' value='".
                $this->fields["id"]."'>";
         echo "<input type='submit' name='add_action' value=\""._sx('button','Add')."\"
                class='submit'>";
         echo "</td></tr>\n";
         echo "</table></div>";
      }
   }


   /**
    * Display the add criteria form
    *
    * @param $rules_id rule ID
   **/
   function addCriteriaForm($rules_id) {
      // CFG_GLPI needed by rulecriteria.php
      global $CFG_GLPI;

      echo "<div class='firstbloc'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>" . _n('Criterion', 'Criteria', 1) . "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='center'>"._n('Criterion', 'Criteria', 1) . "</td><td>";
      $rand   = $this->dropdownCriteria();
      $params = array('criteria' => '__VALUE__',
                      'rand'     => $rand,
                      'sub_type' => $this->getType());

      Ajax::updateItemOnSelectEvent("dropdown_criteria$rand", "criteria_span",
                                    $CFG_GLPI["root_doc"]."/ajax/rulecriteria.php", $params);

      if ($this->specific_parameters) {
         $itemtype = get_class($this).'Parameter';
         echo "<img alt='' title=\"".__s('Add a criterion')."\" src='".$CFG_GLPI["root_doc"].
                "/pics/add_dropdown.png' style='cursor:pointer; margin-left:2px;'
                onClick=\"".Html::jsGetElementbyID('addcriterion'.$rand).".dialog('open');\">";
         Ajax::createIframeModalWindow('addcriterion'.$rand,
                                       Toolbox::getItemTypeFormURL($itemtype),
                                       array('reloadonclose' => true));
      }

      echo "</td><td class='left'><span id='criteria_span'>\n";
/*      $_POST["sub_type"] = $this->getType();
      $_POST["criteria"] = $val;
      include (GLPI_ROOT."/ajax/rulecriteria.php");*/
      echo "</span></td>\n";
      echo "<td class='tab_bg_2' width='80px'>";
      echo "<input type='hidden' name='".$this->rules_id_field."' value='".$this->fields["id"]."'>";
      echo "<input type='submit' name='add_criteria' value=\""._sx('button','Add')."\"
             class='submit'>";
      echo "</td></tr>\n";
      echo "</table></div>";
   }


   function maybeRecursive() {
      return false;
   }


   /**
    * Display all rules criterias
    *
    * @param $rules_id
    * @param $options   array of options : may be readonly
   **/
   function showCriteriasList($rules_id, $options=array()) {

      $rand = mt_rand();
      $p['readonly'] = false;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $canedit = $this->can($rules_id, UPDATE);
      $style   = "class='tab_cadre_fixe'";

      if ($p['readonly']) {
         $canedit = false;
         $style   = "class='tab_cadre'";
      }

      if ($canedit) {
         echo "<form name='criteriasaddform' method='post' action='".
                Toolbox::getItemTypeFormURL(get_class($this))."'>\n";
         $this->addCriteriaForm($rules_id);
         Html::closeForm();
      }

      echo "<div class='spaced'>";

      $nb = sizeof($this->criterias);

      if ($canedit && $nb) {
         Html::openMassiveActionsForm('mass'.$this->rulecriteriaclass.$rand);
         $paramsma = array('num_displayed'  => $nb,
                           'check_itemtype' => get_class($this),
                           'check_items_id' => $rules_id,
                           'container'      => 'mass'.$this->rulecriteriaclass.$rand);
         Html::showMassiveActions($this->rulecriteriaclass, $paramsma);
      }

      echo "<table $style>";
      echo "<tr><th colspan='".($canedit&&$nb?" 4 ":"3")."'>". _n('Criterion', 'Criteria', 2).
           "</th></tr>\n";

      echo "<tr class='tab_bg_2'>";
      if ($canedit && $nb) {
         echo "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.$this->rulecriteriaclass.$rand)."</th>";
      }
      echo "<th class='center b'>"._n('Criterion', 'Criteria', 1)."</th>\n";
      echo "<th class='center b'>".__('Condition')."</th>\n";
      echo "<th class='center b'>".__('Reason')."</th>\n";
      echo "</tr>\n";


      foreach ($this->criterias as $criteria) {
         $this->showMinimalCriteriaForm($criteria->fields, $canedit);
      }
      echo "</table>\n";

      if ($canedit && $nb) {
         $paramsma['ontop'] = false;
         Html::showMassiveActions($this->rulecriteriaclass, $paramsma);
         Html::closeForm();
      }

      echo "</div>\n";
   }



   /**
    * Display the dropdown of the criterias for the rule
    *
    * @since version 0.84 new proto
    *
    * @param $options   array of options : may be readonly
    *
    * @return the initial value (first)
   **/
   function dropdownCriteria($options=array()) {
      global $CFG_GLPI;

      $p['name']    = 'criteria';
      $p['display'] = true;
      $p['value']   = '';

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $items      = array('' => Dropdown::EMPTY_VALUE);
      $group      = array();
      $groupname  = _n('Criterion', 'Criteria', 2);
      foreach ($this->getAllCriteria() as $ID => $crit) {
         // Manage group system
         if (!is_array($crit)) {
            if (count($group)) {
               asort($group);
               $items[$groupname] = $group;
            }
            $group     = array();
            $groupname = $crit;
         } else {
            $group[$ID] = $crit['name'];
         }
      }
      if (count($group)) {
         asort($group);
         $items[$groupname] = $group;
      }
      return Dropdown::showFromArray($p['name'], $items, $p);
   }


   /**
    * Display the dropdown of the actions for the rule
    *
    * @param $options already used actions
    *
    * @return the initial value (first non used)
   **/
   function dropdownActions($options=array()) {
      global $CFG_GLPI;

      $p['name']    = 'field';
      $p['display'] = true;
      $p['used']    = array();
      $p['value']   = '';

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $actions = $this->getAllActions();

      // For each used actions see if several set is available
      // Force actions to available actions for several
      foreach ($p['used'] as $key => $ID) {
         if (isset($actions[$ID]['permitseveral'])) {
            unset($p['used'][$key]);
         }
      }

      // Complete used array with duplicate items
      // add duplicates of used items
      foreach ($p['used'] as $ID) {
         if (isset($actions[$ID]['duplicatewith'])) {
            $p['used'][$actions[$ID]['duplicatewith']] = $actions[$ID]['duplicatewith'];
         }
      }

      // Parse for duplicates of already used items
      foreach ($actions as $ID => $act) {
         if (isset($actions[$ID]['duplicatewith'])
             && in_array($actions[$ID]['duplicatewith'], $p['used'])) {
            $p['used'][$ID] = $ID;
         }
      }

      $items = array('' => Dropdown::EMPTY_VALUE);
      $value = '';

      foreach ($actions as $ID => $act) {
         $items[$ID] = $act['name'];

         if (empty($value) && !isset($used[$ID])) {
            $value = $ID;
         }
      }
      asort($items);

      return Dropdown::showFromArray($p['name'], $items, $p);
   }


   /**
    * Get a criteria description by his ID
    *
    * @param $ID the criteria's ID
    *
    * @return the criteria array
   **/
   function getCriteria($ID) {

      $criterias = $this->getAllCriteria();
      if (isset($criterias[$ID])) {
         return $criterias[$ID];
      }
      return array();
   }


   /**
    * Get a action description by his ID
    *
    * @param $ID the action's ID
    *
    * @return the action array
   **/
   function getAction($ID) {

      $actions = $this->getAllActions();
      if (isset($actions[$ID])) {
         return $actions[$ID];
      }
      return array();
   }


   /**
    * Get a criteria description by his ID
    *
    * @param $ID the criteria's ID
    *
    * @return the criteria's description
   **/

   function getCriteriaName($ID) {

      $criteria = $this->getCriteria($ID);
      if (isset($criteria['name'])) {
         return $criteria['name'];
      }
      return __('Unavailable')."&nbsp;";
   }


   /**
    * Get a action description by his ID
    *
    * @param $ID the action's ID
    *
    * @return the action's description
   **/
   function getActionName($ID) {

      $action = $this->getAction($ID);
      if (isset($action['name'])) {
         return $action['name'];
      }
      return "&nbsp;";
   }


   /**
    * Process the rule
    *
    * @param &$input    the input data used to check criterias
    * @param &$output   the initial ouput array used to be manipulate by actions
    * @param &$params   parameters for all internal functions
    *
    * @return the output array updated by actions.
    *         If rule matched add field _rule_process to return value
   **/
   function process(&$input, &$output, &$params) {

      if (count($this->criterias)) {
         $this->regex_results     = array();
         $this->criterias_results = array();
         $input = $this->prepareInputDataForProcess($input, $params);

         if ($this->checkCriterias($input)) {
            unset($output["_no_rule_matches"]);
            $output = $this->executeActions($output, $params);
            //Hook
            $hook_params["sub_type"] = $this->getType();
            $hook_params["ruleid"]   = $this->fields["id"];
            $hook_params["input"]    = $input;
            $hook_params["output"]   = $output;
            Plugin::doHook("rule_matched", $hook_params);
            $output["_rule_process"] = true;
         }
      }
   }


   /**
    * Check criterias
    *
    * @param $input the input data used to check criterias
    *
    * @return boolean if criterias match
   **/
   function checkCriterias($input) {

      reset($this->criterias);

      if ($this->fields["match"] == self::AND_MATCHING) {
         $doactions = true;

         foreach ($this->criterias as $criteria) {
            $definition_criteria = $this->getCriteria($criteria->fields['criteria']);
            if (!isset($definition_criteria['is_global']) || !$definition_criteria['is_global']) {
               $doactions &= $this->checkCriteria($criteria, $input);
               if (!$doactions) {
                  break;
               }
             }
         }

      } else { // OR MATCHING
         $doactions = false;
         foreach ($this->criterias as $criteria) {
            $definition_criteria = $this->getCriteria($criteria->fields['criteria']);

            if (!isset($definition_criteria['is_global'])
                || !$definition_criteria['is_global']) {
               $doactions |= $this->checkCriteria($criteria,$input);
               if ($doactions) {
                  break;
               }
            }
         }
      }

      //If all simple criteria match, and if necessary, check complex criteria
      if ($doactions) {
         return $this->findWithGlobalCriteria($input);
      }
      return false;
   }


   /**
    * Check criterias
    *
    * @param $input           the input data used to check criterias
    * @param &$check_results
    *
    * @return boolean if criterias match
   **/
   function testCriterias($input, &$check_results) {

      reset($this->criterias);

      foreach ($this->criterias as $criteria) {
         $result = $this->checkCriteria($criteria,$input);
         $check_results[$criteria->fields["id"]]["name"]   = $criteria->fields["criteria"];
         $check_results[$criteria->fields["id"]]["value"]  = $criteria->fields["pattern"];
         $check_results[$criteria->fields["id"]]["result"] = ((!$result)?0:1);
         $check_results[$criteria->fields["id"]]["id"]     = $criteria->fields["id"];
      }
   }


   /**
    * Process a criteria of a rule
    *
    * @param &$criteria  criteria to check
    * @param &$input     the input data used to check criterias
   **/
   function checkCriteria(&$criteria, &$input) {

      $partial_regex_result = array();
      // Undefine criteria field : set to blank
      if (!isset($input[$criteria->fields["criteria"]])) {
         $input[$criteria->fields["criteria"]] = '';
      }

      //If the value is not an array
      if (!is_array($input[$criteria->fields["criteria"]])) {
         $value = $this->getCriteriaValue($criteria->fields["criteria"],
                                          $criteria->fields["condition"],
                                          $input[$criteria->fields["criteria"]]);

         $res   = RuleCriteria::match($criteria, $value, $this->criterias_results,
                                      $partial_regex_result);
      } else {
         //If the value if, in fact, an array of values
         // Negative condition : Need to match all condition (never be)
         if (in_array($criteria->fields["condition"], array(self::PATTERN_IS_NOT,
                                                            self::PATTERN_NOT_CONTAIN,
                                                            self::REGEX_NOT_MATCH,
                                                            self::PATTERN_DOES_NOT_EXISTS))) {
            $res = true;
            foreach ($input[$criteria->fields["criteria"]] as $tmp) {
               $value = $this->getCriteriaValue($criteria->fields["criteria"],
                                                $criteria->fields["condition"], $tmp);

               $res &= RuleCriteria::match($criteria, $value, $this->criterias_results,
                                           $partial_regex_result);
               if (!$res) {
                  break;
               }
            }

         // Positive condition : Need to match one
         } else {
            $res = false;
            foreach ($input[$criteria->fields["criteria"]] as $crit) {
               $value = $this->getCriteriaValue($criteria->fields["criteria"],
                                                $criteria->fields["condition"], $crit);

               $res |= RuleCriteria::match($criteria, $value, $this->criterias_results,
                                           $partial_regex_result);
            }
         }
      }

      // Found regex on this criteria
      if (count($partial_regex_result)) {
         // No regex existing : put found
         if (!count($this->regex_results)) {
            $this->regex_results = $partial_regex_result;

         } else { // Already existing regex : append found values
            $temp_result = array();
            foreach ($partial_regex_result as $new) {

               foreach ($this->regex_results as $old) {
                  $temp_result[] = array_merge($old,$new);
               }
            }
            $this->regex_results = $temp_result;
         }
      }

      return $res;
   }


   /**
    * @param $input
   **/
   function findWithGlobalCriteria($input) {
      return true;
   }


   /**
    * Specific prepare input datas for the rule
    *
    * @param $input  the input data used to check criterias
    * @param $params parameters
    *
    * @return the updated input datas
   **/
   function prepareInputDataForProcess($input, $params) {
      return $input;
   }


   /**
    * Get all data needed to process rules (core + plugins)
    *
    * @since 0.84
    * @param $input  the input data used to check criterias
    * @param $params parameters
    *
    * @return the updated input datas
   **/
   function prepareAllInputDataForProcess($input, $params) {
      global $PLUGIN_HOOKS;

      $input = $this->prepareInputDataForProcess($input, $params);
      if (isset($PLUGIN_HOOKS['use_rules'])) {
         foreach ($PLUGIN_HOOKS['use_rules'] as $plugin => $val) {
            if (is_array($val) && in_array($this->getType(), $val)) {
               $results = Plugin::doOneHook($plugin, "rulePrepareInputDataForProcess",
                                            array('input'  => $input,
                                                  'params' => $params));
               if (is_array($results)) {
                  foreach ($results as $result) {
                     $input[] = $result;
                  }
               }
            }
         }
      }
      return $input;
   }


   /**
    *
    * Execute plugins actions if needed
    *
    * @since 0.84
    *
    * @param $action
    * @param $output rule execution output
    * @param $params parameters
    *
    * @return output parameters array updated
    */
   function executePluginsActions($action, $output, $params) {
      global $PLUGIN_HOOKS;

      if (isset($PLUGIN_HOOKS['use_rules'])) {
         $params['criterias_results'] = $this->criterias_results;
         $params['rule_itemtype']     = $this->getType();
         foreach ($PLUGIN_HOOKS['use_rules'] as $plugin => $val) {
            if (is_array($val) && in_array($this->getType(), $val)) {
               $results = Plugin::doOneHook($plugin, "executeActions", array('output' => $output,
                                                                             'params' => $params,
                                                                             'action' => $action));
               if (is_array($results)) {
                  foreach ($results as $id => $result) {
                     $output[$id] = $result;
                  }
               }

            }
         }
      }
      return $output;
   }


   /**
    * Execute the actions as defined in the rule
    *
    * @param $output the fields to manipulate
    * @param $params parameters
    *
    * @return the $output array modified
   **/
   function executeActions($output, $params) {

      if (count($this->actions)) {
         foreach ($this->actions as $action) {
            switch ($action->fields["action_type"]) {
               case "assign" :
                  $output[$action->fields["field"]] = $action->fields["value"];
                  break;

               case "append" :
                  $actions = $this->getActions();
                  $value   = $action->fields["value"];
                  if (isset($actions[$action->fields["field"]]["appendtoarray"])
                      && isset($actions[$action->fields["field"]]["appendtoarrayfield"])) {
                     $value = $actions[$action->fields["field"]]["appendtoarray"];
                     $value[$actions[$action->fields["field"]]["appendtoarrayfield"]]
                            = $action->fields["value"];
                  }
                  $output[$actions[$action->fields["field"]]["appendto"]][] = $value;
                  break;

               case "regex_result" :
               case "append_regex_result" :
                  //Regex result : assign value from the regex
                  //Append regex result : append result from a regex
                  if ($action->fields["action_type"] == "append_regex_result") {
                     $res = (isset($params[$action->fields["field"]])
                             ?$params[$action->fields["field"]]:"");
                  } else {
                     $res = "";
                  }
                  if (isset($this->regex_results[0])) {
                     $res .= RuleAction::getRegexResultById($action->fields["value"],
                                                            $this->regex_results[0]);
                  } else {
                     $res .= $action->fields["value"];
                  }
                  $output[$action->fields["field"]] = $res;
                  break;

               default:
                  //plugins actions
                  $executeaction = new self();
                  $ouput = $executeaction->executePluginsActions($action, $output, $params);
                  break;
            }
         }
      }
      return $output;
   }


   function cleanDBonPurge() {
      global $DB;

      // Delete a rule and all associated criterias and actions
      if (!empty($this->ruleactionclass)) {
         $sql = "DELETE
                 FROM `".getTableForItemType($this->ruleactionclass)."`
                 WHERE `".$this->rules_id_field."` = '".$this->fields['id']."'";
         $DB->query($sql);
      }

      if (!empty($this->rulecriteriaclass)) {
         $sql = "DELETE
                 FROM `".getTableForItemType($this->rulecriteriaclass)."`
                 WHERE `".$this->rules_id_field."` = '".$this->fields['id']."'";
         $DB->query($sql);
      }
   }


   /**
    * Show the minimal form for the rule
    *
    * @param $target             link to the form page
    * @param $first              is it the first rule ?(false by default)
    * @param $last               is it the last rule ? (false by default)
    * @param $display_entities   display entities / make it read only display (false by default)
   **/
   function showMinimalForm($target, $first=false, $last=false, $display_entities=false) {
      global $CFG_GLPI;

      $canedit = (self::canUpdate() && !$display_entities);
      echo "<tr class='tab_bg_1'>";

      if ($canedit) {
         echo "<td width='10'>";
         Html::showMassiveActionCheckBox(__CLASS__, $this->fields["id"]);
         echo "</td>";

      } else {
         echo "<td>&nbsp;</td>";
      }

      $link = $this->getLink();
      if (!empty($this->fields["comment"])) {
         $link = sprintf(__('%1$s %2$s'), $link,
                         Html::showToolTip($this->fields["comment"], array('display' => false)));
      }
      echo "<td>".$link."</td>";
      echo "<td>".$this->fields["description"]."</td>";
      echo "<td>".Dropdown::getYesNo($this->fields["is_active"])."</td>";

      if ($display_entities) {
         $entname = Dropdown::getDropdownName('glpi_entities', $this->fields['entities_id']);
         if ($this->maybeRecursive()
             && $this->fields['is_recursive']) {
            $entname = sprintf(__('%1$s %2$s'), $entname, "<span class='b'>(".__('R').")</span>");
         }

         echo "<td>".$entname."</td>";
      }

      if (!$display_entities) {
         if ($this->can_sort
             && !$first
             && $canedit) {
            echo "<td>";
            Html::showSimpleForm($target, array('action' => 'up'), '',
                                 array('type' => $this->fields["sub_type"],
                                       'id'   => $this->fields["id"]),
                                 $CFG_GLPI["root_doc"]."/pics/deplier_up.png");
            echo "</td>";
         } else {
            echo "<td>&nbsp;</td>";
         }
      }

      if (!$display_entities) {
         if ($this->can_sort
             && !$last
             && $canedit) {
            echo "<td>";
            Html::showSimpleForm($target, array('action' => 'down'), '',
                                 array('type' => $this->fields["sub_type"],
                                       'id'   => $this->fields["id"]),
                                 $CFG_GLPI["root_doc"]."/pics/deplier_down.png");
            echo "</td>";
         } else {
            echo "<td>&nbsp;</td>";
         }
      }
      echo "</tr>\n";
   }


   /**
    * @see CommonDBTM::prepareInputForAdd()
   **/
   function prepareInputForAdd($input) {

      // Before adding, add the ranking of the new rule
      $input["ranking"] = $this->getNextRanking();
      //If no uuid given, generate a new one
      if (!isset($input['uuid'])) {
         $input["uuid"] = self::getUuid();
      }

      return $input;
   }


   /**
    * Get the next ranking for a specified rule
   **/
   function getNextRanking() {
      global $DB;

      $sql = "SELECT MAX(`ranking`) AS rank
              FROM `glpi_rules`
              WHERE `sub_type` = '".$this->getType()."'";
      $result = $DB->query($sql);

      if ($DB->numrows($result) > 0) {
         $datas = $DB->fetch_assoc($result);
         return $datas["rank"] + 1;
      }
      return 0;
   }


   /**
    * Show the minimal form for the action rule
    *
    * @param $fields    datas used to display the action
    * @param $canedit   can edit the actions rule ?
   **/
   function showMinimalActionForm($fields, $canedit) {

      echo "<tr class='tab_bg_1'>";
      if ($canedit) {
         echo "<td width='10'>";
         Html::showMassiveActionCheckBox($this->ruleactionclass, $fields["id"]);
         echo "</td>";
      }
      echo $this->getMinimalActionText($fields);
      echo "</tr>\n";
   }


   /**
    * Show preview result of a rule
    *
    * @param $target    where to go if action
    * @param $input     input data array
    * @param $params    params used (see addSpecificParamsForPreview)
   **/
   function showRulePreviewResultsForm($target, $input, $params) {

      $actions       = $this->getAllActions();
      $check_results = array();
      $output        = array();

      //Test all criterias, without stopping at the first good one
      $this->testCriterias($input, $check_results);
      //Process the rule
      $this->process($input, $output, $params);
      if (!$criteria = getItemForItemtype($this->rulecriteriaclass)) {
         return;
      }

      echo "<div class='spaced'>";
      echo "<table class='tab_cadrehov'>";
      echo "<tr><th colspan='4'>" . __('Result details') . "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center b'>"._n('Criterion', 'Criteria', 1)."</td>";
      echo "<td class='center b'>".__('Condition')."</td>";
      echo "<td class='center b'>".__('Reason')."</td>";
      echo "<td class='center b'>".__('Validation')."</td>";
      echo "</tr>\n";

      foreach ($check_results as $ID => $criteria_result) {
         echo "<tr class='tab_bg_1'>";
         $criteria->getFromDB($criteria_result["id"]);
         echo $this->getMinimalCriteriaText($criteria->fields);
         if ($criteria->fields['condition'] != self::PATTERN_FIND) {
            echo "<td class='b'>".Dropdown::getYesNo($criteria_result["result"])."</td></tr>\n";
         } else {
            echo "<td class='b'>".Dropdown::EMPTY_VALUE."</td></tr>\n";
         }
      }
      echo "</table></div>";

      $global_result = (isset($output["_rule_process"])?1:0);

      echo "<div class='spaced'>";
      echo "<table class='tab_cadrehov'>";
      echo "<tr><th colspan='2'>" . __('Rule results') . "</th></tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td class='center b'>".__('Validation')."</td><td>";
      echo Dropdown::getYesNo($global_result)."</td></tr>";

      $output = $this->preProcessPreviewResults($output);

      foreach ($output as $criteria => $value) {
         if (isset($actions[$criteria])) {
            echo "<tr class='tab_bg_2'>";
            echo "<td>".$actions[$criteria]["name"]."</td>";
            if (isset($actions[$criteria]['type'])) {
               $actiontype = $actions[$criteria]['type'];
            } else {
               $actiontype ='';
            }
            echo "<td>".$this->getActionValue($criteria, $actiontype, $value);
            echo "</td></tr>\n";
         }
      }

      //If a regular expression was used, and matched, display the results
      if (count($this->regex_results)) {
         echo "<tr class='tab_bg_2'>";
         echo "<td>".__('Result of the regular expression')."</td>";
         echo "<td>";
         if (!empty($this->regex_results[0])) {
            echo "<table class='tab_cadre'>";
            echo "<tr><th>".__('Key')."</th><th>".__('Value')."</th></tr>";
            foreach ($this->regex_results[0] as $key => $value) {
               echo "<tr class='tab_bg_1'>";
               echo "<td>$key</td><td>$value</td></tr>";
            }
            echo "</table>";
         }
         echo "</td></tr>\n";
      }
      echo "</tr>\n";
      echo "</table></div>";
   }


   /**
    * Show the minimal form for the criteria rule
    *
    * @param $fields    datas used to display the criteria
    * @param $canedit   can edit the criterias rule ?
   **/
   function showMinimalCriteriaForm($fields, $canedit) {

      echo "<tr class='tab_bg_1'>";
      if ($canedit) {
         echo "<td width='10'>";
         Html::showMassiveActionCheckBox($this->rulecriteriaclass, $fields["id"]);
         echo "</td>";
      }

      echo $this->getMinimalCriteriaText($fields);
      echo "</tr>\n";
   }


   /**
    * @param $fields
   **/
   function getMinimalCriteriaText($fields) {

      $text  = "<td>" . $this->getCriteriaName($fields["criteria"]) . "</td>";
      $text .= "<td>" . RuleCriteria::getConditionByID($fields["condition"], get_class($this),
                                                       $fields["criteria"])."</td>";
      $text .= "<td>" . $this->getCriteriaDisplayPattern($fields["criteria"], $fields["condition"],
                                                         $fields["pattern"]) . "</td>";
      return $text;
   }


   /**
    * @param $fields
   **/
   function getMinimalActionText($fields) {

      $text  = "<td>" . $this->getActionName($fields["field"]) . "</td>";
      $text .= "<td>" . RuleAction::getActionByID($fields["action_type"]) . "</td>";
      $text .= "<td>" . $this->getActionValue($fields["field"], $fields['action_type'],
                                              $fields["value"]) . "</td>";
      return $text;
   }


   /**
    * Return a value associated with a pattern associated to a criteria to display it
    *
    * @param $ID        the given criteria
    * @param $condition condition used
    * @param $pattern   the pattern
   **/
   function getCriteriaDisplayPattern($ID, $condition, $pattern) {

      if (($condition == self::PATTERN_EXISTS)
          || ($condition == self::PATTERN_DOES_NOT_EXISTS)
          || ($condition == self::PATTERN_FIND)) {
          return __('Yes');

      } else if (in_array($condition, array(self::PATTERN_IS, self::PATTERN_IS_NOT,
                                            self::PATTERN_NOT_UNDER, self::PATTERN_UNDER))) {
         $crit = $this->getCriteria($ID);

         if (isset($crit['type'])) {
            switch ($crit['type']) {
               case "yesonly" :
               case "yesno" :
                  return Dropdown::getYesNo($pattern);

               case "dropdown" :
                  $addentity = Dropdown::getDropdownName($crit["table"], $pattern);
                  if ($this->isEntityAssign()) {
                     $itemtype = getItemTypeForTable($crit["table"]);
                     $item     = getItemForItemtype($itemtype);
                     if ($item
                         && $item->getFromDB($pattern)
                         && $item->isEntityAssign()) {
                        $addentity = sprintf(__('%1$s (%2$s)'), $addentity,
                                             Dropdown::getDropdownName('glpi_entities',
                                                                       $item->getEntityID()));
                     }
                  }
                  $tmp = $addentity;
                  return (($tmp == '&nbsp;') ? NOT_AVAILABLE : $tmp);

               case "dropdown_users" :
                  return getUserName($pattern);

               case "dropdown_tracking_itemtype" :
                  if ($item = getItemForItemtype($pattern)) {
                     return $item->getTypeName(1);
                  }
                  if (empty($pattern)) {
                     return __('General');
                  }
                  break;

               case "dropdown_status" :
                  return Ticket::getStatus($pattern);

               case "dropdown_priority" :
                  return Ticket::getPriorityName($pattern);

               case "dropdown_urgency" :
                  return Ticket::getUrgencyName($pattern);

               case "dropdown_impact" :
                  return Ticket::getImpactName($pattern);

               case "dropdown_tickettype" :
                  return Ticket::getTicketTypeName($pattern);
            }
         }
      }
      if ($result = $this->getAdditionalCriteriaDisplayPattern($ID, $condition, $pattern)) {
         return $result;
      }
      return $pattern;
   }


   /**
    * Used to get specific criteria patterns
    *
    * @param $ID        the given criteria
    * @param $condition condition used
    * @param $pattern   the pattern
    *
    * @return a value associated with the criteria, or false otherwise
   **/
   function getAdditionalCriteriaDisplayPattern($ID, $condition, $pattern) {
      return false;
   }


   /**
    * Display item used to select a pattern for a criteria
    *
    * @param $name      criteria name
    * @param $ID        the given criteria
    * @param $condition condition used
    * @param $value     the pattern (default '')
    * @param $test      Is to test rule ? (false by default)
   **/
   function displayCriteriaSelectPattern($name, $ID, $condition, $value="", $test=false) {

      $crit    = $this->getCriteria($ID);
      $display = false;
      $tested  = false;

      if (isset($crit['type'])
          && ($test
              || in_array($condition, array(self::PATTERN_IS, self::PATTERN_IS_NOT,
                                            self::PATTERN_NOT_UNDER, self::PATTERN_UNDER)))) {

         switch ($crit['type']) {
            case "yesonly" :
               Dropdown::showYesNo($name, $crit['table'], 0);
               $display = true;
               break;

            case "yesno" :
               Dropdown::showYesNo($name, $crit['table']);
               $display = true;
               break;

            case "dropdown" :
               $param = array('name'  => $name,
                              'value' => $value);
               if (isset($crit['condition'])) {
                  $param['condition'] = $crit['condition'];
               }

               Dropdown::show(getItemTypeForTable($crit['table']), $param);

               $display = true;
               break;

            case "dropdown_users" :
               User::dropdown(array('value'  => $value,
                                    'name'   => $name,
                                    'right'  => 'all'));
               $display = true;
               break;

            case "dropdown_tracking_itemtype" :
               Dropdown::showItemTypes($name, array_keys(Ticket::getAllTypesForHelpdesk()));
               $display = true;
               break;

            case "dropdown_urgency" :
               Ticket::dropdownUrgency(array('name'  => $name,
                                             'value' => $value));
               $display = true;
               break;

            case "dropdown_impact" :
               Ticket::dropdownImpact(array('name'  => $name,
                                            'value' => $value));
               $display = true;
               break;

            case "dropdown_priority" :
               Ticket::dropdownPriority(array('name'  => $name,
                                              'value' => $value));
               $display = true;
               break;

            case "dropdown_status" :
               Ticket::dropdownStatus($name, $value);
               $display = true;
               break;

            case "dropdown_tickettype" :
               Ticket::dropdownType($name, array('value' => $value));
               $display = true;
               break;
         }
         $tested = true;
      }
      //Not a standard condition
      if (!$tested) {
        $display = $this->displayAdditionalRuleCondition($condition, $crit, $name, $value, $test);
      }

      if (($condition == self::PATTERN_EXISTS)
          || ($condition == self::PATTERN_DOES_NOT_EXISTS)) {
         echo "<input type='hidden' name='$name' value='1'>";
         $display = true;
      }

      if (!$display
          && ($rc = getItemForItemtype($this->rulecriteriaclass))) {
         Html::autocompletionTextField($rc, "pattern", array('name'  => $name,
                                                             'value' => $value,
                                                             'size'  => 70));
      }
   }


   /**
    * Return a "display" value associated with a pattern associated to a criteria
    *
    * @param $ID     the given action
    * @param $type   the type of action
    * @param $value  the value
   **/
   function getActionValue($ID, $type, $value) {

      $action = $this->getAction($ID);
      if (isset($action['type'])) {

         switch ($action['type']) {
            case "dropdown" :
               if ($type=='fromuser' || $type=='fromitem') {
                  return Dropdown::getYesNo($value);
               }
               // $type == assign
               $tmp = Dropdown::getDropdownName($action["table"], $value);
               return (($tmp == '&nbsp;') ? NOT_AVAILABLE : $tmp);

            case "dropdown_status" :
               return Ticket::getStatus($value);

            case "dropdown_assign" :
            case "dropdown_users" :
            case "dropdown_users_validate" :
               return getUserName($value);

            case "yesonly" :
            case "yesno" :
               return Dropdown::getYesNo($value);

            case "dropdown_urgency" :
               return Ticket::getUrgencyName($value);

            case "dropdown_impact" :
               return Ticket::getImpactName($value);

            case "dropdown_priority" :
               return Ticket::getPriorityName($value);

            case "dropdown_tickettype" :
               return Ticket::getTicketTypeName($value);

            case "dropdown_management" :
               return Dropdown::getGlobalSwitch($value);

            default :
               return $this->displayAdditionRuleActionValue($value);
         }
      }

      return $value;
   }


   /**
    * Return a value associated with a pattern associated to a criteria to display it
    *
    * @param $ID        the given criteria
    * @param $condition condition used
    * @param $value     the pattern
   **/
   function getCriteriaValue($ID, $condition, $value) {

      if (!in_array($condition, array(self::PATTERN_DOES_NOT_EXISTS, self::PATTERN_EXISTS,
                                      self::PATTERN_IS, self::PATTERN_IS_NOT,
                                      self::PATTERN_NOT_UNDER, self::PATTERN_UNDER))) {
         $crit = $this->getCriteria($ID);
         if (isset($crit['type'])) {

            switch ($crit['type']) {
               case "dropdown" :
                  $tmp = Dropdown::getDropdownName($crit["table"], $value);
                  // return empty string to be able to check if set
                  if ($tmp == '&nbsp;') {
                     return '';
                  }
                  return $tmp;

               case "dropdown_assign" :
               case "dropdown_users" :
                  return getUserName($value);

               case "yesonly" :
               case "yesno"  :
                  return Dropdown::getYesNo($value);

               case "dropdown_impact" :
                  return Ticket::getImpactName($value);

               case "dropdown_urgency" :
                  return Ticket::getUrgencyName($value);

               case "dropdown_priority" :
                  return Ticket::getPriorityName($value);
            }
         }
      }
      return $value;
   }


   /**
    * Function used to display type specific criterias during rule's preview
    *
    * @param $fields fields values
   **/
   function showSpecificCriteriasForPreview($fields) {
   }


   /**
    * Function used to add specific params before rule processing
    *
    * @param $params parameters
   **/
   function addSpecificParamsForPreview($params) {
      return $params;
   }


   /**
    * Criteria form used to preview rule
    *
    * @param $target    target of the form
    * @param $rules_id  ID of the rule
   **/
   function showRulePreviewCriteriasForm($target, $rules_id) {
      global $DB;

      $criterias = $this->getAllCriteria();

      if ($this->getRuleWithCriteriasAndActions($rules_id, 1, 0)) {
         echo "<form name='testrule_form' id='testrule_form' method='post' action='$target'>\n";
         echo "<div class='spaced'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='3'>" . _n('Criterion', 'Criteria', 2) . "</th></tr>";

         $type_match        = (($this->fields["match"] == self::AND_MATCHING) ?__('and') :__('or'));
         $already_displayed = array();
         $first             = true;

         //Brower all criterias
         foreach ($this->criterias as $criteria) {

            //Look for the criteria in the field of already displayed criteria :
            //if present, don't display it again
            if (!in_array($criteria->fields["criteria"],$already_displayed)) {
               $already_displayed[] = $criteria->fields["criteria"];
               echo "<tr class='tab_bg_1'>";
               echo "<td>";

               if ($first) {
                  echo "&nbsp;";
                  $first = false;
               } else {
                  echo $type_match;
               }

               echo "</td>";
               $criteria_constants = $criterias[$criteria->fields["criteria"]];
               echo "<td>".$criteria_constants["name"]."</td>";
               echo "<td>";
               $value = "";
               if (isset($_POST[$criteria->fields["criteria"]])) {
                  $value = $_POST[$criteria->fields["criteria"]];
               }

               $this->displayCriteriaSelectPattern($criteria->fields['criteria'],
                                                   $criteria->fields['criteria'],
                                                   $criteria->fields['condition'], $value, true);
               echo "</td></tr>\n";
            }
         }
         $this->showSpecificCriteriasForPreview($_POST);

         echo "<tr><td class='tab_bg_2 center' colspan='3'>";
         echo "<input type='submit' name='test_rule' value=\""._sx('button','Test')."\"
                class='submit'>";
         echo "<input type='hidden' name='".$this->rules_id_field."' value='$rules_id'>";
         echo "<input type='hidden' name='sub_type' value='" . $this->getType() . "'>";
         echo "</td></tr>\n";
         echo "</table></div>\n";
         Html::closeForm();
      }
   }


   /**
    * @param $output
   **/
   function preProcessPreviewResults($output) {
      global $PLUGIN_HOOKS;

      if (isset($PLUGIN_HOOKS['use_rules'])) {
         $params['criterias_results'] = $this->criterias_results;
         $params['rule_itemtype']     = $this->getType();
         foreach ($PLUGIN_HOOKS['use_rules'] as $plugin => $val) {
            if (is_array($val) && in_array($this->getType(), $val)) {
               $results = Plugin::doOneHook($plugin, "preProcessRulePreviewResults",
                                            array('output' => $output,
                                                  'params' => $params));
               if (is_array($results)) {
                  foreach ($results as $id => $result) {
                     $output[$id] = $result;
                  }
               }
            }
         }
      }
      return $output;
   }


   /**
    * Dropdown rules for a defined sub_type of rule
    *
    * @param $options   array of possible options:
    *    - name : string / name of the select (default is depending itemtype)
    *    - sub_type : integer / sub_type of rule
   **/
   static function dropdown($options=array()) {
      global $DB, $CFG_GLPI;

      $p['sub_type']        = '';
      $p['name']            = 'rules_id';
      $p['entity_restrict'] = '';

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      if ($p['sub_type'] == '') {
         return false;
      }

      $p['condition'] = "`sub_type` = '".$p['sub_type']."'";
      return Dropdown::show('Rule',$p);
   }


   /**
    * @since version 0.84
   **/
   function getAllCriteria() {

      return self::doHookAndMergeResults("getRuleCriteria", $this->getCriterias(),
                                         $this->getType());
   }


   function getCriterias() {
      return array();
   }


   /**
    * @since version 0.84
   */
   function getAllActions() {
      return self::doHookAndMergeResults("getRuleActions", $this->getActions(), $this->getType());
   }


   function getActions() {
      return array();
   }


   /**
    *  Execute a hook if necessary and merge results
    *
    *  @since version 0.84
    *
    * @param $hook            the hook to execute
    * @param $params   array  input parameters
    * @param $itemtype        (default '')
    *
    * @return input parameters merged with hook parameters
   **/
   static function doHookAndMergeResults($hook, $params=array(), $itemtype='') {
      global $PLUGIN_HOOKS;

      if (empty($itemtype)) {
         $itemtype = static::getType();
      }

      //Agregate all plugins criteria for this rules engine
      $toreturn = $params;
      if (isset($PLUGIN_HOOKS['use_rules'])) {
         foreach ($PLUGIN_HOOKS['use_rules'] as $plugin => $val) {
            if (is_array($val) && in_array($itemtype, $val)) {
               $results = Plugin::doOneHook($plugin, $hook, array('rule_itemtype' => $itemtype,
                                                                  'values'        => $params));
               if (is_array($results)) {
                  foreach ($results as $id => $result) {
                     $toreturn[$id] = $result;
                  }
               }
            }
         }
      }
      return $toreturn;
   }


   /**
    * @param $sub_type
   **/
   static function getActionsByType($sub_type) {

      if ($rule = getItemForItemtype($sub_type)) {
         return $rule->getAllActions();
      }
      return array();
   }


   /**
    * Return all rules from database
    *
    * @param $crit array of criteria (at least, 'field' and 'value')
    *
    * @return array of Rule objects
   **/
   function getRulesForCriteria($crit) {
      global $DB;

      $rules = array();

      /// TODO : not working for SLALevels : no sub_type

      //Get all the rules whose sub_type is $sub_type and entity is $ID
      $query = "SELECT `".$this->getTable()."`.`id`
                FROM `".getTableForItemType($this->ruleactionclass)."`,
                     `".$this->getTable()."`
                WHERE `".getTableForItemType($this->ruleactionclass)."`.".$this->rules_id_field."
                           = `".$this->getTable()."`.`id`
                      AND `".$this->getTable()."`.`sub_type` = '".get_class($this)."'";

      foreach ($crit as $field => $value) {
         $query .= " AND `".getTableForItemType($this->ruleactionclass)."`.`$field` = '$value'";
      }

      foreach ($DB->request($query) as $rule) {
         $affect_rule = new Rule();
         $affect_rule->getRuleWithCriteriasAndActions($rule["id"], 0, 1);
         $rules[]     = $affect_rule;
      }
      return $rules;
   }


   /**
    * @param $ID
   **/
   function showNewRuleForm($ID) {

      echo "<form method='post' action='".Toolbox::getItemTypeFormURL('Entity')."'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='7'>" . $this->getTitle() . "</th></tr>\n";
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name') . "</td><td>";
      Html::autocompletionTextField($this, "name", array('value' => '',
                                                         'size'  => 33));
      echo "</td><td>".__('Description') . "</td><td>";
      Html::autocompletionTextField($this, "description", array('value' => '',
                                                                'size'  => 33));
      echo "</td><td>".__('Logical operator') . "</td><td>";
      $this->dropdownRulesMatch();
      echo "</td><td class='tab_bg_2 center'>";
      echo "<input type=hidden name='sub_type' value='".get_class($this)."'>";
      echo "<input type=hidden name='entities_id' value='-1'>";
      echo "<input type=hidden name='affectentity' value='$ID'>";
      echo "<input type=hidden name='_method' value='AddRule'>";
      echo "<input type='submit' name='execute' value=\""._sx('button','Add')."\" class='submit'>";
      echo "</td></tr>\n";
      echo "</table>";
      Html::closeForm();
   }


   /**
    * @param $item
   **/
   function showAndAddRuleForm($item) {

      $rand    = mt_rand();
      $canedit = self::canUpdate();

      if ($canedit
          && ($item->getType() == 'Entity')) {
         $this->showNewRuleForm($item->getField('id'));
      }

         //Get all rules and actions
      $crit = array('field' => getForeignKeyFieldForTable($item->getTable()),
                    'value' => $item->getField('id'));

      $rules = $this->getRulesForCriteria($crit);
      $nb    = count($rules);
      echo "<div class='spaced'>";

      if (!$nb) {
         echo "<table class='tab_cadre_fixehov'>";
         echo "<tr><th>" . __('No item found') . "</th>";
         echo "</tr>\n";
         echo "</table>\n";

      } else {
         if ($canedit) {
            Html::openMassiveActionsForm('mass'.get_called_class().$rand);
            $paramsma = array('num_displayed'    => $nb,
                              'specific_actions' => array('update' => _x('button', 'Update'),
                                                          'purge'  => _x('button',
                                                                         'Delete permanently')));
            Html::showMassiveActions(get_called_class(), $paramsma);
         }
         echo "<table class='tab_cadre_fixehov'><tr>";

         if ($canedit) {
            echo "<th width='10'>";
            Html::checkAllAsCheckbox('mass'.get_called_class().$rand);
            echo "</th>";
         }
         echo "<th>" . $this->getTitle() . "</th>";
         echo "<th>" . __('Description') . "</th>";
         echo "<th>" . __('Active') . "</th>";
         echo "</tr>\n";

         Session::initNavigateListItems(get_class($this),
                              //TRANS: %1$s is the itemtype name,
                              //       %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'),
                                                $item->getTypeName(1), $item->getName()));

         foreach ($rules as $rule) {
            Session::addToNavigateListItems(get_class($this), $rule->fields["id"]);
            echo "<tr class='tab_bg_1'>";

            if ($canedit) {
               echo "<td width='10'>";
               Html::showMassiveActionCheckBox(__CLASS__, $rule->fields["id"]);
               echo "</td>";
               echo "<td><a href='".Toolbox::getItemTypeFormURL(get_class($this))."?id=" .
                      $rule->fields["id"] . "&amp;onglet=1'>" .$rule->fields["name"] ."</a></td>";

            } else {
               echo "<td>" . $rule->fields["name"] . "</td>";
            }

            echo "<td>" . $rule->fields["description"] . "</td>";
            echo "<td>" . Dropdown::getYesNo($rule->fields["is_active"]) . "</td>";
            echo "</tr>\n";
         }
         echo "</table>\n";

         if ($canedit) {
            $paramsma['ontop'] = false;
            Html::showMassiveActions(get_called_class(), $paramsma);
            Html::closeForm();
         }
      }
      echo "</div>";
   }


   /**
    * @see CommonGLPI::defineTabs()
   **/
   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   /**
    * Add more criteria specific to this type of rule
   **/
   static function addMoreCriteria() {
      return array();
   }


   /**
    * Add more actions specific to this type of rule
    *
    * @param $value
   **/
   function displayAdditionRuleActionValue($value) {
      return $value;
   }


   /**
    * @param $condition
    * @param $criteria
    * @param $name
    * @param $value
    * @param $test         (false by default)
   **/
   function displayAdditionalRuleCondition($condition, $criteria, $name, $value, $test=false) {
      return false;
   }


   /**
    * @param $action array
   **/
   function displayAdditionalRuleAction(array $action) {
      return false;
   }



   /**
    * Clean Rule with Action or Criteria linked to an item
    *
    * @param $item                  Object
    * @param $field        string   name (default is FK to item)
    * @param $ruleitem              object (instance of Rules of SlaLevel)
    * @param $table        string   (glpi_ruleactions, glpi_rulescriterias or glpi_slalevelcriterias)
    * @param $valfield     string   (value or pattern)
    * @param $fieldfield   string   (criteria of field)
   **/
   private static function cleanForItemActionOrCriteria($item, $field, $ruleitem, $table,
                                                        $valfield, $fieldfield) {
      global $DB;

      $fieldid = getForeignKeyFieldForTable($ruleitem->getTable());

      if (empty($field)) {
         $field = getForeignKeyFieldForTable($item->getTable());
      }

      if (isset($item->input['_replace_by']) && ($item->input['_replace_by'] > 0)) {
         $query = "UPDATE `$table`
                   SET `$valfield` = '".$item->input['_replace_by']."'
                   WHERE `$valfield` = '".$item->getField('id')."'
                         AND `$fieldfield` LIKE '$field'";
         $DB->query($query);

      } else {
         $query = "SELECT `$fieldid`
                   FROM `$table`
                   WHERE `$valfield` = '".$item->getField('id')."'
                         AND `$fieldfield` LIKE '$field'";

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
               $input['is_active'] = 0;

               while ($data = $DB->fetch_assoc($result)) {
                  $input['id'] = $data[$fieldid];
                  $ruleitem->update($input);
               }
               Session::addMessageAfterRedirect(__('Rules using the object have been disabled.'),
                                                true);
            }
         }
      }
   }


   /**
    * Clean Rule with Action is assign to an item
    *
    * @param $item            Object
    * @param $field  string   name (default is FK to item) (default '')
   **/
   static function cleanForItemAction($item, $field='') {

      self::cleanForItemActionOrCriteria($item, $field,
                                         new self(), 'glpi_ruleactions', 'value', 'field');

      self::cleanForItemActionOrCriteria($item, $field,
                                         new SlaLevel(), 'glpi_slalevelactions', 'value', 'field');
   }


   /**
    * Clean Rule with Criteria on an item
    *
    * @param $item            Object
    * @param $field  string   name (default is FK to item) (default '')
   **/
   static function cleanForItemCriteria($item, $field='') {

      self::cleanForItemActionOrCriteria($item, $field,
                                         new self(), 'glpi_rulecriterias', 'pattern', 'criteria');
   }


   /**
    * @see CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate) {
         switch ($item->getType()) {
            case 'Entity' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $types      = array();
                  $collection = new RuleRightCollection();
                  if ($collection->canList()) {
                     $types[] = 'RuleRight';
                  }
                  $collection = new RuleImportEntityCollection();
                  if ($collection->canList()) {
                     $types[] = 'RuleImportEntity';
                  }
                  $collection = new RuleMailCollectorCollection();
                  if ($collection->canList()) {
                     $types[] = 'RuleMailCollector';
                  }
                  $nb = 0;
                  if (count($types)) {
                     $nb = countElementsInTable(array('glpi_rules', 'glpi_ruleactions'),
                                                "`glpi_ruleactions`.`rules_id` = `glpi_rules`.`id`
                                                  AND `glpi_rules`.`sub_type`
                                                         IN ('".implode("','",$types)."')
                                                  AND `glpi_ruleactions`.`field` = 'entities_id'
                                                  AND `glpi_ruleactions`.`value`
                                                            = '".$item->getID()."'");
                  }

                  return self::createTabEntry(self::getTypeName(2), 2);
               }
               return $this->getTypeName(2);

            case 'SLA' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry(self::getTypeName(2),
                                              countElementsInTable('glpi_ruleactions',
                                                                   "`field` = 'slas_id'
                                                                     AND `value`
                                                                        = '".$item->getID()."'"));
               }
               return $this->getTypeName(2);

            default:
               if ($item instanceof Rule) {
                  return sprintf(__('%1$s / %2$s'), _n('Criterion', 'Criteria', 2),
                                 _n('Action', 'Actions', 2));
               }
         }
      }
      return '';
   }


   /**
    * @param $item         CommonGLPI object
    * @param $tabnum       (default 1)
    * @param $withtemplate (default 0)
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType() == 'Entity') {
         $collection = new RuleRightCollection();
         if ($collection->canList()) {
            $ldaprule = new RuleRight();
            $ldaprule->showAndAddRuleForm($item);
         }

         $collection = new RuleImportEntityCollection();
         if ($collection->canList()) {
            $importrule = new RuleImportEntity();
            $importrule->showAndAddRuleForm($item);
         }

         $collection = new RuleMailCollectorCollection();
         if ($collection->canList()) {
            $mailcollector = new RuleMailCollector();
            $mailcollector->showAndAddRuleForm($item);
         }
      } else if ($item->getType() == 'SLA') {
         $rule = new RuleTicket();
         $rule->showAndAddRuleForm($item);

//       } else if ($item->getType() == 'SlaLevel') {
//          $rule = new RuleTicket();
//          $item->getRuleWithCriteriasAndActions($item->getID(), 0, 1);
//          $item->showActionsList($item->getID());

      } else if ($item instanceof Rule) {
         $item->getRuleWithCriteriasAndActions($item->getID(), 1, 1);
         $item->showCriteriasList($item->getID());
         $item->showActionsList($item->getID());
      }

      return true;
   }


   /**
    * Generate unique id for rule based on server name, glpi directory and basetime
    *
    * @since version 0.85
    *
    * @return uuid
   **/
   static function getUuid() {

      //encode uname -a, ex Linux localhost 2.4.21-0.13mdk #1 Fri Mar 14 15:08:06 EST 2003 i686
      $serverSubSha1 = substr(sha1(php_uname('a')), 0, 8);
      // encode script current dir, ex : /var/www/glpi_X
      $dirSubSha1    = substr(sha1(__FILE__), 0, 8);

      return uniqid("$serverSubSha1-$dirSubSha1-", true);
   }


   /**
    * Display debug information for current object
    *
    * @since version 0.85
   **/
   function showDebug() {

      echo "<div class='spaced'>";
      printf(__('%1$s: %2$s'), "<b>UUID</b>", $this->fields['uuid']);
      echo "</div>";
   }


}
?>
