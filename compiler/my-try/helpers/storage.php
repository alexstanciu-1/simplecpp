<?php

/* Numeric shared object list; deletion leaves holes without reusing positions. */
namespace scpp\compiler;

class Storage extends Storage_Abstract
{
	private int $next_position = 0;

	/** Allocate a monotonically increasing position; removed slots are never reused. */
	public function append(object $record): int
	{
		if ($this->next_position === PHP_INT_MAX) {
			throw new \OverflowException('Storage position limit reached');
		}
		$position = $this->next_position;
		$this->data[$position] = $record;
		$this->next_position++;
		return $position;
	}

	/** Route array-style writes to append or replacement without changing position rules. */
	protected function write(mixed $offset, object $record): void
	{
		if ($offset === null) {
			$this->append($record);
		}
		else {
			$this->replace($offset, $record);
		}
	}

	protected function key(mixed $offset): int
	{
		if (!is_int($offset)) {
			throw new \InvalidArgumentException('Storage requires an integer position');
		}
		return $offset;
	}
}
