<?php

/* Ordered duplicate-key membership; returned arrays are independent snapshots. */
namespace scpp\compiler;

final class Key_Storage_List
{
	/** @var list<object> */
	private array $ordered = [];
	/** @var array<string, list<int>> */
	private array $positions = [];

	/** Every insertion is distinct, even when its key and object identity repeat. */
	public function add(string $key, object $record): void
	{
		$position = count($this->ordered);
		$this->ordered[] = $record;
		$this->positions["\0" . $key][] = $position;
	}

	/** Copy membership without copying or changing the records themselves. */
	public function named(string $key): array
	{
		$result = [];
		foreach ($this->positions["\0" . $key] ?? [] as $position) {
			$result[] = $this->ordered[$position];
		}
		return $result;
	}

	public function items(): array
	{
		return $this->ordered;
	}

	public function is_empty(): bool
	{
		return count($this->ordered) === 0;
	}
}
