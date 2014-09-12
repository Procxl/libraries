<?php
// admin/index.php    Norbert Haider, University of Vienna, 2009-2014
// part of MolDB6     last change: 2014-08-18

/**
 * @dir admin
 * @author Norbert Haider
 *
 * Scripts providing administration pages
 */
/**
 * @file admin/index.php
 * @author Norbert Haider
 * 
 * This script provides the central administration page. Users can create,
 * edit and delete data collections. From here, data input/editing can be
 * selected via the script editdata.php.
 */

// some defaults (will be overridden by config files
$sitename      = "Sristi Biosciences";
$cssfilename   = "moldb.css";

$myname = $_SERVER['PHP_SELF'];
@include("moldb6conf.php");    // if moldb6conf.php is in the PHP include path
@include("../moldb6conf.php"); // if moldb6conf.php is where it should *not* be...
@include("moldb6uiconf.php");    // if moldb6uiconf.php is in the PHP include path
@include("../moldb6uiconf.php"); // if moldb6uiconf.php is where it should *not* be...
require_once("../functions.php");
require_once("dbfunct.php");

if (config_quickcheck() > 0) { die(); }
set_charset($charset);

if (!isset($sitename) || ($sitename == "")) {
  $sitename = "Sristi Biosciences";
}

@$db_id   = $_REQUEST['db'];
@$action  = $_POST['action'];

$ostype = getostype();

$link = mysql_pconnect($hostname,"$rw_user", "$rw_password")
  or die("Could not connect to database server!");
mysql_select_db($database)
  or die("Could not select database!");    
mysql_query("SET NAMES $mysql_charset");

$db_id = check_db_all($db_id);
if ($db_id < 0) {
  $db_id = $default_db;
  $db_id = check_db_all($db_id);
  if ($db_id < 0) {
    $db_id = get_lowestdbid();
  }
}

$ip         = $_SERVER['REMOTE_ADDR'];
$trusted    = is_trustedIP($ip);            // IP of MolDB6 admin
$db_trusted = is_db_trustedIP($db_id,$ip);  // sub-admin just for this db

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=<?php echo "$html_charset"; ?>">
<meta name="author" content="Norbert Haider, University of Vienna">

<script src="../js/jquery-1.10.2.js"></script>
<script src="../js/jquery-ui-1.10.4.min.js"></script>
<script src="../js/bootstrap.min.js"></script>

<link href="../css/bootstrap.css" rel="stylesheet">
<link href="../css/font-awesome.min.css" rel="stylesheet">
<link href="../css/jquery-ui-1.10.4.min.css" rel="stylesheet">

<title><?php echo "$sitename (administration page)"; ?></title>
<?php insert_style("../$cssfilename"); ?>
</head>
<body>

<div class="container">
  <div class="row">
    <div class="col-md-12 col-xm-12">
      <?php show_header($myname,$dbstr);?>
    </div>
  </div>
</div> 

<?php
echo "<h2><span style=\"color:Purple\">$sitename</span></h2>\n";
//echo "On this page, you can add new MolDB6 data collections and edit existing ones.<br/>\n";
//echo "<hr/>\n";

if ($action == 'add') {
  if ($trusted == false) {
    echo "Your client IP is not authorized to perform the requested operation!<br/>\n";
    echo "<p/><a href=\"$myname?db=$db_id\">Continue</a>\n";
    echo "</body></html>\n";
    die();
  }
  $newname  = $_POST['name'];
  #$newname = trim($newname);
  $newname = clean_fieldstr($newname);
  if ($newname == "") {
    echo "No valid name was entered!<br/>\n";
  } else {

    if ($use_cmmmsrv == 'y') {
      /* create a TCP/IP socket */
      $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
      if ($socket < 0) {
        //echo "socket_create() failed.\nreason: " . socket_strerror ($socket) . "\n";
        echo "<!-- could not connect to cmmmsrv - reverting to checkmol/matchmol --!>\n";
        $use_cmmmsrv = "n";
      }
      $sockresult = socket_connect ($socket, $cmmmsrv_addr, $cmmmsrv_port);
      if ($sockresult < 0) {
        //echo "socket_connect() failed.<p />\n";
        echo "<!-- could not connect to cmmmsrv - reverting to checkmol/matchmol --!>\n";
        $use_cmmmsrv = "n";
      }
    }

    if ($use_cmmmsrv == 'y') {
      $a = socket_read($socket, 250, PHP_NORMAL_READ);
      //echo "the socket says: $a<br />\n";
      $pos = strpos($a,"READY");
      if ($pos === false) {
        echo "<!-- could not connect to cmmmsrv - reverting to checkmol/matchmol --!>\n";
        $use_cmmmsrv = "n";
      }
    }

    $dbname   = $_POST['name'];
    $dbtype   = $_POST['type'];
    $dbdescr  = $_POST['descr'];
    $dbaccess = intval($_POST['access']);
    $dbdigits = intval($_POST['digits']);
    $dbsubdirdigits = intval($_POST['subdirdigits']);
    $dbtrustedIP  = $_POST['trustedip'];

    $new_db = add_database($dbname,$dbtype,$dbdescr,$dbaccess,$dbdigits,$dbsubdirdigits,$dbtrustedIP,0);
    
    if ($use_cmmmsrv == 'y') {
      socket_write($socket,'#### bye');
      socket_close($socket);
    }

    if ($new_db > 0) {
      echo "added new database...<br/>\n";
    } else {
      if ($new_db == 0) {
        echo "a data collection of this name exists already!<br/>\n";
      } else {
        echo "addition of new data collection failed!<br/>\n";
      }
    }
  }
  echo "<p/><a href=\"$myname?db=$new_db\">Continue</a>\n";
  echo "</body></html>\n";
  die();
}


