<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Closed lexical vocabulary with explicit IDs in 1..127; zero is reserved. */
enum token_kind: int
{
	case identifier = 1;
	case variable = 2;
	case integer = 3;
	case floating = 4;
	case single_string = 5;
	case double_string = 6;

	case ellipsis = 7;
	case power_assign = 8;
	case left_assign = 9;
	case right_assign = 10;
	case identical = 11;
	case not_identical = 12;
	case spaceship = 13;
	case attribute = 14;
	case equal = 15;
	case not_equal = 16;
	case less_equal = 17;
	case greater_equal = 18;
	case arrow = 19;
	case member = 20;
	case scope = 21;
	case coalesce = 22;
	case logical_and = 23;
	case logical_or = 24;
	case increment = 25;
	case decrement = 26;
	case power = 27;
	case plus_assign = 28;
	case minus_assign = 29;
	case star_assign = 30;
	case slash_assign = 31;
	case percent_assign = 32;
	case concat_assign = 33;
	case and_assign = 34;
	case or_assign = 35;
	case xor_assign = 36;
	case semicolon = 37;
	case lparen = 38;
	case rparen = 39;
	case lbrace = 40;
	case rbrace = 41;
	case question = 42;
	case colon = 43;
	case comma = 44;
	case ampersand = 45;
	case lbracket = 46;
	case rbracket = 47;
	case less = 48;
	case greater = 49;
	case plus = 50;
	case minus = 51;
	case star = 52;
	case slash = 53;
	case percent = 54;
	case dot = 55;
	case bang = 56;
	case tilde = 57;
	case pipe = 58;
	case caret = 59;
	case backslash = 60;
	case assign = 61;
	case keyword = 62;

	/** Keep presentation and punctuation spellings independent of the numeric IDs. */
	public function label(): string
	{
		return match ($this) {
			self::identifier => 'identifier',
			self::variable => 'variable',
			self::integer => 'integer',
			self::floating => 'floating',
			self::single_string => 'single_string',
			self::double_string => 'double_string',
			self::ellipsis => '...',
			self::power_assign => '**=',
			self::left_assign => '<<=',
			self::right_assign => '>>=',
			self::identical => '===',
			self::not_identical => '!==',
			self::spaceship => '<=>',
			self::attribute => '#[',
			self::equal => '==',
			self::not_equal => '!=',
			self::less_equal => '<=',
			self::greater_equal => '>=',
			self::arrow => '=>',
			self::member => '->',
			self::scope => '::',
			self::coalesce => '??',
			self::logical_and => '&&',
			self::logical_or => '||',
			self::increment => '++',
			self::decrement => '--',
			self::power => '**',
			self::plus_assign => '+=',
			self::minus_assign => '-=',
			self::star_assign => '*=',
			self::slash_assign => '/=',
			self::percent_assign => '%=',
			self::concat_assign => '.=',
			self::and_assign => '&=',
			self::or_assign => '|=',
			self::xor_assign => '^=',
			self::semicolon => ';',
			self::lparen => '(',
			self::rparen => ')',
			self::lbrace => '{',
			self::rbrace => '}',
			self::question => '?',
			self::colon => ':',
			self::comma => ',',
			self::ampersand => '&',
			self::lbracket => '[',
			self::rbracket => ']',
			self::less => '<',
			self::greater => '>',
			self::plus => '+',
			self::minus => '-',
			self::star => '*',
			self::slash => '/',
			self::percent => '%',
			self::dot => '.',
			self::bang => '!',
			self::tilde => '~',
			self::pipe => '|',
			self::caret => '^',
			self::backslash => '\\',
			self::assign => '=',
			self::keyword => 'keyword',
		};
	}

}

final readonly class token
{
	public int $offset;
	public int $length;
	public token_kind $kind;

	public function __construct(int $offset, int $length, token_kind $kind)
	{
		$this->offset = $offset;
		$this->length = $length;
		$this->kind = $kind;

		if (($offset < 0) || ($offset > 4294967295) || ($length < 1) || ($length > 16777215)) {
			throw new \InvalidArgumentException('Invalid token span');
		}
	}
}

/** Published tokens retain their source and its flavor; scanning state stays in the worker. */
final readonly class token_list
{
	public source_file $source;
	public array $tokens;

	/** @param list<token> $tokens */
	public function __construct(source_file $source, array $tokens)
	{
		$this->source = $source;
		$this->tokens = $tokens;

		if (!array_is_list($tokens)) {
			throw new \InvalidArgumentException('Tokens must be a list');
		}
		$end = 0;
		foreach ($tokens as $token) {
			if (!($token instanceof token) || ($token->offset < $end) || (($token->offset + $token->length) > strlen($source->content))) {
				throw new \InvalidArgumentException('Tokens must be ordered, nonoverlapping and contained');
			}
			$end = $token->offset + $token->length;
		}
	}

	public function text_at(int $index): string
	{
		$token = $this->tokens[$index] ?? throw new \OutOfRangeException('Token index outside list');
		return substr($this->source->content, $token->offset, $token->length);
	}

	/** Adjacent angles can spell shifts without losing individual generic delimiters. */
	public function operator_text_at(int $index): string
	{
		$text = $this->text_at($index);
		$first = $this->tokens[$index];
		$next = $this->tokens[$index + 1] ?? null;
		if ((($text === '<') || ($text === '>')) && ($next !== null)
			&& ($next->offset === ($first->offset + $first->length)) && ($this->text_at($index + 1) === $text)) {
			return $text . $text;
		}
		return $text;
	}
}
