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
	 * @reference.source source_record.tokens
	 * @reference.weak
	 */
	public ?token_list $tokens = null;
}

final class module
{
	public string $path;
	/**
	 * Numeric storage of stable source records.
	 * @storage.owner
	 */
	public Storage $sources /** Storage<source_record> */;

	public function __construct()
	{
		$this->sources = new Storage /** Storage<source_record> */();
	}
}

/** Stable module membership; stage publication replaces snapshots without replacing this identity. */
final class source_record
{
	public string $path;
	/** @reference.weak Model.modules */
	public module $module /** weak<module> */;
	/** Current published input and its optional completed stages. @ownership owner */
	public file $file;
	public ?token_list $tokens = null;
	public ?parsed_file $parsed = null;

	public function __construct(module $owner, file $snapshot)
	{
		$this->module = $owner;
		$this->path = $snapshot->path;
		$this->file = $snapshot;
	}
}