if ($action == 'clone') {
  if ($trusted == false) {
    echo "Your client IP is not authorized to perform the requested operation!<br/>\n";
    echo "<p/><a href=\"$myname?db=$db_id\">Continue</a>\n";
    echo "</body></html>\n";
    die();
  }
  
  $mydb = get_dbproperties($db_id);
  $db             = $mydb['db_id'];
  $dbtype         = $mydb['type'];
  $dbaccess       = $mydb['access'];
  $dbname         = $mydb['name'];
  $dbdescription  = $mydb['description'];
  $dbusemem       = $mydb['usemem'];
  $dbmemstatus    = $mydb['memstatus'];
  $dbdigits       = $mydb['digits'];
  $dbsubdirdigits = $mydb['subdirdigits'];
  $dbtrustedIP    = $mydb['trustedIP'];
  $dbflags        = $mydb['flags'];
  
  
  $newname  = $dbname . "_clone";
  $newname = clean_fieldstr($newname);
  if ($newname == "") {
    echo "No valid name was entered!<br />\n";
  } else {

    if ($use_cmmmsrv == 'y') {
      /* create a TCP/IP socket */
      $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
      if ($socket < 0) {
        //echo "socket_create() failed.\nreason: " . socket_strerror ($socket) . "\n";
        echo "<!-- could not connect to cmmmsrv - reverting to checkmol/matchmol --!>\n";
        $use_cmmmsrv = "n";
      }
      $sockresult = socket_connect ($socket, $cmmmsrv_addr, $cmmmsrv_port);
      if ($sockresult < 0) {
        //echo "socket_connect() failed.<p />\n";
        echo "<!-- could not connect to cmmmsrv - reverting to checkmol/matchmol --!>\n";
        $use_cmmmsrv = "n";
      }
    }

    if ($use_cmmmsrv == 'y') {
      $a = socket_read($socket, 250, PHP_NORMAL_READ);
      //echo "the socket says: $a<br />\n";
      $pos = strpos($a,"READY");
      if ($pos === false) {
        echo "<!-- could not connect to cmmmsrv - reverting to checkmol/matchmol --!>\n";
        $use_cmmmsrv = "n";
      }
    }

    $dbtypecode = "SD";
    if ($dbtype == 2) { $dbtypecode = "RD"; }
    $new_db = add_database($newname,$dbtypecode,$dbdescription,$dbaccess,$dbdigits,$dbsubdirdigits,$dbtrustedIP,$dbflags);
    
    if ($use_cmmmsrv == 'y') {
      socket_write($socket,'#### bye');
      socket_close($socket);
    }

    if ($new_db > 0) {
      // now duplicate all data fields in moldatatable/rxndatatable
      if ($dbtype == 1) {
        $srctable = $prefix . "db" . $db_id . "_" . $moldatasuffix;
        $dsttable = $prefix . "db" . $new_db . "_" . $moldatasuffix;
        $id_id = "mol_id";
        $id_name = "mol_id";
        $prevcol = "auto_mol_fw";
      } elseif ($dbtype == 2) {
        $srctable = $prefix . "db" . $db_id . "_" . $rxndatasuffix;
        $dsttable = $prefix . "db" . $new_db . "_" . $rxndatasuffix;
        $id_id = "rxn_id";
        $id_name = "rxn_id";
        $prevcol = "rxn_name";
      } else { exit; }
      $alterstr = "";

      $result1 = mysql_query("SHOW FULL COLUMNS FROM $srctable")
        or die("Query failed! (1x)");
      while ($line1 = mysql_fetch_array($result1, MYSQL_ASSOC)) {
        $fieldname = $line1["Field"];
        $fieldtype = $line1["Type"];
        $default   = $line1["Default"];
        $defaultstr = "";
        if (($default != "NULL") && ($default != "")) {
          $defaultstr = "DEFAULT '" . $default . "'";
        }
        $comment   = $line1["Comment"];
        if (($fieldname !== "mol_id") && ($fieldname !== "rxn_id") && 
            ($fieldname !== "mol_name") && ($fieldname !== "rxn_name") &&
            (strpos($fieldname,"auto_mol_") === FALSE)) {
          if (strlen($alterstr) > 0) { $alterstr .= ", "; }
          $alterstr .= "ADD `$fieldname` $fieldtype NOT NULL $defaultstr COMMENT '" . $comment . "' AFTER `$prevcol`";
          $prevcol = $fieldname;
        }
      }  // while
      $alterstr = "ALTER TABLE `$dsttable` " . $alterstr;

      #echo "<pre>$alterstr\n</pre>\n";

      $result1 = mysql_query($alterstr)
        or die("ALTER command failed!");

      echo "added new database...<br />\n";
    } else {
      if ($new_db == 0) {
        echo "a data collection of this name exists already!<br />\n";
      } else {
        echo "addition of new data collection failed!<br />\n";
      }
    }
  }
  echo "<p /><a href=\"$myname?db=$new_db\">Continue</a>\n";
  echo "</body></html>\n";
  die();
}


if ($action == 'erase') {
  if ($trusted == false) {
    echo "Your client IP is not authorized to perform the requested operation!<br />\n";
    echo "<p /><a href=\"$myname?db=$db_id\">Continue</a>\n";
    echo "</body></html>\n";
    die();
  }
  $kill_db = $_POST['db'];
  echo "going to erase $kill_db ..... ";
  $kill_db = check_db($kill_db);
  if ($kill_db < 1) {
    echo "Erase data collection failed: invalid ID#! ($kill_db)<br />\n";
  } else {
    echo "<h3>Do you really want to erase this data collection?</h3>\n";
    $result1 = mysql_query("SELECT db_id, name FROM $metatable WHERE (db_id = $kill_db)")
      or die("Query failed! (1a)");
    $line1 = mysql_fetch_row($result1);
    mysql_free_result($result1);
    $db   = $line1[0];
    $name = $line1[1];
    echo "ID#: $db<br />\n";
    echo "Name: $name<br />\n";

    echo "<p />\n";
    echo "<table>\n";
    echo "<tr><td>";
    echo "<form action=\"$myname\" method=post>\n";
    echo "<input type=\"hidden\" name=\"action\" value=\"erase-confirm\">\n";
    echo "<input type=\"hidden\" name=\"db\" value=\"$db\">\n";
    echo "<input type=\"Submit\" name=\"select\" value=\"Yes, erase it!\">\n";
    echo "</form>\n";

    echo "</td><td>";

    echo "<form action=\"$myname\" method=post>\n";
    echo "<input type=\"hidden\" name=\"action\" value=\"\">\n";
    echo "<input type=\"hidden\" name=\"db\" value=\"$db\">\n";
    echo "<input type=\"Submit\" name=\"select\" value=\"&nbsp;&nbsp;&nbsp;Cancel&nbsp;&nbsp;&nbsp;\">\n";
    echo "</form>\n";
    echo "</td></tr>\n";
    echo "</table>\n";

  }
  echo "</body></html>\n";
  die();
}
  
  
if ($action == 'erase-confirm') {
  if ($trusted == false) {
    echo "Your client IP is not authorized to perform the requested operation!<br />\n";
    echo "<p /><a href=\"$myname?db=$db_id\">Continue</a>\n";
    echo "</body></html>\n";
    die();
  }
  $kill_db = $_POST['db'];
  $kill_db = check_db($kill_db);
  if ($kill_db < 1) {
    echo "Erase data collection failed: invalid ID#!<br />\n";
    echo "</body></html>";
    die();
  }
  echo "Going to erase data collection #$kill_db ....<br />\n";
  
  $mydb   = get_dbproperties($db_id);
  $db     = $mydb['db_id'];
  $access = $mydb['access'];
  $name   = $mydb['name'];
  $dbtype = $mydb['type'];
  
  $killed = unregister_db($kill_db);
  if ($killed == 1) {
    echo "metadata for 1 collection erased<br />\n";
  } else {
    echo "Oops!<br />\n";
  }

  // now drop all the data tables
  if ($dbtype == 1) {
    $killresult = drop_moltables($kill_db); 
    echo "$killresult";
  } elseif ($dbtype == 2) {
    $killresult = drop_rxntables($kill_db);
    echo "$killresult";
  }

  echo "<p />\n<a href=\"$myname?db=$kill_db\">Continue</a>\n";
  echo "</body></html>\n";
  die();
}

