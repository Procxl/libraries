<?php 
// moldbfgb.php    Norbert Haider, University of Vienna, 2007-2014
// part of MolDB6  last change: 2014-08-18

/**
 * @file moldfgb.php
 * @author Norbert Haider
 * 
 * This script performs the functional group search in structure and
 * reaction data collections.
 */

$myname = $_SERVER['PHP_SELF'];
require_once("functions.php");

/**
 * enable or disable download of hit structures ("y" or "n"),
 * set the maximum number of downloaded structures; these defaults
 * can be overridden by the settings in moldb6uiconf.php
 */
$enable_download = "n";
$download_limit  = 100;

/**
 * Debug level
 * - 0:            remain silent, higher values: be more verbose
 * - odd numbers:  output as HTML comments, 
 * - even numbers: output as clear-text messages
 */
$debug = 1;

// some default settings (will be overridden by the config files)
$enable_svg     = "y";   # first choice (overridden by settings in moldb6conf.php)
$enable_bitmaps = "y";   # second choice (overridden by settings in moldb6conf.php)
$enable_jme     = "y";   # structure editor; fallback for 2D display
$enable_jsme    = "y";   # structure editor; fallback for 2D display
$sitename       = "Sristi Biosciences";
$cssfilename    = "moldb.css";

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

$rxnmodes = FALSE;

