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


// FUNCTIONS knowledgebase



/**
 * Print out an HTML "<form>" for Search knowbase item
 *
 * @param $target where to go
 * @param $contains search pattern
 * @param $parentID category ID
 * @param $faq display on faq ?
 * @return nothing (display the form)
 **/
function searchFormKnowbase($target,$contains,$parentID=0,$faq=0){
	global $LANG,$CFG_GLPI;

	if ($CFG_GLPI["public_faq"] == 0&&!haveRight("knowbase","r")&&!haveRight("faq","r")) return false;

	echo "<div>";
	echo "<table  class='center-h'><tr><td>";


	echo "<form method=get action=\"".$target."\">";
	echo "<table border='0' class='tab_cadre'>";

	echo "<tr ><th colspan='2'>".$LANG['search'][0].":</th></tr>";
	echo "<tr class='tab_bg_2' align='center'><td><input type='text' size='30' name=\"contains\" value=\"". stripslashes($contains) ."\" ></td>";

	echo "<td><input type='submit' value=\"".$LANG['buttons'][0]."\" class='submit' ></td></tr>";

	echo "</table></form>";

	echo "</td>";

	// Category select not for anonymous FAQ
	if (isset($_SESSION["glpiID"])&&!$faq){
		echo "<td><form method=get action=\"".$target."\">";
		echo "<table border='0' class='tab_cadre'>";
		echo "<tr ><th colspan='2'>".$LANG['buttons'][43]."</th></tr>";
		echo "<tr class='tab_bg_2'><td class='center'>";
		echo $LANG['common'][36]." : &nbsp; &nbsp;";
		dropdownValue("glpi_dropdown_kbcategories","parentID",$parentID);
		// ----***** TODO Dropdown qui affiche uniquement les categories contenant une FAQ

		echo "</td><td><input type='submit' value=\"".$LANG['buttons'][2]."\" class='submit' ></td></tr>";

		echo "</table></form></td>";
	}

	echo "</tr></table></div>";


}




/**
 * Show KB categories
 *
 * @param $target where to go
 * @param $parentID category ID
 * @param $faq display on faq ?
 * @return nothing (display the form)
 **/
function showKbCategoriesFirstLevel($target,$parentID=0,$faq=0){

	global $DB,$LANG,$CFG_GLPI;

	if($faq){
		if ($CFG_GLPI["public_faq"] == 0 && !haveRight("faq","r")) {
			return false;
		}

		// Get All FAQ categories
		if (!isset($_SESSION['glpi_faqcategories'])){
			$_SESSION['glpi_faqcategories']='(0)';
         $tmp=array();
			$query="SELECT DISTINCT categoryID
				FROM glpi_kbitems
				WHERE glpi_kbitems.faq = '1'";
			if ($result=$DB->query($query)){
				if ($DB->numrows($result)){
					while ($data=$DB->fetch_array($result)){
						if (!in_array($data['categoryID'],$tmp)){
							$tmp[]=$data['categoryID'];
							$tmp=array_merge($tmp,getAncestorsOfTreeItem('glpi_dropdown_kbcategories',$data['categoryID']));
						}
					}
				}
				if (count($tmp)){
					$_SESSION['glpi_faqcategories']="('".implode("','",$tmp)."')";
				}

			}

		}
		$query = "SELECT DISTINCT glpi_dropdown_kbcategories.*
			FROM glpi_dropdown_kbcategories
			WHERE ID IN ".$_SESSION['glpi_faqcategories']."
				AND (glpi_dropdown_kbcategories.parentID = '$parentID')
			ORDER  BY name ASC";
	}else{
		if (!haveRight("knowbase","r")) {
			return false;
		}
		$query = "SELECT *
			FROM glpi_dropdown_kbcategories
			WHERE  (glpi_dropdown_kbcategories.parentID = '$parentID')
			ORDER  BY name ASC";
	}

	/// Show category
	if ($result=$DB->query($query)){
		echo "<table class='tab_cadre_central'  >";
		echo "<tr><td colspan='3'><a  href=\"".$target."\"><img alt='' src='".$CFG_GLPI["root_doc"]."/pics/folder-open.png'  class='bottom'></a>";

		// Display Category
		if ($parentID!=0){
			$tmpID=$parentID;
			$todisplay="";
			while ($tmpID!=0){
				$query2="SELECT *
					FROM glpi_dropdown_kbcategories
					WHERE ID='$tmpID'";
				$result2=$DB->query($query2);
				if ($DB->numrows($result2)==1){
					$data=$DB->fetch_assoc($result2);
					$tmpID=$data["parentID"];
					$todisplay="<a href='$target?parentID=".$data["ID"]."'>".$data["name"]."</a>".(empty($todisplay)?"":" > ").$todisplay;
				} else $tmpID=0;
//				echo getDropdownName("glpi_dropdown_kbcategories",$parentID,"")."</td></tr>";
			}
			echo " > ".$todisplay;
		}

		if ($DB->numrows($result)>0){


				$i=0;
			while ($row=$DB->fetch_array($result)){
					// on affiche les résultats sur trois colonnes
					if ($i%3==0) { echo "<tr>";}
					$ID = $row["ID"];
					echo "<td class='tdkb_result'>";

					echo "<img alt='' src='".$CFG_GLPI["root_doc"]."/pics/folder.png'  hspace=\"5\" > <strong><a  href=\"".$target."?parentID=".$row["ID"]."\">".$row["name"]."</a></strong>\n";
					echo "<div class='kb_resume'>".resume_text($row['comments'],60)."</div>";

				if($i%3==2) { echo "</tr>\n"; }

				$i++;
			}

		}
	echo "<tr><td colspan='3'>&nbsp;</td></tr></table><br>";

	}

}



