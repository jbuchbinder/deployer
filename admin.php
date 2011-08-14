<?php
//      $Id: admin.php 1818 2009-11-24 15:51:01Z jbuchbinder $

$GLOBALS['base'] = dirname(__FILE__);

require_once($GLOBALS['base'] . '/config.php');
require_once($GLOBALS['base'] . '/lib/tools.php');

$__V = split(" ", "\$Revision: 1818 $");
$VERSION = trim( str_replace( '$', '', $__V[1] ) );

?>
<html>

<head>
<meta http-equiv="Content-Language" content="en-us">
<meta HTTP-EQUIV="expires" CONTENT="Wed, 20 Feb 1990 08:30:00 GMT">
<meta HTTP-EQUIV="Pragma" CONTENT="no-cache">
<link rel="stylesheet" type="text/css" href="css/deployer.css" />
<title><?php print coloid() . "-". web_hostname(); ?> Administration</title>
<body>

<?php if (!$_GET['embed']) { ?>
<h1>Administration [version <?php print $VERSION; ?>]</h1>

<ul class="buttons">
	<li><a href="dashboard.php">Dashboard</a></li>
	<li><a href="repository_sync.php">Update software from repository</a></li>
	<li><a href="edit_config.php">Edit Domain Config</a></li>
</ul>

<?php } ?>

<?php

