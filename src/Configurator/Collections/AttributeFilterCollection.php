<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Items\AttributeFilter;

class AttributeFilterCollection extends NormalizedCollection
{
	public function get($key)
	{
		$key = $this->normalizeKey($key);

		if (!$this->exists($key))
			if ($key[0] === '#')
			{
				$filterName = \ucfirst(\substr($key, 1));
				$className  = 's9e\\TextFormatter\\Configurator\\Items\\AttributeFilters\\' . $filterName;

				if (!\class_exists($className))
					throw new InvalidArgumentException("Unknown attribute filter '" . $key . "'");

				$this->set($key, new $className);
			}
			else
				$this->set($key, new AttributeFilter($key));

		$filter = parent::get($key);

		$filter = clone $filter;

		return $filter;
	}

	public function normalizeKey($key)
	{
		if (\preg_match('/^#[a-z_0-9]+$/Di', $key))
			return \strtolower($key);

		if (\is_string($key) && \is_callable($key))
			return $key;

		throw new InvalidArgumentException("Invalid filter name '" . $key . "'");
	}

	public function normalizeValue($value)
	{
		if ($value instanceof AttributeFilter)
			return $value;

		if (\is_callable($value))
			return new AttributeFilter($value);

		throw new InvalidArgumentException('Argument 1 passed to ' . __METHOD__ . ' must be a valid callback or an instance of s9e\\TextFormatter\\Configurator\\Items\\AttributeFilter');
	}
}