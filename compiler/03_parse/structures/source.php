<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * File root owns tokens and the ordered syntax inventory; its keyed indexes only reference declarations.
 *
 * PHP-flavor file:
 * ```text
 * function ping(): void {}
 * ping(); return 8;
 * ```
 * declarations contains private func:main first, then func:ping; the entry body owns the call and return.
 */
final class ast_file_node extends ast_node
{
	/** PROPERTIES */
	/** Native shared-owning; retained token generation. */
	public readonly token_list $tokens;
	/** Native shared-owning immutable context; retained roots never point to a mutable session. */
	public readonly type_availability $type_availability;
	/** Native owning; file reference scope. */
	public readonly scope $scope;
	/**
	 * All duplicate declarations found in this completed source; syntax failures still throw.
	 * Native owning diagnostics.
	 *
	 * @var diagnostic[]
	 */
	public readonly array $diagnostics /** list<diagnostic> */;
	/**
	 * Source declaration inventory, including methods owned by their struct; independent of live duplicate chains.
	 *
	 * @var (ast_function_declaration|ast_global_declaration|ast_constant_declaration|ast_type_declaration)[]
	 */
	public readonly array $declaration_nodes /** list<ast_function_declaration|ast_global_declaration|ast_constant_declaration|ast_type_declaration> */;
	/** @var (ast_function_declaration|ast_global_declaration|ast_constant_declaration|ast_type_declaration)[] Symbol key -> node; native non-owning first-reference index. */
	public readonly array $declarations /** array<string,ast_function_declaration|ast_global_declaration|ast_constant_declaration|ast_type_declaration> */;

	/** @var ast_import[] Native owning import occurrences; repeats remain inspectable syntax. */
	public readonly array $imports /** list<ast_import> */;
	/** @var ast_import[] Native non-owning first-binding index; preparation checks every occurrence. */
	private readonly array $function_imports /** array<string,ast_import> */;
	/** @var ast_import[] Native non-owning constant binding index into imports. */
	private readonly array $constant_imports /** array<string,ast_import> */;

	/** Optional source trait declarations and direct use sites, owned by this parse. */
	public ?trait_file_context $trait_context = null;

	/** PREP FACTS */
	/** Preparation state: run identity invalidates old emission handles even if reanalysis later succeeds. */
	private int $preparation_revision /** uint32 */ = 0;
	/** Preparation state: whole-file facts are ready only after successful settlement. */
	private bool $prepared = false;

	/** Prepared fact: source-owned executable implementations. @var array<string,array<int,ast_function_declaration>> */
	private array $static_implementations /** array<string,array<uint64,ast_function_declaration>> */ = [];

	/** CONSTRUCTOR */
	public function __construct(token_list $tokens, ?string $namespace = null, type_availability $availability = new type_availability())
	{
		parent::__construct(0, count($tokens->tokens));
		$this->parent = null;
		$this->tokens = $tokens;
		$this->type_availability = $availability;
		$this->scope = new scope(null, $namespace);
	}

	/** METHODS */
	public function begin_preparation(): void
	{
		++$this->preparation_revision;
		$this->prepared = false;
	}

	/** Publish file completion only after every body and global storage contract is settled. */
	public function settle_preparation(): void
	{
		foreach ($this->functions() as $function) {
			$function->return_type_def();
			if ($function->is_abstract && !$function->method_contract_ready) throw new \LogicException('Abstract method contract is not prepared');
			if (!$function->is_static) $function->optional_body()?->require_prepared();
		}
		foreach ($this->globals() as $global) {
			$global->type_def();
		}
		foreach ($this->constants() as $constant) {
			$constant->type_def();
		}
		$this->main()?->body()->require_prepared();
		foreach (array_keys($this->static_implementations) as $method_key) {
			if (count($this->static_implementations[$method_key]) < 2) continue;
			uasort($this->static_implementations[$method_key], static fn(ast_function_declaration $a, ast_function_declaration $b): int =>
				strcmp($a->instance->called->declaration()->name(), $b->instance->called->declaration()->name()));
		}
		$this->prepared = true;
	}