foreach ($dba as $db_id) {
  if (exist_db($db_id)) {$mydb = get_dbproperties($db_id); }
  $dbtype = $mydb['type'];
  if (($dbtype >= 2) && ($enablereactions == "y")) { $rxnmodes = TRUE; }
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

<title><?php echo "$sitename"; ?>: functional group search</title>
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
      <?php show_header($myname , $dbstr); ?>
    </div>
  </div>
</div>

<?php
$maxhits = 1000;              // maximum number of hits we want to allow
$max_selections = 50;         // maximum number of selections in listbox

@$action   = $_POST['action'];
@$mode     = $_POST['mode'];
@$checkbox = $_POST['checkbox'];

//show_header($myname,$dbstr);

$ndb = get_numdb_readable();
if ($ndb == 0) { 
  echo "<h3>no data collection available</h3><br>\n</body>\n</html>\n"; 
  die();
}

echo "<h2><span style=\"color:Purple\">${sitename}</span></h2>\n";
echo "<p>Search for molecules containing the following functional groups<br>\n";
echo "(multiple selections are possible)</p>\n";
echo "<form action=\"$myname\" method=post>\n";
?>

<select size="15" name="checkbox[]" multiple >
	<option value="201">aromatic compound</option>
	<option value="202">heterocycle</option>
	<option value="199">alkene</option>
	<option value="200">alkyne</option>

	<option value="3">carbonyl compound (general)</option>
	<option value="5">&nbsp;&nbsp;&nbsp;ketone</option>
	<option value="4">&nbsp;&nbsp;&nbsp;aldehyde</option>
	<option value="19">&nbsp;&nbsp;&nbsp;acetal</option>
	<option value="17">&nbsp;&nbsp;&nbsp;carbonyl hydrate</option>
	<option value="18">&nbsp;&nbsp;&nbsp;hemiacetal</option>
	<option value="23">&nbsp;&nbsp;&nbsp;thioacetal</option>
	<option value="24">&nbsp;&nbsp;&nbsp;enamine</option>
	<option value="26">&nbsp;&nbsp;&nbsp;enol ether</option>
	<option value="25">&nbsp;&nbsp;&nbsp;enol</option>
	<option value="36">&nbsp;&nbsp;&nbsp;enediol</option>
	<option value="9">&nbsp;&nbsp;&nbsp;imine</option>
	<option value="10">&nbsp;&nbsp;&nbsp;hydrazone</option>
	<option value="14">&nbsp;&nbsp;&nbsp;oxime ether</option>
	<option value="13">&nbsp;&nbsp;&nbsp;oxime</option>
	<option value="21">&nbsp;&nbsp;&nbsp;aminal</option>
	<option value="20">&nbsp;&nbsp;&nbsp;hemiaminal</option>
	<option value="11">&nbsp;&nbsp;&nbsp;semicarbazone</option>
	<option value="12">&nbsp;&nbsp;&nbsp;thiosemicarbazone</option>
	<option value="22">&nbsp;&nbsp;&nbsp;thiohemiaminal</option>
	<option value="108">&nbsp;&nbsp;&nbsp;iminohetarene</option>
	<option value="106">&nbsp;&nbsp;&nbsp;oxohetarene</option>
	<option value="107">&nbsp;&nbsp;&nbsp;thioxohetarene</option>

	<option value="75">carboxylic acid derivative (general)</option>
	<option value="76">&nbsp;&nbsp;&nbsp;carboxylic acid</option>
	<option value="77">&nbsp;&nbsp;&nbsp;carboxylic acid salt</option>
	<option value="112">&nbsp;&nbsp;&nbsp;carboxylic acid anhydride</option>
	<option value="91">&nbsp;&nbsp;&nbsp;carboxylic acid halide</option>
	<option value="78">&nbsp;&nbsp;&nbsp;carboxylic acid ester</option>
	<option value="79">&nbsp;&nbsp;&nbsp;lactone</option>
	<option value="204">&nbsp;&nbsp;&nbsp;alpha-hydroxyacid</option>
	<option value="203">&nbsp;&nbsp;&nbsp;alpha-aminoacid</option>
	<option value="90">&nbsp;&nbsp;&nbsp;nitrile</option>
	<option value="88">&nbsp;&nbsp;&nbsp;carboxylic acid amidine</option>
	<option value="97">&nbsp;&nbsp;&nbsp;imidoester</option>
	<option value="105">&nbsp;&nbsp;&nbsp;imidothioester</option>
	<option value="109">&nbsp;&nbsp;&nbsp;orthoacid derivative (general)</option>
	<option value="16">&nbsp;&nbsp;&nbsp;ketene acetal derivative</option>
	<option value="80">&nbsp;&nbsp;&nbsp;carboxylic acid amide (general)</option>
	<option value="81">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;primary carboxylic acid amide</option>
	<option value="82">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;secondary carboxylic acid amide</option>
	<option value="83">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;tertiary carboxylic acid amide</option>
	<option value="84">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;lactam</option>
	<option value="113">&nbsp;&nbsp;&nbsp;carboxylic acid imide (general)</option>
	<option value="115">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;carboxylic acid imide (N-substituted)</option>
	<option value="114">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;carboxylic acid imide (N-unsubstituted)</option>
	<option value="85">&nbsp;&nbsp;&nbsp;carboxylic acid hydrazide</option>
	<option value="87">&nbsp;&nbsp;&nbsp;hydroxamic acid</option>
	<option value="99">&nbsp;&nbsp;&nbsp;thiocarboxylic acid derivative (general)</option>
	<option value="100">&nbsp;&nbsp;&nbsp;thiocarboxylic acid</option>
	<option value="103">&nbsp;&nbsp;&nbsp;thiocarboxylic acid amide</option>
	<option value="104">&nbsp;&nbsp;&nbsp;thiolactam</option>
	<option value="101">&nbsp;&nbsp;&nbsp;thiocarboxylic acid ester</option>
	<option value="102">&nbsp;&nbsp;&nbsp;thiolactone</option>

	<option value="116">CO2 derivative (general)</option>
	<option value="134">&nbsp;&nbsp;&nbsp;isourea</option>
	<option value="136">&nbsp;&nbsp;&nbsp;isothiourea</option>
	<option value="137">&nbsp;&nbsp;&nbsp;guanidine</option>
        <option value="145">&nbsp;&nbsp;&nbsp;isocyanate</option>
	<option value="147">&nbsp;&nbsp;&nbsp;isothiocyanate</option>
	<option value="133">&nbsp;&nbsp;&nbsp;urea</option>
	<option value="125">&nbsp;&nbsp;&nbsp;carbamic acid derivative (general)</option>
	<option value="126">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;carbamic acid</option>
	<option value="127">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;carbamic acid ester (urethane)</option>
	<option value="117">&nbsp;&nbsp;&nbsp;carbonic acid derivative (general)</option>
	<option value="119">&nbsp;&nbsp;&nbsp;carbonic acid diester</option>
	<option value="138">&nbsp;&nbsp;&nbsp;semicarbazide</option>
	<option value="139">&nbsp;&nbsp;&nbsp;thiosemicarbazide</option>
	<option value="135">&nbsp;&nbsp;&nbsp;thiourea</option>
	<option value="129">&nbsp;&nbsp;&nbsp;thiocarbamic acid derivative (general)</option>
	<option value="130">&nbsp;&nbsp;&nbsp;thiocarbamic acid</option>
	<option value="131">&nbsp;&nbsp;&nbsp;thiocarbamic acid ester (thiourethane)</option>

	<option value="60">N-oxide</option>
	<option value="46">hydroxylamine derivative</option>
	<option value="47">amine (general)</option>
	<option value="48">&nbsp;&nbsp;&nbsp;primary amine</option>
	<option value="50">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;primary aromatic amine (arylamine)</option>
	<option value="49">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;primary aliphatic amine (alkylamine)</option>
	<option value="51">&nbsp;&nbsp;&nbsp;secondary amine</option>
	<option value="54">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;secondary aromatic amine (diarylamine)</option>
	<option value="53">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;secondary mixed amine (alkylarylamine)</option>
	<option value="52">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;secondary aliphatic aminw (dialkylamine)</option>
	<option value="55">&nbsp;&nbsp;&nbsp;tertiary amine</option>
	<option value="58">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;tertiary aromatic amine (triarylamine)</option>
	<option value="57">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;tertiary mixed amin (alkylarylamine)</option>
	<option value="56">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;tertiary aliphatic amine (trialkylamine)</option>
	<option value="59">&nbsp;&nbsp;&nbsp;quaternary ammonium compound</option>
	<option value="45">hydrazine derivative (general)</option>
	<option value="141">azo compound</option>
	<option value="151">nitrite</option>
	<option value="140">azide</option>
	<option value="150">nitro compound</option>
	<option value="149">nitroso compound</option>
	<option value="152">nitrate</option>
	<option value="37">ether (general)</option>
	<option value="40">&nbsp;&nbsp;&nbsp;diaryl ether</option>
	<option value="39">&nbsp;&nbsp;&nbsp;alkyl aryl ether</option>
	<option value="38">&nbsp;&nbsp;&nbsp;dialkyl ether</option>
	<option value="27">hydroxy compound (general)</option>
	<option value="34">&nbsp;&nbsp;&nbsp;phenol</option>
	<option value="35">&nbsp;&nbsp;&nbsp;1,2-diphenol</option>
	<option value="28">&nbsp;&nbsp;&nbsp;alcohol</option>
	<option value="29">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;primary alcohol</option>
	<option value="30">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;secondary alcohol</option>
	<option value="31">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;tertiary alcohol</option>
	<option value="32">&nbsp;&nbsp;&nbsp;1,2-diol</option>
	<option value="33">&nbsp;&nbsp;&nbsp;1,2-aminoalcohol</option>
	<option value="43">peroxide (general)</option>
	<option value="44">&nbsp;&nbsp;&nbsp;hydroperoxide</option>
	<option value="41">thioether</option>
	<option value="178">thiol (sulfanyl compound, mercaptane; general)</option>
	<option value="180">&nbsp;&nbsp;&nbsp;arylthiol</option>
	<option value="179">&nbsp;&nbsp;&nbsp;alkylthiol</option>
	<option value="42">disulfide</option>
	<option value="167">sulfoxide</option>
	<option value="166">sulfone</option>
	<option value="161">sulfonic acid derivative (general)</option>
	<option value="164">sulfonamide</option>
	<option value="162">sulfonic acid</option>
	<option value="163">sulfonic acid ester</option>
	<option value="153">sulfuric acid derivative (general)</option>
	<option value="159">&nbsp;&nbsp;&nbsp;sulfuric acid diamide</option>
	<option value="158">&nbsp;&nbsp;&nbsp;sulfuric acid amide</option>
	<option value="157">&nbsp;&nbsp;&nbsp;sulfuric acid amide ester</option>
	<option value="154">&nbsp;&nbsp;&nbsp;sulfuric acid</option>
	<option value="155">&nbsp;&nbsp;&nbsp;sulfuric acid monoester</option>

	<option value="181">phosphoric acid derivative (general)</option>
	<option value="182">&nbsp;&nbsp;&nbsp;phosphoric acid</option>
	<option value="183">&nbsp;&nbsp;&nbsp;phosphoric acid ester</option>
	<option value="184">&nbsp;&nbsp;&nbsp;phosphoric acid halide</option>
	<option value="185">&nbsp;&nbsp;&nbsp;phosphoric acid amide</option>
	<option value="186">&nbsp;&nbsp;&nbsp;thiophosphoric acid derivative</option>
	<option value="187">&nbsp;&nbsp;&nbsp;thiophosphoric acid</option>
	<option value="188">&nbsp;&nbsp;&nbsp;thiophosphoric acid ester</option>
	<option value="189">&nbsp;&nbsp;&nbsp;thiophosphoric acid halide</option>
	<option value="190">&nbsp;&nbsp;&nbsp;thiophosphoric acid amide</option>

	<option value="191">phosphonic acid derivative (general)</option>
	<option value="192">&nbsp;&nbsp;&nbsp;phosphonic acid</option>
	<option value="193">&nbsp;&nbsp;&nbsp;phosphonic acid ester</option>

	<option value="194">phosphine</option>
	<option value="195">phosphinoxide</option>

	<option value="196">boronic acid derivative (general)</option>
	<option value="197">&nbsp;&nbsp;&nbsp;boronic acid</option>
	<option value="198">&nbsp;&nbsp;&nbsp;boronic acid ester</option>

	<option value="61">halogen compound (general)</option>
	<option value="62">&nbsp;&nbsp;&nbsp;alkyl halide</option>
	<option value="67">&nbsp;&nbsp;&nbsp;aryl halide</option>
	<option value="68">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;aryl fluoride</option>
	<option value="63">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;alkyl fluoride</option>
	<option value="69">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;aryl chloride</option>
	<option value="64">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;alkyl chloride</option>
	<option value="70">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;aryl bromide</option>
	<option value="65">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;alkyl bromide</option>
	<option value="71">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;aryl iodide</option>
	<option value="66">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;alkyl iodide</option>
</select><br>
<input type="hidden" name="action" value="search">
<input type="hidden" name="db" value="<?php echo "$dbstr"?>">
<input type="Submit" name="Submit" value="Search">
<input type="Reset" name="Reset" value="Reset"><p></p>
<?php
if ($rxnmodes == TRUE) {
echo "<small>There is at least one <b>reaction data</b> collection selected. Your selection of ";
echo "functional groups can be interpreted as follows:</small><br>\n";
echo "<input type=\"radio\" name=\"mode\" value=\"1\">present in any reactant<br>\n";
echo "<input type=\"radio\" name=\"mode\" value=\"2\" checked>present in any product<br>\n";
echo "<input type=\"radio\" name=\"mode\" value=\"3\">lost during the reaction<br>\n";
echo "<input type=\"radio\" name=\"mode\" value=\"4\">created during the reaction<br>\n";
$rxnh3 = "/reactions";
} else { $rxnh3 = ""; }
echo "</form>\n";

echo "<hr />\n";
echo "<h3>Found structures$rxnh3:</h3>\n";
echo "<table width=\"100%\">\n";

$hits_s    = 0;  // number of hit structures
$hits_r    = 0;  // number of hit reactions
$hitlist_s = "";
$hitlist_r = "";

if ($action=="search") {
  $ncodes = count($checkbox);
  if ($ncodes > $max_selections) {
    $ncodes = $max_selections;
    echo "ATTENTION: search is limited to $max_selections simultaneous selections!<br>\n";
  }

  if ($ncodes < 1) {
    echo "Nothing selected... <br>\n</body>\n</html>\n";
    exit;
  }

  $fgbits  = '0000000000000000000000000000000000000000000000000000000000000000';   // 64
  $fgbits .= '0000000000000000000000000000000000000000000000000000000000000000';   // 128
  $fgbits .= '0000000000000000000000000000000000000000000000000000000000000000';   // 192
  $fgbits .= '0000000000000000000000000000000000000000000000000000000000000000';   // 256

  for ($n = 0; $n < $ncodes; $n++) {
    $fgnum = $checkbox[$n];
    $fgbits[($fgnum - 1)] = '1';
  }

  $fgdec_list = array();
  $rfgdec_list = array();
  for ($n = 0; $n < 8; $n++) {
    $str32 = substr($fgbits, (32 * $n), 32);
    $fgdec = 0+0;
    $fgdec = $fgdec+0;
    $fgincr = 0+0;
    $fgincr = $fgincr+0;
    for ($i = 0; $i < 32; $i++) {
      if (substr($str32,$i,1) == '1') {
        $fgincr = 1 << $i;
        if ($fgincr < 0) {   // v5.01; fixes PHP problem with signed/unsigned integers
          $fgincr = 2147483648;
        }
        $fgdec = $fgdec + $fgincr +0;
      }
    }
    $fgdec_list[$n] = $fgdec;
  }

  $n_structurestotal = 0;
  $nhitstotal = 0;
  $nhits = 0;
  $hits = 0;

  foreach ($dba as $db_id) {
    $dbprefix      = "db" . $db_id . "_";
    $molstructable = $prefix . $dbprefix . $molstrucsuffix;
    $moldatatable  = $prefix . $dbprefix . $moldatasuffix;
    $molstattable  = $prefix . $dbprefix . $molstatsuffix;
    $molcfptable   = $prefix . $dbprefix . $molcfpsuffix;
    $molfgbtable   = $prefix . $dbprefix . $molfgbsuffix;
    $pic2dtable    = $prefix . $dbprefix . $pic2dsuffix;
    #if ($usemem == 'T') {
    #  $molstattable  = $molstattable . $memsuffix;
    #  $molcfptable   = $molcfptable  . $memsuffix;
    #}
    $rxnstructable = $prefix . $dbprefix . $rxnstrucsuffix;
    $rxndatatable  = $prefix . $dbprefix . $rxndatasuffix;
    $rxnfgbtable   = $prefix . $dbprefix . $rxnfgbsuffix;
    
    $mydb = $mydb = get_dbproperties($db_id);
    $dbtype       = $mydb['type'];
    $dbname       = $mydb['name'];
    $digits       = $mydb['digits'];
    $subdirdigits = $mydb['subdirdigits'];
    
    #mysql_free_result($result01);
    $time_start = getmicrotime();  
    
    if ($dbtype == 1) {
      if (!isset($digits) || (is_numeric($digits) == false)) { $digits = 8; }
      if (!isset($subdirdigits) || (is_numeric($subdirdigits) == false)) { $subdirdigits = 0; }
      if ($subdirdigits < 0) { $subdirdigits = 0; }
      if ($subdirdigits > ($digits - 1)) { $subdirdigits = $digits - 1; }
  
      $qstr1 = "SELECT mol_id FROM $molfgbtable WHERE ";
      $qstr2 = '';
    
      for ($n = 0; $n < 8; $n++) {
        $fgdec = intval(trim($fgdec_list[$n]));
        $qnum = $n + 1;
        while (strlen($qnum) < 2) { $qnum = '0' . $qnum; }
        if ($fgdec > 0) {
          if (strlen($qstr2) > 0) {
            $qstr2 .= ' AND ';
          }
          $qstr2 .= '(fg' . $qnum . ' & ' . $fgdec . ' = ' . $fgdec . ')';
        }
      }
    
      if ($qstr2 == '') {
        echo "Nothing selected.... <br>\n</body>\n</html>\n";
        exit;
      }
    
      $limit = $maxhits + 1;
      $qstr = $qstr1 . $qstr2 . ' LIMIT ' . $limit;
    
      $result = mysql_query($qstr)
        or die("Query failed (#1b1)!");    
      $hits = 0;
    
      $nhits = mysql_num_rows($result);
      $nhitstotal = $nhitstotal + $nhits;
    
      while($line=mysql_fetch_array($result)) {
        $mol_id=$line['mol_id'];
        $hits ++;
        // output of the hits, if they are not too many...
        if ( $hits > $maxhits ) {
          echo "</table>\n<hr>\n";
          echo "<p>Too many hits (>$maxhits)! Aborting....</p>\n";
          echo "</body>\n";
          echo "</html>\n";
          exit;
        }
        showHit($mol_id,"");
        if (($enable_download == "y") && ($hits_s <= $download_limit)) {
          $hits_s ++;
          if (strlen($hitlist_s) > 0) { $hitlist_s .= ","; }
          $hitlist_s .= $db_id . ":" . $mol_id;
        }
      } // end while($line)...
  
      mysql_free_result($result);
      // get total number of structures in the database
      $n_qstr = "SELECT COUNT(mol_id) AS count FROM $molfgbtable;";
      $n_result = mysql_query($n_qstr)
          or die("Could not get number of entries!"); 
      while ($n_line = mysql_fetch_array($n_result, MYSQL_ASSOC)) {
        $n_structures = $n_line["count"];
      } 
      mysql_free_result($n_result);
      $n_structurestotal = $n_structurestotal + $n_structures;
    } // end if ($dbtype == 1)

    if (($dbtype == 2) && ($enablereactions == "y")) {   // reactions
      if ($mode == 1) {
        $what = "rxn_id";
        $role_outer = "R";
        $role_inner = "";
      } elseif ($mode == 2) {
        $what = "rxn_id";
        $role_outer = "P";
        $role_inner = "";
      } elseif ($mode == 3) {
        $what = "rxn_id, fg01, fg02, fg03, fg04, fg05, fg06, fg07, fg08";
        $role_outer = "R";
        $role_inner = "P";
      } elseif ($mode == 4) {
        $what = "rxn_id, fg01, fg02, fg03, fg04, fg05, fg06, fg07, fg08";
        $role_outer = "P";
        $role_inner = "R";
      }
  
      $qstr1 = "SELECT " . $what . " FROM $rxnfgbtable WHERE role LIKE '" . $role_outer . "' AND ";
      $qstr2 = '';
    
      for ($n = 0; $n < 8; $n++) {
        $fgdec = intval(trim($fgdec_list[$n]));
        $qnum = $n + 1;
        while (strlen($qnum) < 2) { $qnum = '0' . $qnum; }
        if ($fgdec > 0) {
          if (strlen($qstr2) > 0) {
            $qstr2 .= ' AND ';
          }
          $qstr2 .= '(fg' . $qnum . ' & ' . $fgdec . ' = ' . $fgdec . ')';
        }
      }
    
      if ($qstr2 == '') {
        echo "Nothing selected.... <br>\n</body>\n</html>\n";
        exit;
      }
    
      $limit = $maxhits + 1;
      $qstr = $qstr1 . $qstr2 . ' LIMIT ' . $limit;
    
      $result = mysql_query($qstr)
        or die("Query failed (#1b2)!");    
      $hits = 0;
    
      while($line=mysql_fetch_array($result)) {
        $rxn_id = $line['rxn_id'];
        if (($mode == 1) || ($mode == 2)) {
          $hits ++;
          $nhitstotal++;
          // output of the hits, if they are not too many...
          if ( $hits > $maxhits ) {
            echo "</table>\n<hr>\n";
            echo "<p>Too many hits (>$maxhits)! Aborting....</p>\n";
            echo "</body>\n";
            echo "</html>\n";
            exit;
          }
          showHitRxn($rxn_id,"");
          if (($enable_download == "y") && ($hits_r <= $download_limit)) {
            $hits_r ++;
            if (strlen($hitlist_r) > 0) { $hitlist_r .= ","; }
            $hitlist_r .= $db_id . ":" . $rxn_id;
          }
        } else {
          $rfgdec_list[0] = $line['fg01'];
          $rfgdec_list[1] = $line['fg02'];
          $rfgdec_list[2] = $line['fg03'];
          $rfgdec_list[3] = $line['fg04'];
          $rfgdec_list[4] = $line['fg05'];
          $rfgdec_list[5] = $line['fg06'];
          $rfgdec_list[6] = $line['fg07'];
          $rfgdec_list[7] = $line['fg08'];
          $iqstr1 = "SELECT rxn_id FROM " . $rxnfgbtable . " WHERE rxn_id = " . $rxn_id;
          $iqstr1 .= " AND role LIKE '" . $role_inner . "' AND ";
          $iqstr2 = "";
          for ($ir = 0; $ir < 8; $ir++) {
            $fnum = $ir + 1;
            while (strlen($fnum) < 2) { $fnum = "0" . $fnum; }
            $fid = "fg" . $fnum;
            if ($fgdec_list[$ir] > 0) {
              if (strlen($iqstr2) > 0) { $iqstr2 .= " AND "; }
              $iqstr2 .= "((" . $rfgdec_list[$ir] . " ^ " . $fid . ") & " . $fgdec_list[$ir] . " = " . $fgdec_list[$ir] . ")";
            }
          }
          $iqstr = $iqstr1 . $iqstr2;
          //echo "$iqstr<br>\n";
          $iresult = mysql_query($iqstr)
            or die("Query failed (#1b2i)!");
          while($iline = mysql_fetch_array($iresult)) {
            $rxn_id = $iline['rxn_id'];
            $hits ++;
            $nhitstotal++;
            // output of the hits, if they are not too many...
            if ( $hits > $maxhits ) {
              echo "</table>\n<hr>\n";
              echo "<p>Too many hits (>$maxhits)! Aborting....</p>\n";
              echo "</body>\n";
              echo "</html>\n";
              exit;
            }
            showHitRxn($rxn_id,"");
            if (($enable_download == "y") && ($hits_r <= $download_limit)) {
              $hits_r ++;
              if (strlen($hitlist_r) > 0) { $hitlist_r .= ","; }
              $hitlist_r .= $db_id . ":" . $rxn_id;
            }
          }   // end while($iline)...
          mysql_free_result($iresult);
        }
      } // end while($line)...
  
      mysql_free_result($result);
      // get total number of structures in the database
      $n_qstr = "SELECT COUNT(rxn_id) AS count FROM $rxnfgbtable WHERE role LIKE 'P';";
      $n_result = mysql_query($n_qstr)
          or die("Could not get number of entries!"); 
      while ($n_line = mysql_fetch_array($n_result, MYSQL_ASSOC)) {
        $n_structures = $n_line["count"];
      } 
      mysql_free_result($n_result);
      $n_structurestotal = $n_structurestotal + $n_structures;
    } // end if ($dbtype == 2)



  }  // foreach

  echo "</table>\n<hr>\n";
  $time_end = getmicrotime();  

  if (($enable_download == "y") && ($hits_s > 0)) {
    echo "<form action=\"hits2sdf.php\" method=\"post\">\n";
    echo "<input type=\"hidden\" name=\"hits\" value=\"$hitlist_s\">\n";
    echo "<input type=\"Submit\" name=\"download\" value=\"Download\"> hit structures (max. $download_limit) as SD file<br>\n";
    echo "</form>\n";
  }
  if (($enable_download == "y") && ($hits_r > 0)) {
    echo "<form action=\"hits2rdf.php\" method=\"post\">\n";
    echo "<input type=\"hidden\" name=\"hits\" value=\"$hitlist_r\">\n";
    echo "<input type=\"Submit\" name=\"download\" value=\"Download\"> hit reactions (max. $download_limit) as RD file<br>\n";
    echo "</form>\n";
  }

  print "<p><small>number of hits: <b>$nhitstotal</b><br>\n";
  $time = $time_end - $time_start;
  print "number of analyzed entries in selected data collection(s): $n_structurestotal <br>\n";
  printf("time used for query: %2.3f seconds </small></p>\n", $time);

}

echo "\n";
if ($enable_prefs == "y") {
  mkprefscript();
}
echo "</body>\n";
echo "</html>\n";
?>
