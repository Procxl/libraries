<?php
// index.php        Norbert Haider, University of Vienna, 2009-2014
// part of MolDB6   last change: 2014-08-18

/**
 * @file index.php
 * @author Norbert Haider
 * 
 * This is the main page of a MolDB6 website. Here, the user can select
 * data collections and then navigate to the different search pages. A
 * link to the administration page appears at the bottom. For
 * site-specific customisation (text, logo, etc.), please edit the file 
 * custom-inc.php
 */

$myname = $_SERVER['PHP_SELF'];

// some defaults (will be overridden by config files
$sitename      = "Sristi Biosciences";
//$cssfilename   = "moldb.css";

include("moldb6conf.php");
include("moldb6uiconf.php");
require_once("functions.php");

if (config_quickcheck() > 0) { die(); }
set_charset($charset);

if (!isset($sitename) || ($sitename == "")) {
  $sitename = "Sristi Biosciences";
}

@$db   = $_REQUEST['db'];
@$dbl     = $_POST['dbl'];

$link = mysql_pconnect($hostname,"$ro_user", "$ro_password")
  or die("Could not connect to database server!");
mysql_select_db($database)
  or die("Could not select database!");    
mysql_query("SET NAMES $mysql_charset");

if (!isset($dbl)) {
  $dbl = array();
  if ((!isset($db) || ($db == ""))) {
    $dbl = explode(",",$default_db);
  } else {
    $dbl = explode(",",$db);
  }
}



$dba = array();
$dbstr = "";

$ndbsel = 0;
foreach ($dbl as $id) {
  $db_id = check_db($id);
  if (($db_id > 0) && (($ndbsel < 1) || ($multiselect == "y"))) {
    $ndbsel++;
    $dba[($ndbsel - 1)] = $dbl[($ndbsel - 1)];
    if (strlen($dbstr)>0) { $dbstr .= ","; }
    $dbstr .= "$db_id";
  }
}

if ($ndbsel < 1) {
  $dbfb   = get_fallbackdbid();
  if ($dbfb > 0) {
    $ndbl = 1;
    $dba[0] = $dbfb;
    $ndbsel = 1;
    $dbstr = "$dbfb";
    $db_id = $dbfb;
  }
}

//echo "$dba[0] $dbl[1] ($ndbsel): $dbstr<br>";

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=<?php echo "$html_charset"; ?>">
<script src="js/jquery-1.10.2.js"></script>
<script src="js/jquery-ui-1.10.4.min.js"></script>
<script src="js/bootstrap.min.js"></script>

<link href="css/bootstrap.css" rel="stylesheet">
<link href="css/font-awesome.min.css" rel="stylesheet">
<link href="css/jquery-ui-1.10.4.min.css" rel="stylesheet">

<title><?php echo "$sitename"; ?></title>
<?php insert_style($cssfilename); ?>
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
     // show_header($myname,$dbstr);
      echo "<h2><span style=\"color:Purple\">$sitename</span></h2>\n";
      //include("custom-inc.php");

    if ($enablereactions == "y") { $onlysd = ""; } else { $onlysd = " AND (type = 1) "; }

    $result = mysql_query("SELECT db_id, name, type FROM $metatable WHERE (access > 0) $onlysd ORDER BY db_id")
      or die("Query failed! (1)");
    $ndb = mysql_num_rows($result);
    $db_id = $dba[0];

    if ($ndb == 0) {
      echo "There is no data collection available in the moment. The administrator can add ";
      echo "new collections via the <a href=\"admin/\" target=\"blank\">administration page</a> ";
      echo "or via import of SD files.<p />\n";
    } elseif ($multiselect == "n") {
      echo "<h3>Available data collections:</h3>\n";
      echo "<form action=\"$myname\" method=\"post\">\n";
      echo "<select size=\"1\" name=\"db\">\n";
      while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
        $db   = $line["db_id"];
        $name = $line["name"];
        $dbtype = $line["type"];
        echo "<option value=\"$db\"";
        if ($db == $db_id) { echo " selected"; }
        $typestr = "";
        if ($enablereactions == "y") { 
          if ($dbtype == 1) { $typestr = " [S]"; }
          if ($dbtype == 2) { $typestr = " [R]"; }
        }
        echo ">${db}${typestr}: $name</option>\n";
      }
      mysql_free_result($result);
      echo "</select>\n";
      echo "<input type=\"Submit\" name=\"select\" value=\"Apply selection\">\n";
      echo "</form>\n";
      if ($enablereactions == "y") { 
        echo "<small>[S] = structures,  [R] = reactions</small><br>\n";
      }
    } else {         // multiselect
      if ($ndb <= 5) { $maxlines = $ndb; } else { $maxlines = 5; }
      echo "<h3><span style=\"color:LightSeaGreen\">Available data collections:</span></h3>\n";
      echo "<form action=\"$myname\" method=\"post\">\n";
      echo "<select size=\"$maxlines\" name=\"dbl[]\" multiple>\n";
      while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
        $db   = $line["db_id"];
        $name = $line["name"];
        $dbtype = $line["type"];
        echo "<option value=\"$db\"";
        if (in_array($db,$dba)) { echo " selected"; }
        $typestr = "";
        if ($enablereactions == "y") { 
          if ($dbtype == 1) { $typestr = " [S]"; }
          if ($dbtype == 2) { $typestr = " [R]"; }
        }
        echo ">${db}${typestr}: $name</option>\n";
      }
      mysql_free_result($result);
      echo "</select>\n";
      echo "<input type=\"Submit\" name=\"select\" value=\"Apply selection\">\n";
      echo "</form>\n";
      if ($enablereactions == "y") { 
        echo "<medium>[S] = structures,  [R] = reactions</medium><br>\n";
      }
    }


    echo "<br />\n";

    if ($ndb > 0) {
      echo "<table width=\"100%\" class=\"highlight\">\n";
      echo "<tr align=\"left\"><th><h4>Current&nbsp;selection:</h4></th><th></th></tr>\n";

      $qstr = "SELECT db_id, name, description FROM $metatable WHERE ";
      for ($i = 0; $i < $ndbsel; $i++) {
        if ($i > 0) { $qstr .= " OR"; }
        $qstr .= " (db_id = " . $dba[$i] . ")";
      }
      $qstr .= " ORDER BY db_id";

      $result2 = mysql_query($qstr)
        or die("Query failed! (2)");
      while ($line2 = mysql_fetch_array($result2, MYSQL_ASSOC)) {
        $db   = $line2["db_id"];
        $name = $line2["name"];
        $description = $line2["description"];
        echo "<tr align=\"left\" valign=\"top\"><td><b>$name</b></td><td>$description</td></tr>\n";
      }
      mysql_free_result($result2);

    echo "</table>\n";
    }  // if ($ndb > 0) ...

    // new in Jan 2014 edition: choice of structure editors
    // via cookie-based preferences
    if ($enable_prefs == "y") {
      mkprefscript();
    }
    ?>
   <!-- <p>&nbsp;</p>

    <hr>
    <small>MolDB6 2014</small>
    <br />
-->
</body>
</html>
