<?php

/*
 * Role: source-loading data records.
 * Used by: File_Loader and Module_Loader.
 * Flow: filesystem input -> file records -> module file list.
 * Each file references its retained token_list, never the scanner.
 */
namespace scpp\compiler;

/** Flags describe the current update; deleted rows remain until explicit cleanup. */
final class file
{
	public int $changes = 0;
	public string $path;
	public int $mtime = 0;
	public int $size = 0;
	/** Input for the next tokenization; existing token lists retain their own snapshot. */
	public string $content = '';
	/** Discovered paths are read by the tokenizer; false selects supplied in-memory bytes. */
	public bool $disk_source = false;
	/**
	 * Convenience backlink to the completed tokenization result; not its owner.
	 * @storage.reference model.tokens
	 * @reference.weak
	 */
	public ?token_list $tokens = null;
}

final class module
{
	public string $path;
	/**
	 * Numeric storage of file records.
	 * @storage.owner
	 */
	public Storage $files /** Storage<file> */;

	public function __construct()
	{
		$this->files = new Storage /** Storage<file> */();
	}
}
