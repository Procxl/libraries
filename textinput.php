<?php 
// textinput.php    Norbert Haider, University of Vienna, 2014
// part of MolDB6   last change: 2014-07-10

/**
 * @file textinp.php
 * @author Norbert Haider
 * 
 * This script is called by a JavaScript window.open call from moldbgss.php,
 * it allows input of structures/reactions in MDL molfile/rxnfile format, 
 * e.g. by pasting from the clipboard. Attention: popup-blockers should be 
 * disabled for this site.
 */

$myname = $_SERVER['PHP_SELF'];
require_once("functions.php");

$sitename       = "Sristi Biosciences";
$cssfilename    = "moldb.css";

include("moldb6conf.php");
include("moldb6uiconf.php");
set_charset($charset);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=<?php echo "$html_charset"; ?>">
<meta http-equiv="imagetoolbar" content="no"> 
<meta name="author" content="Norbert Haider, University of Vienna">
<title><?php echo "$sitename"; ?>: text input</title>
<?php insert_style($cssfilename); ?>

<script language="javascript" type="text/javascript">

  function sendmol() {
    var moltext = document.textform.molarea.value;
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
      document.textform.molarea.value = moltext;
    }
    document.textform.molarea.focus();
  }

</script>

</head>
<body onload="loadmol()">

<b>Enter your query in MDL molfile/rxnfile format</b>
<form name="textform">
<textarea name="molarea" cols="80" rows="20"></textarea><br>
<input type="button" value="Submit to search form" onClick="sendmol();">&nbsp;
<input type="button" value="Cancel" onClick="window.close();">
</form>
</p>

</body>
</html>
