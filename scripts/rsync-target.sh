#!/bin/bash
#
#	$Id: rsync-target.sh 635 2008-05-28 01:20:56Z vuksan $
#

SERVER=$1
PRODUCT=$2
SOURCE=$3

if [ $# -ne 3 ]; then
	echo "syntax: $(basename "$0") server product/userid sourcedir"
	exit 1
fi

rsync \
	--archive \
	--delete \
	-e ssh \
	${SOURCE}/ \
	root@${SERVER}:/run/${PRODUCT}/

