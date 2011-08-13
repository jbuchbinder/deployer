<?php
//
//	$Id: edit_config.php 1810 2009-11-16 19:18:33Z jbuchbinder $
//

require_once( dirname(__FILE__)."/config.php" );
require_once( dirname(__FILE__)."/lib/tools.php" );
require_once( dirname(__FILE__)."/lib/JSON.php" );
require_once( dirname(__FILE__)."/lib/Sajax.php" );

$GLOBALS['dsn'] = $dsn;

//----- AJAX support functions -----

function jsValue($value) {
  switch(gettype($value)) {
    case 'double':
    case 'integer':
      return $value;
    case 'bool':
      return $value?'true':'false';
    case 'string':
      return '\''.addslashes($value).'\'';
    case 'NULL':
      return 'null';
    case 'object':
      return '\'Object '.addslashes(get_class($value)).'\'';
    case 'array':
      if (isVector($value))
        return '['.implode(',', array_map('jsValue', $value)).']';
      else {
        $result = '{';
        foreach ($value as $k=>$v) {
          if ($result != '{') $result .= ',';
          $result .= jsValue($k).':'.jsValue($v);
        }
        return $result.'}';
      }
    default:
      return '\''.addslashes(gettype($value)).'\'';
  }
}
function isVector (&$array) {
  $next = 0;
  foreach ($array as $k=>$v) {
    if ($k != $next)
      return false;
    $next++;
  }
  return true;
}

//----- AJAX functions go here -----

function commit_all_config ( $changes, $newVal, $removeVal, $domain, $product ) {
	// Get username to push into logs
	if ( isset( $_SERVER['REMOTE_USER'] ) ) {
		$user = $_SERVER['REMOTE_USER'];
	} else if ( isset($_SERVER["SSL_CLIENT_S_DN_Email"]) ) {
		$user = $_SERVER["SSL_CLIENT_S_DN_Email"];
	} else {
		$hostname = gethostbyaddr($_SERVER['REMOTE_ADDR']);
		$user = "Can't determine username :-(, IP=" . $hostname;
	}

	$json = new Services_JSON();
	$c = $json->decode( $changes );
	syslog(LOG_INFO, "[$user] DEPLOYER CONFIG CHANGES: $changes");
	add_config_change( $GLOBALS['dsn'], $user, $domain, $product, $changes );
	$r = commit_config_array( $GLOBALS['dsn'], $c, $domain, $product );
	
	$v = $json->decode( $removeVal );
	if (is_array($v) and count($v)>0) {
		syslog(LOG_INFO, "[$user] DEPLOYER CONFIG REMOVED: $removeVal");
		add_config_change( $GLOBALS['dsn'], $user, $domain, $product, $removeVal );
		$r = commit_remove_config_array( $GLOBALS['dsn'], $v, $domain, $product );
	}

	$v = $json->decode( $newVal );
	if (is_array($v) and count($v)>0) {
		syslog(LOG_INFO, "[$user] DEPLOYER CONFIG ADDED: $newVal");
		add_config_change( $GLOBALS['dsn'], $user, $domain, $product, $newVal );
		$r = commit_new_config_array( $GLOBALS['dsn'], $v, $domain, $product );
	}

	return true;
}

function get_config_files( $domain, $product ) {
	return jsValue( get_config_files_array( $GLOBALS['dsn'], $domain, $product ) );
}

function get_config_options( $domain, $product ) {
	return jsValue( get_config_options_array( $GLOBALS['dsn'], $domain, $product ) );
}

function get_products( $domain ) {
	$x = get_product_array($GLOBALS['dsn'], $domain);
	$ret = array();
	foreach ($x AS $k => $v) {
		$ret[$k] = $k;
	}
	return jsValue( $ret );
}

//starting SAJAX stuff
$sajax_request_type = "GET";
sajax_init();
sajax_export(
	  "get_config_options"
	, "get_config_files"
	, "get_products"
	, "commit_all_config"
	, "commit_changes"
	, "commit_new_config"
	, "commit_remove_config"
);
sajax_handle_client_request();

