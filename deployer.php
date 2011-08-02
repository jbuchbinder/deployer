#!/usr/bin/env php
<?php

//
//	$Id: deployer.php 2609 2011-07-26 17:55:23Z jbuchbinder $
//

$GLOBALS['base'] = dirname(__FILE__);

require_once($GLOBALS['base'] . '/config.php');
require_once($GLOBALS['base'] . '/lib/tools.php');

$cmd_line_array = commandline_arguments($argv);

require_once 'DB.php';
require_once 'MDB2.php';

#########################################################
# Check arguments
#########################################################
if ( ! isset($cmd_line_array['product']) || ! isset($cmd_line_array['server']) || ! isset($cmd_line_array['domain']) ||  ! isset($cmd_line_array['action'])) { 
	print "You need to supply product, server, domain and action being deployed. Exiting ...\n";
	exit(1);
}

#########################################################
# Connect to the database
#########################################################
$db =& MDB2::factory($dsn);

if (DB::isError($db)) {
	print "Encountered db error!\n";
        die ($db->getMessage());
}

##################################################################
# Get Product ID
##################################################################
$sql = "
	SELECT 
		  p.id
		, productContainer( p.name, '".addslashes($cmd_line_array['version'])."', '".addslashes($cmd_line_array['domain'])."' ) AS apptype
		, p.start_port_offset
		, p.shortname
	FROM products p
	WHERE
		p.name = '" . addslashes($cmd_line_array['product']) . "'
	GROUP BY p.id
";

$product_array = $db->queryRow($sql);

# Assign product ID
$product_id = $product_array[0];
$app_type = $product_array[1];
$product_port_offset = $product_array[2];
$product_short_name = $product_array[3];

##################################################################
# Get Domain ID
##################################################################
$sql = "SELECT id,identifier,startport FROM domains where name='" . $cmd_line_array['domain'] . "'";

$domain_array = $db->queryRow($sql);

$domain_id = $domain_array[0];
$domain_identifier = $domain_array[1];
$domain_start_port = $domain_array[2];
# Username application runs as. 
$app_user = $domain_identifier . "_" . $product_short_name;
# Start script
$start_script = "/run/" . $app_user . "/start.sh";

