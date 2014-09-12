<?php 
// ew4jme.php       Norbert Haider, University of Vienna, 2014
// part of MolDB6   last change: 2014-08-18

$myname = $_SERVER['PHP_SELF'];
require_once("functions.php");

// some defaults (will be overridden by config files
$sitename      = "Sristi Biosciences";
$cssfilename   = "moldb.css";
$default_edmode  = "flex";  // possible values: "mol", "rxn", "flex" (for JME/JSME)

include("moldb6conf.php");
include("moldb6uiconf.php");
set_charset($charset);

$edmodes = array("mol","rxn","flex");
@$edmode = $_REQUEST['mode'];
if ((!isset($edmode)) || (!in_array($edmode,$edmodes))) {
  $edmode = $default_edmode;
}

If ($edmode == "rxn") {
  $jme_opt = "reaction,query,hydrogens,xbutton,oldlook";
} else {
  $jme_opt = "query,hydrogens,xbutton,oldlook";
}

$codebase = "";
if (isset($java_codebase) && ($java_codebase != "")) { 
  $codebase = "codebase=\"" . $java_codebase . "\"" ; 
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=<?php echo "$html_charset"; ?>">
<meta http-equiv="imagetoolbar" content="no"> 
<meta name="author" content="Norbert Haider, University of Vienna">
<title><?php echo "$sitename"; ?>: JME input</title>
<?php insert_style($cssfilename); ?>

<script language="javascript" type="text/javascript">

  function sendmol() {
    var moltext = document.JME.molFile();
    if (moltext.length < 120) {
      alert("Nothing to submit!");
    }
    else {
      opener.putmoltext(moltext);
      window.close();
    }
  }
  
  function loadmol() {
    var moltext = opener.getmoltext();
    if (moltext.length >= 120) {
      document.JME.readMolFile(moltext);
    }
  }

</script>

</head>
<body onload="loadmol()">

<span style="font-weight:bold">Enter your query structure</span>&nbsp;<br>
<span style="font-size:0.75em">(special atom symbols: <b>A</b> = any atom except H, <b>Q</b> = any atom except C and H,
<b>X</b> = any halogen, <b>H</b> = explicit hydrogen)</span><br>
<table border="0">
  <tr>
    <td colspan="2">
    <applet name="JME" code="JME.class" <?php echo "$codebase"; ?> archive="JME.jar" width="720" height="460">
    <param name="options" value="<?php echo "$jme_opt"; ?>">
    You have to enable Java in your browser.
    </applet>
    </td>
  </tr>
  <tr>
    <td>
    <input type="button" value="Submit to search form" onClick="sendmol();">
    <input type="button" value="Cancel" onClick="window.close();">
    </td>
    <td align="right">
    <?php 
    if ($edmode == "flex") {    /* allows switching between MOL and RXN mode */ 
    ?><small>Mode:&nbsp;<button type="button" onclick="document.JME.options('noreaction');">Structure</button>
    <button type="button" onclick="document.JME.options('reaction');">Reaction</button></small>
    <?php
    }
    ?></td>
  </tr>
</table>

</body>
</html>
