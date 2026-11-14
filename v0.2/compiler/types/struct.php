<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Nominal record payload/layout identity, distinct from its source value storage contract. */
final class record_type_definition extends type_definition implements dependency_watcher
{
	/** PROPERTIES */
	/** Native non-owning defining AST. */
	public readonly ast_record_declaration $declaration;
	/** Canonical shared identity published alongside the value identity before body preparation. */
	public readonly shared_record_type_definition $shared;
	/** Required class value; structs reuse their explicit shared identity without another allocation. */
	public readonly shared_record_type_definition $required;
	public readonly weak_record_type_definition $weak;

	/** Native non-owning publication context for relaying layout changes to embedding types and body readers. */
	private ?Dependency_Watches $dependencies = null;
	private ?symbol_entry $symbol = null;
	private string $source_key = '';

	/** PREP FACTS */
	/** Prepared fact: the base relation and local field names/types have been validated. */
	private bool $ready = false;
	/** Prepared fact: non-owning resolved base, null for no base or invalidated layout. */
	private ?self $base_type = null;

	/** CONSTRUCTOR */
	public function __construct(ast_record_declaration $declaration)
	{
		parent::__construct($declaration->name());
		$this->declaration = $declaration;
		$this->shared = new shared_record_type_definition($this);
		$this->required = $declaration->kind === record_kind::class_type ? new shared_record_type_definition($this, true) : $this->shared;
		$this->weak = new weak_record_type_definition($this);
	}

	/** METHODS */
	/** Bare classes use required shared values; bare structs retain inline values. */
	public function default_type(): type_definition
	{
		return $this->declaration->kind === record_kind::class_type ? $this->required : $this;
	}

	/** Bind the declaration to this publication before following any forward field references. */
	public function bind_layout(string $source_key, symbol_entry $symbol, Dependency_Watches $dependencies): void
	{
		if ($this->dependencies === $dependencies && $this->symbol === $symbol && $this->source_key === $source_key) return;
		$this->dependencies?->forget($this);
		$this->ready = false;
		$this->base_type = null;
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
		$this->base_type = null;
		foreach ($this->declaration->fields as $field) $field->settle_type(null);
	}

