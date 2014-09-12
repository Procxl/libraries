<?php
// rxnfunct.php    Norbert Haider, University of Vienna, 2010-2014
// a collection of reaction functions for MolDB6, last change: 2014-07-10

/**
 * @file rxnfunct.php
 * @author Norbert Haider
 *
 * A collection of reaction functions for MolDB6
 */

function analyze_rxnfile($myrxn) {
  $result = "";
  $rxnarr = split("\n",$myrxn);
  $nrmol = 0;
  $npmol = 0;
  $lcount = count($rxnarr);
  if ($lcount > 4) {
    $tmpline = $rxnarr[4];
    $nrmol = intval(substr($tmpline,0,3));
    $npmol = intval(substr($tmpline,3,3));
  }
  $result = "nrmol=" . $nrmol . ";npmol=" . $npmol;
  return($result);
}

function get_nrmol($mydescr) {
  $result = 0;
  $arr1 = split(";",$mydescr);
  if (count($arr1) > 1) {
    $tmp1 = $arr1[0];
    if (strpos($tmp1,"nrmol") !== FALSE) {
      $arr2 = split("=",$tmp1);
      if (count($arr2) > 1) {
        $tmp2 = $arr2[1];    
        $result = intval($tmp2);
      }    
    }
  }
  return($result);
}

function get_npmol($mydescr) {
  $result = 0;
  $arr1 = split(";",$mydescr);
  if (count($arr1) > 1) {
    $tmp1 = $arr1[1];
    if (strpos($tmp1,"npmol") !== FALSE) {
      $arr2 = split("=",$tmp1);
      if (count($arr2) > 1) {
        $tmp2 = $arr2[1];    
        $result = intval($tmp2);
      }    
    }
  }
  return($result);
}

function get_atomlabels($mymol) {
  $result  = "";
  $n_atoms = 0;
  $n_bonds = 0;
  $mrec = explode("\n",$mymol);
  $statline = $mrec[3];
  $n_atoms = substr($statline,0,3);
  $n_atoms = intval($n_atoms);
  $n_bonds = substr($statline,3,3);
  $n_bonds = intval($n_bonds);
  for ($i = 0; $i < $n_atoms; $i++) {
    $aline = $mrec[4+$i];
    $lbl = substr($aline,60,3);  // attention: old JME used wrong column (66-69)
    $lbl = trim($lbl);
    if (($lbl !== "0") && (strlen($lbl) > 0)) {
      $anum = $i + 1;
      if (strlen($result) > 0) { $result .= ","; }
      $result .= $anum . "(" . $lbl . ")";
    }
  }
  return($result);
}

function get_labelstr($mystring) {
  $result = "";
  $opos = strpos($mystring,"(");
  $cpos = strpos($mystring,")");
  if (($opos > 0) && ($cpos > $opos)) {
    $result = substr($mystring,$opos+1,$cpos - $opos - 1);
  }
  return($result);
}

function get_anumstr($mystring) {
  $result = $mystring;
  $opos = strpos($mystring,"(");
  if ($opos > 0) {
    $result = substr($mystring,0,$opos);
  }
  return($result);
}

function get_molnumstr($mystring) {
  $result = $mystring;
  $opos = strpos($mystring,":");
  if ($opos > 0) {
    $result = substr($mystring,0,$opos);
  }
  return($result);
}

function add_labels($molid,$mylabels) {
  global $n_labels;
  global $label_list;
  $nlbl = "";
  $lblarr = explode(",",$mylabels);
  foreach ($lblarr as $item) {
    $isnew = TRUE;
    $itemstr = get_labelstr($item);
#    foreach ($label_list as $oldlbl) {    // check if a label is unique
#      $arr1 = explode(":",$oldlbl);       // in this structure
#      $omid = $arr1[0];
#      $olbl = get_labelstr($oldlbl);
#      if (($omid == $molid) && ($olbl == $itemstr)) { $isnew = FALSE; }
#    }
    if ($isnew == TRUE) {
      $nlbl = $molid . ":" . $item;
      $label_list[$n_labels] = $nlbl;
      $n_labels++;
    }
  }
}

