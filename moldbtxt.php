<?php 
// moldbtxt.php     Norbert Haider, University of Vienna, 2005-2014
// part of MolDB6   last change: 2014-08-18

/**
 * @file moldbtxt.php
 * @author Norbert Haider
 * 
 * This script performs text searches in structure and reaction data
 * collections. By default, only the mol_name and rxn_name fields are
 * searches, optionally the search can be extended to all fields which
 * have been flagged "includable in search" in the administration tool.
 * As of version MolDB5.03, an advanced text search is included which
 * allows to enter search terms for each searchable field (this works
 * only if a single data collection is selected). As of 2012-07-02,
 * also ID numbers (comma-separated) or ID number ranges can be
 * retrieved (must be enabled by setting $enable_idsearch to "y".
 */

/**
 * set default search mode:
 * 1 = simple search, mol_name/rxn_name only
 * 2 = simple search, mol_name/rxn_name + all searchable fields
 * 3 = advanced text search (works only with 1 selected data collection!)
 * 4 = advanced text or ID number search (works only with 1 selected data collection!)
 */
$default_mode  = 1;

/**
 * set auto_fallback (0 or 1):
 * 1 automatically switches to simple search, if more than
 * one data collection is selected
 */
$auto_fallback = 1;

/**
 * maximum number of hits we want to allow
 */
$maxhits     = 500;

/**
 * maximum number of characters we want to allow
 */
$maxtextlen  = 80;

/**
 * enable or disable download of hit structures ("y" or "n"),
 * set the maximum number of downloaded structures; these defaults
 * can be overridden by the settings in moldb6conf.php
 */
$enable_download = "n";
$download_limit  = 100;

/**
 * enable or disable search by ID number ("y" or "n"),
 * can be overridden by the settings in moldb6conf.php
 */
$enable_idsearch = "y";

/**
 * Debug level
 * - 0:            remain silent, higher values: be more verbose
 * - odd numbers:  output as HTML comments, 
 * - even numbers: output as clear-text messages
 */
$debug = 1;

$myname = $_SERVER['PHP_SELF'];
require_once("functions.php");

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
    <div class="col-md-8 col-xm-8">
      <?php show_header($myname , $dbstr); ?>
    </div>
  </div>
</div>

<?php

@$action = $_POST['action'];
@$mode = $_REQUEST['mode'];
if (($mode != "1") && ($mode != "2") && ($mode != "3")  && ($mode != "4")) { $mode = $default_mode; }
@$textinput = substr($_POST['name'],0,$maxtextlen);
@$idinput = substr($_POST['id'],0,$maxtextlen);

//show_header($myname,$dbstr);

$ndb = get_numdb_readable();
if ($ndb == 0) { 
  echo "<h3>no data collection available</h3><br>\n</body>\n</html>\n"; 
  die();
}

if (($mode > 2) && ($ndbsel > 1)) {
  if ($auto_fallback > 0) {
    $mode = $auto_fallback;  // usually 1 (may be also 2)
  } elseif ($auto_fallback == 0) {
    echo "<h3>If you want to use advanced text search, please select only one data collection.</h3><br>\n";
    echo "<a href=\"index.php?db=$dbstr\">Continue</a>\n</body>\n</html>\n";
    die();
  }
}

$hits_s    = 0;  // number of hit structures
$hits_r    = 0;  // number of hit reactions
$hitlist_s = "";
$hitlist_r = "";

