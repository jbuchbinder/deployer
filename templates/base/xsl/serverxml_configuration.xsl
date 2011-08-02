<?xml version="1.0"?>
<!--

	$Id: serverxml_configuration.xsl 2535 2011-06-13 16:49:56Z jbuchbinder $

 -->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">

  <xsl:output method="xml" indent="yes"/> 

  <!-- Passed parameters from deployer -->

  <xsl:param name="serverName" />
  <xsl:param name="domain" />
  <xsl:param name="container" />

  <!-- Set jdbc authority -->

  <xsl:template match="GlobalNamingResources">
    <GlobalNamingResources>
      <xsl:choose>
        <xsl:when test="$domain = 'Production'">
          <xsl:comment>Production</xsl:comment>
        </xsl:when>
        <xsl:otherwise>
          <xsl:comment>Domain <xsl:value-of select="$domain" /> not supported</xsl:comment>
        </xsl:otherwise>
      </xsl:choose>
      <!-- Allow conf/tomcat-users.xml file for manager app -->
      <Resource name="UserDatabase" auth="Container" type="org.apache.catalina.UserDatabase" description="User database that can be updated and saved" factory="org.apache.catalina.users.MemoryUserDatabaseFactory" pathname="conf/tomcat-users.xml"/>
      <xsl:apply-templates />
    </GlobalNamingResources>
  </xsl:template>

  <xsl:template match="Engine">
    <Engine name="Catalina" defaultHost="localhost">
      <!-- Insert data source realm -->
      <Realm className="org.apache.catalina.realm.DataSourceRealm" debug="0"
       dataSourceName="jdbc/authority"
       userTable="tWebappUsers" userNameCol="userName" userCredCol="passwordTxt"
       userRoleTable="tWebappRoles" roleNameCol="roleName"/>
      <!-- Manager app realm -->
      <Realm className="org.apache.catalina.realm.UserDatabaseRealm"
       debug="1" resourceName="UserDatabase" />

      <!-- Pass through host entries but not other Realm elements -->
      <xsl:apply-templates select="Host" />
    </Engine>
  </xsl:template>

  <xsl:template match="*[local-name()='Host']">
    <xsl:element name="Host">
      <xsl:copy-of select="@*" /> 
      <!-- Host customizations here -->
      <xsl:apply-templates />
    </xsl:element> 
  </xsl:template> 

  <!-- Up maximum thread count -->

  <xsl:template match="*[local-name()='Connector']">
    <xsl:element name="Connector">
      <xsl:copy-of select="@*" /> 
      <!-- Define overrides after copy from source, otherwise they won't overwrite the original -->
      <xsl:attribute name="maxThreads">500</xsl:attribute>
      <xsl:apply-templates />
    </xsl:element> 
  </xsl:template> 

  <!-- Global fall through, required to not destroy existing XML -->

  <xsl:template match="@*|node()">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
    </xsl:copy>
  </xsl:template>
 
</xsl:stylesheet>

