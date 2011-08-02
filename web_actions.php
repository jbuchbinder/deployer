<?php
//	$Id: web_actions.php 1340 2009-02-17 20:09:36Z vuksan $
?>
<html>
<head>
<title>Deployment helper</title>
<link rel="stylesheet" type="text/css" href="css/deployer.css" />
</head>
<body>
<div>
<pre>
<?php

# Limit the execution time of this script to 60 minutes
set_time_limit(3600);

ob_flush();
flush();

$GLOBALS['base'] = dirname(__FILE__);

require_once($GLOBALS['base'] . '/config.php');
require_once($GLOBALS['base'] . '/lib/tools.php');

$action = $_GET['action'];
$stamp = $_GET['stamp'];

if ( isset( $_SERVER['REMOTE_USER'] ) )
        $remote_user=$_SERVER['REMOTE_USER'];
else if ( isset($_SERVER["SSL_CLIENT_S_DN_Email"]) )
	$remote_user=$_SERVER["SSL_CLIENT_S_DN_Email"];
else {
	$hostname = gethostbyaddr($_SERVER['REMOTE_ADDR']);
	$remote_user="Can't determine username :-(, IP=" . $hostname;
}

$addl_info = "";

# If action is deploy log the version being deployed
if ( $action == "deploy" ) {
	$addl_info = " (ver. " . $_GET['version'] . ")";
}

insert_into_log ( $dsn, $remote_user ,$_GET['server'],$_GET['domain'],$_GET['product'],  "Requested " . $_GET['action'] . $addl_info);

if ( ! action_lock( $dsn, $_GET['server']."-".$_GET['domain']."-".$_GET['product']."-".$_GET['action'], $stamp ) ) {
	die("Action already completed. Please click on the Close Button first if you want to replay this action.");
}

global $cmd_deployer, $cmd_action;
$base_cmd = $cmd_deployer . " --domain=" . escapeshellarg($_GET['domain']) . " --server=" . escapeshellarg($_GET['server']). " --product=".escapeshellarg($_GET['product']). " --action=deploy ";
$base_action_cmd = $cmd_deployer . " --domain=" . escapeshellarg($_GET['domain']) . " --server=" . escapeshellarg($_GET['server']) . " --product=".escapeshellarg($_GET['product']);
print "DEBUG: $action\n";

switch ($action) {

case "start":
case "stop":
case "restart":
case "undeploy":
case "version":
	print $base_action_cmd . " --action=".$action."\n";
	passthru($base_action_cmd . " --action=".$action, $return_var);
	break;

case "deploy":
	print $base_cmd . " --version=" . escapeshellarg($_GET['version'])."\n";
	passthru($base_cmd . " --version=" . escapeshellarg($_GET['version']), $return_var);
	break;

}

for ( $i = 0 ; $i < sizeof($return_var) ; $i++ ) {
	print ($return_var[$i]);
}

?>
</pre>
</div>
</body>
</html>

