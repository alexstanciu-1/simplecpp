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
	/** Module-relative identity; absolute paths are passed separately for IO. */
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
	/** Configuration-backed modules participate in filesystem scanning. */
	public bool $disk_source = false;
	public change_state $changes = change_state::unchanged;
	/** Last reconciliation run in which this module was present. */
	public int $revision /** uint32 */ = 0;
	/**
	 * Module-relative paths own and index stable source records.
	 * @storage.owner
	 */
	public Keyed_Storage $sources /** Keyed_Storage<source_record> */;

	/** Initialize identity and both path forms before publishing module membership. */
	public function __construct(string $declared_path, string $resolved_path, string $name)
	{
		$this->name = $name;
		$this->declared_path = $declared_path;
		$this->resolved_path = $resolved_path;
		$this->sources = new Keyed_Storage /** Keyed_Storage<source_record> */();
	}
}

/** Stable module membership; stage publication replaces snapshots without replacing this identity. */
final class source_record
{
	/** Normalized module-relative path, including subfolders. */
	public string $path;
	/** Pending filesystem work; successful publication clears live changes. */
	public change_state $changes = change_state::added;
	public int $revision /** uint32 */ = 0;
	/** @reference.weak Model.modules */
	public module $module /** weak<module> */;
	/** Current published input and its optional completed stages. @ownership owner */
	public file $file;
	/** Current input range and retained token/source storage. */
	public ?token_list $tokens = null;
	public ?parsed_file $parsed = null;

	public function __construct(module $owner, file $snapshot)
	{
		$this->module = $owner;
		$this->path = $snapshot->path;
		$this->file = $snapshot;
	}

	/** Acquire the module while constructing an IO path from this relative identity. */
	public function owning_module(): module
	{
		return object_cast(weakref_get($this->module), module::class);
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
