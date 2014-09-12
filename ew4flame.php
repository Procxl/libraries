<?php 
// ew4flame.php     Norbert Haider, University of Vienna, 2014
// part of MolDB6   last change: 2014-08-18

$myname = $_SERVER['PHP_SELF'];
require_once("functions.php");

// some defaults (will be overridden by config files
$sitename      = "Sristi Biosciences";
$cssfilename   = "moldb.css";

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
<title><?php echo "$sitename"; ?>: FlaME input</title>
<?php insert_style($cssfilename); ?>

<script language="javascript" type="text/javascript">

function sendmol() {
  var moltext = getFRxn();
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
    setFMol(moltext);
  }
  document.buttonform.sbutton.focus();
}

</script>

</head>
<body onload="loadmol()">

<span style="font-weight:bold">Enter your query structure</span>&nbsp;<br>
<span style="font-size:0.75em">(special atom symbols: <b>A</b> = any atom except H, <b>Q</b> = any atom except C and H,
<b>X</b> = any halogen, <b>H</b> = explicit hydrogen)</span><br>
<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" 
	codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0" 
	width="720" height="454" id="flame" align="middle">
<param name="allowScriptAccess" value="sameDomain" />
<param name="movie" value="<?php echo "$flame_swf"; ?>" />
<param name="quality" value="high" />
<param name="bgcolor" value="#ffffff" />
<embed src="<?php echo "$flame_swf"; ?>" 
	quality="high" 
	bgcolor="#ffffff" 
	width="720" 
	height="454" 
	name="flame" 
	align="middle" 
	allowScriptAccess="sameDomain" 
	type="application/x-shockwave-flash" 
	pluginspage="http://www.macromedia.com/go/getflashplayer" />
</object>

<form name="buttonform">
<input type="button" name="sbutton" value="Submit to search form" onClick="sendmol();">&nbsp;
<input type="button" name ="cbutton" value="Cancel" onClick="window.close();">
</form>
</p>

<script language="javascript" type="text/javascript">

function thisMovie(movie) {
  if (window.document[movie]) {
    return window.document[movie];
  }
  if (navigator.appName.indexOf("Microsoft Internet")==-1) {
    if (document.embeds && document.embeds[movie]) {
      return document.embeds[movie]; 
    }
  } else {
    return document.getElementById(movie);
  }
}

function getFMol(){
	var m =	thisMovie("flame").GetVariable("mol");
	m = m.replace(/\r/g, "\r\n");
	return m;
}

function setFMol(m) {
	m = m.replace(/\r\n/g, "\r");
	thisMovie("flame").send2Flame(m);
}

function getFRxn_old(){    // not yet supported in FlaME 2011.04
	var r =	thisMovie("flame").GetVariable("rxn");
	r = r.replace(/\r/g, "\r\n");
	return r;
}

function getFRxn(){    // supported in FlaME 2011.04
    var r =    thisMovie("flame").getSourceFromFlame();
    r = r.replace(/\r/g, "\r\n");
    return r;
}


</script>

</body>
</html>
