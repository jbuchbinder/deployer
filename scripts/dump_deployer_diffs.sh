#!/bin/bash
#
#	$Id: dump_deployer_diffs.sh 1148 2008-11-21 20:58:51Z jbuchbinder $
#

TABLES=(
	config_files
	config_templates
	deploy_files
	deploy_files_manifests
	products
)

mysqldump deployer ${TABLES[@]} > config.sql

