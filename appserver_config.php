<?php
//	$Id: appserver_config.php 1164 2008-12-01 21:23:37Z jbuchbinder $

include_once("./config.php");
include_once("./lib/tools.php");

$__V = split(" ", "\$Revision: 1164 $");
$VERSION = trim( str_replace( '$', '', $__V[1] ) );

?>
<html>
<head>
	<script type="text/javascript" src="./jsscripts/standardista-common.js"></script>
	<script type="text/javascript" src="./jsscripts/standardista-css.js"></script>
	<script type="text/javascript" src="./jsscripts/standardista-table-sorting.js"></script>
	<link rel="stylesheet" href="./css/standardista-style.css" type="text/css" />
	<link rel="stylesheet" href="./css/hilight.css" type="text/css" />
	<link rel="stylesheet" href="./css/deployer.css" type="text/css" />
        <title><?php print coloid() . "-" . web_hostname(); ?> Deployer :: App Server Configuration Viewer</title>
</head>
<body>
<?php if (!$_GET['embed']) { ?>
<h1>Configuration Viewer [version <?php print $VERSION; ?>]</h1>
<ul class="buttons">
	<li><a href="repository_sync.php">Update software from shrek</a></li>
	<li><a href="edit_config.php">Edit Config</a></li>
	<li><a href="dashboard.php">Dashboard</a></li>
</ul>
<p>
<?php } ?>
<form method="GET">
<input type="hidden" name="embed" value="<?php print htmlentities($_GET['embed']); ?>" />
<?php

$GLOBALS['base'] = dirname(__FILE__);

$products = array_keys( get_product_array( $dsn ) );
$properties = array();
foreach ( $products AS $product ) {
	$properties[ $product ] = get_config_files_for_product( $dsn, $product );
}

################################################################################
# Downloaded from
# http://us3.php.net/manual/en/function.parse-ini-file.php#57075
# Parse an INI file and put it into an array
################################################################################
function parse_ini_file_quotes_safe($f)
{
 $r="";
 $sec="";
 $f=@file($f);
 for ($i=0;$i<@count($f);$i++)
 {
  $newsec=0;
  $w=@trim($f[$i]);
  if ($w)
  {
   if ((!$r) or ($sec))
   {
   if ((@substr($w,0,1)=="[") and (@substr($w,-1,1))=="]") {$sec=@substr($w,1,@strlen($w)-2);$newsec=1;}
   }
   if (!$newsec)
   {
   $w=@explode("=",$w);$k=@trim($w[0]);unset($w[0]); $v=@trim(@implode("=",$w));
   if ((@substr($v,0,1)=="\"") and (@substr($v,-1,1)=="\"")) {$v=@substr($v,1,@strlen($v)-2);}
   if ($sec) {$r[$sec][$k]=$v;} else {$r[$k]=$v;}
   }
  }
 }
 return $r;
}
################################################################################

function get_config_files_for_product($dsn, $product_name) {
	$sql = "( SELECT d.name FROM deploy_files_manifests m, deploy_files d, products p, deploy_file_types t WHERE m.product_id = p.id AND m.deploy_file_id = d.id AND t.id = d.deploy_file_type_id AND p.name = '".addslashes($product_name)."' AND t.name = 'name_value_configuration' ) UNION ( SELECT cf.config_file FROM config_files cf LEFT OUTER JOIN products p ON p.id = cf.product_id WHERE p.name = '".addslashes($product_name)."' ORDER BY cf.config_file )";

	$db = DB::connect( $dsn, true );
	if ( DB::isError( $db ) ) {
	        die ( $db->getMessage() );
	}
	$recordset = $db->query( $sql );
	if ( DB::isError( $recordset ) ) {
	        die ( $recordset->getMessage() );
	}

	$i = array();
	while ( $row = $recordset->fetchRow() ) {
	        $i[] = $row[0];
	}

	return $i;
}

$sql = "SELECT d.name,s.name,p.name, concat('/run/',d.identifier,'_',p.shortname)
	FROM installations AS i, systems AS s, domains AS d , products AS p 
	WHERE i.system_id = s.id 
	AND i.domain_id = d.id 
	AND i.product_id = p.id";

$db = DB::connect($dsn, true);

if (DB::isError($db)) {
        die ($db->getMessage());
}

$recordset = $db->query($sql);

if (DB::isError($recordset)) {
        die ($recordset->getMessage());
}

while ($row = $recordset->fetchRow()) {

        $instances[$row[0]][] = array( "server_name" => $row[1], 
				"product_name" => $row[2],
				"directory" => $row[3]);
}

