<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use s9e\TextFormatter\Parser\Logger;

class Parser
{
	/**#@+
	* Boolean rules bitfield
	*/
	const RULE_AUTO_CLOSE       =  1;
	const RULE_AUTO_REOPEN      =  2;
	const RULE_IGNORE_TEXT      =  4;
	const RULE_IS_TRANSPARENT   =  8;
	const RULE_NO_BR_CHILD      = 16;
	const RULE_NO_BR_DESCENDANT = 32;
	const RULE_TRIM_WHITESPACE  = 64;
	/**#@-*/

	/**
	* @var array Tags' config
	*/
	protected $tagsConfig;

	/**
	* @var string Text being parsed
	*/
	protected $text;

	/**
	* Constructor
	*/
	public function __construct(array $config)
	{
		$this->logger        = new Logger($this);
		$this->pluginsConfig = $config['plugins'];
		$this->rootContext   = $config['rootContext'];
		$this->tagsConfig    = $config['tags'];
	}

	/**
	* Get this parser's Logger instance
	*
	* @return Logger
	*/
	public function getLogger()
	{
		return $this->logger;
	}

	/**
	* Parse a text
	*
	* @param  string $text Text to parse
	* @return string       XML representation
	*/
	public function parse($text)
	{
		$this->reset($text);
		$this->executePluginParsers();
		$this->processTags();

		return $this->output;
	}

	/**
	* Reset the parser for a new parsing
	*
	* @return void
	*/
	public function reset($text)
	{
		$this->logger->clear();

		$this->cntOpen  = array();
		$this->cntTotal = array();
		$this->tagStack = array();

		$this->context = $this->rootContext;
		$this->output  = '';
		$this->pos     = 0;
		$this->text    = $text;
		$this->textLen = strlen($text);

		unset($this->currentTag);
	}
}