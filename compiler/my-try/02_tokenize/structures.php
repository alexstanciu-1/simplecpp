<?php

/*
 * Role: token spans and retained spelling.
 * Used by: Tokenizer.
 */
namespace scpp\compiler;

final class token {
	public int $offset;
	public int $length;
	# only in PHP, if we move to PHP++ text will be virtual via a string view
	public string $text;
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
		$this->tokens = new Storage();
	}
}
