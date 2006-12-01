<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

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


// FUNCTIONS contact


/**
 * Print the HTML array for entreprises on contact
 *
 * Print the HTML array for entreprises on contact for contact $instID
 *
 *@param $instID array : Contact identifier.
 *
 *@return Nothing (display)
 *
 **/
function showEnterpriseContact($instID) {
	global $db,$cfg_glpi, $lang,$HTMLRel;

	if (!haveRight("contact_enterprise","r")) return false;
	$canedit=haveRight("contact_enterprise","w");

	$query = "SELECT glpi_contact_enterprise.ID as ID, glpi_enterprises.ID as entID, glpi_enterprises.name as name, glpi_enterprises.website as website, glpi_enterprises.fax as fax,glpi_enterprises.phonenumber as phone, glpi_enterprises.type as type";
	$query.= " FROM glpi_enterprises,glpi_contact_enterprise WHERE glpi_contact_enterprise.FK_contact = '$instID' AND glpi_contact_enterprise.FK_enterprise = glpi_enterprises.ID";
	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;

	echo "<form method='post' action=\"".$cfg_glpi["root_doc"]."/front/contact.form.php\">";
	echo "<br><br><div align='center'><table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='6'>".$lang["financial"][65].":</th></tr>";
	echo "<tr><th>".$lang["financial"][26]."</th>";
	echo "<th>".$lang["financial"][79]."</th>";
	echo "<th>".$lang["financial"][29]."</th>";
	echo "<th>".$lang["financial"][30]."</th>";
	echo "<th>".$lang["financial"][45]."</th>";
	echo "<th>&nbsp;</th></tr>";

	while ($data= $db->fetch_array($result)) {
		$ID=$data["ID"];
		$website=$data["website"];
		if (!empty($website)){
			$website=$data["website"];
			if (!ereg("https*://",$website)) $website="http://".$website;
			$website="<a target=_blank href='$website'>".$data["website"]."</a>";
		}
		echo "<tr class='tab_bg_1'>";
		echo "<td align='center'><a href='".$HTMLRel."/front/enterprise.form.php?ID=".$data["entID"]."'>".getDropdownName("glpi_enterprises",$data["entID"])."</a></td>";
		echo "<td align='center'>".getDropdownName("glpi_dropdown_enttype",$data["type"])."</td>";
		echo "<td align='center'  width='100'>".$data["phone"]."</td>";
		echo "<td align='center'  width='100'>".$data["fax"]."</td>";
		echo "<td align='center'>".$website."</td>";
		echo "<td align='center' class='tab_bg_2'>";
		if ($canedit) 
			echo "<a href='".$_SERVER['PHP_SELF']."?deleteenterprise=deleteenterprise&amp;ID=$ID&amp;cID=$instID'><b>".$lang["buttons"][6]."</b></a>";
		else echo "&nbsp;";
		echo "</td></tr>";
	}
	if ($canedit){
		echo "<tr class='tab_bg_1'><td>&nbsp;</td><td align='center'>";
		echo "<div class='software-instal'><input type='hidden' name='conID' value='$instID'>";
		dropdown("glpi_enterprises","entID");

		echo "&nbsp;&nbsp;<input type='submit' name='addenterprise' value=\"".$lang["buttons"][8]."\" class='submit'>";
		echo "</div>";
		echo "</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>";
		echo "</tr>";
	}

	echo "</table></div></form>"    ;

}

function generateContactVcard($ID){

	$contact = new Contact;
	$contact->getfromDB($ID);

	// build the Vcard

	$vcard = new vCard();



	$vcard->setName($contact->fields["name"], $contact->fields["firstname"], "", "");  

	$vcard->setPhoneNumber($contact->fields["phone"], "PREF;WORK;VOICE");
	$vcard->setPhoneNumber($contact->fields["phone2"], "HOME;VOICE");
	$vcard->setPhoneNumber($contact->fields["mobile"], "WORK;CELL");

	//if ($contact->birthday) $vcard->setBirthday($contact->birthday);

	$addr=$contact->GetAddress();
	if (is_array($addr))
		$vcard->setAddress($addr["name"], "", $addr["address"], $addr["town"], $addr["state"], $addr["postcode"], $addr["country"],"WORK;POSTAL"); 

	$vcard->setEmail($contact->fields["email"]);

	$vcard->setNote($contact->fields["comments"]);

	$vcard->setURL($contact->GetWebsite(), "WORK");



	// send the  VCard 

	$output = $vcard->getVCard();


	$filename =$vcard->getFileName();      // "xxx xxx.vcf"

	@Header("Content-Disposition: attachment; filename=\"$filename\"");
	@Header("Content-Length: ".strlen($output));
	@Header("Connection: close");
	@Header("content-type: text/x-vcard; charset=UTF-8");

	echo $output;

}


?>
