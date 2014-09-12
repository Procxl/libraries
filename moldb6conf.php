<?php  // for use as moldb6conf.php, remove the leading "#" in this line and in the last line
# configuration data for MolDB6 database=======================================

# This file contains settings that are relevant both to the Perl command-line scripts
# and to the PHP scripts of the web frontend. One copy of this file (named "moldb6.conf",
# with removed or commented-out PHP tags) should reside in the working directory
# and another copy (named "moldb6conf.php, with valid PHP tags) should
# reside in the PHP include path (or in the MolDB6 web directory, if you don't mind the
# potential security risk).

$database       = "moldb6";        # name of the MySQL database
$hostname       = "localhost";     # hostname of MySQL server, probably "localhost"
$clientname     = "localhost";     # name of MySQL client, usually "localhost"
$mysql_admin    = "root";          # MySQL administrator, usually "root"
$rw_user        = "mdb6-admin";    # proxy user with CREATE and INSERT privileges
$rw_password    = "top-secret";    # (make sure this script is unreadable to others)
$ro_user        = "mdb6-user";     # proxy user with SELECT privilege
$ro_password    = "secret";        # (better avoid dollar signs etc.)
$drop_db        = "y";             # erase entire database before re-creating it?

$prefix         = "";              # this allows to have different MolDB6 instances
                                  # in one MySQL database; each MolDB6 instance can
                                  # contain multiple data collections 

$charset        = "latin1";        # may be "latin1", "latin2" or "utf8"
$CHECKMOL       = "/usr/local/bin/checkmol";
$MATCHMOL       = "/usr/local/bin/matchmol";

# the following options are required for generating graphics in SVG format

$MOL2SVG        = "/usr/local/bin/mol2svg";
$mol2svgopt     = "--rotate=auto3Donly --hydrogenonmethyl=off --color=/usr/local/etc/color.conf"; # options for mol2svg, e.g. "--showmolname=on"
#$mol2svgopt    = "--rotate=auto3Donly --hydrogenonmethyl=off"; # options for mol2svg, e.g. "--showmolname=on"
$mol2svgopt_rxn = "-R --rotate=auto3Donly --hydrogenonmethyl=off"; # options for mol2svg in reaction mode
$svg_scalingfactor = 1.0;         # 1.0  gives good results
$svg_scalingfactor_rxn = 0.80;    # 0.75 is a good compromise for reactions

# the following options are relevant only if you want to use bitmap
# graphics for 2D depiction of your molecular structures, otherwise
# set $bitmapdir to an empty string ($bitmapdir = "")

$MOL2PS         = "/usr/local/bin/mol2ps";
$GHOSTSCRIPT    = "/usr/bin/gs";
#$bitmapdir     = "";
$bitmapdir      = "";  # this is the base directory (Linux example)
#$bitmapdir     = "/Applications/MAMP/htdocs/moldb5r/bitmaps"; # this is the base directory (Mac OS X example)
#$bitmapdir     = "/xampp/htdocs/moldb5r/bitmaps";  # this is the base directory (Windows example)
$bitmapURLdir   = "";     # typically, a substring of $bitmapdir
#$bitmapURLdir  = "";
$mol2psopt      = "--rotate=auto3Donly --hydrogenonmethyl=off --color=/usr/local/etc/color.conf"; # options for mol2ps, e.g. "--showmolname=on"
#$mol2psopt     = "--rotate=auto3Donly --hydrogenonmethyl=off"; # options for mol2ps, e.g. "--showmolname=on"
$mol2psopt_rxn  = "-R --rotate=auto3Donly --hydrogenonmethyl=off"; # options for mol2ps in reaction mode
$scalingfactor  = 0.22;          # 0.22 gives good results
$scalingfactor_rxn = 0.18;      # 0.18 is a good compromise for reactions

# the following options are relevant only if you want to use the auto_mol_inchikey feature
$enable_inchi   = "n";   # "y" or "n"
$INCHI          = "/usr/local/bin/inchi-1";  # available from the IUPAC website
$INCHI_OPT      = "-STDIO -Key";


# further settings=============================================================

$fpdict_mode    = 1;         # 1 = auto adjust, 2 = force 64 bit, 3 = force 32 bit
$scratchdir     = "/data/moldb/moldb-scratch";  # needed by cp2mem.pl, 
                                               # must be writeable by mysql UID, too


# definitions for MolDB6 (do not edit if not really necessary)================

# fixed table names
$metatable      = "${prefix}moldb_meta";
$fpdeftable     = "${prefix}moldb_fpdef";
$memsuffix      = "_mem";  # will be appended to table name if applicable

# other table names, will be prepended by appropriate prefix;
$molstrucsuffix  = "molstruc";
$moldatasuffix   = "moldata";
$molstatsuffix   = "molstat";
$molfgbsuffix    = "molfgb";
$molcfpsuffix    = "molcfp";
$pic2dsuffix     = "pic2d";

# tables for reaction data ===============================================
$rxnstrucsuffix  = "rxnstruc";
$rxndatasuffix   = "rxndata";
$rxncfpsuffix    = "rxncfp";
$rxnfgbsuffix    = "rxnfgb";

?>
