<?php 
// moldbgss.php     Norbert Haider, University of Vienna, 2014
// part of MolDB6   last change: 2014-08-18

/**
 * @file moldbgss.php
 * @author Norbert Haider
 * 
 * This script performs (sub-)structure and similarity searches in
 * structure data collections and (sub-)structure searches in reaction
 * data collections. It includes incsim.php, incss.php and incrss.php.
 */

$myname = $_SERVER['PHP_SELF'];
require_once("functions.php");
require_once("rxnfunct.php");

$mol     = '';

// some default settings (will be overridden by the config files)
$enable_svg     = "y";   # first choice (overridden by settings in moldb6conf.php)
$enable_bitmaps = "y";   # second choice (overridden by settings in moldb6conf.php)
$enable_jme     = "y";   # structure editor; fallback for 2D display
$default_editor = "jme"; # if not set in moldb6conf.php ("jme" or "jsme")

$editors = array("text","ketcher","jme","jsme","flame");
$edmode  = "flex";       # possible values: "mol", "rxn", "flex" (for JME and JSME)

$sitename       = "Sristi Biosciences";
$cssfilename    = "moldb.css";


/**
 * Debug level
 * - 0:            remain silent, higher values: be more verbose
 * - odd numbers:  output as HTML comments, 
 * - even numbers: output as clear-text messages
 */
$debug = 1;

/**
 * set to a value between 0.0 and 1.0 for the relative weight
 * of functional similarity in a similarity search
 */
$fsim = 0.5; 
$ssim_wt     = 1 - $fsim;
$fsim_wt     = $fsim;

/**
 * enable or disable download of hit structures ("y" or "n"),
 * set the maximum number of downloaded structures; these defaults
 * can be overridden by the settings in moldb6uiconf.php
 */
$enable_download = "n";
$download_limit  = 100;

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


if (config_quickcheck() > 0) { die(); }
set_charset($charset);

$user     = $ro_user;           # from configuration file
$password = $ro_password;

if ($user == "") {
  die("no username specified!\n");
}

if (!isset($sitename) || ($sitename == "")) {
  $sitename = "Sristi Biosciences";
}

$defaultmode = 2;              // 1 = exact, 2 = substructure, 3 = similarity
$exact  = "n";                 // search options
$strict = "n";
$stereo = "n";

$maxhits = 1000;               // maximum number of hits we want to allow
$maxcand = 5000;               // maximum number of candidate structures we want to allow

@$dbcont  = $_REQUEST['dbcont'];
@$idcont  = $_REQUEST['idcont'];
@$dbcont = intval($dbcont);
@$idcont = intval($idcont);

if (!isset($dbcont)) { $dbcont = 0; }
if (!isset($idcont)) { $idcont = 0; }

@$prev_nhits = $_POST['nhits'];
@$prev_hitlist = $_POST['hits'];
$nhits = 0;
$hitlist = "";
if (isset($prev_nhits) && is_numeric($prev_nhits) && ($prev_nhits > 0)) { $nhits = $prev_nhits; }
if (isset($prev_hitlist) && (strlen($prev_hitlist) > 0)) { $hitlist = $prev_hitlist; }

$usebfp  = 'y';
$usehfp  = 'y';

$dbcont = 0;                   // for rxn only
$idcont = 0;                   // for rxn only

if ($enablereactions != "y") {
  $edmode  = "mol";
}

$ostype = getostype();

// choice of structure editors
@$curr_settings = $_COOKIE["MolDB6"];
$curr_a = explode(",",$curr_settings);
foreach ($curr_a as $curr_line) {
  if (strpos($curr_line,"editor") !== FALSE) {
    $ed_a = explode("=",$curr_line);
    $editor = $ed_a[1];
  }
}

if ((!isset($editor)) || ($editor == "") || (!in_array($editor,$editors))) {
  $editor = $default_editor;
}

$editor = "jme";

if ($editor == "jme") {
  $edtag  = "applet";    // "applet" (for JME) or "div" (for JSME)
} else {
  $edtag  = "div";    // "applet" (for JME) or "div" (for JSME)
}

