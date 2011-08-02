<?php
//
//	$Id: tools.php 2609 2011-07-26 17:55:23Z jbuchbinder $
//

require_once($GLOBALS['base'] . '/config.php');

require_once 'DB.php';
require_once 'MDB2.php';

######################################################################
# Returns the Hostname of the server web server is running on 
######################################################################
function web_hostname() {
	return substr($_ENV['HOSTNAME'],0,strpos($_ENV['HOSTNAME'], "."));
}

function coloid() {
	return trim( file_get_contents( "/etc/coloid" ) );
}

function add_config_change( $dsn, $user, $domain, $product, $hash ) {
	$db =& MDB2::factory($dsn);
	if (DB::isError($db)) { die ($db->getMessage()); }

	if ($hash != "[]") {
		$sql = "INSERT INTO config_log ( user, domain_id, product_id, changehash ) VALUES ( '".addslashes($user)."', ( SELECT id FROM domains WHERE name = '".addslashes($domain)."' ), ( SELECT id FROM products WHERE name = '".addslashes($product)."' ), '".addslashes($hash)."' );";
		syslog( LOG_INFO, $sql );
		$db->query($sql);
	}
}

// Return true if we can proceed, false if action has started
function action_lock($dsn, $action, $stamp) {
	$db =& MDB2::factory($dsn);
	
	if (DB::isError($db)) {
		die ($db->getMessage());
	}

	$sql = "SELECT COUNT(*) FROM action_lock WHERE action='".addslashes($action)."' AND stamp='".$stamp."' ;";
	syslog( LOG_INFO, $sql );
	$action_lock = $db->queryOne($sql);
	if ( $action_lock > 0 ) {
			return false;
	} else {
	  // Otherwise, insert.
	  $sql = "INSERT INTO action_lock ( action, stamp ) VALUES ( '".addslashes($action)."', '".addslashes($stamp)."' ); ";
	  syslog( LOG_INFO, $sql );
	  $result = $db->query($sql);

	  return true;
       }

}

#####################################################################################
#
#####################################################################################
function check_valid_ip($ip) {

    $octets = explode(".", $ip);

    if ( sizeof($octets) != 4 ) {
	return 0;
    }

    foreach ( $octets AS $octet ) {
            if ( !is_numeric($octet) || $octet > 255 ) {
                return 0;
            }
    }

    return 1;

}


#####################################################################################
#
#####################################################################################
function resolve_server( $dsn, $server_name, $product_name ) {
	$db =& MDB2::factory( $dsn );
	
	if (DB::isError($db)) {
		die ($db->getMessage());
	}

	$sql = "SELECT IFNULL(i.ip,s.name) FROM products p, systems s, installations i WHERE i.product_id = p.id AND i.system_id = s.id AND s.name='".addslashes($server_name)."' AND p.name = '".addslashes($product_name)."';";
	return $db->queryOne( $sql );
}

#####################################################################################
#
#####################################################################################
function commit_config_array($dsn, $data, $domain, $product) {
	$db =& MDB2::factory($dsn);
	
	if (DB::isError($db)) {
		die ($db->getMessage());
	}

	foreach ($data AS $v) {
		#$sql = "UPDATE settings SET value='".addslashes($v[1])."' WHERE id='".addslashes($v[0])."' AND deploy_file_id = ( SELECT df.id FROM deploy_files df WHERE df.name = '".addslashes($v[2])."' ) AND product_id = ( SELECT p.id FROM products p WHERE p.name = '".addslashes($product)."' ) AND domain_id = ( SELECT d.id FROM domains d WHERE d.name = '".addslashes($domain)."' );";
		$sql = "UPDATE settings SET value='".addslashes($v[1])."' WHERE id=".addslashes($v[0]).";";
		syslog( LOG_INFO, $sql );
		$result = $db->query($sql);
	}

	return true;
}

function commit_remove_config_array($dsn, $data, $domain, $product) {
	$db =& MDB2::factory($dsn);
	
	if (DB::isError($db)) {
		die ($db->getMessage());
	}

	foreach ($data AS $v) {
		$sql = "DELETE FROM settings WHERE id=".addslashes($v).";";
		syslog( LOG_INFO, $sql );
		$result = $db->query($sql);
	}

	return true;
}

