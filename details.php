<?php 
// details.php     Norbert Haider, University of Vienna, 2006-2014
// part of MolDB6  last change: 2014-08-18

/**
 * @file details.php
 * @author Norbert Haider
 * 
 * This script is invoced when a user clicks on the ID hyperlink of an item
 * (molecule or reaction) on a hit list. It displays the structure either
 * in SVG format or as a bitmap image (PNG) or with the JME applet (in depict 
 * mode) or with JSME (in depict mode). In addition, all data fields are 
 * displayed with appropriate formatting.
 * Optionally, the Jmol applet can be used to display 3D structures.
 */

$myname = $_SERVER['PHP_SELF'];
require_once("functions.php");

// some default settings ("y" or "n"; will be overridden by the config files)
$enable_svg     = "y";   # first choice
$enable_bitmaps = "y";   # second choice
$enable_jme     = "y";   # structure editor; fallback for 2D display
$enable_jsme    = "y";   # structure editor; fallback for 2D display

/**
 * use 3D display (for 3D molfiles)
 * - "n":          use plain 2D display (SVG, bitmaps, JME/JSME)
 * - "y":          use the Jmol Java applte for 3D display
 */
$use3d    = "n";

/**
 * JMol path (required only if $use3d is set to "y")
 * This must be the _relative_ path to the directory containing
 * JmolApplet.jar and Jmol.js
 */
$jmolpath = "../../classes";
                              
/**
 * Debug level
 * - 0:            remain silent, higher values: be more verbose
 * - odd numbers:  output as HTML comments, 
 * - even numbers: output as clear-text messages
 */
$debug = 1;

// some defaults (will be overridden by config files
$sitename      = "Sristi Biosciences";
$cssfilename   = "moldb.css";

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

// determine the user's choice of structure editors
@$curr_settings = $_COOKIE["MolDB6"];
$curr_a = explode(",",$curr_settings);
foreach ($curr_a as $curr_line) {
  if (strpos($curr_line,"editor") !== FALSE) {
    $ed_a = explode("=",$curr_line);
    $editor = $ed_a[1];
  }
}

$editors = array("text","ketcher","jme","jsme","flame");

