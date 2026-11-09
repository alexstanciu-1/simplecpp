<?php

declare(strict_types=1);
namespace scpp\compiler;

/** General change state shared by compiler models; native uint8 intent. */
enum change_status: int {
	case none = 0;
	case added = 1;
	case removed = 2;
	case changed = 3;
}

/** Owns the selected graph; entry references a module owned by modules. */
final class compilation_session
{
	/** PROPERTIES */
	/** @var module[] Native owning index. */
	public readonly array $modules /** array<string,module> */;
	/** Native non-owning reference into modules. */
	public readonly module $entry;
	/** Native namespace root selected for this application. Empty selects global emission. */
	public readonly string $cpp_namespace;
	/**
	 * Previous frontend generation owns replaced/removed roots; unchanged roots may be shared.
	 * Native shared-owning; retains prior syntax generations.
	 */
	public ?compilation_session $previous = null;
	/**
	 * Null until the entire frontend generation has completed successfully.
	 * Native shared-owning; live across frontend generations.
	 */
	public ?scope $scope = null;
	/** Sequential lifecycle; successful replacement retires the prior session. */
	public bool $is_current = true;
	/** Native shared-owning; shared by current and retained sessions. */
	public Dependency_Watches $dependency_watches;
	/** Native shared-owning; one pending-body set follows the current session lineage. */
	public preparation_work $preparation_work;
	/** @var source_unit[] Removed records reference old ASTs; native owning records with shared-owning retained ASTs. */
	public array $removed_sources /** list<source_unit> */ = [];

	/** CONSTRUCTOR */
	/** @param module[] $modules */
	public function __construct(array $modules /** array<string,module> */, module $entry, string $cpp_namespace = 'App')
	{
		$this->dependency_watches = new Dependency_Watches();
		$this->preparation_work = new preparation_work();
		$this->modules = $modules;
		$this->entry = $entry;
		$this->cpp_namespace = $cpp_namespace;
	}

	/** METHODS */
	/** Source keys retain the existing single-module bundle spelling. */
	public function source_key(source_unit $source): string
	{
		return count($this->modules) === 1 ? $source->path : $source->module->name . '/' . $source->path;
	}

	public function entry_key(): string
	{
		return $this->source_key($this->entry->entry ?? throw new \LogicException('Session requires an entry source'));
	}

	/**
	 * References only: module membership remains the source-unit owner.
	 *
	 * @return source_unit[]
	 */
	public function units(): array /** array<string,source_unit> */
	{
		$result = [];
		foreach ($this->modules as $module) {
			foreach ($module->sources as $source) {
				$result[$this->source_key($source)] = $source;
			}
		}
		return $result;
	}

	/**
	 * A complete frontend inventory is required before global publication.
	 *
	 * Return: non-owning references.
	 *
	 * @return ast_file_node[]
	 */
	public function files(): array /** array<string,ast_file_node> */
	{
		$result = [];
		foreach ($this->units() as $key => $source) {
			if ($source->module->type === 'c++') {
				continue;
			}
			$result[$key] = $source->ast ?? throw new \LogicException('Incomplete source frontend: ' . $key);
		}
		return $result;
	}
}

/** Common source/runtime module owner; e.g. app depends on core through a non-owning link. */
final class module
{
	public readonly string $name;
	public readonly string $root;
	/** @var string[] Manually built link inputs, unchecked. */
	public readonly array $artifacts /** list<string> */;
	/** Canonical configuration input; null for explicit in-memory inventories. */
	public readonly ?string $manifest;
	/** default: compiler source; c++: native translation units. */
	public readonly string $type;
	/** @var module[] Native non-owning references. */
	public array $dependencies /** array<string,module> */ = [];
	/** @var source_unit[] Native owning index. */
	public array $sources /** array<string,source_unit> */ = [];
	/** Native non-owning reference; null for library modules. */
	public ?source_unit $entry = null;

	/** @param string[] $artifacts */
	public function __construct(string $name, string $root, string $type = 'default', ?string $manifest = null,
		array $artifacts /** list<string> */ = [])
	{
		$this->name = $name;
		$this->root = $root;
		$this->type = $type;
		$this->manifest = $manifest;
		$this->artifacts = $artifacts;
	}
}

/** Stable module-relative identity, e.g. app/main.cpp; the AST owns its tokens and source snapshot. */
final class source_unit
{
	/** Native non-owning owner backlink. */
	public readonly module $module;
	public readonly string $path;
	public readonly flavor $flavor;
	public change_status $status = change_status::none;
	/** Filesystem timestamp in seconds. */
	public int $mtime /** int64 */ = 0;
	/** File size in bytes. */
	public int $size /** uint64 */ = 0;
	/**
	 * Retained frontend root; unchanged generations share this reference. Null before
	 * collection/native inputs.
	 * Native shared-owning; unchanged roots survive across generations.
	 */
	public ?ast_file_node $ast = null;

	public function __construct(module $module, string $path, flavor $flavor)
	{
		$this->module = $module;
		$this->path = $path;
		$this->flavor = $flavor;
	}
}
