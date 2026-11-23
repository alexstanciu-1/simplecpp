<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Published private initialization transport; fixed for a compiler session and shared by literal occurrences. */
final class initialization_transport
{
	public readonly string $element;
	public readonly string $input;
	public readonly string $factory;
	public readonly int $inline_entries /** uint32 */;

	public function __construct(string $element, string $input, string $factory, int $inline_entries /** uint32 */)
	{
		$this->element = $element;
		$this->input = $input;
		$this->factory = $factory;
		$this->inline_entries = $inline_entries;
	}
}

/** Whole-value move assignment preserves source value semantics when a private source dies. */
interface private_value_transfer_type
{
}

/** Generated keyed construction capability; strict key admission reuses the ordinary key contract. */
interface keyed_initializer_type
{
	public function keyed_initialization(): initialization_transport;
	public function prepare_key(ast_expression $key): void;
	public function lower_cpp_key(ast_expression $syntax, string $key): string;
	public function write_type(): type_definition;
}

/** Generated concrete collection contract; canonical arguments serve layout planning, recipe data serves private lowering. */
interface application_collection_type
{
	public static function concrete_recipe(): string;
	/** @return type_definition[] Native non-owning canonical recipe arguments. */
	public function concrete_types(): array;
	/** @return string[] Transient recipe substitutions, not retained preparation facts. */
	public function recipe_arguments(): array;
	public function cpp_type(): string;
	/** @return string[] */
	public function cpp_headers(): array;
}

/** Concrete provider whose separate helper can construct a completed aggregate in spare storage. */
interface destination_append_type extends application_collection_type, appendable_collection_type
{
	/** @param string[] $values Native helper template substitutions. */
	public function destination_append_definition(array $values): string;
}

/** Existing owned-value lifecycle accepted by concrete recipes; native assertions still verify every requirement. */
trait concrete_value_storage
{
	public function concrete_storage_support(string $requirement): ?bool
	{
		return in_array($requirement, ['copy_constructible', 'nothrow_move_constructible', 'nothrow_move_assignable', 'nothrow_destructible'], true)
			? true : null;
	}
}

/** Storage-capable element owns canonical outer collections; nested arguments never need per-use identities. */
trait collection_specializations
{
	/** Native owning outer identities; their element links back non-owningly. @var type_definition[] */
	private array $collections /** array<string,type_definition> */ = [];

	/** Cache only supported identities on the element generation; no global cache retains retired declarations. */
	public function collection_type(string $family, ?type_definition $key): ?type_definition
	{
		if ($key !== null) return $key->keyed_collection_type($family, $this->default_type());
		$identity = $family;
		if (isset($this->collections[$identity])) return $this->collections[$identity];
		$type = type_registry::application_collection($family, $this->default_type(), $key);
		if ($type !== null) $this->collections[$identity] = $type;
		return $type;
	}
}

/** Weak canonical pair index: a surviving key must not retain retired value declarations or hash definitions. */
trait hash_key_specializations
{
	/** Native weak key/value links; live AST/type users own specializations. @var \WeakMap<type_definition,array<string,\WeakReference<type_definition>>>|null */
	private ?\WeakMap $hashes = null;

	public function hash_key_supported(): bool
	{
		return true;
	}

	/** Reuse a live pair without pinning either a retired value generation or its specialization. */
	public function keyed_collection_type(string $family, type_definition $value): ?type_definition
	{
		$type = ($this->hashes[$value][$family] ?? null)?->get();
		if ($type !== null) return $type;
		$type = type_registry::application_collection($family, $value, $this);
		if ($type !== null) {
			$this->hashes ??= new \WeakMap();
			$families = $this->hashes[$value] ?? [];
			$families[$family] = \WeakReference::create($type);
			$this->hashes[$value] = $families;
		}
		return $type;
	}
}