?>
<html>
<head>
        <title><?php print coloid() . "-" . web_hostname(); ?> Deployer :: Edit Configuration</title>
	<link rel="stylesheet" type="text/css" href="css/deployer.css"></link>
	<script language="javascript" src="jsscripts/json2.js"></script>
        <script language="javascript">
        <?php sajax_show_javascript(); ?>

	var loadedValues = new Array();
	var appendValues = 0;
	var deleteValues = new Array();
	var cfOptions = new Array();
	var cfMap = new Array();

	function addConfigOption( ) {
		appendValues++;
		var tb = document.getElementById('tableBody');
		var tR = document.createElement('tr');
		var tD1 = document.createElement('td');
		var tD2 = document.createElement('td');
		var tD3 = document.createElement('td');
		var cf = document.createElement('select');
			cf.id = 'add_config_file_' + appendValues;
			cf.name = 'add_config_file_' + appendValues;
			tD1.appendChild(cf);
		var k = document.createElement('input');
			k.id = 'add_key_' + appendValues;
			k.type = 'text';
			k.size = 40;
			tD2.appendChild( k );
		var v = document.createElement('input');
			v.id = 'add_value_' + appendValues;
			v.type = 'text';
			v.size = 40;
			tD3.appendChild( v );
		tR.appendChild( tD1 );
		tR.appendChild( tD2 );
		tR.appendChild( tD3 );
		tb.appendChild( tR );
		
		var count = 0;
		var e = document.getElementById('add_config_file_' + appendValues);
		for (var i in cfOptions) {
			try {
				e.add(new Option( cfOptions[i], cfOptions[i], false ), null );
			} catch (ex) {
				e.add(new Option( cfOptions[i], cfOptions[i], false ) );
			}
		}
		//if (typeof console !=  "undefined") { console.debug ('selected index = ' + e.selectedIndex); }
	}

	function onCommitChanges( response ) {
		eval('var x = '+response+';');
		if (x) {
			alert('Changes committed successfully.');
			refreshConfigOptions();
		} else {
			alert('ERROR: ' + x);
		}
	}

	function onRemoveConfigClick ( wId ) {
		var id = wId.replace('remove_config_', '');
		deleteValues.push( parseInt( id ) );
		document.getElementById( 'row_' + id ).style.display = 'none';
	}

	function onConfigFileLoad( options ) {
		eval('var x = '+options+';');
		var tb = document.getElementById('tableBody');
		tb.innerHTML = "";
		var count = 1;

		// Reset globals
		loadedValues = new Array();
		appendValues = 0;
		deleteValues = new Array();

		for (var i in x) {
			var tR = document.createElement('tr');
			tR.id = 'row_' + x[i].id;
			var bg = '';

			if ( count % 2 == 1 ) {
				bg = 'row';
			} else {
				bg = 'row_alternate';
			}
			tR.className = bg;

			var tD1 = document.createElement('td');
				var hCF = document.createElement('input');
				hCF.className = bg;
				hCF.type = 'text';
				hCF.disabled = true;
				hCF.id = 'config_file_' + x[i].id;
				hCF.value = x[i].config_file;
				tD1.appendChild(hCF);
				tR.appendChild(tD1);
			var tD2 = document.createElement('td');
			 	tD2.innerHTML = x[i].name;
				tD2.className = bg;
				tR.appendChild(tD2);
			var tD3 = document.createElement('td');
				var tI = document.createElement('input');
					tI.id = 'value_' + x[i].id;
					tI.type = 'text';
					tI.value = x[i].value;
					tI.size = 40;
				var tH = document.createElement('input');
					tH.id = 'value_orig_' + x[i].id;
					tH.type = 'hidden';
					tH.value = x[i].value;
				tD3.appendChild(tI);
				tD3.appendChild(tH);
				tD3.className = bg;
				tR.appendChild(tD3);
			var tD4 = document.createElement('td');
				var tI = document.createElement('img');
				tI.id = 'remove_config_' + x[i].id;
				tI.src = 'css/del.png';
				tI.onclick = function() { onRemoveConfigClick(this.id); };
				tD4.appendChild(tI);
				tR.appendChild(tD4);

			loadedValues.push(x[i].id);

			tb.appendChild( tR );
			count++;
		}
		document.getElementById('table').style.display = 'block';
		document.getElementById('addButton').style.display = 'block';
		document.getElementById('submitButton').style.display = 'block';
	}

	function onConfigFilesLoad( options ) {
		eval('var x = '+options+';');
		//if (typeof console !=  "undefined") { console.debug (options); }
		cfOptions = new Array();
		for (var i in x) {
			cfOptions.push( x[i].config_file );
			cfMap[ x[i].config_file ] = x[i].config_file_id;
		}
	}

	function commitChanges( ) {
		var changes = new Array();
		for (var x in loadedValues) {
			var i = loadedValues[x];
			var cur = document.getElementById( 'value_' + i ).value;
			var orig = document.getElementById( 'value_orig_' + i ).value;
			var df = document.getElementById( 'config_file_' + i ).value;
			if (cur != orig) {
				changes.push(new Array(i, cur, df));
			}
		}

		var nv = new Array();
		if ( appendValues > 0 ) {	
			for (var i=0; i<appendValues; i++) {
				var k = i + 1;
				nv.push( new Array(
					  document.getElementById('add_key_' + k).value
					, document.getElementById('add_value_' + k).value
					, cfMap[ document.getElementById('add_config_file_' + k).value ]
				) );
			}
		} else {
			nv = '';
		}

		//if (typeof console !=  "undefined") { console.debug (JSON.stringify(nv) + "\nMAP = " + JSON.stringify(cfMap)); }
		x_commit_all_config(JSON.stringify(changes), JSON.stringify(nv), JSON.stringify(deleteValues),  document.getElementById('domains').options[document.getElementById('domains').selectedIndex].value, document.getElementById('products').options[document.getElementById('products').selectedIndex].value, onCommitChanges);
	}

	function onProductLoad( products ) {
		eval('var x = '+products+';');
		var o = document.getElementById('products');
		var count = 1;
		for (var i in x) {
			o.options[count] = new Option( x[i], x[i], false );
			count++;
		}
	}

	function refreshConfigOptions() {
		x_get_config_options(document.getElementById('domains').options[document.getElementById('domains').selectedIndex].value, document.getElementById('products').options[document.getElementById('products').selectedIndex].value, onConfigFileLoad);
		x_get_config_files(document.getElementById('domains').options[document.getElementById('domains').selectedIndex].value, document.getElementById('products').options[document.getElementById('products').selectedIndex].value, onConfigFilesLoad);
	}

	function refreshProducts() {
		x_get_products(document.getElementById('domains').options[document.getElementById('domains').selectedIndex].value, onProductLoad);
	}

        </script>
