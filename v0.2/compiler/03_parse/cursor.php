<?php

declare(strict_types=1);
namespace scpp\compiler;

/** A parser-local cursor; reading never mutates the published token generation. */
final class Syntax_Cursor
{
	private int $index /** uint32; token position including EOF */ = 0;

	public readonly token_list $tokens;

	public function __construct(token_list $tokens)
	{
		$this->tokens = $tokens;
	}

	public function at_end(): bool
	{
		return $this->index === count($this->tokens->tokens);
	}

	public function position(): int /** uint32; token position including EOF */
	{
		return $this->index;
	}

	public function offset(): int /** uint32; byte position including EOF */
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

	public function take(token_kind $kind, string $expected): int /** uint32; consumed token position */
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
