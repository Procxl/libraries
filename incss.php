<?php
// incss.php        Norbert Haider, University of Vienna, 2009-2014
// part of MolDB6   last change: 2014-08-13

/**
 * @file incss.php
 * @author Norbert Haider
 * 
 * This script is included by moldbgss.php (which performs
 * (sub)structure or reaction (sub)structure searches. It is responsible
 * for the (sub-)structure part.
 */

echo "<h3>Found structures:</h3><br>\n";

if ($mol !='') { 
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

  // first step: get the molecular statistics of the input structure
  // by piping it through the checkmol program, then do a search
  // in molstat and the fingerprint table(s) ==> this gives a list of candidates

  if ($use_cmmmsrv == 'y') {
    if ($usehfp == 'y') {
      $chkresult = filterthroughcmmm("$safemol", "#### checkmol:axH");   // "a" for charges
    } else {
      $chkresult = filterthroughcmmm("$safemol", "#### checkmol:ax"); 
    }
  } else {
    if ($usehfp == 'y') {
      if ($ostype == 1) { $chkresult = filterthroughcmd("$safemol", "$CHECKMOL -axH - "); }
      if ($ostype == 2) { $chkresult = filterthroughcmd2("$safemol", "$CHECKMOL -axH - "); }
    } else {
      if ($ostype == 1) { $chkresult = filterthroughcmd("$safemol", "$CHECKMOL -ax - "); }
      if ($ostype == 2) { $chkresult = filterthroughcmd2("$safemol", "$CHECKMOL -ax - "); }
    }  
  }
  //echo "chkresult: $chkresult<br>\n";

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

  // determine number of atoms in query structure,
  // reject queries with less than 3 atoms ==>
  // $chkresult contains as the first 2 entries: "n_atoms:nn;n_bonds:nn;"
  $scpos = strpos($chkresult1,";");
  $na_str = substr($chkresult1,8,$scpos-8);
  $na_val = intval($na_str);
  if ( $na_val < 3 ) {
    echo "</table>\n<hr>\n";
    echo "Query structure must contain at least 3 atoms!<br>\n</body></html>\n";
    exit;
  }

  $chkresult2 = str_replace("\n","",$chkresult2);
  if (strpos($chkresult2,";") !== false) {
    $chkresult2 = substr($chkresult2,0,strpos($chkresult2,";"));
  }
  $hfp = explode(",",$chkresult2);

  // now create the SQL query string from checkmol output
  $qstr = str_replace(";"," AND ",$chkresult1);

  // comparison operator for substructure search is ">=", 
  // for exact search it would be "=";
  // in the moment, we always use ">=" and just specify the exact number
  // of atoms and bonds if an exact search is required
  $op = ">=";
  $qstr = str_replace(":",$op,$qstr);
  if ($exact == "y") {
    $qstr = str_replace("n_atoms>=","n_atoms=",$qstr);
    $qstr = str_replace("n_bonds>=","n_bonds=",$qstr);
    $qstr = str_replace("n_rings>=","n_rings=",$qstr);
    $qstr = str_replace("n_C>=","n_C=",$qstr);
  }

  $coreqstr = $qstr;
  $hits_sum = 0;
  $total_cand_sum = 0;
  //$fplbl_str = "";
  $n_structures_sum = 0;
  $nsel = 0;
  $hits_s    = 0;  // number of hit structures
  $hits_r    = 0;  // number of hit reactions
  $hitlist_s = "";
  $hitlist_r = "";


  // read the fragment dictionaries from the fpdef table and store them in an array
  $fpdefqstr = "SELECT fp_id, fpdef FROM $fpdeftable;";
  $fpdefresult = mysql_query($fpdefqstr)
      or die("Could not get fingerprint definition!"); 
  $i = -1;
  while ($fpdefline = mysql_fetch_array($fpdefresult, MYSQL_ASSOC)) {
    $i++;
    $fpdef[$i] = $fpdefline["fpdef"];
  } 
  mysql_free_result($fpdefresult);
  $fp_count = $i + 1;

  foreach ($dba as $db_id) {
    $dbprefix      = $prefix . "db" . $db_id . "_";
    $molstructable = $dbprefix . $molstrucsuffix;
    $moldatatable  = $dbprefix . $moldatasuffix;
    $molstattable  = $dbprefix . $molstatsuffix;
    $molcfptable   = $dbprefix . $molcfpsuffix;
    $pic2dtable    = $dbprefix . $pic2dsuffix;
    $rxnstructable = $dbprefix . $rxnstrucsuffix;
    $rxndatatable  = $dbprefix . $rxndatasuffix;
    $rxncfptable   = $dbprefix . $rxncfpsuffix;

    $qstr = $coreqstr;
    $bfp_exit = 0;
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

    // first, use SD data collections (structures)
    if ($dbtype == 1) {
      $nsel++;
      if (!isset($digits) || (is_numeric($digits) == false)) { $digits = 8; }
      if (!isset($subdirdigits) || (is_numeric($subdirdigits) == false)) { $subdirdigits = 0; }
      if ($subdirdigits < 0) { $subdirdigits = 0; }
      if ($subdirdigits > ($digits - 1)) { $subdirdigits = $digits - 1; }
      if ($usemem == 'T') {
        if (($memstatus & 1) == 1) { $molstattable  .= $memsuffix; }
        if (($memstatus & 2) == 2) { $molcfptable   .= $memsuffix; }
      }
  
      $hfpqstr  = "";
      $hfpnum   = 0;
      $hfpfield = "";
      $hfptest = 0;
      for ($i   = 0; $i <= 15; $i++) {
        $hfpnum = $i + 1;
        if ($hfpnum < 10) { $hfpfield = "hfp0" . $hfpnum; } else { $hfpfield = "hfp" . $hfpnum; }
        @$hfptest = $hfp[$i];
        if ($hfptest > 0) {
          $hfpqstr = $hfpqstr . " AND (${molcfptable}.$hfpfield & $hfp[$i] = $hfp[$i])";
        }
      }
      $orighfpqstr = $hfpqstr;
  
      $bfpusable = 0;
      if ($usebfp == "y") {
        if (($fp_count > 0) && ($usebfp == "y")) {
          if (strlen($options) > 0) {
            $fpoptions = $options;
          } else {
            $fpoptions = '-' . $options;
          }
          if (strpos($fpoptions,"s") === false) {
            $fpoptions = $fpoptions . "s";
          }
          $fpoptions1 = $fpoptions . "F";
          $fpoptions2 = str_replace("s","",$fpoptions1);
          $bfpexact  = 0;    
          for ($i = 0; $i < $fp_count; $i++) {
            $fpnum = $i + 1;
            while (strlen($fpnum) < 2) { $fpnum = "0" . $fpnum; }
            $fpsdf = $fpdef[$i];
            $fpsdf = str_replace("\$","\\\$",$fpsdf);
            $fpchunk = $safemol . "\n\\\$\\\$\\\$\\\$\n" . $fpsdf;
   
            if ($use_cmmmsrv == "y") {    
              // remove extra "\\" for cmmmsrv
              $fpchunk = str_replace("\\\$","\$",$fpchunk);
              $bfp[$i] = filterthroughcmmm("$fpchunk", "#### matchmol: $fpoptions1"); 
            } else {
              $mmcmd = "$MATCHMOL $fpoptions1 -";
              if ($ostype == 1) { $bfp[$i] = filterthroughcmd("$fpchunk ", "$mmcmd"); }
              if ($ostype == 2) { $bfp[$i] = filterthroughcmd2("$fpchunk ", "$mmcmd"); }
            }
            $bfp[$i] = rtrim($bfp[$i]);
            //echo "$bfp[$i] (1st attempt)<br>\n";
            $truematch = 1;
            $bfpnum = intval($bfp[$i]);
            if (!(1&$bfpnum)) {
              if ($use_cmmmsrv == "y") {    
                $bfp[$i] = filterthroughcmmm("$fpchunk", "#### matchmol: $fpoptions2"); 
              } else {
                $mmcmd = "$MATCHMOL $fpoptions2 -";  // second choice
                if ($ostype == 1) { $bfp[$i] = filterthroughcmd("$fpchunk ", "$mmcmd"); }
                if ($ostype == 2) { $bfp[$i] = filterthroughcmd2("$fpchunk ", "$mmcmd"); }
              }
              $bfp[$i] = rtrim($bfp[$i]);
              //echo "$bfp[$i] (2nd attempt)<br>\n";
              $bfp2num = intval($bfp[$i]);
              if ($bfp2num > 0) {
                $truematch = 0;
                $bfpusable = 1;
              }
            } else { $bfpusable = 1; }
            $str = strval($bfp[$i]);
            $lastdigit = substr($str, -1);
            $num = intval($lastdigit);
            // check if $num is odd or even
            if ((1&$num)) {
              $num--;
              $lastdigit = strval($num);
              $bfp[$i] = substr($str,0,-1) . $lastdigit;
              if ($truematch == 1) {
                $bfpexact    = $bfp[$i];
                $bfpexactnum = $fpnum;
              }
            }
          }   // for ...
          if (($bfpexact > 0) && !($exact == "y") && !($strict == "y")) {
            $limit = $maxhits + 1;
            $fpqstr = "SELECT mol_id FROM $molcfptable WHERE (dfp$bfpexactnum & $bfpexact = $bfpexact) LIMIT $limit";
            $bfpresult = mysql_query($fpqstr)
              or die("Query failed! (exact dfp)"); 
            $hits = 0;
            while ($bfpline = mysql_fetch_array($bfpresult, MYSQL_ASSOC)) {
              $mol_id = $bfpline["mol_id"];
              $hits++;
              if ( $hits > $maxhits ) {
                echo "</table>\n<hr>\n";
                echo "<br>Too many hits (>$maxhits)! Aborting....<br>\n</body></html>\n";
                exit;
              }
              showHit($mol_id,"");
              if (($enable_download == "y") && ($hits_s <= $download_limit)) {
                $hits_s++;
                if (strlen($hitlist_s) > 0) { $hitlist_s .= ","; }
                $hitlist_s .= $db_id . ":" . $mol_id;
              }
            } 
            mysql_free_result($bfpresult);
            $bfp_exit = 1;
          }  // if ($bfpexact > 0)
        }    // if ($fp_count > 0)...
      }    // if ($usebfp == "y")
  
      if ($bfp_exit == 0) {
        // get total number of structures in the database
        $n_qstr = "SELECT COUNT(mol_id) AS count FROM $molstructable";
        $n_result = mysql_query($n_qstr)
            or die("Could not get number of entries!"); 
        while ($n_line = mysql_fetch_array($n_result, MYSQL_ASSOC)) {
          $n_structures = $n_line["count"];
        } 
        mysql_free_result($n_result);
      
        $addtbl = $molstattable . '.n_';
        $qstr = str_replace("n_",$addtbl,$qstr);  // add table name to the query string
        $qhdr = "";
        $qftr = " AND (${molstattable}.mol_id = ${molstructable}.mol_id) ";
        $qstr1 = $qstr;
     
        if ($bfpusable == 0) {
          if ($usehfp == 'y') {
            $qhdr = "SELECT ${molstattable}.mol_id, ${molstructable}.struc FROM $molstattable, $molstructable, $molcfptable WHERE ";
            $qstr1 = $qstr1 . $hfpqstr;
            $qftr = $qftr . " AND (${molstattable}.mol_id = ${molcfptable}.mol_id) ";
          } else {
            $qhdr = "SELECT ${molstattable}.mol_id, ${molstructable}.struc FROM $molstattable, $molstructable WHERE ";
          }
          $q1  = $qhdr . $qstr1 . $qftr;
        } else {           // both hfp (1) and dfp (2) are available
          $qstr2 = "";
          for ($i = 0; $i < $fp_count; $i++) {
            $bfpnum = $i + 1;
            while (strlen($bfpnum) < 2) { $bfpnum = '0' . $bfpnum; }
            $qstr2 = $qstr2 . " AND (" . $molcfptable . ".dfp$bfpnum & $bfp[$i] = $bfp[$i])";
          }
          $dfpqstr = $qstr2;
          
          $qftr1 = " AND (${molstattable}.mol_id = ${molcfptable}.mol_id) AND (${molstattable}.mol_id = ${molstructable}.mol_id)";
          $qhdr1 = "SELECT ${molstattable}.mol_id, ${molstructable}.struc FROM $molstattable, $molstructable, $molcfptable WHERE ";
          
          $qstr1 = $qstr1 . $dfpqstr . $hfpqstr;
          
          $q1 = $qhdr1 . $qstr1 . $qftr1;
          $qhdr = $qhdr1;
          $qftr = $qftr1;
        }
            
        $qstr = $q1;
        
        
        if ($use_cmmmsrv == "y") {
          $bs      = 50;                          // block size (number of structures per query SDF)
        } else {
          $bs      = 10;                          // smaller block size (<128K) if we use shell calls
        }
        $maxbmem   = 0;                           // for diagnostic purposes only
        $sqlbsmult = 10;                          // relates $bs to SQL block size (for LIMIT clause)
        $sqlbs     = $bs * $sqlbsmult;
        $mmcmd = "$MATCHMOL $options -";
        $total_cand  = 0;
        $offsetcount = 0;
        $hits        = 0;
      
        // submit the query structure ("needle")
        if ($use_cmmmsrv == "y") {
          $dummy = filterthroughcmmm("$safemol", "#### matchmol: $options"); 
        }
      
        //=============== begin outer loop
        do {
          $offset  = $offsetcount * $sqlbs;
          $qstrlim = $qstr . " LIMIT $offset, $sqlbs";
          #echo "db: $db_id qstrlim: $qstrlim<br>\n";
          $result = mysql_query($qstrlim)
            or die("Query failed! (4a)");    
          $offsetcount ++;
          $n_cand  = mysql_num_rows($result);     // number of candidate structures
          $bi      = 0;                           // counter within block
          $n       = 0;                           // number of candidates already processed
    
          if ($use_cmmmsrv == "y") {
            $qstruct = '';
          } else {
            $qstruct = $safemol;
          }
    
          $total_cand = $total_cand + $n_cand;
         
          while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
            $mol_id = $line["mol_id"];
            $haystack = $line["struc"];
              
            // "burst mode":
            // store candidate numbers (mol_id) in array $b
            // store query as $qstruct: consists of several haystack molecules (up to $bs)
            // store result of matchmol invocation in array $br
            $b[$bi] = $mol_id;
            if ($use_cmmmsrv == "y") {
              if ($qstruct != '') { $qstruct = $qstruct . "\n\$\$\$\$\n"; }
              $qstruct = $qstruct . $haystack;    
            } else {
              $qstruct = $qstruct . "\n\\\$\\\$\\\$\\\$\n" . $haystack;    
            }
            $bi ++;
            $n ++;
            if (($bi == $bs) || ($n == $n_cand) || ($maxbmem > 120000)) {
              if (strlen($qstruct) > $maxbmem) { $maxbmem = strlen($qstruct); }  // for diagnostics only
              if ($use_cmmmsrv == "y") {
                $matchresult = filterthroughcmmm("$qstruct", "#### mcreset"); 
              } else {
                if ($ostype == 1) {$matchresult = filterthroughcmd("$qstruct ", "$mmcmd"); }
                if ($ostype == 2) {$matchresult = filterthroughcmd2("$qstruct ", "$mmcmd"); }
              }
              $br = explode("\n", $matchresult);
              for ($i = 0; $i < $bi; $i++) { 
                if (strpos($br[$i],":T") !== FALSE) {
                  $hits ++;
                  // output of the hits, if they are not too many...
                  if ( $hits > $maxhits ) {
                    echo "</table>\n<hr>\n";
                    echo "<br>Too many hits (>$maxhits)! Aborting....<br>\n</body></html>\n";
                    exit;
                  }
                  showHit($b[$i],"");
                  if (($enable_download == "y") && ($hits_s <= $download_limit)) {
                    $hits_s ++;
                    if (strlen($hitlist_s) > 0) { $hitlist_s .= ","; }
                    $hitlist_s .= $db_id . ":" . $b[$i];
                  }
                  
                } // else echo "<br>miss: $b[$i]<br>\n";
              }
              if ($use_cmmmsrv == "y") {
                $qstruct = '';
              } else {
                $qstruct = $safemol;
              }
              $bi = 0;
              $maxbmem = 0;
            }
          }              // while ($line ....
          mysql_free_result($result);
        
        } while ($n_cand >= $sqlbs);
        //=============== end outer loop
  
      }  // if $bfp_exit == 0
  
      $hits_sum = $hits_sum + $hits;
      $total_cand_sum = $total_cand_sum + $total_cand;
      //$fplbl_str = $fplbl_str . $fplbl;
      $n_structures_sum = $n_structures_sum + $n_structures;

    }  // end if ($dbtype == 1)
    
    if (($dbtype == 2) && ($enablereactions == "y")) {    // reactions

      // get total number of entries in the database
      $n_qstr = "SELECT COUNT(rxn_id) AS count FROM $rxnstructable";
      $n_result = mysql_query($n_qstr)
          or die("Could not get number of entries!"); 
      while ($n_line = mysql_fetch_array($n_result, MYSQL_ASSOC)) {
        $n_structures = $n_line["count"];
      } 
      mysql_free_result($n_result);

      //create the dictionary-based fingerprints
      $moldfp = "";
      for ($k = 0; $k < $fp_count; $k++) {
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
        //echo "dictionary-based fingerprints for reactant $i + dictionary $k\n$dfpstr\n";
      }  // for..
      $firstpart = substr($moldfp,0,-1);
      $lastdigit = intval(substr($moldfp,-1,1));
      if ((1&$lastdigit)) {
        $lastdigit--;
        $lastdigit = strval($lastdigit);
        $moldfp = $firstpart . $lastdigit;
      }

      // create the hash-based fingerprints
      if ($use_cmmmsrv == 'y') {
        $molhfp = filterthroughcmmm($safemol,"#### checkmol:H");
      } else {
        if ($ostype == 1) {$molhfp = filterthroughcmd($safemol,"$CHECKMOL -H - "); }
    	if ($ostype == 2) {$molhfp = filterthroughcmd2($safemol,"$CHECKMOL -H - "); }
      }
      $hfparr = explode(";",$molhfp);
      $molhfp = $hfparr[0];
      $qstr = "";
      $qstr .= mk_fpqstr("dfp",$moldfp);
      if (strlen($qstr) > 0) { $qstr .= " AND"; }
      $qstr .= mk_fpqstr("hfp",$molhfp);

      if ($use_cmmmsrv == "y") {
        $bs      = 50;                          // block size (number of structures per query SDF)
      } else {
        $bs      = 10;                          // smaller block size (<128K) if we use shell calls
      }
      $maxbmem   = 0;                           // for diagnostic purposes only
      $sqlbsmult = 10;                          // relates $bs to SQL block size (for LIMIT clause)
      $sqlbs     = $bs * $sqlbsmult;
      $mmcmd = "$MATCHMOL $options -";
      $total_cand  = 0;
      $offsetcount = 0;
      $hits        = 0;

      //=============== begin outer loop (LIMIT)
      do {
        $offset  = $offsetcount * $sqlbs;
        $qstrlim = "SELECT rxn_id FROM $rxncfptable WHERE " . $qstr . " GROUP BY rxn_id LIMIT $offset, $sqlbs";
        $result = mysql_query($qstrlim)
          or die("Query failed! (4a_outer)");    
        $offsetcount ++;
        $n_cand  = mysql_num_rows($result);     // number of candidate structures
        $bi      = 0;                           // counter within block
        $n       = 0;                           // number of candidates already processed
        $total_cand = $total_cand + $n_cand;
        while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {   // outer loop ( rxn_id)
          $rxn_id = $line["rxn_id"];
          $total_cand++;
          $qstr_struc = "SELECT struc FROM $rxnstructable WHERE rxn_id = $rxn_id";
          $result2 = mysql_query($qstr_struc)
          or die("Query failed! (4a_struc)");    
          $line2 = mysql_fetch_row($result2);
          $struc    = $line2[0];
          $struc = str_replace("\n","\r\n",$struc);
          $struc = str_replace("\r\r\n","\r\n",$struc);
          $strucmatch = matchmolrxn($safemol,$struc,$options);
          if ($strucmatch == TRUE) {
            $hits++;
            $hits_r ++;
            // output of the hits, if they are not too many...
            if ( $hits > $maxhits ) {
              echo "</table>\n<hr>\n";
              echo "<br>Too many hits (>$maxhits)! Aborting....<br>\n</body></html>\n";
              exit;
            }
            showHitRxn($rxn_id,"");
            if (($enable_download == "y") && ($hits_r <= $download_limit)) {
              if (strlen($hitlist_r) > 0) { $hitlist_r .= ","; }
              $hitlist_r .= $db_id . ":" . $rxn_id;
            }
          }
        }  // while line
      } while ($n_cand >= $sqlbs);
      //=============== end outer loop  (LIMIT)
      $hits_sum = $hits_sum + $hits;
      $total_cand_sum = $total_cand_sum + $total_cand;
      $n_structures_sum = $n_structures_sum + $n_structures;

    }  // end if ($dbtype == 2)

  }  // foreach

  echo "</table>\n<hr>\n";

  if ($use_cmmmsrv == "y") {
    socket_write($socket,'#### bye');
    socket_close($socket);
  }

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

  
  print "<p><small>number of hits: <b>$hits_sum</b>";
  if ($bfp_exit == 0) {
    print " (out of $total_cand_sum candidate structures)<br>\n";
    print "total number of entries in data collection(s): $n_structures_sum <br>\n";
  } else { echo "<br>\n"; }
  $time = $time_end - $time_start;
  printf("time used for query: %2.3f seconds</small></p>\n", $time);
}                  // if ($mol != '')...
?>
