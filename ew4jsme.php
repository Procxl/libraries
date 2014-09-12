<?php 
// ew4jsme.php      Norbert Haider, University of Vienna, 2014
// part of MolDB6   last change: 2014-08-18

$myname = $_SERVER['PHP_SELF'];
require_once("functions.php");

// some defaults (will be overridden by config files
$sitename      = "Sristi Biosciences";
$cssfilename   = "moldb.css";
$jsme_path = "../jsme";

include("moldb6conf.php");
include("moldb6uiconf.php");
set_charset($charset);

$default_edmode  = "flex";  // possible values: "mol", "rxn", "flex" (for JME/JSME)
$edmodes = array("mol","rxn","flex");
@$edmode = $_REQUEST['mode'];
if ((!isset($edmode)) || (!in_array($edmode,$edmodes))) {
  $edmode = $default_edmode;
}

If ($edmode == "rxn") {
  $jsme_opt = "reaction,query,hydrogens,xbutton,oldlook";
} else {
  $jsme_opt = "query,hydrogens,xbutton,oldlook";
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=<?php echo "$html_charset"; ?>">
<meta http-equiv="imagetoolbar" content="no"> 
<meta name="author" content="Norbert Haider, University of Vienna">
<?php  insert_style($cssfilename);  ?>
<title><?php echo "$sitename"; ?>: JSME input</title>
  
<script type="text/javascript" language="javascript" src="<?php echo "$jsme_path"; ?>/jsme.nocache.js"></script>

<script language="javascript" type="text/javascript">
  
  //this function will be called after the JavaScriptApplet code has been loaded.
  function jsmeOnLoad() {
    jsmeApplet = new JSApplet.JSME("appletContainer", "720px", "460px", {
      //optional parameters
      "options" : "<?php echo "$jsme_opt"; ?>"
    });
	
    //Opera patch: if some applet elements are not displayed, force repaint
    //jsmeApplet.deferredRepaint(); //the applet will be repainted after the browser event loop returns
    //it is recommended to use it if the JSME is created outside this jsmeOnLoad() function
    
    //jsmeApplet has the same API as the original Java applet
    //One can mimic the JME Java applet access to simplify the adaptation of HTML and JavaScript code:
    document.JME = jsmeApplet;

    loadmol();
    setedmode();
  }

  function loadmol() {
    var moltext = opener.getmoltext();
    if (moltext.length >= 120) {
      document.JME.readMolFile(moltext);
    }
  }
  
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

  function setedmode() {
    var edmode = opener.getedmode();
    if (edmode.length < 3) {
      edmode = "flex";
    }
  }

</script>

</head>
<body>
    
<noscript>
Your web browser must have JavaScript enabled for this application to display correctly.<p>
</noscript>

<span style="font-weight:bold">Enter your query structure</span>&nbsp;<br>
<span style="font-size:0.75em">(special atom symbols: <b>A</b> = any atom except H, <b>Q</b> = any atom except C and H,
<b>X</b> = any halogen, <b>H</b> = explicit hydrogen)</span><br>
<table border="0">
  <tr>
    <td id="appletContainer" colspan="2"></td>
  </tr>
  <tr>
    <td>
    <input type="button" name="sbutton" value="Submit to search form" onClick="sendmol();">
    <input type="button" name="cbutton" value="Cancel" onClick="window.close();">
    </td>
    <td align="right">
    <?php 
    if ($edmode == "flex") {    /* allows switching between MOL and RXN mode */ 
    ?><small>Mode:&nbsp;<button type="button" onclick="jsmeApplet.options('noreaction');">Structure</button>
    <button type="button" onclick="jsmeApplet.options('reaction');">Reaction</button></small>
    <?php
    }
    ?></td>
  </tr>
</table>

</body>
</html>
