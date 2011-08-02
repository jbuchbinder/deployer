#
#	$Id: containers.sql 2454 2011-04-18 21:25:35Z jbuchbinder $
#

DROP TABLE IF EXISTS containers;

CREATE TABLE containers (
	  id			SERIAL
	, name			VARCHAR (50) NOT NULL UNIQUE
	, description		VARCHAR (100)
	, archive		VARCHAR (100)
	, extract_location	VARCHAR (100)
	, server_xml_location	VARCHAR (100)
);

INSERT INTO containers VALUES ( 1, "tomcat", "Tomcat", "apache-tomcat-5.5.26.tar.gz", "webapps", "conf/server.xml" );
INSERT INTO containers VALUES ( 2, "jboss", "JBoss 4.3", "jboss-4.3.tgz", "server/production", "server/production/deploy/jboss-web.deployer" );
INSERT INTO containers VALUES ( 3, "jboss42", "JBoss 4.2", "jboss-4.2.2.tgz", "server/all", "server/all/deploy/jboss-web.deployer" );
INSERT INTO containers VALUES ( 4, "jboss43-hibernate3", "JBoss 4.3 + Hibernate 3", "jboss-4.3.hibernate3.tgz", "server/production", "server/production/deploy/jboss-web.deployer/server.xml" );
INSERT INTO containers VALUES ( 5, "tomcat6", "Tomcat 6.x", "apache-tomcat-6.0.29.tar.gz", "webapps", "conf/server.xml" );
INSERT INTO containers VALUES ( 6, "tomcat7", "Tomcat 7.x", "apache-tomcat-7.0.12.tar.gz", "webapps", "conf/server.xml" );
INSERT INTO containers VALUES ( 7, "jboss6", "JBoss 6.0.0", "jboss-6.0.0.tar.gz", "server/all", "server/all/deploy/jbossweb.sar/server.xml" );

