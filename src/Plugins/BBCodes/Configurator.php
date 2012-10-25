<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\BBCodes;

use ArrayAccess;
use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

class Configurator extends ConfiguratorBase implements ArrayAccess
{
	use CollectionProxy;

	/**
	* @var BBCodeCollection BBCode collection
	*/
	protected $collection;

	/**
	* @var RepositoryCollection BBCode repositories
	*/
	public $repositories;

	/**
	* Plugin setup
	*
	* @return void
	*/
	protected function setUp()
	{
		$this->collection = new BBCodeCollection;

		$this->repositories = new RepositoryCollection;
		$this->repositories->add('default', __DIR__ . '/repository.xml');
	}

	/**
	* Add a BBCode from a repository
	*
	* @param  string $bbcodeName Name of the BBCode to add
	* @param  string $repository Name of the repository to use as source
	* @param  array  $vars       Variables that will replace default values in the tag definition
	* @return BBCode             Newly-created BBCode
	*/
	public function addFromRepository($bbcodeName, $repository = 'default', array $vars = array())
	{
		if (!$this->repositories->exists($repository))
		{
			throw new InvalidArgumentException("Repository '" . $repository . "' does not exist");
		}

		$bbcodeName = BBCode::normalizeName($bbcodeName);

		$config = $this->repositories->get($repository);
		$bbcode = $config['bbcode'];
		$tag    = $config['tag'];

		$this->items->add($bbcodeName, $bbcode);
		$this->configurator->tags->add($bbcode->tagName, $tag);

		return $bbcode;
	}

	/**
	* 
	*/
	public function toConfig()
	{
		if (!count($this->collection))
		{
			return false;
		}

		/**
		* Build the regexp that matches all the BBCode names
		*/
		$regexp = RegexpBuilder::fromList(array_keys(iterator_to_array($this->collection)));

		// Remove the non-capturing subpattern since we place the regexp inside a capturing pattern
		$regexp = preg_replace('#^\\(\\?:(.*)\\)$#D', '$1', $regexp);

		return array(
			'bbcodes' => $this->collection->toConfig(),
			'regexp'  => '#\\[/?(' . $regexp . ')(?=[\\] =:/])#iS'
		);
	}
}