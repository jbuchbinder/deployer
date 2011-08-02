#
#	$Id: compare_versions.sql 1003 2008-09-17 20:01:18Z jbuchbinder $
#	Custom function to compare three part versions
#		(MAJOR.MINOR.MICRO)
#	Returns -1 if comp is less than orig
#	Returns 0 if comp is equal to orig
#	Returns 1 if comp is greater than orig
#

DROP FUNCTION IF EXISTS COMPARE_VERSIONS;

DELIMITER //
CREATE FUNCTION COMPARE_VERSIONS (
		  orig		VARCHAR (100)
		, comp		VARCHAR (100)
	)
	RETURNS INT
	DETERMINISTIC
	CONTAINS SQL
	BEGIN
		DECLARE o_a INT UNSIGNED;
		DECLARE o_b INT UNSIGNED;
		DECLARE o_c INT UNSIGNED DEFAULT 0;
		DECLARE c_a INT UNSIGNED;
		DECLARE c_b INT UNSIGNED;
		DECLARE c_c INT UNSIGNED DEFAULT 0;

		IF ISNULL( orig ) OR ISNULL( comp ) THEN
			RETURN -2;
		END IF;

		# Split
		SELECT LEFT(orig, LOCATE('.', orig) - 1) INTO o_a;
		SELECT MID(orig, LOCATE('.', orig) + 1, (LOCATE('.', orig, LOCATE('.', orig) + 1) - LOCATE('.', orig)) - 1) INTO o_b;
		SELECT RIGHT(orig, LENGTH(orig) - LOCATE('.', orig, LOCATE('.', orig) + 1)) INTO o_c;

		SELECT LEFT(comp, LOCATE('.', comp) - 1) INTO c_a;
		SELECT MID(comp, LOCATE('.', comp) + 1, (LOCATE('.', comp, LOCATE('.', comp) + 1) - LOCATE('.', comp)) - 1) INTO c_b;
		SELECT RIGHT(comp, LENGTH(comp) - LOCATE('.', comp, LOCATE('.', comp) + 1)) INTO c_c;

		# Major version
		IF c_a < o_a THEN
			RETURN -1;
		END IF;

		IF c_a > o_a THEN
			RETURN 1;
		END IF;

		IF c_b < o_b THEN
			RETURN -1;
		END IF;

		IF c_b > o_b THEN
			RETURN 1;
		END IF;

		IF c_c < o_c THEN
			RETURN -1;
		END IF;

		IF c_c > o_c THEN
			RETURN 1;
		END IF;

		# All else falls through, equal
		RETURN 0;
	END//
DELIMITER ;

#----- Make sure privileges are granted, otherwise miserable failure  -----
GRANT EXECUTE ON deployer.* TO webread@localhost;
FLUSH PRIVILEGES;