function clear_ambigouslabels($mylabel_list) {
  $n_mylabels = count($mylabel_list);
  $acount = 0;
  $lcount = 0;
  $astr = "";
  $lstr = "";
  for ($i = 0; $i < $n_mylabels; $i++) {
    $oldlbl = $mylabel_list[$i];
    $omol = get_molnumstr($oldlbl);
    $olbl = get_labelstr($oldlbl);
    $isambigous = FALSE;
    $occurrence = 0;
    for ($j = 0; $j < $n_mylabels; $j++) {
      $newlbl = $mylabel_list[$j];
      $nmol = get_molnumstr($newlbl);
      $nlbl = get_labelstr($newlbl);
      if (($omol == $nmol) && ($olbl == $nlbl) && ($olbl !== "0")) { $occurrence++; }
    }
    if ($occurrence > 1) { $isambigous = TRUE; }
    if ($isambigous == TRUE) {
      //echo "found ambigous label: $olbl !!\n";
      for ($j = 0; $j < $n_mylabels; $j++) {
        $newlbl = $mylabel_list[$j];
        $na = get_anumstr($newlbl);
        $nlbl = get_labelstr($newlbl);
        if ($olbl == $nlbl) { 
          $mylabel_list[$j] = $na . "(0)";   // "0" labels will be ignored
        }
      }
    }
  }
  return($mylabel_list);
}

function print_labels() {
  global $n_labels;
  global $label_list;
  foreach ($label_list as $item) {
    echo " $item \n";
  }
}

function make_maps() {
  global $n_labels;
  global $label_list;
  global $n_maps;
  global $map_list;
  for ($il = 0; $il < $n_labels; $il++) {
    $item = $label_list[$il];
    $lblstr = get_labelstr($item);
    $a1 = get_anumstr($item);
    for ($jl = 0; $jl < $n_labels; $jl++) {
      if ($il != $jl) {
        $item2 = $label_list[$jl];
        $a2 = get_anumstr($item2);
        $lblstr2 = get_labelstr($item2);
        if ($lblstr == $lblstr2) {
          if ((substr($a1,0,1) == "r") && (substr($a2,0,1) == "p") &&
              ($lblstr !== "0") && ($lblstr2 !== "0")) {
            $mapstr = $a1 . "=" . $a2;
            //echo "map detected: $mapstr\n";
            $map_list[$n_maps] = $mapstr;
            $n_maps++;
          }
        }
      }
    }
  }
}

function print_maps($mymap_list) {
  //global $n_maps;
  //global $map_list;
  foreach ($mymap_list as $item) {
    echo " $item \n";
  }
}

function add_molfp($molfpsum,$molfp) {
  $result = $molfp;
  if (strlen($molfpsum) > 0) {
    $oldarr1 = explode(";",$molfpsum);
    $molfpsum = $oldarr1[0];
    $oldarr = explode(",",$molfpsum);
    $newarr1 = explode(";",$molfp);
    $molfp = $newarr1[0];
    $newarr = explode(",",$molfp);
    if (count($oldarr) != count($newarr)) { 
      return($result); 
      exit;
    }
    $n = count($oldarr);
    $tmpstr = "";
    for ($i = 0; $i < $n; $i++) {
      $tmpint1 = $oldarr[$i];
      $tmpint2 = $newarr[$i];
      //$tmpint3 = $tmpint1 | $tmpint2;   // this does not work with large numbers
      $qstr = "SELECT " . $tmpint1 . " | " . $tmpint2 . " AS bitsum";
      $result = mysql_query($qstr)
      or die("Query failed! (1c3-e)");
      $line = mysql_fetch_row($result);
      $tmpint3 = trim($line[0]);
      if (strlen($tmpstr) > 0) { $tmpstr .= ","; }
      $tmpstr .= $tmpint3;
    }
    $result = $tmpstr;
  }
  return($result);
}

