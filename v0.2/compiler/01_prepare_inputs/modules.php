<?php

declare(strict_types=1);
namespace scpp\compiler;

/** General change state shared by compiler models; native uint8 intent. */
enum change_status: int
{
	case none = 0;
	case added = 1;
	case removed = 2;
	case changed = 3;
}

/** Owns the selected graph; entry references a module owned by modules. */
final readonly class compilation_session
{
	public array $modules /** array<string,module>; native owning index */;
	public module $entry; /** Native non-owning reference into modules. */

	public function __construct(array $modules /** array<string,module> */, module $entry)
	{
		$this->modules = $modules;
		$this->entry = $entry;
	}

	/** Source keys retain the existing single-module bundle spelling. */
	public function source_key(source_unit $source): string
	{
		return count($this->modules) === 1 ? $source->path : $source->module->name . '/' . $source->path;
	}

	public function entry_key(): string
	{
		return $this->source_key($this->entry->entry ?? throw new \LogicException('Session requires an entry source'));
	}

	/** References only: module membership remains the source-unit owner. */
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

	/** A complete frontend inventory is required before global publication. */
	public function files(): array /** array<string,ast_file_node>; non-owning references */
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
	public readonly array $artifacts /** list<string>; manually built link inputs, unchecked */;
	public readonly ?string $manifest; /** Canonical configuration input; null for explicit in-memory inventories. */
	public readonly string $type; /** default: compiler source; c++: native translation units. */
	public array $dependencies /** array<string,module>; native non-owning references */ = [];
	public array $sources /** array<string,source_unit>; native owning index */ = [];
	public ?source_unit $entry = null; /** Native non-owning reference; null for library modules. */

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
	public readonly module $module; /** Native non-owning owner backlink. */
	public readonly string $path;
	public readonly flavor $flavor;
	public change_status $status = change_status::none;
	public int $mtime /** int64; filesystem timestamp in seconds */ = 0;
	public int $size /** uint64; file size in bytes */ = 0;
	/** Owned completed frontend result; null until parsing/collection succeeds, or always null for native inputs. */
	public ?ast_file_node $ast = null;

	public function __construct(module $module, string $path, flavor $flavor)
	{
		$this->module = $module;
		$this->path = $path;
		$this->flavor = $flavor;
	}
}
