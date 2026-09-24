<?php

/* Shared object collection behavior; subclasses define key and insertion rules. */
namespace scpp\compiler;

abstract class Storage_Abstract implements \IteratorAggregate, \ArrayAccess, \Countable
{
	/** @var array<int|string, object> Membership retains shared object handles. */
	protected array $data = [];

	public function __construct(int $capacity = 0)
	{
		$this->reserve($capacity);
	}

	/** Validate and translate an external key to its PHP backing-array key. */
	abstract protected function key(mixed $offset): int|string;
	abstract protected function write(mixed $offset, object $record): void;

	protected function public_key(int|string $key): int|string
	{
		return $key;
	}

	public function replace(mixed $offset, object $record): void
	{
		$key = $this->key($offset);
		$this->require_key($key);
		$this->data[$key] = $record;
	}

	public function remove(mixed $offset): void
	{
		$key = $this->key($offset);
		$this->require_key($key);
		unset($this->data[$key]);
	}

	/** Native allocation hint; PHP does not allocate or change membership. */
	public function reserve(int $capacity): void
	{
		if ($capacity < 0) {
			throw new \InvalidArgumentException('Storage capacity must not be negative');
		}
	}

	public function offsetGet(mixed $offset): object
	{
		$key = $this->key($offset);
		$this->require_key($key);
		return $this->data[$key];
	}

	public function offsetSet(mixed $offset, mixed $value): void
	{
		if (!is_object($value)) {
			throw new \InvalidArgumentException('Storage accepts object records');
		}
		$this->write($offset, $value);
	}

	public function offsetExists(mixed $offset): bool
	{
		return isset($this->data[$this->key($offset)]);
	}

	public function offsetUnset(mixed $offset): void
	{
		unset($this->data[$this->key($offset)]);
	}

	public function getIterator(): \Traversable
	{
		foreach ($this->data as $key => $record) {
			yield $this->public_key($key) => $record;
		}
	}

	public function count(): int
	{
		return count($this->data);
	}

	public function is_empty(): bool
	{
		return empty($this->data);
	}

	private function require_key(int|string $key): void
	{
		if (!isset($this->data[$key])) {
			throw new \OutOfBoundsException('Unknown storage key: ' . $this->public_key($key));
		}
	}
}
