<?php
// admin/dbfunct.php    Norbert Haider, University of Vienna, 2010-2013
// a collection of database functions for MolDB5R, last change: 2014-06-17

/**
 * @file admin/dbfunct.php
 * @author Norbert Haider
 *
 * A collection of database functions for MolDB5R
 */

function check_db_all($id) {
  global $metatable;
  $db_id = -1;
  if (is_numeric($id)) {
    $result = mysql_query("SELECT db_id, name FROM $metatable WHERE (db_id = $id)")
      or die("Query failed! (1)");
    while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
      $db_id = $line["db_id"];
    }
    mysql_free_result($result);
  }
  if ($db_id == -1) {   // check if there is any data collection at all
    $result = mysql_query("SELECT COUNT(db_id) AS dbcount FROM $metatable")
      or die("Query failed! (2)");
    $dbcount = 0;
    while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
      $dbcount = $line["dbcount"];
    }
    mysql_free_result($result);
    if ($dbcount == 0) { $db_id = 0; }
  }
  return($db_id);
}

function unregister_db($kill_db) {
  global $metatable;
  $result  = 0;
  $killstr = "DELETE FROM " . $metatable . " WHERE db_id = " . $kill_db;
  mysql_query($killstr);
  $result = mysql_affected_rows();
  return($result);
}

function set_memstatus_dirty($id) {
  global $metatable;
  $qstr = "UPDATE $metatable SET memstatus = 0 WHERE db_id = $id";
  $result = mysql_query($qstr);
  $err = 0;
  $err = mysql_errno();
  return($err);
}

function drop_moltables($kill_db) {
  global $prefix;
  global $molstrucsuffix;
  global $moldatasuffix;
  global $molfgbsuffix;
  global $molstatsuffix;
  global $molcfpsuffix;
  global $pic2dsuffix;
  global $memsuffix;

  $result = "";
  $dbprefix = $prefix . "db" . $kill_db . "_";
  $killstr = "DROP TABLE IF EXISTS ";
  $killstr .= $dbprefix . $molstrucsuffix . ", ";
  $killstr .= $dbprefix . $moldatasuffix . ", ";
  $killstr .= $dbprefix . $molfgbsuffix . ", ";
  $killstr .= $dbprefix . $molstatsuffix . ", ";
  $killstr .= $dbprefix . $molstatsuffix . $memsuffix . ", ";
  $killstr .= $dbprefix . $molcfpsuffix . ", ";
  $killstr .= $dbprefix . $molcfpsuffix . $memsuffix . ", ";
  $killstr .= $dbprefix . $pic2dsuffix;
  mysql_query($killstr);
  $result = mysql_error();
  return($result);
}

function drop_rxntables($kill_db) {
  global $prefix;
  global $rxnstrucsuffix;
  global $rxndatasuffix;
  global $rxncfpsuffix;
  global $rxnfgbsuffix;
  global $pic2dsuffix;

  $result = "";
  $dbprefix = $prefix . "db" . $kill_db . "_";
  $killstr = "DROP TABLE IF EXISTS ";
  $killstr .= $dbprefix . $rxnstrucsuffix . ", ";
  $killstr .= $dbprefix . $rxndatasuffix . ", ";
  $killstr .= $dbprefix . $rxnfgbsuffix . ", ";
  $killstr .= $dbprefix . $rxncfpsuffix . ", ";
  $killstr .= $dbprefix . $pic2dsuffix;  // 5R.10
  mysql_query($killstr);
  $result = mysql_error();
  return($result);
}