if ( !isset ($_GET['action'] ) ) {

	print "<h2>Create Server</h2>
	<form method=\"GET\">
	<input type=\"hidden\" name=\"embed\" value=\"".htmlentities($_GET['embed'])."\" />
	<input type=\"hidden\" name=\"action\" value=\"create_server\" />
	<table>
	<tr><th>Enter FQDN :</th>
	<td><input type=\"text\" name=\"server\" /></td></tr>
	<tr><th>Description :</th>
	<td><input type=\"text\" name=\"description\" /></td></tr>
	</table>
	<p><input type=\"Submit\" VALUE=\"Create\"></form>";

	print "<h2>Deploy Server</h2>
	<form method=\"GET\">
	<input type=\"hidden\" name=\"embed\" value=\"".htmlentities($_GET['embed'])."\" />
	<input type=\"hidden\" name=\"action\" value=\"deploy_product\" />
	Select domain to deploy to ";

	display_domain_dropdown();

	print " Product ";

	display_product_dropdown();

	print " Server ";

	display_server_dropdown();

	print "<p>Deploy IP (if applicable, leave blank if you don't know what it is) <input name=deploy_ip>";

	print "<p><input type=\"Submit\" VALUE=\"Deploy\"></form>";

	print "

	<p><hr>
	<h2>Clone Domain:</h2>
	
	<form method=\"GET\">
	<input type=\"hidden\" name=\"embed\" value=\"".htmlentities($_GET['embed'])."\" />
	<input type=\"hidden\" name=\"action\" value=\"clone_domain\">
	Select domain to clone ";
		
	display_domain_dropdown("domain_name_to_clone");

	print "
	<p>
	<table border=\"1\">
	<tr><th>Name of new domain:</th><td><input type=\"text\" name=\"domain_name\" /></td></tr>
	<tr><th>Domain identifier e.g. ba, bb</th><td><input type=\"text\" name=\"domain_identifier\" /></td></tr>
	<tr><th width=\"60%\">Optional replace string in values e.g. we use usernames as suffixes e.g. Db_joe. If you are creating a domain for Jack put from joe to jack. 
	</th><td>From <input type=\"text\" name=\"fromstring\" /><br />To <input name=\"tostring\" /></td></tr>
	</table>
	<p><input type=\"submit\" /></form>
	";

} else {

	if ( $_GET['action'] == "edit_template" ) {
		$db =& MDB2::factory($GLOBALS['dsn']);
		if (DB::isError($db)) {
			print "Encountered db error!\n";
			die ($db->getMessage());
		}

		$sql = "SELECT templatetext FROM config_templates WHERE id = '" . $_GET['template'] . "'";
		$templatetext = $db->queryOne($sql);

		print "
		<form method=\"GET\" style=\"margin: 0; display: inline;\">
		<input type=\"hidden\" name=\"embed\" value=\"".htmlentities($_GET['embed'])."\" />
		<input type=\"hidden\" name=\"action\" value=\"edit_template_commit\" />
		<input type=\"hidden\" name=\"id\" value=\"".htmlentities($_GET['template'])."\" />
		<textarea name=\"templatetext\" cols=\"120\" rows=\"34\" wrap=\"virtual\"
		 >".htmlentities(stripslashes($templatetext))."</textarea>
		<br/>
		<input type=\"submit\" value=\"Commit Changes\">
		</form>
		<form method=\"GET\" style=\"margin: 0; display: inline;\">
		<input type=\"hidden\" name=\"embed\" value=\"".htmlentities($_GET['embed'])."\" />
		<input type=\"submit\" value=\"Cancel\" />
		</form>
		";
	}

	if ( $_GET['action'] == "create_server" ) {

		$db =& MDB2::factory($GLOBALS['dsn']);

		if (DB::isError($db)) {
			print "Encountered db error!\n";
			die ($db->getMessage());
		}

		$sql = "INSERT INTO systems ( name, description ) VALUES ( '".addslashes($_GET['server'])."', '".addslashes($_GET['description'])."' );";
		$db->query( $sql );

		print "Created server <b>".$_GET['server']."</b><br/>\n";

		print "<br/>\n";
		print "<a href=\"admin.php?embed=".$_GET['embed']."\">Return to Administration</a>";

	}

	if ( $_GET['action'] == "edit_template_commit" ) {
		$db =& MDB2::factory($GLOBALS['dsn']);
		if (DB::isError($db)) {
			print "Encountered db error!\n";
			die ($db->getMessage());
		}

		// Update
		$sql = "UPDATE config_templates SET templatetext='".addslashes( $_GET['templatetext'] )."' WHERE id='".$_GET['id']."';";
		$didSomething = $db->queryOne($sql);
		
		print "
		<h2>Completed commit</h2>
		<form method=\"GET\">
		<input type=\"hidden\" name=\"embed\" value=\"".htmlentities($_GET['embed'])."\" />
		<input type=\"submit\" value=\"Return to Administration\" />
		</form>
		";
	}

	if ( $_GET['action'] == "clone_domain" ) {
	
		print "We are cloning domain " . $_GET['domain_name_to_clone'];

		print "You entered:<p>";
		print "Domain Name: " . $_GET['domain_name'] . "<br />";
		print "Domain Identifier: " . $_GET['domain_identifier'] . "<br />";

		$db =& MDB2::factory($GLOBALS['dsn']);

		if (DB::isError($db)) {
			print "Encountered db error!\n";
			die ($db->getMessage());
		}

		###########################################################################
		# Let's find what the origination domain_id is
		###########################################################################
		$origination_domain_id = get_domain_id( $dsn, $_GET['domain_name_to_clone']);

		###########################################################################
		# Let's make sure domain doesn't already exist
		###########################################################################
		$sql = "SELECT id FROM domains WHERE name = '" . $_GET['domain_name'] . "'";

		$domain_id = $db->queryOne($sql);

		if ( $domain_id == "" ) {

			echo "Creating domain ...";
			###########################################################################
			# Let's find what the highest start port is
			###########################################################################
			$sql = "SELECT MAX(startport) FROM domains";
	
			$startport = $db->queryOne($sql);
	
			# Increment the highest start port by 100
			$startport += 100;
	
			$statement = $db->prepare('INSERT INTO domains (name,identifier,startport) VALUES (?, ?, ?)');
	
			$data = array($_GET['domain_name'], $_GET['domain_identifier'], $startport );

			$result = $statement->execute($data);

			$statement->free();

			# Get Domain_Id
			$sql = "SELECT id FROM domains WHERE name = '" . $_GET['domain_name'] . "'";
	
			$domain_id = $db->queryOne($sql);

			if ( $domain_id == "" ) { 
				die("Apparently insert into domains table didn't succeed. Exiting..");
			}

		} else {
			echo "Domain exists. Skipping creation.";
		}

		###########################################################################
		# Now let's get all the settings
		###########################################################################
		$sql = "SELECT name, value, product_id, setting_version_id, deploy_file_id
			FROM settings
			WHERE domain_id = " . $origination_domain_id;

		$settings_array = $db->queryAll($sql);

		print "<p>Preparing settings to be inserted";

		for ( $i = 0 ; $i < sizeof($settings_array) ; $i++ ) {
			# Append domain_id to every row
			$settings_array[$i][5] = $domain_id;
			if ( isset($_GET['fromstring']) && isset($_GET['tostring']) )
				$settings_array[$i][1] = str_replace($_GET['fromstring'],$_GET['tostring'],$settings_array[$i][1]);
			$statement = $db->prepare('INSERT INTO settings (name, value, product_id, setting_version_id, deploy_file_id, domain_id) VALUES (?,?,?,?,?,?)');
			print "Inserting value for Product_ID = " . $$settings_array[$i][2] . "|" . $$settings_array[$i][0] . "=" . $settings_array[$i][1];
			$result = $statement->execute($settings_array[$i]);
			$statement->free();
			

		}

		if ($_REQUEST['embed'] == 1) { ?>
		<script language="javascript">
			parent.document.getElementById('dashboard_frame').src='dashboard.php?embed=1&ts=<?php print mktime(); ?>';
		</script>
		<?php }

		print "<br/>\n";
		print "<a href=\"admin.php?embed=1\">Return to Administration</a>";

	}

	if ( $_GET['action'] == "deploy_product" ) {

		print "Requested deploy<p>";
		if ( $_GET['domain_name'] != "NONE" && $_GET['domain_name'] != "NONE" && $_GET['domain_name'] != "NONE" ) {
			print "You are deploying following ";
	
			print "<table border=\"1\">
				<tr><th>Domain</th><td>" . $_GET['domain_name'] . "</td></tr>
				<tr><th>Product</th><td>" . $_GET['product_name'] . "</td></tr>
				<tr><th>Server</th><td>" . $_GET['server_name'] . "</td></tr>
				<tr><th>Deploy IP</th> (if applicable) <td>" . $_GET['deploy_ip'] . "</td></tr>
			</table>";

		$db =& MDB2::factory($GLOBALS['dsn']);

		if (DB::isError($db)) {
			print "Encountered db error!\n";
			die ($db->getMessage());
		}

		if ( $_GET['deploy_ip'] != "" && check_valid_ip($_GET['deploy_ip']) == 0 ) {
			echo "<p><h2><font color=\"red\">You have supplied an invalid IP address. Either leave the fill blank or enter valid ip. Action aborted.</font></h2>";
			exit(1);
		}

		# Get Domain_Id
		$domain_id = get_domain_id( $dsn, $_GET['domain_name']);
		# Get Product_Id
		$product_id = get_product_id( $dsn, $_GET['product_name']);
		# Get Server_Id
		$system_id = get_system_id( $dsn, $_GET['server_name']);

		######################################################################
		# Insert it
		######################################################################
		$statement = $db->prepare('INSERT INTO installations (domain_id, product_id, system_id, ip, created_at) VALUES (?, ?, ?, ?, NOW())');

		$data = array($domain_id, $product_id, $system_id, $_GET['deploy_ip']);

		$result = $statement->execute($data);

		$statement->free();

		if ($_REQUEST['embed'] == 1) { ?>
		<script language="javascript">
			parent.document.getElementById('dashboard_frame').src='dashboard.php?embed=1&ts=<?php print mktime(); ?>';
		</script>
		<?php }

		print "<a href=\"admin.php?embed=1\">Return to Administration</a>";

		}

	}
}

?>

</body>
</html>