if (($db_id > 0) && (($action == 'fields') || ($action == 'updatefields') 
  || ($action == 'dropfield') || ($action == 'dropfieldconfirm')  )) {
  $result1 = mysql_query("SELECT db_id, access, name, type FROM $metatable WHERE (db_id = $db_id)")
    or die("Query failed! (1a)");
  $line1 = mysql_fetch_row($result1);
  mysql_free_result($result1);
  $db     = $line1[0];
  $access = $line1[1];
  $name   = $line1[2];
  $dbtype   = $line1[3];
  if ($dbtype == 1) {
    $tbl    = $prefix . "db" . $db_id . "_" . $moldatasuffix;
    $idname = "mol_id";
    $namename = "mol_name";
  } elseif ($dbtype == 2) {
    $tbl    = $prefix . "db" . $db_id . "_" . $rxndatasuffix;
    $idname = "rxn_id";
    $namename = "rxn_name";
  }

  if (($access < 3) && ($trusted == false) && ($db_trusted == false)) {
    echo "Your client IP is not authorized to perform the requested operation!<br />\n";
    echo "<p /><a href=\"$myname?db=$db_id\">Continue</a>\n";
    echo "</body></html>\n";
    die();
  }

  if ($action == 'fields') {
    $newoptions = "";
    @$newfield  = $_POST['fn'];
    @$newtype   = $_POST['ft'];
    @$newcustomtype  = $_POST['ftcustom'];
    @$newoptions  = $_POST['opt'];
    @$newlabel  = $_POST['lbl'];
    @$newformat = $_POST['fmt'];
    @$newsearch = $_POST['search'];
    $newfield  = clean_fieldstr($newfield);
    $newtype   = trim($newtype);
    $newcustomtype   = trim($newcustomtype);
    $newoptions   = trim($newoptions);
    $newlabel  = trim($newlabel);
    $newlabel  = str_replace("<","",$newlabel);
    $newlabel  = str_replace(">","",$newlabel);
    $newformat = trim($newformat);
    $newsearch = trim($newsearch);
    @$newsdflabel = $_POST['sdflbl'];
    $newsdflabel   = trim($newsdflabel);
    $newsdflabel   = str_replace("<","",$newsdflabel);
    $newsdflabel   = str_replace(">","",$newsdflabel);
    $newcustomtype = str_replace(";","",$newcustomtype);
    $newcustomtype = str_replace("\\","",$newcustomtype);
    $newoptions    = str_replace(";","",$newoptions);
    $newoptions    = str_replace("\\","",$newoptions);

    $nt = "";
    if ($newtype == "vc255" ) { $nt = "VARCHAR(255)"; }
    if ($newtype == "txt" ) { $nt = "TEXT"; }
    if ($newtype == "int" ) { $nt = "INT(11)"; }
    if ($newtype == "dec" ) { $nt = "DOUBLE"; }
    if ($newtype == "yn" )  { $nt = "ENUM('Y','N')"; }
    if ($newtype == "custom") {$nt = $newcustomtype; }
    if ($nt == "") { $nt = "VARCHAR(255)"; }  // fallback
    if ($newoptions == "") { $newoptions = "NOT NULL"; }
    $nfmt = "0";
    if ($newformat == "0" )  { $nfmt = "0"; }
    if ($newformat == "1" )  { $nfmt = "1"; }
    if ($newformat == "2" )  { $nfmt = "2"; }
    if ($newformat == "3" )  { $nfmt = "3"; }
    if ($newformat == "4" )  { $nfmt = "4"; }
    $nsearch = "0";
    if ($newsearch == "1" )  { $nsearch = "1"; }
    // check if field type is appropriate for text search
    if (is_stringtype($nt) == FALSE) { $nsearch = "0"; }
    
     // insert new fields here, e.g.
    if (($newfield != "") && ($nt != "")) {
      $qstr = "ALTER TABLE " . $tbl;
      $qstr .= " ADD `" . $newfield . "` " . $nt . " " . $newoptions . " ";
      $qstr .= "COMMENT '>>>>" . $newlabel . "<" . $nfmt . "<" . $newsdflabel . "<" . $nsearch . "<<'";
      $result1 = mysql_query($qstr);
      $err = mysql_errno();
      mysql_free_result($result1);
      if ($err != 0) { echo "Action failed ($err)<br />\n"; }
    }
  } elseif ($action == "dropfield") {
    $newfield    = $_POST['fn'];
    $newfield    = clean_fieldstr($newfield);
    $newcustomtype  = $_POST['ftcustom'];
    $newcustomtype   = trim($newcustomtype);
    $newcustomtype = str_replace(";","",$newcustomtype);
    $newcustomtype = str_replace("\\","",$newcustomtype);
    
    if ($newfield == $namename) {
      echo "<h3>ERROR: this data field (${namename}) cannot be erased!</h3>\n";
      echo "<p />\n<a href=\"$myname?db=$db_id\">Continue</a>\n";
      echo "</body></html>\n";
      die();
    }

    $result2 = mysql_query("SELECT COUNT(${idname}) AS molcount FROM $tbl")
      or die("Query failed! (1b)");
    $line2 = mysql_fetch_row($result2);
    mysql_free_result($result2);
    $molcount   = $line2[0];
    if ($molcount > 0) {
      echo "<h3>ATTENTION: this data collection contains already $molcount entries! ";
      echo "Do you REALLY want to erase this data field with all its content?</h3>\n";

      ?>
      <table><tr valign="top"><td>
      <form action="<?php echo "$myname"; ?>" method="post">
      <input type="hidden" name="action" value="editfield">
      <input type="hidden" name="db" value="<?php echo "$db_id"; ?>">
      <input type="hidden" name="fn" value="<?php echo "$newfield";?>">
      <input type="hidden" name="ft" value="custom">
      <input type="hidden" name="ftcustom" value="<?php echo "$newcustomtype"?>">
      <input type="Submit" name="update" value="No, keep this field">
      </form></td><td><form action="<?php echo "$myname"; ?>" method="post">
      <input type="hidden" name="action" value="dropfieldconfirm">
      <input type="hidden" name="db" value="<?php echo "$db_id"; ?>">
      <input type="hidden" name="fn" value="<?php echo "$newfield";?>">
      <input type="hidden" name="ft" value="<?php echo "$newcustomfieldtype"?>">
      <input type="Submit" name="drop" value="Yes, erase this field">
      </form></td></tr>
      </table>
      <?php
      
      echo "</body></html>\n";
      die();
    }
    
    if ($newfield != "") {
      $updstr = "ALTER TABLE $tbl DROP $newfield";
      $result1 = mysql_query($updstr);
      $err = mysql_errno();
      #mysql_free_result($result1);
      if ($err != 0) { echo "Action failed ($err)<br />\n"; }
    }
  } elseif ($action == "dropfieldconfirm") {
    $newfield    = $_POST['fn'];
    $newfield    = clean_fieldstr($newfield);
    if ($newfield == $namename) {
      echo "ERROR: this data field ($namename) cannot be erased!\n</body></html>\n";
      die();
    }
    if ($newfield != "") {
      $updstr = "ALTER TABLE $tbl DROP $newfield";
      $result1 = mysql_query($updstr);
      $err = mysql_errno();
      #mysql_free_result($result1);
      if ($err != 0) { echo "Action failed ($err)<br />\n"; }
    }
  } else {    // updatefields
    $newfield    = $_POST['fn'];
    $newtype     = $_POST['ft'];
    $newcustomtype = $_POST['ftcustom'];
    $newoptions  = $_POST['opt'];
    $newlabel    = $_POST['lbl'];
    $newformat   = $_POST['fmt'];
    $newsearch   = $_POST['search'];
    $newsdflabel = $_POST['sdflbl'];
    $newfield    = clean_fieldstr($newfield);
    $newtype     = trim($newtype);
    $newcustomtype   = trim($newcustomtype);
    $newoptions   = trim($newoptions);
    $newlabel    = trim($newlabel);
    $newlabel  = str_replace("<","",$newlabel);
    $newlabel  = str_replace(">","",$newlabel);
    $newformat   = trim($newformat);
    $newsearch   = trim($newsearch);
    $newsdflabel = trim($newsdflabel);
    $newsdflabel  = str_replace("<","",$newsdflabel);
    $newsdflabel  = str_replace(">","",$newsdflabel);
    $newcustomtype = str_replace(";","",$newcustomtype);
    $newcustomtype = str_replace("\\","",$newcustomtype);
    $newoptions    = str_replace(";","",$newoptions);
    $newoptions    = str_replace("\\","",$newoptions);

    $nt = "";
    if ($newtype == "vc255" ) { $nt = "VARCHAR(255)"; }
    if ($newtype == "txt" ) { $nt = "TEXT"; }
    if ($newtype == "int" ) { $nt = "INT(11)"; }
    if ($newtype == "dec" ) { $nt = "DOUBLE"; }
    if ($newtype == "yn" )  { $nt = "ENUM('Y','N')"; }
    if ($newtype == "custom") {$nt = $newcustomtype; }
    if ($nt == "") { $nt = "VARCHAR(255)"; }  // fallback
    if ($newoptions == "") { $newoptions = "NOT NULL"; }

    // check if field type is appropriate for text search
    if (is_stringtype($nt) == FALSE) { $newsearch = "0"; }
    
    $comment = ">>>>" . $newlabel . "<" . $newformat . "<" . $newsdflabel . "<" . $newsearch . "<<";

    if ($newfield != "") {
      $updstr = "ALTER TABLE $tbl CHANGE $newfield $newfield $nt $newoptions COMMENT '$comment'";
      #echo "$updstr <br />";
      $result1 = mysql_query($updstr);
      $err = mysql_errno();
      #mysql_free_result($result1);
      if ($err != 0) { echo "Action failed ($err)<br />\n"; }
    }
  }

  echo "<h3>Data fields for data collection \"$name\"</h3>\n";
  echo "<b>Properties of table $tbl</b><p />\n";
  echo "<table class=\"highlight\">\n";
  echo "<tr align=\"left\"><th>field name&nbsp;&nbsp;&nbsp;</th><th>field type&nbsp;&nbsp;&nbsp;</th>";
  echo "<th>label&nbsp;&nbsp;&nbsp;</th><th>format&nbsp;&nbsp;&nbsp;</th><th>field&nbsp;name&nbsp;for&nbsp;SDF&nbsp;export&nbsp;&nbsp;</th><th>search</th></tr><th></th>\n";
  $result1 = mysql_query("SHOW FULL COLUMNS FROM $tbl")
    or die("Query failed! (1x)");
  while ($line1 = mysql_fetch_array($result1, MYSQL_ASSOC)) {
    $fieldname = $line1["Field"];
    $fieldtype = $line1["Type"];
    $comment   = $line1["Comment"];
    $label     = "";
    $sdflabel  = "";
    $format    = 1;
    $formatstr = "plain HTML";
    $searchmode = 0;
    $searchstr = "";
    $pos = strpos($comment, ">>>>");
    if ($pos !== false) {
      if ($pos == 0) {
        $comment = str_replace(">>>>","",$comment);
        $acomment = explode("<",$comment);
        $label  = $acomment[0];
        $format = 1;
        $formatstr = "plain HTML";
        $nformat = $acomment[1];
        if ($nformat == 0) { $format = 0; $formatstr = "hidden"; }
        if ($nformat == 1) { $format = 1; $formatstr = "plain HTML"; }
        if ($nformat == 2) { $format = 2; $formatstr = "multiline"; }
        if ($nformat == 3) { $format = 3; $formatstr = "formula"; }
        if ($nformat == 4) { $format = 4; $formatstr = "URL"; }
        $sdflabel   = $acomment[2];
        $searchmode = $acomment[3];
        if ($searchmode != 1) { $searchmode = 0; }
        if ($searchmode == 0) { $searchstr = ""; }
        if ($searchmode == 1) { $searchstr = "includable"; }
      }
    }
    if ($fieldname == $namename) { $searchstr = "always"; }
    echo "<tr valign=\"top\"><td valign=\"top\">$fieldname&nbsp;&nbsp;&nbsp;</td>
    <td valign=\"top\">$fieldtype&nbsp;&nbsp;&nbsp;</td><td valign=\"top\">$label&nbsp;&nbsp;&nbsp;</td>
    <td>$formatstr&nbsp;&nbsp;&nbsp;</td><td valign=\"top\">$sdflabel&nbsp;&nbsp;&nbsp;</td>
    <td valign=\"top\">$searchstr&nbsp;&nbsp;</td>\n";
    
    echo "<td valign=\"top\">";
    //if (($fieldname != "mol_id") && ($fieldname != "mol_name")) {
    if ($fieldname != $idname) {
      echo "<form action=\"$myname\" method=\"post\">\n";
      echo "<input type=\"hidden\" name=\"action\" value=\"editfield\">\n";
      echo "<input type=\"hidden\" name=\"db\" value=\"$db_id\">\n";
      echo "<input type=\"hidden\" name=\"fn\" value=\"$fieldname\">\n";
      echo "<input type=\"Submit\" name=\"editfield\" value=\"Edit\">\n";
      echo "</form>\n";
    }
    echo "</td>";
    echo "</tr>\n";
  }
  mysql_free_result($result1);
  echo "</table>\n";

  // form for new fields
?>

<p />&nbsp;<p />
<hr />
<h3>Add new data field:</h3>
<form action="<?php echo "$myname";?>" method=post>

<table>
<tr align="left"><td><b>field name (in database)</b></td><td><input type="text" size="40" name="fn"></td></tr>
<tr align="left"><td><b>MySQL field type</b></td><td><select size="1" name="ft">
<option value="vc255" selected>varchar(255)</option>
<option value="txt">text</option> 
<option value="int">integer</option> 
<option value="dec">decimal</option> 
<option value="yn">enum('Y','N')</option> 
<option value="custom">other...</option> 
</select>&nbsp;<input type="text" size="22" name="ftcustom"></td></tr>
<tr align="left"><td><b>MySQL field options</b></td><td><input type="text" size="40" name="opt"></td></tr>
<tr align="left"><td><b>label (for display)</b></td><td><input type="text" size="40" name="lbl"></td></tr>
<tr align="left"><td><b>format</b></td><td><select size="1" name="fmt">
<option value="0">hidden</option> 
<option value="1" selected>plain HTML</option>
<option value="2">multiline</option> 
<option value="3">formula</option> 
<option value="4">URL</option> 
</select></td></tr>
<tr align="left"><td><b>field name for SDF export&nbsp;</b></td><td><input type="text" size="40" name="sdflbl"></td></tr>
<tr align="left"><td><b>search mode</b></td><td><select size="1" name="search">
<option value="0" selected>not&nbsp;searchable</option> 
<option value="1">includable&nbsp;in&nbsp;search</option>
</select></td></tr>
</table>
<p />
<input type="hidden" name="action" value="fields">
<input type="hidden" name="db" value="<?php echo "$db_id"; ?>">
<input type="Submit" name="add" value="Add new data field">
</form>

<?php

  echo "<p /><a href=\"$myname?db=$db_id\">Continue</a>\n";
  echo "</body></html>\n";
  die();
}

  
if (($db_id > 0) && ($action == "editfield")) {
  $result1 = mysql_query("SELECT db_id, access, name, type FROM $metatable WHERE (db_id = $db_id)")
    or die("Query failed! (1a)");
  $line1 = mysql_fetch_row($result1);
  mysql_free_result($result1);
  $db     = $line1[0];
  $access = $line1[1];
  $name   = $line1[2];
  $dbtype   = $line1[3];
  $field  = $_POST['fn'];
  if ($dbtype == 1) {
    $tbl    = $prefix . "db" . $db_id . "_" . $moldatasuffix;
    $idname = "mol_id";
    $namename = "mol_name";
  } elseif ($dbtype == 2) {
    $tbl    = $prefix . "db" . $db_id . "_" . $rxndatasuffix;
    $idname = "rxn_id";
    $namename = "rxn_name";
  }

  if (($access < 3) && ($trusted == false) && ($db_trusted == false)) {
    echo "Your client IP is not authorized to perform the requested operation!<br />\n";
    echo "<p /><a href=\"$myname?db=$db_id\">Continue</a>\n";
    echo "</body></html>\n";
    die();
  }

  echo "<p />&nbsp;<p />\n";
  echo "<h3>Edit data field \"$field\"</h3>\n";
  echo "<form action=\"$myname\" method=\"post\">\n";

  $result1 = mysql_query("SHOW FULL COLUMNS FROM $tbl")
    or die("Query failed! (1xx)");
  while ($line1 = mysql_fetch_array($result1, MYSQL_ASSOC)) {
    $fieldname = $line1["Field"]; #$line1[0];
    if ($field == $fieldname) {
      $fieldtype  = $line1["Type"]; #$line1[1];
      $comment    = $line1["Comment"];
      $nullstr    = $line1["Null"];
      $defaultstr = $line1["Default"];
      $extrastr   = $line1["Extra"];
      $options = "";
      if ($nullstr == "NO") { $options .= "NOT NULL "; }
      if ($defaultstr != "") { $options .= "DEFAULT '" . $defaultstr . "' "; }
      if ($extrastr != "") { $options .= $extrastr . " "; }
      $label      = "";
      $format     = 1;
      $searchmode = 0;
      $searchstr  = "";
      $pos = strpos($comment, ">>>>");
      if ($pos !== false) {
        if ($pos == 0) {
          $comment = str_replace(">>>>","",$comment);
          $acomment = explode("<",$comment);
          $label  = $acomment[0];
          $format = 1;
          $formatstr = "plain HTML";
          $nformat = $acomment[1];
          if ($nformat == 0) { $format = 0; }
          if ($nformat == 1) { $format = 1; }
          if ($nformat == 2) { $format = 2; }
          if ($nformat == 3) { $format = 3; }
          if ($nformat == 4) { $format = 4; }
          $sdflabel   = $acomment[2];
          $searchmode = $acomment[3];
          if ($searchmode != 1) { $searchmode = 0; }
          if ($searchmode == 0) { $searchstr = ""; }
          if ($searchmode == 1) { $searchstr = "includable"; }
        }
      }
      echo "</tr>\n";
    }  // if $field == $fieldname
  }   // while ...
  mysql_free_result($result1);

  $customfieldtype = $fieldtype;

  echo "<table>\n";
  echo "<tr align=\"left\"><td><b>field name (in database)</b>&nbsp;&nbsp;&nbsp;</td><td>$field</td></tr>\n";
  echo "<tr align=\"left\"><td><b>MySQL field type</b>&nbsp;&nbsp;&nbsp;</td><td>$customfieldtype</td></tr>\n";
  echo "<tr align=\"left\"><td><b>options</b>&nbsp;&nbsp;&nbsp;</td><td><input type=\"text\" size=\"40\" name=\"opt\" value=\"$options\"></td>\n";
  echo "<tr align=\"left\"><td><b>label (for display)</b>&nbsp;&nbsp;&nbsp;</td><td><input type=\"text\" size=\"40\" name=\"lbl\" value=\"$label\"></td>\n";
  echo "<tr align=\"left\"><td><b>format</b>&nbsp;&nbsp;&nbsp;</td><td>";
  echo "<select size=\"1\" name=\"fmt\">";
  echo "<option value=\"0\""; if ($format == 0) { echo " selected"; } echo ">hidden</option>\n"; 
  echo "<option value=\"1\""; if ($format == 1) { echo " selected"; }; echo ">plain HTML</option>\n";
  echo "<option value=\"2\""; if ($format == 2) { echo " selected"; }; echo ">multiline</option>\n"; 
  echo "<option value=\"3\""; if ($format == 3) { echo " selected"; }; echo ">formula</option>\n"; 
  echo "<option value=\"4\""; if ($format == 4) { echo " selected"; }; echo ">URL</option>\n"; 
  echo "</select></td></tr>\n";
  echo "<tr align=\"left\"><td><b>field name for SDF export</b>&nbsp;&nbsp;&nbsp;</td><td><input type=\"text\" size=\"40\" name=\"sdflbl\" value=\"$sdflabel\"></td></tr>\n";
  echo "<tr align=\"left\"><td><b>search mode</b>&nbsp;&nbsp;&nbsp;</td><td>";
  echo "<select size=\"1\" name=\"search\">";
  echo "<option value=\"0\""; if ($searchmode == 0) { echo " selected"; } echo ">not&nbsp;searchable</option>\n"; 
  echo "<option value=\"1\""; if ($searchmode == 1) { echo " selected"; }; echo ">includable&nbsp;in&nbsp;search</option>\n";
  echo "</select></td></tr>\n";
  echo "</table><p />\n";

  ?>
  <table><tr valign="top"><td>
  <input type="hidden" name="action" value="updatefields">
  <input type="hidden" name="db" value="<?php echo "$db_id"; ?>">
  <input type="hidden" name="fn" value="<?php echo "$field";?>">
  <input type="hidden" name="ft" value="custom">
  <input type="hidden" name="ftcustom" value="<?php echo "$customfieldtype"?>">
  <input type="Submit" name="update" value="Update data field">
  </form></td><td><form action="<?php echo "$myname"; ?>" method="post">
  <input type="hidden" name="action" value="dropfield">
  <input type="hidden" name="db" value="<?php echo "$db_id"; ?>">
  <input type="hidden" name="fn" value="<?php echo "$field";?>">
  <input type="hidden" name="ft" value="<?php echo "$fieldtype"?>">
  <input type="Submit" name="drop" value="Erase data field">
  </form></td></tr>
  <tr valign="top"><td></td><td><small>be careful with this button!</small></td></tr>  
  </table>
  <?php
  
  echo "<p /><a href=\"$myname?db=$db_id\">Continue without saving</a>\n";
  echo "</body></html>\n";
  die();
  //}
}   // action == editfield


