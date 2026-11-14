<?php

declare(strict_types=1);
namespace scpp\compiler;

/** UTF-8 bulk lexer for the PHP bootstrap; preserves byte spans and flavor-specific lexical rules. */
final class Tokenizer
{
	/** CONSTANTS */
	private const PUNCTUATION /** list<token_kind> */ = [
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

	/** STATIC PROPERTIES */
	/**
	 * Built once per process; no source state is retained in these lookup tables.
	 * Maps each flavor to its PCRE pattern.
	 *
	 * @var string[]
	 */
	private static array $patterns /** array<string,string> */ = [];
	/** @var token_kind[] */
	private static array $punctuation /** array<string,token_kind> */ = [];

	/** PROPERTIES */
	/** Native non-owning; source retained by the caller during tokenization. */
	private readonly source_file $source;

	/** CONSTRUCTOR */
	public function __construct(source_file $source)
	{
		$this->source = $source;
	}

	/** METHODS */
	/** Publish only a complete generation; UTF-8 or lexical failures expose no partial tokens. */
	public function tokenize(): token_list
	{
		$source = $this->source;
		$pattern = self::pattern($source->flavor);
		$result = preg_match_all($pattern, $source->content, $matches, \PREG_OFFSET_CAPTURE);
		if ($result === false) {
			if (preg_last_error() === \PREG_BAD_UTF8_ERROR) {
				throw new diagnostic($source, self::invalid_utf8_offset($source->content),
					'Invalid UTF-8 source');
			}
			throw new diagnostic($source, 0, 'Tokenizer regular expression failed: ' . preg_last_error_msg());
		}
		$tokens = [];
		$end = 0;
		foreach ($matches[0] as [$word, $start])
		{
			if ($start !== $end) {
				throw new diagnostic($source, $end, 'Unsupported token');
			}
			$length = strlen($word);
			$end = $start + $length;
			$first = $word[0];
			if (str_contains(" \t\r\n", $first) || str_starts_with($word, '//')
				|| (($source->flavor === flavor::php) && ($first === '#') && (!str_starts_with($word,
							'#[')))) {
				continue;
			}
			if (str_starts_with($word, '/*')) {
				if (($length < 4) || (!str_ends_with($word, '*/'))) {
					throw new diagnostic($source, $start, 'Unterminated block comment');
				}
				continue;
			}
			if ((($first === '`') && ($source->flavor === flavor::js)) || str_starts_with($word, 'f"')) {
				if (!self::closed_quote($word, $first === 'f' ? 1 : 0)) {
					throw new diagnostic($source, $start, 'Unterminated interpolated string');
				}
				$kind = token_kind::template_string;
			}
			elseif (($first === '"') || ($first === "'")) {
				if (!self::closed_quote($word)) {
					throw new diagnostic($source, $start, 'Unterminated quoted string');
				}
				$kind = $first === '"' ? token_kind::double_string : token_kind::single_string;
			}
			elseif (($first === '$') && ($source->flavor === flavor::php)) {
				if ($length === 1) {
					throw new diagnostic($source, $start, 'Expected variable name');
				}
				$kind = token_kind::variable;
			}
			elseif ((($first >= '0') && ($first <= '9')) || (($first === '.') && ($length > 1) && ($word[1] >= '0')
					&& ($word[1] <= '9')))
			{
				// Prefixed integers must be classified before hexadecimal e/E can look like an exponent.
				$prefixed = $length >= 2 && $word[0] === '0' && str_contains('xXoO', $word[1]);
				if ($prefixed && $length === 2) {
					throw new diagnostic($source, $start, 'Expected integer digits after radix prefix');
				}
				// The regex retains incomplete exponents so errors stay anchored at the number's start.
				$last = $word[$length - 1];
				if (!$prefixed && str_contains('eE+-', $last)) {
					throw new diagnostic($source, $start, 'Expected exponent digits');
				}
				$next = $source->content[$end] ?? '';
				if (($next === '.') || self::letter($next) || ($prefixed && $next >= '0' && $next <= '9')) {
					throw new diagnostic($source, $start, 'Unsupported numeric literal');
				}
				$kind = $prefixed || strpbrk($word, '.eE') === false ? token_kind::integer : token_kind::floating;
			}
			elseif (isset(self::$punctuation[$word]) && (($first !== '#') || ($source->flavor === flavor::php))) {
				$kind = self::$punctuation[$word];
			}
			elseif (self::letter($first) || (($first === '$') && ($source->flavor === flavor::js))) {
				$kind = $source->flavor->word_kind($word);
			}
			else {
				throw new diagnostic($source, $start, 'Unsupported token');
			}
			if ($length > 16777215) {
				throw new diagnostic($source, $start, 'Token length exceeds 24-bit capacity');
			}
			$tokens[] = new token($start, $length, $kind);
		}
		if ($end !== strlen($source->content)) {
			throw new diagnostic($source, $end, 'Unsupported token');
		}
		return new token_list($source, $tokens);
	}

	/** STATIC METHODS */
	/** A failed quote match consumes the remaining source once, avoiding repeated suffix scans. */
	private static function closed_quote(string $word, int $opening /** uint8 */ = 0): bool
	{
		$last = strlen($word) - 1;
		if (($last === $opening) || ($word[$last] !== $word[$opening])) {
			return false;
		}
		$index = $last - 1;
		while (($index > 0) && ($word[$index] === '\\')) {
			$index--;
		}
		return (($last - 1 - $index) % 2) === 0;
	}

	private static function letter(string $byte): bool
	{
		return ($byte === '_') || (($byte >= 'a') && ($byte <= 'z')) || (($byte >= 'A') && ($byte <= 'Z'));
	}

	/** Match complete lexemes, trivia and error openers; the final alternative consumes unsupported code points. */
	private static function pattern(flavor $flavor): string
	{
		if (isset(self::$patterns[$flavor->value])) {
			return self::$patterns[$flavor->value];
		}
		$parts = [];
		foreach (self::PUNCTUATION as $kind) {
			$spelling = $kind->label();
			self::$punctuation[$spelling] = $kind;
			if (($kind !== token_kind::attribute) || ($flavor === flavor::php)) {
				$parts[] = preg_quote($spelling, '~');
			}
		}
		$trivia = '[ \t\r\n]++|//[^\r\n]*+|/\*[^*]*+(?:\*(?!/)[^*]*+)*+\*/|/\*[\s\S]*+\z';
		if ($flavor === flavor::php) {
			$trivia .= '|\#(?!\[)[^\r\n]*+';
		}
		$strings = <<<'REGEX'
"[^"\\]*+(?:\\[\s\S][^"\\]*+)*+"|'[^'\\]*+(?:\\[\s\S][^'\\]*+)*+'
REGEX;
		if ($flavor === flavor::cpp) {
			$strings = 'f?' . $strings . '|f"[\s\S]*+\z';
		}
		elseif ($flavor === flavor::js) {
			$strings .= '|' . str_replace('"', '`', strstr($strings, '|', true)) . '|`[\s\S]*+\z';
		}
		$names = match ($flavor) {
			flavor::php => '\$[A-Za-z_][A-Za-z_0-9]*+|[A-Za-z_][A-Za-z_0-9]*+',
				flavor::cpp => '[A-Za-z_][A-Za-z_0-9]*+',
				flavor::js => '[A-Za-z_$][A-Za-z_$0-9]*+',
		};
		$numbers = '0[xX][0-9a-fA-F]*+|0[oO][0-7]*+|(?:[0-9]++(?:\.[0-9]*+)?|\.[0-9]++)(?:[eE][+-]?[0-9]*+)?';
		return self::$patterns[$flavor->value] = '~(?:' . $trivia . '|' . $strings . '|' . $names . '|'
			. $numbers . '|' . implode('|', $parts) . '|["\'][\s\S]*+\z|[\s\S])~u';
	}

	/**
	 * Slow failure-only locator: report the start of the first invalid UTF-8 sequence, not a PCRE
	 * offset guess.
	 *
	 * Return: byte offset.
	 */
	private static function invalid_utf8_offset(string $text): int /** uint32 */
	{
		$size = strlen($text);
		for ($offset = 0; $offset < $size;)
		{
			$lead = ord($text[$offset]);
			if ($lead < 0x80) {
				$offset++;
				continue;
			}
			$length = match (true) {
				($lead >= 0xC2) && ($lead <= 0xDF) => 2,
					($lead >= 0xE0) && ($lead <= 0xEF) => 3,
					($lead >= 0xF0) && ($lead <= 0xF4) => 4,
					default => 0,
			};
			if (($length === 0) || ($offset + $length > $size)) {
				return $offset;
			}
			for ($index = 1; $index < $length; $index++) {
				$byte = ord($text[$offset + $index]);
				if (($byte < 0x80) || ($byte > 0xBF)) {
					return $offset;
				}
			}
			$second = ord($text[$offset + 1]);
			if ((($lead === 0xE0) && ($second < 0xA0)) || (($lead === 0xED) && ($second > 0x9F))
				|| (($lead === 0xF0) && ($second < 0x90)) || (($lead === 0xF4) && ($second > 0x8F))) {
				return $offset;
			}
			$offset += $length;
		}
		throw new \LogicException('PCRE reported invalid UTF-8 for valid source');
	}
}
