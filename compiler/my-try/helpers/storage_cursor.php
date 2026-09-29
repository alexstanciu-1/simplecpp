<?php

/* Host binding for the compiler runtime's typed, retaining read cursor. */
namespace scpp\compiler;

/** @scpp-no-export */
final class Storage_Cursor implements \Iterator
{
	private \Iterator $cursor;
	private bool $advanced = false;

	/** Retain Storage's existing lazy cursor; iteration must not mutate membership. */
	public function __construct(Storage $source)
	{
		$this->cursor = $source->getIterator();
		$this->cursor->rewind();
	}

	public function current(): object
	{
		$this->require_valid();
		return $this->cursor->current();
	}

	public function key(): int
	{
		$this->require_valid();
		return $this->cursor->key();
	}

	public function valid(): bool
	{
		return $this->cursor->valid();
	}

	public function next(): void
	{
		if ($this->valid()) {
			$this->cursor->next();
			$this->advanced = true;
		}
	}

	public function rewind(): void
	{
		if ($this->advanced) {
			throw new \LogicException('Create a fresh Storage cursor to restart traversal');
		}
	}

	private function require_valid(): void
	{
		if (!$this->valid()) {
			throw new \LogicException('Storage cursor has no current record');
		}
	}
}
