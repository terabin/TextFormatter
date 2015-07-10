<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Emoji;

use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\RegExp;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

class Configurator extends ConfiguratorBase
{
	/**
	* @var string Name of the attribute used by this plugin
	*/
	protected $attrName = 'seq';

	/**
	* @var bool Whether to force the image size in the img tag
	*/
	protected $forceImageSize = true;

	/**
	* @var string Emoji set to use
	*/
	protected $imageSet = 'twemoji';

	/**
	* @var integer Target size for the emoji images
	*/
	protected $imageSize = 16;

	/**
	* @var string Preferred image type
	*/
	protected $imageType = 'png';

	/**
	* @var string Name of the tag used by this plugin
	*/
	protected $tagName = 'EMOJI';

	/**
	* Plugin's setup
	*
	* Will create the tag used by this plugin
	*/
	protected function setUp()
	{
		if (isset($this->configurator->tags[$this->tagName]))
		{
			return;
		}

		$tag = $this->configurator->tags->add($this->tagName);
		$tag->attributes->add($this->attrName)->filterChain->append(
			$this->configurator->attributeFilters['#identifier']
		);
		$this->resetTemplate();
	}

	/**
	* Force the size of the image to be set in the img element
	*
	* @return void
	*/
	public function forceImageSize()
	{
		$this->forceImageSize = true;
		$this->resetTemplate();
	}

	/**
	* Omit the size of the image in the img element
	*
	* @return void
	*/
	public function omitImageSize()
	{
		$this->forceImageSize = false;
		$this->resetTemplate();
	}

	/**
	* Set the size of the images used for emoji
	*
	* @param  integer $size Preferred size
	* @return void
	*/
	public function setImageSize($size)
	{
		$this->imageSize = (int) $size;
		$this->resetTemplate();
	}

	/**
	* Use the EmojiOne image set
	*
	* @return void
	*/
	public function useEmojiOne()
	{
		$this->imageSet = 'emojione';
		$this->resetTemplate();
	}

	/**
	* Use PNG images if available
	*
	* @return void
	*/
	public function usePNG()
	{
		$this->imageType = 'png';
		$this->resetTemplate();
	}

	/**
	* Use SVG images if available
	*
	* @return void
	*/
	public function useSVG()
	{
		$this->imageType = 'svg';
		$this->resetTemplate();
	}

	/**
	* Use the Twemoji image set
	*
	* @return void
	*/
	public function useTwemoji()
	{
		$this->imageSet = 'twemoji';
		$this->resetTemplate();
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		$phpRegexp = '(';
		$jsRegexp  = '';

		// Start with a lookahead assertion for performance
		//
		// NOTE: PCRE does not really require it if the S flag is set, but it doesn't seem to
		//       negatively impact performance
		$phpRegexp .= '(?=[#0-9:\\xC2\\xE2\\xE3\\xF0])';

		// Start the main alternation
		$phpRegexp .= '(?>';
		$jsRegexp  .= '(?:';

		// Shortcodes
		$phpRegexp .= ':[-+_a-z0-9]+(?=:)';
		$jsRegexp  .= ':[-+_a-z0-9]+(?=:)';

		// Start the emoji alternation
		$phpRegexp .= '|(?>';
		$jsRegexp  .= '|(?:';

		// Keypad emoji: starts with [#0-9], optional U+FE0F, ends with U+20E3
		$phpRegexp .= '[#0-9](?>\\xEF\\xB8\\x8F)?\\xE2\\x83\\xA3';
		$jsRegexp  .= '[#0-9]\\uFE0F?\\u20E3';

		// (c) and (r). We also start a character class in JS
		$phpRegexp .= '|\\xC2[\\xA9\\xAE]';
		$jsRegexp  .= '|[\\u00A9\\u00AE';

		// 0xE2XXXX block: U+203C..U+2B55. We try to avoid common symbols such as U+2018..U+201D
		$phpRegexp .= '|\\xE2(?>\\x80\\xBC|[\\x81-\\xAD].)';
		$jsRegexp  .= '\\u203C\\u2049\\u2122-\\u2B55';

		// 0xE3XXXX block: U+3030, U+303D, U+3297, U+3299. Also the end of the JS character class
		$phpRegexp .= '|\\xE3(?>\\x80[\\xB0\\xBD]|\\x8A[\\x97\\x99])';
		$jsRegexp  .= '\\u3030\\u303D\\u3297\\u3299]';

		// Start the 0xF09FXXXX block
		$phpRegexp .= '|\\xF0\\x9F(?>';
		$jsRegexp  .= '|\\uD83C(?:';

		// Subblock: 0x80XX..0x86XX
		//
		//    0xF09F8084..0xF09F869A
		//       U+1F004..U+1F19A
		// U+D83C U+DC04..U+D83C U+DD9A
		$phpRegexp .= '[\\x80-\\x86].';
		$jsRegexp  .= '[\\uDC04-\\uDD9A]';

		// Subblock: 0x87XX (flag pairs)
		//
		//    0xF09F87A6..0xF09F87BA
		//       U+1F1E6..U+1F1FF
		// U+D83C U+DDE6..U+D83C U+DDFF
		$phpRegexp .= '|\\x87.\\xF0\\x9F\\x87.';
		$jsRegexp  .= '|[\\uDDE6-\\uDDFF]\\uD83C[\\uDDE6-\\uDDFF]';

		// Subblock: 0x88XX..0x9BXX
		//
		//    0xF09F8881..0xF09F9B85
		//       U+1F201..U+1F3FF
		// U+D83C U+DE01..U+D83C U+DFFF
		//       U+1F400..U+1F6C5
		// U+D83D U+DC00..U+D83D U+DEC5
		$phpRegexp .= '|[\\x88-\\x9B].';
		$jsRegexp  .= '|[\\uDE01-\\uDFFF])|\\uD83D[\\uDC00-\\uDEC5]';

		// Close the 0xF09FXXXX block
		$phpRegexp .= ')';

		// Close the emoji alternation, optionally followed by U+FE0F
		$phpRegexp .= ')(?>\\xEF\\xB8\\x8F)?';
		$jsRegexp  .= ')\uFE0F?';

		// Close the main alternation
		$phpRegexp .= ')';
		$jsRegexp  .= ')';

		// End the PHP regexp with the S modifier
		$phpRegexp .= ')S';

		// Create a Variant to hold both regexps
		$regexp = new Variant($phpRegexp);
		$regexp->set('JS', new RegExp($jsRegexp, 'g'));

		return [
			'attrName' => $this->attrName,
			'regexp'   => $regexp,
			'tagName'  => $this->tagName
		];
	}

