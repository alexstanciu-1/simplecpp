<?php

declare(strict_types=1);
namespace scpp\compiler;

/** A parser-local cursor; reading never mutates the published token generation. */
final class Syntax_Cursor
{
	/** PROPERTIES */
	/** Token position including EOF. */
	private int $index /** uint32 */ = 0;
	/** Native non-owning; parser input remains alive through parsing. */
	public readonly token_list $tokens;

	/** CONSTRUCTOR */
	public function __construct(token_list $tokens)
	{
		$this->tokens = $tokens;
	}

	/** METHODS */
	public function at_end(): bool
	{
		return $this->index === count($this->tokens->tokens);
	}

	/** Return: token position including EOF. */
	public function position(): int /** uint32 */
	{
		return $this->index;
	}

	/** Return: byte position including EOF. */
	public function offset(): int /** uint32 */
	{
		return $this->tokens->tokens[$this->index]->offset ?? strlen($this->tokens->source->content);
	}

	public function is(token_kind $kind): bool
	{
		return ($this->tokens->tokens[$this->index]->kind ?? null) === $kind;
	}

	public function next_is(token_kind $kind): bool
	{
		return ($this->tokens->tokens[$this->index + 1]->kind ?? null) === $kind;
	}

	public function keyword(string $word): bool
	{
		return $this->is(token_kind::keyword) && ($this->tokens->text_at($this->index) === $word);
	}

	/** Return: consumed token position. */
	public function take(token_kind $kind, string $expected): int /** uint32 */
	{
		if (!$this->is($kind)) {
			throw new diagnostic($this->tokens->source, $this->offset(), 'Expected ' . $expected);
		}
		return $this->index++;
	}

	public function take_keyword(string $word): void
	{
		if (!$this->keyword($word)) {
			throw new diagnostic($this->tokens->source, $this->offset(), 'Expected ' . $word);
		}
		$this->index++;
	}

	public function name(token_kind $kind): string
	{
		$start = $this->take($kind, $kind === token_kind::variable ? 'variable name' : 'identifier');
		$text = $this->tokens->text_at($start);
		return $kind === token_kind::variable ? substr($text, 1) : $text;
	}
}
