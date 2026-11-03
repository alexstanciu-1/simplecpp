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
	public readonly token_list $tokens;
	public readonly scope $scope;
	/** Preparation run identity; invalidates old emission handles even if reanalysis later succeeds. */
	private int $preparation_revision /** uint32 */ = 0;
	private bool $prepared = false;
	public function begin_preparation(): void
	{
		++$this->preparation_revision;
		$this->prepared = false;
	}
	public function settle_preparation(): void
	{
		foreach ($this->functions() as $function) { $function->body()->require_prepared(); }
		$this->main()?->body()->require_prepared();
		$this->prepared = true;
	}
	public function prepared_revision(): int /** uint32 */
	{
		if (!$this->prepared) { throw new \LogicException('Source preparation is not complete'); }
		return $this->preparation_revision;
	}
	/** All duplicate declarations found in this completed source; syntax failures still throw. */
	public readonly array $diagnostics /** list<diagnostic> */;
	public readonly array $declaration_nodes /** list<ast_function_declaration>; syntax inventory independent of live duplicate chains */;
	public readonly array $declarations /** array<string,ast_function_declaration>; func:name -> node */;

	public function __construct(token_list $tokens)
	{
		parent::__construct(0, count($tokens->tokens));
		$this->parent = null;
		$this->tokens = $tokens;
		$this->scope = new scope();
	}

	/** Retain complete syntax once; file indexes never walk compilation-owned duplicate chains. */
	public function complete(array $declarations /** array<string,ast_function_declaration>; first references */,
		array $nodes /** list<ast_function_declaration> */, array $diagnostics /** list<diagnostic> */ = []): void
	{
		foreach ($nodes as $declaration) {
			if ($declaration->parent !== $this) { throw new \LogicException('Invalid AST declaration ownership'); }
			$declaration->body();
		}
		$this->declarations = $declarations;
		$this->declaration_nodes = $nodes;
		$this->diagnostics = $diagnostics;
	}

	/** Source-local public functions in syntax order, including invalid duplicates; never follows foreign chains. */
	public function functions(): array /** list<ast_function_declaration> */
	{
		$functions = [];
		foreach ($this->declaration_nodes as $node) { if (!$node->is_private) { $functions[] = $node; } }
		return $functions;
	}

	public function main(): ?ast_function_declaration
	{
		return $this->declarations['func:main'] ?? null;
	}

	/** Empty ranges anchor at the next token, or at source EOF after the final token. */
	public function byte_at(int $position /** uint32; token boundary */): int /** uint32; byte offset */
	{
		if (($position < 0) || ($position > $this->end)) {
			throw new \OutOfBoundsException('Token position is outside this source generation');
		}
		return $this->tokens->tokens[$position]->offset ?? strlen($this->tokens->source->content);
	}
}