	/**
	* Get the template used to display EmojiOne's images
	*
	* @return string
	*/
	protected function getEmojiOneTemplate()
	{
		$template = '<img alt="{.}" class="emoji"';
		if ($this->forceImageSize)
		{
			$template .= ' width="' . $this->imageSize . '" height="' . $this->imageSize . '"';
		}
		$template .= '>
			<xsl:attribute name="src">
				<xsl:text>//cdn.jsdelivr.net/emojione/assets/' . $this->imageType . '/</xsl:text>
				<xsl:if test="contains(@seq, \'-20e3\') or @seq = \'a9\' or @seq = \'ae\'">00</xsl:if>
				<xsl:value-of select="translate(@seq, \'abcdef\', \'ABCDEF\')"/>
				<xsl:text>.' . $this->imageType . '</xsl:text>
			</xsl:attribute>
		</img>';

		return $template;
	}

	/**
	* Get the first available size that satisfies our size requirement
	*
	* @param  integer[] $sizes Available sizes
	* @return integer
	*/
	protected function getTargetSize(array $sizes)
	{
		$k = 0;
		foreach ($sizes as $k => $size)
		{
			if ($size >= $this->imageSize)
			{
				break;
			}
		}

		return $sizes[$k];
	}

	/**
	* Get this tag's template
	*
	* @return string
	*/
	protected function getTemplate()
	{
		return ($this->imageSet === 'emojione') ? $this->getEmojiOneTemplate() :  $this->getTwemojiTemplate();
	}

	/**
	* Get the template used to display Twemoji's images
	*
	* @return string
	*/
	protected function getTwemojiTemplate()
	{
		$template = '<img alt="{.}" class="emoji" draggable="false"';
		if ($this->forceImageSize)
		{
			$template .= ' width="' . $this->imageSize . '" height="' . $this->imageSize . '"';
		}
		$template .= ' src="//twemoji.maxcdn.com/';
		if ($this->imageType === 'svg')
		{
			$template .= 'svg';
		}
		else
		{
			$size = $this->getTargetSize([16, 36, 72]);
			$template .= $size . 'x' . $size;
		}
		$template .= '/{@seq}.' . $this->imageType . '"/>';

		return $template;
	}

	/**
	* Reset the template used by this plugin's tag
	*
	* @return void
	*/
	protected function resetTemplate()
	{
		$this->getTag()->template = $this->getTemplate();
	}
}