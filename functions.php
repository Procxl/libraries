<?php 
// functions.php    Norbert Haider, University of Vienna, 2009-2014
// a collection of common functions for MolDB6, last change: 2014-07-28

/**
 * @file functions.php
 * @author Norbert Haider
 *
 * A collection of common functions for MolDB6
 */

function getmicrotime() {
  list($usec, $sec) = explode(" ", microtime());
  return ((float)$usec + (float)$sec);
}

function getostype() {
  if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $ostype = 2;   // Windows
  } else {
    $ostype = 1;  // Linux, Mac OS X
  }
  return $ostype;
}

function insert_style($cssfilename) {
  // depending on the browser, insert a link to the specified CSS file
  // or read its content and place it in an inline <style> </style> section;
  // background: IE9 does not like external CSS links together with inline SVG
  // note: if a path is included, it must be RELATIVE
  $user_agent = $_SERVER['HTTP_USER_AGENT'];
  if (eregi("msie",$user_agent)) {
    echo "<meta http-equiv=\"X-UA-Compatible\" content=\"IE=9\"/>\n";
    $mystyle = "";
    $mycssfile = fopen($cssfilename, "rb");
    $mystyle = fread($mycssfile,filesize($cssfilename));
    fclose($mycssfile);
    if (strlen($mystyle) > 0) {
      print "<style>\n${mystyle}\n</style>\n";
    }
  } else {
    echo "<link href=\"${cssfilename}\" rel=\"stylesheet\" type=\"text/css\">\n";
  }
}

function show_header_old($myname,$dbstr) {
  global $enablereactions;
  global $enable_prefs;
  global $db_id;
  $item = array();
  $item[0][0] = "index.php";    $item[0][1] = "Home";
  $item[1][0] = "moldblst.php"; $item[1][1] = "Browse";
  $item[2][0] = "moldbtxt.php"; $item[2][1] = "Text Search";
  $item[3][0] = "moldbfg.php";  $item[3][1] = "Functional Group Search";
  $item[4][0] = "moldbsss.php"; $item[4][1] = "libraries Search";
  if ($enablereactions == "y") {
    $item[5][0] = "moldbrss.php"; $item[5][1] = "Reaction Search";
    $nitems = 6;  
  } else { $nitems = 5; }
  echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\"><tr><td align=\"left\">";
  for ($i = 0; $i < $nitems; $i++) {
    $url   = $item[$i][0];
    $label = $item[$i][1];
    //echo "$i: $url : $label <br>\n";
    $pos = strpos($myname,$url);
    if ($pos !== false) {
      //echo "[$label]";
      echo "<b style=\"color:lightgrey;\">&nbsp;${label}&nbsp;</b>";
    } else {
      //echo "[<a href=\"${url}?db=${dbstr}\">$label</a>]";
      echo "<a class=\"menu\" href=\"${url}?db=${dbstr}\">$label</a>";
    }
  }
  echo "</td><td align=\"right\"\n";
  if ($enable_prefs == "y") {
    echo "<div id=\"settings\" onclick=\"openprefs()\"><a class=\"menu\" href=\"\">Preferences</a></div>\n";
  }
  echo "</td><td align=\"right\"><a class=\"menu\" href=\"admin/?db=$db_id\" target=\"admin\">Administration</a>";
  echo "</td></tr></table><hr />\n";
  echo "<small><span style=\"color:Red\">selected data collection: $dbstr</span></small><p />\n";
}

function show_header($myname,$dbstr) {
  global $enablereactions;
  global $enable_prefs;
  global $enable_adminlink;
  global $db_id;
  //TODO - 12/Sep/2014 - Should identify how to map with relative folder path, currently hardcoded the folder name 'libraries'
  $item = array();
  $item[0][0] = "index.php";    $item[0][1] = "Home";
  $item[1][0] = "/libraries/moldblst.php"; $item[1][1] = "Browse";
  $item[2][0] = "/libraries/moldbtxt.php"; $item[2][1] = "Text Search";
  $item[3][0] = "/libraries/moldbfg.php";  $item[3][1] = "Functional Group Search";
  $item[4][0] = "/libraries/moldbgss.php"; $item[4][1] = "libraries Search";
  //$item[5][0] = "openprefs();"; //$item[5][1] = "Preferences";
  $item[5][0] = "/libraries/admin/?db=$db_id"; $item[5][1] = "Administration";

  $nitems = 6; 
  
  //echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\"><tr><td align=\"left\">";
  $home = false;
  echo "<ul class='nav nav-pills pull-left'>";
  for ($i = 0; $i < $nitems; $i++) {
    $url   = $item[$i][0];
    $label = $item[$i][1];
    //echo "$i: $url : $label <br>\n";
    $pos = strpos($myname,$url);
    if ($pos !== false) {
      //echo "[$label]";
      //echo "<b style=\"color:blue;\">&nbsp;${label}&nbsp;</b>";
      echo "<li class='active'><a href=''>${label}</a></li>";
      if ($label == "Home") { $home = true; }

    } else {
      //echo "[<a href=\"${url}?db=${dbstr}\">$label</a>]";
      //echo "<a class=\"menu\" href=\"${url}?db=${dbstr}\">$label</a>";
      //TODO: Should implement the popup for multiple libraries editors - 3/Sep/2014
        echo "<li><a href='${url}?db=${dbstr}'>$label</a></li>";
    }
  }
  echo "</ul>";
  echo "<br/>";
  echo "<br/>";
  //echo "<hr/>\n";
  //echo "<h4><span style=\"color:red\">selected data collection: $dbstr</span></h4><p/>\n";
}