if (($mode == 1) || ($mode == 2)) {
  echo "<h2><span style=\"color:Purple\">${sitename}</span></h2>\n";
  echo "<p>Enter search term (chemical name or name fragment):</p>\n";
  
  echo "<form method=\"post\" action=\"$myname\">\n";
  echo "<input type=\"text\" size=\"40\" name=\"name\">\n";
  echo "<input type=\"hidden\" name=\"action\" value=\"search\">\n";
  echo "<input type=\"Submit\" name=\"Submit\" value=\"Search\">";
  if ($ndbsel == 1) {
    echo "&nbsp;&nbsp;<small><a href=\"${myname}?db=${dbstr}&mode=3\">Advanced search</a></small>";
  }
  echo "<br>\n<input type=\"hidden\" name=\"db\" value=\"$dbstr\">\n";
  echo "<input type=\"radio\" name=\"mode\" value=\"1\"";
  if ($mode == 1) { echo " checked"; }
  echo ">only name\n";
  echo "<input type=\"radio\" name=\"mode\" value=\"2\"";
  if ($mode == 2) { echo " checked"; }
  echo ">include other searchable fields\n";
  echo "</form>\n";
  
  echo "<hr />\n";
  echo "<h3>Found entries:</h3>\n";
  echo "<table width=\"100%\">\n";
  
  if (($action=="search") && (strlen($textinput) > 2)) {
    $time_start = getmicrotime();  
    if (get_magic_quotes_gpc()) {
      $textinput = stripslashes($textinput);
    }     
    $nhitstotal = 0;
  
    foreach ($dba as $db_id) {
      $dbprefix       = "db" . $db_id . "_";
      $molstructable = $prefix . $dbprefix . $molstrucsuffix;
      $moldatatable  = $prefix . $dbprefix . $moldatasuffix;
      $molstattable  = $prefix . $dbprefix . $molstatsuffix;
      $molcfptable   = $prefix . $dbprefix . $molcfpsuffix;
      $pic2dtable    = $prefix . $dbprefix . $pic2dsuffix;
      $rxnstructable = $prefix . $dbprefix . $rxnstrucsuffix;
      $rxndatatable  = $prefix . $dbprefix . $rxndatasuffix;
      #if ($usemem == 'T') {
      #  $molstattable  = $molstattable . $memsuffix;
      #  $molcfptable   = $molcfptable  . $memsuffix;
      #}
      if ($enablereactions == "y") { $onlysd = ""; } else { $onlysd = " AND (type = 1) "; }
      $qstr01 = "SELECT * FROM $metatable WHERE (db_id = $db_id) $onlysd";
      
      $result01 = mysql_query($qstr01)
        or die("Query failed (#1a)!");    
      while($line01=mysql_fetch_array($result01)) {
        $db_id        = $line01['db_id'];
        $dbtype       = $line01['type'];
        $dbname       = $line01['name'];
        $digits       = $line01['digits'];
        $subdirdigits = $line01['subdirdigits'];
      }
      mysql_free_result($result01);
      if ($dbtype == 1) {
        $idname = "mol_id";
        $namename = "mol_name";
        $datatable = $moldatatable;
      } elseif ($dbtype == 2) {
        $idname = "rxn_id";
        $namename = "rxn_name";
        $datatable = $rxndatatable;
      }
      
      if (!isset($digits) || (is_numeric($digits) == false)) { $digits = 8; }
      if (!isset($subdirdigits) || (is_numeric($subdirdigits) == false)) { $subdirdigits = 0; }
      if ($subdirdigits < 0) { $subdirdigits = 0; }
      if ($subdirdigits > ($digits - 1)) { $subdirdigits = $digits - 1; }
  
      $textinput  = str_replace("?","_",$textinput);
      $textinput  = str_replace("*","%",$textinput);
      $searchtext = str_replace(";"," ",$textinput);
      $searchtext = "%" . mysql_real_escape_string($searchtext) . "%";
      //$searchtext = "%" . mysql_escape_string($searchtext) . "%";  // use this for older PHP versions

      $addstr = "";
      if ($mode == 2) {   // get other searchable fields
        $qstr02 = "SHOW FULL COLUMNS FROM $datatable";
        $result02 = mysql_query($qstr02)
          or die("Query failed (#1a)!");    
        while($line02=mysql_fetch_array($result02)) {
          $fieldname = $line02["Field"];
          $fieldtype = $line02["Type"];
          $comment   = $line02["Comment"];
          $pos = strpos($comment, ">>>>");
          if ($pos !== false) {
            $fieldprop = getfieldprop($comment);
            $searchabletype = is_stringtype($fieldtype);  // should be checked!  (only char, varchar, text, enum, set)
            if (($fieldprop["searchmode"] == 1) && ($searchabletype ==1)) {
              $addstr .= " OR (" . $fieldname . " LIKE \"$searchtext\")";
            }
          }
        }
        mysql_free_result($result02);
      }  // if ($mode == 2)
  
      $limit = $maxhits + 1;
      $qstr = "SELECT $idname FROM $datatable WHERE ($namename LIKE \"$searchtext\") $addstr GROUP BY $idname LIMIT $limit";
    
      //echo "$qstr <br>\n";
    
      $result = mysql_query($qstr)
        or die("Query failed (#1b)!");    
      $hits = 0;
    
      $nhits = mysql_num_rows($result);
      $nhitstotal = $nhitstotal + $nhits; 
    
      while($line=mysql_fetch_array($result)) {
        $item_id = $line[$idname];
        $hits ++;
        // output of the hits, if they are not too many...
        if ( $hits > $maxhits ) {
          echo "</table>\n";
          echo "<p>Too many hits (>$maxhits)! Aborting....</p>\n";
          echo "</body>\n";
          echo "</html>\n";
          exit;
        }
        if ($dbtype == 1) { 
          showHit($item_id,""); 
          if (($enable_download == "y") && ($hits_s <= $download_limit)) {
            $hits_s ++;
            if (strlen($hitlist_s) > 0) { $hitlist_s .= ","; }
            $hitlist_s .= $db_id . ":" . $item_id;
          }
        }
        if ($dbtype == 2) { 
          showHitRxn($item_id,""); 
          if (($enable_download == "y") && ($hits_r <= $download_limit)) {
            $hits_r ++;
            if (strlen($hitlist_r) > 0) { $hitlist_r .= ","; }
            $hitlist_r .= $db_id . ":" . $item_id;
          }
        }
      } // end while($line)...
      mysql_free_result($result);
  
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
    printf("time used for query: %2.3f seconds </small></p>\n", $time);
  } else {
     if (strlen($textinput) <= 2) { echo "</table>\n"; }
  }
}  // if $mode == 1 or 2

if ($mode >= 3) {
  $time_start    = getmicrotime();  
  $dbprefix      = "db" . $db_id . "_";
  $molstructable = $prefix . $dbprefix . $molstrucsuffix;
  $moldatatable  = $prefix . $dbprefix . $moldatasuffix;
  $pic2dtable    = $prefix . $dbprefix . $pic2dsuffix;
  $rxnstructable = $prefix . $dbprefix . $rxnstrucsuffix;
  $rxndatatable  = $prefix . $dbprefix . $rxndatasuffix;

  if ($enablereactions == "y") { $onlysd = ""; } else { $onlysd = " AND (type = 1) "; }
  $qstr01 = "SELECT * FROM $metatable WHERE (db_id = $db_id) $onlysd";
  
  $result01 = mysql_query($qstr01)
    or die("Query failed (#1a)!");    
  while($line01=mysql_fetch_array($result01)) {
    $db_id        = $line01['db_id'];
    $dbtype       = $line01['type'];
    $dbname       = $line01['name'];
    $digits       = $line01['digits'];
    $subdirdigits = $line01['subdirdigits'];
  }
  mysql_free_result($result01);
  if ($dbtype == 1) {
    $idname = "mol_id";
    $namename = "mol_name";
    $datatable = $moldatatable;
  } elseif ($dbtype == 2) {
    $idname = "rxn_id";
    $namename = "rxn_name";
    $datatable = $rxndatatable;
  }

  $qstr01 = "SELECT COUNT($idname) AS itemcount, MIN($idname) AS minitem, MAX($idname) AS maxitem FROM $datatable";
  $result01 = mysql_query($qstr01)
    or die("Query failed (#1b)!");    
  while($line01=mysql_fetch_array($result01)) {
    $itemcount     = $line01['itemcount'];
    $min_id        = $line01['minitem'];
    $max_id        = $line01['maxitem'];
  }
  mysql_free_result($result01);

  $what = "text";
  if ($enable_idsearch == "y") { $what = "text/ID"; }
  echo "<h1>${sitename}: advanced $what search</h1>\n";

  if ($action !== "search") {
    echo "<p>Enter one search term per field, using the symbols <b>?</b> (any single character) or <b>*</b> (any multiple characters) for substring searching:</p>\n";

    echo "<form name=\"searchform\" action=\"$myname\" method=post>\n";
    echo "<table class=\"highlight\">\n";
    echo "<tr align=\"left\"><th>Field:</th><th></th></tr>\n";
    $i = 0;
    $qstr = "SHOW FULL COLUMNS FROM $datatable";
    $result = mysql_query($qstr)
      or die("Query failed! (searchform, text search mode 3)");
    $nfields = 0;
    while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
      $field   = $line["Field"];
      $type    = $line["Type"];
      $comment = $line["Comment"];
  
      $i ++;
      $ifmt = "line";
      $searchmode = 0;
      if (strlen($comment)>4) {
        $pos = strpos($comment, ">>>>");
        if ($pos !== false) {
          if ($pos == 0) {
            $comment = str_replace(">>>>","",$comment);
            $acomment = explode("<",$comment);
            $label  = $acomment[0];
            $nformat = $acomment[1];
            if ($nformat == 0) { $ifmt = "line"; }
            if ($nformat == 1) { $ifmt = "line"; }
            if ($nformat == 2) { $ifmt = "text"; }
            if ($nformat == 3) { $ifmt = "line"; }
            $searchmode = $acomment[3];
            
          }
        }
      }
      if(stristr($type, 'text') != FALSE) { $ifmt = "text"; }
      if(stristr($type, 'enum') != FALSE) { $ifmt = "select"; }
      if(stristr($type, 'set')  != FALSE) { $ifmt = "select"; }
      
      if ($field == $namename) { 
        if ($label == "") { $label = "Name"; }
        $searchmode = 1; 
      }
      if ($searchmode == 1) {
        $nfields ++;
        echo "<tr><td>${label}:</td><td>";
        echo "<input type=\"text\" name=\"$field\" size=\"80\">&nbsp;&nbsp;";
        echo "</td></tr>\n";
      }
    
    }  // end while 
    echo "<tr><td>&nbsp;</td><td>&nbsp;</td></tr>\n";
    echo "</table>";
    echo "<small>back to <a href=\"${myname}?db=${dbstr}&mode=1\">simple search</a></small><p></p>\n";
    if ($nfields > 1) {
      echo "find all entries containing<br>\n";
      echo "<input type=\"radio\" name=\"op\" value=\"or\" checked> any of these terms<br>\n";
      echo "<input type=\"radio\" name=\"op\" value=\"and\"> all of these terms<br>\n";
    } else {
      echo "<input type=\"hidden\" name=\"op\" value=\"and\">\n";
    }
    echo "<input type=\"hidden\" name=\"action\" value=\"search\">\n";
    echo "<input type=\"hidden\" name=\"db\" value=\"$db_id\">\n";
    echo "<input type=\"hidden\" name=\"mode\" value=\"3\">\n";
    echo "<input type=\"Submit\" name=\"select\" value=\"&nbsp;&nbsp;Search&nbsp;&nbsp;\">\n";
    echo "</form><p />\n";
    mysql_free_result($result);

    if ($enable_idsearch == "y") {
      // input mask for ID number search
      echo "<hr>\n";
      echo "<h3>ID number search</h3>\n";   
      echo "<p>Enter an ID number or a comma-separated list of ID numbers or number ranges:</p>\n";
      echo "<p><small>current entries: $itemcount, lowest ID number: $min_id, highest ID number: $max_id</small></p>\n";
      echo "<form name=\"searchform\" action=\"$myname\" method=post>\n";
      echo "<table class=\"highlight\">\n";
      echo "<tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td></td></tr>\n";
      echo "<tr align=\"left\"><td>ID:</td><td>";
      echo "<input type=\"text\" name=\"id\" size=\"80\">&nbsp;&nbsp;";
      echo "</td></tr>";
      echo "<tr><td>&nbsp;&nbsp;</td><td></td></tr>\n";
      echo "</table>\n";
      echo "<small>back to <a href=\"${myname}?db=${dbstr}&mode=1\">simple search</a></small><p></p>\n";
      echo "<input type=\"hidden\" name=\"action\" value=\"search\">\n";
      echo "<input type=\"hidden\" name=\"db\" value=\"$db_id\">\n";
      echo "<input type=\"hidden\" name=\"mode\" value=\"4\">\n";
      echo "<input type=\"Submit\" name=\"select\" value=\"&nbsp;&nbsp;Search&nbsp;&nbsp;\">\n";
      echo "</form><p />\n";
    }

  } else {          //  if ($action != "search")

    if ($mode == 3) {
      $operator = "OR";
      $op = $_POST['op'];
      if ($op === "and") { $operator = "AND"; }
  
      // assemble the query string, field by field
    
      $query = "";
      $value = "";
        $qstr = "SHOW FULL COLUMNS FROM $datatable";
      $result = mysql_query($qstr)
        or die("Query failed! (search, text search mode 3)");
      while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
        $field   = $line["Field"];
        $type    = $line["Type"];
        $comment = $line["Comment"];
        $searchmode = 0;
        if (strlen($comment)>4) {
          $pos = strpos($comment, ">>>>");
          if ($pos !== false) {
            if ($pos == 0) {
              $comment = str_replace(">>>>","",$comment);
              $acomment = explode("<",$comment);
              $searchmode = $acomment[3];
            }
          }
        }
        if ($field == $namename) { $searchmode = 1; }
        if ($searchmode == 1) {
          $value = "";
          $value = $_POST[$field];
          $value = trim($value);
          if (strlen($value) > 0) {
            $value = str_replace("?","_",$value);
            $value = str_replace("*","%",$value);
            $value = mysql_real_escape_string($value);
            //$value = mysql_escape_string($value);  // use this for older PHP versions
            if (strlen($query) > 0) { $query .= " $operator "; }
            $query .= "($field LIKE \"$value\")";
          }
        }
      }  // end while 
  
      if (strlen($query) < 4) {
        echo "no search term entered....<br>\n";
        echo "<a href=\"${myname}?db=${dbstr}&mode=3\">New search</a><p></p>\n";
        echo "</body>\n</html>\n";
        die();
      } else {
        echo "<a href=\"${myname}?db=${dbstr}&mode=3\">New search</a><p></p>\n";
        echo "<hr />\n<h3>Found entries:</h3>\n";
  
        $limit = $maxhits + 1;
        $qstr = "SELECT $idname FROM $datatable WHERE $query GROUP BY $idname LIMIT $limit";
        #echo "$qstr<br>\n";
  
        $result = mysql_query($qstr)
          or die("Query failed (#1b)!");    
        $hits = 0;
      
        $nhits = mysql_num_rows($result);
  
        if ($nhits > 0) {
          echo "<table width=\"100%\">\n";
      
          while ($line = mysql_fetch_array($result)) {
            $item_id = $line[$idname];
            $hits ++;
            // output of the hits, if they are not too many...
            if ( $hits > $maxhits ) {
              echo "</table>\n";
              echo "<p>Too many hits (>$maxhits)! Aborting....</p>\n";
              echo "</body>\n";
              echo "</html>\n";
              exit;
            }
            if ($dbtype == 1) { 
              showHit($item_id,""); 
              if (($enable_download == "y") && ($hits_s <= $download_limit)) {
                $hits_s ++;
                if (strlen($hitlist_s) > 0) { $hitlist_s .= ","; }
                $hitlist_s .= $db_id . ":" . $item_id;
              }
            }
            if ($dbtype == 2) { 
              showHitRxn($item_id,""); 
              if (($enable_download == "y") && ($hits_r <= $download_limit)) {
                $hits_r ++;
                if (strlen($hitlist_r) > 0) { $hitlist_r .= ","; }
                $hitlist_r .= $db_id . ":" . $item_id;
              }
            }
          } // end while($line)...
          mysql_free_result($result);
  
          echo "</table>\n<hr>\n";
          echo "<a href=\"${myname}?db=${dbstr}&mode=3\">New search</a><p></p>\n";
        
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
         
          print "<p><small>number of hits: <b>$nhits</b><br>\n";
          $time = $time_end - $time_start;
          printf("time used for query: %2.3f seconds </small></p>\n", $time);
  
        }  // if $nhits > 0
      }
    }   // mode = 3

    if (($mode == 4) && ($enable_idsearch == "y")) {
      $reclist    = $idinput;
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
      //$qstr = "SELECT $idname from $datatable $wherestr";
      //echo "<pre>$qstr</pre>\n";

      if (strlen($wherestr) < 4) {
        echo "no valid ID number entered....<br>\n";
        echo "<a href=\"${myname}?db=${dbstr}&mode=4\">New search</a><p></p>\n";
        echo "</body>\n</html>\n";
        die();
      } else {
        echo "<a href=\"${myname}?db=${dbstr}&mode=3\">New search</a><p></p>\n";
        echo "<hr />\n<h3>Found entries:</h3>\n";
  
        $limit = $maxhits + 1;
        $qstr = "SELECT $idname FROM $datatable $wherestr GROUP BY $idname LIMIT $limit";
        #echo "$qstr<br>\n";
  
        $result = mysql_query($qstr)
          or die("Query failed (#1b)!");    
        $hits = 0;
      
        $nhits = mysql_num_rows($result);
  
        if ($nhits > 0) {
          echo "<table width=\"100%\">\n";
      
          while ($line = mysql_fetch_array($result)) {
            $item_id = $line[$idname];
            $hits ++;
            // output of the hits, if they are not too many...
            if ( $hits > $maxhits ) {
              echo "</table>\n";
              echo "<p>Too many hits (>$maxhits)! Aborting....</p>\n";
              echo "</body>\n";
              echo "</html>\n";
              exit;
            }
            if ($dbtype == 1) { 
              showHit($item_id,""); 
              if (($enable_download == "y") && ($hits_s <= $download_limit)) {
                $hits_s ++;
                if (strlen($hitlist_s) > 0) { $hitlist_s .= ","; }
                $hitlist_s .= $db_id . ":" . $item_id;
              }
            }
            if ($dbtype == 2) { 
              showHitRxn($item_id,""); 
              if (($enable_download == "y") && ($hits_r <= $download_limit)) {
                $hits_r ++;
                if (strlen($hitlist_r) > 0) { $hitlist_r .= ","; }
                $hitlist_r .= $db_id . ":" . $item_id;
              }
            }
          } // end while($line)...
          mysql_free_result($result);
  
          echo "</table>\n<hr>\n";
          echo "<a href=\"${myname}?db=${dbstr}&mode=3\">New search</a><p></p>\n";
        
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
         
          print "<p><small>number of hits: <b>$nhits</b><br>\n";
          $time = $time_end - $time_start;
          printf("time used for query: %2.3f seconds </small></p>\n", $time);
  
        }  // if $nhits > 0
      }
   
    }   // mode = 4

  }  // end of "else" (from: if ($action != "search") )
}  // end $mode >= 3

echo "\n";

if ($enable_prefs == "y") {
  mkprefscript();
}

echo "</body>\n";
echo "</html>\n";
?>
