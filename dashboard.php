<?php
//	$Id: dashboard.php 2567 2011-07-05 14:13:29Z rbortone $

$GLOBALS['base'] = dirname(__FILE__);

require_once($GLOBALS['base'] . '/config.php');
require_once($GLOBALS['base'] . '/lib/tools.php');
require_once( dirname(__FILE__)."/lib/Sajax.php" );

$__V = split(" ", "\$Revision: 2567 $");
$VERSION = trim( str_replace( '$', '', $__V[1] ) );

##################################################
# Load PEAR DB abstraction library
##################################################
require_once("DB.php");

################################################################
#  Connect to the database
#   Use persistent connections
################################################################
$db =& MDB2::factory($dsn);

if (DB::isError($db)) {
	print "Encountered db error!\n";
        die ($db->getMessage());
}

function get_log ( ) {
	global $db;

	$sql = "SELECT logtime, domain, server, product, user, message FROM log ORDER BY logtime DESC LIMIT 25";

	$buf = "<table class='logtable' border=\"0\"><tr class=log_tableheader><th>Log Time</th><th>Domain</th><th>Server</th><th>Product</th><th>User</th><th>Action</th></tr>";

	$log_messages = $db->queryAll($sql);

	for ( $i = 0 ; $i < sizeof($log_messages); $i++ ) {
		$buf .= "<tr class=log_tablerows><td>" . $log_messages[$i][0] . "</td><td>". $log_messages[$i][1] ."</td>" . "<td>". $log_messages[$i][2] . "</td>" . 
		"<td>". $log_messages[$i][3] . "</td>" .
		"<td>". $log_messages[$i][4] . "</td>" .
		"<td>". $log_messages[$i][5] . "</td>" . "</tr>"; 
	}

	$buf .= "</table>";

	return $buf;
}

function get_timestamp ( ) {
	return time();
}

function get_server_status ( $server_name, $product_name ) {
	$product_array = get_product_array($GLOBALS['dsn']);
	$server = resolve_server( $GLOBALS['dsn'], $server_name, $product_name );

	// Find out if the service is up or down
	if ( $product_name == "messaging" || $product_name == "msg-sms" || $product_name == "msg-email" ) {
		$about_jsp_url = "http://" . $server . ":8080" . $product_array[$product_name]['proxy_name'] . "/about.jsp";
	} else {
		# If there is : in the proxy_name it means we support multiple proxynames
		if (strpos($product_array[$product_name]['proxy_name'], ";") === false) {
			$proxy_name = $product_array[$product_name]['proxy_name'];
			$about_jsp_url = "http://" . $server . $proxy_name . "/about.jsp";
		} else {
			$proxies = explode( ';', $product_array[$product_name]['proxy_name'] );
			$about_jsp_url = array();
			foreach ($proxies AS $proxy) {
				$about_jsp_url[] = "http://" . $server . $proxy . "/about.jsp";
			}
		}
	}

	if (is_array( $about_jsp_url )) {
		foreach ( $about_jsp_url AS $url ) {
			$get_version = @file_get_contents($url);
			$get_version = str_replace('version: ', '', $get_version);
			if ( $get_version ) { return $get_version; }
		}
		return false;
	} else {
		$get_version = @file_get_contents($about_jsp_url);
		$get_version = str_replace('version: ', '', $get_version);
		if ( $get_version ) {
			return $get_version;
		} else {
			return false;
		}
	}
}

//starting SAJAX stuff
$sajax_request_type = "GET";
sajax_init();
sajax_export(
	  "get_server_status"
	, "get_log"
	, "get_timestamp"
);
sajax_handle_client_request();

?>
<html>

<head>
<meta http-equiv="Content-Language" content="en-us">
<meta HTTP-EQUIV="expires" CONTENT="Wed, 20 Feb 1990 08:30:00 GMT">
<meta HTTP-EQUIV="Pragma" CONTENT="no-cache">
<link rel="stylesheet" type="text/css" href="css/deployer.css" />
<title><?php print coloid() . "-" . web_hostname(); ?> Dashboard</title>

<?php print file_get_contents('lib/tabs.js'); ?>
</head>

<body id="body">

