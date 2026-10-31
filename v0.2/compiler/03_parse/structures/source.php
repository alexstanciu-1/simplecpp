<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * File root owns the token generation and the sole keyed declaration collection.
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
	public readonly array $declarations /** array<string,ast_function_declaration>; func:name -> node */;

	public function __construct(token_list $tokens)
	{
		parent::__construct(0, count($tokens->tokens));
		$this->parent = null;
		$this->tokens = $tokens;
	}

	/** Publish completed declarations once; their root links were assigned before discovery. */
	public function complete(array $declarations /** array<string,ast_function_declaration> */): void
	{
		foreach ($declarations as $key => $declaration) {
			if (($key !== 'func:' . $declaration->declaration_key()) || ($declaration->parent !== $this)) {
				throw new \LogicException('Invalid AST declaration ownership');
			}
			$declaration->body();
		}
		$this->declarations = $declarations;
	}

	/** Transient view of public function nodes; the keyed declaration map remains their sole owner. */
	public function functions(): array /** list<ast_function_declaration> */
	{
		return array_values(array_filter($this->declarations,
			static fn(ast_function_declaration $node): bool => !$node->is_private));
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