	public function prepared_revision(): int /** uint32 */
	{
		if (!$this->prepared) {
			throw new \LogicException('Source preparation is not complete');
		}
		return $this->preparation_revision;
	}

	/**
	 * Retain complete syntax once; file indexes never walk compilation-owned duplicate chains.
	 *
	 * $declarations: first references.
	 *
	 * @param (ast_function_declaration|ast_global_declaration|ast_constant_declaration|ast_type_declaration)[] $declarations
	 * @param (ast_function_declaration|ast_global_declaration|ast_constant_declaration|ast_type_declaration)[] $nodes
	 * @param diagnostic[] $diagnostics
	 * @param ast_import[] $imports
	 */
	public function complete(array $declarations /** array<string,ast_function_declaration|ast_global_declaration|ast_constant_declaration|ast_type_declaration> */,
		array $nodes /** list<ast_function_declaration|ast_global_declaration|ast_constant_declaration|ast_type_declaration> */, array $diagnostics /** list<diagnostic> */ = [],
		array $imports /** list<ast_import> */ = []): void
	{
		foreach ($nodes as $declaration) {
			if ($declaration->parent !== $this && !(($declaration instanceof ast_function_declaration || $declaration instanceof ast_literal_declaration)
				&& $declaration->parent instanceof ast_method_owner && $declaration->parent->parent === $this)) {
				throw new \LogicException('Invalid AST declaration ownership');
			}
			if ($declaration instanceof ast_function_declaration && !isset($declaration->end)) {
				throw new \LogicException('Function declaration is not complete');
			}
		}
		$bindings = [];
		$constants = [];
		foreach ($imports as $import)
		{
			if ($import->parent !== $this) {
				throw new \LogicException('Invalid AST import ownership');
			}
			if ($import->kind !== import_kind::function_only) {
				$constants[$import->local_name] ??= $import;
			}
			if ($import->kind !== import_kind::constant_only) {
				$bindings[$import->local_name] ??= $import;
			}
		}
		$this->imports = $imports;
		$this->function_imports = $bindings;
		$this->constant_imports = $constants;
		$this->declarations = $declarations;
		$this->declaration_nodes = $nodes;
		$this->diagnostics = $diagnostics;
	}

	/**
	 * Source-local explicit functions and methods in syntax order, including invalid duplicates; never follows foreign chains.
	 *
	 * @return ast_function_declaration[]
	 */
	public function functions(): array /** list<ast_function_declaration> */
	{
		$functions = [];
		foreach ($this->declaration_nodes as $node) {
			if (($node instanceof ast_function_declaration) && (!$node->is_implicit_entry)) {
				$functions[] = $node;
			}
		}
		return $functions;
	}

	/** Install one completed body in its defining unit. */
	public function publish_implementation(ast_function_declaration $method): void
	{
		$this->static_implementations[$method->symbol_key()][spl_object_id($method->instance->called)] = $method;
	}

	/** Remove one retired body without reading a deleted called-type declaration. */
	public function retire_implementation(ast_function_declaration $method): void
	{
		$key = $method->symbol_key();
		unset($this->static_implementations[$key][spl_object_id($method->instance->called)]);
		if (empty($this->static_implementations[$key])) unset($this->static_implementations[$key]);
	}

	/** Fresh standalone preparation replaces the complete demand graph. */
	public function reset_implementations(): void
	{
		$this->static_implementations = [];
	}

	/** @return ast_function_declaration[] Published implementations of one static method. */
	public function static_implementations(ast_function_declaration $method): array
	{
		return $this->static_implementations[$method->symbol_key()] ?? [];
	}

