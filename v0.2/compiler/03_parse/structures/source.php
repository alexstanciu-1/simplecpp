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
	 * Syntax inventory independent of live duplicate chains; native owning syntax inventory.
	 *
	 * @var (ast_function_declaration|ast_global_declaration|ast_constant_declaration)[]
	 */
	public readonly array $declaration_nodes /** list<ast_function_declaration|ast_global_declaration|ast_constant_declaration> */;
	/** @var (ast_function_declaration|ast_global_declaration|ast_constant_declaration)[] Symbol key -> node; native non-owning first-reference index. */
	public readonly array $declarations /** array<string,ast_function_declaration|ast_global_declaration|ast_constant_declaration> */;

	/** @var ast_import[] Native owning import occurrences; repeats remain inspectable syntax. */
	public readonly array $imports /** list<ast_import> */;
	/** @var ast_import[] Native non-owning first-binding index; preparation checks every occurrence. */
	private readonly array $function_imports /** array<string,ast_import> */;
	/** @var ast_import[] Native non-owning constant binding index into imports. */
	private readonly array $constant_imports /** array<string,ast_import> */;

	/** PREP FACTS */
	/** Preparation state: run identity invalidates old emission handles even if reanalysis later succeeds. */
	private int $preparation_revision /** uint32 */ = 0;
	/** Preparation state: whole-file facts are ready only after successful settlement. */
	private bool $prepared = false;

	/** CONSTRUCTOR */
	public function __construct(token_list $tokens, ?string $namespace = null)
	{
		parent::__construct(0, count($tokens->tokens));
		$this->parent = null;
		$this->tokens = $tokens;
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
			$function->body()->require_prepared();
		}
		foreach ($this->globals() as $global) {
			$global->type_def();
		}
		foreach ($this->constants() as $constant) {
			$constant->type_def();
		}
		$this->main()?->body()->require_prepared();
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
	 * @param (ast_function_declaration|ast_global_declaration|ast_constant_declaration)[] $declarations
	 * @param (ast_function_declaration|ast_global_declaration|ast_constant_declaration)[] $nodes
	 * @param diagnostic[] $diagnostics
	 * @param ast_import[] $imports
	 */
	public function complete(array $declarations /** array<string,ast_function_declaration|ast_global_declaration|ast_constant_declaration> */,
		array $nodes /** list<ast_function_declaration|ast_global_declaration|ast_constant_declaration> */, array $diagnostics /** list<diagnostic> */ = [],
		array $imports /** list<ast_import> */ = []): void
	{
		foreach ($nodes as $declaration) {
			if ($declaration->parent !== $this) {
				throw new \LogicException('Invalid AST declaration ownership');
			}
			if ($declaration instanceof ast_function_declaration) {
				$declaration->body();
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
	 * Source-local public functions in syntax order, including invalid duplicates; never follows foreign chains.
	 *
	 * @return ast_function_declaration[]
	 */
	public function functions(): array /** list<ast_function_declaration> */
	{
		$functions = [];
		foreach ($this->declaration_nodes as $node) {
			if (($node instanceof ast_function_declaration) && (!$node->is_private)) {
				$functions[] = $node;
			}
		}
		return $functions;
	}

	/** @return ast_global_declaration[] */
	public function globals(): array /** list<ast_global_declaration> */
	{
		return array_values(array_filter($this->declaration_nodes,
			static fn(ast_node $node): bool => $node instanceof ast_global_declaration));
	}

	/** @return (ast_function_declaration|ast_global_declaration|ast_constant_declaration)[] */
	public function published_declarations(): array /** list<ast_function_declaration|ast_global_declaration|ast_constant_declaration> */
	{
		return array_values(array_filter($this->declaration_nodes,
			static fn(ast_node $node): bool => !($node instanceof ast_function_declaration) || (!$node->is_private)));
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