function create_moltables($db_id) {
  global $fpdeftable;
  global $prefix;
  global $molstrucsuffix;
  global $moldatasuffix;
  global $molfgbsuffix;
  global $molstatsuffix;
  global $molcfpsuffix;
  global $pic2dsuffix;
  global $use_cmmmsrv;
  global $cmmmsrv_addr;
  global $cmmmsrv_port;
  global $CHECKMOL;
  global $socket;
  global $mysql_charset;
  global $mysql_collation;
  global $ostype;
  global $enable_inchi;

  $dbprefix = $prefix . "db" . $db_id . "_";
  //molstruc
  $createcmd = "CREATE TABLE IF NOT EXISTS ${dbprefix}$molstrucsuffix (";
  $createcmd .= "mol_id INT(11) NOT NULL DEFAULT '0', struc MEDIUMBLOB NOT NULL, PRIMARY KEY mol_id (mol_id)";
  $createcmd .= ") ENGINE = MYISAM CHARACTER SET $mysql_charset COLLATE $mysql_collation COMMENT='Molecular structures'";
  $result = mysql_query($createcmd)
    or die("Create failed! (create_moltables 1)");
  #mysql_free_result($result);
  
  //moldatatable
  $inchistr = "";
  if ($enable_inchi == "y") {
    $inchistr = "auto_mol_inchikey VARCHAR(250) NOT NULL COMMENT '>>>>InChIKey<1<inchikey<1<<',";
  }
  $createcmd = "CREATE TABLE IF NOT EXISTS ${dbprefix}$moldatasuffix (";
  $createcmd .= "mol_id INT(11) NOT NULL DEFAULT '0', mol_name TEXT NOT NULL, auto_mol_formula VARCHAR(250) NOT NULL COMMENT '>>>>Formula<3<auto_mol_formula<0<<', 
  auto_mol_fw DOUBLE NOT NULL DEFAULT '0.0' COMMENT '>>>>MW<1<auto_mol_fw<0<<', $inchistr PRIMARY KEY mol_id (mol_id)";
  $createcmd .= ") ENGINE = MYISAM CHARACTER SET $mysql_charset COLLATE $mysql_collation COMMENT='Molecular data'";
  $result = mysql_query($createcmd)
    or die("Create failed! (4b)");
  #mysql_free_result($result);

  //molstattable
  if ($use_cmmmsrv == 'y') {
    $msdef = filterthroughcmmm("\$\$\$\$","#### checkmol:l");
  } else {
    if ($ostype == 1) {$msdef = filterthroughcmd("","$CHECKMOL -l"); }
    if ($ostype == 2) {$msdef = filterthroughcmd2("","$CHECKMOL -l"); }
  }
  $createcmd = "CREATE TABLE IF NOT EXISTS ${dbprefix}$molstatsuffix (
mol_id int(11) NOT NULL DEFAULT '0', \n";
  $msdef = rtrim($msdef);    
  $msline = explode("\n",$msdef);
  $nfields = count($msline);
  foreach ($msline as $line) {
    $element = explode(":",$line);
    $createcmd = $createcmd . "  $element[0]" . " SMALLINT(6) NOT NULL DEFAULT '0',\n";
  }  
  $createcmd = $createcmd . "  PRIMARY KEY  (mol_id)
) ENGINE = MYISAM COMMENT='Molecular statistics';";

  //echo "<pre>$createcmd</pre>\n";
  $result = mysql_query($createcmd)
    or die("Create failed! (4c)");
  #mysql_free_result($result);

  //molfgbtable
  $createcmd = "CREATE TABLE IF NOT EXISTS ${dbprefix}$molfgbsuffix (mol_id INT(11) NOT NULL DEFAULT '0', 
  fg01 INT(11) UNSIGNED NOT NULL,
  fg02 INT(11) UNSIGNED NOT NULL,
  fg03 INT(11) UNSIGNED NOT NULL,
  fg04 INT(11) UNSIGNED NOT NULL,
  fg05 INT(11) UNSIGNED NOT NULL,
  fg06 INT(11) UNSIGNED NOT NULL,
  fg07 INT(11) UNSIGNED NOT NULL,
  fg08 INT(11) UNSIGNED NOT NULL,
  n_1bits SMALLINT NOT NULL,
  PRIMARY KEY mol_id (mol_id)) ENGINE = MYISAM COMMENT='Functional group patterns'";
  $result6 = mysql_query($createcmd)
    or die("Create failed! (4d)");
  #mysql_free_result($result6);

  //molcfptable
  //  first step: analyse the fingerprint dictionary (how many dict.?
  $createstr = "";
  $n_dict = 0;
  $result = mysql_query("SELECT fp_id, fpdef, fptype FROM $fpdeftable")
    or die("Query failed! (fpdef)");
  $fpdef  = "";
  $fptype = 1;
  while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
    $fp_id  = $line["fp_id"];
    $fpdef  = $line["fpdef"];
    $fptype = $line["fptype"];

    if (strlen($fpdef)>20) {
      $n_dict++;
      $dictnum = $n_dict;
      while (strlen($dictnum) < 2) { $dictnum = "0" . $dictnum;  }
      if ($fptype == 1) {
        $createstr .= "  dfp$dictnum BIGINT NOT NULL,\n";
      } else {
        $createstr .= "  dfp$dictnum INT(11) UNSIGNED NOT NULL,\n";
      }
    }
  }
  mysql_free_result($result);
  $createstr = trim($createstr);
  if ($n_dict < 1) {
    die("ERROR: could not retrieve fingerprint definition from table $fpdeftable\n");
  }
  $tblname = $dbprefix . $molcfpsuffix;
  $idname = "mol_id";
  $keystr = "PRIMARY KEY mol_id (mol_id)";

  $createcmd = "CREATE TABLE IF NOT EXISTS $tblname 
  ($idname INT(11) NOT NULL DEFAULT '0', $createstr
  hfp01 INT(11) UNSIGNED NOT NULL,
  hfp02 INT(11) UNSIGNED NOT NULL,
  hfp03 INT(11) UNSIGNED NOT NULL,
  hfp04 INT(11) UNSIGNED NOT NULL,
  hfp05 INT(11) UNSIGNED NOT NULL,
  hfp06 INT(11) UNSIGNED NOT NULL,
  hfp07 INT(11) UNSIGNED NOT NULL,
  hfp08 INT(11) UNSIGNED NOT NULL,
  hfp09 INT(11) UNSIGNED NOT NULL,
  hfp10 INT(11) UNSIGNED NOT NULL,
  hfp11 INT(11) UNSIGNED NOT NULL,
  hfp12 INT(11) UNSIGNED NOT NULL,
  hfp13 INT(11) UNSIGNED NOT NULL,
  hfp14 INT(11) UNSIGNED NOT NULL,
  hfp15 INT(11) UNSIGNED NOT NULL,
  hfp16 INT(11) UNSIGNED NOT NULL,
  n_h1bits SMALLINT NOT NULL, $keystr) 
  ENGINE = MYISAM COMMENT='Combined dictionary-based and hash-based fingerprints'";
  $result6 = mysql_query($createcmd)
    or die("Create failed! (4e)");
  #mysql_free_result($result6);

  //pic2dtable
  $createcmd = "CREATE TABLE ${dbprefix}$pic2dsuffix (
  `mol_id` INT(11) NOT NULL DEFAULT '0',
  `type` TINYINT NOT NULL DEFAULT '1' COMMENT '1 = png',
  `status` TINYINT NOT NULL DEFAULT '0' COMMENT '0 = does not exist, 1 = OK, 2 = OK, but do not show, 3 = to be created/updated, 4 = to be deleted',
  `svg` BLOB NOT NULL,
  PRIMARY KEY (mol_id)
  ) ENGINE = MYISAM CHARACTER SET $mysql_charset COLLATE $mysql_collation COMMENT='Housekeeping for 2D depiction'";
  $result6 = mysql_query($createcmd)
    or die("Create failed! (4f)");
  #mysql_free_result($result6);
}

