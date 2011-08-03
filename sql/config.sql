-- MySQL dump 10.11
--
-- Host: localhost    Database: deployer
-- ------------------------------------------------------
-- Server version	5.0.45

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `config_files`
--

DROP TABLE IF EXISTS `config_files`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `config_files` (
  `product_id` int(10) unsigned default NULL,
  `config_file` varchar(150) default NULL,
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `config_templates`
--

DROP TABLE IF EXISTS `config_templates`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `config_templates` (
  `id` int(11) NOT NULL auto_increment,
  `deploy_file_id` int(11) NOT NULL,
  `name` varchar(255) default NULL,
  `template_path` varchar(250) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=22 DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `container_override`
--

DROP TABLE IF EXISTS `container_override`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `container_override` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `product_id` int(10) unsigned default NULL,
  `minimum_version` varchar(100) default NULL,
  `maximum_version` varchar(100) default NULL,
  `container_name` varchar(100) default NULL,
  `limit_domain_id` int(10) unsigned NOT NULL default '0',
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `containers`
--

DROP TABLE IF EXISTS `containers`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `containers` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `name` varchar(50) NOT NULL,
  `description` varchar(100) default NULL,
  `archive` varchar(100) default NULL,
  `extract_location` varchar(100) default NULL,
  `server_xml_location` varchar(100) default NULL,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `deploy_files`
--

DROP TABLE IF EXISTS `deploy_files`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `deploy_files` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `description` varchar(255) default NULL,
  `config_template_id` int(11) default NULL,
  `deploy_file_type_id` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=53 DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `deploy_file_types`
--

DROP TABLE IF EXISTS `deploy_file_types`;
CREATE TABLE `deploy_file_types` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `description` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `deploy_file_types`
--

/*!40000 ALTER TABLE `deploy_file_types` DISABLE KEYS */;
LOCK TABLES `deploy_file_types` WRITE; 
INSERT INTO `deploy_file_types` VALUES (3,'applicationarchive',NULL),(2,'appserver',''),(1,'name_value_configuration',NULL),(5,'other',NULL),(4,'shell_script',NULL);
UNLOCK TABLES; 
/*!40000 ALTER TABLE `deploy_file_types` ENABLE KEYS */;

--
-- Table structure for table `deploy_files_manifests`
--

DROP TABLE IF EXISTS `deploy_files_manifests`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `deploy_files_manifests` (
  `manifest_id` int(11) NOT NULL,
  `deploy_file_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  PRIMARY KEY  (`manifest_id`,`deploy_file_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `products` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `shortname` varchar(5) NOT NULL COMMENT 'Short Product name',
  `proxyname` varchar(255) default NULL,
  `apptype` varchar(255) default NULL,
  `start_port_offset` tinyint(2) unsigned NOT NULL,
  `protocol` enum('AJP','HTTP','NONE','JK') default 'AJP',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2011-06-09 18:08:53