if ( $cmd_line_array['action'] == "start" ) {
	echo "Invoking Application Start for user $app_user on " .$cmd_line_array['server'] . "\n";
	start_application($cmd_line_array['server'],  $app_user, $start_script );

} else if ( $cmd_line_array['action'] == "stop" ) {
	echo "Invoking Application Stop for user $app_user on " .$cmd_line_array['server'] . "\n";
	stop_application($cmd_line_array['server'], $app_user);

} else if ( $cmd_line_array['action'] == "restart" ) {
	echo "Invoking Application Stop for user $app_user on " .$cmd_line_array['server'] . "\n";
	stop_application($cmd_line_array['server'], $app_user);
	echo "Invoking Application Start for user $app_user on " .$cmd_line_array['server'] . "\n";
	start_application($cmd_line_array['server'],  $app_user, $start_script );

###################################################################
# Undeploy
###################################################################
} else if ( $cmd_line_array['action'] == "undeploy" ) {
	echo "Invoking Application Stop for user $app_user on " .$cmd_line_array['server'] . "\n";
	stop_application($cmd_line_array['server'], $app_user);

	$domain_id = get_domain_id( $dsn, $cmd_line_array['domain']);
	$product_id = get_product_id( $dsn, $cmd_line_array['product']);
	$system_id = get_system_id( $dsn, $cmd_line_array['server']);

	echo "== Undeploying " . $cmd_line_array['product'] . " for " .$cmd_line_array['domain'] . "  from  " .$cmd_line_array['server'] .  "\n";

	######################################################################
	# Insert it
	######################################################################
	$statement = $db->prepare('DELETE FROM installations WHERE domain_id = ? AND  product_id = ? AND system_id = ?');

	$data = array($domain_id, $product_id, $system_id );

	$result = $statement->execute($data);

	$statement->free();

##################################################################
# Deploy
##################################################################
} else if ( $cmd_line_array['action'] == "deploy" or $cmd_line_array['action'] == "colddeploy" ) {

	if ( ! isset($cmd_line_array['version'])) { 
		print "Deploy Action requires version to be deployed. Exiting ... ";
		exit(1);
	}

	$domain_id = get_domain_id( $dsn, $cmd_line_array['domain']);
	$product_id = get_product_id( $dsn, $cmd_line_array['product']);
	$system_id = get_system_id( $dsn, $cmd_line_array['server']);
	
	# Update the installations table with the last_deployed_version value
	$statement = $db->prepare('UPDATE installations SET last_version_deployed = ? AND updated_at = NOW() WHERE domain_id = ? AND product_id = ? AND system_id = ?');

	$data = array($cmd_line_array['version'], $domain_id, $product_id, $system_id );

	$result = $statement->execute($data);

	$statement->free();

	echo "Invoking Application Stop for user $app_user on " .$cmd_line_array['server'] . "\n";
	stop_application($cmd_line_array['server'], $app_user);
	
	# Create prep directory. This is the directory where we are gonna prepare installation
	$prep_dir = tempnam($temp_dir, $app_user . "_" . date("Ymd-Hi") . '_');
	`rm -rf $prep_dir`;
	mkdir ($prep_dir);
	
	print "Installing App Type " . $app_type . "\n";

	# Get container information
	$container_array = get_container_array( $dsn );

	# Get the array of available product versions
	$version_array = get_product_version_array($dsn, $dist_dir);

	#
	$int_version = convert_version_to_int($cmd_line_array['version']);

	# Unpack App Type and WAR files
	unpack_tar_archive( $prep_dir, $dist_dir . "/" . $container_array[$app_type]['archive']);	
	print "Unpackaging " . $dist_dir . "/" . $container_array[$app_type]['archive'] . "\n";

	# Create server.xml
	//if ( $cmd_line_array['product'] != "messaging" && $cmd_line_array['product'] != "msg-email" && $cmd_line_array['product'] != "msg-sms" ) {
		print "Preparing server.xml file.\n";
		$serverxml_string = write_server_xml($prep_dir . "/" . $container_array[$app_type]['server_xml'], $domain_start_port + $product_port_offset, "jdbc:mysql://DB_HOST/DB_NAME", "DB_USER", "DB_PASSWORD", $cmd_line_array['product'], $cmd_line_array['domain'], $app_type);
		write_string_into_file($prep_dir . "/" . $container_array[$app_type]['server_xml'], $serverxml_string);
		print "Preparing tomcat-users.xml.\n";
		if (file_exists( $prep_dir . "/conf/tomcat-users.xml" )) {
			$tomcatusers_string = write_tomcat_users_xml( $prep_dir . "/conf/tomcat-users.xml", $cmd_line_array['product'], $cmd_line_array['domain'], $app_type);
			write_string_into_file($prep_dir . "/conf/tomcat-users.xml", $tomcatusers_string);
		} else {
			print "No tomcat-users.xml found in container, skipping prep.\n";
		}
	//} else {
	//	print "Skipping server.xml preparation, not needed at the moment.\n";
	//}
	
	# Get the full path to the product archive e.g. /dist/product/product-1.2.5.tar.gz
	$product_archive = $version_array[$cmd_line_array['product']][$int_version]['filename'];

	print "Unpackaging " . $product_archive . " into /" . $container_array[$app_type]['extract_location'] . "\n";
	unpack_tar_archive( $prep_dir . "/" . $container_array[$app_type]['extract_location'], $product_archive);

	##################################################################3
	# Get list of files that need to be deployed
	##################################################################3
	$sql = "SELECT t.name, m.deploy_file_id, f.name FROM deploy_files_manifests m LEFT OUTER JOIN products p ON m.product_id = p.id LEFT OUTER JOIN deploy_files f ON f.id = m.deploy_file_id LEFT OUTER JOIN deploy_file_types t ON f.deploy_file_type_id = t.id WHERE p.name = '" . $cmd_line_array['product'] . "'";
	#####$sql = "SELECT file_type, deploy_file_id, deploy_file_name FROM deploy_files_manifests_v where product_name='" . $cmd_line_array['product'] . "'";
	
	$deploy_files_array = $db->queryAll($sql);
	
	for ( $i = 0; $i < sizeof($deploy_files_array) ; $i++ ) {
	
		switch ( $deploy_files_array[$i][0]) {
	
			case "name_value_configuration":
			print "Building name/value conf file -> " . $deploy_files_array[$i][2] . "\n";
			$sql = "SELECT name,value FROM settings 
				WHERE domain_id = " . $domain_id .
				" AND deploy_file_id = " . $deploy_files_array[$i][1] .
				" ORDER BY name";
			$result = $db->query($sql);
	
			$name_value_string = "";
			while ($row = $result->fetchRow()) {
				$name_value_string .= trim($row[0]) . "=" . trim($row[1]) . "\n";
			}
	
			write_string_into_file($prep_dir."/".$deploy_files_array[$i][2], $name_value_string); 
	
			break;
	
			case "shell_script":
			print "Building shell script -> " . $deploy_files_array[$i][2] . "\n";
				$sql = "SELECT template_path FROM config_templates
					WHERE deploy_file_id = " . $deploy_files_array[$i][1];

			$t_path = $template_dir . "/" . $db->queryOne($sql);
			print " --> Grabbing template from ${t_path} ... ";

			if (copy( $t_path, $prep_dir . "/" . $deploy_files_array[$i][2] )) {
				print "[OK]\n";
			} else {
				print "[COPY FAILED]\n";
			}
			chmod( $prep_dir . "/" . $deploy_files_array[$i][2], 0755 );
			break;
	
		}
	
	}


	# Verify contents
	# Make sure that WAR files are only thing present in e.g. webapps directory for Tomcat
	# While at it unzip all WAR files
	if ( $app_type == "tomcat" ) {
		$num_wars = explode("\n",`find $prep_dir/webapps -name '*.war' |sort`);
		if ( sizeof($num_wars) > 0 ) {
			print "We have at least one WAR file. Good\n";
			// for each item (file) in the array...
			for ($count=0;$count<count($num_wars);$count++) {
				// get the filename (including preceding directory, ie: ./software/gth1.0.9.tar.gz)
				$filename=$num_wars[$count];
				// if it's not a directory and the filename is not empty add it to the version
				// array
				if (!is_dir($filename) && $filename != "" ) {
					# Extract product name
					$basePath = basename($filename, ".war");
					echo "Unjaring " . $filename . " into $prep_dir/webapps/${basePath}\n";
					mkdir("$prep_dir/webapps/" . $basePath);
					print `cd $prep_dir/webapps/$basePath ; /usr/java/jdk/bin/jar -xf $filename 2>&1 `;
				}
			}

		} else {
			die ("</pre><h3>No WAR files in the webapps directory. Something is horribly wrong</h3></div><div class=deployment_failure><h1>ERROR: Deployment unsuccessful. Please check output</h1></div></body></html>\n");
		}
	}

	# Generate proxy_ajp.conf
	# Get Server_Id
		$server_id = get_system_id( $dsn, $cmd_line_array['server'] );

		$sql = "SELECT p.proxyname, d.startport, p.start_port_offset, p.protocol
			FROM installations AS i, domains AS d, products AS p
			WHERE i.system_id = " . $server_id . "
			AND d.id = i.domain_id
			AND p.id = i.product_id";

		$proxy_array = $db->queryAll($sql);
		if (!function_exists('proxysort')) {
			function proxysort( $a, $b ) {
				// Force root namespace to be last
				if($a[3] == '/') return -1;

				// Stock namespace comparison, reversed
				if($a[3] == $b[3]) return 0;
				return ($a[3] < $b[3]) ? 1 : -1;
			}
		}

		//print_r($proxy_array);
		usort($proxy_array, 'proxysort');
		//print_r($proxy_array);

		$proxy_ajp_conf = "LoadModule proxy_ajp_module modules/mod_proxy_ajp.so\n";
		$proxy_ajp_conf .= "### Do not proxy Shibboleth ###\nProxyPass /Shibboleth.sso !\n";
		$proxy_ajp_conf .= "### Do not proxy RSS feeds ###\nProxyPass /rss !\n";
		$workers = array();
		$workers_headers = "";
		$workers_properties = "";
		$http_offset = 1;
		$ajp_offset = 2;
		for ( $i = 0 ; $i < sizeof($proxy_array) ; $i++ ) {
			$ajp_port = $proxy_array[$i][1] + $proxy_array[$i][2] + $ajp_offset;
			$http_port = $proxy_array[$i][1] + $proxy_array[$i][2] + $http_offset;
			# If there is a semi-colon in the proxy_name it means that we
			# have multiple proxy_names for a single product
			if (strpos($proxy_array[$i][0], ";") === false) {
				$proxy_names[0] = $proxy_array[$i][0];
			} else {
				$proxy_names = explode(";", $proxy_array[$i][0]);
			}

			for ( $j = 0 ; $j < sizeof($proxy_names) ; $j++ ) {
				switch ($proxy_array[$i][3]) {
					case 'HTTP': // mod_proxy_http
					$proxy_ajp_conf .= "ProxyPass " . $proxy_names[$j] . " http://localhost:" .$http_port . $proxy_names[$j] . "\n";
					$proxy_ajp_conf .= "ProxyPassReverse " . $proxy_names[$j] . " http://localhost:" .$http_port . $proxy_names[$j] . "\n";
					break;

					case 'JK': // mod_jk
					$worker_name = str_replace('/', '', $proxy_names[$j]);
					if ($proxy_name[$j] == '/') { $proxy_names[$j] = ''; }
					if ($worker_name == '') { $worker_name = 'ROOT'; }
					$workers_properties .= "worker.${worker_name}.type=ajp13\n";
					$workers_properties .= "worker.${worker_name}.host=localhost\n";
					$workers_properties .= "worker.${worker_name}.port=${ajp_port}\n";
					$workers[] = $worker_name;

					$proxy_ajp_conf .= "JkMount " . $proxy_names[$j] . "/* ${worker_name}\n";
					break;

					case 'AJP': default: // mod_proxy_ajp
					$proxy_ajp_conf .= "ProxyPass " . $proxy_names[$j] . " ajp://localhost:" .$ajp_port . $proxy_names[$j] . "\n";
					break;
				}
			}

			# Destroy the array
			unset($proxy_names);

	}

	$workers_headers = "worker.list=" . join(",", $workers) . "\n";

	write_string_into_file($prep_dir."/proxy_ajp.conf",$proxy_ajp_conf);
	write_string_into_file($prep_dir."/workers.properties",$workers_headers . $workers_properties);

	print "Changing ownership of /logs/".$app_user. " on " . $cmd_line_array['server'] ."\n";
	$cmd = $GLOBALS['base']."/scripts/ssh-wrapper.sh ".escapeshellarg($cmd_line_array['server'])." ".escapeshellarg("install -o  ".$app_user." -d /logs/".$app_user);
	print `$cmd`;

	print "Rsyncing to target:\n";
	$cmd = $GLOBALS['base']."/scripts/rsync-target.sh ".escapeshellarg($cmd_line_array['server'])." ".escapeshellarg( $app_user )." ".escapeshellarg($prep_dir);
	//print $cmd."\n";
	print `$cmd`;

	print "Move proxy_ajp.conf and workers.properties to /etc/httpd/deployer:\n";
	$cmd = $GLOBALS['base']."/scripts/ssh-wrapper.sh ".escapeshellarg($cmd_line_array['server'])." ".escapeshellarg("mv /run/$app_user/proxy_ajp.conf /run/$app_user/workers.properties /etc/httpd/deployer");
	print `$cmd`;


	print "Reload Apache:\n";
	$cmd = $GLOBALS['base']."/scripts/ssh-wrapper.sh ".escapeshellarg($cmd_line_array['server'])." ".escapeshellarg("/etc/init.d/httpd reload");
	print `$cmd`;


	# Change ownership to the app_user
	print "Changing permission:\n";
	$cmd = $GLOBALS['base']."/scripts/ssh-wrapper.sh ".escapeshellarg($cmd_line_array['server'])." ".escapeshellarg("chown -R  ".$app_user."    /run/".$app_user);
	print `$cmd`;

	if ( $cmd_line_array['action'] != "colddeploy" ) {
		echo "Invoking Application Start for user $app_user on " .$cmd_line_array['server'] . "\n";
		start_application($cmd_line_array['server'],  $app_user, $start_script );
	}

	echo "Cleaning up local prep directory ${prep_dir}\n";
	`rm -rf $prep_dir`;

        print "</div><div class=deployment_success><h1>Product deployed successfully!</h1></div></body></html>";

} else {
	print "No valid action was specified...";
}


?>
