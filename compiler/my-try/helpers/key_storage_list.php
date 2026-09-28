<?php

/* Ordered duplicate-key membership; returned arrays are independent snapshots. */
namespace scpp\compiler;

final class Key_Storage_List
{
	/** @var array<int, object> Removed positions are not reused. */
	private array $ordered = [];
	/** @var array<string, list<int>> */
	private array $positions = [];
	private int $next_position = 0;

	/** Every insertion is distinct, even when its key and object identity repeat. */
	public function add(string $key, object $record): void
	{
		$position = $this->next_position++;
		$this->ordered[$position] = $record;
		$this->positions["\0" . $key][] = $position;
	}

	/** Remove every insertion of this identity under this key, preserving other keys and duplicates. */
	public function remove(string $key, object $record): void
	{
		$encoded = "\0" . $key;
		$remaining = [];
		foreach ($this->positions[$encoded] ?? [] as $position)
		{
			if ($this->ordered[$position] === $record) {
				unset($this->ordered[$position]);
			}
			else {
				$remaining[] = $position;
			}
		}
		if (count($remaining) === 0) {
			unset($this->positions[$encoded]);
		}
		else {
			$this->positions[$encoded] = $remaining;
		}
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
		return array_values($this->ordered);
	}

	public function is_empty(): bool
	{
		return count($this->ordered) === 0;
	}
}