if (($db_id > 0) && ($action == "editdb")) {
  if ($trusted == false) {
    echo "Your client IP is not authorized to perform the requested operation!<br />\n";
    echo "<p /><a href=\"$myname?db=$db_id\">Continue</a>\n";
    echo "</body></html>\n";
    die();
  }
  $db_id = $_POST['db'];
  $db_id = check_db_all($db_id);
  $result2 = mysql_query("SELECT db_id, type, access, name, description, usemem, memstatus, digits, subdirdigits, trustedIP FROM $metatable WHERE (db_id = $db_id)")
    or die("Query failed! (editdb)");
  $line2 = mysql_fetch_row($result2);
  mysql_free_result($result2);

  $db           = $line2[0];
  $dbtype         = $line2[1];
  $access       = $line2[2];
  $name         = $line2[3];
  $description  = $line2[4];
  $usemem       = $line2[5];
  $memstatus    = $line2[5];
  $digits       = $line2[7];
  $subdirdigits = $line2[8];
  $trustedIP    = $line2[9];

  echo "<h3>Edit properties of  data collection $db:</h3>\n";
  
  echo "<form action=\"$myname\" method=\"post\">\n";
  echo "<table>\n";
  echo "<tr><td><b>Name</b></td><td><input type=\"text\" size=\"40\" name=\"name\" value=\"$name\"> max. 80 characters</td></tr>\n";
  echo "<tr><td><b>Type</b></td><td><select size=\"1\" name=\"type\">\n";
  echo "<option value=\"SD\""; if ($dbtype == 1) {echo " selected";} echo ">SD</option>\n";
  echo "<option value=\"RD\""; if ($dbtype == 2) {echo " selected";} echo ">RD</option>\n"; 
  echo "</select> <small>(SD = structure+data, RD = reaction+data)</small></td></tr>\n";
  echo "<tr><td><b>Access</b></td><td><select size=\"1\" name=\"access\">\n";
  echo "<option value=\"0\""; if ($access == 0) {echo " selected";} echo ">disabled</option>\n";
  echo "<option value=\"1\""; if ($access == 1) {echo " selected";} echo ">read-only</option>\n";
  echo "<option value=\"2\""; if ($access == 2) {echo " selected";} echo ">add/update</option>\n"; 
  echo "<option value=\"3\""; if ($access == 3) {echo " selected";} echo ">full&nbsp;access</option>\n"; 
  echo "</select></td></tr>\n";
  echo "<tr><td><b>Description</b></td><td><textarea  cols=\"60\" rows=\"3\" name=\"descr\">$description</textarea></td></tr>\n";

  echo "<tr><td><b>Number of digits for bitmap files</b></td><td><input type=\"text\" size=\"10\" 
  name=\"digits\" value=\"$digits\"> (e.g. <b>8</b> for filenames like 00000123.png)</td></tr>\n";
  echo "<tr><td><b>Number&nbsp;of&nbsp;digits&nbsp;for&nbsp;subdirectories&nbsp;</b></td><td><input type=\"text\" size=\"10\" 
  name=\"subdirdigits\" value=\"$subdirdigits\"> (e.g. <b>4</b> for something like 0002/00020123.png)</td></tr>\n";
  echo "<tr><td><b>Trusted IP addresses </b></td><td><textarea cols=\"60\" rows=\"1\" 
  name=\"trustedip\">$trustedIP</textarea><br />
  <small>a comma-separated list of max. 10 IP addresses (for extended administrative privileges)</small></td></tr>\n";
 
  echo "</table>\n";

  echo "<input type=\"hidden\" name=\"action\" value=\"update\">\n";
  echo "<input type=\"hidden\" name=\"db\" value=\"$db_id\">\n";
  echo "<input type=\"Submit\" name=\"update\" value=\"Update data collection properties\">\n";
  echo "</form>\n";
  echo "<table>\n";

  echo "<p /><a href=\"$myname?db=$db_id\">Continue without saving</a>\n";
  echo "</body></html>\n";
  die();
}   // action == editdb


