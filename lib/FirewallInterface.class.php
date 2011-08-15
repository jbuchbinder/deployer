<?php

interface FirewallInterface {

	public function up($fw, $proxy, $server);
	public function down($fw, $proxy, $server);
	public function status($fw, $proxy, $server);

}

?>
