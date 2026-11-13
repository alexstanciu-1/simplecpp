<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Nominal identity of an application-defined inline struct. Whole-value copies use memberwise assignment. */
final class struct_type_definition extends type_definition implements dependency_watcher
{
	/** PROPERTIES */
	/** Native non-owning defining AST. */
	public readonly ast_struct_declaration $declaration;
	/** Canonical shared identity published alongside the value identity before body preparation. */
	public readonly shared_struct_type_definition $shared;
	public readonly weak_struct_type_definition $weak;

	/** Native non-owning publication context for relaying layout changes to embedding types and body readers. */
	private ?Dependency_Watches $dependencies = null;
	private ?symbol_entry $symbol = null;
	private string $source_key = '';

	/** PREP FACTS */
	/** Prepared fact: all field names and types have been validated. */
	private bool $ready = false;

	/** CONSTRUCTOR */
	public function __construct(ast_struct_declaration $declaration)
	{
		parent::__construct($declaration->name());
		$this->declaration = $declaration;
		$this->shared = new shared_struct_type_definition($this);
		$this->weak = new weak_struct_type_definition($this);
	}

	/** METHODS */
	/** Bind the declaration to this publication before following any forward field references. */
	public function bind_layout(string $source_key, symbol_entry $symbol, Dependency_Watches $dependencies): void
	{
		if ($this->dependencies === $dependencies && $this->symbol === $symbol && $this->source_key === $source_key) return;
		$this->dependencies?->forget($this);
		$this->ready = false;
		$this->symbol = $symbol;
		$this->source_key = $source_key;
		$this->dependencies = $dependencies;
	}

	public function dependency_source_key(): string
	{
		return $this->source_key;
	}

	/** Drop stale field links and relay invalidation once for the previously completed layout. */
	public function invalidate_dependencies(): void
	{
		if ($this->ready) $this->dependencies->changed($this->symbol);
		$this->ready = false;
		foreach ($this->declaration->fields as $field) $field->settle_type(null);
	}

	/** Resolve children first; a repeated active declaration is an infinite by-value layout. @param bool[] $active */
	public function prepare_layout(scope $symbols, array &$active /** array<string,bool> */): void
	{
		if ($this->ready) return;
		if (isset($active[$this->name])) {
			$names = array_keys($active);
			$cycle = array_slice($names, array_search($this->name, $names, true));
			// Anchor a cycle consistently even when a retained generation visits it from another root.
			$index = array_search(min($cycle), $cycle, true);
			$cycle = array_merge(array_slice($cycle, $index), array_slice($cycle, 0, $index));
			$node = $symbols->resolve('class:' . $cycle[0]);
			$cycle[] = $cycle[0];
			throw new diagnostic($node->source(), $node->byte_start(), 'Recursive inline layout: ' . implode(' -> ', $cycle));
		}
		$active[$this->name] = true;
		$this->dependencies->forget($this);
		foreach ($this->declaration->fields as $field) {
			if (isset($this->declaration->static_properties[$field->short_name])) {
				throw new diagnostic($field->source(), $field->byte_start(), 'Duplicate struct property: ' . $this->name . '::' . $field->short_name);
			}
			if (($duplicate = $field->duplicate_element()) !== null) {
				throw new diagnostic($field->source(), $duplicate->byte_start(), 'Duplicate struct field: ' . $field->short_name);
			}
			$type = Type_Resolution::named($field->type_reference, $symbols, $this->declaration->namespace, $this, $this->dependencies);
			if ($type instanceof self) {
				$type->prepare_layout($symbols, $active);
				if (($type->declaration->methods['__construct']->parameters ?? []) !== []) {
					throw new diagnostic($field->source(), $field->type_reference->byte_start(),
						'Inline field requires a zero-argument constructor: ' . $type->name);
				}
			} elseif ($type !== type_registry::integer() && !($type instanceof struct_handle_definition)) {
				throw new diagnostic($field->source(), $field->type_reference->byte_start(),
					'Struct fields require int or int64, a shared or weak handle, or an inline struct: ' . $field->type_reference->spelling());
			}
			$field->settle_type($type);
		}
		unset($active[$this->name]);
		$this->ready = true;
	}

	public function supports_inferred_local(): bool
	{
		return true;
	}

	/** Snapshot a field value before overwriting storage that might own its containing allocation. */
	public function lower_scalar_conversion(int $conversion /** uint16 */, string $value, ?ast_expression $syntax = null): string
	{
		$value = parent::lower_scalar_conversion($conversion, $value, $syntax);
		if ($syntax instanceof ast_field_expression && ($syntax->parent instanceof ast_field_assignment
			|| $syntax->parent instanceof ast_assignment_statement)) {
			for ($field = $syntax; $field instanceof ast_field_expression; $field = $field->receiver) {
				if ($field->receiver->type_def() instanceof shared_struct_type_definition) return $this->cpp_type() . '(' . $value . ')';
			}
		}
		return $value;
	}

