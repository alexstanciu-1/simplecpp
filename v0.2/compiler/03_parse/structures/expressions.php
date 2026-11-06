<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Binary syntax identities, independent of token IDs; native uint8. */
enum binary_operator: int
{
	case equal = 1;
	case not_equal = 2;
	case less = 3;
	case less_equal = 4;
	case greater = 5;
	case greater_equal = 6;
	case add = 7;
	case subtract = 8;
	case multiply = 9;
	case divide = 10;
	case remainder = 11;
	case logical_and = 12;
	case logical_or = 13;
	case bit_and = 14;
	case bit_or = 15;
	case bit_xor = 16;
	case shift_left = 17;
	case shift_right = 18;
	case concatenate = 19;
	case logical_xor = 20;
	case identical = 21;
	case not_identical = 22;
	case compare = 23;
	case power = 24;

	/** Map recognized compound spellings to ordinary binary semantics; admission belongs to generated types. */
	public static function compound(token_kind $token): ?self
	{
		return match ($token) {
			token_kind::power_assign => self::power,
			token_kind::plus_assign => self::add,
			token_kind::concat_assign => self::concatenate,
			token_kind::minus_assign => self::subtract,
			token_kind::star_assign => self::multiply,
			token_kind::slash_assign => self::divide,
			token_kind::percent_assign => self::remainder,
			token_kind::and_assign => self::bit_and,
			token_kind::or_assign => self::bit_or,
			token_kind::xor_assign => self::bit_xor,
			token_kind::left_assign => self::shift_left,
			token_kind::right_assign => self::shift_right,
			default => null,
		};
	}

	/** Logical operands use each type's generated condition contract. */
	public function logical(): bool
	{
		return $this->lazy() || ($this === self::logical_xor);
	}

	public function lazy(): bool
	{
		return ($this === self::logical_and) || ($this === self::logical_or);
	}

	/** Higher levels bind more tightly; power alone associates right. */
	public function precedence(): int /** uint8 */
	{
		return match ($this) {
			self::logical_xor => 0,
			self::logical_or => 1,
			self::logical_and => 2,
			self::bit_or => 3,
			self::bit_xor => 4,
			self::bit_and => 5,
			self::equal, self::not_equal, self::identical, self::not_identical, self::compare => 6,
			self::less, self::less_equal, self::greater, self::greater_equal => 7,
			self::concatenate => 8,
			self::shift_left, self::shift_right => 9,
			self::add, self::subtract => 10,
			self::multiply, self::divide, self::remainder => 11,
			self::power => 12,
		};
	}
}

/** Owns operands of e.g. value < limit or ready && valid; inherited type readiness records successful admission. */
final class ast_binary_expression extends ast_expression
{
	/** PROPERTIES */
	/** Native owning left operand; lazy operators test its truthiness before deciding whether to evaluate the RHS. */
	public readonly ast_expression $left;
	/** Native owning right operand. */
	public readonly ast_expression $right;
	public readonly binary_operator $operator;

	/** PREP FACTS */
	/** Prepared fact: native non-owning execution type; null before settlement or after reset. */
	protected ?type_definition $execution_type = null;
	/** Prepared fact: approved left conversion; identity before settlement or after reset. */
	public value_conversion $left_conversion = value_conversion::identity;
	/** Prepared fact: approved right conversion; identity before settlement or after reset. */
	public value_conversion $right_conversion = value_conversion::identity;

	/** CONSTRUCTOR */

	/** Attach both source operands once; semantic links remain on their original occurrences. */
	public function __construct(ast_expression $left, binary_operator $operator, ast_expression $right)
	{
		parent::__construct($left->start, $right->end);
		$this->left = $left;
		$this->right = $right;
		$this->operator = $operator;
		$left->attach($this);
		$right->attach($this);
	}

	/** METHODS */
	/** Settles the approved result identity; no constant folding or duplicate prepared tree. */
	public function settle_binary(type_definition $result, ?type_definition $execution = null,
		value_conversion $left = value_conversion::identity, value_conversion $right = value_conversion::identity): void
	{
		$this->settle_value($result, null);
		$this->execution_type = $execution ?? $this->left->type_def();
		$this->left_conversion = $left;
		$this->right_conversion = $right;
	}
	public function execution_type(): type_definition
	{
		return $this->execution_type ?? throw new \LogicException('Binary operation is not prepared');
	}

	public function reset_preparation(): void
	{
		parent::reset_preparation();
		$this->execution_type = null;
		$this->left_conversion = value_conversion::identity;
		$this->right_conversion = value_conversion::identity;
	}
}

/** Numeric prefix syntax identities, independent of token IDs; native uint8. */
enum unary_operator: int
{
	case positive = 1;
	case negative = 2;
	case bit_not = 3;
}

/** Owns the operand of e.g. -count(); inherited readiness stores only the approved result type. */
final class ast_unary_expression extends ast_expression
{
	public readonly unary_operator $operator;
	/** Native owning operand; the result is a value, never assignable storage. */
	public readonly ast_expression $operand;

	/** $start/$end: token positions spanning the sign and operand, including operand grouping. */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */, unary_operator $operator,
		ast_expression $operand)
	{
		parent::__construct($start, $end);
		$this->operator = $operator;
		$this->operand = $operand;
		$operand->attach($this);
	}

	/** No constant folding or duplicate prepared operand. */
	public function settle_unary(type_definition $type): void
	{
		$this->settle_value($type, null);
	}
}

/** Logical negation, e.g. !ready(); owns one operand and retains only the inherited bool result fact. */
final class ast_not_expression extends ast_expression
{
	/** Native owning operand; interpreted through its generated condition handler. */
	public readonly ast_expression $operand;

	/** $start: position of !; $end: exclusive token position including operand grouping. */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */, ast_expression $operand)
	{
		parent::__construct($start, $end);
		$this->operand = $operand;
		$operand->attach($this);
	}

	public function settle_not(): void
	{
		$this->settle_value(type_bool::instance(), null);
	}
}

/** Explicit value conversion, e.g. (byte) count; owns syntax and caches only the approved conversion. */
final class ast_cast_expression extends ast_expression
{
	/** PROPERTIES */
	public readonly string $type_name;
	/** Native owning operand; a cast produces a value, never mutable-reference storage. */
	public readonly ast_expression $operand;

	/** PREP FACTS */
	/** Prepared fact: generated conversion decision; null before preparation or after reset. */
	protected ?value_conversion $conversion = null;

	/** CONSTRUCTOR */
	/** $start/$end: token positions spanning the written target and operand, including operand grouping. */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */, string $type_name,
		ast_expression $operand)
	{
		parent::__construct($start, $end);
		$this->type_name = $type_name;
		$this->operand = $operand;
		$operand->attach($this);
	}

	/** METHODS */
	/** Keep converted scalar facts on this occurrence; the generated destination owns conversion behavior. */
	public function settle_cast(type_definition $type, value_conversion $conversion): void
	{
		$this->settle_value($type, $type->convert_constant($conversion, $this->operand->constant()),
			$this->operand->constant_known());
		$this->conversion = $conversion;
	}

	public function conversion(): value_conversion
	{
		return $this->conversion ?? throw new \LogicException('Cast conversion is not settled');
	}

	public function reset_preparation(): void
	{
		parent::reset_preparation();
		$this->conversion = null;
	}
}