/**
*Print out list kb item
*
* @param $target where to go
* @param $contains search pattern
* @param $start where to start
* @param $parentID category ID
* @param $faq display on faq ?
**/
function showKbItemList($target,$contains,$start,$parentID,$faq=0){
	// Lists kb  Items

	global $DB,$CFG_GLPI, $LANG;



	$where="";
	$order="";
	$score="";

	// Build query
	if (isset($_SESSION["glpiID"])){
		$where = getEntitiesRestrictRequest("", "glpi_kbitems", "", "", true) . " AND ";
	} else {
		// Anonymous access
		if (isMultiEntitiesMode()){
			$where = "(glpi_kbitems.FK_entities=0 AND glpi_kbitems.recursive=1) AND ";
		}
	}

	if ($faq){ // helpdesk
		$where .= " (glpi_kbitems.faq = '1') AND ";
	}

	// a search with $contains
	if (strlen($contains)) {
		$search=unclean_cross_side_scripting_deep($contains);
		$score=" ,MATCH(glpi_kbitems.question,glpi_kbitems.answer) AGAINST('$search' IN BOOLEAN MODE) as SCORE ";
		$where_1=$where." MATCH(glpi_kbitems.question,glpi_kbitems.answer) AGAINST('$search' IN BOOLEAN MODE) ";
		$order="order by SCORE DESC";

		// preliminar query to allow alternate search if no result with fulltext
		$query_1 = "SELECT count(ID)
			FROM glpi_kbitems
			WHERE $where_1";
		$result_1 = $DB->query($query_1);
		$numrows_1 =  $DB->result($result_1,0,0);

		if ($numrows_1<=0) {// not result this fulltext try with alternate search

			$search1 = array(
			/* 1 */		'/\\\"/',
			/* 2 */		"/\+/",
			/* 3 */		"/\*/",
			/* 4 */		"/~/",
		 	/* 5 */		"/</",
			/* 6 */		"/>/",
			/* 7 */		"/\(/",
			/* 8 */		"/\)/",
			/* 9 */		"/\-/"
					);


 			$contains = preg_replace($search1,"", $contains);


			$where.= " (glpi_kbitems.question ".makeTextSearch($contains)." OR glpi_kbitems.answer ".makeTextSearch($contains).")"  ;
		 } else {
			$where=$where_1;
		}

	} else { // no search -> browse by category
		$where.="(glpi_kbitems.categoryID = '$parentID') ";
		$order="ORDER BY glpi_kbitems.question ASC";
	}

	if (!$start) {
		$start = 0;
	}

	$query = "SELECT  * $score FROM glpi_kbitems";
 	$query.=" WHERE $where $order";



	// Get it from database
	if ($result = $DB->query($query)) {
		$numrows =  $DB->numrows($result);
		$list_limit=$_SESSION['glpilist_limit'];
		// Limit the result, if no limit applies, use prior result
		if ($numrows > $list_limit&&!isset($_GET['export_all'])) {
			$query_limit = $query ." LIMIT ".intval($start).",".intval($list_limit)." ";
			$result_limit = $DB->query($query_limit);
			$numrows_limit = $DB->numrows($result_limit);
		} else {
			$numrows_limit = $numrows;
			$result_limit = $result;
		}

		if ($numrows_limit>0) {

			// Set display type for export if define
			$output_type=HTML_OUTPUT;
			if (isset($_GET["display_type"]))
				$output_type=$_GET["display_type"];

			// Pager
			$parameters="start=$start&amp;parentID=$parentID&amp;contains=$contains&amp;faq=$faq";
			if ($output_type==HTML_OUTPUT){
				printPager($start,$numrows,$_SERVER['PHP_SELF'],$parameters,KNOWBASE_TYPE);
			}

			$nbcols=1;
			// Display List Header
			echo displaySearchHeader($output_type,$numrows_limit+1,$nbcols);

			if ($output_type!=HTML_OUTPUT){
				$header_num=1;
				echo displaySearchHeaderItem($output_type,$LANG['knowbase'][3],$header_num);
				echo displaySearchHeaderItem($output_type,$LANG['knowbase'][4],$header_num);
			}

			// Num of the row (1=header_line)
			$row_num=1;
			for ($i=0; $i < $numrows_limit; $i++) {
				$data=$DB->fetch_array($result_limit);

				// Column num
				$item_num=1;
				$row_num++;

				echo displaySearchNewLine($output_type,$i%2);

				if ($output_type==HTML_OUTPUT){
					echo displaySearchItem($output_type,"<div class='kb'><a ".($data['faq']?" class='pubfaq' ":" class='knowbase' ")." href=\"".$target."?ID=".$data["ID"]."\">".resume_text($data["question"],80)."</a></div><div class='kb_resume'>".resume_text(html_clean(unclean_cross_side_scripting_deep($data["answer"])),600)."</div>",$item_num,$row_num);
				} else {
					echo displaySearchItem($output_type,$data["question"],$item_num,$row_num);
					echo displaySearchItem($output_type,html_clean(unclean_cross_side_scripting_deep(utf8_html_entity_decode($data["answer"]))),$item_num,$row_num);
				}
				// le cumul de fonction me plait pas TODO à optimiser.



				// End Line
				echo displaySearchEndLine($output_type);
			}

			// Display footer
			if ($output_type==PDF_OUTPUT_LANDSCAPE || $output_type==PDF_OUTPUT_PORTRAIT){
				echo displaySearchFooter($output_type,getDropdownName("glpi_dropdown_kbcategories",$parentID));
			} else {
				echo displaySearchFooter($output_type);
			}
			echo "<br>";
			if ($output_type==HTML_OUTPUT) {
				printPager($start,$numrows,$_SERVER['PHP_SELF'],$parameters,KNOWBASE_TYPE);
			}

		} else {
			if ($parentID!=0) {echo "<div class='center'><strong>".$LANG['search'][15]."</strong></div>";}
		}
	}

}