function create_rxntables($db_id) {
  global $fpdeftable;
  global $prefix;
  global $rxnstrucsuffix;
  global $rxndatasuffix;
  global $rxncfpsuffix;
  global $rxnfgbsuffix;
  global $pic2dsuffix;
  global $mysql_charset;
  global $mysql_collation;
  
  $dbprefix = $prefix . "db" . $db_id . "_";

  //rxnstruc
  $createcmd = "CREATE TABLE IF NOT EXISTS ${dbprefix}$rxnstrucsuffix (";
  $createcmd .= "rxn_id INT(11) NOT NULL DEFAULT '0', struc MEDIUMBLOB NOT NULL, ";
  $createcmd .= "map TEXT CHARACTER SET $mysql_charset COLLATE $mysql_collation NOT NULL, PRIMARY KEY rxn_id (rxn_id)";
  $createcmd .= ") ENGINE = MYISAM CHARACTER SET $mysql_charset COLLATE $mysql_collation COMMENT='Reaction structures'";
  $result6 = mysql_query($createcmd)
    or die("Create failed! (rxnstructable)");

  //rxndata
  $createcmd = "CREATE TABLE IF NOT EXISTS ${dbprefix}$rxndatasuffix (";
  $createcmd .= "rxn_id INT(11) NOT NULL DEFAULT '0', rxn_name TEXT NOT NULL, PRIMARY KEY rxn_id (rxn_id)";
  $createcmd .= ") ENGINE = MYISAM CHARACTER SET $mysql_charset COLLATE $mysql_collation COMMENT='Reaction data'";
  $result6 = mysql_query($createcmd)
    or die("Create failed! (rxndatatable)");

  //rxnfgbtable
  $createcmd = "CREATE TABLE IF NOT EXISTS ${dbprefix}$rxnfgbsuffix (rxn_id INT(11) NOT NULL DEFAULT '0', 
  role CHAR(1) NOT NULL,
  fg01 INT(11) UNSIGNED NOT NULL,
  fg02 INT(11) UNSIGNED NOT NULL,
  fg03 INT(11) UNSIGNED NOT NULL,
  fg04 INT(11) UNSIGNED NOT NULL,
  fg05 INT(11) UNSIGNED NOT NULL,
  fg06 INT(11) UNSIGNED NOT NULL,
  fg07 INT(11) UNSIGNED NOT NULL,
  fg08 INT(11) UNSIGNED NOT NULL,
  n_1bits SMALLINT NOT NULL,
  PRIMARY KEY rxn_id (rxn_id,role)) ENGINE = MYISAM COMMENT='Summarized functional group patterns'";
  $result6 = mysql_query($createcmd)
    or die("Create failed! (rxnfgbtable)");

  //rxncfptable
  //  first step: analyse the fingerprint dictionary (how many dict.?
  $createstr = "";
  $n_dict = 0;
  $result = mysql_query("SELECT fp_id, fpdef, fptype FROM $fpdeftable")
    or die("Query failed! (fpdef)");
  $fpdef  = "";
  $fptype = 1;
  while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
    $fp_id  = $line["fp_id"];
    $fpdef  = $line["fpdef"];
    $fptype = $line["fptype"];
    if (strlen($fpdef)>20) {
      $n_dict++;
      $dictnum = $n_dict;
      while (strlen($dictnum) < 2) { $dictnum = "0" . $dictnum;  }
      if ($fptype == 1) {
        $createstr .= "  dfp$dictnum BIGINT NOT NULL,\n";
      } else {
        $createstr .= "  dfp$dictnum INT(11) UNSIGNED NOT NULL,\n";
      }
    }
  }
  mysql_free_result($result);
  $createstr = trim($createstr);
  if ($n_dict < 1) {
    die("ERROR: could not retrieve fingerprint definition from table $fpdeftable\n");
  }
  $tblname = $dbprefix . $rxncfpsuffix;
  $idname = "rxn_id";
  $keystr = "PRIMARY KEY rxn_id (rxn_id,role)";
  $createstr = "role CHAR(1) NOT NULL, " . $createstr;
  $createcmd = "CREATE TABLE IF NOT EXISTS $tblname 
  ($idname INT(11) NOT NULL DEFAULT '0', $createstr
  hfp01 INT(11) UNSIGNED NOT NULL,
  hfp02 INT(11) UNSIGNED NOT NULL,
  hfp03 INT(11) UNSIGNED NOT NULL,
  hfp04 INT(11) UNSIGNED NOT NULL,
  hfp05 INT(11) UNSIGNED NOT NULL,
  hfp06 INT(11) UNSIGNED NOT NULL,
  hfp07 INT(11) UNSIGNED NOT NULL,
  hfp08 INT(11) UNSIGNED NOT NULL,
  hfp09 INT(11) UNSIGNED NOT NULL,
  hfp10 INT(11) UNSIGNED NOT NULL,
  hfp11 INT(11) UNSIGNED NOT NULL,
  hfp12 INT(11) UNSIGNED NOT NULL,
  hfp13 INT(11) UNSIGNED NOT NULL,
  hfp14 INT(11) UNSIGNED NOT NULL,
  hfp15 INT(11) UNSIGNED NOT NULL,
  hfp16 INT(11) UNSIGNED NOT NULL,
  n_h1bits SMALLINT NOT NULL, $keystr) 
  ENGINE = MYISAM COMMENT='Combined dictionary-based and hash-based fingerprints'";
  $result6 = mysql_query($createcmd)
    or die("Create failed! (4e)");
  #mysql_free_result($result6);

  //pic2dtable, added in 5R.10
  $createcmd = "CREATE TABLE ${dbprefix}$pic2dsuffix (
  `rxn_id` INT(11) NOT NULL DEFAULT '0',
  `type` TINYINT NOT NULL DEFAULT '1' COMMENT '1 = png',
  `status` TINYINT NOT NULL DEFAULT '0' COMMENT '0 = does not exist, 1 = OK, 2 = OK, but do not show, 3 = to be created/updated, 4 = to be deleted',
  `svg` BLOB NOT NULL,
  PRIMARY KEY (rxn_id)
  ) ENGINE = MYISAM CHARACTER SET $mysql_charset COLLATE $mysql_collation COMMENT='Housekeeping for 2D depiction'";
  $result6 = mysql_query($createcmd)
    or die("Create failed! (4f)");
  #mysql_free_result($result6);
}

