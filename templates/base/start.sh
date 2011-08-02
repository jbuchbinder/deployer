#!/bin/sh
#
#	$Id: start.sh 2454 2011-04-18 21:25:35Z jbuchbinder $
#	Base start script
#

# This script starts Base
BASE_HOME=${0%/*}
LOGDIR="/logs"

echo "Using JDK 1.6"
export JAVA_HOME=/usr/java/latest

export PATH=$JAVA_HOME/bin:$PATH

# Create a soft link to /logs/username
if [ ! -L $BASE_HOME/logs ]; then
	echo "Creating /logs/$USERNAME link"
	rm -rf $BASE_HOME/logs
	ln -s $LOGDIR/$USER $BASE_HOME/logs
fi

# Source any necessary environment variables from a deployed file called
# 'env.sh'.
if [ -e $BASE_HOME/env.sh ]; then
	source $BASE_HOME/env.sh
fi

# If BASE_ADMIN_HOST variable not set set it to "NONE
BASE_ADMIN_HOST=${BASE_ADMIN_HOST:=NONE}

echo "Admin Host is ${BASE_ADMIN_HOST}"

# Make sure we get the full hostname
HOSTNAME=`hostname -f`

# Same for BASE_JAVA_OPTS
export BASE_JAVA_OPTS=${BASE_JAVA_OPTS:=-Xmx256m -XX:MaxPermSize=128m}

# If we are running on admins server set tomcat.conf values to true
if [ $HOSTNAME = $BASE_ADMIN_HOST -o $BASE_ADMIN_HOST = "NONE" ]; then
	# Set true for tomcat.conf
	perl -pi -e 's/false/true/g' $BASE_HOME/conf/tomcat.conf
fi

############################################################################
### DB Patching
############################################################################

if [ $HOSTNAME = $BASE_ADMIN_HOST -o $BASE_ADMIN_HOST = "NONE" ]; then
	echo "Perform admin tasks here"
fi

echo -n "Extracting webapps/ROOT ... "
(
	mkdir -p $BASE_HOME/webapps/ROOT ;
	cd $BASE_HOME/webapps/ROOT ;
	jar xf ../ROOT.war
)
echo "done"

echo -n "Importing MySQL JDBC connector into Tomcat context ... "
JDBCJAR=$( cd $BASE_HOME ; find $BASE_HOME/webapps/ROOT | grep 'mysql-connector-java' | head -1 )
echo $( basename "$JDBCJAR" )
if [ -d $BASE_HOME/common/lib ]; then
	cp $JDBCJAR $BASE_HOME/common/lib/ -vf
else
	cp $JDBCJAR $BASE_HOME/lib/ -vf
fi

echo "Adjusting temp directory perms for apache"
chgrp apache temp -vf
chmod g+s temp -vf
rm temp/* -rf 2>&1 >> /dev/null

#########################################################################
# Start up Tomcat
#########################################################################
JAVA_OPTS="$BASE_JAVA_OPTS" $BASE_HOME/bin/catalina.sh start

# Resources for servlets/Struts actions
export CLASSPATH=$CLASSPATH:$BASE_HOME/webapps/ROOT/WEB-INF/resources

if [ $HOSTNAME = $BASE_ADMIN_HOST -o $BASE_ADMIN_HOST = "NONE" ]; then
	echo "Running singleton tasks on admin host"
fi

