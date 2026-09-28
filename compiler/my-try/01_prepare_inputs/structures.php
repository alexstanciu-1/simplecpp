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
	public string $name;
	public string $declared_path;
	public string $resolved_path;
	public int $position = 0;
	public change_state $changes = change_state::unchanged;
	/** Last reconciliation run in which this module was present. */
	public int $revision /** uint32 */ = 0;
	/**
	 * Numeric storage of stable source records.
	 * @storage.owner
	 */
	public Storage $sources /** Storage<source_record> */;

	/** Initialize identity and both path forms before publishing module membership. */
	public function __construct(string $declared_path, string $resolved_path, string $name)
	{
		$this->name = $name;
		$this->declared_path = $declared_path;
		$this->resolved_path = $resolved_path;
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

/** Incoming module configuration; an omitted name preserves the exact path spelling. */
final class module_input
{
	public string $name;
	public string $declared_path;

	public function __construct(string $path, ?string $name = null)
	{
		$this->declared_path = $path;
		$this->name = $name ?? $path;
	}
}
