#!/bin/bash
#
#	$Id: ssh-wrapper.sh 663 2008-05-30 17:07:51Z jbuchbinder $
#

if [ $# -lt 2 ]; then
	echo "syntax: $(basename "$0") server command"
	exit 1
fi

SERVER=$1
shift
PARAMS=$*

logger -t "$(basename "$0")" "ssh -Antt -o StrictHostKeyChecking=no  root@${SERVER} \"${PARAMS} 2>&1\" 2>&1"

ssh \
	-Antt \
	-o StrictHostKeyChecking=no \
	root@${SERVER} \
	"${PARAMS}" 2>&1