function commit_new_config_array($dsn, $data, $domain, $product) {
	$db =& MDB2::factory($dsn);
	
	if (DB::isError($db)) {
		die ($db->getMessage());
	}

	foreach ($data AS $v) {
		$sql = "INSERT INTO settings ( name, value, domain_id, product_id, deploy_file_id ) VALUES ( '".addslashes($v[0])."', '".addslashes($v[1])."', ( SELECT d.id FROM domains d WHERE d.name = '".addslashes($domain)."' ), ( SELECT p.id FROM products p WHERE p.name = '".addslashes($product)."' ), '".addslashes($v[2])."' );";
		syslog(LOG_INFO, $sql);
		$result = $db->query($sql);
	}

	return true;
}

function get_config_files_array( $dsn, $domain, $product ) {
	$db =& MDB2::factory($dsn);
	
	if (DB::isError($db)) {
		die ($db->getMessage());
	}

	$sql = "
		SELECT
			  DISTINCT( df.name ) AS config_file
			, df.id AS config_file_id
		FROM deploy_files_manifests m
		LEFT OUTER JOIN deploy_files df ON df.id = m.deploy_file_id
		LEFT OUTER JOIN products p ON p.id = m.product_id
		LEFT OUTER JOIN deploy_file_types t ON t.id = df.deploy_file_type_id
		WHERE
			    t.name = 'name_value_configuration'
			AND p.name = '".addslashes( $product )."'
	";

	$result = $db->query($sql);

	$options = array();	
	while ($row = $result->fetchRow()) {
		if ($row[0] != "") {
			$options[] = array (
				  'config_file' => $row[0]
				, 'config_file_id' => $row[1]
			);
		}
	}

	return $options;
}

function get_config_options_array( $dsn, $domain, $product ) {
	$db =& MDB2::factory($dsn);
	
	if (DB::isError($db)) {
		die ($db->getMessage());
	}

	$sql = "
		SELECT
			  df.name AS config_file
			, df.id AS config_file_id
			, s.name AS name
			, s.value AS value
			, s.id AS id
		FROM settings s
		LEFT OUTER JOIN domains d ON d.id = s.domain_id
		LEFT OUTER JOIN products p ON p.id = s.product_id
		LEFT OUTER JOIN deploy_files df ON df.id = s.deploy_file_id
		WHERE
			    d.name = '".addslashes( $domain )."'
			AND p.name = '".addslashes( $product )."'
		ORDER BY df.name,s.name
	";

	$result = $db->query($sql);

	$options = array();	
	while ($row = $result->fetchRow()) {
		$options[] = array (
			  'config_file' => $row[0]
			, 'config_file_id' => $row[1]
			, 'name' => trim($row[2])
			, 'value' => $row[3]
			, 'id' => $row[4]
		);
	}

	return $options;
}


#############################################################################
# Get Domain ID from Domain Name
#############################################################################
function get_domain_id( $dsn, $domain_name ) {
	$db =& MDB2::factory($dsn);
	
	if (DB::isError($db)) {
		die ($db->getMessage());
	}

	$sql = "SELECT id FROM domains WHERE name = '" . $domain_name . "'";

	return $db->queryOne($sql);

}

#############################################################################
# Get Product ID from Product Name
#############################################################################
function get_product_id( $dsn, $product_name ) {
	$db =& MDB2::factory($dsn);
	
	if (DB::isError($db)) {
		die ($db->getMessage());
	}

	$sql = "SELECT id FROM products WHERE name = '" . $product_name . "'";

	return $db->queryOne($sql);

}

#############################################################################
# Get System ID from System Name
#############################################################################
function get_system_id( $dsn, $system_name ) {
	$db =& MDB2::factory($dsn);
	
	if (DB::isError($db)) {
		die ($db->getMessage());
	}

	# Get Server_Id
	$sql = "SELECT id FROM systems WHERE name = '" . $system_name . "'";

	return $db->queryOne($sql);

}

#############################################################################
# Domain Array
#############################################################################
function get_domain_array( $dsn ) {
	$db =& MDB2::factory($dsn);
	
	if (DB::isError($db)) {
		die ($db->getMessage());
	}

	$sql = "SELECT name FROM domains";

	$result = $db->query($sql);

	$domain_array = array();	
	while ($row = $result->fetchRow()) {
		$domain_array[] = $row[0];
	}

	return $domain_array;
}