/** Published value traversal capability; concrete generated types supply key/value identity and native calls. */
interface iterable_type
{
	/** Null uses an existing value; otherwise the generated native slot constructs from a selected element. */
	public function iteration_output_type(): ?string;
	public function iteration_method(): ?ast_function_declaration;
	public function lower_cpp_iteration_storage(bool $keyed): string;
	public function lower_cpp_iteration_initialize(string $receiver, string $prefix = ''): string;
	public function iteration_value_type(): type_definition;
	public function iteration_key_type(): ?type_definition;
	/** $key is null for value-only traversal; boxed implementations decode normalized key transport. */
	public function lower_cpp_iteration_next(string $receiver, string $value, ?string $key, string $prefix = ''): string;
}

/** Published traversal that can advance without materializing a value. */
interface key_iterable_type extends iterable_type
{
	public function lower_cpp_iteration_next_key(string $receiver, string $output, string $prefix = ''): string;
	public function lower_cpp_iteration_current_value(string $receiver): string;
}

/** Unary observations on a published collection. Native uint8. */
enum collection_cardinality: int
{
	case count = 1;
	case empty = 2;
}

/** Generated by each published typed vector/hash that supports cardinality queries. */
interface counted_collection_type
{
	public function cardinality_result(collection_cardinality $operation): type_definition;
	public function lower_cpp_cardinality(collection_cardinality $operation, string $receiver): string;
}

/** Published key existence query, independent of payload representation or null state. */
interface key_presence_type
{
	public function presence_function(): runtime_function;
}

/** Published copied lookup with an explicit absence carrier; no implicit carrier synthesis. */
interface key_lookup_type
{
	public function lookup_function(): runtime_function;
}

/** A single probe returning a shared owner; absent and present-empty both produce an empty handle. */
interface shared_value_lookup_type
{
	public function shared_lookup_function(): ?runtime_function;
}

/** A receiver with a non-addressable indexed value, e.g. text[0]. */
interface index_read_type
{
	public function prepare_index(ast_index_expression $value): void;
	public function lower_cpp_index(ast_expression $key, string $receiver, string $index): string;
}

/** An immutable descriptor can reuse this provider's checked read while the owner is stable. */
interface scoped_byte_read_type extends index_read_type
{
	public function is_byte_length(runtime_function $function): bool;
}

/** Indexed collections that can also be initialized with []. */
interface indexed_collection_type extends index_read_type
{
	public function lower_cpp_empty(): string;
}

/** Metadata-approved ordered initialization, using the existing checked element and append contracts. */
interface sequence_initializer_type extends indexed_collection_type
{
	/** Null selects ordered append; a binding selects private bulk transport. */
	public function list_initialization(): ?initialization_transport;
	public function write_type(): type_definition;
	public function lower_cpp_sequence_append(ast_expression $element, int $conversion /** uint16 */, string $receiver, string $value, ?Cpp_Adapters $adapters = null): string;
}

/** Source append on a writable receiver with a published value parameter. */
interface appendable_collection_type
{
	public function write_type(): type_definition;
	public function lower_cpp_append(ast_append_statement $write, string $receiver, string $value, ?Cpp_Adapters $adapters = null): string;
}

/** Single-level keyed store; the receiver owns key admission and the destination type for its values. */
interface index_write_type
{
	public function prepare_key(ast_expression $key): void;
	public function write_type(): type_definition;
	public function lower_cpp_store(ast_index_assignment $write, string $receiver, string $key, string $value, ?Cpp_Adapters $adapters = null): string;
}

/** Read/modify/write collections replace an approved element value without exposing an element reference. */
interface indexed_mutation_type extends indexed_collection_type, index_write_type
{
	public function lower_cpp_mutation_read(ast_expression $key, string $receiver, string $index): string;
	public function lower_cpp_replace(ast_expression $key, string $receiver, string $index, string $value): string;
}

/** Collections whose normal read already rejects missing elements reuse that check for mutations. */
trait checked_indexed_mutation
{
	abstract public function lower_cpp_index(ast_expression $key, string $receiver, string $index): string;

	public function lower_cpp_mutation_read(ast_expression $key, string $receiver, string $index): string
	{
		return $this->lower_cpp_index($key, $receiver, $index);
	}
}

/** Ordered stores snapshot the normalized key and converted value before invoking the runtime member. */
trait ordered_keyed_store
{
	abstract public function write_type(): type_definition;
	abstract public function lower_cpp_key(ast_expression $syntax, string $key): string;
	abstract public function lower_cpp_write(string $receiver, string $key, string $value): string;