if ($db_id > 0) {
  echo "<h3><span style=\"color:LightSeaGreen\">Available data collections:</span></h3>\n";
  echo "<form action=\"$myname\" method=post>\n";
  echo "<select size=\"1\" name=\"db\">\n";

  $result = mysql_query("SELECT db_id, name FROM $metatable ORDER BY db_id")
    or die("Query failed! (1b)");
  while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
    $db   = $line["db_id"];
    $name = $line["name"];
    echo "<option value=\"$db\"";
    if ($db == $db_id) { echo " selected"; }
    echo ">$db: $name</option>\n";
  }
  mysql_free_result($result);

?>
</select>
<input type="hidden" name="action" value="change">
<input type="Submit" name="select" value="Apply selection">
</form>
<br />
<?php

} else {  // if ($db_id > 0)....
  echo "There is no data collection available so far.<br />\n";
}


if (($db_id > 0) && ($action == "update")) {
  if ($trusted == false) {
    echo "Your client IP is not authorized to perform the requested operation!<br />\n";
    echo "<p /><a href=\"$myname?db=$db_id\">Continue</a>\n";
    echo "</body></html>\n";
    die();
  }
  $db_id = $_POST['db'];
  $db_id = check_db_all($db_id);
  $result2 = mysql_query("SELECT db_id, type, access, name, description, usemem, digits, subdirdigits FROM $metatable WHERE (db_id = $db_id)")
    or die("Query failed! (editdb)");
  $line2 = mysql_fetch_row($result2);
  mysql_free_result($result2);

  $db           = $line2[0];
  $dbtype       = $line2[1];
  $access       = $line2[2];
  $name         = $line2[3];
  $description  = $line2[4];
  $usemem       = $line2[5];
  $digits       = $line2[6];
  $subdirdigits = $line2[7];

  $newname   = $_POST['name'];
  $newname   = substr($newname,0,80);
  $newname   = mysql_real_escape_string($newname);
  $newtype   = $_POST['type'];
  $newaccess = $_POST['access'];
  $newdescr  = $_POST['descr'];
  $newdescr  = mysql_real_escape_string($newdescr);
  $ntype = 1;
  if ($newtype == "SD") {$ntype = 1; }
  if ($newtype == "RD") {$ntype = 2; } 
  $naccess = 1;
  if ($newaccess == 0) { $naccess = 0; }
  if ($newaccess == 1) { $naccess = 1; }
  if ($newaccess == 2) { $naccess = 2; }
  if ($newaccess == 3) { $naccess = 3; }

  $newdigits = intval($_POST['digits']);
  if ($newdigits < 3)  { $newdigits = 3; }
  if ($newdigits > 30) { $newdigits = 30; }
  $newsubdirdigits = intval($_POST['subdirdigits']);
  if ($newsubdirdigits < 0)  { $newsubdirdigits = 0; }
  if ($newsubdirdigits > $newdigits) { $newsubdirdigits = $newdigits; }
  $newtrustedIP = $_POST['trustedip'];
  $newtrustedIP = mysql_real_escape_string($newtrustedIP);
  $newtrustedIP = substr($newtrustedIP,0,250);
  
  $changetype_OK = 1;
  if ($ntype != $dbtype) {
    if ((($dbtype == 1) && (get_numentries($db_id,1) > 0)) ||
        (($dbtype == 2) && (get_numentries($db_id,2) > 0))) { $changetype_OK = 0; }
  }
  if ($changetype_OK == 0) { $ntype = $dbtype; }

  $updstr = "UPDATE $metatable SET name = \"$newname\", type = $ntype, access = $naccess, ";
  $updstr .= "description = \"$newdescr\", usemem = \"$usemem\", digits = $newdigits, ";
  $updstr .= "subdirdigits = $newsubdirdigits, trustedIP = '" . $newtrustedIP . "' WHERE db_id = $db_id";
  #echo "SQL: $updstr<br>\n";
  $result1 = mysql_query($updstr);
  $err = mysql_errno();
  #mysql_free_result($result1);
  if ($err != 0) { echo "Action failed ($err)<br />\n"; }
  if (($ntype != $dbtype) && ($changetype_OK == 1)) {
    if ($use_cmmmsrv == 'y') {
      /* create a TCP/IP socket */
      $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
      if ($socket < 0) {
        //echo "socket_create() failed.\nreason: " . socket_strerror ($socket) . "\n";
        echo "<!-- could not connect to cmmmsrv - reverting to checkmol/matchmol --!>\n";
        $use_cmmmsrv = "n";
      }
      $sockresult = socket_connect ($socket, $cmmmsrv_addr, $cmmmsrv_port);
      if ($sockresult < 0) {
        //echo "socket_connect() failed.<p />\n";
        echo "<!-- could not connect to cmmmsrv - reverting to checkmol/matchmol --!>\n";
        $use_cmmmsrv = "n";
      }
    }
    if ($use_cmmmsrv == 'y') {
      $a = socket_read($socket, 250, PHP_NORMAL_READ);
      //echo "the socket says: $a<br />\n";
      $pos = strpos($a,"READY");
      if ($pos === false) {
        echo "<!-- could not connect to cmmmsrv - reverting to checkmol/matchmol --!>\n";
        $use_cmmmsrv = "n";
      }
    }
    
    if ($ntype == 1) {
      drop_rxntables($db_id);
      create_moltables($db_id);
    }
    if ($ntype == 2) {
      drop_moltables($db_id);
      create_rxntables($db_id);
    }
    
    if ($use_cmmmsrv == 'y') {
      socket_write($socket,'#### bye');
      socket_close($socket);
    }
  }
}


