<?php
// incrss.php       Norbert Haider, University of Vienna, 2010-2014
// part of MolDB6   last change: 2014-07-10

/**
 * @file incrss.php
 * @author Norbert Haider
 * 
 * This script is included by moldbgss.php which performs
 * (sub)structure or reaction (sub)structure searches.
 */

echo "<h3>Found reactions:</h3><br>\n";

$validrxn = TRUE;
if (strpos($mol,"\$RXN") === FALSE) { $validrxn = FALSE; }   // very rudimentary...
if (strpos($mol,"\$MOL") === FALSE) { $validrxn = FALSE; }
if (strpos($mol,"M  END") === FALSE) { $validrxn = FALSE; }
$nsel = 0;
$bfp_exit = 0;

//echo "idcont: $idcont<br>dbcont: $dbcont<br>";

if (($mol != '') && ($validrxn == TRUE)) { 
  echo "<table width=\"100%\">\n";
  //echo "<pre>$mol</pre>\n";
  $time_start = getmicrotime();  

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

  $coreqstr = "";
  $hits_sum = 0;
  $total_cand_sum = 0;
  $fplbl_str = "";
  $n_structures_sum = 0;
  $pageonly = "";
  $endpage  = true;

  // get the fingerprint dictionary
  $fpdefqstr = "SELECT fp_id, fpdef FROM $fpdeftable;";
  $fpdefresult = mysql_query($fpdefqstr)
      or die("Could not get fingerprint definition!"); 
  $i = -1;
  $n_dict = 0;
  $fpdef = array();
  while ($fpdefline = mysql_fetch_array($fpdefresult, MYSQL_ASSOC)) {
    $i++;
    $n_dict++;
    $fpdef[$i] = $fpdefline["fpdef"];
  } 
  mysql_free_result($fpdefresult);
  $fp_count = $i + 1;

  // disassemble the RXN file
  $mol = str_replace("\r\n","\n",$mol);
  $mol = str_replace("\n","\r\n",$mol);
  //$saferxn = escapeshellcmd($mol);
  $saferxn = str_replace(";"," ",$mol);
  $saferxn = str_replace("\""," ",$saferxn);
  $saferxn = str_replace("\'"," ",$saferxn);
  $saferxn = str_replace("\´"," ",$saferxn);
  $saferxn = str_replace("\`"," ",$saferxn);
  $saferxn = str_replace("\|"," ",$saferxn);
  $rxndescr = analyze_rxnfile($saferxn);
  $nrmol = get_nrmol($rxndescr);
  $npmol = get_npmol($rxndescr);
  //echo "there are $nrmol reactants and $npmol products<br>\n";
  $allmol = array();
  $rmol = array();
  $pmol = array();
  $label_list = array();
  $map_list = array();
  $n_labels = 0;
  $n_maps = 0;
  $rqstr  = "";
  $pqstr  = "";
  $allmol = explode("\$MOL\r\n",$saferxn);
  $header = $allmol[0];

  if ($nrmol > 0) {
    $moldfpsum = "";
    $molhfpsum = "";
    for ($i = 0; $i < $nrmol; $i++) {
      $rmol[$i] = $allmol[($i+1)];
      $mnum = $i + 1;
      //echo "processing reactant no. $mnum ...";
      $labels = get_atomlabels($rmol[$i]);
      $mid = "r" . $mnum;
      if (strlen($labels) > 0) {
        add_labels($mid,$labels);
        //echo "labels found for this structure: $labels \n\n";
      }
      $safemol = $rmol[$i];
      
      //create the dictionary-based fingerprints
      $moldfp = "";
      for ($k = 0; $k < $n_dict; $k++) {
        $dict = $fpdef[$k];
        $cand = $safemol . "\n" . '$$$$' ."\n" . $dict;
        if ($use_cmmmsrv == 'y') {
          $dfpstr = filterthroughcmmm($cand,"#### MATCHMOL:F");
        } else {
          $cand   = str_replace("\$","\\\$",$cand);
          if ($ostype == 1) { $dfpstr = filterthroughcmd($cand,"$MATCHMOL -F - "); }
    	  if ($ostype == 2) { $dfpstr = filterthroughcmd2($cand,"$MATCHMOL -F - "); }
        }
        $dfpstr = trim($dfpstr);
        
        if (strlen($dfpstr) < 1) {
          echo "no response from checkmol (maybe a server configuration problem?)\n</body></html>\n";
          exit;
        }
        
        if ($k > 0) { $moldfp .= ","; }
        $moldfp .= $dfpstr;
        //echo "dictionary-based fingerprints for reactant $i + dictionary $k\n$dfpstr\n";
      }  // for..
      $firstpart = substr($moldfp,0,-1);
      $lastdigit = intval(substr($moldfp,-1,1));
      if ((1&$lastdigit)) {
        $lastdigit--;
        $lastdigit = strval($lastdigit);
        $moldfp = $firstpart . $lastdigit;
      }
      $moldfpsum = add_molfp($moldfpsum,$moldfp);
      
      // create the hash-based fingerprints
      if ($use_cmmmsrv == 'y') {
        $molhfp = filterthroughcmmm($safemol,"#### checkmol:H");
      } else {
        if ($ostype == 1) {$molhfp = filterthroughcmd($safemol,"$CHECKMOL -H - "); }
    	if ($ostype == 2) {$molhfp = filterthroughcmd2($safemol,"$CHECKMOL -H - "); }
      }
      $hfparr = explode(";",$molhfp);
      $molhfp = $hfparr[0];
      $molhfpsum = add_molfp($molhfpsum,$molhfp);
      
    }  // end for ($i = 0; $i < $nrmol; $i++) ...
    //echo "added moldfp: $moldfpsum\n";
    //echo "added molhfp: $molhfpsum\n";
    
    $rqstr = "";     // reactants
    $rqstr .= mk_fpqstr("dfp",$moldfpsum);
    if (strlen($rqstr) > 0) { $rqstr .= " AND "; }
    $rqstr .= mk_fpqstr("hfp",$molhfpsum);
  }

  if ($npmol > 0) {
    $moldfpsum = "";
    $molhfpsum = "";
    for ($i = 0; $i < $npmol; $i++) {
      $pmol[$i] = $allmol[($i+1+$nrmol)];
      $mnum = $i + 1;
      //echo "processing product no. $mnum ...";
      $labels = get_atomlabels($pmol[$i]);
      $mid = "p" . $mnum;
      if (strlen($labels) > 0) {
        add_labels($mid,$labels);
        //echo "labels found for this structure: $labels \n\n";
      }
      $safemol = $pmol[$i];
      
      //create the dictionary-based fingerprints
      $moldfp = "";
      for ($k = 0; $k < $n_dict; $k++) {
        $dict = $fpdef[$k];
        $cand = $safemol . "\n" . '$$$$' ."\n" . $dict;
        if ($use_cmmmsrv == 'y') {
          $dfpstr = filterthroughcmmm($cand,"#### MATCHMOL:F");
        } else {
          $cand   = str_replace("\$","\\\$",$cand);
          if ($ostype == 1) { $dfpstr = filterthroughcmd($cand,"$MATCHMOL -F - "); }
    	  if ($ostype == 2) { $dfpstr = filterthroughcmd2($cand,"$MATCHMOL -F - "); }
        }
        $dfpstr = trim($dfpstr);
        if ($k > 0) { $moldfp .= ","; }
        $moldfp .= $dfpstr;
        //echo "dictionary-based fingerprints for product $i + dictionary $k\n$dfpstr\n";
      }  // for..
      $firstpart = substr($moldfp,0,-1);
      $lastdigit = intval(substr($moldfp,-1,1));
      if ((1&$lastdigit)) {
        $lastdigit--;
        $lastdigit = strval($lastdigit);
        $moldfp = $firstpart . $lastdigit;
      }
      $moldfpsum = add_molfp($moldfpsum,$moldfp);
      
      // create the hash-based fingerprints
      if ($use_cmmmsrv == 'y') {
        $molhfp = filterthroughcmmm($safemol,"#### checkmol:H");
      } else {
        if ($ostype == 1) {$molhfp = filterthroughcmd($safemol,"$CHECKMOL -H - "); }
    	if ($ostype == 2) {$molhfp = filterthroughcmd2($safemol,"$CHECKMOL -H - "); }
      }
      $hfparr = explode(";",$molhfp);
      $molhfp = $hfparr[0];
      $molhfpsum = add_molfp($molhfpsum,$molhfp);
    }  // end for ($i = 0; $i < $npmol; $i++) ...
    //echo "added moldfp: $moldfpsum\n";
    //echo "added molhfp: $molhfpsum\n";
    
    // insert combined reaction fingerprints for product(s)
    $qstr = "$moldfpsum,$molhfpsum";

    $pqstr = "";     // products
    $pqstr .= mk_fpqstr("dfp",$moldfpsum);
    if (strlen($pqstr) > 0) { $pqstr .= " AND "; }
    $pqstr .= mk_fpqstr("hfp",$molhfpsum);
    //echo "<br>moldfpsum: $moldfpsum<br>pqstr: $pqstr <br>";
  }

  $outer_qstr = "";
  $inner_qstr = "";
  //echo "<br>rqstr: $rqstr<br>pqstr: $pqstr<br>";
  if ((strlen($rqstr) > 0) && (strlen($pqstr) > 0)) {
    $outer_qstr = "(role LIKE 'P') AND $pqstr";
    $inner_qstr = "(role LIKE 'R') AND $rqstr";
  } elseif ((strlen($rqstr) > 0) && (strlen($pqstr) == 0)) {
    $outer_qstr = "(role LIKE 'R') AND $rqstr";
    $inner_qstr = "";
  } elseif ((strlen($rqstr) == 0) && (strlen($pqstr) > 0)) {
    $outer_qstr = "(role LIKE 'P') AND $pqstr";
    $inner_qstr = "";
  }  elseif ((strlen($rqstr) == 0) && (strlen($pqstr) == 0)) {
    echo "<br>nothing to do....<br>\n";
    echo "</body></html>\n";
    die();
  }
  
  //echo "<br>outer_qstr: $outer_qstr <br>";
  //echo "<br>inner_qstr: $inner_qstr <br>";
  
  if ($use_cmmmsrv == "y") {
    $bs      = 50;                          // block size (number of structures per query SDF)
  } else {
    $bs      = 10;                          // smaller block size (<128K) if we use shell calls
  }
  $maxbmem   = 0;                           // for diagnostic purposes only
  $sqlbsmult = 10;                          // relates $bs to SQL block size (for LIMIT clause)
  $sqlbs     = $bs * $sqlbsmult;
  $mmcmd = "$MATCHMOL $options -";
  $sel = 0;

  foreach ($dba as $db_id) {    //========= loop through all selected data collections
  
    $dbprefix      = $prefix . "db" . $db_id . "_";
    $rxnstructable = $dbprefix . $rxnstrucsuffix;
    $rxndatatable  = $dbprefix . $rxndatasuffix;
    $rxncfptable   = $dbprefix . $rxncfpsuffix;
    $pic2dtable    = $dbprefix . $pic2dsuffix;
    $qstr = $coreqstr;

    $qstr01 = "SELECT * FROM $metatable WHERE (db_id = $db_id)";
    $result01 = mysql_query($qstr01)
      or die("Query failed (#1)!");    
    while($line01   = mysql_fetch_array($result01)) {
      $db_id        = $line01['db_id'];
      $dbtype       = $line01['type'];
      $dbname       = $line01['name'];
      $usemem       = $line01['usemem'];
      $memstatus    = $line01['memstatus'];
      $digits       = $line01['digits'];
      $subdirdigits = $line01['subdirdigits'];
    }
    mysql_free_result($result01);

    // use only RD data collection

    if (($dbtype == 2) && ($db_id >= $dbcont)) {
      $nsel++;
      // get total number of entries in the database
      $n_qstr = "SELECT COUNT(rxn_id) AS count FROM $rxnstructable";
      $n_result = mysql_query($n_qstr)
          or die("Could not get number of entries!"); 
      while ($n_line = mysql_fetch_array($n_result, MYSQL_ASSOC)) {
        $n_structures = $n_line["count"];
      } 
      mysql_free_result($n_result);
    
      $offsetcount = 0;
      $total_cand  = 0;
      $hits        = 0;

      if (is_numeric($idcont) && ($idcont > 0)) {
        $outer_qstr .= " AND (rxn_id >= " . $idcont . ")";
      }

      //=============== begin outer loop (LIMIT)
      do {
        $offset  = $offsetcount * $sqlbs;
        $qstrlim = "SELECT rxn_id FROM $rxncfptable WHERE " . $outer_qstr . " ORDER BY rxn_id LIMIT $offset, $sqlbs";
        //echo "<br>db: $db_id qstrlim: $qstrlim<br>\n";
        $result = mysql_query($qstrlim)
          or die("Query failed! (4a_outer)");    
        $offsetcount ++;
        $n_cand  = mysql_num_rows($result);     // number of candidate structures
        $bi      = 0;                           // counter within block
        $n       = 0;                           // number of candidates already processed
  
        while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {   // outer loop ( rxn_id)
          $rxn_id = $line["rxn_id"];
          //echo "outer loop candidate: $rxn_id<br>\n";
          
          if (strlen($inner_qstr) == 0) {
            $total_cand++;
            $qstr_struc = "SELECT struc, map FROM $rxnstructable WHERE rxn_id = $rxn_id";
            $result2 = mysql_query($qstr_struc)
            or die("Query failed! (4a_struc)");    
            $line2 = mysql_fetch_row($result2);
            $struc    = $line2[0];
            $struc = str_replace("\n","\r\n",$struc);
            $struc = str_replace("\r\r\n","\r\n",$struc);
            $map      = $line2[1];
            //echo "<pre>$struc</pre>";
            if ($debug > 2) {
              debug_output("***** match protocol for candidate ${db_id}:${rxn_id}\n");
            }
            $total_cand_sum++;
            $strucmatch = matchrxn($saferxn,$struc,$map,$options);
            if ($strucmatch == TRUE) {
              $hits++;
              $hits_sum++;
              if ( $hits_sum > $maxhits ) {
                echo "</table>\n";
                echo "<hr>There are more hits....<br>\n";
                $hits_sum--;
                $total_cand_sum--;
                mk_continueform($db_id,$rxn_id,$nhits,$hitlist);
                $pageonly = " on this page";
                $endpage = false;
                break 3;
              }
              showHitRxn($rxn_id,"");
              if (($enable_download == "y") && ($nhits < $download_limit)) {
                $nhits++;
                if (strlen($hitlist) > 0) { $hitlist .= ","; }
                $hitlist .= $db_id . ":" . $rxn_id;
              }
            }
            mysql_free_result($result2);
          } else {    // do inner loop
            $qstr_i = "SELECT rxn_id FROM $rxncfptable WHERE (rxn_id = $rxn_id) AND " . $inner_qstr;
            //echo "inner qstr: $qstr_i<br>";
            $result3 = mysql_query($qstr_i)
              or die("Query failed! (4a_inner)");    
            while ($line3 = mysql_fetch_array($result3, MYSQL_ASSOC)) {
              $total_cand++;
              $rxn_id_i = $line3["rxn_id"];
              //echo "inner loop candidate: $rxn_id_i<br>\n";
              $qstr_struc = "SELECT struc, map FROM $rxnstructable WHERE rxn_id = $rxn_id";
              $result2 = mysql_query($qstr_struc)
              or die("Query failed! (4a_struc)");    
    
              $line2 = mysql_fetch_row($result2);
              $struc    = $line2[0];
              $struc = str_replace("\n","\r\n",$struc);
              $struc = str_replace("\r\r\n","\r\n",$struc);
              $map      = $line2[1];
              //echo "<pre>$struc</pre>";
              if ($debug > 2) {
                debug_output("***** match protocol for candidate ${db_id}:${rxn_id}\n");
              }
              $total_cand_sum++;
              $strucmatch = matchrxn($saferxn,$struc,$map,$options);
              if ($strucmatch == TRUE) {
                $hits++;
                $hits_sum++;
                if ( $hits_sum > $maxhits ) {
                  echo "</table>\n";
                  echo "<hr>There are more hits....<br>\n";
                  $hits_sum--;
                  $total_cand_sum--;
                  mk_continueform($db_id,$rxn_id,$nhits,$hitlist);
                  $pageonly = " on this page";
                  $endpage = false;
                  break 4;
                }
                showHitRxn($rxn_id,"");
                if (($enable_download == "y") && ($nhits < $download_limit)) {
                  $nhits++;
                  if (strlen($hitlist) > 0) { $hitlist .= ","; }
                  $hitlist .= $db_id . ":" . $rxn_id;
                }
              }
              mysql_free_result($result2);
            }
            mysql_free_result($result3);
         
          }   // end inner loop
          
        }  // end outer loop (rxn_id)

        mysql_free_result($result);
      } while ($n_cand >= $sqlbs);
      //=============== end outer loop  (LIMIT)
      $n_structures_sum = $n_structures_sum + $n_structures;

    }  // end if ($dbtype == 2)

  }  // foreach

  echo "</table>\n<hr>\n";
  if ($nsel == 0) {
    echo "no reaction data collection selected!<br>\n<hr>\n";
  }

  if ($use_cmmmsrv == "y") {
    socket_write($socket,'#### bye');
    socket_close($socket);
  }

  $time_end = getmicrotime();  
  
  if (($enable_download == "y") && ($nhits <= $download_limit)
  && ($nhits > 0) && ($endpage == true)) {  
    echo "<form action=\"hits2rdf.php\" method=\"post\">\n";
    echo "<input type=\"hidden\" name=\"hits\" value=\"$hitlist\">\n";
    echo "<input type=\"Submit\" name=\"download\" value=\"Download\"> hit reactions (max. $download_limit) as RD file<br>\n";
    echo "</form>\n";
  }
  
  if (($dbcont > 0) || ($idcont > 0)) { $pageonly = " on this page"; }
  print "<p><small>number of hits${pageonly}: <b>$hits_sum</b>";
  if ($bfp_exit == 0) {
    print " (out of $total_cand_sum candidate reactions)<br>\n";
    print "total number of reactions in data collection(s): $n_structures_sum <br>\n";
  } else { echo "<br>\n"; }
  $time = $time_end - $time_start;
  printf("time used for query: %2.3f seconds</small></p>\n", $time);
  //echo "nhits: $nhits<br>\nhitlist: $hitlist<br>";
}                   // if ($mol != '')...