	public function lower_cpp_store(ast_index_assignment $write, string $receiver, string $key, string $value, ?Cpp_Adapters $adapters = null): string
	{
		$value = $this->write_type()->lower_cpp_exact_argument($write->value, $write->conversion(), $value, $adapters);
		return '{ const auto write_key = ' . $this->lower_cpp_key($write->index, $key) . '; const auto write_value = ' . $value . '; '
			. $this->lower_cpp_write($receiver, 'write_key', 'write_value') . '; }';
	}
}

/** Constant-label contracts shared by selection constructs. The strategy is canonical per type. */
enum constant_dispatch
{
	case integer_switch;
	case enum_switch;
	case byte_equality;

	/** A receiver-dependent name is an expression, even when it resolves to a constant. */
	private static function static_binding(ast_expression $label): ?name_binding
	{
		if ($label instanceof ast_qualified_data || $label instanceof ast_called_type_name) return null;
		return $label instanceof ast_name_expression ? $label->declaration() : null;
	}

	public static function integer_label(ast_expression $label): ?int
	{
		if (!($label instanceof ast_integer_expression)) {
			$binding = self::static_binding($label);
			if (!($binding instanceof ast_constant_declaration || $binding instanceof runtime_integer_constant)) return null;
		}
		$value = $label->constant();
		return is_int($value) ? $value : null;
	}

	public static function enum_case_name(ast_expression $label): ?string
	{
		$binding = self::static_binding($label);
		return $binding instanceof ast_enum_case ? $binding->short_name : null;
	}

	public static function string_label_bytes(ast_expression $label): ?string
	{
		$binding = self::static_binding($label);
		$literal = $binding instanceof ast_constant_declaration ? $binding->initializer : $label;
		return $literal instanceof ast_string_expression ? $literal->bytes() : null;
	}
}

/** Shared semantic type contract; canonical definitions own type-specific behavior, never occurrence facts. */
abstract class type_definition
{
	/** PROPERTIES */
	public readonly string $name;

	/** CONSTRUCTOR */
	protected function __construct(string $name)
	{
		$this->name = $name;
	}

	/** METHODS */
	public function constant_dispatch(): ?constant_dispatch
	{
		return null;
	}

	public function hash_key_supported(): bool
	{
		return false;
	}

	public function lower_cpp_hash_key(string $key): ?string
	{
		return null;
	}

	public function lower_cpp_key_equal(string $left, string $right): ?string
	{
		return null;
	}

	public function keyed_collection_type(string $family, type_definition $value): ?type_definition
	{
		return null;
	}

	/** Null leaves a concrete lifecycle requirement unproven; native recipe assertions remain mandatory. */
	public function concrete_storage_support(string $requirement): ?bool
	{
		return null;
	}

	/** Source storage identity; class declarations override this with their required shared value. */
	public function default_type(): type_definition
	{
		return $this;
	}

	/** Types without an approved concrete storage contract do not synthesize outer collections. */
	public function collection_type(string $family, ?type_definition $key): ?type_definition
	{
		return null;
	}

	/** Generated native member signature on this canonical type, independent of concrete publication location. */
	public function runtime_method(string $name): runtime_function|runtime_overloads|null
	{
		return null;
	}

	/** Optional settled traversal capability; ordinary types are not iterable. */
	public function iteration(): ?iterable_type
	{
		return $this instanceof iterable_type ? $this : null;
	}

	public function iteration_output_type(): ?string
	{
		return null;
	}

	/** Definitions are canonical shared identities, not copyable per-expression records. */
	final protected function __clone(): void
	{
	}

	/** Current support for using this type as an inferred local declaration type. */
	public function supports_inferred_local(): bool
	{
		return false;
	}

	/** Current support for global storage; independent of local/constant declarations. */
	public function supports_global_storage(): bool
	{
		return false;
	}

	/** Declaration eligibility for scalar instance fields, supplied by generated type definitions. */
	public function supports_instance_field(): bool
	{
		return false;
	}

	/** Static storage admission is independent from instance initialization. */
	public function supports_static_property(): bool
	{
		return $this->supports_instance_field();
	}