if ($db_id > 0) {
  $mydb = get_dbproperties($db_id);
  $db           = $mydb['db_id'];
  $dbtype       = $mydb['type'];
  $access       = $mydb['access'];
  $name         = $mydb['name'];
  $description  = $mydb['description'];
  $usemem       = $mydb['usemem'];
  $memstatus    = $mydb['memstatus'];
  $digits       = $mydb['digits'];
  $subdirdigits = $mydb['subdirdigits'];
  $trustedIP    = $mydb['trustedIP'];

  echo "<table width=\"100%\" class=\"highlight\">\n";
  echo "<tr align=\"left\"><th colspan=\"2\">Current selection:</th></tr>\n";
  echo "<tr><td>ID number:</td><td width=\"80%\">$db</td></tr>\n"; 
  echo "<tr><td>Name:</td><td> $name</td></tr>\n";
  echo "<tr><td>Type:</td><td>";
  if ($dbtype == 1) {echo "SD"; }
  if ($dbtype == 2) {echo "RD"; }
  echo "<tr><td>Access:</td><td>";
  if ($access == 0) {echo "disabled"; }
  if ($access == 1) {echo "read-only"; }
  if ($access == 2) {echo "add/update"; }
  if ($access == 3) {echo "full&nbsp;access"; }
  echo "</td></tr>\n";
  echo "<tr><td>Description:</td><td> $description</td></tr>\n";
  echo "</table>\n<p />\n";
  
  // added in 5R.10: a quick check if the pic2dtable exists
  // added in 5R.30: a quick check if the `svg`column exists in pic2dtable
  $dbprefix = $prefix . "db" . $db . "_";
  $qtest = "DESCRIBE " . $dbprefix . $pic2dsuffix;
  $testresult = mysql_query($qtest);
  $err = mysql_errno();
  if ($err == 0) {
    //echo "OK<br>\n";
    $dline = ""; 
    $field = "";
    $found_svg = 0;
    while ($dline = mysql_fetch_array($testresult, MYSQL_ASSOC)) {
      $field   = $dline["Field"];
      if ($field == "svg") { $found_svg = 1; }
    }
    if ($found_svg == 0) {
    $altercmd = "ALTER TABLE `${dbprefix}$pic2dsuffix` ADD `svg` BLOB NOT NULL;";
    $result6 = mysql_query($altercmd)
      or die("Alter failed! (4f)");
    }
  } else {
    if ($dbtype == 1) { $idname = "mol_id"; }
    if ($dbtype == 2) { $idname = "rxn_id"; }
    $createcmd = "CREATE TABLE IF NOT EXISTS ${dbprefix}$pic2dsuffix (
    `$idname` INT(11) NOT NULL DEFAULT '0',
    `type` TINYINT NOT NULL DEFAULT '1' COMMENT '1 = png',
    `status` TINYINT NOT NULL DEFAULT '0' COMMENT '0 = does not exist, 1 = OK, 2 = OK, but do not show, 3 = to be created/updated, 4 = to be deleted',
    `svg` BLOB NOT NULL,
    PRIMARY KEY ($idname)
    ) ENGINE = MYISAM CHARACTER SET $mysql_charset COLLATE $mysql_collation COMMENT='Housekeeping for 2D depiction'";
    $result6 = mysql_query($createcmd)
      or die("Create failed! (4g)");
    //echo "$createcmd<br>\n";
    echo "<b>ATTENTION:</b> missing table ${dbprefix}$pic2dsuffix was added! Please
    run the <i>updatebitmap.pl</i> script on the command line in order to synchronize
    the tables.<br>\n";
  }


  echo "<table>\n<tr>\n<td>\n";
  echo "<form action=\"$myname\" method=post>\n";
  echo "<input type=\"hidden\" name=\"action\" value=\"editdb\">\n";
  echo "<input type=\"hidden\" name=\"db\" value=\"$db\">\n";
  echo "<input type=\"Submit\" name=\"select\" value=\"&nbsp;Edit data collection properties&nbsp;\">\n";
  echo "</form>\n";
  echo "</td>\n<td>\n";
  echo "<form action=\"$myname\" method=post>\n";
  echo "<input type=\"hidden\" name=\"action\" value=\"fields\">\n";
  echo "<input type=\"hidden\" name=\"db\" value=\"$db\">\n";
  echo "<input type=\"Submit\" name=\"select\" value=\"&nbsp;&nbsp;&nbsp;Edit data field definitions&nbsp;&nbsp;&nbsp;\">\n";
  echo "</form>\n";
  echo "</td>\n<td>\n";
  echo "<form action=\"$myname\" method=post>\n";
  echo "<input type=\"hidden\" name=\"action\" value=\"erase\">\n";
  echo "<input type=\"hidden\" name=\"db\" value=\"$db\">\n";
  echo "<input type=\"Submit\" name=\"select\" value=\"Erase selected data collection\">\n";
  echo "</form>\n";
  echo "</td>\n</tr>\n</table>\n";

  echo "<hr />\n";

  echo "<p><form action=\"editdata.php\" method=post>\n";
  echo "<input type=\"hidden\" name=\"db\" value=\"$db\">\n";
  echo "<input type=\"Submit\" name=\"select\" value=\"&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Add/edit data record&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\">\n";
  echo "&nbsp;&nbsp;<b>Draw structure formulae in the structure editor, add textual data.</b>\n";
  echo "</form></p>\n";

  echo "<hr />\n";

  echo "<p><form action=\"export.php\" method=post>\n";
  echo "<input type=\"hidden\" name=\"db\" value=\"$db\">\n";
  echo "<input type=\"Submit\" name=\"select\" value=\"&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Export data records&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\">\n";
  echo "&nbsp;&nbsp;Export data collection entries in SDF or RDF format, respectively.\n";
  echo "</form></p>\n";

  echo "<hr />\n";

  echo"<p>&nbsp; </p>\n";
  echo "<h3>Add a new data collection with identical settings:</h3>\n";
  echo "<p><form action=\"${myname}\" method=post>\n";
  echo "<input type=\"hidden\" name=\"db\" value=\"$db\">\n";
  echo "<input type=\"hidden\" name=\"action\" value=\"clone\">\n";
  echo "<input type=\"Submit\" name=\"select\" value=\"&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Clone data collection&nbsp;&nbsp;&nbsp;&nbsp;\">\n";
  echo "&nbsp;&nbsp;Create a new, empty data collection with the same data fields as the currently selected one.\n";
  echo "</form></p>\n";


} // if ($db_id > 0)

