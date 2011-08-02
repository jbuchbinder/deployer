<?php

if ( isset ($_GET['host']) ) {

	$url="http://" . $_GET['host'] . "/admin/loadConfig.jsp";
	$data=NULL;
	$data=file_get_contents($url);
	print "<pre>";
	print $data;
	print "</pre>";
	

} else {
	echo "No host supplied";
}

?>
