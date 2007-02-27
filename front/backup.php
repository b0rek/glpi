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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
$NEEDED_ITEMS=array("xml");
include ($phproot . "/inc/includes.php");

checkRight("backup","w");

// full path 
$path = $cfg_glpi["dump_dir"] ;



commonHeader($lang["title"][2],$_SERVER['PHP_SELF']);


$max_time=min(get_cfg_var("max_execution_time"),get_cfg_var("max_input_time"));
if ($max_time==0) {$defaulttimeout=60;$defaultrowlimit=5;}
else if ($max_time>5) {$defaulttimeout=$max_time-2;$defaultrowlimit=5;}
else {$defaulttimeout=max(1,$max_time-2);$defaultrowlimit=2;}



?>
<script language="JavaScript" type="text/javascript">
<!--
function dump(what3){
	if (confirm("<?php echo $lang["backup"][15];?> " + what3 +  "?")) {
		window.location = "backup.php?dump=" + what3;
	}
}
function restore(what) {
	if (confirm("<?php echo $lang["backup"][16];?> " + what +  "?")) {
		window.location = "backup.php?file=" + what +"&donotcheckversion=1";
	}
}

function erase(what2){
	if (confirm("<?php echo $lang["backup"][17];?> " + what2 +  "?")) {
		window.location = "backup.php?delfile=" + what2;
	}
}

function xmlnow(what4){
	if (confirm("<?php echo $lang["backup"][18] ;?> " + what4 +  "?")) {
		window.location = "backup.php?xmlnow=" + what4;
	}
}


//-->
</script>


<?php



// les deux options qui suivent devraient �re incluses dans le fichier de config plutot non ?
// 1 only with ZLib support, else change value to 0
$compression = 0;



if ($compression==1) $filetype = "sql.gz";
else $filetype = "sql";

// g��e un fichier backup.xml a partir de base dbhost connect�avec l'utilisateur dbuser et le mot de passe
//dbpassword sur le serveur dbdefault
function xmlbackup()
{
	global $cfg_glpi,$db;

	//on parcoure la DB et on liste tous les noms des tables dans $table
	//on incremente $query[] de "select * from $table"  pour chaque occurence de $table

	$result = $db->list_tables();
	$i = 0;
	while ($line = $db->fetch_array($result))
	{


		// on se  limite aux tables pr�ix�s _glpi
		if (ereg("glpi_",$line[0])){

			$table = $line[0];


			$query[$i] = "select * from ".$table.";";
			$i++;
		}
	}

	//le nom du fichier a generer...
	//Si fichier existe deja il sera remplac�par le nouveau

	$chemin = $cfg_glpi["dump_dir"]."/backup.xml";

	// Creation d'une nouvelle instance de la classe
	// et initialisation des variables
	$A=new XML();

	// Your query
	$A->SqlString=$query;

	//File path
	$A->FilePath = $chemin;


	// Type of layout : 1,2,3,4
	// For details about Type see file genxml.php
	if (empty($Type))
	{
		$A->Type=4;
	}
	else
	{
		$A->Type=$Type;
	}

	//appelle de la methode g��ant le fichier XML
	$A->DoXML();


	// Affichage, si erreur affiche erreur
	//sinon affiche un lien vers le fichier XML g���


	if ($A->IsError==1)
	{
		echo "ERR : ".$A->ErrorString;
	}

	//fin de fonction xmlbackup
}
////////////////////////// DUMP SQL FUNCTIONS
function init_time() 
{
	global $TPSDEB,$TPSCOUR;


	list ($usec,$sec)=explode(" ",microtime());
	$TPSDEB=$sec;
	$TPSCOUR=0;

}

function current_time() 
{
	global $TPSDEB,$TPSCOUR;
	list ($usec,$sec)=explode(" ",microtime());
	$TPSFIN=$sec;
	if (round($TPSFIN-$TPSDEB,1)>=$TPSCOUR+1) //une seconde de plus
	{
		$TPSCOUR=round($TPSFIN-$TPSDEB,1);
	}
}

function get_content($db, $table,$from,$limit)
{
	$content="";
	$result = $db->query("SELECT * FROM $table LIMIT $from,$limit");
	if($result)
		while($row = $db->fetch_row($result)) {
			if (get_magic_quotes_runtime()) $row=addslashes_deep($row);
			$insert = "INSERT INTO $table VALUES (";
			for($j=0; $j<$db->num_fields($result);$j++) {
				if(is_null($row[$j])) $insert .= "NULL,";
				else if($row[$j] != "") $insert .= "'".addslashes($row[$j])."',";
				else $insert .= "'',";
			}
			$insert = ereg_replace(",$","",$insert);
			$insert .= ");\n";
			$content .= $insert;
		}
	return $content;
}


