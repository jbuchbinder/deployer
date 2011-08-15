<?php
//	$Id: repository_sync.php 1152 2008-11-24 20:03:26Z jbuchbinder $
?>
<?php if ($_REQUEST['inner'] != 1 ) { ?>
<html>
<head>
	<title>Deployer: Sync Repository</title></head>
	<meta http-equiv="Content-Language" content="en-us">
	<meta HTTP-EQUIV="expires" CONTENT="Wed, 20 Feb 1990 08:30:00 GMT">
	<meta HTTP-EQUIV="Pragma" CONTENT="no-cache">
	<link rel="stylesheet" type="text/css" href="css/deployer.css" />
</head>

<body>

<?php if ($_REQUEST['embed'] != 1) { ?>
<h1>Syncing Repository</h1>

<ul class="buttons">
	<li><a href="dashboard.php">Dashboard</a></li>
	<li><a href="firewall.php">Firewall</a></li>
	<li><a href="edit_config.php">Edit Domain Config</a></li>
</ul>
<?php } ?>

<?php if ($_GET['go']+0 == 1) { ?>
<div id="status">Please wait while the repository syncs....</div>

<br/>

<iframe height="80%" frameborder="0" width="90%" src="repository_sync.php?inner=1&embed=<?php print $_GET['embed']; ?>" onLoad="document.getElementById('status').innerHTML='Completed. <br/><a href=&quot;repository_sync.php?inner=0&amp;embed=<?php print $_REQUEST['embed']; ?>&amp;go=1&quot;>Sync repository</a>'; <?php if ($_REQUEST['embed'] == 1) { ?>parent.document.getElementById('dashboard_frame').src='dashboard.php?embed=1&ts=<?php print mktime(); ?>';<?php } ?> return true;"></iframe>
<?php } else { ?>
<div align="center"><button onClick="window.location='repository_sync.php?inner=0&embed=<?php print $_REQUEST['embed']; ?>&go=1'; return true;">Sync repository</button></div>
<?php } ?>
</body>
</html>

<?php } else { ?>

<pre>
<?php

$GLOBALS['base'] = dirname(__FILE__);

require_once($GLOBALS['base'] . '/config.php');
require_once($GLOBALS['base'] . '/lib/tools.php');

if ( isset( $_SERVER['REMOTE_USER'] ) )
        $remote_user=$_SERVER['REMOTE_USER'];
else if ( isset($_SERVER["SSL_CLIENT_S_DN_Email"]) )
	$remote_user=$_SERVER["SSL_CLIENT_S_DN_Email"];
else {
	$hostname = gethostbyaddr($_SERVER['REMOTE_ADDR']);
	$remote_user="Can't determine username :-(, IP=" . $hostname;
}

# Log the action
insert_into_log ( $dsn, $remote_user , "ALL", "ALL", "ALL", "Initiated repository sync from repository" );

// Force direct sync
print `rsync -rvazp --delete -e ssh dev@dev.example.com:/dist/ /dist/ 2>&1`;

?>
</pre>
<div align="center"><button onClick="window.location='repository_sync.php?inner=0&embed=<?php print $_REQUEST['embed']; ?>&go=1'; return true;">Sync repository</button></div>

<br/><br/>

<?php } ?>
