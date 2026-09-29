<?php

/*
 * Role: token spans and retained spelling.
 * Used by: Tokenizer.
 */
namespace scpp\compiler;

final class token
{
	public int $offset /** uint32 */;
	public int $length /** uint32 */;

	// Retained spelling for now; a native string view is a future representation.
	protected string $_text;

	public function __construct(int $offset, int $length, string $text)
	{
		$this->offset = $offset;
		$this->length = $length;
		$this->_text = $text;
	}

	public function text(): string
	{
		return $this->_text;
	}
}

/** A reused half-open token interval and its equivalent interval in the current input. */
final class retained_token_range
{
	public int $first;
	public int $end;
	public int $current_first;

	public function __construct(int $first, int $end, int $current_first)
	{
		$this->first = $first;
		$this->end = $end;
		$this->current_first = $current_first;
	}
}

/** One file's appended source/token storage and the current input range; no scanner state. */
final class token_list
{
	/**
	 * Source dependency, selected from the owning module.
	 * @reference.source source_record.file
	 */
	public file $file;

	/** Authoritative source snapshot for this token list and all its spans. */
	public string $content;
	/** Current input in appended storage; byte offsets in tokens already include content_offset. */
	public int $first_token = 0;
	public int $end_token = 0;
	public int $content_offset = 0;
	/** Reused syntax intervals awaiting post-output compaction. @storage.owner */
	public Storage $retained_ranges /** Storage<retained_token_range> */;

	/**
	 * Numeric storage of token records.
	 * @storage.owner
	 */
	public Storage $tokens /** Storage<token> */;

	public function __construct()
	{
		$this->tokens = new Storage /** Storage<token> */();
		$this->retained_ranges = new Storage /** Storage<retained_token_range> */();
	}

	public function text_at(int $index): string
	{
		$tokens /** Storage<token> */ = $this->tokens;
		return $tokens[$index]->text();
	}
}