function get_numentries($db_id,$dbtype_id) {
  global $prefix;
  global $molstrucsuffix;
  global $rxnstrucsuffix;

  $n_entries = 0;
  $table = "";
  $dbprefix = $prefix . "db" . $db_id . "_";
  if ($dbtype_id == 1) {
    $table = $dbprefix . $molstrucsuffix;
    $idname = "mol_id";
  } elseif ($dbtype_id == 2) {
    $table = $dbprefix . $rxnstrucsuffix;
    $idname = "rxn_id";
  }
  if (strlen($table) > 0) {
    $result1 = mysql_query("SELECT COUNT($idname) AS entrycount FROM $table")
      or die("Query failed! (get_numentries)");
    $line1 = mysql_fetch_row($result1);
    mysql_free_result($result1);
    $n_entries = $line1[0];
  }
  return($n_entries);
}

function get_next_mol_id($db_id) {
  global $prefix;
  global $molstrucsuffix;
  
  $result = 0;
  $dbprefix      = $prefix . "db" . $db_id . "_";
  $molstructable = $dbprefix . $molstrucsuffix;

  $result1 = mysql_query("SELECT COUNT(mol_id) AS molcount FROM $molstructable")
    or die("Query failed! (get_next_mol_id #1)");
  $line = mysql_fetch_row($result1);
  mysql_free_result($result1);
  $molcount = $line[0];
  if ($molcount == 0) { 
    $result = 1; 
  } else {
    $result1 = mysql_query("SELECT MAX(mol_id) AS molcount FROM $molstructable")
      or die("Query failed! (get_next_mol_id #2)");
    $line = mysql_fetch_row($result1);
    mysql_free_result($result1);
    $molcount = $line[0];
    $result = $molcount + 1;
  }
  return($result);
}