/**
 * Print out lists of recent and popular kb/faq
 *
 * @param $target where to go on action
 * @param $faq display only faq
 * @return nothing (display table)
 **/
function showKbViewGlobal($target,$faq=0){

	echo "<div>";
	echo "<table  class='center-h' width='950px'><tr><td align='center' valign='middle'>";

	showKbRecentPopular($target,"recent",$faq);

	echo "</td><td align='center' valign='middle'>";

	showKbRecentPopular($target,"popular",$faq);

	echo "</td></tr>";

	echo "</table>";
	echo "</div>";
}
/**
 * Print out list recent or popular kb/faq
 *
 * @param $target where to go on action
 * @param $type type : recent / popular
 * @param $faq display only faq
 * @return nothing (display table)
 **/
function showKbRecentPopular($target,$type,$faq=0){

	global $DB,$CFG_GLPI, $LANG;


	if ($type=="recent"){
		$orderby="ORDER BY date DESC";
		$title=$LANG['knowbase'][29];
	}else {
		$orderby="ORDER BY view DESC";
		$title=$LANG['knowbase'][30];
	}

	$faq_limit="";
	if (isset($_SESSION["glpiID"])){
		$faq_limit .= getEntitiesRestrictRequest(" WHERE ", "glpi_kbitems", "", "", true);
	} else {
		// Anonymous access
		if (isMultiEntitiesMode()){
			$faq_limit .= " WHERE (glpi_kbitems.FK_entities=0 AND glpi_kbitems.recursive=1)";
		} else {
			$faq_limit .= " WHERE 1";
		}
	}

	if($faq){ // FAQ
		$faq_limit.=" AND (glpi_kbitems.faq = '1')";
	}

	$query = "SELECT  *  FROM glpi_kbitems $faq_limit $orderby LIMIT 10";
	//echo $query;
	$result = $DB->query($query);
	$number = $DB->numrows($result);

	if ($number > 0) {
		echo "<table class='tab_cadrehov'>";

		echo "<tr><th>".$title."</th></tr>";

		while ($data=$DB->fetch_array($result)) {
			echo "<tr class='tab_bg_2'><td class='left'><a ".($data['faq']?" class='pubfaq' ":" class='knowbase' ")." href=\"".$target."?ID=".$data["ID"]."\">".resume_text($data["question"],80)."</a></td></tr>";
		}
		echo "</table>";
	}
}





