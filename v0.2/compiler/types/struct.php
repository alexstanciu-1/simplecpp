<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Nominal record payload/layout identity, distinct from its source value storage contract. */
final class record_type_definition extends type_definition implements dependency_watcher, iterable_type
{
	use collection_specializations;
	use concrete_value_storage;

	/** PROPERTIES */
	/** Native non-owning defining AST. */
	public readonly ast_record_declaration $declaration;
	/** Canonical shared identity published alongside the value identity before body preparation. */
	public readonly shared_record_type_definition $shared;
	/** Required class/interface value; structs reuse their explicit shared identity without another allocation. */
	public readonly shared_record_type_definition $required;
	public readonly weak_record_type_definition $weak;

	/** Native non-owning publication context for relaying layout changes to embedding types and body readers. */
	private ?Dependency_Watches $dependencies = null;
	private ?symbol_entry $symbol = null;
	private string $source_key = '';

	/** PREP FACTS */
	/** Prepared fact: the base relation and local field names/types have been validated. */
	private bool $ready = false;
	/** Prepared fact: abstract/interface obligations checked after signatures; cleared with nominal layout invalidation. */
	public bool $method_contract_ready = false;
	/** Prepared fact: non-owning resolved base, null for no base or invalidated layout. */
	private ?self $base_type = null;
	/** Prepared fact: non-owning validated traversal method; null for absent or invalidated contract. */
	private ?ast_function_declaration $iterator = null;
	/** Prepared fact: non-owning direct interface edges, owned by this declaration generation. @var record_type_definition[] */
	private array $interfaces /** list<record_type_definition> */ = [];
	/** Prepared fact: native non-owning forwarding targets; original methods remain at their declaring owner. @var ast_function_declaration[] */
	public array $interface_forwards /** array<string,ast_function_declaration> */ = [];

	/** CONSTRUCTOR */
	public function __construct(ast_record_declaration $declaration)
	{
		parent::__construct($declaration->name());
		$this->declaration = $declaration;
		$this->shared = new shared_record_type_definition($this);
		$this->required = $declaration->kind !== record_kind::structure ? new shared_record_type_definition($this, true) : $this->shared;
		$this->weak = new weak_record_type_definition($this);
	}

	/** METHODS */
	/** Bare classes/interfaces use required shared values; bare structs retain inline values. */
	public function default_type(): type_definition
	{
		return $this->declaration->kind !== record_kind::structure ? $this->required : $this;
	}

	/** Bind the declaration to this publication before following any forward field references. */
	public function bind_layout(string $source_key, symbol_entry $symbol, Dependency_Watches $dependencies): void
	{
		if ($this->dependencies === $dependencies && $this->symbol === $symbol && $this->source_key === $source_key) return;
		$this->dependencies?->forget($this);
		$this->ready = false;
		$this->method_contract_ready = false;
		$this->base_type = null;
		$this->iterator = null;
		$this->interfaces = [];
		$this->interface_forwards = [];
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
		$this->method_contract_ready = false;
		$this->base_type = null;
		$this->iterator = null;
		$this->interfaces = [];
		$this->interface_forwards = [];
		foreach ($this->declaration->fields as $field) $field->settle_type(null);
	}

