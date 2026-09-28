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
	public int $changes = 0;
	public sync_presence $presence;
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
		$this->presence = new sync_presence();
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

/** Own module identities by key, retaining deleted records separately from active order. */
final class module_collection
{
	/** @storage.owner Includes tombstones until a fresh session. */
	private Keyed_Storage $records /** Keyed_Storage<module> */;
	/** Ordered retaining aliases into records; excludes deleted modules. */
	private Storage $ordered /** Storage<module> */;
	private int $revision = 0;

	public function __construct()
	{
		$this->records = new Keyed_Storage /** Keyed_Storage<module> */();
		$this->ordered = new Storage /** Storage<module> */();
	}

	public function next_revision(): int
	{
		$this->revision++;
		return $this->revision;
	}

	public function find(string $name): ?module
	{
		$records /** Keyed_Storage<module> */ = $this->records;
		if (isset($records[$name])) {
			return $records[$name];
		}
		return null;
	}

	/** Insert a unique identity and expose it in active order. */
	public function add(module $record): void
	{
		$records /** Keyed_Storage<module> */ = $this->records;
		$ordered /** Storage<module> */ = $this->ordered;
		$records->add($record->name, $record);
		$ordered->append($record);
	}

	public function items(): Storage /** Storage<module> */
	{
		return $this->ordered;
	}

	public function inventory(): Keyed_Storage /** Keyed_Storage<module> */
	{
		return $this->records;
	}

	public function set_order(Storage $ordered /** Storage<module> */): void
	{
		$this->ordered = $ordered;
	}
}