	/** Separate source records from synthesized functions, owners and variables in the enclosing app namespace. */
	public function cpp_namespace(): string
	{
		return $this->declaration->namespace === null ? 'scpp_records'
			: 'scpp_records_ns_' . Cpp_Emitter::qualified_path($this->declaration->namespace);
	}

	public function cpp_type(): string
	{
		return $this->cpp_namespace() . '::' . Cpp_Identifier::source($this->declaration->short_name);
	}
}

/** Canonical identity of a strong owner or weak observer of an application record. */
abstract class struct_handle_definition extends type_definition
{
	/** Native non-owning payload identity. */
	public readonly struct_type_definition $payload;

	protected function __construct(string $family, struct_type_definition $payload)
	{
		parent::__construct($family . '<' . $payload->name . '>');
		$this->payload = $payload;
	}

	public function supports_inferred_local(): bool
	{
		return true;
	}
}

/** Observer of an exact struct allocation, e.g. weak<Point>. Only lock() exposes its payload. */
final class weak_struct_type_definition extends struct_handle_definition
{
	public function __construct(struct_type_definition $payload)
	{
		parent::__construct('weak', $payload);
	}

	public function cpp_type(): string
	{
		return 'weak_' . Cpp_Emitter::qualified_name($this->payload->declaration);
	}
}

/** Thread-confined owning reference to an exact application struct, e.g. shared<Point>. */
final class shared_struct_type_definition extends struct_handle_definition
{
	public function __construct(struct_type_definition $payload)
	{
		parent::__construct('shared', $payload);
	}

	/** A condition observes ownership without retaining the allocation. */
	public function prepare_condition(ast_expression $value): void
	{
	}

	public function lower_cpp_condition(string $value, bool $negated = false): string
	{
		return 'static_cast<bool>(' . $value . ')';
	}

	/** Ordinary and strict equality use allocation identity for owners and presence for null. */
	public function prepare_binary(ast_binary_expression $value): void
	{
		$peer = $value->left->type_def() === $this ? $value->right : $value->left;
		if (($peer->type_def() !== type_null::instance() && $peer->type_def() !== $this) || !in_array($value->operator, [
			binary_operator::equal, binary_operator::not_equal, binary_operator::identical, binary_operator::not_identical], true)) {
			throw new diagnostic($value->source(), $value->byte_start(), 'Shared owners require equality against null or the same shared type');
		}
		$value->settle_binary(type_bool::instance(), $this);
	}

	/** Preserve effects of a null-valued expression and evaluate operands once in source order. */
	public function lower_prepared_binary(ast_binary_expression $expr, string $left, string $right, bool $typed_initializer = false): string
	{
		if ($expr->left->type_def() === $this && $expr->right->type_def() === $this) {
			$op = in_array($expr->operator, [binary_operator::equal, binary_operator::identical], true) ? '==' : '!=';
			return "(($left).ptr $op ($right).ptr)";
		}
		$owner_first = $expr->left->type_def() === $this;
		$test = ($expr->operator === binary_operator::equal || $expr->operator === binary_operator::identical ? '!' : '')
			. $this->lower_cpp_condition($owner_first ? $left : $right);
		$null = $owner_first ? $expr->right : $expr->left;
		if ($null instanceof ast_keyword_expression) return '(' . $test . ')';
		return $owner_first ? '([&]() { const bool present_result = ' . $test . '; (void)(' . $right . '); return present_result; }())'
			: '((void)(' . $left . '), ' . $test . ')';
	}

	public function cpp_direct_binary(ast_binary_expression $expr, bool $operands_safe, bool $same_spelling): bool
	{
		return $expr->left->type_def() !== $this || $expr->right->type_def() !== $this
			|| ($expr->left instanceof ast_name_expression && $expr->right instanceof ast_name_expression);
	}

	public function prepare_scalar_conversion(ast_expression $value, conversion_context $context): int /** uint16 */
	{
		if ($value->type_def() === type_null::instance()
			&& ($context === conversion_context::assignment || $context === conversion_context::typed_destination)) {
			return value_conversion::empty_owner;
		}
		return parent::prepare_scalar_conversion($value, $context);
	}

	public function lower_scalar_conversion(int $conversion /** uint16 */, string $value, ?ast_expression $syntax = null): string
	{
		if ($conversion !== value_conversion::empty_owner) return parent::lower_scalar_conversion($conversion, $value, $syntax);
		$empty = $this->cpp_type() . '{}';
		return $syntax instanceof ast_keyword_expression ? $empty : '((void)(' . $value . '), ' . $empty . ')';
	}

	public function convert_scalar_constant(int $conversion /** uint16 */, int|float|bool|null $value /** int64|float64|bool|null */): int|float|bool|null /** int64|float64|bool|null */
	{
		return $conversion === value_conversion::empty_owner ? null : parent::convert_scalar_constant($conversion, $value);
	}

	public function cpp_type(): string
	{
		return 'owner_' . Cpp_Emitter::qualified_name($this->payload->declaration);
	}
}
