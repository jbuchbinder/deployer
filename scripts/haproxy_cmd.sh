#!/bin/bash
#
#	$Id$
#

HAPROXY_SERVER="$1"; shift

if [ "${HAPROXY_SERVER}" == "" ]; then
	echo "FAIL: no server specified"
	exit
fi

KDIR="$( cd "$(dirname "$0")" ; cd ../keys ; pwd )"

case "${HAPROXY_SERVER}" in
	*:*)
		S=$( echo "${HAPROXY_SERVER}" | cut -d: -f1 )
		P=$( echo "${HAPROXY_SERVER}" | cut -d: -f2 )
		;;
	*)
		S=${HAPROXY_SERVER}
		P=22
		;;
esac

echo "$*" | ssh -p${P} -i "${KDIR}/id_rsa" "${S}" socat unix-connect:/var/run/haproxy.stats stdio

