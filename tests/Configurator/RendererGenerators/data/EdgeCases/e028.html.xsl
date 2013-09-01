<?xml version="1.0" encoding="utf-8"?><xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"><xsl:output method="html" encoding="utf-8" indent="no"/><xsl:template match="p"><p><xsl:apply-templates/></p></xsl:template><xsl:template match="br"><br/></xsl:template><xsl:template match="et|i|st"/><xsl:template match="LIST"><xsl:choose><xsl:when test="not(@type)"><ul><xsl:apply-templates/></ul></xsl:when><xsl:when test="contains('upperlowerdecim',substring(@type,1,5))"><ol style="list-style-type:{@type}"><xsl:apply-templates/></ol></xsl:when><xsl:otherwise><ul style="list-style-type:{@type}"><xsl:apply-templates/></ul></xsl:otherwise></xsl:choose></xsl:template></xsl:stylesheet>