function matchrxn($qrxn,$crxn,$cmaps,$options) {
  global $use_cmmmsrv;
  global $ostype;
  global $MATCHMOL;
  global $n_labels;
  global $n_maps;
  global $map_list;
  global $debug;

  $result   = TRUE;
  $rxndescr = analyze_rxnfile($qrxn);
  $nqrmol   = get_nrmol($rxndescr);
  $nqpmol   = get_npmol($rxndescr);
  $rxndescr = analyze_rxnfile($crxn);
  $ncrmol   = get_nrmol($rxndescr);
  $ncpmol   = get_npmol($rxndescr);
  $optstr   = $options;

  if (($nqrmol == 0) && ($ncpmol == 0)) { $result = FALSE; }  // some initial check
  if (($nqpmol == 0) && ($ncrmol == 0)) { $result = FALSE; }

  if ($result == TRUE) {
    if ($use_cmmmsrv == "y") {
      $separator = "\$\$\$\$"; 
      $lf = "\n";
    } else {
      $separator = "\\\$\\\$\\\$\\\$";
      if ($ostype == 1) { $lf = "\n"; }      // Linux
      if ($ostype == 2) { $lf = "\r\n"; }    // Windows
    }

    $allmol    = array();
    $qrmol     = array();
    $qpmol     = array();
    $crmol     = array();
    $cpmol     = array();
    $allmol    = explode("\$MOL\r\n",$qrxn);
    $header    = $allmol[0];
    $label_list = array();
    $map_list  = array();
    $n_labels  = 0;
    $n_maps    = 0;
    $n_qmaps   = 0;
    $n_cmaps   = 0;
    $qmap_list = array();
    $cmap_list = array();

    if ($nqrmol > 0) {
      for ($i = 0; $i < $nqrmol; $i++) {
        $qrmol[$i] = $allmol[($i+1)];
        $mnum = $i + 1;
        if ($debug > 2) { debug_output("query reactant no. $mnum:\n$qrmol[$i]\n"); }
      }
    }
    if ($nqpmol > 0) {
      for ($i = 0; $i < $nqpmol; $i++) {
        $qpmol[$i] = $allmol[($i+1+$nqrmol)];
        $mnum = $i + 1;
        if ($debug > 2) { debug_output("query product no. $mnum:\n$qpmol[$i]\n"); }
      }
    }

    $qmaps = get_maps($qrxn);
    $qmap_list = explode(",",$qmaps);
    $n_qmaps = count($qmap_list);
    if (strlen($qmaps) < 8) { $n_qmaps = 0; }
    
    $allmol = "";
    $allmol = explode("\$MOL\r\n",$crxn);
    $header = $allmol[0];

    // reset labels and maps
    $n_maps = 0;
    $map_list = ("");
    $n_labels = 0;
    $label_list = ("");
  
    if ($ncrmol > 0) {
      for ($i = 0; $i < $ncrmol; $i++) {
        $crmol[$i] = $allmol[($i+1)];
        $mnum = $i + 1;
        if ($debug > 2) { debug_output("candidate reactant no. $mnum:\n$crmol[$i]\n"); }
      }
    }
    if ($ncpmol > 0) {
      for ($i = 0; $i < $ncpmol; $i++) {
        $cpmol[$i] = $allmol[($i+1+$ncrmol)];
        $mnum = $i + 1;
        if ($debug > 2) { debug_output("candidate product no. $mnum:\n$cpmol[$i]\n)"); }
      }
    }
    //echo "<pre>$qrmol[0]\n$qpmol[0]\n\n$crmol[0]\n$cpmol[0]</pre>";

    //$cmaps = get_maps($crxn);    // this is now retrieved from rxnstructable
    $cmap_list = explode(",",$cmaps);
    $n_cmaps = count($cmap_list);
    if (strlen($cmaps) < 8) { $n_cmaps = 0; }
  
    // set up and initialize the two match matrices (reactant and product)
    $rmm = array();
    for ($iq = 0; $iq < $nqrmol; $iq++) {
      for ($ic = 0; $ic < $ncrmol; $ic++) {
        $rmm[$iq][$ic] = "0";
      }
    }
    $pmm = array();
    for ($iq = 0; $iq < $nqpmol; $iq++) {
      for ($ic = 0; $ic < $ncpmol; $ic++) {
        $pmm[$iq][$ic] = "0";
      }
    }
    
    if (($n_qmaps > 0) && ($n_cmaps > 0)) { $optstr .= "n"; }
    $mmcmd = "$MATCHMOL $optstr -";
    
    //============ first match the reactants, if there are any
    if (($nqrmol > 0) && ($ncrmol > 0)) {
      // assemble the SDF files to be passed to matchmol
      for ($iq = 0; $iq < $nqrmol; $iq++) {
        $qmol = rtrim($qrmol[$iq]);
        $sdf = $qmol . $lf . $separator . $lf;
        for ($ic = 0; $ic < $ncrmol; $ic++) {
          $cmol = rtrim($crmol[$ic]);
          $sdf .= $cmol . $lf . $separator . $lf;
        }   // end   for ($ic ...
        //echo "<pre>reactant query for query reactant no. $iq:\n$sdf\n</pre>";
        // now do the match
        
        if ($use_cmmmsrv == "y") {
          $matchresult = filterthroughcmmm("$sdf", "#### matchmol:$optstr"); 
        } else {
          if ($ostype == 1) { $matchresult = filterthroughcmd("$sdf ", "$mmcmd"); }
          if ($ostype == 2) { 
            $sdf = str_replace("\r","",$sdf);       // for Windows
            $sdf = str_replace("\n","\r\n",$sdf);   // for Windows
            $matchresult = filterthroughcmd2("$sdf ", "$mmcmd"); 
          }
        }
        //echo "<pre>match result for for query reactant no. $iq:\n$matchresult\n</pre>";
        $br = explode("\n", $matchresult);
        $nr = count($br);
        for ($ir = 0; $ir < $nr; $ir++) {
          if (strpos($br[$ir],":T") !== FALSE) {
            $rmm[$iq][$ir] = $br[$ir];  
          }
        }
      } // end   for ($iq ....
      // we have to clean up the matrix to remove multiple hits of one query mol
      if ($nqrmol > 1) {
        $rmm = cleanup_matrix($rmm);
      }
    }  // end    if ($nqrmol > 0)

    //============ now match the products, if there are any
    if (($nqpmol > 0) && ($ncpmol > 0)) {
      // assemble the SDF files to be passed to matchmol
      for ($iq = 0; $iq < $nqpmol; $iq++) {
        $qmol = rtrim($qpmol[$iq]);
        $sdf = $qmol . $lf . $separator . $lf;
        for ($ic = 0; $ic < $ncpmol; $ic++) {
          $cmol = rtrim($cpmol[$ic]);
          $sdf .= $cmol . $lf . $separator . $lf;
        }   // end   for ($ic ...
        //echo "<pre>product query for query product no. $iq:\n$sdf\n</pre>";
        // now do the match
        
        //$sdf = str_replace("\r","",$sdf);
        if ($use_cmmmsrv == "y") {
          $matchresult = filterthroughcmmm("$sdf", "#### matchmol:$optstr"); 
        } else {
          if ($ostype == 1) { $matchresult = filterthroughcmd("$sdf ", "$mmcmd"); }
          if ($ostype == 2) { 
            $sdf = str_replace("\r","",$sdf);       // for Windows
            $sdf = str_replace("\n","\r\n",$sdf);   // for Windows
            $matchresult = filterthroughcmd2("$sdf ", "$mmcmd"); 
          }
        }
        //echo "<pre>match result for for query product no. $iq:\n$matchresult\n</pre>";
        $br = explode("\n", $matchresult);
        $nr = count($br);
        for ($ir = 0; $ir < $nr; $ir++) {
          if (strpos($br[$ir],":T") !== FALSE) {
            $pmm[$iq][$ir] = $br[$ir];  
          }
        }
      } // end   for ($iq ....
      // we have to clean up the matrix to remove multiple hits of one query mol
      if ($nqpmol > 1) {
        $pmm = cleanup_matrix($pmm);
      }
    }  // end    if ($nqpmol > 0)

    //echo "<pre>\n";
    if (($debug == 4) || ($debug == 6))  {
      echo "<pre>now the reactant match matrix looks like this:\n";
      print_matrix($rmm);
      echo "and the product match matrix looks like this:\n";
      print_matrix($pmm);
      echo "</pre>";
    }
    
    // and finally we check if every query structure matches a candidate structure
    // first check: reactants
    $foundallreactants = TRUE;
    for ($iq = 0; $iq < $nqrmol; $iq++) {
      $foundthisreactant = FALSE;
      for ($ic = 0; $ic < $ncrmol; $ic++) {
        if (strpos($rmm[$iq][$ic],":T") !== FALSE) { $foundthisreactant = TRUE; }
      }
      if ($foundthisreactant == FALSE) { $foundallreactants = FALSE; }
    }
    // second check: products
    $foundallproducts = TRUE;
    for ($iq = 0; $iq < $nqpmol; $iq++) {
      $foundthisproduct = FALSE;
      for ($ic = 0; $ic < $ncpmol; $ic++) {
        if (strpos($pmm[$iq][$ic],":T") !== FALSE) { $foundthisproduct = TRUE; }
      }
      if ($foundthisproduct == FALSE) { $foundallproducts = FALSE; }
    }
    // now let's draw the conclusion:
    if (($foundallreactants == FALSE) || ($foundallproducts == FALSE)) { 
      $result = FALSE; 
    } else {
      // now we should consider atom mappings, if present
      if (($n_qmaps > 0) && ($n_cmaps > 0)) {
        $allmapsconfirmed = TRUE;
        for ($iqm = 0; $iqm < $n_qmaps; $iqm++) {
          $thismapconfirmed = FALSE;
          if ($debug > 2) { debug_output("now checking query map no. $iqm: $qmap_list[$iqm]\n"); }
          // split map into reactant and product part
          $qmarr = explode("=",$qmap_list[$iqm]);
          $qrpart = $qmarr[0];
          $qppart = $qmarr[1];
          if ($debug > 2) { debug_output("query reactant: $qrpart     query product: $qppart\n"); }
          // extract mol id and atom id
          $qrarr = explode(":",$qrpart);
          $qrmid = $qrarr[0];
          $qrmidn = str_replace("r","",$qrmid);
          $qrmidn = intval($qrmidn) - 1;
          $qraid = $qrarr[1];
          $qparr = explode(":",$qppart);
          $qpmid = $qparr[0];
          $qpmidn = str_replace("p","",$qpmid);
          $qpmidn = intval($qpmidn) - 1;
          $qpaid = $qparr[1];
          // now look up in the reactant match matrix which candidate reactant(s) 
          // match with this query reactant
          if ($debug > 2) { debug_output("   checking reactant match matrix for query reactant $qrmid ($qrmidn)\n"); }
          for ($irmm = 0; $irmm < $ncrmol; $irmm++) {
            if ($rmm[$qrmidn][$irmm] !== "0") {
              // assemble the id string
              $crmidn = $irmm;
              $crmid = $crmidn + 1;
              $crmid = "r" . $crmid . ":";
              if ($debug > 2) { debug_output("   ---- there is a matching candidate reactant: $crmid ($crmidn)\n"); }
              // now check if there is a suitable counterpart on the product side
              if ($debug > 2) { debug_output("   checking product match matrix for query product $qpmid ($qpmidn)\n"); }
              for ($ipmm = 0; $ipmm < $ncpmol; $ipmm++) {
                if ($pmm[$qpmidn][$ipmm] !== "0") {
                  // assemble the id string
                  $cpmidn = $ipmm;
                  $cpmid = $cpmidn + 1;
                  $cpmid = "p" . $cpmid . ":";
                  if ($debug > 2) { debug_output("   ---- there is a matching candidate product: $cpmid ($cpmidn)\n"); }
                  // now we can check the atom mappings....
                  // first, go through the list of candidate maps and look for a map
                  for ($icm = 0; $icm < $n_cmaps; $icm++) {
                    $cmarr  = explode("=",$cmap_list[$icm]);
                    $crpart = $cmarr[0];
                    $cppart = $cmarr[1];
                    if ($debug > 2) { debug_output("  checking query map $iqm against candidate map $icm\n"); }
                    if ($debug > 2) { debug_output("    ==== candidate reactant: $crpart     candidate product: $cppart\n"); }
                    // extract mol id and atom id
                    $crarr = explode(":",$crpart);
                    $crmid2 = $crarr[0];
                    $crmid2n = str_replace("r","",$crmid2);
                    $crmid2n = intval($crmid2n) - 1;
                    $craid2 = trim($crarr[1]);
                    $cparr = explode(":",$cppart);
                    $cpmid2 = $cparr[0];
                    $cpmid2n = str_replace("p","",$cpmid2);
                    $cpmid2n = intval($cpmid2n) - 1;
                    $cpaid2 = trim($cparr[1]);
                    
                    // check for presence of both mol identifiers
                    if ((strpos($crpart,$crmid) !== FALSE) && (strpos($cppart,$cpmid) !== FALSE)) {
                      if ($debug > 2) { debug_output("    **** found a map: $cmap_list[$icm]\n"); }
                      // now do some atom-number translation...
                      // first, get the atom match matrix for this pair of reactants
                      $rmmitem = $rmm[$qrmidn][$irmm];
                      $rmmitemarr = explode(":T ",$rmmitem);
                      $rmmitem = $rmmitemarr[1];
                      // next, get the atom match matrix for this pair of products
                      $pmmitem = $pmm[$qpmidn][$ipmm];
                      $pmmitemarr = explode(":T ",$pmmitem);
                      $pmmitem = $pmmitemarr[1];
                      if ($debug > 2) { 
                        debug_output("    reactant atom map: $rmmitem\n");
                        debug_output("    product atom map:  $pmmitem\n");
                      }
                      // now extract all possible orientations from the atom maps
                      $ror = explode(".",$rmmitem);
                      $n_ror = count($ror);
                      $por = explode(".",$pmmitem);
                      $n_por = count($por);
                      for ($i_ror = 0; $i_ror < $n_ror; $i_ror++) {
                        if ($debug > 4) { debug_output("    trying reactant orientation no. $i_ror: $ror[$i_ror]\n"); }
                        $ratom = $ror[$i_ror];
                        $ratomarr = explode(";",$ratom);
                        foreach ($ratomarr as $ra) {
                          //echo "    reactant atom pair: $ra\n";
                          $ra2arr = explode("=",$ra);
                          $rqa = $ra2arr[0];
                          $rcalist = "";
                          $rcalist = $ra2arr[1];
                          if ($debug > 4) { debug_output("    query reactant atom $rqa corresponds to candidate reactant atom(s): $rcalist\n"); }
                          if ($qraid == $rqa) { 
                            if ($debug > 4) { debug_output("    .... this is interesting! (rqa = $rqa)\n");  }
                            // get all sub-orientations
                            $rca2arr = explode(",",$rcalist);
                            foreach ($rca2arr as $rca) {
                              $rca = trim($rca);  // this is important!
                              if ($debug > 4) { 
                                debug_output("    labelled query reactant atom $rqa corresponds to candidate reactant atom: $rca\n");
                                debug_output("      craid2 = $craid2 rca = $rca rqa = $rqa\n");
                              }
                              if ($rca == $craid2) {
                                if ($debug > 4) { 
                                  debug_output("    found a label counterpart for $qraid: $craid2!!\n");
                                  debug_output("    who is the counterpart of query product atom $qpaid? is it $cpaid2?\n");
                                }
                                // now look up the product end...
                                // check all possible reactant orientations....
                                for ($i_por = 0; $i_por < $n_por; $i_por++) {
                                  if ($debug > 4) { debug_output("    trying product orientation no. $i_por: $por[$i_por]\n"); }
                                  $patom = $por[$i_por];
                                  $patomarr = explode(";",$patom);
                                  foreach ($patomarr as $pa) {
                                    //echo "    product atom pair: $pa\n";
                                    $pa2arr = explode("=",$pa);
                                    $pqa = trim($pa2arr[0]);
                                    $pcalist = "";
                                    $pcalist = $pa2arr[1];
                                    if ($debug > 6) { debug_output("        query product atom $pqa corresponds to candidate product atom(s): $pcalist\n"); }
                                    if ($qpaid == $pqa) {
                                      if ($debug > 6) { debug_output("        NOW IT BECOMES INTERESTING!\n"); }
                                      // again, get all sub-orientations
                                      $pca2arr = explode(",",$pcalist);
                                      foreach ($pca2arr as $pca) {
                                        $pca = trim($pca);  // this is important!
                                        if ($debug > 6) { debug_output("            query product atom $qpaid corresponds to candidate product atom: $pca\n"); }
                                        if ($cpaid2 == $pca) {
                                          $thismapconfirmed = TRUE;
                                          if ($debug > 2) { debug_output("BINGO!!!! confirmed query map no. $iqm\n"); }
                                          break 7;   // immediately stop looping (7 depth levels!)
                                        }
                                      }
                                    }
                                  }
                                }
                              }
                            }  
                          }
                        }
                      }
                    }
                  }
                }
              }
            }
          }
          if ($thismapconfirmed == FALSE) { 
            $allmapsconfirmed = FALSE; 
            if ($debug > 2) { debug_output("==== could not confirm query map no. $iqm\n"); }
          }
        }
        if ($allmapsconfirmed == FALSE) { $result = FALSE; }
      }   // end of "if we have both maps..."
    } 

  }  // end if ($result == TRUE) from initial check
  return($result);
}

