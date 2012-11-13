<?xml version="1.0" encoding="UTF-8"?>

<!--
    Document   : metadata.xsl
    Created on : 5. November 2012, 12:08
    Author     : edouard
    Description:
        Purpose of transformation follows.
-->

<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:php="http://php.net/xsl"
                xmlns:dc="http://purl.org/dc/elements/1.1/"
                xmlns:xml="http://www.w3.org/XML/1998/namespace"
                exclude-result-prefixes="php">
   
           
   <xsl:template name="MetaData">
    </xsl:template>

   
   
    <!--  -->
    <!-- Templates for "internal fields". -->
    <!--  -->
    <xsl:template match="@CompletedYear|@ContributingCorporation|@CreatingCorporation|@Volume|@Issue|@Edition">
        <tr>
            <th class="name">
                <xsl:call-template name="translateFieldname" />
            </th>
            <td>
                <xsl:value-of select="." />
            </td>
        </tr>
    </xsl:template>
    
    <xsl:template match="@PageFirst|@PageLast|@PageNumber|@PublishedYear|@PublisherName|@PublisherPlace">
        <tr>
            <th class="name">
                <xsl:call-template name="translateFieldname" />
            </th>
            <td>
                <xsl:value-of select="." />
            </td>
        </tr>
    </xsl:template>   
    
    <xsl:template match="@Language|@Type">
        <tr>
            <th class="name">
                <xsl:call-template name="translateFieldname" />
            </th>
            <td>
                <xsl:call-template name="translateString">
                    <xsl:with-param name="string">
                        <xsl:value-of select="." />
                    </xsl:with-param>
                </xsl:call-template>	    
                
            </td>
        </tr>
    </xsl:template>    

    <!-- -->
    <!-- Templates for "external fields". -->
    <!-- -->
    <xsl:template match="Collection">
        <tr>
            <xsl:choose>
                <xsl:when test="position()=1">
                    <th class="name">
                        <xsl:call-template name="translateStringWithDefault">
                            <xsl:with-param name="string">default_collection_role_<xsl:value-of select="@RoleName" />
                            </xsl:with-param>
                            <xsl:with-param name="default">
                                <xsl:value-of select="@RoleName" />
                            </xsl:with-param>
                        </xsl:call-template>
                        <xsl:text>:</xsl:text>
                    </th>
                </xsl:when>
                <xsl:otherwise>
                    <th class="name"></th>
                </xsl:otherwise>
            </xsl:choose>
            <td>
                <xsl:call-template name="checkdisplay"/>
            </td>
        </tr>
    </xsl:template>

    <!-- Catch-all for deleted/invisible collections. -->
    <xsl:template match="Collection[@RoleVisibleFrontdoor='false']">
        <xsl:comment>
            <tr>
                <th class="name">
                    <xsl:value-of select="@Name" />
                    <xsl:text>:</xsl:text>
                </th>
                <td>
                    <xsl:text>(deleted) </xsl:text>
                    <xsl:call-template name="checkdisplay"/>
                </td>
            </tr>
        </xsl:comment>
        <xsl:text>
        </xsl:text>
    </xsl:template>

    <!-- Catch-all for hidden collections. -->
    <xsl:template match="Collection[@Visible='0']">
        <xsl:comment>
            <tr>
                <th class="name">
                    <xsl:value-of select="@Name" />
                    <xsl:text>:</xsl:text>
                </th>
                <td>
                    <xsl:text>(hidden) </xsl:text>
                    <xsl:call-template name="checkdisplay"/>
                </td>
            </tr>
        </xsl:comment>
        <xsl:text>
        </xsl:text>
    </xsl:template>

    <xsl:template match="CompletedDate|PublishedDate|ThesisDateAccepted">
        <tr>
            <th class="name">
                <xsl:call-template name="translateFieldname"/>
            </th>
            <td>
                <xsl:value-of select="concat(format-number(@Day,'00'),'.',format-number(@Month,'00'),'.',@Year)" />
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="Enrichment" mode="unescaped">
        <tr>
            <th class="name">
                <xsl:call-template name="translateString">
                    <xsl:with-param name="string">Enrichment<xsl:value-of select="@KeyName" />
                    </xsl:with-param>
                </xsl:call-template>
                <xsl:text>:</xsl:text>
            </th>
            <td>
                <xsl:value-of select="@Value" disable-output-escaping="yes"/>
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="Enrichment">
        <tr>
            <th class="name">
                <xsl:call-template name="translateString">
                    <xsl:with-param name="string">Enrichment<xsl:value-of select="@KeyName" />
                    </xsl:with-param>
                </xsl:call-template>
                <xsl:text>:</xsl:text>
            </th>
            <td>
                <xsl:value-of select="@Value" />
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="PersonAuthor|PersonReferee">
        <xsl:if test="position() = 1">
            <xsl:text disable-output-escaping="yes">&lt;tr&gt;</xsl:text>
            <th class="name">
                <xsl:if test="position() = 1">
                    <xsl:call-template name="translateFieldname"/>
                </xsl:if>
            </th>
            <xsl:text disable-output-escaping="yes">&lt;td&gt;</xsl:text>
        </xsl:if>
        <xsl:element name="a">
            <xsl:attribute name="href">
                <xsl:value-of select="$baseUrl"/>
                <xsl:if test="name()='PersonAuthor'">
                    <xsl:text>/solrsearch/index/search/searchtype/authorsearch/author/</xsl:text>
                </xsl:if>
                <xsl:if test="name()='PersonReferee'">
                    <xsl:text>/solrsearch/index/search/searchtype/authorsearch/referee/</xsl:text>
                </xsl:if>
                <xsl:value-of select="php:function('urlencode', concat(@FirstName, ' ', @LastName))" />
            </xsl:attribute>
            <xsl:attribute name="title">
                <xsl:if test="name()='PersonAuthor'">
                    <xsl:call-template name="translateString">
                        <xsl:with-param name="string">frontdoor_author_search</xsl:with-param>
                    </xsl:call-template>
                </xsl:if>
                <xsl:if test="name()='PersonReferee'">
                    <xsl:call-template name="translateString">
                        <xsl:with-param name="string">frontdoor_referee_search</xsl:with-param>
                    </xsl:call-template>
                </xsl:if>
            </xsl:attribute>
            <xsl:value-of select="concat(@FirstName, ' ', @LastName)" />
        </xsl:element>
        <xsl:if test="position() != last()">, </xsl:if>
        <xsl:if test="position() = last()">
            <xsl:text disable-output-escaping="yes">&lt;/td&gt;</xsl:text>
            <xsl:text disable-output-escaping="yes">&lt;/tr&gt;</xsl:text>
        </xsl:if>
    </xsl:template>
   

    <xsl:template match="PersonAdvisor|PersonOther|PersonContributor|PersonEditor|PersonTranslator">
        <xsl:if test="position() = 1">
            <xsl:text disable-output-escaping="yes">&lt;tr&gt;</xsl:text>
            <th class="name">
                <xsl:if test="position() = 1">
                    <xsl:call-template name="translateFieldname"/>
                </xsl:if>
            </th>
            <xsl:text disable-output-escaping="yes">&lt;td&gt;</xsl:text>
        </xsl:if>
        <xsl:value-of select="concat(@FirstName, ' ', @LastName)" />
        <xsl:if test="position() != last()">, </xsl:if>
        <xsl:if test="position() = last()">
            <xsl:text disable-output-escaping="yes">&lt;/td&gt;</xsl:text>
            <xsl:text disable-output-escaping="yes">&lt;/tr&gt;</xsl:text>
        </xsl:if>
    </xsl:template>

    <xsl:template match="IdentifierArxiv">
        <tr>
            <th class="name">
                <xsl:call-template name="translateFieldname"/>
            </th>
            <td>
                <xsl:element name="a">
                    <xsl:attribute name="href">
                        <xsl:text>http://arxiv.org/abs/</xsl:text>
                        <xsl:value-of select="@Value" />
                    </xsl:attribute>
                    <xsl:text>http://arxiv.org/abs/</xsl:text>
                    <xsl:value-of select="@Value" />
                </xsl:element>
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="IdentifierPubmed">
        <tr>
            <th class="name">
                <xsl:call-template name="translateFieldname"/>
            </th>
            <td>
                <xsl:element name="a">
                    <xsl:attribute name="href">
                        <xsl:text>http://www.ncbi.nlm.nih.gov/pubmed?term=</xsl:text>
                        <xsl:value-of select="@Value" />
                    </xsl:attribute>
                    <xsl:text>http://www.ncbi.nlm.nih.gov/pubmed?term=</xsl:text>
                    <xsl:value-of select="@Value" />
                </xsl:element>
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="IdentifierHandle|IdentifierUrl">
        <tr>
            <th class="name">
                <xsl:call-template name="translateFieldname"/>
            </th>
            <td>                
                <xsl:element name="a">
                    <xsl:choose>
                        <xsl:when test="contains(@Value, '://')">
                            <xsl:attribute name="href">
                                <xsl:value-of select="@Value" />
                            </xsl:attribute>
                            <xsl:value-of select="@Value" />
                        </xsl:when>
                        <xsl:otherwise>
                            <xsl:attribute name="href">
                                <xsl:text>http://</xsl:text>
                                <xsl:value-of select="@Value" />
                            </xsl:attribute>
                            <xsl:text>http://</xsl:text>
                            <xsl:value-of select="@Value" />
                        </xsl:otherwise>
                    </xsl:choose>
                </xsl:element>
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="IdentifierDoi|ReferenceDoi">
        <tr>
            <th class="name">
                <xsl:call-template name="translateFieldname"/>
            </th>
            <td>
                <xsl:element name="a">
                    <xsl:attribute name="href">
                        <xsl:text>http://dx.doi.org/</xsl:text>
                        <xsl:value-of select="@Value" />
                    </xsl:attribute>
                    <xsl:text>http://dx.doi.org/</xsl:text>
                    <xsl:value-of select="@Value" />
                </xsl:element>
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="IdentifierUrn|ReferenceUrn">
        <tr>
            <th class="name">
                <xsl:call-template name="translateFieldname"/>
            </th>
            <td>
                <xsl:element name="a">                
                    <xsl:attribute name="href">
                        <xsl:text>http://nbn-resolving.de/urn/resolver.pl?</xsl:text>
                        <xsl:value-of select="@Value" />
                    </xsl:attribute>
                    <xsl:value-of select="@Value" />
                </xsl:element>
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="IdentifierIsbn|IdentifierIssn|IdentifierSerial|ReferenceIsbn|ReferenceIssn|ReferenceHandle|TitleParent|TitleSub|TitleAdditional">
        <tr>
            <th class="name">
                <xsl:call-template name="translateFieldname"/>
            </th>
            <td>
                <xsl:value-of select="@Value" />
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="Series[@Visible=1]">
        <tr>
            <xsl:choose>
                <xsl:when test="position()=1">
                    <th class="name">
                        <xsl:call-template name="translateString">
                            <xsl:with-param name="string">Series</xsl:with-param>
                        </xsl:call-template>
                        <xsl:text> (</xsl:text>
                        <xsl:call-template name="translateString">
                            <xsl:with-param name="string">SeriesNumber</xsl:with-param>
                        </xsl:call-template>
                        <xsl:text>)</xsl:text>
                    </th>
                </xsl:when>
                <xsl:otherwise>
                    <th class="name"></th>
                </xsl:otherwise>
            </xsl:choose>
            <td>
                <a>
                    <xsl:attribute name="href">
                        <xsl:value-of select="$baseUrl"/>
                        <xsl:text>/solrsearch/index/search/searchtype/series/id/</xsl:text>
                        <xsl:value-of select="@Id" />
                    </xsl:attribute>

                    <xsl:attribute name="title">
                        <xsl:call-template name="translateString">
                            <xsl:with-param name="string">frontdoor_series_link</xsl:with-param>
                        </xsl:call-template>
                    </xsl:attribute>

                    <xsl:value-of select="@Title" />
                </a>
                <xsl:text> (</xsl:text>
                <xsl:value-of select="@Number" />
                <xsl:text>)</xsl:text>
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="Licence">
        <tr>
            <th class="name">
                <xsl:call-template name="translateFieldname"/>
            </th>
            <td>
                <img alt="License Logo">
                    <xsl:attribute name="src">
                        <xsl:value-of select="@LinkLogo"/>
                    </xsl:attribute>
                    <xsl:attribute name="title">
                        <xsl:value-of select="@LinkLicence"/>
                    </xsl:attribute>
                </img>

                <xsl:element name="a">
                    <xsl:attribute name="href">
                        <xsl:value-of select="$baseUrl"/><xsl:text>/default/license/index/licId/</xsl:text><xsl:value-of select="@Id"/>
                    </xsl:attribute>
                    <xsl:value-of select="@NameLong"/>
                </xsl:element>
            </td>
        </tr>
    </xsl:template>
      
    <xsl:template match="Note">
        <tr>
            <th class="name">
                <xsl:call-template name="translateFieldname"/>
            </th>
            <td>
               <pre class="preserve-spaces"><xsl:value-of select="@Message" /></pre>
            </td>
        </tr>
    </xsl:template>
 
    <xsl:template match="Patent"/>
 
    <xsl:template match="ReferenceUrl">
        <tr>
            <th class="name">
                <xsl:call-template name="translateFieldname"/>
            </th>
            <td>
                <xsl:element name="a">
                    <xsl:attribute name="href">
                        <xsl:value-of select="@Value" />
                    </xsl:attribute>
                    <xsl:attribute name="rel">
                        <xsl:text>nofollow</xsl:text>
                    </xsl:attribute>
                    <xsl:value-of select="@Label" />
                </xsl:element>
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="Subject">
        <xsl:if test="position() = 1">
            <xsl:text disable-output-escaping="yes">&lt;tr&gt;</xsl:text>
            <xsl:text disable-output-escaping="yes">&lt;th class="name"&gt;</xsl:text>
            <xsl:call-template name="translateString">
                <xsl:with-param name="string">subject_frontdoor_<xsl:value-of select="@Type" />
                </xsl:with-param>
            </xsl:call-template>
            <xsl:text>:</xsl:text>
            <xsl:text disable-output-escaping="yes">&lt;/th&gt;</xsl:text>
            <xsl:text disable-output-escaping="yes">&lt;td&gt;&lt;em class="data-marker"&gt;</xsl:text>
        </xsl:if>
        <xsl:value-of select="@Value" />
        <xsl:if test="position() != last()">; </xsl:if>
        <xsl:if test="position() = last()">
            <xsl:text disable-output-escaping="yes">&lt;/em&gt;&lt;/td&gt;</xsl:text>
            <xsl:text disable-output-escaping="yes">&lt;/tr&gt;</xsl:text>
        </xsl:if>
    </xsl:template>


    <xsl:template match="ThesisGrantor|ThesisPublisher">
        <tr>
            <th class="name">
                <xsl:call-template name="translateFieldname"/>
            </th>
            <td>
                <xsl:value-of select="@Name" />
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="IdentifierStdDoi"/>
    <xsl:template match="IdentifierCrisLink"/>
    <xsl:template match="IdentifierSplashUrl"/>
    <xsl:template match="ReferenceStdDoi"/>
    <xsl:template match="ReferenceCrisLink"/>
    <xsl:template match="ReferenceSplashUrl"/>

</xsl:stylesheet>