function get_def($db, $table) {


	$def = "### Dump table $table\n\n";
	$def .= "DROP TABLE IF EXISTS `$table`;\n";
	$query = "SHOW CREATE TABLE $table";
	$result=$db->query($query);
	$row=$db->fetch_array($result);

	// DELETE charset definition : UNEEDED WHEN UTF8 CONVERSION OF THE DATABASE
	$def.=preg_replace("/DEFAULT CHARSET=\w+/i","",$row[1]); 
	$def.=";";
	return $def."\n\n";
}


function restoreMySqlDump($db,$dumpFile , $duree)
{
	// $dumpFile, fichier source
	// $database, nom de la base de donn�s cible
	// $mysqlUser, login pouyr la connexion au serveur MySql
	// $mysqlPassword, mot de passe
	// $histMySql, nom de la machine serveur MySQl
	// $duree=timeout pour changement de page (-1 = aucun)

	// Desactivation pour empecher les addslashes au niveau de la creation des tables
	// En plus, au niveau du dump on consid�e qu'on est bon
	//set_magic_quotes_runtime(0);

	global $db,$TPSCOUR,$offset,$cpt;

	if ($db->error)
	{
		echo "Connexion impossible �$hostMySql pour $mysqlUser";
		return FALSE;
	}

	if(!file_exists($dumpFile))
	{
		echo "$dumpFile non trouv�br>";
		return FALSE;
	}
	$fileHandle = fopen($dumpFile, "rb");

	if(!$fileHandle)
	{
		echo "Ouverture de $dumpFile non trouv�br>";
		return FALSE;
	}

	if ($offset!=0)
	{
		if (fseek($fileHandle,$offset,SEEK_SET)!=0) //erreur
		{
			echo "Impossible de trouver l'octet ".number_format($offset,0,""," ")."<br>";
			return FALSE;
		}
		glpi_flush();
	}

	$formattedQuery = "";

	while(!feof($fileHandle))
	{
		current_time();
		if ($duree>0 and $TPSCOUR>=$duree) //on atteint la fin du temps imparti
			return TRUE;

		//    echo $TPSCOUR."<br>";

		// on indique le  length pour la fonction fgets pour compatibilit�avec les versions <=PHP 4.2
		$buffer=fgets($fileHandle,102400);

		if(substr($buffer, 0, 1) != "#")
		{
			$formattedQuery .= $buffer;
			if (get_magic_quotes_runtime()) $formattedQuery=stripslashes($formattedQuery);
			if (substr(rtrim($formattedQuery),-1)==";"){
				// Do not use the $db->query 
				if ($db->query($formattedQuery)) //r�ssie sinon continue �conca&t�er
				{

					$offset=ftell($fileHandle);
					$formattedQuery = "";
					$cpt++;
				}
			}
		}

	}

	if ($db->error)
		echo "<hr>ERREUR �partir de [$formattedQuery]<br>".$db->error()."<hr>";

	fclose($fileHandle);
	$offset=-1;
	return TRUE;
}

function backupMySql($db,$dumpFile, $duree,$rowlimit)
{
	// $dumpFile, fichier source
	// $database, nom de la base de donn�s cible
	// $mysqlUser, login pouyr la connexion au serveur MySql
	// $mysqlPassword, mot de passe
	// $histMySql, nom de la machine serveur MySQl
	// $duree=timeout pour changement de page (-1 = aucun)

	global $TPSCOUR,$offsettable,$offsetrow,$cpt;

	if ($db->error)
	{
		echo "Connexion impossible �$hostMySql pour $mysqlUser";
		return FALSE;
	}

	$fileHandle = fopen($dumpFile, "a");

	if(!$fileHandle)
	{
		echo "Ouverture de $dumpFile impossible<br>";
		return FALSE;
	}

	if ($offsettable==0&&$offsetrow==-1){
		$time_file=date("Y-m-d-H-i");
		$cur_time=date("Y-m-d H:i");
		$todump="#GLPI Dump database on $cur_time\n";
		fwrite ($fileHandle,$todump);

	}

	$result=$db->list_tables();
	$numtab=0;
	while ($t=$db->fetch_array($result)){

		// on se  limite aux tables pr�ix�s _glpi
		if (ereg("glpi_",$t[0])){
			$tables[$numtab]=$t[0];
			$numtab++;
		}
	}


	for (;$offsettable<$numtab;$offsettable++){

		// Dump de la structure table
		if ($offsetrow==-1){
			$todump="\n".get_def($db,$tables[$offsettable]);
			fwrite ($fileHandle,$todump);
			$offsetrow++;
			$cpt++;
		}
		current_time();
		if ($duree>0 and $TPSCOUR>=$duree) //on atteint la fin du temps imparti
			return TRUE;

		$fin=0;
		while (!$fin){
			$todump=get_content($db,$tables[$offsettable],$offsetrow,$rowlimit);
			$rowtodump=substr_count($todump, "INSERT INTO");
			if ($rowtodump>0){
				fwrite ($fileHandle,$todump);
				$cpt+=$rowtodump;
				$offsetrow+=$rowlimit;
				if ($rowtodump<$rowlimit) $fin=1;
				current_time();
				if ($duree>0 and $TPSCOUR>=$duree) //on atteint la fin du temps imparti
					return TRUE;
			} else {
				$fin=1;
				$offsetrow=-1;
			}
		}
		if ($fin) $offsetrow=-1;
		current_time();
		if ($duree>0 and $TPSCOUR>=$duree) //on atteint la fin du temps imparti
			return TRUE;
	}
	if ($db->error())
		echo "<hr>ERREUR �partir de [$formattedQuery]<br>".$db->error()."<hr>";
	$offsettable=-1;
	fclose($fileHandle);
	return TRUE;
}