function matchmolrxn($qmol,$crxn,$options) {
  global $use_cmmmsrv;
  global $ostype;
  global $mmcmd;
  global $debug;

  $result   = TRUE;
  if ((strlen($qmol) < 10) || (strlen($crxn) < 20)) { $result = FALSE; }

  if ($result == TRUE) {
    $result = FALSE;
    if ($use_cmmmsrv == "y") {
      $separator = "\$\$\$\$"; 
      $lf = "\n";
    } else {
      $separator = "\\\$\\\$\\\$\\\$";
      if ($ostype == 1) { $lf = "\n"; }      // Linux
      if ($ostype == 2) { $lf = "\r\n"; }    // Windows
    }

    $allmol    = array();
    $allmol    = explode("\$MOL\r\n",$crxn);
    $header    = $allmol[0];
    $mol = array();
    $mol = array_slice($allmol,1);
    $nmol = count($mol);

    if ($debug > 4) { 
      debug_output("nmol: $nmol\n");
    }
    
    //============ now match
    if ($nmol > 0) {
      // assemble the SDF files to be passed to matchmol
      $qmol = rtrim($qmol);
      $sdf = $qmol . $lf . $separator . $lf;
      for ($ic = 0; $ic < $nmol; $ic++) {
        $cmol = rtrim($mol[$ic]);
        $sdf .= $cmol . $lf . $separator . $lf;
      }   // end   for ($ic ...
      if ($debug > 4) { 
        debug_output("$sdf\n");
      }
      
      if ($use_cmmmsrv == "y") {
        $matchresult = filterthroughcmmm("$sdf", "#### matchmol:$options"); 
      } else {
        if ($ostype == 1) { $matchresult = filterthroughcmd("$sdf ", "$mmcmd"); }
        if ($ostype == 2) { 
          $sdf = str_replace("\r","",$sdf);       // for Windows
          $sdf = str_replace("\n","\r\n",$sdf);   // for Windows
          $matchresult = filterthroughcmd2("$sdf ", "$mmcmd"); 
        }
      }
      //echo "<pre>match result:\n$matchresult\n</pre>";
      $br = explode("\n", $matchresult);
      $nr = count($br);
      for ($ir = 0; $ir < $nr; $ir++) {
        if (strpos($br[$ir],":T") !== FALSE) {
          $result = TRUE;  
        }
      }
    }  // end    if ($nmol > 0)
  }  // end if ($result == TRUE) from initial check
  return($result);
}