	/** Output admission is separate from string conversion. */
	public function supports_output(): bool
	{
		return false;
	}

	/** @return string[] Public headers for the approved output strategy. */
	public function output_headers(): array /** list<string> */
	{
		return [];
	}

	/** Consume an approved operand; null spelling denotes a string literal handled without storage. */
	public function lower_cpp_output(ast_expression $value, ?string $operand): string
	{
		throw new \LogicException('Output was not approved');
	}

	public function accepts_name(string $name): bool
	{
		return $name === $this->name;
	}

	/** Unsupported literal kinds fail during preparation; successful overrides attach facts to the AST. */
	public function prepare_literal(ast_expression $syntax): void
	{
		throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Unsupported literal for type: ' . $this->name);
	}

	/** Attach ordinary binary facts through generated type admission. */
	public function prepare_binary(ast_binary_expression $value): void
	{
		$result = $this->binary_result($value->operator, $value->right->value_type());
		if (($value->left->value_type() !== $this) || ($result === null) || !$this->binary_syntax_allowed($value->operator, $value)) {
			throw new diagnostic($value->source(), $value->byte_start(), 'Unsupported binary operands or operator');
		}
		$value->settle_binary($result);
	}

	/** Generated flavor restrictions apply only when an operation has flavor-specific spelling. */
	public function binary_syntax_allowed(binary_operator $operator, ast_node $site): bool
	{
		return true;
	}

	/** Generated operator admission without requiring synthetic syntax; null means the pair is unsupported. */
	public function binary_result(binary_operator $operator, type_definition $right): ?type_definition
	{
		return null;
	}

	/** Attach same-type compound facts without creating synthetic binary syntax. */
	public function prepare_compound(ast_assignment_statement $value): void
	{
		if (!$this->supports_compound($value->operator) || !$this->binary_syntax_allowed($value->operator, $value) || ($this->binary_result($value->operator, $value->value->type_def()) !== $this)) {
			throw new diagnostic($value->source(), $value->byte_start(), 'Unsupported compound operands or write-back type');
		}
		$value->settle_compound($this, value_conversion::identity, value_conversion::identity, value_conversion::identity);
	}

	/** Compound writes may remain unavailable even when ordinary arithmetic is supported. */
	public function supports_compound(binary_operator $operator): bool
	{
		return false;
	}

	/** Native spelling when this execution type permits a direct compound write. */
	public function cpp_compound_operator(binary_operator $operator): ?string
	{
		return null;
	}

	public function lower_cpp_binary(binary_operator $operator, string $left, string $right, type_definition $right_type, bool $typed_initializer = false): string
	{
		throw new \LogicException('Binary lowering unavailable for type: ' . $this->name);
	}

	/** Compound execution consumes settled conversions; a type can also select a flavor-specific binding. */
	public function lower_prepared_compound(ast_assignment_statement $site, string $left, string $right): string
	{
		return $this->lower_cpp_binary($site->operator, $left, $right, $this);
	}

	/** Consume the original prepared operand types when selecting the native binding. */
	public function lower_prepared_binary(ast_binary_expression $expr, string $left, string $right, bool $typed_initializer = false,
		bool $bare_comparison = false): string
	{
		return $this->lower_cpp_binary($expr->operator, $left, $right, $expr->right->value_type(), $typed_initializer);
	}

	/** Observe presence in operand order, including effects from a null-typed expression. */
	protected function lower_null_presence_comparison(ast_binary_expression $expr, string $presence, string $null_value): string
	{
		$null_first = $expr->left->type_def() === type_null::instance();
		$null = $null_first ? $expr->left : $expr->right;
		$test = ($expr->operator === binary_operator::equal || $expr->operator === binary_operator::identical ? '!' : '') . $presence;
		if ($null instanceof ast_keyword_expression) return '(' . $test . ')';
		return $null_first ? '((void)(' . $null_value . '), ' . $test . ')'
			: '([&]() { const bool present_result = ' . $test . '; (void)(' . $null_value . '); return present_result; }())';
	}