#echo("<PRE>"); print_r($instances);  echo("</PRE>");

$products = &get_product_array($dsn, "any");

$servers = &get_server_array($dsn);

# If domain is not set we haven't picked an instance yet
if ( ! ( isset( $_GET['domain']) && isset( $_GET['product'] ) && isset( $_GET['server'] )  ) ) {

	print "Domain <select name=domain><option value=NONE>Please pick one</option>\n";
	while ($produc_name = current($instances)) {
		print "<option value=" . key($instances) . ">".key($instances)."</option>\n";
		next($instances);
	}

	print "</select><br>\n";
	print "Product <select name=product><option value=NONE>Please pick one</option>\n";
	for ( $i = 0 ; $i < sizeof($products) ; $i++ ) {
		print "<option value=" . $products[$i] . ">".$products[$i]."</option>\n";
	}
	print "</select><br>\n";
	print "Server <select name=server onchange='this.form.submit();'><option value=NONE>Please pick one</option>\n";
	for ( $i = 0 ; $i < sizeof($servers) ; $i++ ) {
		print "<option value=" . $servers[$i] . ">".$servers[$i]."</option>\n";
	}
	print "</select>\n";

	

} else {

	print "<INPUT TYPE=hidden name=domain VALUE='" . $_GET['domain'] . "'>\n";
	print "<INPUT TYPE=hidden name=product VALUE='" . $_GET['product'] . "'>\n";
	print "<INPUT TYPE=hidden name=server VALUE='" . $_GET['server'] . "'>\n";

	for ($i = 0; $i < sizeof($instances[$_GET['domain']]) ; $i++ ) {
		if ( $instances[$_GET['domain']][$i]["server_name"] == $_GET['server'] && 
				$instances[$_GET['domain']][$i]['product_name'] == $_GET['product'] ) {
			$product_directory = $instances[$_GET['domain']][$i]["directory"];
			break;
		}
	}

	print "You are looking for configuration for domain: <b>" . $_GET['domain'] . "</b> product: <b>" . $_GET['product']
		. "</b> server: <b> " . $_GET['server'] . "</b>, Directory: <b> " . $product_directory . "</b><p>";
		# Define all property files
		$properties_array = $properties[$_GET['product']];

	print "Configuration File <select name=conf_file onchange='this.form.submit();'><option value=NONE>Pick one</option>\n";
	for ( $i = 0 ; $i < sizeof($properties_array) ; $i++ ) {	
			print "<OPTION VALUE=" . $i . ">" . $properties_array[$i] . "</OPTION>\n";
	}
	print "</select>";
	
	if ( isset($_GET["conf_file"]) ) {

		$original_filename = $product_directory . "/" . $properties_array[$_GET["conf_file"]];
		
		print "<p>Currently viewing <b>" . $original_filename . "</b><p>\n";

		# Generate a temporary filename
		$tmpfname = tempnam("/tmp", "app_prop");

		# We need to copy the properties file since we may not have permission to read it
		# we'll use sudo
		@exec($GLOBALS['base'] . "/scripts/show_config_file.sh " . $_GET['server'] . " $original_filename $tmpfname");
		
		if ( filesize($tmpfname) == 0 ) {
			die ("Configuration file is empty. Likely it doesn't exist. Exiting ... ");
			unlink($tmpfname);
		}

		# Check if it is an XML file
		if ( strrpos($properties_array[$_GET["conf_file"]], ".xml") === false ) {

			# Read the properties file 
			$ini_arr = parse_ini_file_quotes_safe($tmpfname);
				
			print "<table class='sortable'>
			<thead>
			<tr>
				<th>Property</th>
				<th>Value</th>
			</tr>
			</thead>
			
			<tbody>";
	
			while (list($key, $value) = each($ini_arr)) {
			print "<tr><td>" . $key . "</td><td>" . $value . "</td></tr>\n";
			}
		
			print "</tbody>
			</table>";

		} else {

		        $fp2 = fopen($tmpfname, "r");

			$xml_file = "";
                        while ( !feof ($fp2) ) {

				$line = fgets ($fp2, 1024);
				$xml_file .= $line;

			}

			$xml_file = preg_replace('/^\s+$/mi', '', $xml_file);

			require_once 'Text/Highlighter.php';
			$highlighter =& Text_Highlighter::factory('xml');
			echo $highlighter->highlight($xml_file);

			fclose($fp2);


			
		}

		# Delete it when we're done
		unlink($tmpfname);

	}	
	
}
	
?>
</form>
</body>
</html>