function cleanup_matrix($m) {
  $nr = count($m,0);
  $nc = (count($m,1)/count($m,0))-1;
  // cycle 8 times through the matrix, this should be sufficient...
  for ($n = 0; $n < 8; $n++) {
    for ($q = 0; $q < $nr; $q++) {
      $n_hits = 0;
      for ($c = 0; $c < $nc; $c++) {
        if (strpos($m[$q][$c],":T") !== FALSE) {
          $n_hits++;
          $hitcol = $c;
        }  
      }
      if ($n_hits == 1) {   // found a unique match ==> kick out all other hits in this column
        for ($k = 0; $k < $nr; $k++) {
          if ($k != $q) { $m[$k][$hitcol] = "0"; }
        }
      }
    }
  }
  return($m);
}

function print_matrix($myarr) {   // for diagnostics only; enclose in <pre>...</pre>
  $rows = count($myarr,0);
  $cols = (count($myarr,1)/count($myarr,0))-1;
  //print "There are {$rows} rows and {$cols} columns in the table!\n\n";
  for ($r = 0; $r < $rows; $r++) {
    for ($c = 0; $c < $cols; $c++) {
      $item = $myarr[$r][$c];
      while (strlen($item) < 4) { $item = ' ' . $item; }
      echo "$item ";
    }
    echo "\n";
  }
  echo "\n";
}

