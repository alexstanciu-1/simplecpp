<?php

declare(strict_types=1);
namespace scpp\compiler;

/** One instance field in the record layout, e.g. private int $x; */
final class ast_struct_field extends ast_node implements ast_declaration_interface
{
	use ast_declaration;

	/** PROPERTIES */
	/** Native owning written field type. */
	public readonly ast_type_reference $type_reference;
	/** Native owning optional literal written on the field. */
	public readonly ?ast_expression $initializer;
	public readonly bool $is_private;

	/** PREP FACTS */
	/** Prepared fact: typed destination conversion for the written literal. */
	public int $conversion /** uint16 */ = value_conversion::identity;
	/** Prepared fact: canonical type once the immutable declaration layout has been validated. */
	private ?type_definition $type_def = null;

	/** CONSTRUCTOR */
	/** Retain the written visibility and attach the field type to its declaration. */
	public function __construct(string $name, ast_type_reference $type, int $start /** uint32 */, int $end /** uint32 */, bool $private,
		?ast_expression $initializer = null)
	{
		parent::__construct($start, $end);
		$this->is_private = $private;
		$this->short_name = $name;
		$this->namespace = null;
		$this->type_reference = $type;
		$type->attach($this);
		$this->initializer = $initializer;
		$initializer?->attach($this);
	}

	/** METHODS */
	/** Publish the resolved field type, or release the previous generation’s type on invalidation. */
	public function settle_type(?type_definition $type): void
	{
		$this->type_def = $type;
	}

	public function type_def(): type_definition
	{
		return $this->type_def ?? throw new \LogicException('Struct field type is not prepared');
	}

	public function symbol_key(): string
	{
		return $this->short_name;
	}
}

/** Source-owned value type and ordered field index, e.g. struct Point { public int $x; }. */
final class ast_struct_declaration extends ast_node implements ast_declaration_interface
{
	use ast_declaration;

	/** PROPERTIES */
	/** @var ast_struct_field[] Fields in layout order, indexed by spelling. */
	public readonly array $fields /** array<string,ast_struct_field> */;
	/** @var ast_function_declaration[] First public method by source spelling. */
	public readonly array $methods /** array<string,ast_function_declaration> */;
	/** @var ast_constant_declaration[] Public constants indexed independently of fields and methods. */
	public readonly array $constants /** array<string,ast_constant_declaration> */;
	/** @var ast_global_declaration[] Static properties share one storage location per declaring type. */
	public readonly array $static_properties /** array<string,ast_global_declaration> */;
	/** Native owning canonical type for this declaration generation. */
	public readonly struct_type_definition $definition;

	/** CONSTRUCTOR */
	/**
	 * @param ast_struct_field[] $fields
	 * @param (ast_function_declaration|ast_constant_declaration|ast_global_declaration)[] $members All occurrences in source order before duplicate publication.
	 */
	public function __construct(string $name, ?string $namespace, array $fields /** array<string,ast_struct_field> */,
		int $start /** uint32 */, int $end /** uint32 */, array $members /** list<ast_function_declaration|ast_constant_declaration|ast_global_declaration> */ = [])
	{
		parent::__construct($start, $end);
		$this->short_name = $name;
		$this->namespace = $namespace;
		$methods = [];
		$constants = [];
		$properties = [];
		foreach ($members as $member) {
			$member->attach($this);
			if ($member instanceof ast_constant_declaration) $constants[$member->short_name] ??= $member;
			elseif ($member instanceof ast_global_declaration) $properties[$member->short_name] ??= $member;
			else $methods[$member->short_name] ??= $member;
		}
		$this->methods = $methods;
		$this->constants = $constants;
		$this->static_properties = $properties;
		$this->fields = $fields;
		foreach ($fields as $field) {
			for ($node = $field; $node !== null; $node = $node->duplicate_element()) {
				$node->attach($this);
			}
		}
		$this->definition = new struct_type_definition($this);
	}

	/** METHODS */
	public function symbol_key(): string
	{
		return 'class:' . $this->name();
	}

	public function name_position(): int /** uint32 */
	{
		return $this->start + 1;
	}
}

