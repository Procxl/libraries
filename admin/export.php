<?php
// admin/export.php      Norbert Haider, University of Vienna, 2012-2014
// part of MolDB6        last change: 2014-08-18

/**
 * @file admin/export.php
 * @author Norbert Haider
 *
 * Script exports structures/reactions+data as SDF/RDF files
 * 
 */

// default settings (may be overridden by configuration files)
$download_limit       = 100;  # maximum number of entries we allow to export in one file
$include_empty_fields = 0;     # 0 or 1
$include_id           = 1;     # 0,1,2   0 = no id number, 1 = mol_id, 2 = hit number
$sitename       = "Sristi Biosciences";
$cssfilename    = "moldb.css";

$myname = $_SERVER['PHP_SELF'];
@include("moldb6conf.php");    // if moldb6conf.php is in the PHP include path
@include("../moldb6conf.php"); // if moldb6conf.php is where it should *not* be...
@include("moldb6uiconf.php");    // if moldb6conf.php is in the PHP include path
@include("../moldb6uiconf.php"); // if moldb6conf.php is where it should *not* be...
require_once("../functions.php");
require_once("dbfunct.php");

/**
 * Debug level
 * - 0:            use correct MIME type (chemical/x-mdl-sdfile or chemical/x-mdl-rdfile)
 * - 1:            use text/plain as Content-Type
 */
$debug = 0;

// here we can override any settings made in the configuration file(s):
$download_limit       = 1000000;  # maximum number of entries we allow to export in one file

if (config_quickcheck() > 0) { die(); }
set_charset($charset);

if (!isset($sitename) || ($sitename == "")) {
  $sitename = "Sristi Biosciences";
}

@$db_id    = $_REQUEST['db'];
@$action   = $_POST['action'];

$link = mysql_pconnect($hostname,"$rw_user", "$rw_password")
  or die("Could not connect to database server!");
mysql_select_db($database)
  or die("Could not select database!");    
mysql_query("SET NAMES $mysql_charset");

$db_id = check_db($db_id);
if ($db_id < 0) {
  $db_id = $default_db;
  $db_id = check_db_all($db_id);
  if ($db_id < 0) {
    $db_id = get_lowestdbid();
  }
}

$mydb = get_dbproperties($db_id);
$dbtype = $mydb['type'];

$ip = $_SERVER['REMOTE_ADDR'];
$trusted = is_trustedIP($ip);
if ($trusted == false) {
  $trusted = is_db_trustedIP($db_id,$ip);   // try if IP is trusted for this db
}

$dbprefix      = $prefix . "db" . $db_id . "_";
$moldatatable  = $dbprefix . $moldatasuffix;
$molstructable = $dbprefix . $molstrucsuffix;
$rxnstructable = $dbprefix . $rxnstrucsuffix;
$rxndatatable  = $dbprefix . $rxndatasuffix;

$mydb   = get_dbproperties($db_id);
$db     = $mydb['db_id'];
$access = $mydb['access'];
$usemem = $mydb['usemem'];
$dbtype = $mydb['type'];

if ($dbtype == 1) {
  $structable = $molstructable;
  $datatable = $moldatatable;
  $idname = "mol_id";
  $namename = "mol_name";
} elseif ($dbtype == 2) {
  $structable = $rxnstructable;
  $datatable = $rxndatatable;
  $idname = "rxn_id";
  $namename = "rxn_name";
}

if (($access < 5) && ($trusted == FALSE)) {
  echo "Your client IP is not authorized to perform the requested operation!<br />\n";
  //echo "<p /><a href=\"$myname?db=$db_id\">Continue</a>\n";
  echo "</body></html>\n";
  die();
}

$result = mysql_query("SELECT COUNT($idname) AS itemcount FROM $structable")
  or die("Query failed! (export #1)");
$line = mysql_fetch_row($result);  
mysql_free_result($result);
$itemcount = $line[0];

$result = mysql_query("SELECT MAX($idname) AS maxitem FROM $structable")
  or die("Query failed! (export #2)");
$line = mysql_fetch_row($result);  
mysql_free_result($result);
$max_id = $line[0];


$afield  = array();
$nfields = 0;

$dqstr = "SHOW FULL COLUMNS FROM $datatable";
$dresult = mysql_query($dqstr)
  or die("Query failed! (export #3)");