	/** Resolve children first; diagnose inline cycles and the deferred recursive concrete-field boundary. @param bool[] $active */
	public function prepare_layout(scope $symbols, array &$active /** array<string,bool> */, ?preparation_work $work, bool $concrete_field = false): void
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
			throw new diagnostic($node->source(), $node->byte_start(), ((in_array(true, $active, true) || $concrete_field) ? 'Recursive concrete collection fields are deferred: ' : 'Recursive inline layout: ') . implode(' -> ', $cycle));
		}
		$active[$this->name] = $concrete_field;
		$this->dependencies->forget($this);
		$this->base_type = null;
		$this->iterator = null;
		$this->interfaces = [];
		$this->interface_forwards = [];
		$interface_references = $this->declaration->interface_references;
		if ($this->declaration->base_reference !== null) {
			$written = $this->declaration->base_reference;
			$base = Type_Resolution::named($written, $symbols, $this->declaration->namespace, $this, $this->dependencies);
			if ($base instanceof shared_record_type_definition && $base->required) $base = $base->payload;
			if ($base instanceof self && $base->declaration->kind === record_kind::interface_type && $written->source()->flavor === flavor::cpp) {
				array_unshift($interface_references, $written);
			} else {
				if (!($base instanceof self) || $base->declaration->kind !== $this->declaration->kind) {
					throw new diagnostic($written->source(), $written->byte_start(),
						$this->declaration->kind === record_kind::structure ? 'Base must be a struct' : 'Base must be a class');
				}
				$base->prepare_layout($symbols, $active, $work);
				$this->base_type = $base;
				$this->validate_inherited_members();
			}
		}
		foreach ($interface_references as $written) {
			if ($written->name === Iterator_Contract::NAME) continue;
			if ($this->declaration->kind !== record_kind::class_type) throw new diagnostic($written->source(), $written->byte_start(), 'Only classes implement interfaces in this slice');
			$type = Type_Resolution::named($written, $symbols, $this->declaration->namespace, $this, $this->dependencies);
			if (!($type instanceof shared_record_type_definition) || !$type->required || $type->payload->declaration->kind !== record_kind::interface_type) throw new diagnostic($written->source(), $written->byte_start(), 'Implemented type must be an interface');
			$interface = $type->payload;
			if (in_array($interface, $this->interfaces, true)) throw new diagnostic($written->source(), $written->byte_start(), 'Duplicate implemented interface');
			$interface->prepare_layout($symbols, $active, $work);
			$this->interfaces[] = $interface;
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
			if ($type instanceof application_collection_type) {
				foreach (self::collection_records($type) as $record) {
					$record->prepare_layout($symbols, $active, $work, true);
				}
			}
			if ($type instanceof enum_type_definition && !($field->initializer instanceof ast_name_expression) && !$field->is_readonly) {
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
			if ($field->is_readonly && $field->initializer === null && !isset($this->declaration->methods['__construct'])) {
				throw new diagnostic($field->source(), $field->byte_start(), 'Readonly property requires a default or constructor: ' . $field->short_name);
			}
			$field->settle_type($type);
		}
		unset($active[$this->name]);
		$this->ready = true;
		// A declaration-only unit may emit dependent constructor or owner declarations.
		$work?->change_source($this->source_key);
	}

	/** Check once after ordinary signature preparation; all bodies consume the settled method. */
	public function prepare_iterator(scope $symbols): void
	{
		if ($this->iterator !== null) return;
		$written = null;
		foreach ($this->declaration->interface_references as $reference) {
			if ($reference->name !== Iterator_Contract::NAME) continue;
			if ($written !== null) throw new diagnostic($reference->source(), $reference->byte_start(), 'Duplicate Iterator contract');
			$written = $reference;
		}
		if ($written === null) return;
		if ($this->declaration->kind !== record_kind::structure) {
			throw new diagnostic($written->source(), $written->byte_start(), 'Class Iterator implementations are deferred');
		}
		$argument = $written->first_child;
		$key_argument = $argument?->next_sibling;
		if ($written->name !== Iterator_Contract::NAME || $argument === null || $key_argument?->next_sibling !== null) {
			throw new diagnostic($written->source(), $written->byte_start(), 'Expected Iterator<T> or Iterator<T,K> contract');
		}
		$element = Type_Resolution::named($argument, $symbols, $this->declaration->namespace, $this, $this->dependencies);
		$key = $key_argument === null ? null : Type_Resolution::named($key_argument, $symbols, $this->declaration->namespace, $this, $this->dependencies);
		$method = $this->declaration->methods[Iterator_Contract::METHOD] ?? null;
		if ($element === null || ($key_argument !== null && $key === null) || $method === null
			|| $method->has_virtual_dispatch() || !Iterator_Contract::validate($method, $element, $key)) {
			throw new diagnostic($written->source(), $written->byte_start(), 'Iterator requires public instance ' . Iterator_Contract::METHOD . '(T &value' . ($key_argument === null ? '' : ', K &key') . '): bool');
		}
		$this->dependencies?->watch($symbols->entry($method->symbol_key()), $this);
		$this->iterator = $method;
	}

	public function iteration(): ?iterable_type
	{
		return $this->iterator === null ? null : $this;
	}

	public function iteration_method(): ?ast_function_declaration
	{
		return $this->iterator;
	}

	public function iteration_value_type(): type_definition
	{
		return $this->iterator->parameters[0]->type_def();
	}

	public function iteration_key_type(): ?type_definition
	{
		return ($this->iterator->parameters[1] ?? null)?->type_def();
	}

	public function lower_cpp_iteration_storage(bool $keyed): string
	{
		return '';
	}

	public function lower_cpp_iteration_initialize(string $receiver, string $prefix = ''): string
	{
		return '';
	}

	public function lower_cpp_iteration_next(string $receiver, string $value, ?string $key, string $prefix = ''): string
	{
		throw new \LogicException('Source traversal calls are emitted through their method declaration');
	}

	/** Direct settled edges; interfaces currently have no interface parents. @return record_type_definition[] */
	public function interfaces(): array /** list<record_type_definition> */
	{
		return $this->interfaces;
	}

	/** Read original contracts through settled edges; the temporary result is not a retained member index. @return ast_function_declaration[] */
	public function interface_requirements(?string $name = null): array /** list<ast_function_declaration> */
	{
		$requirements = [];
		for ($type = $this; $type !== null; $type = $type->base_type) {
			foreach ($type->interfaces as $interface) {
				if ($name === null) {
					foreach ($interface->declaration->methods as $method) $requirements[] = $method;
				} elseif (isset($interface->declaration->methods[$name])) $requirements[] = $interface->declaration->methods[$name];
			}
		}
		return $requirements;
	}

	/** A prepared interface slot can add dispatch to an inherited ordinary implementation. */
	public function interface_dispatch(string $name): bool
	{
		return isset($this->interface_forwards[$name]) || ($this->base_type?->interface_dispatch($name) ?? false);
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

	/** Follow settled ancestry while observing absent nearer methods as well as the selected contract. */
	public function method_symbol(string $name, scope $symbols, dependency_watcher $watcher, ?Dependency_Watches $dependencies): ?symbol_entry
	{
		for ($type = $this; $type !== null; $type = $type->base_type) {
			$dependencies?->watch($symbols->entry($type->declaration->symbol_key()), $watcher);
			$symbol = $symbols->entry('method:' . $type->name . '::' . $name);
			$dependencies?->watch($symbol, $watcher);
			if ($symbol->declaration() instanceof ast_function_declaration) return $symbol;
		}
		foreach ($this->interface_requirements($name) as $method) {
			$symbol = $symbols->entry($method->symbol_key());
			$dependencies?->watch($symbol, $watcher);
			return $symbol;
		}
		return null;
	}

	/** Native layout follows validated written virtual contracts, including inherited ones. */
	public function polymorphic(): bool
	{
		if ($this->declaration->kind === record_kind::interface_type || $this->interfaces !== []) return true;
		foreach ($this->declaration->methods as $method) if ($method->has_virtual_dispatch()) return true;
		return $this->base_type?->polymorphic() ?? false;
	}

	/** Settled single-base relation; equality includes the type itself. */
	public function derives_from(self $ancestor): bool
	{
		for ($type = $this; $type !== null; $type = $type->base_type) {
			if ($type === $ancestor || in_array($ancestor, $type->interfaces, true)) return true;
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

	/** STATIC METHODS */
	/** Follow nested value storage to its nominal layout dependencies without retaining a flattened index. @return \Generator<self> */
	private static function collection_records(application_collection_type $type): \Generator
	{
		foreach ($type->concrete_types() as $argument) {
			if ($argument instanceof application_collection_type) yield from self::collection_records($argument);
			$record = $argument instanceof record_handle_definition ? $argument->payload : $argument;
			if ($record instanceof self) yield $record;
		}
	}
}

/** Canonical identity of a strong owner or weak observer of an application record. */
abstract class record_handle_definition extends type_definition
{
	use concrete_value_storage;

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
		$name = $payload->declaration->kind !== record_kind::structure ? ($required ? '' : '?') . $payload->name : null;
		parent::__construct('shared', $payload, $name);
	}

	/** METHODS */
	public function mutates_referenced_value(): bool
	{
		return true;
	}

	/** Borrow the payload's settled traversal while the emitter retains this shared allocation. */
	public function iteration(): ?iterable_type
	{
		return $this->payload->iteration();
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
		$peer_type = $peer->type_def();
		$compatible = $peer_type === type_null::instance()
			|| ($peer_type instanceof self && ($this->admits_view($peer_type) || $peer_type->admits_view($this)));
		if (!$compatible || !in_array($value->operator, [
			binary_operator::equal, binary_operator::not_equal, binary_operator::identical, binary_operator::not_identical], true)) {
			throw new diagnostic($value->source(), $value->byte_start(), 'Shared owners require equality against null or related shared types');
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

	/** Struct owners stay exact; object views admit settled base and interface edges. */
	private function admits_view(self $source): bool
	{
		return $source->payload === $this->payload || ($source->payload->declaration->kind !== record_kind::structure
			&& $this->payload->declaration->kind !== record_kind::structure && $source->payload->derives_from($this->payload));
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
		if ($source instanceof self && $this->admits_view($source)) {
			return $this->required && !$source->required ? value_conversion::require_owner_view : value_conversion::owner_view;
		}
		if (!$this->required && $source === type_null::instance()
			&& ($context === conversion_context::assignment || $context === conversion_context::typed_destination)) {
			return value_conversion::empty_owner;
		}
		return parent::prepare_scalar_conversion($value, $context);
	}

	/** Select the shared-view pattern from settled conversion facts; no ancestry query occurs here. */
	public function cpp_conversion_adapter(int $conversion /** uint16 */, ?type_definition $source): ?cpp_inline_adapter
	{
		if ($conversion !== value_conversion::owner_view && $conversion !== value_conversion::require_owner_view) return null;
		if ($source === null) throw new \LogicException('View conversion requires a concrete source type');
		return Cpp_Shared_View::request($source, $this, $conversion === value_conversion::require_owner_view);
	}

	/** Emit remaining presence/empty conversion after any concrete view adapter. */
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
