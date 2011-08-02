<?php
//
//	$Id: config.php 1005 2008-09-18 15:24:45Z jbuchbinder $
//

//----- Database configuration
$db_hostname = "localhost";
$db_username = "webread";
$db_password = "webread";
$db_database = "deployer";

//----- Paths 
$dist_dir = "/dist";
$temp_dir = "/tmp/deployer_tmp";

//----- Do NOT change below this unless you know what you're doing! -----

$GLOBALS['base'] = dirname(__FILE__);
$dsn = "mysql://${db_username}:${db_password}@${db_hostname}/${db_database}";
$cmd_deployer = $GLOBALS['base'] . "/deployer.php ";
$cmd_action = $GLOBALS['base'] . "/scripts/action.sh ";
$template_dir = $GLOBALS['base'] . "/templates";

$colors = "Thistle,LightBlue,Khaki,Violet,Linen,Lavender,Beige,LightGoldenrodYellow,LemonChiffon,MistyRose,PapayaWhip,AntiqueWhite,BlanchedAlmond,Bisque,Moccasin,Gainsboro,PeachPuff,PaleTurquoise,Pink,NavajoWhite,Wheat,PaleGoldenrod,LightGrey,PowderBlue,Plum,LightSteelBlue,Aquamarine,LightSkyBlue,Silver,SkyBlue,PaleGreen,Orchid,BurlyWood,HotPink,LightPink,LightSalmon,Tan,LightGreen,Aqua,Cyan,Fuchsia,Magenta,Yellow,DarkGray,DarkSalmon,SandyBrown,LightCoral,Turquoise,Salmon,CornflowerBlue,MediumTurquoise,MediumOrchid,DarkKhaki,MediumPurple,PaleVioletRed,MediumAquamarine,GreenYellow,DarkSeaGreen,RosyBrown,Gold,MediumSlateBlue,Coral,DeepSkyBlue,DodgerBlue,Tomato,DeepPink,Orange,Goldenrod,DarkTurquoise,CadetBlue,YellowGreen,LightSlateGray,DarkOrchid,BlueViolet,MediumSpringGreen,SlateBlue,Peru,RoyalBlue,DarkOrange,IndianRed";

$background_colors = explode(",", $colors);

?>
