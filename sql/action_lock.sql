#
#	$Id: action_lock.sql 1152 2008-11-24 20:03:26Z jbuchbinder $
#

CREATE TABLE IF NOT EXISTS action_lock (
	  id			SERIAL
	, action		VARCHAR (100)
	, stamp			BIGINT UNSIGNED
	, INDEX			( action, stamp )
);

TRUNCATE action_lock;