function filterthroughcmmm($input,$commandline) {
  global $socket;
  $input = $commandline . "\n" . $input . "####" . "\n";
  socket_write ($socket, $input, strlen ($input));
  $output = '';
  $a = '';
  while (($a = socket_read($socket, 250, PHP_NORMAL_READ)) && (strpos($a,'####') === false)) {
    if (strpos($a,'####') === false) {
      $output = $output . $a;
    }
  }
  return $output;
}

function filterthroughcmd($input, $commandLine) {
  $pipe = popen("echo \"$input\"|$commandLine" , 'r');
  if (!$pipe) {
    print "pipe failed.";
    return "";
  }
  $output = '';
  while(!feof($pipe)) {
    $output .= fread($pipe, 1024);
  }
  pclose($pipe);
  return $output;
}

function filterthroughcmd2($input, $commandLine) {     // Windows version
  global $tempdir;  // if not set, use system temporary directory
  $tempdir = realpath($tempdir);
  $tmpfname = tempnam($tempdir, "mdb"); 
  #$tmpfname = tempnam(realpath("C:/temp/"), "mdb"); // for testing (directory must exist!)
  $tfhandle = fopen($tmpfname, "wb");
  $myinput = str_replace("\r","",$input);
  $myinput = str_replace("\n","\r\n",$myinput);
  $myinput = str_replace("\\\$","\$",$myinput);
  fwrite($tfhandle, $myinput);
  fclose($tfhandle);
  #$output = `type $tmpfname | $commandLine `;
  $output = `$commandLine < $tmpfname `;
  unlink($tmpfname);
  return $output;
}

function tr4jme($inmol) {
  $outmol = $inmol;
  $outmol = str_replace("\r","",$outmol);
  $outmol = strtr($outmol,"\n","|");
  return($outmol);
}