//==============================================================================
// various functions


function add_database($dbname,$dbtype,$dbdescr,$dbaccess,$dbdigits,$dbsubdirdigits,$dbtrustedIP,$flags) {
  global $prefix;
  global $metatable;
  global $fpdeftable;
  global $molstrucsuffix;
  global $moldatasuffix; 
  global $molstatsuffix; 
  global $molfgbsuffix;  
  global $molcfpsuffix;  

  global $rxnstrucsuffix;
  global $rxndatasuffix; 
  global $rxncfpsuffix;  

  global $pic2dsuffix;   
  global $use_cmmmsrv;
  global $cmmmsrv_addr;
  global $cmmmsrv_port;
  global $CHECKMOL;
  
  $dbname  = trim($dbname);
  $dbname = substr($dbname,0,80);
  $dbname = mysql_real_escape_string($dbname);
  $dbtype = trim($dbtype);
  $dbtype_id = 1;
  if ($dbtype == 'RD') {
    $dbtype_id = 2;   // new
  }
  $dbdescr = trim($dbdescr);
  $dbdescr = mysql_real_escape_string($dbdescr);
  $dbaccess_id = 1;
  if ($dbaccess == 0) { $dbaccess_id = 0; }
  if ($dbaccess == 2) { $dbaccess_id = 2; }
  if ($dbaccess == 3) { $dbaccess_id = 3; }

  if ($dbdigits < 3)  { $dbdigits = 3; }
  if ($dbdigits > 30) { $dbdigits = 30; }
  if ($dbsubdirdigits < 0)  { $dbsubdirdigits = 0; }
  if ($dbsubdirdigits > $dbdigits) { $dbsubdirdigits = $dbdigits; }

  $dbtrustedIP = mysql_real_escape_string($dbtrustedIP);
  $dbtrustedIP = substr($dbtrustedIP,0,250);

  $retval = -1;
  echo "name: $dbname<br />\n";
  echo "type: $dbtype<br />\n";
  echo "description: $dbdescr<br />\n";

  $result3 = mysql_query("SELECT db_id, name FROM $metatable WHERE (name LIKE \"$dbname\")")
    or die("Query failed! (2)");
  $num_rows = mysql_num_rows($result3); 
  mysql_free_result($result3);
  if ($num_rows > 0) {
    $retval = 0;
  } else {
    // get the next available id
    $result4 = mysql_query("SELECT MAX(db_id) FROM $metatable")
      or die("Query failed! (3)");
    $row = mysql_fetch_row($result4);
    mysql_free_result($result4);
    $curr_max = $row[0];
    $next_id = $curr_max + 1;
    
    $qstr = "INSERT INTO `" . $metatable . "` (`db_id`, `type`, `access`, `name`, `description`, `usemem`, `digits`, `subdirdigits`, `trustedIP`, `flags`) VALUES ";
    $qstr = $qstr . "(" . $next_id . ", " . $dbtype_id . ", " . $dbaccess_id . ", '" . $dbname . "', '" . $dbdescr .
     "', 'F', '" . $dbdigits . "', '" . $dbsubdirdigits . "', '" . $dbtrustedIP . "', " . $flags . ");";

    //echo "$qstr<br />\n";

    $result5 = mysql_query($qstr)
      or die("Insert failed! (4)");
    #mysql_free_result($result5);

    // now create the tables
    if ($dbtype_id == 1) {
      create_moltables($next_id);
    } elseif($dbtype_id == 2) {
      create_rxntables($next_id);
    }    

    $retval = $next_id;  
  }
  return($retval);
}