<?php if ( !isset($_GET['embed']) || $_GET['embed'] != 1) { ?>
<h1 id="banner">Dashboard [version <?php print $VERSION; ?>]</h1>

<!--
<ul class="buttons">
	<li><a onClick="load_all_status(); return true;" href="#">Refresh Server Status</a></li>
</ul>
-->

<ul id="deployertabs" class="shadetabs">
	<li><a href="#" rel="dashboard" class="selected">Dashboard</a></li>
	<li><a href="#" rel="firewall">Firewall</a></li>
	<li><a href="#" rel="editconfig">Edit Domain Config</a></li>
	<li><a href="#" rel="admin">Administration</a></li>
	<li><a href="#" rel="sync">Update Software</a></li>
</ul>

<div id="containerForTabs" style="border:1px solid gray; width: 98%; margin: 0; padding: 0;">

<div id="dashboard" class="tabcontent">
	<iframe id="dashboard_frame" src="dashboard.php?embed=1&stamp=<?php print mktime(); ?>" height="100%" width="100%"></iframe>
<?php
} else {
// Actual population
?>
<div>
<script language="javascript">
	<?php sajax_show_javascript(); ?>

	var timestamp;

	x_get_timestamp( function (x) { timestamp = x; } );

	// Enable button row function
	function eb( count ) {
		try {
			document.getElementById('button_stop_'+count).disabled = false;
			document.getElementById('button_deploy_'+count).disabled = false;
			document.getElementById('button_restart_'+count).disabled = false;
			document.getElementById('button_undeploy_'+count).disabled = false;
		} catch (x) { }
	}

	// Disable button row function
	function db( count ) {
		try {
			document.getElementById('button_stop_'+count).disabled = true;
			document.getElementById('button_deploy_'+count).disabled = true;
			document.getElementById('button_restart_'+count).disabled = true;
			document.getElementById('button_undeploy_'+count).disabled = true;
		} catch (x) { }
	}

	function act( count, widget, action, domain, run_as_user, product, server, version ) {
		if (confirm("Are you sure you want to "+action+" "+product+" on "+server+( version!=0 ? " (version "+version+")" : "" )+"?")) {
			var c = document.getElementById('deploy_container_'+count);
			var w = document.getElementById('deploy_'+count);
			w.scrolling = true;
			w.src = "web_actions.php?product="+product+"&run_as_user="+run_as_user+"&domain="+domain+"&server="+server+"&action="+action+"&version="+version+"&stamp="+timestamp;
			c.style.display = 'block';
		}
		setTimeout(function() { eb(count); }, 5000);
	}

	function view_url(count, url) {
		var c = document.getElementById('deploy_container_'+count);
		var w = document.getElementById('deploy_'+count);
		w.scrolling = true;
		w.src = url;
		c.style.display = 'block';
	}

</script>
<?php

$sql = "SELECT d.name AS domain_name, p.name AS product_name, s.name AS server_name, d.identifier AS domain_identifier 
	FROM installations AS i, systems AS s, domains AS d , products AS p 
	WHERE i.system_id = s.id 
	AND i.domain_id = d.id 
	AND i.product_id = p.id 
	ORDER BY domain_name, product_name, server_name";

$last_domain = "";

$product_array = get_product_array($dsn);

$version_array = get_product_version_array($dsn, $dist_dir);

# This is to keep track of background colors used
$j = 0;

$status = array();
if ( DB::isError( $recordset = $db->query( $sql ) ) ) {
    echo DB::errorMessage($recordset);
} else {
    $count = 0;
    while ($row = $recordset->fetchRow()) {
		$count++;

		$domain_name = $row[0];
		$product_name = $row[1];
		$server_name = $row[2];
		$identifier = $row[3];
		

		$app_user = $identifier . "_" . $product_name;

		$app_name = $identifier . "_" . $product_name;

		if ( $last_domain != $domain_name ) { 
			$last_domain = $domain_name;
			if ( $last_domain != "" ) 
				print "</table></div>";
				// Print out the Domain Name Row
				print "<div><table width=\"100%\" border=\"0\"><tr><td colspan=\"10\" bgcolor=\"orange\" align=\"center\"><b>" . $domain_name . " (" . $identifier .  ")</b></td></tr>";
			print "<tr bgcolor=\"#dddddd\"><th width=\"15%\">Product</th><th width=\"20%\">Server</th><th>Config</th><th>Version</th><th colspan=\"2\">Deploy</th><th>Stop</th><th>Restart</th><th>Undeploy</th></tr>";
		}

		#######################################################################
		# Check if we are using a color for a particular product. If not
		# set it from available background colors
		if ( !isset($bgcolor_array[$product_name]) ) {
			$bgcolor_array[$product_name] = $background_colors[$j];
			$j++;			
		}

		#######################################################################
		# Check whether this particular entry has had any changes within
		# Last 30 minutes
		#######################################################################
		$recent_activity_threshold = 30;
		$sql = "SELECT COUNT(logtime) FROM log WHERE logtime > (select subtime(now(), '0 00:" . $recent_activity_threshold . ":00')) AND server='" . $server_name . "' AND domain='" . $domain_name . "' AND product='" . $product_name . "'";

		$recent_activity = $db->queryOne($sql);

		if ( $recent_activity > 0 ) 
			$warning_image = "<img title=\"There was an action within last " . $recent_activity_threshold . " minutes please check log below for details\" alt=\"There was an action within last " . $recent_activity_threshold . " minutes please check log below for details\" width=\"20\" height=\"20\" src=\"img/exclamationgif.gif\" />";
		else
			$warning_image = "";

		print "<tr><td class=\"tableText\" bgcolor=\"" . $bgcolor_array[$product_name] . "\">" . $product_name . $warning_image . "</td><td class=\"tableText\" bgcolor=\"" . $bgcolor_array[$product_name] . "\">" . $server_name . "</td>";

		print " <td class=\"tableText\" bgcolor=\"" . $bgcolor_array[$product_name] . "\"><input type=\"button\" value=\"View Config\" onClick=\"view_url(${count}, 'appserver_config.php?server=" . $server_name  . "&product=" . $product_name . "&domain=" . $domain_name . "&embed=1'); return true;\" /></td>";

		$status[$count] = array ( $count, $server_name, $product_name );
		print "<td style=\"cursor: pointer;\" class=\"tableText\" align=\"center\" id=\"status_${count}\" onClick=\"document.getElementById('status_${count}').innerHTML = '-'; document.getElementById('status_${count}').style.backgroundColor = '#ffffff'; x_get_server_status( '${server_name}', '${product_name}', function(x) { if (x) { document.getElementById('status_${count}').innerHTML = x; document.getElementById('status_${count}').style.backgroundColor = '#90ee90'; } else { document.getElementById('status_${count}').innerHTML = 'DOWN'; document.getElementById('status_${count}').style.backgroundColor = '#ff0000'; } }); return true;\">-</td>";

		###############################################################
		# Display available versions
		print "<td align=\"center\"><select id=\"version_${count}\" name=\"version_to_deploy[$count]\">\n";
		$array_keys = array_keys($version_array[$product_name]);
		# Sort them in descending order
		arsort($array_keys);
		foreach ($array_keys as $key => $val) {
			print "\t<option value=\"". $version_array[$product_name][$val]['version'] ."\">" . $version_array[$product_name][$val]['version'] . "</option>\n";
		}
		print "</select></td><td><input type=\"button\" id=\"button_deploy_${count}\" value=\"Deploy/Reconfig\" onClick=\"db(${count}); act(${count}, this, 'deploy', '".$domain_name."','".$app_user."','".$product_name."', '".$server_name."', document.getElementById('version_${count}').options[document.getElementById('version_${count}').selectedIndex].value ); return true;\" /> </td>";

		###############################################################

		print "<td align=\"center\">";
		print "<input type=\"button\" value=\"Stop\" id=\"button_stop_${count}\" onClick=\"db(${count}); act(${count}, this, 'stop', '".$domain_name."','".$app_user."','".$product_name."', '".$server_name."', 0 ); return true;\" />\n";
		print "</td><td align=\"center\">";
		print "<input type=\"button\" value=\"Restart\" id=\"button_restart_${count}\" onClick=\"db(${count}); act(${count}, this, 'restart', '".$domain_name."','".$app_user."','".$product_name."', '".$server_name."', 0 ); return true;\" />\n";
		print "</td><td align=\"center\">";
		print "<input type=\"button\" value=\"Undeploy\" id=\"button_undeploy_${count}\" onClick=\"db(${count}); act(${count}, this, 'undeploy', '".$domain_name."','".$app_user."','".$product_name."', '".$server_name."', 0 ); return true;\" />\n";
		print "</td>
		</tr>\n";

		#### Create hidden div
		print "<tr><td colspan=\"10\"><div id=\"deploy_container_${count}\" style=\"display: none;\"><input type=\"button\" value=\"Close\" onClick=\"x_get_timestamp(function(x){ timestamp = x; }); document.getElementById('deploy_container_${count}').style.display = 'none'; document.getElementById('status_${count}').innerHTML = '-'; document.getElementById('deploy_${count}').src = 'about:blank'; document.getElementById('status_${count}').style.backgroundColor = '#ffffff'; x_get_server_status( '${server_name}', '${product_name}', function(x) { if (x) { document.getElementById('status_${count}').innerHTML = x; document.getElementById('status_${count}').style.backgroundColor = '#90ee90'; } else { document.getElementById('status_${count}').innerHTML = 'DOWN'; document.getElementById('status_${count}').style.backgroundColor = '#ff0000'; } }); x_get_log(function(x){ document.getElementById('log').innerHTML = x; }); return true;\" /><br/><iframe id=\"deploy_${count}\" width=\"80%\" height=\"600\" style=\"width: 100%;\"></iframe></div></td></tr>\n";
        }

}


print "</table></div><div>

<p>
Log of last 25 actions
</p>
<div id=\"log\">Please wait, loading ...</div>
";
?>

<script language="javascript">

function resizeDashboard() {
	var winW, winH;
	if (parseInt(navigator.appVersion)>3) {
		if (navigator.appName=="Netscape") {
			winW = parent.window.innerWidth;
			winH = parent.window.innerHeight;
		}
		if (navigator.appName.indexOf("Microsoft")!=-1) {
			winW = parent.document.body.offsetWidth;
			winH = parent.document.body.offsetHeight;
		}
	}

	var h =
		(
			winH -
			(
				  parent.document.getElementById('banner').offsetHeight 
				+ parent.document.getElementById('deployertabs').offsetHeight
				+ 10
			)
		);
	parent.document.getElementById('containerForTabs').style.height = h + 'px';
}

function load_all_status() {
// Load at the end ...
<?php
$count = 50;
foreach ($status AS $s) {
	print "\tdocument.getElementById('status_$s[0]').innerHTML = '-'; document.getElementById('status_$s[0]').style.backgroundColor = '#ffffff';\n";
	print "\tsetTimeout(function(){ x_get_server_status( '$s[1]', '$s[2]', function(x) { if (x) { document.getElementById('status_$s[0]').innerHTML = x; document.getElementById('status_$s[0]').style.backgroundColor = '#90ee90'; } else { document.getElementById('status_$s[0]').innerHTML = 'DOWN'; document.getElementById('status_$s[0]').style.backgroundColor = '#ff0000'; } }); }, $count);\n";
	$count += 50;
}
?>
	x_get_log(function(x){ document.getElementById('log').innerHTML = x; resizeDashboard(); });
}

load_all_status();

</script>
</div> <!-- id = dashboard -->
<?php } ?>
<?php if ( !isset($_GET['embed']) || $_GET['embed'] != 1) { ?>
</div>

	<div id="firewall" class="tabcontent" style="display: none;">
		<iframe src="firewall.php?embed=1" height="100%" width="100%" border="0"></iframe>
	</div>

	<div id="editconfig" class="tabcontent" style="display: none;">
		<iframe src="edit_config.php?embed=1" height="100%" width="100%" border="0"></iframe>
	</div>

	<div id="admin" class="tabcontent" style="display: none;">
		<iframe src="admin.php?embed=1&stamp=<?php print time(); ?>" height="100%" width="100%" border="0"></iframe>
	</div>

	<div id="sync" class="tabcontent" style="display: none;">
		<iframe src="repository_sync.php?embed=1&stamp=<?php print time(); ?>" height="100%" width="100%" border="0"></iframe>
	</div>

</div>

<script language="javascript">
	// Let's resize ...
	function resizeDashboard() {
		var winW, winH;
		if (parseInt(navigator.appVersion)>3) {
			if (navigator.appName=="Netscape") {
				winW = window.innerWidth;
				winH = window.innerHeight;
			}
			if (navigator.appName.indexOf("Microsoft")!=-1) {
				winW = document.body.offsetWidth;
				winH = document.body.offsetHeight;
			}
		}

		var h =
			(
				winH -
				(
					  document.getElementById('banner').offsetHeight 
					+ document.getElementById('deployertabs').offsetHeight
					+ 10
				)
			)
		//alert(h);
		document.getElementById('containerForTabs').style.height = h + 'px';
	}

	// Run this once ...
	resizeDashboard();
	// And connect to the resize event
	window.onresize = resizeDashboard;

	// Tabs
	var deployer=new ddtabcontent("deployertabs");
	deployer.setpersist(true);
	deployer.setselectedClassTarget("link");
	deployer.init();

</script>

<?php } /* end checking for get embed */ ?>

</body>
</html>
