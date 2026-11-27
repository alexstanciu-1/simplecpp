<?php

declare(strict_types=1);
namespace scpp\compiler;

/** A parser-local cursor; reading never mutates the published token generation. */
final class Syntax_Cursor
{
	/** PROPERTIES */
	/** Unconsumed suffix when a generic delimiter shares a punctuation token with assignment. */
	public private(set) ?token_kind $angle_remainder = null;
	/** Token position including EOF. */
	private int $index /** uint32 */ = 0;
	/** Native non-owning; parser input remains alive through parsing. */
	public readonly token_list $tokens;

	/** CONSTRUCTOR */
	public function __construct(token_list $tokens, int $start /** uint32 */ = 0)
	{
		$this->tokens = $tokens;
		$this->index = $start;
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
		$token = $this->tokens->tokens[$this->index] ?? null;
		if ($token === null) return strlen($this->tokens->source->content);
		return $token->offset + ($this->angle_remainder === null ? 0 : $token->length - strlen($this->angle_remainder->label()));
	}

	public function is(token_kind $kind): bool
	{
		return ($this->angle_remainder ?? $this->tokens->tokens[$this->index]->kind ?? null) === $kind;
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
		$this->angle_remainder = null;
		return $this->index++;
	}

	/** A PHP type can end immediately before assignment, including nested types ending in >>=. */
	public function close_type(): void
	{
		if ($this->tokens->source->flavor === flavor::php) {
			if ($this->is(token_kind::greater_equal)) {
				$this->angle_remainder = token_kind::assign;
				return;
			}
			if ($this->is(token_kind::right_assign)) {
				$this->angle_remainder = token_kind::greater_equal;
				return;
			}
		}
		$this->take(token_kind::greater, '>');
	}

	public function take_keyword(string $word): void
	{
		if (!$this->keyword($word)) {
			throw new diagnostic($this->tokens->source, $this->offset(), 'Expected ' . $word);
		}
		$this->index++;
	}

	/** Read a declaration or assignment target. PHP reserves $this for the current instance. */
	public function binding_name(token_kind $kind): string
	{
		$position = $this->position();
		$name = $this->name($kind);
		if ($kind === token_kind::variable && $name === 'this' && $this->tokens->source->flavor === flavor::php) {
			throw new diagnostic($this->tokens->source, $this->tokens->tokens[$position]->offset, 'Cannot rebind this receiver');
		}
		return $name;
	}

	public function name(token_kind $kind): string
	{
		$start = $this->take($kind, $kind === token_kind::variable ? 'variable name' : 'identifier');
		$text = $this->tokens->text_at($start);
		return $kind === token_kind::variable ? substr($text, 1) : $text;
	}
}