while ($dline = mysql_fetch_array($dresult, MYSQL_ASSOC)) {
  $field   = $dline["Field"];
  $label = $field;
  $sdflabel = $field;
  $type    = $dline["Type"];
  $comment = $dline["Comment"];
  if ($field != $idname) {
    $nfields++;
    if (strlen($comment)>4) {
      $pos = strpos($comment, ">>>>");
      if ($pos !== false) {
        if ($pos == 0) {
          $comment = str_replace(">>>>","",$comment);
          $acomment = explode("<",$comment);
          $label  = $acomment[0];
          //$nformat = $acomment[1];
          $sdflabel   = $acomment[2];
          //$searchmode = $acomment[3];
        }
      }
    }
    if (strlen($sdflabel) == 0) { $sdflabel = $field; }
    $afield[($nfields-1)][0] = $sdflabel;
    $afield[($nfields-1)][1] = $field;
    $afield[($nfields-1)][2] = 0;   // 1 or 0 (use or not)
  }  // if...
}  // while


if (($db_id > 0) && ($action == "export")) {
  if ($debug == 0) {
    if ($dbtype == 1) { 
      header("Content-Type: chemical/x-mdl-sdfile"); 
      header("Content-Disposition: attachment; filename=export.sdf");
    }
    if ($dbtype == 2) { 
      header("Content-Type: chemical/x-mdl-rdfile"); 
      header("Content-Disposition: attachment; filename=export.rdf");
    }
  } else {
    header("Content-Type: text/plain");
    header("Content-Disposition: filename=export.txt");
  }

  for ($i = 0; $i < $nfields; $i++) {
    $fname = $afield[$i][1];
    $cbval = $_POST["$fname"];
    if ($cbval == "y") { $afield[$i][2] = 1; }
  }
  
  $reclist    = $_POST['rec'];
  $wherestr = "";
  $rmin = 0;
  $rmax = 0;
  if (strlen($reclist) > 0) {
    $rec_a = explode(',',$reclist);
    $n_el = count($rec_a);
    for ($i = 0; $i < $n_el; $i++) {
      $rline = $rec_a[$i];
      $pos = strpos($rline,'-');
      if ($pos === FALSE) {     // single ID number
        $rmin = 0;
        if (is_numeric($rline)) {
          $rmin = intval($rline);
          if (($rmin > 0) && ($rmin <= $max_id)) {
            // this is a valid ID number
            if (strlen($wherestr) == 0) { $wherestr .= "WHERE "; } else { $wherestr .= " OR "; }
            $wherestr .= "($idname = $rmin)";
          }
        }
      } else {                  // ID number range
        $rline_a = explode('-',$rline);
        $lstr = $rline_a[0];
        $rstr = $rline_a[1];
        $rmin = 0; $rmax = 0;
        if (is_numeric($lstr) || is_numeric($rstr)) {  // at least one of them must be numeric
          $rmin = intval($lstr);
          $rmax = intval($rstr);
          if ($lstr == "") { $rmin = 1; }
          if ($rstr == "") { $rmax = $max_id; }
          if ($rmin > $rmax) {
            $rtmp = $rmin; $rmin = $rmax; $rmax = $rtmp;   // swap values if necessary
          }
          if (strlen($wherestr) == 0) { $wherestr .= "WHERE "; } else { $wherestr .= " OR "; }
          $wherestr .= "(($idname >= $rmin) AND ($idname <= $rmax))";
        }
      }
    }  // for ...
  }  // if strlen(reclist)...

  $hits    = 0;
  $offsetcount = 0;
  $sqlbs = 100;
  $qstr = "SELECT $idname, struc from $structable $wherestr";
  
  if ($dbtype == 2) { 
    echo "\$RDFILE 1\n";
    echo "\$DATM ";
    echo date(DATE_RFC2822);
    echo "\n";
  }
  do {
    $offset  = $offsetcount * $sqlbs;
    $qstrlim = $qstr . " LIMIT $offset, $sqlbs";
    $result  = mysql_query($qstrlim)
      or die("Query failed! (export #4)");    
    $rows  = mysql_num_rows($result);     // number of candidate structures
    while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
      $item_id = $line["$idname"];
      $struc   = $line["struc"];
      $hits++;
      if ($hits > $download_limit) {
        echo " ======== file truncated (download limit exceeded)\n";
        die();
      }
      $struc = chop($struc);
      if ($dbtype == 2) { echo "\$RFMT \$RIREG $item_id\n"; }
      echo "$struc\n";
      if ($dbtype == 1) { echo "\n"; }
      $idstr = "";
      if ($include_id == 1) { $idstr = $item_id; }
      if ($include_id == 2) { $idstr = $hits; }
      $dataval = array();   //preliminary....
      $dq = "";
      for ($i = 0; $i < $nfields; $i++) {
        if ($i > 0) { $dq .= ","; }
        $dq .= $afield[$i][1];
      }
      $qstr2 = "SELECT $dq FROM $datatable WHERE $idname = $item_id";
      #echo "$qstr2<br>";
      $result2 = mysql_query($qstr2)
        or die("Query failed! (export #5)");
      $y = mysql_num_fields($result2);
      while ($dline = mysql_fetch_array($result2, MYSQL_BOTH)) {
        for ($x = 0; $x < $y; $x++) {
          $fieldname = mysql_field_name($result2, $x);
          //$fieldtype = mysql_field_type($result2, $x);
          $use_field = field_usable($fieldname);;
          if (($fieldname != $idname) && (($dline[$fieldname] != "") || ($include_empty_fields == 1))) {
            if ($use_field == TRUE) {
              $dfname = get_dfname($fieldname);
              $dfval = $dline[$fieldname];
              if ($dbtype == 1) { write_sdfield($dfname,$dfval,$idstr); }
              if ($dbtype == 2) { write_rdfield($dfname,$dfval); }
            }
          }
        }
        if ($dbtype == 1) { echo "\$\$\$\$\n"; }
      } 
      mysql_free_result($result2);
    }  // while ...
    mysql_free_result($result);
    $offsetcount++;
  } while ($rows > 0);
  die();
}