#############################################################################
# Container array
#############################################################################
function get_container_array( $dsn ) {
	$db =& MDB2::factory($dsn);
	
	if (DB::isError($db)) {
		die ($db->getMessage());
	}

	$sql = "SELECT name, archive, extract_location, server_xml_location FROM containers";

	$result = $db->query($sql);

	$container_array = array();	
	while ($row = $result->fetchRow()) {
		$container_array[$row[0]] = array (
			  "archive" => $row[1]
			, "extract_location" => $row[2]
			, "server_xml" => $row[3]
		);
	}

	return $container_array;
}

#############################################################################
# Product Array
#############################################################################
function get_product_array($dsn, $domain = "any" ) {
	$db =& MDB2::factory($dsn);
	
	if (DB::isError($db)) {
		die ($db->getMessage());
	}

	$sql = "SELECT name, proxyname, apptype FROM products ORDER BY name";

	$result = $db->query($sql);
	
	while ($row = $result->fetchRow()) {
		$product_array[$row[0]] = array (
			"proxy_name" => $row[1],
			"app_type" => $row[2] );
	}

	return $product_array;

}

function get_server_array($dsn, $domain = "any" ) {

	$db2 =& MDB2::factory($dsn);
	
	if (DB::isError($db2)) {
		die ($db2->getMessage());
	}

	$sql = "SELECT name FROM systems";

	return $db2->queryAll($sql);

}

function get_template_array($dsn, $domain = "any" ) {
	$db =& MDB2::factory($dsn);
	
	if (DB::isError($db)) {
		die ($db->getMessage());
	}

	$sql = "SELECT t.id,t.name,f.description FROM config_templates t LEFT OUTER JOIN deploy_files f ON f.id = t.deploy_file_id;";

	$result = $db->query($sql);
	
	while ($row = $result->fetchRow()) {
		$template_array[$row[0]] = array(
			  'name' => $row[1]
			, 'description' => $row[2]
			, 'id' => $row[0]
		); 
	}

	return $template_array;
}




#############################################################################
# Display's Domain Drop Down
#############################################################################
function display_domain_dropdown ($varname = "domain_name") {

	print "<SELECT name=" . $varname . ">
	<option value=NONE>Please pick one</option>\n";

	$domains = &get_domain_array( $GLOBALS['dsn'] );
	
	for ( $i = 0 ; $i < sizeof($domains) ; $i++ ) {
		print "<OPTION>" . $domains[$i] . "</OPTION>\n";
	}

	print "	
	</SELECT>";
}

#############################################################################
# Display's Product Drop Down
#############################################################################
function display_product_dropdown ($varname = "product_name") {

	$products = &get_product_array($GLOBALS['dsn'], "any");

	print "<select name=" . $varname . ">
	<option value=NONE>Please pick one</option>\n";
	while ($product_name = current($products)) {
		print "<option value=" . key($products) . ">".key($products)."</option>\n";
		next($products);
	}

	print "</select>";

}

#############################################################################
# Display's Domain Drop Down
#############################################################################
function display_server_dropdown ($varname = "server_name") {

	print "<SELECT name=" . $varname . ">
	<option value=NONE>Please pick one</option>\n";

	$servers = &get_server_array( $GLOBALS['dsn'] );
	
	for ( $i = 0 ; $i < sizeof($servers) ; $i++ ) {
		print "<OPTION>" . $servers[$i][0] . "</OPTION>\n";
	}

	print "	
	</SELECT>";
}


#############################################################################
# Convert a string version into an integer e.g. 1.2.7 would become
# 1002007, 1.2.14 would correspond to 1002014.
#############################################################################
function convert_version_to_int ( $version ) {
	
	$base = 1000;

	$version_exploded = explode(".", $version);

	$version_int = 0;

	for ( $i = sizeof($version_exploded) - 1 ; $i >=0 ; $i-- ) {

		$version_int += $version_exploded[$i] * pow($base, sizeof($version_exploded) - 1 - $i);

	} 

	return $version_int;

}

