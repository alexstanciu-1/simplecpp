<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Byte scanner adapted from my-try's stage worker, with per-source flavor rules. */
final class Tokenizer
{
	private int $offset = 0;
	private const PUNCTUATION = [
		token_kind::ellipsis,
		token_kind::power_assign,
		token_kind::left_assign,
		token_kind::right_assign,
		token_kind::identical,
		token_kind::not_identical,
		token_kind::spaceship,
		token_kind::attribute,
		token_kind::equal,
		token_kind::not_equal,
		token_kind::less_equal,
		token_kind::greater_equal,
		token_kind::arrow,
		token_kind::member,
		token_kind::scope,
		token_kind::coalesce,
		token_kind::logical_and,
		token_kind::logical_or,
		token_kind::increment,
		token_kind::decrement,
		token_kind::power,
		token_kind::plus_assign,
		token_kind::minus_assign,
		token_kind::star_assign,
		token_kind::slash_assign,
		token_kind::percent_assign,
		token_kind::concat_assign,
		token_kind::and_assign,
		token_kind::or_assign,
		token_kind::xor_assign,
		token_kind::semicolon,
		token_kind::lparen,
		token_kind::rparen,
		token_kind::lbrace,
		token_kind::rbrace,
		token_kind::question,
		token_kind::colon,
		token_kind::comma,
		token_kind::ampersand,
		token_kind::lbracket,
		token_kind::rbracket,
		token_kind::less,
		token_kind::greater,
		token_kind::plus,
		token_kind::minus,
		token_kind::star,
		token_kind::slash,
		token_kind::percent,
		token_kind::dot,
		token_kind::bang,
		token_kind::tilde,
		token_kind::pipe,
		token_kind::caret,
		token_kind::backslash,
		token_kind::assign,
	];

	private readonly source_file $source;

	public function __construct(source_file $source)
	{
		$this->source = $source;
	}

	/** Publish only a complete generation; failures cannot expose partial tokens. */
	public function tokenize(): token_list
	{
		$this->offset = 0;
		$tokens = [];
		while ($this->offset < strlen($this->source->content)) {
			$this->skip_trivia();
			if ($this->peek() === '') {
				break;
			}
			$start = $this->offset;
			$kind = $this->scan_token();
			$length = $this->offset - $start;
			if ($length > 16777215) {
				throw new diagnostic($this->source, $start, 'Token length exceeds 24-bit capacity');
			}
			$tokens[] = new token($start, $length, $kind);
		}
		return new token_list($this->source, $tokens);
	}

	private function peek(): string
	{
		return $this->source->content[$this->offset] ?? '';
	}

	private function starts(string $text): bool
	{
		return substr_compare($this->source->content, $text, $this->offset, strlen($text)) === 0;
	}

	private static function letter(string $byte): bool
	{
		return ($byte === '_') || (($byte >= 'a') && ($byte <= 'z')) || (($byte >= 'A') && ($byte <= 'Z'));
	}

	private static function digit(string $byte): bool
	{
		return ($byte !== '') && (ord($byte) >= 48) && (ord($byte) <= 57);
	}

	private function name_start(string $byte): bool
	{
		return self::letter($byte) || (($this->source->flavor === flavor::js) && ($byte === '$'));
	}

	/** Trivia leaves original byte gaps intact; block comments do not nest. */
	private function skip_trivia(): void
	{
		while ($this->peek() !== '') {
			if (str_contains(" \t\r\n", $this->peek())) {
				$this->offset++;
				continue;
			}
			if ($this->starts('//') || (($this->source->flavor === flavor::php) && $this->starts('#') && !$this->starts('#['))) {
				while (($this->peek() !== '') && ($this->peek() !== "\r") && ($this->peek() !== "\n")) {
					$this->offset++;
				}
				continue;
			}
			if ($this->starts('/*')) {
				$end = strpos($this->source->content, '*/', $this->offset + 2);
				if ($end === false) {
					throw new diagnostic($this->source, $this->offset, 'Unterminated block comment');
				}
				$this->offset = $end + 2;
				continue;
			}
			break;
		}
	}

	/** Classify spelling only; no decoding, symbol lookup or PHP runtime tokenization. */
	private function scan_token(): token_kind
	{
		$start = $this->offset;
		$byte = $this->peek();
		if (($byte === '$') && ($this->source->flavor === flavor::php)) {
			$this->offset++;
			if (!self::letter($this->peek())) {
				throw new diagnostic($this->source, $start, 'Expected variable name');
			}
			while (self::letter($this->peek()) || self::digit($this->peek())) {
				$this->offset++;
			}
			return token_kind::variable;
		}
		if ($this->name_start($byte)) {
			while ($this->name_start($this->peek()) || self::digit($this->peek())) {
				$this->offset++;
			}
			return $this->source->flavor->word_kind(substr($this->source->content, $start, $this->offset - $start));
		}
		if (($byte === "'") || ($byte === '"')) {
			return $this->quoted($byte);
		}
		if (self::digit($byte) || (($byte === '.') && self::digit($this->source->content[$start + 1] ?? ''))) {
			return $this->number();
		}
		if (($byte !== '#') || ($this->source->flavor === flavor::php)) {
			foreach (self::PUNCTUATION as $kind) {
				$spelling = $kind->label();
				if ($this->starts($spelling)) {
					$this->offset += strlen($spelling);
					return $kind;
				}
			}
		}
		throw new diagnostic($this->source, $start, 'Unsupported token');
	}

	/** Preserve quotes and escapes verbatim, including arbitrary interior bytes. */
	private function quoted(string $quote): token_kind
	{
		$start = $this->offset++;
		while ($this->peek() !== '') {
			$byte = $this->peek();
			$this->offset++;
			if ($byte === $quote) {
				return $quote === "'" ? token_kind::single_string : token_kind::double_string;
			}
			if (($byte === '\\') && ($this->peek() !== '')) {
				$this->offset++;
			}
		}
		throw new diagnostic($this->source, $start, 'Unterminated quoted string');
	}

	private function digits(): void
	{
		while (self::digit($this->peek())) {
			$this->offset++;
		}
	}

	/** Keep signs separate except inside a decimal exponent; reject malformed suffixes. */
	private function number(): token_kind
	{
		$start = $this->offset;
		$kind = token_kind::integer;
		$this->digits();
		if ($this->peek() === '.') {
			$kind = token_kind::floating;
			$this->offset++;
			$this->digits();
		}
		if (($this->peek() === 'e') || ($this->peek() === 'E')) {
			$kind = token_kind::floating;
			$this->offset++;
			if (($this->peek() === '+') || ($this->peek() === '-')) {
				$this->offset++;
			}
			$exponent = $this->offset;
			$this->digits();
			if ($exponent === $this->offset) {
				throw new diagnostic($this->source, $start, 'Expected exponent digits');
			}
		}
		if (self::letter($this->peek()) || ($this->peek() === '.')) {
			throw new diagnostic($this->source, $start, 'Unsupported numeric literal');
		}
		return $kind;
	}
}
