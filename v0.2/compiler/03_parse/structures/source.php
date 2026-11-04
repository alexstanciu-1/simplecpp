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
	 * @var ast_function_declaration[]
	 */
	public readonly array $declaration_nodes /** list<ast_function_declaration> */;
	/** @var ast_function_declaration[] Func:name -> node; native non-owning first-reference index. */
	public readonly array $declarations /** array<string,ast_function_declaration> */;

	/** PREP FACTS */
	/** Preparation state: run identity invalidates old emission handles even if reanalysis later succeeds. */
	private int $preparation_revision /** uint32 */ = 0;
	/** Preparation state: whole-file facts are ready only after successful settlement. */
	private bool $prepared = false;

	/** CONSTRUCTOR */
	public function __construct(token_list $tokens)
	{
		parent::__construct(0, count($tokens->tokens));
		$this->parent = null;
		$this->tokens = $tokens;
		$this->scope = new scope();
	}

	/** METHODS */
	public function begin_preparation(): void
	{
		++$this->preparation_revision;
		$this->prepared = false;
	}

	public function settle_preparation(): void
	{
		foreach ($this->functions() as $function) {
			$function->body()->require_prepared();
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
	 * @param ast_function_declaration[] $declarations
	 * @param ast_function_declaration[] $nodes
	 * @param diagnostic[] $diagnostics
	 */
	public function complete(array $declarations /** array<string,ast_function_declaration> */,
		array $nodes /** list<ast_function_declaration> */, array $diagnostics /** list<diagnostic> */ = []): void
	{
		foreach ($nodes as $declaration) {
			if ($declaration->parent !== $this) {
				throw new \LogicException('Invalid AST declaration ownership');
			}
			$declaration->body();
		}
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
			if (!$node->is_private) {
				$functions[] = $node;
			}
		}
		return $functions;
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