	/** Executable definitions in stable publication order. @return \Generator<ast_function_declaration> */
	public function implementations(): \Generator
	{
		foreach ($this->functions() as $method) {
			if ($method->is_static) yield from $this->static_implementations[$method->symbol_key()] ?? [];
			else yield $method;
		}
	}

	/** @return ast_global_declaration[] */
	public function globals(): array /** list<ast_global_declaration> */
	{
		return array_values(array_filter($this->declaration_nodes,
			static fn(ast_node $node): bool => $node instanceof ast_global_declaration));
	}

	/** @return (ast_function_declaration|ast_global_declaration|ast_constant_declaration|ast_type_declaration)[] */
	public function published_declarations(): array /** list<ast_function_declaration|ast_global_declaration|ast_constant_declaration|ast_type_declaration> */
	{
		return array_values(array_filter($this->declaration_nodes,
			static fn(ast_node $node): bool => !($node instanceof ast_function_declaration) || (!$node->is_implicit_entry)));
	}

	/** Imports affect only complete unqualified names, never namespace prefixes or rooted paths. */
	public function function_key(string $name): string
	{
		return 'func:' . ($this->imported_function_target($name) ?? Source_Names::resolve($name, $this->scope->namespace));
	}

	/** A function or plain import binds this spelling to an exact declaration lookup. */
	public function imported_function_target(string $name): ?string
	{
		return $this->function_imports[$name]->target ?? null;
	}

	/** Registered unqualified builtins are language-scoped; explicit imports and qualified calls retain exact targets. */
	public function runtime_function_key(string $name, string $resolved_key): string
	{
		if (!isset($this->function_imports[$name]) && !str_contains($name, '\\')) {
			return 'func:' . $name;
		}
		return $resolved_key;
	}

	/** Resolve unqualified runtime constants after the namespace's source declaration. */
	public function runtime_constant_key(string $name): string
	{
		return !isset($this->constant_imports[$name]) && !str_contains($name, '\\')
			? 'const:' . $name : $this->constant_key($name);
	}

	/** Compare written binding context across a file replacement without retaining a second index. */
	public function same_import_bindings(ast_file_node $previous): bool
	{
		if (count($this->imports) !== count($previous->imports)) return false;
		foreach ($this->imports as $i => $import) {
			$old = $previous->imports[$i];
			if ($old->local_name !== $import->local_name || $old->kind !== $import->kind || $old->target !== $import->target) return false;
		}
		return true;
	}

	/** Plain imports bind complete type names; no prefix aliasing or duplicate retained index. */
	public function type_key(string $name): string
	{
		return 'class:' . ($this->imported_type_target($name) ?? Source_Names::resolve($name, $this->scope->namespace));
	}

	public function imported_type_target(string $name): ?string
	{
		$import = $this->function_imports[$name] ?? null;
		return $import?->kind === import_kind::symbol ? $import->target : null;
	}

	public function constant_key(string $name): string
	{
		$import = $this->constant_imports[$name] ?? null;
		return 'const:' . ($import?->target ?? Source_Names::resolve($name, $this->scope->namespace));
	}

	/** @return ast_constant_declaration[] */
	public function constants(): array /** list<ast_constant_declaration> */
	{
		return array_values(array_filter($this->declaration_nodes,
			static fn(ast_node $node): bool => $node instanceof ast_constant_declaration));
	}

	public function main(): ?ast_function_declaration
	{
		return $this->declarations['func:main'] ?? null;
	}

	/**
	 * Empty ranges anchor at the next token, or at source EOF after the final token.
	 *
	 * $position: token boundary.
	 * Return: byte offset.
	 */
	public function byte_at(int $position /** uint32 */): int /** uint32 */
	{
		if (($position < 0) || ($position > $this->end)) {
			throw new \OutOfBoundsException('Token position is outside this source generation');
		}
		return $this->tokens->tokens[$position]->offset ?? strlen($this->tokens->source->content);
	}
}
