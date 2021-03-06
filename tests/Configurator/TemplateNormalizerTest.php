<?php

namespace s9e\TextFormatter\Tests\Configurator;

use DOMElement;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateNormalization;
use s9e\TextFormatter\Configurator\TemplateNormalizer;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizer
*/
class TemplateNormalizerTest extends Test
{
	/**
	* @testdox Implements ArrayAccess
	*/
	public function testImplementsArrayAccess()
	{
		$this->assertInstanceOf('ArrayAccess', new TemplateNormalizer);
	}

	/**
	* @testdox Implements Iterator
	*/
	public function testImplementsIterator()
	{
		$this->assertInstanceOf('Iterator', new TemplateNormalizer);
	}

	/**
	* @testdox Only executes a normalization once per run if its "onlyOnce" property is true
	*/
	public function testOnlyOnce()
	{
		$templateNormalizer = new TemplateNormalizer;
		$templateNormalizer->append(new DummyNormalization('?'));
		$templateNormalizer->append(new DummyNormalization('!'))->onlyOnce = true;

		$this->assertSame(
			'Hi?!????',
			$templateNormalizer->normalizeTemplate('Hi')
		);
	}

	/**
	* @testdox normalizeTag() calls each of the tag's template's normalize() method with itself as argument
	*/
	public function testNormalizeTag()
	{
		$templateNormalizer = new TemplateNormalizer;

		$mock = $this->getMockBuilder('s9e\\TextFormatter\\Configurator\\Items\\Template')
		             ->disableOriginalConstructor()
		             ->getMock();

		$mock->expects($this->any())
		     ->method('__toString')
		     ->will($this->returnValue('<br/>'));

		$mock->expects($this->any())
		     ->method('isNormalized')
		     ->will($this->returnValue(false));

		$mock->expects($this->once())
		     ->method('normalize')
		     ->with($templateNormalizer);

		$tag = new Tag;
		$tag->template = $mock;

		$templateNormalizer->normalizeTag($tag);
	}

	/**
	* @testdox normalizeTag() does not call normalize() if the template was already normalized
	*/
	public function testNormalizeTagUnlessNormalized()
	{
		$templateNormalizer = new TemplateNormalizer;

		$mock = $this->getMockBuilder('s9e\\TextFormatter\\Configurator\\Items\\Template')
		             ->disableOriginalConstructor()
		             ->getMock();

		$mock->expects($this->any())
		     ->method('__toString')
		     ->will($this->returnValue('<br/>'));

		$mock->expects($this->any())
		     ->method('isNormalized')
		     ->will($this->returnValue(true));

		$mock->expects($this->never())
		     ->method('normalize');

		$tag = new Tag;
		$tag->template = $mock;

		$templateNormalizer->normalizeTag($tag);
	}

	/**
	* @testdox Default normalization rules
	* @dataProvider getDefault
	*/
	public function testDefault($template, $expected)
	{
		$templateNormalizer = new TemplateNormalizer;

		$this->assertSame($expected, $templateNormalizer->normalizeTemplate($template));
	}

	public function getDefault()
	{
		return [
			[
				// Superfluous whitespace inside tags is removed
				'<div id = "foo" ><xsl:apply-templates /></div >',
				'<div id="foo"><xsl:apply-templates/></div>'
			],
			[
				// <xsl:element><xsl:attribute> is inlined
				'<xsl:element name="hr"><xsl:attribute name="id">foo</xsl:attribute></xsl:element>',
				'<hr id="foo"/>'
			],
			[
				'<b><![CDATA[ ]]></b><![CDATA[ ]]><i><![CDATA[ ]]></i>',
				'<b><xsl:text> </xsl:text></b><xsl:text> </xsl:text><i><xsl:text> </xsl:text></i>'
			],
			[
				'<div>
					<xsl:attribute name="title">
						<xsl:if test="@foo">
							<xsl:value-of select="@foo"/>
						</xsl:if>
					</xsl:attribute>
				</div>',
				'<div title="{@foo}"/>'
			],
			[
				'<div>
					<xsl:attribute name="title">
						<xsl:text>x</xsl:text>
						<xsl:value-of select="@foo"/>
						<xsl:text>y</xsl:text>
					</xsl:attribute>
				</div>',
				'<div title="x{@foo}y"/>'
			],
			[
				'<iframe height="{300 + 20}"/>',
				'<iframe height="320"/>',
			],
			[
				'<div style="padding-bottom:{100 * 315 div 560}%"/>',
				'<div style="padding-bottom:56.25%"/>'
			],
			[
				'<xsl:choose>
					<xsl:when test="@foo">foo</xsl:when>
					<xsl:otherwise/>
				</xsl:choose>',
				'<xsl:if test="@foo">foo</xsl:if>'
			],
			[
				'<a href="{@url}" target="_blank">...</a>',
				'<a href="{@url}" target="_blank" rel="noreferrer">...</a>'
			],
		];
	}
}

class DummyNormalization extends TemplateNormalization
{
	public function __construct($str)
	{
		$this->str = $str;
	}

	public function normalize(DOMElement $template)
	{
		$dom = $template->ownerDocument;
		$template->appendChild($dom->createTextNode($this->str));
	}
}