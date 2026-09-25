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

/** One file's retained source and ordered token records; no scanner state. */
final class token_list
{
	/**
	 * Source dependency, selected from the owning module.
	 * @storage.reference module.files
	 */
	public file $file;

	/** Authoritative source snapshot for this token list and all its spans. */
	public string $content;

	/**
	 * Numeric storage of token records.
	 * @storage.owner
	 */
	public Storage $tokens /** Storage<token> */;

	public function __construct()
	{
		$this->tokens = new Storage /** Storage<token> */();
	}
}
