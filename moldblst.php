<?php 
// moldblst.php     Norbert Haider, University of Vienna, 2008-2014
// part of MolDB6   last change: 2014-08-18

/**
 * @file moldblst.php
 * @author Norbert Haider
 * 
 * This script is used for browsing through the contents of any structure
 * or reaction data collection. Output is paginated, there are navigation
 * elements for moving forward/backward and for jumping between the
 * (previously selected) data collections.
 */

$myname = $_SERVER['PHP_SELF'];
require_once("functions.php");

// some default settings (will be overridden by the config files)
$enable_svg     = "y";   # first choice (overridden by settings in moldb6conf.php)
$enable_bitmaps = "y";   # second choice (overridden by settings in moldb6conf.php)
$enable_jme     = "y";   # structure editor; fallback for 2D display
$enable_jsme    = "y";   # structure editor; fallback for 2D display
$sitename       = "Sristi Biosciences";
$cssfilename    = "moldb.css";

/**
 * Increment
 * - the $increment variable defines the number of hits per page
 */
$increment = 25;

/**
 * Debug level
 * - 0:            remain silent, higher values: be more verbose
 * - odd numbers:  output as HTML comments, 
 * - even numbers: output as clear-text messages
 */
$debug = 1;

// read UID and password from an include file (which should
// be stored _outside_ the web server's document_root directory
// somewhere in the include path of PHP!!!!!!!!!!!!!)
include("moldb6conf.php");   // Contains $uid and $pw of a proxy user 
                             // with read-only access to the moldb database;
                             // contains $bitmapURLdir (location of .png files);
                             // the conf file must have valid PHP start and end tags!
include("moldb6uiconf.php"); // Contains additional settings that are relevant only
                             // to the web frontend (the PHP scripts), but not to
                             // the command-line backend (the Perl scripts)


// override setting in moldb6 configuration files here (for testing):
//$enable_svg     = "n";   # first choice (overridden by settings in moldb6conf.php)
//$enable_bitmaps = "n";   # second choice (overridden by settings in moldb6conf.php)
//$enable_jme     = "y";   # structure editor; fallback for 2D display
//$enable_jsme    = "y";   # structure editor; fallback for 2D display


// choice of structure editors
@$curr_settings = $_COOKIE["MolDB6"];
$curr_a = explode(",",$curr_settings);
foreach ($curr_a as $curr_line) {
  if (strpos($curr_line,"editor") !== FALSE) {
    $ed_a = explode("=",$curr_line);
    $editor = $ed_a[1];
  }
}
if (!isset($editor) || ($editor == "")) {
  $editor = $default_editor;
}

if ($editor == "jme") {
  $edtag  = "applet";    // "applet" (for JME) or "div" (for JSME)
} else {
  $edtag  = "div";    // "applet" (for JME) or "div" (for JSME)
}



if (config_quickcheck() > 0) { die(); }
set_charset($charset);

$user     = $ro_user;         # from configuration file
$password = $ro_password;

if ($user == "") {
  die("no username specified!\n");
}
if (!isset($sitename) || ($sitename == "")) {
  $sitename = "Sristi Biosciences";
}

@$dbstr   = $_REQUEST['db'];
@$dbl     = explode(",",$dbstr);
@$idx     = $_REQUEST['idx'];

$link = mysql_pconnect($hostname,"$ro_user", "$ro_password")
  or die("Could not connect to database server!");
mysql_select_db($database)
  or die("Could not select database!");    
mysql_query("SET NAMES $mysql_charset");

if (!isset($dbl)) {
  $dbl = array();
  $dbl[0] = $db;
}

$dba    = array();
$dbstr  = "";
$dbstr2 = "";

$ndbsel = 0;
foreach ($dbl as $id) {
  $db_id = check_db($id);
  if (($db_id > 0) && (($ndbsel < 1) || ($multiselect == "y"))) {
    $ndbsel++;
    $dba[($ndbsel - 1)] = $dbl[($ndbsel - 1)];
    if (strlen($dbstr)>0) { $dbstr .= ","; $dbstr2 .= " "; }
    $dbstr .= "$db_id"; $dbstr2 .= "$db_id";
  }
}

