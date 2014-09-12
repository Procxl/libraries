<?php
# further settings in addition to those in moldb6conf.php

$sitename      = "Sristi Biosciences"; # appears in title and headline
$cssfilename   = "moldb.css";   # the default CSS (cascading style sheets) file
$use_cmmmsrv   = "n";           # set to "y" if a cmmmsrv daemon is available
$cmmmsrv_addr  = "127.0.0.1";   # must be numeric
$cmmmsrv_port  = 55624;         # the default cmmmsrv port is 55624

$multiselect   = "y";           # allow selection of multiple data collections
$default_db    = "1,2,3";       # default db_id (if more than one, use quotes and commas, e.g. "1,3")
$trustedIP     = "127.0.0.1,192.168.0.10";   # max. 10 numeric IP addresses
$enablereactions  = "y";        # if not "y": no reaction support

$enable_download = "y";         # download option for hit structures/reactions 
$download_limit  = 100;         # maximum number of hit structures/reactions to be downloaded (per search)

$enable_adminlink = "y";        # "y" or "n"; show/hide "Administration" menu item

$enable_prefs  = "y";           # "y" or "n"; enable choice of structure editor (JME, JSME, Ketcher, FlaME)
$default_editor = "jsme";       # may be "jme", "jsme", "ketcher" or "flame" (theoretically, also "text")


# the following variables control the 2D structure display

$enable_svg       = "y";        # first choice
$enable_bitmaps   = "n";        # second choice
$enable_jme       = "y";        # structure editor; fallback for 2D display
$enable_jsme      = "n";        # structure editor; fallback for 2D display (preferred)
$enable_ketcher   = "n";        # structure editor for input only
$enable_flame     = "n";        # structure editor for input only
$enable_textinput = "n";        # allow users to enter MDL molfiles/rxnfiles as text

# if an editor is enabled, specify its location below

#$java_codebase = "/classes";   # either leave empty or specify URL of the directory containing JME.jar
$java_codebase = "";            # either leave empty or specify URL of the directory containing JME.jar
$jsme_path     = "../jsme";     # absolute or relative path to the JSME directory
$ketcher_path  = "../ketcher";  # absolute or relative path to the GGA Ketcher directory
$flame_swf     = "../flame/flamer.swf";  # The FlaME swf file with absolute or relative path

?>
