<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Adds logical line and suite boundaries to the shared scanner's Python lexemes. */
final class Python_Layout
{
	/** @var int[] */
	private array $levels /** list<uint32> */ = [0];
	/** @var token_kind[] */
	private array $closers /** list<token_kind> */ = [];
	private int $line_start /** uint32 */ = 0;
	private bool $pending = false;

	public function __construct(private readonly source_file $source)
	{
	}

	/** @param token[] $tokens */
	public function trivia(array &$tokens, string $word, int $offset): void
	{
		if ($word === "\r") throw new diagnostic($this->source, $offset, 'Expected LF after carriage return');
		if ($word !== "\n" && $word !== "\r\n") return;
		if ($this->closers === [] && $this->pending) {
			$tokens[] = new token($offset, strlen($word), token_kind::newline);
			$this->pending = false;
		}
		$this->line_start = $offset + strlen($word);
	}

	/** @param token[] $tokens */
	public function before_token(array &$tokens, token_kind $kind, string $word, int $offset): void
	{
		if (strpbrk($word, "\r\n") !== false) {
			throw new diagnostic($this->source, $offset, 'Multiline Python strings are deferred');
		}
		if (!$this->pending) $this->indentation($tokens, $offset);
		$this->pending = true;
		$closer = match ($kind) {
			token_kind::lparen => token_kind::rparen,
			token_kind::lbracket => token_kind::rbracket,
			token_kind::lbrace => token_kind::rbrace,
			default => null,
		};
		if ($closer !== null) {
			$this->closers[] = $closer;
		}
		elseif (in_array($kind, [token_kind::rparen, token_kind::rbracket, token_kind::rbrace], true)
			&& array_pop($this->closers) !== $kind) {
			throw new diagnostic($this->source, $offset, 'Mismatched Python delimiter');
		}
	}

	/** @param token[] $tokens */
	private function indentation(array &$tokens, int $offset): void
	{
		$width = $offset - $this->line_start;
		$tab = strpos(substr($this->source->content, $this->line_start, $width), "\t");
		if ($tab !== false) throw new diagnostic($this->source, $this->line_start + $tab, 'Tab in Python indentation');
		if ($width > end($this->levels)) {
			$this->levels[] = $width;
			$tokens[] = new token($offset, 0, token_kind::indent);
			return;
		}
		while ($width < end($this->levels)) {
			array_pop($this->levels);
			$tokens[] = new token($offset, 0, token_kind::dedent);
		}
		if ($width !== end($this->levels)) throw new diagnostic($this->source, $this->line_start, 'Unmatched Python dedent');
	}

	/** @param token[] $tokens */
	public function finish(array &$tokens, int $offset): void
	{
		if ($this->closers !== []) throw new diagnostic($this->source, $offset, 'Unclosed Python delimiter');
		if ($this->pending) $tokens[] = new token($offset, 0, token_kind::newline);
		for ($level = count($this->levels) - 1; $level > 0; --$level) {
			$tokens[] = new token($offset, 0, token_kind::dedent);
		}
	}
}