#############################################################################
# Build an array of available product versions
#############################################################################
function get_product_version_array ( $dsn, $distdir = "/dist" ) {

	# Let's see what products we have
	$product_array = get_product_array($dsn);

	foreach ( $product_array as $key => $value) {
		$filelist = explode("\n",`find $distdir/$key -name "$key-[0-9]*.tar*" |sort`);

		// for each item (file) in the array...
		for ($count=0;$count<count($filelist);$count++) {
			// get the filename (including preceding directory, ie: ./software/gth1.0.9.tar.gz)
			$filename=$filelist[$count];
			// if it's not a directory and the filename is not empty add it to the version
			// array
			if (!is_dir($filename) && $filename != "" ) {
				if ( eregi( '(.*)-([0-9]+)\.([0-9]+)\.([0-9]+)\.(.*)tar(.*)', $filename, $out) ) {
					$version = "$out[2].$out[3].$out[4]";
					$numeric_version = convert_version_to_int($version);
					$version_array[$key][$numeric_version] = array( "filename" => $filename,
					"version" => $version );
					unset($out);
				} else if ( eregi( '(.*)-([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)\.(.*)tar(.*)', $filename, $out) ) {
					$version = "$out[2].$out[3].$out[4].$out[5]";
					$numeric_version = convert_version_to_int($version);
					$version_array[$key][$numeric_version] = array( "filename" => $filename,
					"version" => $version );
					unset($out);
				}
			}

		}

	}

	return $version_array;
}

##############################################################################
# Copied from
# http://www.php.net/features.commandline

#If the argument is of the form –NAME=VALUE it will be represented in the array as an element #with the key NAME and the value VALUE. I the argument is a flag of the form -NAME it will be #represented as a boolean with the name NAME with a value of true in the associative array.

#Example:
#
#<?php print_r(arguments($argv));
# php5 myscript.php --user=nobody --password=secret -p
#
#Array
#(
#    [user] => nobody
#    [password] => secret
#    [p] => true
#)
function commandline_arguments($argv) {
    $_ARG = array();
    foreach ($argv as $arg) {
        if (ereg('--[a-zA-Z0-9]*=.*',$arg)) {
            $str = split("=",$arg); $arg = '';
            $key = ereg_replace("--",'',$str[0]);
            for ( $i = 1; $i < count($str); $i++ ) {
                $arg .= $str[$i];
            }
                        $_ARG[$key] = $arg;
        } elseif(ereg('-[a-zA-Z0-9]',$arg)) {
            $arg = ereg_replace("-",'',$arg);
            $_ARG[$arg] = 'true';
        }
   
    }
return $_ARG;
}

##############################################################################
# Unpacks a tar archive into a destination directory
##############################################################################
function unpack_tar_archive( $dest_dir, $archive_name ) {
	$cmd = "tar -C " . $dest_dir . " -xf " . $archive_name;
#	print "Executing $cmd\n";
	`$cmd`;
}

##############################################################################
# Writes a string into a file
##############################################################################
function write_string_into_file( $dest_file, $string ) {

	# Get Directory component
	$dir_name = dirname($dest_file);

	# Create directory if it doesn't exist
	if ( !is_dir($dir_name) ) {
		`mkdir -p $dir_name`;
	}

	file_put_contents($dest_file, $string );

	return TRUE;
}

