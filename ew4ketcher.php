<?php 
// ew4ketcher.php   Norbert Haider, University of Vienna, 2014
// part of MolDB6   last change: 2014-08-18

$myname = $_SERVER['PHP_SELF'];
require_once("functions.php");

// some defaults (will be overridden by config files
$sitename      = "Sristi Biosciences";
$cssfilename   = "moldb.css";
$ketcher_path = "../ketcher";

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
<title><?php echo "$sitename"; ?>: GGA Ketcher input</title>

<script type="text/javascript" src="<?php echo "$ketcher_path"; ?>/prototype-min.js"></script>
<script type="text/javascript" src="<?php echo "$ketcher_path"; ?>/ketcher.js"></script>
<script type="text/javascript">
  function getKetcher() {
    var ketcherFrame = document.getElementById('ketcherFrame');
    var ketcher = null;
    if ('contentDocument' in ketcherFrame)
      ketcher = ketcherFrame.contentWindow.ketcher;
    else // IE7
      ketcher = document.frames['ketcherFrame'].window.ketcher;
  return ketcher;
  }

  function sendmol() {
    var ketcher = getKetcher();
    var moltext = ketcher.getMolfile();
    if (moltext.length < 120) {
      alert("Nothing to submit!");
    }
    else {
      opener.putmoltext(moltext);
      window.close();
    }
  }
  
  function loadMol() {
    var ketcher = getKetcher();
    var moltext = opener.getmoltext();
    if (moltext.length >= 120) {
      ketcher.setMolecule(moltext);
    }
  }

</script>

<?php insert_style($cssfilename); ?>

</head>
<body>

<span style="font-weight:bold">Enter your query structure</span>&nbsp;<br>
<span style="font-size:0.75em">(special atom symbols: <b>A</b> = any atom except H, <b>Q</b> = any atom except C and H,
<b>X</b> = any halogen, <b>H</b> = explicit hydrogen)</span><br>
<form name="textform">
<iframe onload="loadMol()" id="ketcherFrame" src="<?php echo "$ketcher_path"; ?>/ketcher.html" style="overflow: hidden; min-width: 800px; min-height: 503px; border: 1px solid darkgray;"></iframe><br>
<input type="button" value="Submit to search form" onClick="sendmol();">&nbsp;
<input type="button" value="Cancel" onClick="window.close();"></p>

</body>
</html>
