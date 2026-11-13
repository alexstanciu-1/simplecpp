<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Flavor-specific path syntax; produces canonical backslashes and preserves a leading root marker. */
final class Namespace_Syntax
{
	/** PROPERTIES */
	/** Native non-owning; parser owns the cursor. */
	private readonly Syntax_Cursor $cursor;
	private readonly token_kind $separator;
	private readonly token_kind $root_marker;

	/** CONSTRUCTOR */
	/** Select notation once for this cursor; namespace identity itself is flavor-independent. */
	public function __construct(Syntax_Cursor $cursor)
	{
		$this->cursor = $cursor;
		$this->separator = match ($cursor->tokens->source->flavor) {
			flavor::php => token_kind::backslash,
			flavor::cpp => token_kind::scope,
			flavor::js => token_kind::dot,
		};
		$this->root_marker = $cursor->tokens->source->flavor === flavor::cpp ? token_kind::scope : token_kind::backslash;
	}

	/** METHODS */
	/** PHP also permits an explicit global namespace body; anonymous C++ namespaces are outside this slice. */
	public function declaration(): ?string
	{
		$this->cursor->take_keyword('namespace');
		if (($this->cursor->tokens->source->flavor === flavor::php) && $this->cursor->is(token_kind::lbrace)) {
			return null;
		}
		return $this->path(false);
	}

	/** Look through a name path without consuming it; qualified variable reads are not calls. */
	public function call_start(): bool
	{
		$index = $this->cursor->position();
		$tokens = $this->cursor->tokens->tokens;
		if (($tokens[$index]->kind ?? null) === $this->root_marker) {
			$index++;
		}
		while (($tokens[$index]->kind ?? null) === token_kind::identifier) {
			$index++;
			if (($tokens[$index]->kind ?? null) === token_kind::lparen) {
				return true;
			}
			if ($this->cursor->tokens->source->flavor === flavor::php && ($tokens[$index]->kind ?? null) === token_kind::scope) {
				return ($tokens[$index + 1]->kind ?? null) === token_kind::identifier && ($tokens[$index + 2]->kind ?? null) === token_kind::lparen;
			}
			if (($tokens[$index]->kind ?? null) !== $this->separator) {
				return false;
			}
			$index++;
		}
		return (($tokens[$index]->kind ?? null) === token_kind::keyword)
			&& ($this->cursor->tokens->text_at($index) === 'empty')
			&& (($tokens[$index + 1]->kind ?? null) === token_kind::lparen);
	}

	/** Recognize a qualified plain assignment without consuming a qualified C++ declaration or call. */
	public function qualified_write(): bool
	{
		$i = $this->cursor->position();
		$tokens = $this->cursor->tokens->tokens;
		if (($tokens[$i]->kind ?? null) === $this->root_marker) $i++;
		$qualified = false;
		while (($tokens[$i]->kind ?? null) === token_kind::identifier) {
			$next = $tokens[++$i]->kind ?? null;
			if ($next === token_kind::assign || ($qualified && $next !== null && binary_operator::compound($next) !== null)) return $qualified;
			if ($this->cursor->tokens->source->flavor === flavor::php && $next === token_kind::scope) {
				$operator = $tokens[$i + 2]->kind ?? null;
				return ($tokens[$i + 1]->kind ?? null) === token_kind::variable && $operator !== null
					&& ($operator === token_kind::assign || binary_operator::compound($operator) !== null);
			}
			if ($next !== $this->separator) return false;
			$qualified = true;
			$i++;
		}
		return false;
	}

	/** PHP qualifies the final variable token (test\$value); other flavors qualify ordinary identifiers. */
	public function variable_path(bool &$constant_only = false): string
	{
		if ($this->cursor->tokens->source->flavor !== flavor::php) {
			return $this->path(true);
		}
		$name = '';
		if ($this->cursor->is($this->root_marker)) {
			$this->cursor->take($this->root_marker, 'root qualifier');
			$name = '\\';
		}
		while ($this->cursor->is(token_kind::identifier)) {
			$name .= $this->cursor->name(token_kind::identifier);
			if ($this->cursor->is(token_kind::scope)) {
				$this->cursor->take(token_kind::scope, '::');
				$constant_only = !$this->cursor->is(token_kind::variable);
				return $name . '::' . $this->cursor->name($constant_only ? token_kind::identifier : token_kind::variable);
			}
			if (!$this->cursor->is($this->separator)) {
				$constant_only = true;
				return $name;
			}
			$name .= '\\';
			$this->cursor->take($this->separator, 'namespace separator');
		}
		return $name . $this->cursor->name(token_kind::variable);
	}

	/** Normalize only admitted name-path syntax; JS value/member ambiguity is checked by body preparation. */
	public function path(bool $allow_root, bool $allow_group = false, bool $call = false): string
	{
		$rooted = ($allow_root) && $this->cursor->is($this->root_marker);
		if ($rooted) {
			$this->cursor->take($this->root_marker, 'root qualifier');
		}
		$name = $rooted ? '\\' : '';
		while (true) {
			// PHP tokenizes the empty call name as a keyword.
			$kind = $call && $this->cursor->keyword('empty') ? token_kind::keyword : token_kind::identifier;
			$name .= $this->cursor->name($kind);
			if (!($this->cursor->is($this->separator))) {
				break;
			}
			$this->cursor->take($this->separator, 'namespace separator');
			if ($allow_group && $this->cursor->is(token_kind::lbrace)) {
				break;
			}
			$name .= '\\';
		}
		return $name;
	}
}