######################################################################################3
# Creates tomcat server.xml
######################################################################################3
function write_server_xml( $source, $port, $dburl = "jdbc://" , $dbuser = "user", $dbpass = "password", $product = "base", $domain = 'Production', $container = "tomcat" ) {
	$xslSheet = new DOMDocument();
	$xslSheet->loadXML("<"."?xml version=\"1.0\"?".">
<xsl:stylesheet xmlns:xsl=\"http://www.w3.org/1999/XSL/Transform\" version=\"1.0\">
<xsl:param name=\"startPort\" />
<xsl:output method=\"xml\" indent=\"yes\"/> 

  <xsl:template match=\"comment()\">
  </xsl:template>
 
  <xsl:template match=\"/Server/@port\">
    <xsl:attribute name=\"port\">
      <xsl:value-of select=\"\$startPort + 0\" />
    </xsl:attribute>
  </xsl:template>

  <xsl:template match=\"/Server/Service/Connector[@port='8080']/@port\">
    <xsl:attribute name=\"port\">
      <xsl:value-of select=\"\$startPort + 1\" />
    </xsl:attribute>
    <!--
    <xsl:attribute name=\"connectionTimeout\">
      <xsl:value-of select=\"0\" />
    </xsl:attribute>
    <xsl:attribute name=\"disableUploadTimeout\">
      <xsl:value-of select=\"'false'\" />
    </xsl:attribute>
    -->
  </xsl:template>

  <xsl:template match=\"/Server/Service/Connector[@port='8009']/@port\">
    <xsl:attribute name=\"port\">
      <xsl:value-of select=\"\$startPort + 2\" />
    </xsl:attribute>
    <!--
    <xsl:attribute name=\"connectionTimeout\">
      <xsl:value-of select=\"0\" />
    </xsl:attribute>
    <xsl:attribute name=\"disableUploadTimeout\">
      <xsl:value-of select=\"'false'\" />
    </xsl:attribute>
    -->
  </xsl:template>

  <xsl:template match=\"@*|node()\">
    <xsl:copy>
      <xsl:apply-templates select=\"@*|node()\"/>
    </xsl:copy>
  </xsl:template>
 
</xsl:stylesheet>
");
	$xsl = new XSLTProcessor();
	$xsl->ImportStylesheet($xslSheet);
	$xsl->setParameter('', 'startPort', $port ? $port : 8000 );

	$xml = new DOMDocument();
	$xml->load($source);

	$stageOne = $xsl->transformToXML($xml);

	// Secondary config changes if necessary
	$template = $GLOBALS['template_dir'] . '/' . $product . '/xsl/serverxml_configuration.xsl';
	print "\nAttempting to use template $template\n";

	if (!file_exists($template)) {
		print "\nTemplate doesn't exist\n";
		return $stageOne;
	}

	$xsl = new XSLTProcessor();
	$xsl->ImportStylesheet(DomDocument::loadXML(file_get_contents($template)));
	$xsl->setParameter('', 'domain', $domain );
	$xsl->setParameter('', 'dbUrl', $dbUrl );
	$xsl->setParameter('', 'dbUsername', $dbuser );
	$xsl->setParameter('', 'dbPassword', $dbpass );
	$xsl->setParameter('', 'product', $product );
	$xsl->setParameter('', 'container', $container );

	$xml = new DOMDocument();
	$xml->loadXML($stageOne);
	$stageTwo = $xsl->transformToXML($xml);
	//print "\n XML returned: $stageTwo\n";

	return $stageTwo;
}


function write_tomcat_users_xml( $source, $product = "base", $domain = 'Production', $container = "tomcat" ) {
	$template = $GLOBALS['template_dir'] . '/' . $product . '/xsl/tomcat_users.xsl';
	print "\nAttempting to use template $template\n";

	if (!file_exists($template)) {
		print "\nTemplate doesn't exist\n";
		return file_get_contents($source);
	}

	$xsl = new XSLTProcessor();
	$xsl->ImportStylesheet(DomDocument::loadXML(file_get_contents($template)));
	$xsl->setParameter('', 'domain', $domain );
	$xsl->setParameter('', 'product', $product );
	$xsl->setParameter('', 'container', $container );

	$xml = new DOMDocument();
	$xml->loadXML(file_get_contents($source));
	$out = $xsl->transformToXML($xml);

	return $out;
} // end function write_tomcat_users_xml

function create_versioned_symlink( $file, $version ) {
	$location = dirname( $file );
	$filename = basename( $file );
	$link = $filename . "." . $version;
	`( cd "$location" ; mv "$filename" "$link";  ln -s "$link" "$filename" )`;
}

function create_versioned_symlinks_for_directory( $dir, $version ) {
	$dir_handle = @opendir( $dir );
	while ( $file = readdir( $dir_handle ) ) {
		if( $file == "." || $file == ".." ) { continue; }
		create_versioned_symlink( $dir . "/" . $file, $version );
	}
	closedir($dir_handle);
}

#################################################################
# Inserts into log
#################################################################
function insert_into_log ( $dsn, $user_requesting_action, $server = "", $domain = "", $product = "", $log_message ) {

	$db2 =& MDB2::factory($dsn);
	
	if (DB::isError($db2)) {
		die ($db2->getMessage());
	}

	$statement = $db2->prepare('INSERT INTO log (user,server,domain,product,message) VALUES (?, ?,?,?,?)');
	$data = array($user_requesting_action,$server,$domain,$product,$log_message);
	$result = $statement->execute($data);
	$statement->free();

}


function start_application( $server, $username ) {
	application_action( $server, $username, "start" );
}

function restart_application( $server, $username ) {
	application_action( $server, $username, "restart" );
}

function stop_application( $server, $username ) {
	application_action( $server, $username, "stop" );
}

function application_action( $server, $username, $app_action ) {
	$action = $GLOBALS['base'] . "/scripts/action.sh";
	print "DEBUG: $action -s " . escapeshellarg($server) . " -u " . escapeshellarg($username) . " -a " . escapeshellarg($app_action)."\n";
	passthru( "$action -s " . escapeshellarg($server) . " -u " . escapeshellarg($username) . " -a " . escapeshellarg($app_action) );
}

?>