/**
 * Print out an HTML Menu for knowbase item
 *
 * @param $ID
 * @return nothing (display the form)
 **/
function kbItemMenu($ID)
{
	global $LANG, $CFG_GLPI;

	$ki= new kbitem;

	if (!$ki->can($ID,'r')) {
		return false;
	}

	$edit=$ki->can($ID,'w');
	$isFAQ = $ki->fields["faq"];

	$editFAQ=haveRight("faq","w");

	echo "<table class='tab_cadre_fixe' cellpadding='10' ><tr><th colspan='3'>";

	if($isFAQ) {
		echo $LANG['knowbase'][10]."</th></tr>\n";
	} else {
		echo $LANG['knowbase'][11]."</th></tr>\n";
	}

	if ($edit) {
		echo "<tr>";

		if ($editFAQ) {
			if($isFAQ) {
				echo "<td align='center' width=\"33%\"><a class='icon_nav_move' href=\"".$CFG_GLPI["root_doc"]."/front/knowbase.form.php?ID=$ID&amp;removefromfaq=yes\"><img  src=\"".$CFG_GLPI["root_doc"]."/pics/faqremove.png\" alt='".$LANG['knowbase'][7]."' title='".$LANG['knowbase'][7]."'></a></td>\n";
			} else {
				echo "<td align='center' width=\"33%\"><a  class='icon_nav_move' href=\"".$CFG_GLPI["root_doc"]."/front/knowbase.form.php?ID=$ID&amp;addtofaq=yes\"><img  src=\"".$CFG_GLPI["root_doc"]."/pics/faqadd.png\" alt='".$LANG['knowbase'][5]."' title='".$LANG['knowbase'][5]."'></a></td>\n";
			}
		}

		echo "<td align='center' width=\"34%\"><a class='icon_nav_move' href=\"".$CFG_GLPI["root_doc"]."/front/knowbase.form.php?ID=$ID&amp;modify=yes\"><img  src=\"".$CFG_GLPI["root_doc"]."/pics/faqedit.png\" alt='".$LANG['knowbase'][8]."' title='".$LANG['knowbase'][8]."'></a></td>\n";
		echo "<td align='center' width=\"33%\"><a class='icon_nav_move' href=\"javascript:confirmAction('".addslashes($LANG['common'][55])."','".$CFG_GLPI["root_doc"]."/front/knowbase.form.php?ID=$ID&amp;delete=yes')\"><img  src=\"".$CFG_GLPI["root_doc"]."/pics/faqdelete.png\" alt='".$LANG['knowbase'][9]."' title='".$LANG['knowbase'][9]."'></a></td>";

		echo "</tr>\n";
	}

	echo "</table><br>\n";
}




/**
 * Print out (html) show item : question and answer
 *
 * @param $ID integer
 * @param $linkauthor display author link
 *
 * @return nothing (display item : question and answer)
 **/
