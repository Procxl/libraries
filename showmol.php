<?php 
// showmol.php      Norbert Haider, University of Vienna, 2006-2014
// part of MolDB6   last change: 2014-07-10

/**
 * @file showmol.php
 * @author Norbert Haider
 * 
 * This script sends a molecule (in MDL molfile format) or a reaction
 * (in MDL rxnfile format) to the browser in the appropriate chemical
 * MIME type for display by a browser plugin or an external helper
 * application. With the mode parameter set to "txt", contents will be
 * sent as plain text.
 */

$myname = $_SERVER['PHP_SELF'];
require_once("functions.php");

include("moldb6conf.php");   // Contains $uid and $pw of a proxy user 
                             // with read-only access to the moldb database;
                             // contains $bitmapURLdir (location of .png files);
                             // the conf file must have valid PHP start and end tags!
include("moldb6uiconf.php"); // Contains additional settings that are relevant only
                             // to the web frontend (the PHP scripts), but not to
                             // the command-line backend (the Perl scripts)

if (config_quickcheck() > 0) { die(); }
set_charset($charset);

$user     = $ro_user;         # from configuration file
$password = $ro_password;

if ($user == "") {
  die("no username specified!\n");
}

$mode = "mol";   // can be "mol" or "txt"

@$mol_id  = $_REQUEST['mol'];
@$rxn_id  = $_REQUEST['rxn'];
@$db_id   = $_REQUEST['db'];
@$mode    = $_REQUEST['mode'];

$link = mysql_pconnect($hostname,"$user", "$password")
  or die("Could not connect to database server!");
mysql_select_db($database)
  or die("Could not select database!");    
mysql_query("SET NAMES $mysql_charset");

if (exist_db($default_db) == FALSE) {
  $default_db = get_lowestdbid(); 
}

if ((!isset($db_id)) || (!is_numeric($db_id)) || ($db_id < 1)) {
  $db_id = $default_db;
}

if (!isset($mode)) {
  $mode = "mol";
}

if ($mode != "txt") {
  $mode = "mol";
}

$db_id = check_db($db_id);
if ($db_id < 0) {
  $db_id = $default_db;
  $db_id = check_db($db_id);
  if ($db_id < 0) {
    $db_id = get_lowestdbid();
  }
}

if ($db_id == 0) { die(); }

$qstr01 = "SELECT * FROM $metatable WHERE (db_id = $db_id)";

$result01 = mysql_query($qstr01)
  or die("Query failed (#1)!");    
while($line01   = mysql_fetch_array($result01)) {
  $db_id        = $line01['db_id'];
  $dbtype       = $line01['type'];
  $dbname       = $line01['name'];
  $usemem       = $line01['usemem'];
  $digits       = $line01['digits'];
  $subdirdigits = $line01['subdirdigits'];
}
mysql_free_result($result01);

if (!isset($digits) || (is_numeric($digits) == false)) { $digits = 8; }
if (!isset($subdirdigits) || (is_numeric($subdirdigits) == false)) { $subdirdigits = 0; }
if ($subdirdigits < 0) { $subdirdigits = 0; }
if ($subdirdigits > ($digits - 1)) { $subdirdigits = $digits - 1; }

$dbprefix      = "db" . $db_id . "_";
$molstructable = $prefix . $dbprefix . $molstrucsuffix;
$moldatatable  = $prefix . $dbprefix . $moldatasuffix;
$molstattable  = $prefix . $dbprefix . $molstatsuffix;
$molcfptable   = $prefix . $dbprefix . $molcfpsuffix;
$molfgbtable   = $prefix . $dbprefix . $molfgbsuffix;
$pic2dtable    = $prefix . $dbprefix . $pic2dsuffix;
$rxnstructable = $prefix . $dbprefix . $rxnstrucsuffix;
$rxndatatable  = $prefix . $dbprefix . $rxndatasuffix;

if ($usemem == 'T') {
  $molstattable  = $molstattable . $memsuffix;
  $molcfptable   = $molcfptable  . $memsuffix;
}

$safemol_id = escapeshellcmd($mol_id);
$saferxn_id = escapeshellcmd($rxn_id);

if (($safemol_id != '') && ($dbtype == 1)) { 
  $result2 = mysql_query("SELECT struc FROM $molstructable WHERE mol_id = $safemol_id") or die("Query failed!");    
  while ($line2 = mysql_fetch_array($result2, MYSQL_ASSOC)) {
    $molstruc = $line2["struc"];
  }
  mysql_free_result($result2);
  if ($mode == "mol") {
    header("Content-Type: chemical/x-mdl-molfile");
    header("Content-Disposition: filename=${safemol_id}.mol");
  } else {
    header("Content-Type: text/plain");
    header("Content-Disposition: filename=${safemol_id}.txt");
  }
  print "$molstruc\n";
}

if (($saferxn_id != '') && ($dbtype == 2)) { 
  $result2 = mysql_query("SELECT struc FROM $rxnstructable WHERE rxn_id = $saferxn_id") or die("Query failed!");    
  while ($line2 = mysql_fetch_array($result2, MYSQL_ASSOC)) {
    $molstruc = $line2["struc"];
  }
  mysql_free_result($result2);
  if ($mode == "mol") {
    header("Content-Type: chemical/x-mdl-rxnfile");
    header("Content-Disposition: filename=${saferxn_id}.rxn");
  } else {
    header("Content-Type: text/plain");
    header("Content-Disposition: filename=${saferxn_id}.txt");
  }
  print "$molstruc\n";
}

?>
