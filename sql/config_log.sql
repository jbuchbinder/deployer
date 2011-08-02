#
#	$Id: config_log.sql 1810 2009-11-16 19:18:33Z jbuchbinder $
#

CREATE TABLE config_log (
	  id		SERIAL
	, stamp		TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
	, user		VARCHAR (50)
	, domain_id	INT UNSIGNED NOT NULL
	, product_id	INT UNSIGNED NOT NULL
	, changehash	TEXT

	, KEY		( domain_id, product_id, stamp )
);