function get_maps($myrxn) {
  $result = "";
  $rxndescr = analyze_rxnfile($myrxn);
  $nrmol = get_nrmol($rxndescr);
  $npmol = get_npmol($rxndescr);
  $allmol = array();
  $allmol = explode("\$MOL\r\n",$myrxn);
  $header = $allmol[0];
  $label_list = array();
  $map_list = array();
  $n_labels = 0;
  $n_maps = 0;
  if (($nrmol > 0) && ($npmol > 0)) {
    if ($nrmol > 0) {
      for ($i = 0; $i < $nrmol; $i++) {
        $rmol[$i] = $allmol[($i+1)];
        $mnum = $i + 1;
        $labels = get_atomlabels($rmol[$i]);
        $mid = "r" . $mnum;
        if (strlen($labels) > 0) {
          $larr = explode(",",$labels);
          foreach ($larr as $item) {
            $label_list[$n_labels] = $mid . ":" . $item;
            $n_labels++;
          }
        }

      }
    }
    if ($npmol > 0) {
      for ($i = 0; $i < $npmol; $i++) {
        $pmol[$i] = $allmol[($i+1+$nrmol)];
        $mnum = $i + 1;
        $labels = get_atomlabels($pmol[$i]);
        $mid = "p" . $mnum;
        if (strlen($labels) > 0) {
          $larr = explode(",",$labels);
          foreach ($larr as $item) {
            $label_list[$n_labels] = $mid . ":" . $item;
            $n_labels++;
          }
        }
      }
    }
    $label_list = clear_ambigouslabels($label_list);
    // now make the maps
    for ($il = 0; $il < $n_labels; $il++) {
      $item = $label_list[$il];
      $lblstr = get_labelstr($item);
      $a1 = get_anumstr($item);
      for ($jl = 0; $jl < $n_labels; $jl++) {
        if ($il != $jl) {
          $item2 = $label_list[$jl];
          $a2 = get_anumstr($item2);
          $lblstr2 = get_labelstr($item2);
          if ($lblstr == $lblstr2) {
            if ((substr($a1,0,1) == "r") && (substr($a2,0,1) == "p") &&
                ($lblstr !== "0") && ($lblstr2 !== "0")) {
              $mapstr = $a1 . "=" . $a2;
              $map_list[$n_maps] = $mapstr;
              $n_maps++;
            }
          }
        }
      }
    }
    foreach ($map_list as $item) {
      if (strlen($result) > 0) { $result .= ","; }
      $result .= $item;
    }
  }   // end if there are both reactants and products
  return($result);
}