function ShowKbItemFull($ID,$linkauthor=true){
	// show item : question and answer

	global $DB,$LANG,$CFG_GLPI;

	if (!haveRight("user","r")) $linkauthor=false;

	//update counter view
	$query="UPDATE glpi_kbitems SET view=view+1 WHERE ID = '$ID'";
	$DB->query($query);

	$ki= new kbitem;

	if ($ki->getFromDB($ID)){
		if ($ki->fields["faq"]){
			if ($CFG_GLPI["public_faq"] == 0&&!haveRight("faq","r")&&!haveRight("knowbase","r")) return false;
		}
		else
			if (!haveRight("knowbase","r")) return false;





		$categoryID = $ki->fields["categoryID"];
		$fullcategoryname = getTreeValueCompleteName("glpi_dropdown_kbcategories",$categoryID);

		echo "<table class='tab_cadre_fixe' cellpadding='10' ><tr><th colspan='2'>";

		echo $LANG['common'][36].": <a href='".$CFG_GLPI["root_doc"]."/front/".(isset($_SESSION['glpiactiveprofile'])&&$_SESSION['glpiactiveprofile']['interface']=="central"?"knowbase.php":"helpdesk.faq.php")."?parentID=$categoryID'>".$fullcategoryname."</a></th></tr>";

		if (!($CFG_GLPI["cache"]->start($ID."_".$_SESSION['glpilanguage'],"GLPI_".$ki->type))) {

			echo "<tr class='tab_bg_3'><td class='left' colspan='2'><h2>";
			echo ($ki->fields["faq"]) ? "".$LANG['knowbase'][3]."" : "".$LANG['knowbase'][14]."";
			echo "</h2>";

			echo $ki->fields["question"];

			echo "</td></tr>\n";
			echo "<tr  class='tab_bg_3'><td class='left' colspan='2'><h2>";
			echo ($ki->fields["faq"]) ? "".$LANG['knowbase'][4]."" : "".$LANG['knowbase'][15]."";
			echo "</h2>\n";

			$answer = unclean_cross_side_scripting_deep($ki->fields["answer"]);

			echo "<div id='kbanswer'>".$answer."</div>";
			echo "</td></tr>";

			echo "<tr><th class='tdkb'>";
			if($ki->fields["author"]){
				echo $LANG['common'][37]." : ";
				// Integer because true may be 2 and getUserName return array
				if ($linkauthor){
					$linkauthor=1;
				} else {
					$linkauthor=0;
				}

				echo getUserName($ki->fields["author"],$linkauthor);

				echo "&nbsp;&nbsp;|&nbsp;&nbsp;  ";
			}
			if($ki->fields["date"]){
				echo $LANG['knowbase'][27]." : ". convDateTime($ki->fields["date"]);
			}

			echo "</th><th class='tdkb'>";
			if($ki->fields["date_mod"]){
				echo  $LANG['common'][26]." : ".convDateTime($ki->fields["date_mod"])."&nbsp;&nbsp;|&nbsp;&nbsp; ";
			}
			echo $LANG['knowbase'][26]." : ".$ki->fields["view"]."</th></tr>";

			echo "</table><br>";

			$CFG_GLPI["cache"]->end();
		}
		return true;
	} else return false;
}


//*******************
// Gestion de la  FAQ
//******************



/**
 * Add kb item to the public FAQ
 *
 *
 * @param $ID integer
 *
 *
 * @return nothing
 **/
function KbItemaddtofaq($ID)
{
	global $DB;
	$DB->query("UPDATE glpi_kbitems SET faq='1' WHERE ID='$ID'");
}

/**
 * Remove kb item from the public FAQ
 *
 *
 * @param $ID integer
 *
 *
 * @return nothing
 **/
function KbItemremovefromfaq($ID)
{
	global $DB;
	$DB->query("UPDATE glpi_kbitems SET faq='0' WHERE ID='$ID'");
}



/**
 *
 * get FAQ Categories
 *
 *
 *
 *
 * @return $catNumbers
 **/
/* NOT_USED
function getFAQCategories()
{

	global $DB;

	$query = "SELECT DISTINCT glpi_dropdown_kbcategories.* FROM glpi_kbitems LEFT JOIN glpi_dropdown_kbcategories ON (glpi_kbitems.categoryID = glpi_dropdown_kbcategories.ID) WHERE (glpi_kbitems.faq = '1')";
	$toprocess=array();
	$catNumbers = array();

	if ($result=$DB->query($query)){
		if ($DB->numrows($result)>0){
			while ($row=$DB->fetch_array($result)){
				$catNumbers[]=$row["ID"];
			}
			$DB->data_seek($result,0);
			while ($row=$DB->fetch_array($result)){
				if($row["parentID"]&&!in_array($row["parentID"], $toprocess)){
					$toprocess[]=$row["parentID"];
				}
			}

		}
	}
	while (count($toprocess)){
		$query2="SELECT DISTINCT * FROM glpi_dropdown_kbcategories WHERE '0'='1' ";
		foreach ($toprocess as $key)
			$query2.=  " OR ID = '$key' ";

		$toprocess=array();

		if ($result=$DB->query($query2)){
			if ($DB->numrows($result)>0){
				while ($row=$DB->fetch_array($result)){
					if(!in_array($row["ID"], $catNumbers)){
						$catNumbers[]=$row["ID"];
						if($row["parentID"]&&!in_array($row["parentID"], $toprocess)){
							$toprocess[]=$row["parentID"];
						}
					}
				}
			}
		}
	}



	return($catNumbers);

}
*/

?>