if ((!isset($editor)) || ($editor == "") || (!in_array($editor,$editors))) {
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
if ($enable_svg !== "y") { $svg_mode = 0; }
$sendjsmeheader = 0;
if ($svg_mode == 0) {
  if ($editor !== "jme") { $sendjsmeheader = 1; }
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
<title><?php echo "$sitename"; ?>: compound details</title>
<?php 
insert_style($cssfilename); 
if (($sendjsmeheader == 1) && ($use3d != "y")) {
  echo "<script type=\"text/javascript\" language=\"javascript\" src=\"${jsme_path}/jsme.nocache.js\"></script>\n";
}
if ($use3d == "y") { echo "<script src=\"${jmolpath}/Jmol.js\"></script>"; } 
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
@$mol_id  = $_REQUEST['mol'];
@$rxn_id  = $_REQUEST['rxn'];
@$db_id   = $_REQUEST['db'];

$link = mysql_pconnect($hostname,"$ro_user", "$ro_password")
  or die("Could not connect to database server!");
mysql_select_db($database)
  or die("Could not select database!");    
mysql_query("SET NAMES $mysql_charset");

if (exist_db($default_db) == FALSE) {
  $default_db = get_lowestdbid(); 
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
  $access       = $line01['access'];
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

$dbprefix      = $prefix . "db" . $db_id . "_";
$molstructable = $dbprefix . $molstrucsuffix;
$moldatatable  = $dbprefix . $moldatasuffix;
$molstattable  = $dbprefix . $molstatsuffix;
$molcfptable   = $dbprefix . $molcfpsuffix;
$molfgbtable   = $dbprefix . $molfgbsuffix;
$pic2dtable    = $dbprefix . $pic2dsuffix;
$rxnstructable = $dbprefix . $rxnstrucsuffix;
$rxndatatable  = $dbprefix . $rxndatasuffix;
if ($usemem == 'T') {
  $molstattable  = $molstattable . $memsuffix;
  $molcfptable   = $molcfptable  . $memsuffix;
}

$safemol_id = escapeshellcmd($mol_id);
$saferxn_id = escapeshellcmd($rxn_id);

function showHit3D($id) {
  global $bitmapURLdir;
  global $molstructable;
  global $moldatatable;
  global $pic2dtable;
  global $digits;
  global $subdirdigits;
  global $db_id;
  global $access;
  global $jmolpath;
  $result3 = mysql_query("SELECT mol_name FROM $moldatatable WHERE mol_id = $id")
    or die("Query failed! (showHit3D)");
  while ($line3 = mysql_fetch_array($result3, MYSQL_ASSOC)) {
    $txt = $line3["mol_name"];
  }
  mysql_free_result($result3);
    //echo "<table class=\"table table-condensed table-bordered\" width=\"100%\">\n";
  echo "<table width=\"100%\">\n";
  echo "<tr>\n<td class=\"highlight\">\n";
  print "<b>$txt</b> (<a href=\"showmol.php?mol=${id}&db=${db_id}\" target=\"blank\">$id</a>)\n";
  echo "</td>\n</tr>\n";
  echo "</table>\n";

  if ($access >= 2) {    // display an "edit", "copy" and "erase" link for read/write data collections
    print "<a class=\"menu\" href=\"admin/editdata.php?db=${db_id}&id=${id}&action=editdata\" target=\"admin\">edit</a>\n";
    print "<a class=\"menu\" href=\"admin/editdata.php?db=${db_id}&id=${id}&action=duplcopy\" target=\"admin\">copy</a>\n";
    print "<a class=\"menu\" href=\"admin/editdata.php?db=${db_id}&id=${id}&action=eraserecord\">erase</a><br>\n";
    echo "</small>\n";
  }
  
  echo "<script>\n";
  echo "jmolInitialize(\"${jmolpath}\")\n";  // this _must_ be a relative path!
  echo "</script>\n";
  echo "<form>\n"; 
  //echo "<div class=\"col-lg-12 col-xm-12\">\n";
  echo "<table class=\"table table-bordered\" width=\"100%\">\n"; 
 // echo "<table>\n";
  echo "<tr>\n<td>\n";
  echo "<script>\n";
  echo "jmolApplet([700, 500], \"load showmol.php?mol=$id&db=$db_id\")";
  echo "</script>\n";
  echo "</td>\n<td>\n";
  //echo "</div>\n";
?>
  <script>
  jmolRadio("spacefill off; wireframe on", "wireframe<br>");
  jmolRadio("spacefill 20%; wireframe 0.15", "ball & stick<br>", "checked");
  jmolRadio("spacefill on", "CPK model<br>");
  jmolRadio("spin on", "rotation on<br>");
  jmolRadio("spin off", "rotation off<br>");
  jmolRadio("zoom 50; zoom on", "zoom 50%<br>");
  jmolRadio("zoom 100; zoom on", "zoom 100%<br>");
  jmolRadio("zoom 200; zoom on", "zoom 200%<br>");
  jmolRadio("dots off; set solvent off; dots on", "van der Waals surface<br>");
  jmolRadio("dots off; set solvent on; dots on", "Conolly surface<br>");
  jmolRadio("dots off", "no surface<br>");
  jmolRadio("spacefill off; wireframe 0.1; color atoms grey; select nitrogen; color green; spacefill 50%", "highlight N atoms<br>");
  jmolRadio("spacefill off; wireframe 0.1; color atoms grey; select oxygen; color purple; spacefill 50%", "highlight O atoms<br>");
  jmolRadio("select *; spacefill off; wireframe on; color atoms CPK; ", "highlights off<br>");
  jmolRadio("label %e; set labeloffset 0 0; font label 12; color label black", "labels: element<br>");
  jmolRadio("label %i; set labeloffset 0 0; font label 12; color label black", "labels: atom number<br>");
  jmolRadio("select *; label off", "labels off<br>");
  </script>
<?php
  echo "</td>\n</tr>\n</table>\n</form>\n";
}

function showHit2($id,$type) {
  global $enable_svg;
  global $enable_bitmaps;
  global $enable_jme;
  global $enable_jsme;
  global $bitmapURLdir;
  global $molstructable;
  global $moldatatable;
  global $rxnstructable;
  global $rxndatatable;
  global $pic2dtable;
  global $digits;
  global $subdirdigits;
  global $db_id;
  global $access;
  global $codebase;
  global $svg_mode;
  global $edtag;

// by default, assume $type is 1 (structure data collection)
  $item_name = "mol_name";
  $structable = $molstructable;
  $datatable = $moldatatable;
  $id_name = "mol_id";
  $par = "mol";

if ($type == 2) {
  $item_name = "rxn_name";
  $structable = $rxnstructable;
  $datatable = $rxndatatable;
  $id_name = "rxn_id";
  $par = "rxn";
}

  $result3 = mysql_query("SELECT $item_name FROM $datatable WHERE $id_name = $id")
    or die("Query failed! (showHit2)");
  while ($line3 = mysql_fetch_array($result3, MYSQL_ASSOC)) {
    $txt = $line3["$item_name"];
  }
  mysql_free_result($result3);

  echo "<table width=\"100%\">\n";
  echo "<tr>\n<td class=\"highlight\">\n";
  print "<b>$txt</b> (<a href=\"showmol.php?${par}=${id}&db=${db_id}\" target=\"blank\">$id</a>)\n";
  echo "</td>\n</tr>\n";
  echo "</table>\n";

  if ($access >= 2) {    // display an "edit", "copy" and "erase" link for read/write data collections
    echo "<small>\n";
    print "<a class=\"menu\" href=\"admin/editdata.php?db=${db_id}&id=${id}&action=editdata\" target=\"admin\">edit</a>\n";
    print "<a class=\"menu\" href=\"admin/editdata.php?db=${db_id}&id=${id}&action=duplcopy\" target=\"admin\">copy</a>\n";
    print "<a class=\"menu\" href=\"admin/editdata.php?db=${db_id}&id=${id}&action=eraserecord\">erase</a><br>\n";
    echo "</small>\n";    
  }

  $whatstr = "status";
  if ($svg_mode == 1) { $whatstr = "status, svg"; }
  $svg = "";

  $qstr = "SELECT $whatstr FROM $pic2dtable WHERE $id_name = $id";
  $result2 = mysql_query($qstr)
    or die("Query failed! (pic2d)");
  while ($line2 = mysql_fetch_array($result2, MYSQL_ASSOC)) {
    $status = $line2["status"];
    @$svg    = $line2["svg"];
  }
  mysql_free_result($result2);

  if ($status == 1) { $usebmp = TRUE; } else { $usebmp = FALSE; }

  echo "<tr>\n<td colspan=\"2\">\n";

  $struc_shown = FALSE;

  if ($enable_svg == "y") {  
    if ((strlen($svg) > 0) && ($svg_mode == 1)) {
      print "$svg\n";
      $struc_shown = TRUE;
    } elseif ($svg_mode == 2) {
      echo "<img src=\"showsvg.php?id=$id&db=$db_id\" alt=\"hit structure\">\n";
      $struc_shown = TRUE;
    }
  }

  if (($enable_bitmaps == "y") && ($struc_shown == FALSE)) {  
    if ((isset($bitmapURLdir)) && ($bitmapURLdir != "") && ($usebmp == true)) {
      while (strlen($id) < $digits) { $id = "0" . $id; }
      $subdir = '';
      if ($subdirdigits > 0) { $subdir = substr($id,0,$subdirdigits) . '/'; }
      print "<img src=\"${bitmapURLdir}/${db_id}/${subdir}${id}.png\" alt=\"hit structure\">\n";
      $struc_shown = TRUE;
    } 
  }

  if ((($enable_jme == "y") || ($enable_jsme == "y")) && ($struc_shown == FALSE)) {  
    // if no bitmaps are available, we must invoke another instance of JME/JSME 
    // in "depict" mode for structure display of each hit
    $qstr = "SELECT struc FROM $structable WHERE $id_name = $id";
    $result3 = mysql_query($qstr) or die("Query failed! (struc)");    
    while ($line3 = mysql_fetch_array($result3, MYSQL_ASSOC)) {
      $molstruc = $line3["struc"];
    }
    mysql_free_result($result3);
  
    // JME needs MDL molfiles with the "|" character instead of linebreaks
    $jmehitmol = tr4jme($molstruc);
    echo "<$edtag code=\"JME.class\" archive=\"JME.jar\" $codebase\n";
    echo "width=\"250\" height=\"120\">";
    echo "<param name=\"options\" value=\"depict\"> \n";
    echo "<param name=\"mol\" value=\"$jmehitmol\">\n";
    echo "</$edtag>\n";
    $struc_shown = TRUE;
  }
  echo "</td>\n</tr>\n";
}

function showData_old($id) {
  echo "<p />\n";
  global $moldatatable;
  $result4 = mysql_query("SELECT * FROM $moldatatable WHERE mol_id = $id")
    or die("Query failed! (showData_old)");
  $y = mysql_num_fields($result4);
  echo "<table>\n";
  while ($line4 = mysql_fetch_array($result4, MYSQL_BOTH)) {
    for ($x = 0; $x < $y; $x++) {
      $fieldname = mysql_field_name($result4, $x);
      //$fieldtype = mysql_field_type($result4, $x);
      if ($fieldname != "mol_name" && $fieldname != "mol_id" && $line4[$fieldname] != "") {
        //echo  "<b>$fieldname:</b> \t$line4[$fieldname] <br>\n";
        echo "<tr>\n";
        echo "  <td><b>$fieldname</b></td><td>$line4[$fieldname]</td>\n";
        echo "</tr>\n";
      }
    }
    echo "<br>\n";
  }
  echo "</table>\n";
  mysql_free_result($result4);
}

function showData($id) {
  echo "<p />\n";
  global $dbtype;
  global $moldatatable;
  global $rxndatatable;
  if ($dbtype == 1) {
    $idname = "mol_id";
    $namename = "mol_name";
    $datatable = $moldatatable;
  } elseif ($dbtype == 2) {
    $idname = "rxn_id";
    $namename = "rxn_name";
    $datatable = $rxndatatable;
  } else { exit; }
  //echo "<table class=\"table table-bordered\" width=\"100%\">\n"; 
  echo "<table class=\"table table-bordered\" border=\"0\" cellspacing=\"0\" cellpadding=\"4\" width=\"100%\">\n";
  $qstr = "SHOW FULL COLUMNS FROM $datatable";
  $result = mysql_query($qstr)
    or die("Query failed! (showData)");
  while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
    $field   = $line["Field"];
    $label = $field;
    $type    = $line["Type"];
    $comment = $line["Comment"];
    if (($field != $idname) && ($field != $namename)) {
      $format = 1;
      if (strlen($comment)>4) {
        $pos = strpos($comment, ">>>>");
        if ($pos !== false) {
          if ($pos == 0) {
            $comment = str_replace(">>>>","",$comment);
            $acomment = explode("<",$comment);
            $label  = $acomment[0];
            $nformat = $acomment[1];
            if ($nformat == 0) { $format = 0; }
            if ($nformat == 1) { $format = 1; }
            if ($nformat == 2) { $format = 2; }
            if ($nformat == 3) { $format = 3; }
            if ($nformat == 4) { $format = 4; }
          }
        }
      }
      $dataval = "";   //preliminary....
      $qstr2 = "SELECT $field FROM $datatable WHERE $idname = $id";
      $result2 = mysql_query($qstr2)
        or die("Query failed! (dataval)");
      $line2 = mysql_fetch_row($result2);
      mysql_free_result($result2);
      $dataval = $line2[0];
      if (($format > 0) && (strlen($dataval) > 0)) {
        if ($label != "") { $field = $label; }
        echo "<tr><td valign=\"top\"><b>$field</b></td>";
        if ($format == 1) { echo "<td valign=\"top\">$dataval</td></tr>\n"; }
        if ($format == 2) { echo "<td valign=\"top\"><pre>$dataval</pre></td></tr>\n"; }
        if ($format == 3) { 
          $mfdata = mfreformat($dataval);
          echo "<td valign=\"top\">$mfdata</td></tr>\n";
        }
        if ($format == 4) { 
          $urldata = urlreformat($dataval);
          echo "<td valign=\"top\">$urldata</td></tr>\n";
        }
      } // if ($format > 0)...
    }  // if...
  }  // while
  echo "</table>\n";
  mysql_free_result($result);
}


if (($safemol_id !='') || ($saferxn_id !=''))  { 
  print "<h3><span style=\"color:purple\">${sitename}: details for selected entry</span></h3>\n";
  if (($dbtype == 1) && ($safemol_id !='')) {
    if ($use3d == "y") {
      showHit3D($safemol_id);
    } else {
      showHit2($safemol_id,$dbtype);
    }
    showData($safemol_id);
  } elseif (($dbtype == 2) && ($saferxn_id !='')) {
    showHit2($saferxn_id,$dbtype);
    showData($saferxn_id);
  }
} else {
  echo "<h2>No molecule/reaction ID specified!</h2>\n";
}

?>
</body>
</html>