/** Ordered initialization operands and their prepared conversions; the payload owns the constructor declaration. */
abstract class ast_construction_expression extends ast_expression
{
	/** PROPERTIES */
	/** @var ast_expression[] Written arguments in evaluation order. */
	public readonly array $arguments /** list<ast_expression> */;

	/** PREP FACTS */
	/** Prepared fact: positional conversions. @var int[]. */
	private array $conversions /** list<uint16> */ = [];

	/** CONSTRUCTOR */
	/** @param ast_expression[] $arguments */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */, array $arguments /** list<ast_expression> */ = [])
	{
		parent::__construct($start, $end);
		$this->arguments = $arguments;
		foreach ($arguments as $argument) $argument->attach($this);
	}

	/** METHODS */
	/** @param int[] $conversions */
	public function settle_construction(type_definition $type, array $conversions /** list<uint16> */): void
	{
		$this->conversions = $conversions;
		$this->settle_value($type, null);
	}

	public function conversion(int $index /** uint32 */): int /** uint16 */
	{
		return $this->conversions[$index];
	}

	public function reset_preparation(): void
	{
		parent::reset_preparation();
		$this->conversions = [];
	}
}

/** Inline declaration initializer, e.g. $p Point; or $p Point(1, 2); */
final class ast_struct_default extends ast_construction_expression
{
}

/** Shared construction, e.g. new Point(1, 2). The inherited expression fact holds shared<Point>. */
final class ast_new_expression extends ast_construction_expression
{
	/** Native owning written payload type. */
	public readonly ast_type_reference $target;

	/** @param ast_expression[] $arguments */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */, ast_type_reference $target, array $arguments /** list<ast_expression> */ = [])
	{
		parent::__construct($start, $end, $arguments);
		$this->target = $target;
		$target->attach($this);
	}

}

/** Whole-value field access, e.g. $p->x or p.x. */
final class ast_field_expression extends ast_expression
{
	/** Native owning receiver. */
	public readonly ast_expression $receiver;
	public readonly string $field;

	public function __construct(ast_expression $receiver, string $field, int $end /** uint32 */)
	{
		parent::__construct($receiver->start, $end);
		$this->receiver = $receiver;
		$this->field = $field;
		$receiver->attach($this);
	}

	public function settle_field(type_definition $type): void
	{
		$this->settle_value($type, null);
	}
}

/** Plain property write to an instance field or type-owned storage, e.g. Point::$count = 3; */
final class ast_field_assignment extends ast_statement
{
	/** Native owning instance selection or qualified static property name. */
	public readonly ast_field_expression|ast_name_expression $target;
	/** Native owning assigned expression. */
	public readonly ast_expression $value;

	/** Prepared fact: assignment conversion, cleared before each preparation. */
	public ?int $conversion /** uint16 */ = null;

	public function __construct(ast_field_expression|ast_name_expression $target, ast_expression $value, int $end /** uint32 */)
	{
		parent::__construct($target->start, $end);
		$this->target = $target;
		$this->value = $value;
		$target->attach($this);
		$value->attach($this);
	}
}

/** Explicit one-operand ownership operation, e.g. lock(weak(makePoint())). */
final class ast_ownership_expression extends ast_expression
{
	public readonly ast_expression $operand;
	public readonly bool $lock;

	public function __construct(int $start /** uint32 */, int $end /** uint32 */, ast_expression $operand, bool $lock)
	{
		parent::__construct($start, $end);
		$this->operand = $operand;
		$this->lock = $lock;
		$operand->attach($this);
	}

	/** Read only the operand's settled handle identity; both directions reuse its canonical payload. */
	public function prepare(): void
	{
		$type = $this->operand->type_def();
		if ($this->lock ? !($type instanceof weak_struct_type_definition) : !($type instanceof shared_struct_type_definition)) {
			throw new diagnostic($this->source(), $this->byte_start(), $this->lock ? 'lock() requires weak<T>' : 'weak() requires shared<T>');
		}
		$this->settle_value($this->lock ? $type->payload->shared : $type->payload->weak, null);
	}
}

/** Borrowed payload at a method's receiver occurrence, e.g. $this in $this->x. */
final class ast_this_expression extends ast_expression
{
	public function settle_receiver(struct_type_definition $record): void
	{
		$this->settle_value($record, null);
	}
}