function apply_labels($mymol,$mylabels) {
  $mol = array();
  if (strlen($mymol) > 40) {
    $mol = explode("\n",$mymol);
    $statline = $mol[3];
    $natoms = trim(substr($statline,0,3));
    $natoms = intval($natoms);
    if (strlen($mylabels) > 0) {
      $label_list = explode(",",$mylabels);
      foreach ($label_list as $label) {
        $larr = explode("-",$label);
        $a = $larr[0];
        $l = $larr[1];
        while (strlen($l) < 3) { $l = " " . $l; }
        $mol[($a + 3)] = substr_replace($mol[($a + 3)],$l,60,3);
      }
    }
  }
  $newmol = implode("\n",$mol);
  return($newmol);
}


function apply_maps($myrxn,$mymap) {
  $result = "";
  $rxndescr = analyze_rxnfile($myrxn);
  $nrmol = get_nrmol($rxndescr);
  $npmol = get_npmol($rxndescr);
  $allmol = array();
  $allmol = explode("\$MOL\r\n",$myrxn);           $ca = count($allmol);
  $header = $allmol[0];
  $rlabel_list = array();
  $plabel_list = array();
  $map_list = array();
  $n_rlabels = 0;
  $n_plabels = 0;
  $n_maps = 0;
  if (strlen($mymap) > 0) {
    $map_list = explode(",",$mymap);
    $n_maps = count($map_list);
  }
  if (($nrmol > 0) && ($npmol > 0)) {
    if ($nrmol > 0) {
      for ($i = 0; $i < $nrmol; $i++) {
        $rmol[$i] = $allmol[($i+1)];
        $mnum = $i + 1;
        $mid = "r" . $mnum;
      }
    }
    if ($npmol > 0) {
      for ($i = 0; $i < $npmol; $i++) {
        $pmol[$i] = $allmol[($i+1+$nrmol)];
        $mnum = $i + 1;
        $labels = get_atomlabels($pmol[$i]);
        $mid = "p" . $mnum;
      }
    }
    $l = 1;
    foreach ($map_list as $item) {
      $marr1 = explode("=",$item);
      $rpart = $marr1[0];
      $rarr1 = explode(":",$rpart);
      $rm = $rarr1[0];
      $rm = intval(str_replace("r","",$rm));
      $ra = $rarr1[1];
      $rl = $l;
      $ppart = $marr1[1];
      $parr1 = explode(":",$ppart);
      $pm = $parr1[0];
      $pm = intval(str_replace("p","",$pm));
      $pa = $parr1[1];
      $pl = $l;
      @$rlbl = $rlabel_list[($rm - 1)];
      if (strlen($rlbl) > 0) { $rlbl .= ","; }
      $rlbl .= $ra . "-" . $rl;
      $rlabel_list[($rm - 1)] = $rlbl;
      @$plbl = $plabel_list[($pm - 1)];
      if (strlen($plbl) > 0) { $plbl .= ","; }
      $plbl .= $pa . "-" . $pl;
      $plabel_list[($pm - 1)] = $plbl;
      $l++;
    }
    for ($i = 0; $i < $nrmol; $i++) {
      $lbl = $rlabel_list[$i];
    }
    for ($i = 0; $i < $npmol; $i++) {
      $lbl = $plabel_list[$i];
    }
    $newrxn = $header;
    for ($i = 0; $i < $nrmol; $i++) {
      $rmol[$i] = apply_labels($rmol[$i],$rlabel_list[$i]);
      $newrxn .= "\$MOL\r\n" . $rmol[$i];
    }
    for ($i = 0; $i < $npmol; $i++) {
      $pmol[$i] = apply_labels($pmol[$i],$plabel_list[$i]);
      $newrxn .= "\$MOL\r\n" . $pmol[$i];
    }
    $result = $newrxn;
  }   // end if there are both reactants and products
  return($result);
}


?>
