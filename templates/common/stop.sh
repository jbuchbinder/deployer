#!/bin/sh
#
#	$Id: stop.sh 1004 2008-09-18 14:27:46Z jbuchbinder $
#	Generic stop script
#

# Kills all of this user's processes

if [ $USER = "root" ]; then
        echo "Do not run this script as root. It will kill the machine. Exiting..."
        exit 1
fi

# How many 3 second tries should we attempt. 15 seconds should be enough ie. 3 * 5
TRIES=5

while [ `pgrep -u $USER java | wc -l` -gt 0 -a $TRIES -gt 0 ]
do
        echo "Attempting kill -15"
        kill -15 -1
        TRIES=$(( $TRIES - 1 ))
	echo "Sleeping 3 seconds"
        sleep 3
done

echo "Doing kill -9"
kill -9 -1

