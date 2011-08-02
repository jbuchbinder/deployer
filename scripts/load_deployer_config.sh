#!/bin/sh
#
#	$Id: load_deployer_config.sh 1142 2008-11-20 19:04:18Z jbuchbinder $
#

cat /var/www/html/deployer/sql/config.sql | mysql -uroot deployer