function get_next_rxn_id($db_id) {
  global $prefix;
  global $rxnstrucsuffix;
  
  $result = 0;
  $dbprefix      = $prefix . "db" . $db_id . "_";
  $rxnstructable = $dbprefix . $rxnstrucsuffix;

  $result1 = mysql_query("SELECT COUNT(rxn_id) AS rxncount FROM $rxnstructable")
    or die("Query failed! (get_next_rxn_id #1)");
  $line = mysql_fetch_row($result1);
  mysql_free_result($result1);
  $rxncount = $line[0];
  if ($rxncount == 0) { 
    $result = 1; 
  } else {
    $result1 = mysql_query("SELECT MAX(rxn_id) AS rxncount FROM $rxnstructable")
      or die("Query failed! (get_next_rxn_id #2)");
    $line = mysql_fetch_row($result1);
    mysql_free_result($result1);
    $rxncount = $line[0];
    $result = $rxncount + 1;
  }
  return($result);
}


function tweak_svg($testsvg,$scaling) {
  global $keep_xydata;
  $svgline = explode("\n", $testsvg);
  $xmaxval = "";
  $ymaxval = "";
  $yminval = "";
  $ytrval = "";
  for ($i = 0; $i < count($svgline); $i++) {
    $testline = $svgline[$i];
    $testline = rtrim($testline);
    if ((strpos($testline,"<!-- max_X:") !== FALSE) && (strpos($testline,"-->") !== FALSE)) {
      $xline = explode(":", $testline);
      $xmaxval = $xline[1];
      $xmaxval = str_replace("-->","",$xmaxval);
      $xmaxval = trim($xmaxval);
      if ($keep_xydata == 0) { $svgline[$i] = ""; }
    }
    if ((strpos($testline,"<!-- max_Y:") !== FALSE) && (strpos($testline,"-->") !== FALSE)) {
      $yline = explode(":", $testline);
      $ymaxval = $yline[1];
      $ymaxval = str_replace("-->","",$ymaxval);
      $ymaxval = trim($ymaxval);
      if ($keep_xydata == 0) { $svgline[$i] = ""; }
    }
    if ((strpos($testline,"<!-- min_Y:") !== FALSE) && (strpos($testline,"-->") !== FALSE)) {
      $yline = explode(":", $testline);
      $yminval = $yline[1];
      $yminval = str_replace("-->","",$yminval);
      $yminval = trim($yminval);
      if ($keep_xydata == 0) { $svgline[$i] = ""; }
    }
    if ((strpos($testline,"<!-- yshift:") !== FALSE) && (strpos($testline,"-->") !== FALSE)) {
      $yline = explode(":", $testline);
      $ytrval = $yline[1];
      $ytrval = str_replace("-->","",$ytrval);
      $ytrval = trim($ytrval);
      if ($keep_xydata == 0) { $svgline[$i] = ""; }
    }
    if ((strpos($testline,"<!-- found XY values for adjusting") !== FALSE) && (strpos($testline,"-->") !== FALSE)) {
      if ($keep_xydata == 0) { $svgline[$i] = ""; }
    }
  }  # for
  if ((strlen($xmaxval) > 0) && (strlen($ymaxval) > 0) && (strlen($yminval) > 0) && (strlen($ytrval) > 0)) {
    $ymaxtotal   = $ymaxval + $ytrval;
    $ymintotal   = $yminval + $ytrval;
    $ydiff       = $ymaxtotal - $ymintotal;
    $ydiffscaled = $ydiff * $scaling;
    $xmaxscaled  = $xmaxval * $scaling;
    $ymaxscaled  = $ymaxtotal * $scaling;
    $yminscaled  = $ymintotal * $scaling;
    $svgline[1] = "<svg width=\"$xmaxscaled\" height=\"$ydiffscaled\" viewbox=\"0 $ymintotal $xmaxval $ydiff\" xmlns=\"http://www.w3.org/2000/svg\">";
  }
  $twsvg = implode("\n",$svgline);
  $twsvg = str_replace("\n\n\n","",$twsvg);
  return($twsvg);
}


?>