</head>
<body>
<?php if (!$_GET['embed']) { ?>
<h1>Edit Configuration</h1>
<ul class="buttons">
	<li><a href="repository_sync.php">Update software from repository</a></li>
	<li><a href="dashboard.php">Dashboard</a></li>
</ul>
<?php } ?>

<div>
	<i>
	NOTE: Changes are not finalized until you click the "Submit" button.
	</i>
</div>

<span id="domainSpan">
	Select Domain:
	<select id="domains" onChange="refreshProducts(); return true;">
		<option value="">----</option>
		<?php
			$domains = get_domain_array( $GLOBALS['dsn'] );
			foreach ($domains AS $id => $name ) {
				print "\t\t<option value=\"$name\">$name</option>\n";
			}
		?>
	</select>
</span>

<span id="productSpan">
	Product:
	<select id="products" onChange="refreshConfigOptions(); return true;">
		<option value="" selected="selected">----</option>
	</select>
</span>

<table id="table" style="display: none;">
	<thead>
		<th>Config File</th>
		<th>Key</th>
		<th>Value</th>
		<!-- <th>Action</th> -->
	</thead>
	<tbody id="tableBody">
	</tbody>
</table>

<input type="button" id="addButton" onClick="addConfigOption(); return true;" value="Add Config Setting" style="display: none;" />

<br/>

<input type="button" id="submitButton" onClick="commitChanges(); return true;" value="Submit" style="display:none;" />

</body>
</html>
