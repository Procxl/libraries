<?php
// hits2sdf.php          Norbert Haider, University of Vienna, 2011-2014
// part of MolDB6        last change: 2014-04-24

/**
 * @file hits2sdf.php
 * @author Norbert Haider
 *
 * Script exports hit structures as SD files
 * 
 */


$download_limit       = 100;  // maximum number of entries we allow to export in one file
                              // finally, this value should be set in moldb6conf.php

$myname = $_SERVER['PHP_SELF'];
@include("moldb6conf.php");    // if moldb6conf.php is in the PHP include path
@include("moldb6uiconf.php");
require_once("functions.php");

/**
 * Debug level
 * - 0:            use correct MIME type (chemical/x-mdl-sdfile or chemical/x-mdl-rdfile)
 * - 1:            use text/plain as Content-Type
 */
$debug = 0;

if (config_quickcheck() > 0) { die(); }
set_charset($charset);

if (!isset($sitename) || ($sitename == "")) {
  $sitename = "Sristi Biosciences";
}

#@$db_id    = $_REQUEST['db'];
@$action   = $_POST['action'];
@$hits     = $_POST['hits'];

$link = mysql_pconnect($hostname,"$rw_user", "$rw_password")
  or die("Could not connect to database server!");
mysql_select_db($database)
  or die("Could not select database!");    
mysql_query("SET NAMES $mysql_charset");

$dbtype = 1;

if ($debug == 0) {
  if ($dbtype == 1) { 
    header("Content-Type: chemical/x-mdl-sdfile"); 
    header("Content-Disposition: filename=hitlist.sdf");
  }
  if ($dbtype == 2) { 
    header("Content-Type: chemical/x-mdl-rdfile"); 
    header("Content-Disposition: filename=hitlist.rdf");
  }
} else {
  header("Content-Type: text/plain");
  header("Content-Disposition: filename=export.txt");
}

//echo "$hits\n";

if (strlen($hits) > 0) {
  $hit_arr = explode(",",$hits);
  $hit = "";
  $for_limit = count($hit_arr);  // number of requested hit structures
  if ($for_limit > $download_limit) { $for_limit = $download_limit; }
  for ($i = 0; $i < $for_limit; $i++) {
    $hit = $hit_arr[$i];
    //echo "$hit\n";
    $rec_arr = explode(":",$hit);
    $db_id = $rec_arr[0];
    $hit_id = $rec_arr[1];
    if (is_numeric($db_id) && ($db_id > 0)) {
      $dbprefix      = $prefix . "db" . $db_id . "_";
      $molstructable = $dbprefix . $molstrucsuffix;
      $qstr   = "SELECT struc FROM $molstructable WHERE mol_id = $hit_id";
      $result = mysql_query($qstr)
        or die("Query failed! (hits2sdf #1)");    
      $rows  = mysql_num_rows($result);     // number of candidate structures
      if ($rows > 0) {
        while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
          $struc   = $line["struc"];
          $struc = chop($struc);
          echo "$struc\n";
          echo "\$\$\$\$\n";
        }  // while
      }  // if $rows > 0
    }
  }
}
