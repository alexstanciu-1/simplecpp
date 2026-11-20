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
	case coalesce = 25;

	/** Map recognized compound spellings to ordinary binary semantics; admission belongs to generated types. */
	public static function compound(token_kind $token): ?self
	{
		return match ($token) {
			token_kind::coalesce_assign => self::coalesce,
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

	/** Higher levels bind more tightly; power and coalesce associate right. */
	public function precedence(): int /** uint8 */
	{
		return match ($this) {
			self::logical_xor => 0,
			self::coalesce => 2,
			self::logical_or => 3,
			self::logical_and => 4,
			self::bit_or => 5,
			self::bit_xor => 6,
			self::bit_and => 7,
			self::equal, self::not_equal, self::identical, self::not_identical, self::compare => 8,
			self::less, self::less_equal, self::greater, self::greater_equal => 9,
			self::concatenate => 10,
			self::shift_left, self::shift_right => 11,
			self::add, self::subtract => 12,
			self::multiply, self::divide, self::remainder => 13,
			self::power => 14,
		};
	}
}

/** Owns operands of e.g. value < limit or ready && valid; inherited type readiness records successful admission. */
final class ast_binary_expression extends ast_expression
{
	/** PROPERTIES */
	/** Native owning left operand; lazy operators test its truthiness or presence before deciding whether to evaluate the RHS. */
	public readonly ast_expression $left;
	/** Native owning right operand. */
	public readonly ast_expression $right;
	public readonly binary_operator $operator;

	/** PREP FACTS */
	/** Prepared fact: native non-owning execution type; null before settlement or after reset. */
	protected ?type_definition $execution_type = null;
	/** Prepared fact: approved left conversion; identity before settlement or after reset. */
	public int $left_conversion /** uint16 */ = value_conversion::identity;
	/** Prepared fact: approved right conversion; identity before settlement or after reset. */
	public int $right_conversion /** uint16 */ = value_conversion::identity;

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
		int $left /** uint16 */ = value_conversion::identity, int $right /** uint16 */ = value_conversion::identity): void
	{
		$this->settle_value($result, null);
		$this->execution_type = $execution ?? $this->left->value_type();
		$uses_payload = !$this->operator->logical() && !($this->execution_type instanceof carrier_type_definition);
		$this->left_conversion = $left | ($uses_payload && $this->left->type_def() instanceof carrier_type_definition ? value_conversion::EXTRACT : 0);
		$this->right_conversion = $right;
		if ($uses_payload && !($this->right instanceof ast_throw_expression) && $this->right->type_def() instanceof carrier_type_definition) {
			$this->right_conversion |= value_conversion::EXTRACT;
		}
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
	/** Native owning written type syntax. */
	public readonly ast_type_reference $type_reference;
	/** Native owning operand; a cast produces a value, never mutable-reference storage. */
	public readonly ast_expression $operand;

	/** PREP FACTS */
	/** Prepared fact: generated conversion decision; null before preparation or after reset. */
	protected ?int $conversion /** uint16 */ = null;

	/** CONSTRUCTOR */
	/** $start/$end: token positions spanning the written target and operand, including operand grouping. */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */, ast_type_reference $type_reference,
		ast_expression $operand)
	{
		parent::__construct($start, $end);
		$this->type_reference = $type_reference;
		$type_reference->attach($this);
		$this->operand = $operand;
		$operand->attach($this);
	}

	/** METHODS */
	/** Keep converted scalar facts on this occurrence; the generated destination owns conversion behavior. */
	public function settle_cast(type_definition $type, int $conversion /** uint16 */): void
	{
		$this->settle_value($type, $type->evaluate_constant($conversion, $this->operand), true);
		$this->conversion = $conversion;
	}

	public function conversion(): int /** uint16 */
	{
		return $this->conversion ?? throw new \LogicException('Cast conversion is not settled');
	}

	public function reset_preparation(): void
	{
		parent::reset_preparation();
		$this->conversion = null;
	}
}

/** Presence query, e.g. isset(value); retains its carrier operand without extracting the payload. */
final class ast_isset_expression extends ast_expression
{
	/** Native owning queried expression. */
	public readonly ast_expression $operand;

	/** $start/$end: token positions including the keyword and closing parenthesis. */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */, ast_expression $operand)
	{
		parent::__construct($start, $end);
		$this->operand = $operand;
		$operand->attach($this);
	}

	public function settle_presence(): void
	{
		$this->settle_value(type_bool::instance(), null);
	}
}

/** A parenthesized assignment tested by a control statement, e.g. while ((n = read()) >= 0). */
final class ast_condition_assignment extends ast_expression
{
	public private(set) ?int $conversion /** uint16 */ = null;

	public function __construct(int $start /** uint32 */, int $end /** uint32 */, public readonly ast_name_expression $target,
		public readonly ast_expression $value)
	{
		parent::__construct($start, $end);
		$target->attach($this);
		$value->attach($this);
	}

	/** Follow only predicate operators up to the controlling statement. */
	public function in_condition(): bool
	{
		$node = $this;
		while (true) {
			$parent = $node->parent;
			if ($parent instanceof ast_binary_expression) {
				if (!($parent->operator->lazy() || in_array($parent->operator, [
					binary_operator::equal, binary_operator::not_equal, binary_operator::identical, binary_operator::not_identical,
					binary_operator::less, binary_operator::less_equal, binary_operator::greater, binary_operator::greater_equal,
				], true))) break;
			} elseif (!($parent instanceof ast_not_expression || $parent instanceof ast_isset_expression)) break;
			$node = $parent;
		}
		return (($parent instanceof ast_if_arm || $parent instanceof ast_tested_loop) && $parent->condition === $node)
			|| ($parent instanceof ast_for_statement && ($parent->conditions[array_key_last($parent->conditions)] ?? null) === $node);
	}

	public function settle_assignment(ast_local_declaration $local, int $conversion /** uint16 */): void
	{
		$this->target->settle_local($local, unknown: true);
		$this->settle_value($local->type_def(), null);
		$this->conversion = $conversion;
	}

	public function reset_preparation(): void
	{
		parent::reset_preparation();
		$this->conversion = null;
	}
}