if (exist_db($default_db) == FALSE) {
  $default_db = get_lowestdbid(); 
}

if ($ndbsel < 1) {
  $ndbl = 1;
  $dba[0] = $default_db;
  $ndbsel = 1;
  $dbstr = "$default_db";
  $db_id = $default_db;
}

$dbindex = 1;
if ((isset($idx)) && (is_numeric($idx))) {
  if (($idx > 0) && ($idx <= $ndbsel)) {
    $dbindex = $idx;
  }
}

@$offset = intval($_REQUEST['offset']);
if (!isset($offset)) {
  $offset = 0;
} else {
  if (!is_numeric($offset)) {
    $offset = 0;
  } else {
    if (!is_integer($offset)) {
      $offset = 0;
    }
  }
}

$codebase = "";
if (isset($java_codebase) && ($java_codebase != "")) { 
  $codebase = "codebase=\"" . $java_codebase . "\"" ; 
}

$svg_mode = get_svgmode();
if (!isset($MOL2SVG)) { 
  $svg_mode = 0; 
} else { 
  if ($MOL2SVG == "") {
    $svg_mode = 0; 
  }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=<?php echo "$html_charset"; ?>">
<meta name="author" content="Norbert Haider, University of Vienna">
<script src="js/jquery-1.10.2.js"></script>
<script src="js/jquery-ui-1.10.4.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<link href="css/bootstrap.css" rel="stylesheet">
<link href="css/font-awesome.min.css" rel="stylesheet">
<link href="css/jquery-ui-1.10.4.min.css" rel="stylesheet">

<title><?php echo "$sitename"; ?>: text search</title>
<?php 
insert_style($cssfilename); 
if ($edtag == "div") {
  echo "<script type=\"text/javascript\" language=\"javascript\" src=\"${jsme_path}/jsme.nocache.js\"></script>\n";
}
?>
</head>
<body>

<div class="container">
  <div class="row">
    <div class="col-md-12 col-xm-12">
      <?php show_header($myname,$dbstr); ?>
    </div>
  </div>
</div> 



<?php
@$action = $_POST['action'];
$db_id  = $dba[($dbindex - 1)];

$ndb = get_numdb_readable();
if ($ndb == 0) { 
  show_header($myname,"");
  echo "<h3>no data collection available</h3><br>\n</body>\n</html>\n"; 
  die();
}

if ($enablereactions == "y") { $onlysd = ""; } else { $onlysd = " AND (type = 1) "; }
$qstr01 = "SELECT * FROM $metatable WHERE (db_id = $db_id) $onlysd";

$result01 = mysql_query($qstr01)
  or die("Query failed (#1)!");    
while($line01 = mysql_fetch_array($result01)) {
  $db_id  = $line01['db_id'];
  $dbtype = $line01['type'];
  $dbname = $line01['name'];
  $digits = $line01['digits'];
  $subdirdigits = $line01['subdirdigits'];
  $usemem = $line01['usemem'];  
}
mysql_free_result($result01);

if (!isset($digits) || (is_numeric($digits) == false)) { $digits = 8; }
if (!isset($subdirdigits) || (is_numeric($subdirdigits) == false)) { $subdirdigits = 0; }
if ($subdirdigits < 0) { $subdirdigits = 0; }
if ($subdirdigits > ($digits - 1)) { $subdirdigits = $digits - 1; }

$dbprefix       = "db" . $db_id . "_";
$molstructable = $prefix . $dbprefix . $molstrucsuffix;
$moldatatable  = $prefix . $dbprefix . $moldatasuffix;
$molstattable  = $prefix . $dbprefix . $molstatsuffix;
$molcfptable   = $prefix . $dbprefix . $molcfpsuffix;
$pic2dtable    = $prefix . $dbprefix . $pic2dsuffix;
$rxnstructable = $prefix . $dbprefix . $rxnstrucsuffix;
$rxndatatable  = $prefix . $dbprefix . $rxndatasuffix;
if ($usemem == 'T') {
  $molstattable  = $molstattable . $memsuffix;
  $molcfptable   = $molcfptable  . $memsuffix;
}

 //show_header($myname,$dbstr);

echo "<h2><span style=\"color:Purple\">${dbname}</span></h2>\n";
echo "<hr />\n";

if ($dbtype == 1) {
  $idname = "mol_id";
  $structable = $molstructable;
  $datatable = $moldatatable;
} elseif ($dbtype == 2) {
  $idname = "rxn_id";
  $structable = $rxnstructable;
  $datatable = $rxndatatable;
}

$qstr = "SELECT COUNT($idname) AS itemcount FROM $structable";
$result = mysql_query($qstr)
  or die("Query failed (#1a)!");    
$line = mysql_fetch_row($result);
mysql_free_result($result);
$itemcount = $line[0];
//shownavigation1($offset,$increment,$itemcount);
if ($itemcount > 0) { 
  shownavigation2($offset,$increment,$itemcount);
  echo "<hr />\n<table width=\"100%\">\n";
  $qstr1 = "SELECT $idname FROM $structable LIMIT $offset,$increment";
  $result1 = mysql_query($qstr1)
    or die("Query failed ($idname)!");    
  while ($line1 = mysql_fetch_array($result1,MYSQL_ASSOC)) {
    $item_id = $line1[$idname];
    if ($dbtype == 1) { showHit($item_id,""); }
    if ($dbtype == 2) { showHitRxn($item_id,""); }
  }
  mysql_free_result($result1);
  echo "</table>\n<hr />\n";
 // shownavigation1($offset,$increment,$itemcount);
  shownavigation2($offset,$increment,$itemcount);
}   // if $itemcount > 0....


echo "\n<hr />\n";

echo "<medium>entries in data collection: $itemcount</medium><br>\n";
echo "</body>\n";
echo "</html>\n";
/*
function shownavigation1($offset,$increment,$itemcount) {
  global $myname;
  global $db_id;
  global $dbstr;
  global $dbindex;
  global $dba;
  global $ndbsel;
  echo "<div align=\"left\">";
  $dburlidx = 0;
  foreach ($dba as $id) {
    $dburlidx++;
    if ($id == $db_id) {
      echo " <b>$id</b>";
    } else {
      echo " <a href=\"$myname?db=$dbstr&idx=$dburlidx&offset=0 \">$id</a>";
    }
  }
  echo "</div>\n";
}  

*/
function shownavigation2($offset,$increment,$itemcount) {
  global $myname;
  global $db_id;
  global $dbstr;
  global $dbindex;
  global $dba;
  global $ndbsel;
 /* echo "<div align=\"center\">\n";
  if ($offset <= 0) {
    echo "&nbsp;&nbsp;&lt;&lt;&nbsp;&nbsp;";
    echo "&nbsp;&nbsp;&lt;&nbsp;&nbsp;";
  } else {
    echo "&nbsp;&nbsp;<a class=\"nav\" title=\"first page\" href=\"$myname?db=$dbstr&idx=$dbindex&offset=0\">&lt;&lt;</a>&nbsp;&nbsp;";
    $newoffset = $offset - $increment;
    if ($newoffset < 0) {$newoffset = 0; }
    echo "&nbsp;&nbsp;<a class=\"nav\" title=\"previous page\" href=\"$myname?db=$dbstr&idx=$dbindex&offset=$newoffset\">&lt;</a>&nbsp;&nbsp;";
  }*/
  
  echo "<div align=\"center\">\n";
  /*echo "<ul class=\"pagination\">\n";

  echo "<li><a href=\"$myname?db=$dbstr&idx=$dbindex&offset=0\">&laquo;</a></li>\n";
  echo "<li><a href=\"$myname?db=$dbstr&idx=$dbindex&offset=25\">1 <span class=\"sr-only\">(current)</span></a></li>\n";
  echo "<li><a href=\"$myname?db=$dbstr&idx=$dbindex&offset=50\">2 <span class=\"sr-only\">(current)</span></a></li>\n";
  echo "<li><a href=\"$myname?db=$dbstr&idx=$dbindex&offset=75\">3 <span class=\"sr-only\">(current)</span></a></li>\n";
  $newoffset = $offset + $increment;
    if ($newoffset > $itemcount) {$newoffset = $itemcount; }
  echo "<li><a href=\"$myname?db=$dbstr&idx=$dbindex&offset=$newoffset\">&raquo;</a></li>\n";*/


  echo "<ul class=\"pager\">\n";
  echo "<li><a href=\"$myname?db=$dbstr&idx=$dbindex&offset=0\">Previous</a></li>\n";
  $newoffset = $offset + $increment;
    if ($newoffset > $itemcount) {$newoffset = $itemcount; }
  echo "<li><a href=\"$myname?db=$dbstr&idx=$dbindex&offset=$newoffset\">Next</a></li>\n";

  
 /* echo "<li><a href=\"$myname?db=$dbstr&idx=$dbindex&offset=0\">&laquo;</a></li>\n";
  echo "<li><a href=\"$myname?db=$dbstr&idx=$dbindex&offset=25\">1</a></li>\n";
  echo "<li><a href=\"$myname?db=$dbstr&idx=$dbindex&offset=50\">2</a></li>\n";
  echo "<li><a href=\"$myname?db=$dbstr&idx=$dbindex&offset=75\">3</a></li>\n";
  echo "<li><a href=\"$myname?db=$dbstr&idx=$dbindex&offset=100\">4</a></li>\n";
  echo "<li><a href=\"$myname?db=$dbstr&idx=$dbindex&offset=125\">5</a></li>\n";
  $newoffset = $offset + $increment;
    if ($newoffset > $itemcount) {$newoffset = $itemcount; }
  echo "<li><a href=\"$myname?db=$dbstr&idx=$dbindex&offset=$newoffset\">&raquo;</a></li>\n";*/
  echo "</ul>";

/* if ($offset <= 0) {
   echo "&nbsp;&nbsp;first page &nbsp;&nbsp;";
    echo "&nbsp;&nbsp;last page &nbsp;&nbsp;";
    
  }else {
    echo "&nbsp;&nbsp;<a href=\"$myname?db=$dbstr&idx=$dbindex&offset=0\" title=\"first page\">first page</a>&nbsp;&nbsp;";
    $newoffset = $offset - $increment;
    if ($newoffset < 0) {$newoffset = 0; }
    echo "&nbsp;&nbsp;<a href=\"$myname?db=$dbstr&idx=$dbindex&offset=$newoffset\" title=\"previous page\">previous page</a>&nbsp;&nbsp;";
  }



  if ($offset + $increment >= $itemcount) {
    echo "&nbsp;&nbsp; next page &nbsp;&nbsp;";
    echo "&nbsp;&nbsp; last page &nbsp;&nbsp;";
  } else {
    $newoffset = $offset + $increment;
    if ($newoffset > $itemcount) {$newoffset = $itemcount; }
    echo "&nbsp;&nbsp;<a href=\"$myname?db=$dbstr&idx=$dbindex&offset=$newoffset\" title=\"next page\">next page</a>&nbsp;&nbsp;";
    $newoffset = $itemcount - $increment;
    echo "&nbsp;&nbsp;<a href=\"$myname?db=$dbstr&idx=$dbindex&offset=$newoffset\" title=\"last page\">last page</a>&nbsp;&nbsp;";
  }*/
  echo "</div>\n";
}  

if ($enable_prefs == "y") {
  mkprefscript();
}




?>