//==============================================================================

// body text
?>


<hr />
<p>&nbsp; </p>
<h3>Add a new data collection with the following settings:</h3>
<form action="<?php echo "$myname";?>" method=post>

<table>
<tr><td><b>Name</b></td><td><input type="text" size="40" name="name"> max. 80 characters</td></tr>
<tr><td><b>Type</b></td><td><select size="1" name="type">
<option value="SD" selected>SD</option>
<option value="RD">RD</option> 
</select> <small>(SD = structure+data, RD = reaction+data)</small>
</td></tr>
<tr><td><b>Access</b></td><td><select size="1" name="access">
<option value="0">disabled</option>
<option value="1" selected>read-only</option>
<option value="2">add/update</option> 
<option value="3">full access</option> 
</select></td></tr>
<tr><td><b>Description</b></td><td><textarea cols="60" rows="3" name="descr"></textarea></td></tr>
<tr><td><b>Number of digits for bitmap files</b></td><td><input type="text" size="10" name="digits" value="8">
  (e.g. <b>8</b> for filenames like 00000123.png)</td></tr>
<tr><td><b>Number&nbsp;of&nbsp;digits&nbsp;for&nbsp;subdirectories&nbsp;</b></td><td><input type="text" size="10" name="subdirdigits" value="4">
  (e.g. <b>4</b> for something like 0002/00020123.png)</td></tr>
</td></tr>
<tr><td><b>Trusted IP addresses </b></td><td><textarea cols="60" rows="1" name="trustedip"></textarea><br />
<small>a comma-separated list of max. 10 IP addresses (for extended administrative privileges)</small></td></tr>
</table>
<input type="hidden" name="action" value="add">

<input type="Submit" name="add" value="Add new data collection">
</form>

<br />
<hr />
<br />
</body>
</html>