	/** A known exact native result when emitted as an argument. Null leaves normalization to the consumer. */
	public function cpp_exact_binary_result(binary_operator $operator): ?type_definition
	{
		return null;
	}

	/** @return string[] Public includes needed by an approved binary operation. */
	public function binary_headers(binary_operator $operator): array /** list<string> */
	{
		return [];
	}

	/** Mutation through a reference changes its referent, not the stored binding. */
	public function mutates_referenced_value(): bool
	{
		return false;
	}

	/** Backend property: copying a plain value cannot call user code, allocate or throw. */
	public function cpp_trivial_value(): bool
	{
		return false;
	}

	/** Whether this operation can directly combine independently safe operands with these C++ spellings. */
	public function cpp_binary_reorderable(binary_operator $operator, bool $same_spelling = false): bool
	{
		return false;
	}

	/** A checked native helper consumes each prepared operand once by value. */
	public function cpp_value_helper(binary_operator $operator): bool
	{
		return false;
	}

	/** Whether this lowering accepts the identity-converted operands directly, independently of result reorderability. */
	public function cpp_direct_binary(ast_binary_expression $expr, bool $operands_safe, bool $same_spelling): bool
	{
		return false;
	}

	/** Generated numeric prefix admission attaches only the result identity to the original AST. */
	public function prepare_unary(ast_unary_expression $value): void
	{
		throw new diagnostic($value->source(), $value->byte_start(), 'Unsupported unary operand or operator');
	}

	public function lower_cpp_unary(unary_operator $operator, string $operand): string
	{
		throw new \LogicException('Unary lowering unavailable for type: ' . $this->name);
	}

	/** Conservative for unknown lowering strategies; operand safety is checked separately by the backend. */
	public function cpp_unary_reorderable(unary_operator $operator): bool
	{
		return false;
	}

	/** Conditions are a separate semantic boundary from typed boolean conversion. */
	public function prepare_condition(ast_expression $value): void
	{
		throw new diagnostic($value->source(), $value->byte_start(), 'Unsupported condition type: ' . $this->name);
	}

	public function lower_cpp_condition(string $value, bool $negated = false): string
	{
		throw new \LogicException('Condition lowering is unavailable for type: ' . $this->name);
	}

	/** @return string[] Public includes needed by this type's condition operation. */
	public function condition_headers(): array /** list<string> */
	{
		return [];
	}

	/** Generated capability for discarded-value updates; unsupported types reject by default. */
	public function supports_update(update_operator $operator): bool
	{
		return false;
	}

	public function lower_cpp_update(update_operator $operator, string $target, bool $postfix): string
	{
		throw new \LogicException('Update lowering unavailable for type: ' . $this->name);
	}

	/** Native representation of an already resolved language type. */
	abstract public function cpp_type(): string;

	/** Lower only settled literals; a missing implementation is an internal error, not source admission. */
	public function lower_cpp_literal(ast_expression $syntax): string
	{
		throw new \LogicException('Literal lowering is unavailable for type: ' . $this->name);
	}

	/** Select scalar work once, with carrier transport encoded independently in the prepared integer. */
	final public function prepare_conversion(ast_expression $value, conversion_context $context): int /** uint16 */
	{
		$source = $value->type_def();
		if ($source instanceof boxed_type_definition && $source !== $this) {
			if (!$source->admits_extraction($this, $context)) {
				throw new diagnostic($value->source(), $value->byte_start(), 'Unsupported boxed conversion to ' . $this->name);
			}
			return match ($context) {
				conversion_context::typed_destination => value_conversion::unbox_typed,
				conversion_context::explicit_cast => value_conversion::unbox_explicit,
				conversion_context::assignment => value_conversion::unbox_assignment,
			};
		}
		if ($source === $this && $this instanceof carrier_type_definition) {
			return value_conversion::identity;
		}
		if ($source instanceof carrier_type_definition) {
			$source->require_extraction($value, $context);
		}
		if ($this instanceof carrier_type_definition) {
			return $this->prepare_construction($value, $context);
		}
		if (($source instanceof carrier_type_definition) && ($source->payload_type === $this)) {
			return value_conversion::identity | value_conversion::EXTRACT;
		}
		$conversion = $this->prepare_scalar_conversion($value, $context);
		return $conversion | ($source instanceof carrier_type_definition ? value_conversion::EXTRACT : 0);
	}

