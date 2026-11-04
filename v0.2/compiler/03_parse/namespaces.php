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

	public function call_start(): bool
	{
		return $this->cursor->is($this->root_marker)
			|| ($this->cursor->is(token_kind::identifier)
				&& ($this->cursor->next_is(token_kind::lparen) || $this->cursor->next_is($this->separator)));
	}

	/** PHP qualifies the final variable token (test\$value); other flavors qualify ordinary identifiers. */
	public function variable_path(): string
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
			$name .= $this->cursor->name(token_kind::identifier) . '\\';
			$this->cursor->take($this->separator, 'namespace separator before variable');
		}
		return $name . $this->cursor->name(token_kind::variable);
	}

	/** Normalize only admitted name-path syntax; JS value/member ambiguity is checked by body preparation. */
	public function path(bool $allow_root, bool $allow_group = false): string
	{
		$rooted = ($allow_root) && $this->cursor->is($this->root_marker);
		if ($rooted) {
			$this->cursor->take($this->root_marker, 'root qualifier');
		}
		$name = ($rooted ? '\\' : '') . $this->cursor->name(token_kind::identifier);
		while ($this->cursor->is($this->separator)) {
			$this->cursor->take($this->separator, 'namespace separator');
			if ($allow_group && $this->cursor->is(token_kind::lbrace)) {
				break;
			}
			$name .= '\\' . $this->cursor->name(token_kind::identifier);
		}
		return $name;
	}
}
