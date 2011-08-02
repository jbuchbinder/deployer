#!/bin/bash
#
#	$Id: action.sh 663 2008-05-30 17:07:51Z jbuchbinder $
#

PARAMS="$*"
VERSION='$Id: action.sh 663 2008-05-30 17:07:51Z jbuchbinder $'
function syntax(){
cat<<EOF
$(basename "$0") [${VERSION}]
-h|--help
	Give this syntax screen
-s|--server SERVER
	Specify the target server (example: qaweb1)
-d|--domain DOMAIN
	Target domain (example: production)
-p|--product PRODUCT
	Product name (example: base)
-a|--action ACTION
	start|stop|restart|version
-u|--user USER
	Run As User on the remote host

Was passed parameters: "$PARAMS"
EOF
}

SHORTOPTS="s:d:p:a:u:hv"
LONGOPTS="server:,user:,domain:,product:,action:,help,version"
OPTS=$(getopt -o "$SHORTOPTS" -l "$LONGOPTS" -n $(basename "$0") -- "$@" 2>&1)
if [ $? -ne 0 ]; then syntax; exit 1; fi

#----- Process arguments
eval set -- "$OPTS"

#----- Defaults
SERVER=""
DOMAIN=""
PRODUCT=""
ACTION=""
USER=""
while [ $# -gt 0 ]; do
	case $1 in
		-h|--help)
			syntax
			exit 0
			;;
		-v|--version)
			echo "$Id: action.sh 663 2008-05-30 17:07:51Z jbuchbinder $"
			exit 0
			;;
		-s|--server)
			SERVER="$2"
			shift 2
			;;
		-d|--domain)
			DOMAIN="$2"
			shift 2
			;;
		-p|--product)
			PRODUCT="$2"
			shift 2
			;;
		-u|--user)
			RUN_AS_USER="$2"
			shift 2
			;;
		-a|--action)
			ACTION="$2"
			case "$ACTION" in
				start|stop|restart) ;;
				*) syntax; exit 1 ;;
			esac
			shift 2
			;;
		--)
			shift
			;;
		*)
			echo "$0 Internal error: option processing error: $1" 1>&2
			exit 1
			;;
	esac
done

if [ "$SERVER" == "" ]; then syntax; exit 1; fi
if [ "$ACTION" == "" ]; then syntax; exit 1; fi
if [ "$RUN_AS_USER" == "" ]; then syntax; exit 1; fi

case "$ACTION" in

	start)
	cmd_to_run="${0%/*}/ssh-wrapper.sh $SERVER sudo -u $RUN_AS_USER -H sh -c 'nohup /run/$RUN_AS_USER/start.sh 2>&1' 2>&1"
	echo "DEBUG: $cmd_to_run"
	$cmd_to_run
	;;

	stop)
	cmd_to_run="${0%/*}/ssh-wrapper.sh $SERVER sudo -u $RUN_AS_USER -H sh -c '/run/$RUN_AS_USER/stop.sh'"
	echo "DEBUG: $cmd_to_run"
	$cmd_to_run
	;;

	version)
	echo "NOT IMPLEMENTED YET"
	;;

	restart)
	echo "Stopping the server"
	$0 --server $SERVER --product $PRODUCT -u $RUN_AS_USER --action stop
	sleep 1
	echo "Starting the server"
	$0 --server $SERVER --product $PRODUCT -u $RUN_AS_USER --action start
	;;

esac