	/** Lower a prepared operand for an overload requiring this exact native representation. */
	public function lower_cpp_exact_argument(ast_expression $syntax, int $conversion /** uint16 */, string $operand, ?Cpp_Adapters $adapters = null): string
	{
		return $this->lower_cpp_conversion($conversion, $operand, $syntax, adapters: $adapters);
	}

	/** A concrete adapter request for an already-prepared conversion; ordinary conversions need none. */
	public function cpp_conversion_adapter(int $conversion /** uint16 */, ?type_definition $source): ?cpp_inline_adapter
	{
		return null;
	}

	/** Consume transport flags and the already-approved scalar operation; never re-decide admission. */
	final public function lower_cpp_conversion(int $conversion /** uint16 */, string $value, ?ast_expression $syntax = null, ?type_definition $source_type = null, ?Cpp_Adapters $adapters = null): string
	{
		$source = $source_type ?? $syntax?->type_def();
		$request = $this->cpp_conversion_adapter($conversion, $source);
		if ($request !== null) {
			if ($adapters === null) throw new \LogicException('Concrete conversion requires private adapter lowering');
			$value = $adapters->call($request, [$value]);
			$conversion = $request->continuation;
		}
		if ($conversion === value_conversion::box_scalar && $this instanceof boxed_type_definition && $syntax !== null) {
			return $this->box_cpp($syntax, $value);
		}
		if ($source instanceof boxed_type_definition && $conversion !== value_conversion::identity) {
			$context = match ($conversion) {
				value_conversion::unbox_typed => 'typed_destination',
				value_conversion::unbox_explicit => 'explicit_cast',
				value_conversion::unbox_assignment => 'assignment',
			};
			return 'scpp::boxed_' . $source->name . '_' . $context . '_' . $this->name . '(' . $value . ')';
		}
		if (($conversion & value_conversion::EXTRACT) !== 0) {
			if (!($source instanceof carrier_type_definition)) {
				throw new \LogicException('Checked extraction requires its source carrier type');
			}
			$value = $source->lower_cpp_extract($value);
			$source = $source->payload_type;
			$syntax = null;
		}
		if ($this instanceof carrier_type_definition) {
			if ($conversion === value_conversion::sentinel_carrier) {
				if (!($source instanceof carrier_sentinel_type_definition)) {
					throw new \LogicException('Sentinel conversion requires its source identity');
				}
				return $this->cpp_type() . '::' . $this->sentinel_factory($source->state) . '()';
			}
			if ($conversion === value_conversion::empty_carrier) {
				return '((void)(' . $value . '), ' . $this->cpp_type() . '{})';
			}
			if (($conversion & value_conversion::WRAP) !== 0) {
				$value = $this->payload_type->lower_scalar_conversion($conversion & value_conversion::OPERATION_MASK, $value, $syntax, $source);
				return $this->cpp_type() . '{static_cast<' . $this->payload_type->cpp_type() . '>(' . $value . ')}';
			}
		}
		return $this->lower_scalar_conversion($conversion & value_conversion::OPERATION_MASK, $value, $syntax, $source);
	}

	/** Carrier states are not scalar constant facts; do not confuse absent values with known scalar null. */
	final public function convert_constant(int $conversion /** uint16 */, int|float|bool|null $value /** int64|float64|bool|null */): int|float|bool|null|constant_evaluation /** int64|float64|bool|null|constant_evaluation */
	{
		if (($this instanceof boxed_type_definition) || ($conversion >= value_conversion::unbox_explicit && $conversion <= value_conversion::unbox_assignment)
			|| ($this instanceof carrier_type_definition) || (($conversion & value_conversion::EXTRACT) !== 0)) {
			return constant_evaluation::unavailable;
		}
		return $this->convert_scalar_constant($conversion, $value);
	}

	/** Evaluate only a known source fact; conversion handlers may explicitly decline evaluation. */
	final public function evaluate_constant(int $conversion /** uint16 */, ast_expression $source): int|float|bool|null|constant_evaluation /** int64|float64|bool|null|constant_evaluation */
	{
		return $source->constant_known() ? $this->convert_constant($conversion, $source->constant())
			: constant_evaluation::unavailable;
	}