function mk_continueform($dbcont,$idcont,$nhitscont,$hitlistcont) {
  global $myname;
  global $dbstr;
  global $mode;
  global $strict;
  global $stereo;
  global $saferxn;
  global $mol;
  global $jme;
  echo "<form name=\"form2\" action=\"$myname\" method=\"post\">\n";
  echo "<input type=\"hidden\" name=\"mode\" value=\"$mode\">\n";
  echo "<input type=\"hidden\" name=\"strict\" value=\"$strict\">\n";
  echo "<input type=\"hidden\" name=\"stereo\" value=\"$stereo\">\n";
  echo "<input type=\"Submit\" name=\"select\" value=\"&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Continue&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\">\n";
  echo "<input type=\"hidden\" name=\"mol\" value=\"$mol\">\n";
  echo "<input type=\"hidden\" name=\"jme\" value=\"$jme\">\n";
  echo "<input type=\"hidden\" name=\"db\" value=\"$dbstr\">\n";
  echo "<input type=\"hidden\" name=\"dbcont\" value=\"$dbcont\">\n";
  echo "<input type=\"hidden\" name=\"idcont\" value=\"$idcont\">\n";
  echo "<input type=\"hidden\" name=\"nhits\" value=\"$nhitscont\">\n";
  echo "<input type=\"hidden\" name=\"hits\" value=\"$hitlistcont\">\n";
  echo "</form>\n";
}


?>
