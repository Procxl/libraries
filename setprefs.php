<?php 
// setprefs.php     Norbert Haider, University of Vienna, 2014
// part of MolDB6   last change: 2014-08-18

/**
 * @file setprefs.php
 * @author Norbert Haider
 * 
 * This script is run as a pop-up via a JavaScript function from the menu bar 
 * in all search pages in order to give users a choice of structure editors (JME, 
 * JSME, GGA Ketcher, FlaME or simple text input in MDL MOL/RXN format).
 * The settings are stored in a cookie named "MolDB6" which is used by
 * moldbgss.php and (in part) admin/editdata.php (further settings may
 * be added in the future).
 */

$myname = $_SERVER['PHP_SELF'];
require_once("functions.php");

// default settings (will be overridden by config files):
$enable_textinput = "y";    // "y" or "n", default: "y"
$sitename       = "Sristi Biosciences";
$cssfilename    = "moldb.css";

include("moldb6conf.php");   // Contains $uid and $pw of a proxy user 
                             // with read-only access to the moldb database;
                             // contains $bitmapURLdir (location of .png files);
                             // the conf file must have valid PHP start and end tags!
include("moldb6uiconf.php"); // Contains additional settings that are relevant only
                             // to the web frontend (the PHP scripts), but not to
                             // the command-line backend (the Perl scripts)

if (config_quickcheck() > 0) { die(); }
set_charset($charset);

$act    = "";
@$act   = $_POST["act"];
@$editor   = $_POST["editor"];

$settings  = "editor=" . $editor;

$cookie_placed = false;
if ($act == "set") {
  $cookie_placed = setcookie("MolDB6",$settings);
} else {
  @$curr_settings = $_COOKIE["MolDB6"];
  $curr_a = explode(",",$curr_settings);
  $n = 0;
  foreach ($curr_a as $curr_line) {
    $n++;
    if (strpos($curr_line,"editor") !== FALSE) {
      $tmp_line = $curr_line;
      $ed_a = explode("=",$curr_line);
      $ed = $ed_a[1];
      $editor = $default_editor;
      if ($ed == "jme")      { $editor = "jme"; }
      if ($ed == "jsme")     { $editor = "jsme"; }
      if ($ed == "ketcher")  { $editor = "ketcher"; }
      if ($ed == "flame")    { $editor = "flame"; }
      if ($ed == "text")     { $editor = "text"; }
    }
  }
  if (!isset($editor) || ($editor == "")) {
    $editor = $default_editor;
  }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=<?php echo "$html_charset"; ?>">
<meta http-equiv="imagetoolbar" content="no">
<meta name="author" content="Norbert Haider, University of Vienna">
<title><?php echo "$sitename"; ?>: Preferences</title>
<?php 
insert_style($cssfilename); 
?>
</head>
<body<?php if ($act == "set") { echo " onload=\"window.close()\"";} ?>>
<h3>Select your preferred structure editor:</h3>
<form name="form" action="<?php echo "$myname"; ?>" method="post">
<input type="hidden" name="act" value="set">
<?php
$n_opt = 0;
if ($enable_jme == "y") {
  echo "<input type=\"radio\" name=\"editor\" value=\"jme\"";
  if ($editor == "jme") { echo " checked"; };
  echo ">JME (Java Molecular Editor)<br>\n";
  $n_opt++;
}
if (($enable_jsme == "y") && isset($jsme_path) && ($jsme_path != "")) {
  echo "<input type=\"radio\" name=\"editor\" value=\"jsme\"";
  if ($editor == "jsme") { echo " checked"; };
  echo ">JSME (JavaScript Molecular Editor)<br>\n";
  $n_opt++;
}

if (($enable_ketcher == "y") && isset($ketcher_path) && ($ketcher_path != "")) {
  echo "<input type=\"radio\" name=\"editor\" value=\"ketcher\"";
  if ($editor == "ketcher") { echo " checked"; };
  echo ">GGA Ketcher<br>\n";
  $n_opt++;
}

if (($enable_flame == "y") && isset($flame_swf) && ($flame_swf != "")) {
  echo "<input type=\"radio\" name=\"editor\" value=\"flame\"";
  if ($editor == "flame") { echo " checked"; };
  echo ">FlaME (Flash Molecular Editor)<br>\n";
  $n_opt++;
}

if (isset($enable_textinput) && ($enable_textinput == "y")) {
  echo "<input type=\"radio\" name=\"editor\" value=\"text\"";
  if ($editor == "text") { echo " checked"; };
  echo ">Text input (MOL/RXN format)<br>\n";
  $n_opt++;
}

if ($n_opt == 0) {
  echo "Sorry, there are no structure editors defined. Please ask your database administrator to
  install and/or enable at least one structure editor (e.g., JSME)<br>\n</body>\n</html>\n";
  die();
}

?>

<br>
<input type="button" value="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Save&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" onClick="set_prefs()">&nbsp;&nbsp;

</form>
<?php
/*
  echo "current settings: $curr_settings<br>\n";
  echo "act: $act<br>";
  echo "editor: $editor<br>";
  echo "cookie placed: ";
  if ($cookie_placed == TRUE) { echo "OK<br>\n"; } else { echo "failed<br>\n"; }
*/
?>
<script language="javascript" type="text/javascript">

  function set_prefs() {
    var editors = document.getElementsByName("editor");
    for (var i = 0; i < editors.length; i++) {
      if (editors[i].checked == true) {
        selectededitor = editors[i].value;
      }
    }
    opener.set_editor(selectededitor);
    document.form.submit();
  }
</script>

</body>
</html>