// #################" DUMP sql#################################

if (isset($_GET["dump"]) && $_GET["dump"] != ""){

	$time_file=date("Y-m-d-H-i");
	$cur_time=date("Y-m-d H:i");
	$filename=$path."$time_file.$filetype";


	if (!isset($_GET["duree"])&&is_file($filename)){
		echo "<div align='center'>".$lang["backup"][21]."</div>";
	} else {
		init_time(); //initialise le temps
		//d�ut de fichier
		if (!isset($_GET["offsettable"])) $offsettable=0; 
		else $offsettable=$_GET["offsettable"]; 
		//d�ut de fichier
		if (!isset($_GET["offsetrow"])) $offsetrow=-1; 
		else $offsetrow=$_GET["offsetrow"];
		//timeout de 5 secondes par d�aut, -1 pour utiliser sans timeout
		if (!isset($_GET["duree"])) $duree=$defaulttimeout; 
		else $duree=$_GET["duree"];
		//Limite de lignes �dumper �chaque fois
		if (!isset($_GET["rowlimit"])) $rowlimit=$defaultrowlimit; 
		else  $rowlimit=$_GET["rowlimit"];

		//si le nom du fichier n'est pas en param�re le mettre ici
		if (!isset($_GET["fichier"])) {
			$fichier=$filename;
		} else $fichier=$_GET["fichier"];

		$tab=$db->list_tables();
		$tot=$db->numrows($tab);
		if(isset($offsettable)){
			if ($offsettable>=0)
				$percent=min(100,round(100*$offsettable/$tot,0));
			else $percent=100;
		}
		else $percent=0;

		if ($percent >= 0) {
			displayProgressBar(400,$percent);
		}

		if ($offsettable>=0){
			if (backupMySql($db,$fichier,$duree,$rowlimit))
			{
				echo "<br>Redirection automatique sinon cliquez <a href=\"backup.php?dump=1&duree=$duree&rowlimit=$rowlimit&offsetrow=$offsetrow&offsettable=$offsettable&cpt=$cpt&fichier=$fichier\">ici</a>";
				echo "<script>window.location=\"backup.php?dump=1&duree=$duree&rowlimit=$rowlimit&offsetrow=$offsetrow&offsettable=$offsettable&cpt=$cpt&fichier=$fichier\";</script>";
				glpi_flush();    
				exit;

			}
		}
		else  { //echo "<div align='center'><p>Termin� Nombre de requ�es totales trait�s : $cpt</p></div>";

		}

	}	
}

// ##############################   fin dump sql########################""""




// ################################## dump XML #############################

if (isset($_GET["xmlnow"]) && $_GET["xmlnow"] !=""){

	xmlbackup();


}
// ################################## fin dump XML #############################