	/** Resolve children first; a repeated active declaration is an infinite by-value layout. @param bool[] $active */
	public function prepare_layout(scope $symbols, array &$active /** array<string,bool> */, ?preparation_work $work): void
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
		$this->base_type = null;
		if ($this->declaration->base_reference !== null) {
			$written = $this->declaration->base_reference;
			if ($this->declaration->kind === record_kind::class_type) {
				throw new diagnostic($written->source(), $written->byte_start(), 'Class inheritance is deferred');
			}
			$base = Type_Resolution::named($written, $symbols, $this->declaration->namespace, $this, $this->dependencies);
			if (!($base instanceof self)) throw new diagnostic($written->source(), $written->byte_start(), 'Base must be a struct');
			$base->prepare_layout($symbols, $active, $work);
			$this->base_type = $base;
			$this->validate_inherited_members();
		}
		foreach ($this->declaration->fields as $field) {
			if (isset($this->declaration->static_properties[$field->short_name])) {
				throw new diagnostic($field->source(), $field->byte_start(), 'Duplicate struct property: ' . $this->name . '::' . $field->short_name);
			}
			if (($duplicate = $field->duplicate_element()) !== null) {
				throw new diagnostic($field->source(), $duplicate->byte_start(), 'Duplicate struct field: ' . $field->short_name);
			}
			$type = Type_Resolution::named($field->type_reference, $symbols, $this->declaration->namespace, $this, $this->dependencies);
			// The temporary instance-property policy permits absence independently of written nullability.
			if ($type instanceof shared_record_type_definition && $type->required) $type = $type->payload->shared;
			if ($type instanceof self) {
				$type->prepare_layout($symbols, $active, $work);
				$constructor = $type->constructor();
				$constructor?->require_access($this->declaration, $field->type_reference);
				if ($constructor !== null && !$constructor->accepts_argument_count(0)) {
					throw new diagnostic($field->source(), $field->type_reference->byte_start(),
						'Inline field requires a zero-argument constructor: ' . $type->name);
				}
			} elseif (!($type instanceof record_handle_definition) && !$type?->supports_instance_field()) {
				throw new diagnostic($field->source(), $field->type_reference->byte_start(),
					'Unsupported instance field type: ' . $field->type_reference->spelling());
			}
			if ($type instanceof enum_type_definition && !($field->initializer instanceof ast_name_expression)) {
				throw new diagnostic($field->source(), $field->byte_start(), 'Enum field requires an explicit case default');
			}
			if ($field->initializer !== null) {
				$field->initializer->reset_preparation();
				if ($type instanceof enum_type_definition) {
					if (!Enum_Preparation::reference($field->initializer, $symbols, $this, $this->dependencies)) {
						throw new diagnostic($field->source(), $field->initializer->byte_start(), 'Enum field default requires an enum case');
					}
				} else type_registry::prepare_literal($field->initializer);
				$field->conversion = $type->prepare_conversion($field->initializer, conversion_context::typed_destination);
			}
			$field->settle_type($type);
		}
		unset($active[$this->name]);
		$this->ready = true;
		// A declaration-only unit may emit dependent constructor or owner declarations.
		$work?->change_source($this->source_key);
	}

	public function base(): ?self
	{
		return $this->base_type;
	}

	/** The effective constructor remains owned by its original declaration. */
	public function constructor(): ?ast_function_declaration
	{
		return $this->declaration->methods['__construct'] ?? $this->base_type?->constructor();
	}

	/** Nearest instance-field declaration; access checks never retry a hidden base field. */
	public function field(string $name): ?ast_instance_field
	{
		return $this->declaration->fields[$name] ?? $this->base_type?->field($name);
	}

	/** Settled single-base relation; equality includes the type itself. */
	public function derives_from(self $ancestor): bool
	{
		for ($type = $this; $type !== null; $type = $type->base_type) {
			if ($type === $ancestor) return true;
		}
		return false;
	}

	/** Keep inherited declarations at their owner and reject unsupported hiding. */
	private function validate_inherited_members(): void
	{
		foreach ($this->declaration->fields as $field) {
			$inherited = $this->base_type->field($field->short_name);
			if ($inherited !== null && !($inherited->is_private && $field->is_private)) {
				throw new diagnostic($field->source(), $field->byte_start(), 'Inherited field redeclaration: ' . $field->short_name);
			}
		}
		for ($base = $this->base_type; $base !== null; $base = $base->base_type) {
			foreach ($this->declaration->methods as $name => $method) {
				if ($name !== '__construct' && isset($base->declaration->methods[$name])) {
					throw new diagnostic($method->source(), $method->byte_start(), 'Inherited method redeclaration is deferred: ' . $name);
				}
			}
			foreach ($this->declaration->static_properties as $name => $property) {
				if (isset($base->declaration->static_properties[$name]) || isset($base->declaration->fields[$name])) {
					throw new diagnostic($property->source(), $property->byte_start(), 'Inherited property redeclaration: ' . $name);
				}
			}
			foreach ($this->declaration->fields as $name => $field) {
				if (isset($base->declaration->static_properties[$name])) {
					throw new diagnostic($field->source(), $field->byte_start(), 'Inherited property redeclaration: ' . $name);
				}
			}
			foreach ($this->declaration->constants as $name => $constant) {
				if (isset($base->declaration->constants[$name])) {
					throw new diagnostic($constant->source(), $constant->byte_start(), 'Inherited constant redeclaration: ' . $name);
				}
			}
		}
	}

	public function supports_inferred_local(): bool
	{
		return true;
	}

	/** Snapshot a field value before overwriting storage that might own its containing allocation. */
	public function lower_scalar_conversion(int $conversion /** uint16 */, string $value, ?ast_expression $syntax = null, ?type_definition $source_type = null): string
	{
		$value = parent::lower_scalar_conversion($conversion, $value, $syntax, $source_type);
		if ($syntax instanceof ast_field_expression && ($syntax->parent instanceof ast_field_assignment
			|| $syntax->parent instanceof ast_assignment_statement)) {
			for ($field = $syntax; $field instanceof ast_field_expression; $field = $field->receiver) {
				if ($field->receiver->type_def() instanceof shared_record_type_definition) return $this->cpp_type() . '(' . $value . ')';
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
abstract class record_handle_definition extends type_definition
{
	/** Native non-owning payload identity. */
	public readonly record_type_definition $payload;

	protected function __construct(string $family, record_type_definition $payload, ?string $name = null)
	{
		parent::__construct($name ?? $family . '<' . $payload->name . '>');
		$this->payload = $payload;
	}

	public function supports_inferred_local(): bool
	{
		return true;
	}
}

/** Observer of an exact struct allocation, e.g. weak<Point>. Only lock() exposes its payload. */
final class weak_record_type_definition extends record_handle_definition
{
	public function __construct(record_type_definition $payload)
	{
		parent::__construct('weak', $payload);
	}

	public function cpp_type(): string
	{
		return 'weak_' . Cpp_Emitter::qualified_name($this->payload->declaration);
	}
}

/** Exact record owner contract: explicit shared<Struct>, required Class or optional ?Class. */
final class shared_record_type_definition extends record_handle_definition
{
	/** PROPERTIES */
	/** Semantic presence contract; both variants use the same native owner. */
	public readonly bool $required;

	/** CONSTRUCTOR */
	public function __construct(record_type_definition $payload, bool $required = false)
	{
		$this->required = $required;
		$name = $payload->declaration->kind === record_kind::class_type ? ($required ? '' : '?') . $payload->name : null;
		parent::__construct('shared', $payload, $name);
	}

	/** METHODS */
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
		if (($peer->type_def() !== type_null::instance() && !($peer->type_def() instanceof self && $peer->type_def()->payload === $this->payload)) || !in_array($value->operator, [
			binary_operator::equal, binary_operator::not_equal, binary_operator::identical, binary_operator::not_identical], true)) {
			throw new diagnostic($value->source(), $value->byte_start(), 'Shared owners require equality against null or the same shared type');
		}
		$value->settle_binary(type_bool::instance(), $this);
	}

	/** Preserve effects of a null-valued expression and evaluate operands once in source order. */
	public function lower_prepared_binary(ast_binary_expression $expr, string $left, string $right, bool $typed_initializer = false): string
	{
		if ($expr->left->type_def() instanceof self && $expr->right->type_def() instanceof self) {
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
		return !($expr->left->type_def() instanceof self && $expr->right->type_def() instanceof self)
			|| ($expr->left instanceof ast_name_expression && $expr->right instanceof ast_name_expression);
	}

	/** Same-payload contracts share storage; required destinations check potentially empty owners. */
	public function prepare_scalar_conversion(ast_expression $value, conversion_context $context): int /** uint16 */
	{
		$source = $value->type_def();
		if ($this->required && $value->constant_known() && $value->constant() === null) {
			throw new diagnostic($value->source(), $value->byte_start(), 'Required object cannot be null: ' . $this->name);
		}
		if ($source instanceof self && $source->payload === $this->payload) {
			return $this->required && !$source->required ? value_conversion::require_owner : value_conversion::identity;
		}
		if (!$this->required && $source === type_null::instance()
			&& ($context === conversion_context::assignment || $context === conversion_context::typed_destination)) {
			return value_conversion::empty_owner;
		}
		return parent::prepare_scalar_conversion($value, $context);
	}

	public function lower_scalar_conversion(int $conversion /** uint16 */, string $value, ?ast_expression $syntax = null, ?type_definition $source_type = null): string
	{
		if ($conversion === value_conversion::require_owner) return $this->cpp_type() . '::require(' . $value . ')';
		if ($conversion !== value_conversion::empty_owner) return parent::lower_scalar_conversion($conversion, $value, $syntax, $source_type);
		$empty = $this->cpp_type() . '{}';
		return $syntax instanceof ast_keyword_expression ? $empty : '((void)(' . $value . '), ' . $empty . ')';
	}

	public function convert_scalar_constant(int $conversion /** uint16 */, int|float|bool|null $value /** int64|float64|bool|null */): int|float|bool|null|constant_evaluation /** int64|float64|bool|null|constant_evaluation */
	{
		return $conversion === value_conversion::empty_owner ? null : parent::convert_scalar_constant($conversion, $value);
	}

	public function cpp_type(): string
	{
		return 'owner_' . Cpp_Emitter::qualified_name($this->payload->declaration);
	}
}
