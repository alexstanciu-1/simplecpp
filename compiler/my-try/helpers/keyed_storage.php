<?php

/* Unique string keys select shared objects directly; there is no position map. */
namespace scpp\compiler;

class Keyed_Storage extends Storage_Abstract
{
	/** Insert a new key; use [] assignment to deliberately insert or replace. */
	public function add(string $key, object $record): void
	{
		$internal = $this->key($key);
		if (isset($this->data[$internal])) {
			throw new \InvalidArgumentException('Duplicate storage key: ' . $key);
		}
		$this->data[$internal] = $record;
	}

	protected function write(mixed $offset, object $record): void
	{
		$this->data[$this->key($offset)] = $record;
	}

	protected function key(mixed $offset): string
	{
		if (!is_string($offset)) {
			throw new \InvalidArgumentException('Keyed_Storage requires a string key');
		}
		// Keep numeric-looking strings as strings in PHP, without a second index.
		return "\0" . $offset;
	}

	protected function public_key(int|string $key): string
	{
		return substr($key, 1);
	}
}