if (isset($_GET["file"]) && $_GET["file"] != ""&&is_file($path.$_GET["file"])) {

	init_time(); //initialise le temps
	//d�ut de fichier
	if (!isset($_GET["offset"])) $offset=0;
	else  $offset=$_GET["offset"];
	//timeout de 5 secondes par d�aut, -1 pour utiliser sans timeout
	if (!isset($_GET["duree"])) $duree=$defaulttimeout; 
	else $duree=$_GET["duree"];

	$fsize=filesize($path.$_GET["file"]);
	if(isset($offset)){
		if ($offset==-1)
			$percent=100;
		else $percent=min(100,round(100*$offset/$fsize,0));
	}
	else $percent=0;

	if ($percent >= 0) {

		displayProgressBar(400,$percent);

	}

	if ($offset!=-1){
		if (restoreMySqlDump($db,$path.$_GET["file"],$duree))
		{
			echo "<br>Redirection automatique sinon cliquez <a href=\"backup.php?file=".$_GET["file"]."&amp;duree=$duree&amp;offset=$offset&amp;cpt=$cpt&amp;donotcheckversion=1\">ici</a>";
			echo "<script language=\"javascript\" type=\"text/javascript\">window.location=\"backup.php?file=".$_GET["file"]."&duree=$duree&offset=$offset&cpt=$cpt&donotcheckversion=1\";</script>";
			glpi_flush();
			exit;
		}
	} else   { //echo "<div align='center'><p>Termin� Nombre de requ�es totales trait�s : $cpt<p></div>";
		optimize_tables();
	}


}

if (isset($_GET["delfile"]) && $_GET["delfile"] != ""){

	$filename=$_GET["delfile"];
	if (is_file($path.$_GET["delfile"])){
		unlink($path.$_GET["delfile"]);
		echo "<div align ='center'>".$filename." ".$lang["backup"][9]."</div>";
	}

}

// Title backup
echo " <div align='center'> <table border='0'><tr><td><img src=\"". $HTMLRel."pics/sauvegardes.png\" alt='".$lang["backup"][9]."'></td> <td><a href=\"javascript:dump('".$lang["backup"][19]."')\"  class='icon_consol'><b>". $lang["backup"][0]."</b></a></td><td><a href=\"javascript:xmlnow('".$lang["backup"][19]."')\" class='icon_consol'><b>". $lang["backup"][1]."</b></a></td></tr></table>";


?>




<br>
<table class='tab_cadre'  cellpadding="5">
<tr align="center"> 
<th><u><i><?php echo $lang["backup"][10]; ?></i></u></th>
<th><u><i><?php echo $lang["backup"][11]; ?></i></u></th>
<th><u><i><?php echo $lang["common"][27]; ?></i></u></th>
<th colspan='3'>&nbsp;</th>
</tr>
<?php
$dir=opendir($path); 
$files=array();
while ($file = readdir ($dir)) { 
	if ($file != "." && $file != ".." && eregi("\.sql",$file)) { 
		$files[$file]=filemtime($path.$file);
	}
}
arsort($files);
if (count($files)){
	foreach ($files as $file => $date){
		$taille_fic = filesize($path.$file)/1024;
		$taille_fic = (int)$taille_fic;
		echo "<tr class='tab_bg_2'><td>$file&nbsp;</td>
			<td align=\"right\">&nbsp;" . $taille_fic . " kB&nbsp;</td>
			<td>&nbsp;" . convDateTime(date("Y-m-d H:i",$date)) . "</td>
			<td>&nbsp;<a href=\"javascript:erase('$file')\">".$lang["backup"][20]."</a>&nbsp;</td>

			<td>&nbsp;<a href=\"javascript:restore('$file')\">".$lang["backup"][14]."</a>&nbsp;</td>
			<td>&nbsp;<a href=\"document.send.php?file=_dumps/$file\">".$lang["backup"][13]."</a></td></tr>";
	}
}
closedir($dir);
$dir=opendir($path);
unset($files);
$files=array();
while ($file = readdir ($dir)) {
	if ($file != "." && $file != ".." && eregi("\.xml",$file)) {
		$files[$file]=filemtime($path.$file);
	}
}
arsort($files);
if (count($files)){
	foreach ($files as $file => $date){
		$taille_fic = filesize($path.$file)/1024;
		$taille_fic = (int)$taille_fic;
		echo "
			<tr class='tab_bg_1'><td colspan='6' ><hr noshade></td></tr>
			<tr class='tab_bg_2'><td>$file&nbsp;</td>
			<td align=\"right\">&nbsp;" . $taille_fic . " kB&nbsp;</td>
			<td>&nbsp;" . convDateTime(date("Y-m-d H:i",$date)) . "</td>
			<td>&nbsp;<a href=\"javascript:erase('$file')\">".$lang["backup"][20]."</a>&nbsp;</td>
			<td>&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;</td>

			<td>&nbsp;<a  href=\"document.send.php?file=_dumps/$file\">".$lang["backup"][13]."</a></td></tr>";
	}
}
closedir($dir);
?>
</table>
</div>
<?php

commonFooter();
?>