function showHit($id,$s) {      // version for SVG, bitmap, JME or JSME output
  global $enable_svg;
  global $enable_bitmaps;
  global $enable_jme;
  global $enable_jsme;
  global $bitmapURLdir;
  global $molstructable;
  global $moldatatable;
  global $digits;
  global $subdirdigits;
  global $db_id;
  global $pic2dtable;
  global $codebase;
  global $svg_mode;
  global $edtag;
  
  $result2 = mysql_query("SELECT mol_name FROM $moldatatable WHERE mol_id = $id")
    or die("Query failed! (showHit)");
  while ($line2 = mysql_fetch_array($result2, MYSQL_ASSOC)) {
    $txt = $line2["mol_name"];
  }
  mysql_free_result($result2);

  echo "<tr>\n<td class=\"highlight\" width=\"10%\">\n";
  print "<a href=\"details.php?mol=${id}&db=${db_id}\" target=\"_blank\">$db_id:$id</a></td>\n";
  echo "<td class=\"highlight\"> <b>$txt</b>";
  if ($s != '') {
    echo " $s";
  }
  echo "</td>\n</tr>\n";
  
  $whatstr = "status";
  if ($svg_mode == 1) { $whatstr = "status, svg"; }
  $svg = "";

  $qstr = "SELECT $whatstr FROM $pic2dtable WHERE mol_id = $id";
  $result2 = mysql_query($qstr)
    or die("Query failed! (pic2d)");
  while ($line2 = mysql_fetch_array($result2, MYSQL_ASSOC)) {
    $status = $line2["status"];
    @$svg    = $line2["svg"];
  }
  mysql_free_result($result2);
  //if (($status != 1) || ($svg_mode > 0)) { $usebmp = false; } else { $usebmp = true; }
  if ($status == 1) { $usebmp = TRUE; } else { $usebmp = FALSE; }

  echo "<tr>\n<td colspan=\"2\">\n";

  $struc_shown = FALSE;

  if ($enable_svg == "y") {  
    if ((strlen($svg) > 0) && ($svg_mode == 1)) {
      print "$svg\n";
      $struc_shown = TRUE;
    } elseif ($svg_mode == 2) {
      echo "<img src=\"showsvg.php?id=$id&db=$db_id\" alt=\"hit libraries\">\n";
      $struc_shown = TRUE;
    }
  }

  if (($enable_bitmaps == "y") && ($struc_shown == FALSE)) {  
    if ((isset($bitmapURLdir)) && ($bitmapURLdir != "") && ($usebmp == true)) {
      while (strlen($id) < $digits) { $id = "0" . $id; }
      $subdir = '';
      if ($subdirdigits > 0) { $subdir = substr($id,0,$subdirdigits) . '/'; }
      print "<img src=\"${bitmapURLdir}/${db_id}/${subdir}${id}.png\" alt=\"hit libraries\">\n";
      $struc_shown = TRUE;
    } 
  }
  
  if ((($enable_jme == "y") || ($enable_jsme == "y")) && ($struc_shown == FALSE)) {  
    // if no bitmaps are available, we must invoking another instance of JME 
    // in "depict" mode for libraries display of each hit
    $qstr = "SELECT struc FROM $molstructable WHERE mol_id = $id";
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


function showHitRxn($id,$s) {      // version for SVG, bitmap, JME or JSME output
  global $enable_svg;
  global $enable_bitmaps;
  global $enable_jme;
  global $enable_jsme;  
  global $bitmapURLdir;
  global $rxnstructable;
  global $rxndatatable;
  global $digits;
  global $subdirdigits;
  global $db_id;
  global $pic2dtable;
  global $codebase;
  global $svg_mode;
  global $edtag;

  $result2 = mysql_query("SELECT rxn_name FROM $rxndatatable WHERE rxn_id = $id")
    or die("Query failed! (showHitRxn)");
  while ($line2 = mysql_fetch_array($result2, MYSQL_ASSOC)) {
    $txt = $line2["rxn_name"];
  }
  mysql_free_result($result2);

  echo "<tr>\n<td class=\"highlight\" width=\"10%\">\n";
  print "<a href=\"details.php?rxn=${id}&db=${db_id}\" target=\"_blank\">$db_id:$id</a></td>\n";
  echo "<td class=\"highlight\"> <b>$txt</b>";
  if ($s != '') {
    echo " $s";
  }
  echo "</td>\n</tr>\n";
  
  $whatstr = "status";
  if ($svg_mode == 1) { $whatstr = "status, svg"; }
  $svg = "";

  $qstr = "SELECT $whatstr FROM $pic2dtable WHERE rxn_id = $id";
  $result2 = mysql_query($qstr)
    or die("Query failed! (pic2d)");
  while ($line2 = mysql_fetch_array($result2, MYSQL_ASSOC)) {
    $status = $line2["status"];
    @$svg    = $line2["svg"];
  }
  mysql_free_result($result2);
  //if (($status != 1) || ($svg_mode > 0)) { $usebmp = false; } else { $usebmp = true; }
  if ($status == 1) { $usebmp = TRUE; } else { $usebmp = FALSE; }


  echo "<tr>\n<td colspan=\"2\">\n";

  $struc_shown = FALSE;

  if ($enable_svg == "y") {  
    if ((strlen($svg) > 0) && ($svg_mode == 1)) {
      print "$svg\n";
      $struc_shown = TRUE;
    } elseif ($svg_mode == 2) {
      echo "<img src=\"showsvg.php?id=$id&db=$db_id\" alt=\"hit reaction\">\n";
      $struc_shown = TRUE;
    }
  }

  if (($enable_bitmaps == "y") && ($struc_shown == FALSE)) {  
    if ((isset($bitmapURLdir)) && ($bitmapURLdir != "") && ($usebmp == true)) {
      while (strlen($id) < $digits) { $id = "0" . $id; }
      $subdir = '';
      if ($subdirdigits > 0) { $subdir = substr($id,0,$subdirdigits) . '/'; }
      print "<img src=\"${bitmapURLdir}/${db_id}/${subdir}${id}.png\" alt=\"hit reaction\">\n";
      $struc_shown = TRUE;
    } 
  }
  
  if ((($enable_jme == "y") || ($enable_jsme == "y")) && ($struc_shown == FALSE)) {  
    // if no bitmaps are available, we must invoking another instance of JME 
    // in "depict" mode for libraries display of each hit
    $qstr = "SELECT struc FROM $rxnstructable WHERE rxn_id = $id";
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



function check_db($id) {
  global $metatable;
  $db_id = -1;
  if (is_numeric($id)) {
    $result = mysql_query("SELECT db_id, name FROM $metatable WHERE (db_id = $id) AND (access > 0)")
      or die("Query failed! (check_db)");
    while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
      $db_id = $line["db_id"];
    }
    mysql_free_result($result);
  }
  if ($db_id == -1) {   // check if there is any data collection at all
    $result = mysql_query("SELECT COUNT(db_id) AS dbcount FROM $metatable")
      or die("Query failed! (check_db)");
    $dbcount = 0;
    while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
      $dbcount = $line["dbcount"];
    }
    mysql_free_result($result);
    if ($dbcount == 0) { $db_id = 0; }
  }
  return($db_id);
}

function get_numdb() {
  global $metatable;
  $numdb = 0;
  $result1 = mysql_query("SELECT COUNT(db_id) AS numdb FROM $metatable")
    or die("Query failed! (get_numdb)");
  $line1 = mysql_fetch_row($result1);
  mysql_free_result($result1);
  $numdb = $line1[0];
  return($numdb);
}

function get_numdb_readable() {
  global $metatable;
  $numdb = 0;
  $result1 = mysql_query("SELECT COUNT(db_id) AS numdb FROM $metatable WHERE (access > 0)")
    or die("Query failed! (get_numdb)");
  $line1 = mysql_fetch_row($result1);
  mysql_free_result($result1);
  $numdb = $line1[0];
  return($numdb);
}

function exist_db($db_id) {
  global $metatable;
  $numdb = 0;
  if (is_numeric($db_id)) {
    $result1 = mysql_query("SELECT COUNT(db_id) AS numdb FROM $metatable WHERE db_id = $db_id")
      or die("Query failed! (exist_db)");
    $line1 = mysql_fetch_row($result1);
    mysql_free_result($result1);
    $numdb = $line1[0];
  }
  if ($numdb > 0) { $result = TRUE; } else { $result = FALSE; }
  return($result);
}

function get_highestdbid() {
  global $metatable;
  $dbmax = 0;
  $result1 = mysql_query("SELECT MAX(db_id) AS dbmax FROM $metatable")
    or die("Query failed! (get_highestdbid)");
  $line1 = mysql_fetch_row($result1);
  $dbmax = $line1[0];
  mysql_free_result($result1);
  return($dbmax);
}

function get_lowestdbid() {
  global $metatable;
  $dbmin = 0;
  $result1 = mysql_query("SELECT MIN(db_id) AS dbmin FROM $metatable")
    or die("Query failed! (get_lowestdbid)");
  $line1 = mysql_fetch_row($result1);
  $dbmin = $line1[0];
  mysql_free_result($result1);
  return($dbmin);
}

function get_fallbackdbid() {
  global $metatable;
  $dbfallback = 0;
  $result1 = mysql_query("SELECT MIN(db_id) AS dbfallback FROM $metatable WHERE (access > 0)")
    or die("Query failed! (get_lowestdbid)");
  $line1 = mysql_fetch_row($result1);
  $dbfallback = $line1[0];
  mysql_free_result($result1);
  return($dbfallback);
}

function get_dbproperties($db_id) {
  global $metatable;
  $prop = array();
  $result1 = mysql_query("SELECT db_id, type, access, name, description, usemem,
    memstatus, digits, subdirdigits, trustedIP, flags FROM $metatable WHERE (db_id = $db_id)")
    or die("Query failed! (get_dbproperties)");
  while ($line1 = mysql_fetch_assoc($result1)) {
    $prop['db_id']        = $line1['db_id'];
    $prop['type']         = $line1['type'];
    $prop['access']       = $line1['access'];
    $prop['name']         = $line1['name'];
    $prop['description']  = $line1['description'];
    $prop['usemem']       = $line1['usemem'];
    $prop['memstatus']    = $line1['memstatus'];
    $prop['digits']       = $line1['digits'];
    $prop['subdirdigits'] = $line1['subdirdigits'];
    $prop['trustedIP']    = $line1['trustedIP'];
    $prop['flags']        = $line1['flags'];
  }
  mysql_free_result($result1);
  return($prop);
}

function mfreformat($instring) {
  $outstring = "";
  $firstnum = 1;
  $sub = 0;
  $instring = trim($instring);
  for ($l = 0; $l < strlen($instring); $l++) {
    $c = substr($instring,$l,1);
    if (is_numeric($c)) {
      if (($firstnum == 0) && ($sub == 0)) {
        $outstring = $outstring . "<sub>";
        $sub = 1;
      }
      $outstring = $outstring . $c;
    } else {
      $firstnum = 0;
      if ($c == ".") { $firstnum = 1; }
      if ($sub == 1) {
        $outstring = $outstring . "</sub>";
        $sub = 0;
      }
      $outstring = $outstring . $c;
    }
  }  // for
  if ($sub == 1) {
    $outstring = $outstring . "</sub>";
  }
  return($outstring);
}

function urlreformat($instring) {
  $outstring = "";
  $instring = trim($instring);
  $listarr = explode(",",$instring);
  foreach ($listarr as $item) {
    $item = trim($item);
    if (strlen($item) > 0) {
      if (strlen($outstring) > 0) { $outstring .= "<br>"; }
      $urlarr = explode("|",$item);
      $url_addr = $urlarr[0];
      if (count($urlarr) > 1) {
        $url_label = $urlarr[1];
      } else { 
        $url_label = $url_addr;
      }
      $url_label = trim($url_label);
      if (strlen($url_label) == 0) {
        $url_label = $url_addr;
      }
      $outstring .= "<a href=\"" . $url_addr . "\">" . $url_label . "</a>";
    }  // strlen($item) > 0
  }  // foreach
  return($outstring);
}

function mk_bitmask($mbits) {
  //returns an integer between 0 and 255
  $result = 0;
  if ($mbits > 0) {  
    for ($i = 1; $i <= $mbits; $i++) {
      $n = 8 - $i;
      $result = $result + pow(2,$n);
    }
  }
  return($result);
}

function matching_IP($ip,$pattern) {
  $a1 = explode("/",$pattern);
  $addr = $a1[0];
  @$netbits = trim($a1[1]);
  $mb1 = 8; $mb2 = 8; $mb3 = 8; $mb4 = 8;   // mask bits
  if (is_numeric($netbits) && ($netbits >= 0) && ($netbits <= 32)) {
    if ($netbits >= 8) {
      if ($netbits >= 16) {
        if ($netbits >= 24) {
          if ($netbits == 32) {
            $mb4 = 8;
          } else {
            $mb4 = $netbits - 24;
          }
        } else {
          $mb3 = $netbits - 16;
          $mb4 = 0;
        }
      } else {
        $mb2 = $netbits - 8;
        $mb3 = 0;
        $mb4 = 0;
      }
    } else {
      $mb1 = $netbits;
      $mb2 = 0;
      $mb3 = 0;
      $mb4 = 0;
    }    
  }  // if is_numeric...
  $a2 = explode(".",$addr);
  $p1 = $a2[0]; $p2 = $a2[1]; $p3 = $a2[2]; $p4 = $a2[3];
  $a3 = explode(".",$ip);
  $i1 = $a3[0]; $i2 = $a3[1]; $i3 = $a3[2]; $i4 = $a3[3];
  $m1 = mk_bitmask($mb1);
  $m2 = mk_bitmask($mb2);
  $m3 = mk_bitmask($mb3);
  $m4 = mk_bitmask($mb4);
  
  $result = FALSE;
  if ( (($i1 & $m1) == ($p1 & $m1)) &&
       (($i2 & $m2) == ($p2 & $m2)) &&
       (($i3 & $m3) == ($p3 & $m3)) &&
       (($i4 & $m4) == ($p4 & $m4)) ) {
    $result = TRUE;
  }
  return($result);
}

function is_trustedIP($ip) {
  global $trustedIP;
  $IPlist = str_replace(";",",",$trustedIP);
  $IPlist = preg_replace("/,\ +/",",",$IPlist);
  $IParray = explode(",",$IPlist);
  $result = false;
  foreach($IParray as $value) {
    $value = trim($value);
    #$pos = strpos($ip,$value);
    #if ($pos !== false) { $result = true; }
    if (matching_IP($ip,$value)) { $result = TRUE; }
  }
  return($result);
}

function is_db_trustedIP($db_id,$ip) {
  global $metatable;
  $result = false;
  if (is_numeric($db_id)) {
    $res = mysql_query("SELECT db_id, trustedIP FROM $metatable WHERE (db_id = $db_id)")
      or die("Query failed! (is_db_trustedIP)");
    while ($line = mysql_fetch_array($res, MYSQL_ASSOC)) {
      $trustedIP = $line["trustedIP"];
    }
    mysql_free_result($res);
    $IPlist = str_replace(";",",",$trustedIP);
    $IPlist = preg_replace("/,\ +/",",",$IPlist);
    if (strlen($IPlist)>0) {
      $IParray = explode(",",$IPlist);
      $result = false;
      foreach($IParray as $value) {
        $value = trim($value);
        $pos = strpos($ip,$value);
        if ($pos !== false) { $result = true; }
      }
    }
  }
  return($result);
}

function clean_fieldstr($instr) {
  $outstr = "";
  $allowedstr = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_";
  $instr = trim($instr);
  #$instr = str_replace(".","_",$instr);
  if ($instr != "") {
    $c = "";
    $pos = false;
    for ($i = 0; $i < strlen($instr); $i++) {
      $c = substr($instr,$i,1);
      $pos = strpos($allowedstr,$c);
      if ($pos !== false) {
        $outstr .= $c;
      }
    }
  }
  return($outstr);
}

function getfieldprop($comment) {
  $prop = array();
  $pos = strpos($comment, ">>>>");
  if ($pos !== false) {
    if ($pos == 0) {
      $comment = str_replace(">>>>","",$comment);
      $acomment = explode("<",$comment);
      $label  = $acomment[0];
      $prop["label"] = $label;
      $format = 1;
      $nformat = $acomment[1];
      if ($nformat == 0) { $format = 0; }
      if ($nformat == 1) { $format = 1; }
      if ($nformat == 2) { $format = 2; }
      if ($nformat == 3) { $format = 3; }
      $prop["format"] = $format;
      $sdflabel   = $acomment[2];
      $prop["sdflabel"] = $sdflabel;
      $searchmode = $acomment[3];
      if ($searchmode != 1) { $searchmode = 0; }
      $prop["searchmode"] = $searchmode;
    }
  }
  return $prop;
}

function is_stringtype($columntype) {
  $res = FALSE;
  $columntype = strtoupper($columntype);
  if (strpos($columntype,"CHAR") !== FALSE) { $res = TRUE; }
  if (strpos($columntype,"TEXT") !== FALSE) { $res = TRUE; }
  if (strpos($columntype,"ENUM") !== FALSE) { $res = TRUE; }
  if (strpos($columntype,"SET") !== FALSE)  { $res = TRUE; }
  if (strpos($columntype,"VARBINARY") !== FALSE) { $res = TRUE; }
  return $res;
}

function is_validmol($mol) {
  $res = FALSE;
  if ((strpos($mol,'M  END') > 40) && 
      (strpos($mol,'V2000') > 30)) { $res = TRUE; }  // rather simple, for now
  return $res;
}

function strip_labels($myrxn) {
  //$myrxn = str_replace("\r\n","\n",$myrxn);
  $line_arr = array();
  $line_arr = explode("\n",$myrxn);
  $myrxn = "";
  foreach ($line_arr as $line) {
    if ((strlen($line) > 68) && (strpos($line,"0  0",63) !== FALSE)) {
      $line = substr_replace($line,"  0  0  0",60,9);
    }
    $myrxn .= $line . "\n";
  }
  return($myrxn);
}

function mk_fpqstr($colname,$fplist) {
  $result = "";
  $fpa = explode(",",$fplist);
  $n_el = count($fpa);
  for ($i = 0; $i < $n_el; $i++) {
    $fpval = $fpa[$i];
    $fpnum = $i + 1;
    while (strlen($fpnum) < 2) { $fpnum = "0" . $fpnum; }
    $fpcol = $colname . $fpnum;
    if (is_numeric($fpval)) {
      if ($fpval > 0) {
        if (strlen($result) > 0) { $result .= " AND"; }
        $result .= " ($fpcol & $fpval = $fpval)";
      }
    }
  }
  return($result);
}

function debug_output($msg) {
  global $debug;
  if (($debug & 1) == 1) {
    $begin = "<!-- ";
    $end = " -->\n";
  } else {
    $begin = "<pre>\n";
    $end = "</pre>\n";
  }
  echo "$begin"; 
  echo "$msg"; 
  echo "$end";
}

function config_quickcheck() {
  global $database;
  global $ro_user;
  global $ro_password;
  $result = 0;
  if ((!isset($database)) || (strlen($database) == 0)) { $result = 1; }
  if ((!isset($ro_user)) || (strlen($ro_user) == 0)) { $result = 1; }
  if ((!isset($ro_password)) || (strlen($ro_password) == 0)) { $result = 1; }
  if ($result > 0) {
    echo "<h3>Attention! Missing, invalid, or unreadable configuration file!</h3>\n";
  }
  return($result);
}

function set_charset($confcs) {
  global $html_charset;
  global $mysql_charset;
  global $mysql_collation;
  
  // set defaults
  $html_charset    = "ISO-8859-1";
  $mysql_charset   = "latin1";
  $mysql_collation = "latin1_swedish_ci";

  // derive from configuration value
  if ($confcs == "latin2") {
    $html_charset    = "ISO-8859-2";
    $mysql_charset   = "latin2";
    $mysql_collation = "latin2_general_ci";
  }
  if ($confcs == "utf8") {
    $html_charset    = "UTF-8";
    $mysql_charset   = "utf8";
    $mysql_collation = "utf8_unicode_ci";
    // quoted from http://forums.mysql.com/read.php?103,187048,188748#msg-188748
    // "So when you need better sorting order - use utf8_unicode_ci,
    // and when you utterly interested in performance - use utf8_general_ci."
  }

  // Cyrillic character sets (thanks to Konstantin Tokarev)
  if ($confcs == "cp1251") {
    $html_charset    = "windows-1251";
    $mysql_charset   = "cp1251";
    $mysql_collation = "cp1251_general_ci";
  }
  if ($confcs == "koi8r") {
    $html_charset    = "KOI8-R";
    $mysql_charset   = "koi8r";
    $mysql_collation = "koi8r_general_ci";
  }
  if ($confcs == "koi8u") {
    $html_charset    = "KOI8-U";
    $mysql_charset   = "koi8u";
    $mysql_collation = "koi8u_general_ci";
  }

  /* Add some more, if desired
   Documentation:
     Valid MySQL charset names: http://dev.mysql.com/doc/refman/5.5/en/charset-charsets.html
     Valid HTML charset names:  http://www.iana.org/assignments/character-sets
  */
}

function search_mol_exact($db_id,$mol) {
  global $prefix;
  global $molstrucsuffix;
  global $molstatsuffix;
  global $molcfpsuffix;
  global $CHECKMOL;
  global $MATCHMOL;
  global $use_cmmmsrv;
  global $cmmmsrv_addr;
  global $cmmmsrv_port;
  global $ostype;
  global $socket;

  $mmopt = "xsgGaid";  // x = exact, s = strict, g = E/Z, G = R/S, a = charges, i = isotopes, d = radicals
  $dbprefix      = $prefix . "db" . $db_id . "_";
  $molstructable = $dbprefix . $molstrucsuffix;
  $molstattable  = $dbprefix . $molstatsuffix;
  $molcfptable   = $dbprefix . $molcfpsuffix;
  $mmcmd = "$MATCHMOL -${mmopt} -";

  //$use_cmmmsrv = "n";  // for testing only

  if ($use_cmmmsrv == 'y') {
    /* create a TCP/IP socket */
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket < 0) {
      //echo "socket_create() failed.\nreason: " . socket_strerror ($socket) . "\n";
      echo "<!-- could not connect to cmmmsrv - reverting to checkmol/matchmol -->\n";
      $use_cmmmsrv = "n";
    }
    $result = socket_connect ($socket, $cmmmsrv_addr, $cmmmsrv_port);
    if ($result === FALSE) {
      //echo "socket_connect() failed.<p />\n";
      echo "<!-- could not connect to cmmmsrv - reverting to checkmol/matchmol -->\n";
      $use_cmmmsrv = "n";
    }
  }
  if ($use_cmmmsrv == 'y') {
    $a = socket_read($socket, 250, PHP_NORMAL_READ);
    //echo "the socket says: $a<br>\n";
    $pos = strpos($a,"READY");
    if ($pos === false) {
      echo "<!-- could not connect to cmmmsrv - reverting to checkmol/matchmol -->\n";
      $use_cmmmsrv = "n";
    }
  }
 
  $res = array();
  $ndup = 0;
  $safemol = str_replace(";"," ",$mol);

  if ($use_cmmmsrv == 'y') {
    $chkresult = filterthroughcmmm("$safemol", "#### checkmol:axH");   // "a" for charges
  } else {
    if ($ostype == 1) { $chkresult = filterthroughcmd("$safemol", "$CHECKMOL -axH - "); }
    if ($ostype == 2) { $chkresult = filterthroughcmd2("$safemol", "$CHECKMOL -axH - "); }
  }
  if (strlen($chkresult) < 2) {
    echo "no response from checkmol (maybe a server configuration problem?)\n</body></html>\n";
    exit;
  }

  // first part of output: molstat, second part: hashed fingerprints
  
  $myres = explode("\n", $chkresult);
  $chkresult1 = $myres[0];
  $chkresult2 = $myres[1];
  
  // strip trailing "newline"
  $chkresult1 = str_replace("\n","",$chkresult1);
  $len = strlen($chkresult1);
  // strip trailing semicolon
  if (substr($chkresult1,($len-1),1) == ";") {
    $chkresult1 = substr($chkresult1,0,($len-1));
  }  

  $chkresult2 = str_replace("\n","",$chkresult2);
  if (strpos($chkresult2,";") !== false) {
    $chkresult2 = substr($chkresult2,0,strpos($chkresult2,";"));
  }
  $hfp = explode(",",$chkresult2);

  // now assemble the pre-selection query string
  $ms_qstr = str_replace(";"," AND ",$chkresult1);
  $ms_qstr = str_replace("n_","${molstattable}.n_",$ms_qstr);
  //$op = ">=";
  $op = "=";
  $ms_qstr = str_replace(":",$op,$ms_qstr);
  $hfp_qstr = "";
  for ($h = 0; $h < count($hfp); $h++) {
    $number = $h + 1;
    while (strlen($number) < 2) { $number = "0" . $number; }
    if (strlen($hfp_qstr) > 0) { $hfp_qstr .= "AND "; }
    $hfp_qstr .= "${molcfptable}.hfp$number = $hfp[$h] ";
  }
  $qstr = "SELECT ${molstructable}.mol_id,${molstructable}.struc FROM $molstructable,$molstattable,$molcfptable";
  $qstr .= " WHERE " . $ms_qstr . " AND " . $hfp_qstr;
  $qstr .= " AND (${molstructable}.mol_id = ${molstattable}.mol_id)";
  $qstr .= " AND (${molstructable}.mol_id = ${molcfptable}.mol_id)";

  $bs = 50;
  if ($use_cmmmsrv == "n") { $bs = 8; }
  $sqlbs = 10 * $bs;
  $nqueries = 0;
  do {
    $offset = $nqueries * $sqlbs;
    $offsetstr = " LIMIT ${offset}, $sqlbs";
    $qstrlim = $qstr . $offsetstr;
    $result = mysql_query($qstrlim)
      or die("Query failed! (search_mol_exact #1)");    
    $n_cand  = mysql_num_rows($result);     // number of candidate librariess
    if ($n_cand > 0) {
      $qstruct = $safemol . "\n\$\$\$\$\n";;
      $n = 0;
      while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
        $mol_id = $line["mol_id"];
        $haystack = $line["struc"];
        $qstruct = $qstruct . $haystack  . "\n\$\$\$\$ #${mol_id}\n";    
        $n ++;
        if (($n == $bs) || ($n == $n_cand)) {
          if ($use_cmmmsrv == "n") {
            $qstruct = str_replace("\$","\\\$",$qstruct);
          }
          if ($use_cmmmsrv == "y") {
            $matchresult = filterthroughcmmm("$qstruct", "#### matchmol:${mmopt}"); 
          } else {
            if ($ostype == 1) {$matchresult = filterthroughcmd("$qstruct ", "$mmcmd"); }
            if ($ostype == 2) {$matchresult = filterthroughcmd2("$qstruct ", "$mmcmd"); }
          }
          $br = explode("\n", $matchresult);
          foreach ($br as $reply) {
           if (strpos($reply,":T") > 0) {
             $reply = chop($reply);
             $hr = explode("#",$reply);
             $h_id = $hr[1];
             if (strlen($h_id) > 0) {
               $res[$ndup] = $h_id;
               $ndup++;
             }
           }
          }  // foreach...
          $qstruct = $safemol . "\n\$\$\$\$\n";
          $n = 0;
        }
      }  // while ...
    }  // if n_cand > 0...    
    $nqueries++;
  } while (($n_cand == $sqlbs) && ($nqueries < 100000));
  if ($use_cmmmsrv == "y") {
    socket_write($socket,'#### bye');
    socket_close($socket);
  }
  return $res;
}

function getagent() {
  $user_agent = array();
  if (strstr($_SERVER['HTTP_USER_AGENT'],'Opera')) {
     $brows = ereg_replace(".+\(.+\) (Opera |v){0,1}([0-9,\.]+)[^0-9]*","Opera \\2",$_SERVER['HTTP_USER_AGENT']);
     if(ereg('^Opera/.*',$_SERVER['HTTP_USER_AGENT'])) {
       $brows = ereg_replace("Opera/([0-9,\.]+).*","Opera \\1",$_SERVER['HTTP_USER_AGENT']);    
       if(ereg('Version/.*',$_SERVER['HTTP_USER_AGENT'])) {
         $brows = ereg_replace(".+Version/([0-9,\.]+).*","Opera \\1",$_SERVER['HTTP_USER_AGENT']);    
       }
     }
  } elseif (strstr($_SERVER['HTTP_USER_AGENT'],'MSIE'))
     $brows = ereg_replace(".+\(.+MSIE ([0-9,\.]+).+","MSIE \\1",$_SERVER['HTTP_USER_AGENT']);
  
  elseif (strstr($_SERVER['HTTP_USER_AGENT'],'Firefox'))
     $brows = ereg_replace(".+\(.+rv:.+\).+Firefox/(.*)","Firefox \\1",$_SERVER['HTTP_USER_AGENT']);

  elseif (strstr($_SERVER['HTTP_USER_AGENT'],'Chrome')) 
     $brows = ereg_replace(".+Chrome/([0-9,\.]+).+","Chrome \\1",$_SERVER['HTTP_USER_AGENT']);

  elseif (strstr($_SERVER['HTTP_USER_AGENT'],'Safari'))
     $brows = ereg_replace(".+Safari/([0-9,\.]+).+","Safari \\1",$_SERVER['HTTP_USER_AGENT']);

  elseif (strstr($_SERVER['HTTP_USER_AGENT'],'Konqueror'))
     $brows = ereg_replace(".+Konqueror/([0-9,\.]+).+","Konqueror \\1",$_SERVER['HTTP_USER_AGENT']);

  elseif (strstr($_SERVER['HTTP_USER_AGENT'],'SeaMonkey'))
     $brows = ereg_replace(".+\(.+rv:.+\).+SeaMonkey/(.*)","SeaMonkey \\1",$_SERVER['HTTP_USER_AGENT']);

  elseif (strstr($_SERVER['HTTP_USER_AGENT'],'Mozilla'))
     $brows = ereg_replace(".+\(.+rv:([0-9,\.]+).+","Mozilla \\1",$_SERVER['HTTP_USER_AGENT']);
  else
     $brows = $_SERVER['HTTP_USER_AGENT'];
  $uarec = explode(" ",$brows);
  $tmpver = $uarec[1];
  $newver = "";
  $n_dots = 0;
  if (strlen($tmpver) >0) {
    for ($i = 0; $i < strlen($tmpver); $i++) {
      $tmpchar = substr($tmpver,$i,1);
      if ($tmpchar == ".") {
        if ($n_dots < 1) {
          $newver .= ".";
        }
        $n_dots++;
      } elseif (strpos("0123456789",$tmpchar) !== FALSE) {
        $newver .= $tmpchar;
      }
    }
  }
  if ($newver == "") { $newver = "0"; }
  $user_agent["name"] = $uarec[0];
  $user_agent["version"] = $newver;
  return $user_agent;
} 

function get_svgmode() {
  global $debug;
  $dummy = getagent();
  $ua_name = $dummy["name"];
  $ua_ver  = $dummy["version"];
  #echo "more specifically: $ua_name, version $ua_ver<br>\n";
  if ($debug > 0) { debug_output("browser: $ua_name, version $ua_ver"); }
  $svgmode = 0; // default: SVG not supported
  if (($ua_name === "MSIE") && ($ua_ver >= 9)) { $svgmode = 1; }
  if (($ua_name === "Firefox") && ($ua_ver >= 4)) { $svgmode = 1; }
  if (($ua_name === "Chrome") && ($ua_ver >= 7)) { $svgmode = 1; }  
  if (($ua_name === "Opera") && ($ua_ver >= 8)) { $svgmode = 2; }   // ?? inline SVG since 11.6
  if (($ua_name === "Opera") && ($ua_ver >= 11.6)) { $svgmode = 1; }   // ?? inline SVG since 11.6
  if (($ua_name === "Safari") && ($ua_ver >= 500)) { $svgmode = 2; }   // ?? inline SVG since 5.1
  if (($ua_name === "Safari") && ($ua_ver >= 510)) { $svgmode = 1; }   // ?? inline SVG since 5.1
  // currently, display of _scaled_ external SVGs does not work properly, so disable mode 2
  if ($svgmode == 2) { $svgmode = 0; }
  return($svgmode);
}

function mkprefscript() {
  echo "\n<script language=\"javascript\" type=\"text/javascript\">\n";
  echo "function openprefs() {\n";
  echo "  window.open('setprefs.php','Preferences','width=400,height=220,scrollbars=no,resizable=yes');\n";
  echo "}\n\n";
  echo "function set_editor(neweditor) {\n";
  echo "  selectededitor = neweditor;\n";
  echo "}\n";
  echo "</script>\n\n";
}
?>