?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=<?php echo "$html_charset"; ?>">
<meta name="author" content="Norbert Haider, University of Vienna">
<title><?php echo "$sitename (administration/export page)"; ?></title>
<?php insert_style("../$cssfilename"); ?>
</head>
<body>
<?php
echo "<h1>$sitename: data export</h1>\n";
echo "On this page, you can select MolDB6 entries for SDF/RDF export.<br />\n";
echo "<hr />\n";
echo "<small>selected data collection: $db_id </small><p />\n";
echo "<p><small>current entries: $itemcount, highest entry no.: $max_id</small></p>\n";
echo "<h3>Entries to be exported:</h3>\n";

echo "<form method=\"post\" action=\"$myname\">\n";
echo "<input type=\"text\" size=\"90\" name=\"rec\">\n";
echo "<input type=\"hidden\" name=\"action\" value=\"export\">\n";
echo "<br />\n<input type=\"hidden\" name=\"db\" value=\"$db_id\">\n";
echo "<small>A comma-separated list of ID numbers or number ranges (e.g., 1-12, 15,23,35-43)</small>\n<br>\n<br>\n";
echo "<input type=\"Submit\" name=\"Submit\" value=\"Export\">";

echo "<br>\n<ul>\n";
echo "available data fields:<br>\n";
for ($i = 0; $i < $nfields; $i++) {
  $fname = $afield[$i][1];
  echo "<input type=\"checkbox\" name=\"$fname\" value=\"y\" checked> $fname<br>\n";
}
echo "</ul>\n";
echo "</form><p />\n";

echo "Back to <a href=\"./?db=$db_id\">database administration</a>\n";

function get_dfname($fname) {
  global $nfields;
  global $afield;
  $r = $fname;
  for ($i = 0; $i < $nfields; $i++) {
    if ($afield[$i][1] == $fname) { $r = $afield[$i][0]; }
  }
  if ($r == "") { $r = $fname; }
  return($r);
}

function field_usable($fname) {
  global $nfields;
  global $afield;
  $r = FALSE;
  for ($i = 0; $i < $nfields; $i++) {
    #if ($afield[$i][1] == $fname) && ($afield[$i][2] == 1) { $r = TRUE; }
    if ($afield[$i][1] == $fname) {
      if ($afield[$i][2] == 1) { $r = TRUE; }
    }
  }
  return($r);
}


function write_sdfield($dfname,$dfval,$id) {
  echo "> <${dfname}>";
  if ($id !='') { echo " (${id})"; }
  echo "\n";
  echo "$dfval\n";
  if (strlen($dfval) > 0) { echo "\n"; }
}

function write_rdfield($dfname,$dfval) {
  echo "\$DTYPE $dfname\n";
  echo "\$DATUM $dfval\n";
  #if (strlen($dfval) > 0) { echo "\n"; }
}

?>

<br />
<hr />
<br />
</body>
</html>