@$dbstr   = $_REQUEST['db'];
$dbl     = explode(",",$dbstr);
$dbstr_orig = $dbstr;

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
    //$dba[($ndbsel - 1)] = $dbl[($ndbsel - 1)];
    $dba[($ndbsel - 1)] = $db_id;
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
if ($editor == "jsme") { $sendjsmeheader = 1; }
if ($svg_mode == 0) {
  if ($editor !== "jme") { $sendjsmeheader = 1; }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=<?php echo "$html_charset"; ?>">
<meta http-equiv="imagetoolbar" content="no"> 
<meta name="author" content="Norbert Haider, University of Vienna">
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<script src="js/jquery-1.10.2.js"></script>
<script src="js/jquery-ui-1.10.4.min.js"></script>
<script src="js/bootstrap.min.js"></script>

<link href="css/bootstrap.css" rel="stylesheet">
<link href="css/font-awesome.min.css" rel="stylesheet">
<link href="css/jquery-ui-1.10.4.min.css" rel="stylesheet">



<title><?php echo "$sitename"; ?>: structure search</title>
<?php 
insert_style($cssfilename); 
if ($sendjsmeheader == 1) {
  echo "<script type=\"text/javascript\" language=\"javascript\" src=\"${jsme_path}/jsme.nocache.js\"></script>\n";
}

@$action  = $_POST['action'];
@$mol     = $_POST['mol'];
@$rmode   = $_POST['mode'];
@$strict  = $_POST['strict'];
@$stereo  = $_POST['stereo'];
# @$db_id   = $_REQUEST['db'];
@$fsim = $_POST["fsim"];
if (isset($fsim) && is_numeric($fsim)) {
  if (($fsim >= 0) && ($fsim <= 1)) {
    $fsim_wt = $fsim;
    $ssim_wt = 1 - $fsim_wt;   // the sum must be 1  
  }
}
$fsim10  = intval(10*$fsim_wt);
$fsim100 = intval(100*$fsim_wt);


if ($enable_prefs == "y") {
  mkprefscript();
}

// if reaction support is not enabled, transform any RXN input into MOL
if (($enablereactions !== "y") && (strlen($mol) > 0) && (is_rxnfile($mol))) {
  $rmols = get_allrxnmols($mol);
  $mol = $rmols[0];   // just take the first molecule  
}

?>

<script language="javascript" type="text/javascript">

<?php
if (strlen($mol) > 0) {
  $mola = explode("\n",$mol);
  echo "  var mymol = \n";
  echo "    [\n";
  foreach($mola as $mline) {
    $mline = rtrim($mline);
    echo "    \"$mline\",\n";
  }
  echo "    ].join(\"\\n\");\n";
} else {
  echo "  var mymol = \"\";\n";
}
echo "\n  var selectededitor = \"$editor\";\n";
?>

  function loadmol() {
    document.form.mol.value = mymol;
  }
  
  function openeditorwindow() {
    if (selectededitor == "text") {
      window.open('textinput.php','input','width=700,height=400,scrollbars=no,resizable=yes');
    }
    if (selectededitor == "ketcher") {
      window.open('ew4ketcher.php','input','width=820,height=592,scrollbars=no,resizable=yes');
    }
    if (selectededitor == "jme") {
      window.open('ew4jme.php?mode=<?php echo "$edmode"; ?>','input','width=750,height=550,scrollbars=no,resizable=yes');
    }
    if (selectededitor == "jsme") {
      window.open('ew4jsme.php?mode=<?php echo "$edmode"; ?>','input','width=750,height=550,scrollbars=no,resizable=yes');
    }
    if (selectededitor == "flame") {
      window.open('ew4flame.php','input','width=740,height=540,scrollbars=no,resizable=yes');
    }
  }

  function do_search() {
    if (mymol.length < 1) {
      alert("No molecule!");
    }
    else {
      document.form.mol.value = mymol;
      document.form.action.value = "1";
      document.form.submit();
    }
  }

  function clear_struc() {
    document.form.mol.value = "";
    document.form.action.value = "0";
    document.form.submit();
  }

  function getmoltext() {
    return(mymol);
  }

  function putmoltext(moltext) {
    if (moltext.length < 1) {
      alert("No molecule!");
      return;
    }
    else {
      document.form.mol.value = moltext;
      document.form.action.value = "0";
      document.form.submit();
    }    
  }

  function getedmode() {
    var myedmode = document.form.edmode.value;
    return(myedmode);
  }

</script>


</head>
<body>
<div class="container">
  <div class="row">
    <div class="col-md-12 col-xm-12">
      <?php show_header($myname , $dbstr); ?>
    </div>
  </div>
</div>


<?php
if ($debug > 0) { debug_output("MolDB6, OS type $ostype"); }

$mode = $defaultmode;


//show_header($myname,$dbstr);

$ndb = get_numdb_readable();
if ($ndb == 0) { 
  echo "<h3>no data collection available</h3><br>\n</body>\n</html>\n"; 
  die();
}


echo "<h2><span style=\"color:Purple\">${sitename}</span></h2>\n";

$jmemol = "";
if (strlen($mol) > 10) {
  $jmemol = tr4jme($mol);
}

//############################################################################################


$safemol = clean_molfile($mol);

echo "<table border=\"1\">\n<tr>\n<td>\n";
echo "<div class=\"moldiv\" id=\"strucdiv\" onclick=\"openeditorwindow()\" >\n";
if ($mol !='') { 
  if ($svg_mode == 1) {
    if (is_rxnfile($mol)) { $mol2svgopt = "-R " . $mol2svgopt; }
    if ($ostype == 1) { print filterThroughCmd("$safemol",  "$MOL2SVG $mol2svgopt -"); }
    if ($ostype == 2) { print filterThroughCmd2("$safemol", "$MOL2SVG $mol2svgopt -"); }
  } else {    // use JME/JSME as fallback
    $jmehitmol = tr4jme($safemol);
    echo "<$edtag code=\"JME.class\" archive=\"JME.jar\" $codebase\n";
    echo "width=\"250\" height=\"120\">";
    echo "<param name=\"options\" value=\"depict\"> \n";
    echo "<param name=\"mol\" value=\"$jmehitmol\">\n";
    if ($edtag == "applet" ) {
      echo "</$edtag>click here to edit...\n";
    }
  }
  #print "<pre>$safemol</pre>\n";
}  else {
  if ($svg_mode == 1) {
    echo "<?xml version=\"1.0\" standalone=\"no\" ?>\n";
    echo "<svg width=\"186\" height=\"94\" viewbox=\"0 5 186 94\" xmlns=\"http://www.w3.org/2000/svg\">\n";
    echo "<style type=\"text/css\"><![CDATA[ circle { stroke: #FFFFFF; fill: #FFFFFF; }\n";
    echo "text { font-family: Helvetica; font-size: 14px; } line { stroke: #BBBBBB; stroke-width: 1.0; } ]]> </style>\n";
    echo "<g>\n";
    echo "  <g transform=\"translate(0,-207)\">\n";
    echo "  <path stroke=\"#BBBBBB\" stroke-width=\"1.0\" d=\"\n";
    echo "  M 27.6 270.1 L 27.6 248.2 \n";
    echo "  M 30.8 250.7 L 47.2 241.2 \n";
    echo "  M 27.6 248.2 L 46.6 237.2 \n";
    echo "  M 47.2 277.1 L 30.8 267.6 \n";
    echo "  M 46.6 281.1 L 27.6 270.1 \n";
    echo "  M 65.6 270.1 L 46.6 281.1 \n";
    echo "  M 61.8 249.7 L 61.8 268.6 \n";
    echo "  M 65.6 248.2 L 65.6 270.1 \n";
    echo "  M 46.6 237.2 L 65.6 248.2 \n";
    echo "  \" />\n";
    echo "<text x=\"17.0\" y=\"261.5\" fill=\"gray\"><tspan>no structure defined....</tspan></text>\n";
    echo "  </g>\n";
    echo "</g>\n";
    echo "</svg>\n";
  } else {   // use a static image as fallback
    echo "<img src=\"nostruc.png\" alt=\"empty structure\">\n";
  }

}
echo "</div>\n</td>\n</tr>\n</table>\n";
//echo "<small>To create/edit a structure,<br>\n";
//echo "click on the image above &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";


echo "<p>\n";


if ($mol == "") {

  echo "<form name=\"form\" action=\"$myname\" method=\"post\">\n";
  echo "<input type=\"hidden\" name=\"mol\">\n";
  echo "<input type=\"hidden\" name=\"db\" value=\"$dbstr\">\n";
  echo "<input type=\"hidden\" name=\"action\" value=\"0\">\n";
  echo "</form>\n";  

  echo "<hr>To create/edit a structure, click on the image above (your browser must be configured to accept pop-up windows).<br>\n";
  if ($enable_prefs == "y") {
    echo "In the \"Preferences\" menu, you can select your preferred structure editor.<br>\n";
  }

  echo "</body></html>";
  die();
}

if (is_rxnfile($mol)) {   //##################### RXN #############################

  if (!isset($rmode)) { $mode   = $defaultmode; }       # 1 = exact, 2 = substructure, 3 = similarity
  if ($rmode == 1) { $mode = 1; }
  if ($rmode == 2) { $mode = 2; }
  if ($rmode == 3) { $mode = 2; }    # in RXN mode, there is no similarity search
  if ($mode == 1) {
    $exact = "y";
  }

  if ($exact == 'y') {
    $usebfp  = 'n';
    $usehfp  = 'n';
  }

#  echo "This is a reaction<br>\n";
#  echo "<form name=\"form\" action=\"$myname\" method=\"post\">\n";
#  echo "<input type=\"hidden\" name=\"mol\">\n";
#  echo "<input type=\"hidden\" name=\"db\" value=\"$dbstr\">\n";
#  echo "<input type=\"hidden\" name=\"action\" value=\"0\">\n";
#  echo "</form>\n";  
?>

<form name="form" action="<?php echo $myname;?>" method="post">
<input type="radio" name="mode" value="1" <?php if ($mode == 1) { echo "checked"; } ?>>exact search
<input type="radio" name="mode" value="2" <?php if ($mode == 2) { echo "checked"; } ?>>substructure search<br>
<input type="checkbox" name="strict" value="y" <?php if ($strict == "y") { echo "checked"; } ?>>strict atom/bond type comparison<br />
<input type="checkbox" name="stereo" value="y" <?php if ($stereo == "y") { echo "checked"; } ?>>check configuration (E/Z and R/S)<br />&nbsp;<br />
<!-- <input type="button" value="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Search&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" onClick="check_ss()"> -->
<input type="hidden" name="action" value="1">

<input type="button" value="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Search&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" onClick="do_search()">&nbsp;&nbsp;
<input type="button" value="Clear" onClick="clear_struc()">&nbsp;&nbsp;


<input type="hidden" name="mol">
<input type="hidden" name="db" value="<?php echo "$dbstr"?>">
</form>

<?php


#  echo "</body></html>";
#  die();



} else {  //##################### MOL #############################

if (!isset($rmode)) { $mode   = $defaultmode; }       # 1 = exact, 2 = substructure, 3 = similarity
if ($rmode == 1) { $mode = 1; }
if ($rmode == 2) { $mode = 2; }
if ($rmode == 3) { $mode = 3; }
if ($mode == 1) {
  $exact = "y";
}

if ($exact == 'y') {
  $usebfp  = 'n';
  $usehfp  = 'n';
}

?>

<form name="form" action="<?php echo $myname;?>" method="post">
<input type="radio" name="mode" value="1" <?php if ($mode == 1) { echo "checked"; } ?>>exact search
<input type="radio" name="mode" value="2" <?php if ($mode == 2) { echo "checked"; } ?>>substructure search
<input type="radio" name="mode" value="3" <?php if ($mode == 3) { echo "checked"; } ?>>similarity search,
 <small>using a structural:functional similarity ratio of 
 <select size = "1" name="fsim">
 <option value="0.0"<?php if ($fsim10 == 0) { echo "selected"; } ?>>100:0</option>
 <option value="0.1"<?php if ($fsim10 == 1) { echo "selected"; } ?>>90:10</option>
 <option value="0.2"<?php if ($fsim10 == 2) { echo "selected"; } ?>>80:20</option>
 <option value="0.3"<?php if ($fsim10 == 3) { echo "selected"; } ?>>70:30</option>
 <option value="0.4"<?php if ($fsim10 == 4) { echo "selected"; } ?>>60:40</option>
 <option value="0.5"<?php if ($fsim10 == 5) { echo "selected"; } ?>>50:50</option>
 <option value="0.6"<?php if ($fsim10 == 6) { echo "selected"; } ?>>40:60</option>
 <option value="0.7"<?php if ($fsim10 == 7) { echo "selected"; } ?>>30:70</option>
 <option value="0.8"<?php if ($fsim10 == 8) { echo "selected"; } ?>>20:80</option>
 <option value="0.9"<?php if ($fsim10 == 9) { echo "selected"; } ?>>10:90</option>
 <option value="1.0"<?php if ($fsim10 == 10) { echo "selected"; } ?>>0:100</option>
 </select>
 </small><br />
<input type="checkbox" name="strict" value="y" <?php if ($strict == "y") { echo "checked"; } ?>>strict atom/bond type comparison<br />
<input type="checkbox" name="stereo" value="y" <?php if ($stereo == "y") { echo "checked"; } ?>>check configuration (E/Z and R/S)<br />&nbsp;<br />
<input type="hidden" name="mol">
<input type="hidden" name="db" value="<?php echo "$dbstr"?>">
<input type="hidden" name="action" value="1">

<input type="button" value="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Search&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" onClick="do_search()">&nbsp;&nbsp;
<input type="button" value="Clear" onClick="clear_struc()">&nbsp;&nbsp;

</form>

<?php
}  //######################################################## if is_rxnfile....

echo "<hr />\n";

#echo "<pre>action: $action\n";
#echo "mol:\n$mol\n";
#echo "</pre>\n";

if ($action == "0") {
  echo "</body></html>\n";
  die();
}

$options = '';
if ($strict == 'y') {
  $options = $options . 'ais';  // 'a' for charges, 'i' for isotopes (checkmol v0.3p)
}
if ($exact == 'y') {
  $options = $options . 'x';
}
if ($stereo == 'y') {
  $options = $options . 'gG';
}

if (strlen($options) > 0) {
  $options = '-' . $options;
}

// remove CR if present (IE, Mozilla et al.) and add it again (for Opera)
$mol = str_replace("\r\n","\n",$mol);
$mol = str_replace("\n","\r\n",$mol);

//$safemol = escapeshellcmd($mol);
$safemol = str_replace(";"," ",$mol);


if (is_rxnfile($mol)) {
  $saferxn = $safemol;
  include("incrss.php");
} else {

  if ($mode < 3) {
    include("incss.php");
  } else {
    include("incsim.php");
  }

}
function is_rxnfile($testfile) {
  $result = FALSE;
  if (strpos($testfile,"\$RXN") === 0) { $result = TRUE; }
  return($result);
}

function get_allrxnmols($rxn) {
  $rxna = array();
  $all = explode("\$MOL\r\n",$rxn);
  $hdr = array_shift(&$all);
  return($all);
}

function clean_molfile($infile) {
  $outfile = $infile;
  $outfile = str_replace(";","",$outfile);
  $outfile = str_replace("\"","",$outfile);
  $outfile = str_replace("\'","",$outfile);
  $outfile = str_replace("\`","",$outfile);
  $outfile = str_replace("\|","",$outfile);
  $outfile = str_replace("\\","",$outfile);
  // special treatment for RXN-files:  
  $outfile = str_replace("\$RXN","\\\$RXN",$outfile);
  $outfile = str_replace("\$MOL","\\\$MOL",$outfile);
  return($outfile);
}


?>
</body>
</html>