	/** @return string[] Includes for the prepared payload conversion and destination carrier. */
	final public function conversion_headers(int $conversion /** uint16 */): array /** list<string> */
	{
		if ($this instanceof carrier_type_definition) {
			return ($conversion & value_conversion::WRAP) !== 0
				? array_merge($this->cpp_headers(), $this->payload_type->scalar_conversion_headers($conversion & value_conversion::OPERATION_MASK))
				: $this->cpp_headers();
		}
		return $this->scalar_conversion_headers($conversion & value_conversion::OPERATION_MASK);
	}

	/** Same-type initialization needs no work; other rules are generated on the destination definition. */
	public function prepare_scalar_conversion(ast_expression $value, conversion_context $context): int /** uint16 */
	{
		if ($value->value_type() === $this) {
			return value_conversion::identity;
		}
		throw new diagnostic($value->source(), $value->byte_start(),
			'Unsupported ' . (in_array($context, [conversion_context::explicit_cast, conversion_context::typed_destination], true) ? 'explicit' : 'implicit') . ' conversion from '
			. $value->value_type()->name . ' to ' . $this->name);
	}

	public function lower_scalar_conversion(int $conversion /** uint16 */, string $value, ?ast_expression $syntax = null, ?type_definition $source_type = null): string
	{
		return $conversion === value_conversion::identity ? $value
			: throw new \LogicException('Conversion does not belong to this destination type');
	}

	/** Convert only an already-checked constant; null also carries an unknown payload beside its known flag. */
	public function convert_scalar_constant(int $conversion /** uint16 */, int|float|bool|null $value /** int64|float64|bool|null */): int|float|bool|null|constant_evaluation /** int64|float64|bool|null|constant_evaluation */
	{
		return $conversion === value_conversion::identity ? $value
			: throw new \LogicException('Constant conversion does not belong to this destination type');
	}

	/** @return string[] Public runtime includes required by storage of this type. */
	public function cpp_headers(): array /** list<string> */
	{
		return [];
	}

	/** @return string[] Public runtime includes required by the selected conversion only. */
	public function scalar_conversion_headers(int $conversion /** uint16 */): array /** list<string> */
	{
		return [];
	}

	/** STATIC METHODS */
	/** A literal or a plain storage read cannot modify a sibling's borrowed value. */
	protected static function stable_cpp_operand(ast_expression $operand): bool
	{
		return ($operand instanceof ast_name_expression && $operand->local_binding() !== null)
			|| $operand instanceof ast_integer_expression || $operand instanceof ast_float_expression
			|| $operand instanceof ast_string_expression || $operand instanceof ast_keyword_expression;
	}

	/** A by-value source call on plain scalar reads cannot reach a sibling's ordinary local storage. */
	protected static function independent_local_call(ast_expression $local, ast_expression $operand): bool
	{
		if (!($local instanceof ast_name_expression && $local->local_binding() instanceof ast_local_declaration
			&& $operand instanceof ast_call_expression && $operand->cardinality === null && $operand->callable_binding() === null && $operand->constructed_record() === null)) {
			return false;
		}
		$function = $operand->function_def();
		if (!($function instanceof ast_function_declaration) || $function->parent instanceof ast_record_declaration) {
			return false;
		}
		foreach ($function->parameters as $index => $parameter) {
			$argument = $operand->arguments[$index] ?? $parameter->default_value;
			if ($parameter->mode !== passing_mode::value || !$parameter->type_def()->cpp_trivial_value()
				|| !self::stable_cpp_operand($argument) || !$argument->type_def()->cpp_trivial_value()) {
				return false;
			}
		}
		return true;
	}

	protected static function checked_integer_operand(?ast_expression $syntax, string $value, string $parameter): string
	{
		if ($syntax instanceof ast_call_expression && $syntax->cardinality === null
			&& $syntax->type_def()->cpp_type() === $parameter) {
			return $value;
		}
		return 'static_cast<' . $parameter . '>(' . $value . ')';
	}
}
