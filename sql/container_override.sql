#
#	$Id: container_override.sql 2409 2011-03-16 15:13:07Z jbuchbinder $
#

DROP TABLE container_override;

CREATE TABLE IF NOT EXISTS container_override (
	  id			SERIAL
	, product_id		INT UNSIGNED
	, minimum_version	VARCHAR (100)
	, maximum_version	VARCHAR (100)
	, container_name	VARCHAR (100)
	, limit_domain_id	INT UNSIGNED NOT NULL DEFAULT 0
);

#---- Messaging < 1.5.0 requires JBoss 4.2
INSERT INTO container_override VALUES ( 1, ( SELECT p.id FROM products p WHERE p.name='messaging' ), '0.0.0', '1.4.99', 'jboss42', 0 );

#---- Stored procedure and functions for determining overrides

DROP PROCEDURE IF EXISTS productContainerProcedure;
DROP FUNCTION IF EXISTS productContainer;

DELIMITER //

CREATE PROCEDURE productContainerProcedure( IN pProduct CHAR(50), IN pVersion CHAR(50), IN pDomain CHAR(50), OUT returnValue CHAR(50) )
BEGIN
	DECLARE done BOOL DEFAULT FALSE;
	DECLARE container CHAR(50);
	DECLARE cur CURSOR for
		SELECT o.container_name
			FROM container_override o
			LEFT OUTER JOIN products p ON o.product_id = p.id
			LEFT OUTER JOIN domains d ON o.limit_domain_id = d.id
		WHERE
		    p.name = pProduct
		AND ( COMPARE_VERSIONS( o.minimum_version, pVersion ) >= 0 )
		AND ( COMPARE_VERSIONS( pVersion, o.maximum_version ) >= 0 )
		AND ( o.limit_domain_id = 0 OR d.name = pDomain )
	;
	DECLARE CONTINUE HANDLER FOR SQLSTATE '02000' SET done = TRUE;

	OPEN cur;
	WHILE NOT done DO
		FETCH cur INTO container;
	END WHILE;
	CLOSE cur;

	IF ISNULL(container) THEN
		SELECT apptype INTO container FROM products WHERE name = pProduct;
	END IF;

	SET returnValue := container;
END//

CREATE FUNCTION productContainer( pProduct CHAR(50), pVersion CHAR(20), pDomain CHAR(50) )
	RETURNS CHAR(50)
	DETERMINISTIC READS SQL DATA
BEGIN
	CALL productContainerProcedure( pProduct, pVersion, pDomain, @out );
	RETURN @out;
END//

DELIMITER ;

