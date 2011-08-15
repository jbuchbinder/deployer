<?php
// $Id$

include_once( dirname(__FILE__) . '/FirewallInterface.class.php' );

class Haproxy implements FirewallInterface {

        public function up($fw, $proxy, $server) {
		if (is_array($proxy)) {
			$out = "";
			foreach ($proxy AS $p) {
				$out .= $this->_call( $fw, "enable server ${p}/${server}" );
			}
			return $out;
		} else {
			return $this->_call( $fw, "enable server ${proxy}/${server}" );
		}
	} // end method up

        public function down($fw, $proxy, $server) {
		if (is_array($proxy)) {
			$out = "";
			foreach ($proxy AS $p) {
				$out .= $this->_call( $fw, "disable server ${p}/${server}" );
			}
			return $out;
		} else {
			return $this->_call( $fw, "disable server ${proxy}/${server}" );
		}
	} // end method down

        public function status($fw, $proxy, $server) {
		$p = is_array($proxy) ? $proxy[0] : $proxy;
		$data = $this->_call($fw, "show stat | grep '${server}' | cut -d, -f18 | head -1" );
		return (strpos($data, 'UP') !== false) ? 1 : 0;
	} // end method status

	protected function _call( $fw, $cmd ) {
		$habinary = dirname(dirname(__FILE__)) . '/scripts/haproxy_cmd.sh';
		$execcmd = "${habinary} ${fw} ${cmd}";
		return `$execcmd`;
	} // end method _call

} // end class Haproxy

?>
