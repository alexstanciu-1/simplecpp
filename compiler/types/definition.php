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

/** Presence can be observed directly without converting or copying the stored payload. */
interface presence_test_type
{
	public function lower_cpp_presence(string $value): string;
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

/** A synchronous value cursor over a borrowed collection. Each successful advance supplies one element. */
interface collection_value_source
{
	public function iteration_value_type(): type_definition;
	/** Null uses the element itself; a constructed output slot exposes the element in its value member. */
	public function iteration_output_type(): ?string;
	/** The named receiver stays alive and stable throughout the cursor's use. */
	public function lower_cpp_value_cursor(string $receiver): string;
}

/** Entry traversal supplies the key and value from the same successful advance. */
interface collection_entry_source extends collection_value_source
{
	public function cpp_entry_key_type(): string;
	public function lower_cpp_entry_cursor(string $receiver): string;
}

/** Associated builder for a key-preserving subset. */
interface subset_source extends collection_entry_source
{
	public function subset_result_type(): ?type_definition;
}

/** Resolve a builder after the callback has supplied its result type. */
interface map_source extends collection_entry_source
{
	public function mapped_result_type(type_definition $element): ?type_definition;
}

/** A provider admitted by the general collection_map surface. */
interface collection_map_source extends map_source
{
}

/** A general map source whose mapped output is a dense sequence. */
interface sequence_map_source extends collection_map_source
{
}

/** A general map source preserving a statically typed key domain. */
interface keyed_map_source extends collection_map_source
{
}

/** Map callbacks must produce another value of this source family. */
interface same_family_map_source extends map_source
{
}

/** Construct a fresh result in encounter order. Dense builders may append. */
interface entry_result_builder
{
	public function lower_cpp_empty(): string;
	public function lower_cpp_insert_entry(string $receiver, string $key, string $value): string;
}

/** Preserve the entry cursor's key with the provider's normal keyed store. */
trait keyed_entry_insert
{
	public function lower_cpp_insert_entry(string $receiver, string $key, string $value): string
	{
		return $this->lower_cpp_write($receiver, $key, $value);
	}
}

/** Empty construction and insertion with the key supplied by the entry cursor. */
interface keyed_result_builder extends entry_result_builder
{
}

/** General filtering builds a fresh result of the input type in encounter order. */
interface collection_filter_source extends collection_entry_source, entry_result_builder
{
}

/** Survivors are appended densely in encounter order. */
interface sequence_filter_source extends collection_filter_source
{
}

/** Survivors retain their exact original keys. */
interface keyed_filter_source extends collection_filter_source, keyed_result_builder
{
}

/** A dense sequence can produce sparse subsets through the configured positional result family. */
trait positional_subset
{
	public function subset_result_type(): ?type_definition
	{
		return type_registry::positional_subset($this->iteration_value_type());
	}

	public function cpp_entry_key_type(): string
	{
		return type_int64::instance()->cpp_type();
	}
}

/** A keyed provider builds subsets with its own empty construction and keyed store. */
trait same_type_subset
{
	public function subset_result_type(): type_definition
	{
		return $this;
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

/** A protected selection can lend its live slot for a synchronous call. */
interface writable_iteration_type
{
	public function lower_cpp_iteration_reference(string $receiver, int $loop_start): string;
}

/** Published traversal that can advance without materializing a value. */
interface key_iterable_type extends iterable_type
{
	public function lower_cpp_iteration_next_key(string $receiver, string $output, string $prefix = ''): string;
	public function lower_cpp_iteration_current_value(string $receiver, string $position = 'iteration_position'): string;
}

/** Unary observations on a published collection. Native uint8. */
enum collection_cardinality: int
{
	case count = 1;
	case empty = 2;
}

/** A type publishes a result for each cardinality query it admits. */
interface counted_collection_type
{
	public function cardinality_result(collection_cardinality $operation): ?type_definition;
	public function lower_cpp_cardinality(collection_cardinality $operation, string $receiver): string;
}

/** Python membership uses the receiver's containment semantics for a prepared needle. */
interface membership_type
{
	public function prepare_membership(ast_binary_expression $site): void;
	/** @param array<string,bool> $headers */
	public function lower_cpp_membership(ast_expression $needle, string $key, string $receiver, array &$headers): string;
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

/** An owning runtime text builder eligible for a readonly free-function parameter. */
interface text_builder_type
{
}

/** Opaque resource payload. Boolean sentinels on its carriers are distinct from every payload. */
interface runtime_handle_type
{
}

/** A runtime value with published typed fields, such as a JSON parse diagnostic. */
interface runtime_field_type
{
	public function runtime_field(string $name): ?type_definition;
}

/** Read-only runtime fields whose native getters construct the source value on access. */
interface runtime_field_getters extends runtime_field_type
{
}

/** A receiver with a non-addressable indexed value, e.g. text[0]. */

interface index_read_type
{
	public function prepare_index(ast_index_expression $value): void;
	public function lower_cpp_index(ast_expression $key, string $receiver, string $index): string;
}

/**
 * After operand evaluation, locating an element preserves the receiver and selects the
 * same value or checked failure on an equal snapshot. Materializing the result has
 * the element type's own copy and destruction effects.
 */
interface readonly_index_lookup_type extends index_read_type
{
}

/** An exact named slot can bind to const T& and observe replacement through a separate writer. */
interface readonly_collection_slot
{
}

/** The provider admits Python subscripting and owns key validation, result type and checked lookup. */
interface python_index_read_type extends index_read_type
{
}

/** Hold the selected receiver by value across suspension while computing a Python index. */
interface python_snapshot_index_read_type extends python_index_read_type
{
}

/** An immutable descriptor can reuse this provider's checked read while the owner is stable. */
interface scoped_byte_read_type extends index_read_type
{
	public function byte_view_header(): string;
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

/** Exact native element access for codec traversal of a prepared ordered sequence. */
interface native_sequence_type extends counted_collection_type
{
	public function write_type(): type_definition;
	public function lower_cpp_element_read(string $receiver, string $index): string;
	public function lower_cpp_append_value(string $receiver, string $value): string;
}

/** Source append on a writable receiver with a published value parameter. */
interface appendable_collection_type
{
	public function write_type(): type_definition;
	public function lower_cpp_append_value(string $receiver, string $value): string;
}

/** Single-level keyed store; the receiver owns key admission and the destination type for its values. */
interface index_write_type
{
	public function prepare_key(ast_expression $key): void;
	public function write_type(): type_definition;
	/** Null permits direct lowering. A staged RHS (including a bare await with no prelude) follows key selection. */
	public function lower_cpp_store(ast_index_assignment $write, string $receiver, string $key, string $value,
		?Cpp_Adapters $adapters = null, ?string $rhs_steps = null): string;
}

/** Read/modify/write collections replace an approved element value without exposing an element reference. */
interface indexed_mutation_type extends indexed_collection_type, index_write_type
{
	public function lower_cpp_mutation_read(ast_expression $key, string $receiver, string $index): string;
	public function lower_cpp_replace(ast_expression $key, string $receiver, string $index, string $value): string;
}

/** A keyed write can descend through an existing slot, rechecking its saved key after user code. */
interface nested_index_storage_type extends indexed_mutation_type
{
	public function lower_cpp_key(ast_expression $syntax, string $key): string;
	public function lower_cpp_slot(string $receiver, string $key): string;
	public function lower_cpp_write(string $receiver, string $key, string $value): string;
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

/** Vectors, hashes and boxed collections normalize a key and replace through a runtime setter. */
trait collection_keyed_store
{
	abstract public function write_type(): type_definition;
	abstract public function lower_cpp_key(ast_expression $syntax, string $key): string;
	abstract public function lower_cpp_write(string $receiver, string $key, string $value): string;

	public function lower_cpp_replace(ast_expression $key, string $receiver, string $index, string $value): string
	{
		return $this->lower_cpp_write($receiver, $this->lower_cpp_key($key, $index), $value) . ';';
	}

	public function lower_cpp_store(ast_index_assignment $write, string $receiver, string $key, string $value,
		?Cpp_Adapters $adapters = null, ?string $rhs_steps = null): string
	{
		$value = $this->write_type()->lower_cpp_exact_argument($write->value, $write->conversion(), $value, $adapters);
		if ($rhs_steps === null) return $this->lower_cpp_replace($write->index, $receiver, $key, $value);
		return '{ const auto write_key = ' . $this->lower_cpp_key($write->index, $key) . '; ' . $rhs_steps . ' '
			. $this->lower_cpp_write($receiver, 'write_key', $value) . '; }';
	}
}

/** Ordered stores snapshot the normalized key and converted value before invoking the runtime member. */
trait ordered_keyed_store
{
	abstract public function write_type(): type_definition;
	abstract public function lower_cpp_key(ast_expression $syntax, string $key): string;
	abstract public function lower_cpp_write(string $receiver, string $key, string $value): string;

	public function lower_cpp_store(ast_index_assignment $write, string $receiver, string $key, string $value, ?Cpp_Adapters $adapters = null, ?string $rhs_steps = null): string
	{
		$value = $this->write_type()->lower_cpp_exact_argument($write->value, $write->conversion(), $value, $adapters);
		return '{ const auto write_key = ' . $this->lower_cpp_key($write->index, $key) . '; ' . ($rhs_steps === null ? '' : $rhs_steps . ' ') . 'const auto write_value = ' . $value . '; '
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

	/** Read the prepared integer value of a literal or constant label. */
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
	/** Published Decoder operation for a scalar leaf. */
	public function structural_reader(): ?string
	{
		return null;
	}

	/** Canonical types match by identity. Callable contracts also compare their signature components. */
	public function matches(type_definition $other): bool
	{
		return $this === $other;
	}

	/** Stable identity for concrete recipes, including resolved specialization arguments. */
	public function concrete_identity(): string
	{
		return $this::class . ':' . $this->name;
	}

	/** A by-value parameter must own its storage until its coroutine frame releases it. */
	public function supports_async_parameter(): bool
	{
		return $this instanceof application_collection_type || ($this !== type_void::instance() && type_registry::async_value($this));
	}

	/** Cache a binding only for an eligible canonical runtime value requested by a producer. */
	public function async_task(): ?async_task_binding
	{
		static $bindings = [];
		if (isset($bindings[$this::class])) return $bindings[$this::class];
		if (!type_registry::HAS_ASYNC_FRAMES || !type_registry::async_value($this)) return null;
		return $bindings[$this::class] = new async_task_binding($this->concrete_identity());
	}

	/** Exact label domain for a single constant selection. */
	public function dispatch_label_type(): type_definition
	{
		return $this;
	}

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
		return $name === Stringable_Contract::METHOD ? $this->native_string_formatter() : null;
	}

	/** Published native formatter for the common to_string capability. */
	public function native_string_formatter(bool $fallback = false): ?runtime_function
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

	/** Whether scalar condition lowering is published for this type. */
	public function supports_scalar_condition(): bool
	{
		return false;
	}

	/** Empty for types without a published PHP scalar kind. */
	public function predicate_kind(): string
	{
		return '';
	}

	/** Output admission is separate from string conversion. */
	public function supports_output(flavor $flavor): bool
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
	public function prepare_compound(ast_assignment_statement $value, type_definition $right): void
	{
		if (!$this->supports_compound($value->operator) || !$this->binary_syntax_allowed($value->operator, $value) || ($this->binary_result($value->operator, $right) !== $this)) {
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

	/** Positive proof that copying and destroying an independent element has no observable effect. */
	public function copy_has_no_observable_effects(): bool
	{
		return false;
	}

	/** Only private backing-storage allocation failure may be lost by skipping this collection copy. */
	public function private_copy_allocation_only(): bool
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

	/** Initialize owned storage from a binding using its published copy contract or its source conversion rules. */
	final public function prepare_local_initializer(ast_expression $value, conversion_context $context): int /** uint16 */
	{
		if ($value instanceof ast_name_expression && $value->local_binding() !== null && $value->type_def() === $this) {
			$copy = $this->concrete_storage_support('copy_constructible');
			if ($copy === true) return value_conversion::identity;
			if ($copy === false) throw new diagnostic($value->source(), $value->byte_start(), 'Cannot copy a local of type ' . $this->name);
		}
		return $this->prepare_declaration_conversion($value, $context);
	}

	/** A written PHP integer declaration explicitly requests decimal parsing. */
	final public function prepare_declaration_conversion(ast_expression $value, conversion_context $context): int /** uint16 */
	{
		if ($context === conversion_context::typed_destination && $value->source()->flavor === flavor::php
			&& $this === type_int64::instance() && $value->value_type() instanceof type_string) {
			if ($value->type_def() instanceof carrier_type_definition) $value->type_def()->require_extraction($value, $context);
			return $this->prepare_conversion($value, conversion_context::explicit_cast);
		}
		return $this->prepare_conversion($value, $context);
	}

	/** Select scalar work once, with carrier transport encoded independently in the prepared integer. */
	final public function prepare_conversion(ast_expression $value, conversion_context $context): int /** uint16 */
	{
		$source = $value->type_def();
		if ($context === conversion_context::value_argument) {
			if ($value->source()->flavor === flavor::php && $this instanceof carrier_type_definition && $source !== $this) {
				if (!$this->accepts_empty() || ($source instanceof carrier_type_definition && $source->payload_type !== $this->payload_type)) {
					throw new diagnostic($value->source(), $value->byte_start(),
						'Cannot pass ' . $source->name . ' to a parameter of type ' . $this->name);
				}
				if ($source instanceof boxed_type_definition) {
					if (!$source->admits_extraction($this->payload_type, conversion_context::typed_destination)) {
						throw new diagnostic($value->source(), $value->byte_start(), 'Unsupported boxed conversion to ' . $this->name);
					}
					return value_conversion::boxed_nullable_argument;
				}
			}
			// Scalar conversion, checked extraction and wrapping reuse the typed-destination plan.
			$context = conversion_context::typed_destination;
		}
		$transport = 0;
		if ($source instanceof carrier_type_definition) {
			if ($source === $this) return value_conversion::identity;
			$source->require_extraction($value, $context);
			$transport = value_conversion::EXTRACT;
			if ($source instanceof record_result_type_definition && $source->payload_type === $this) return $transport | value_conversion::identity;
			if ($value->source()->flavor === flavor::php && $context !== conversion_context::inference) {
				$source = carrier_operand_payload($source)
					?? throw new diagnostic($value->source(), $value->byte_start(), 'Carrier does not participate in payload conversion');
				if ($source === $this) return $transport | value_conversion::identity;
				if ($this instanceof boxed_type_definition && $this->accepts_scalar($source)) return $transport | value_conversion::box_scalar;
			}
		}
		if ($context === conversion_context::explicit_cast && $value->source()->flavor === flavor::php
			&& $this === type_int64::instance() && $source instanceof type_string) {
			$bytes = constant_dispatch::string_label_bytes($value);
			if ($bytes === null) return $transport | value_conversion::decimal_text;
			if (!preg_match('/\A[+-]?[0-9]+\z/', $bytes)) throw new diagnostic($value->source(), $value->byte_start(), 'Invalid decimal integer text');
			$digits = ltrim($bytes, '+-0');
			$limit = $bytes[0] === '-' ? '9223372036854775808' : '9223372036854775807';
			if (strlen($digits) > strlen($limit) || (strlen($digits) === strlen($limit) && strcmp($digits, $limit) > 0)) {
				throw new diagnostic($value->source(), $value->byte_start(), 'Decimal text is outside int64 range');
			}
			return value_conversion::decimal_literal;
		}
		// A PHP carrier destination applies this context to its payload, including boxed conversions.
		if ($this instanceof carrier_type_definition && (!($source instanceof boxed_type_definition) || $value->source()->flavor === flavor::php)) {
			return $this->prepare_construction($value, $context);
		}
		if ($source instanceof boxed_type_definition && $source !== $this) {
			if (!$source->admits_extraction($this, $context)) {
				throw new diagnostic($value->source(), $value->byte_start(), 'Unsupported boxed conversion to ' . $this->name);
			}
			return $transport | match ($context) {
				conversion_context::typed_destination => value_conversion::unbox_typed,
				conversion_context::explicit_cast => value_conversion::unbox_explicit,
				conversion_context::assignment => value_conversion::unbox_assignment,
			};
		}
		if (($source instanceof carrier_type_definition) && ($source->payload_type === $this)) {
			return value_conversion::identity | value_conversion::EXTRACT;
		}
		$conversion = $this->prepare_scalar_conversion($value, $context);
		return $conversion | $transport;
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
		if (($conversion & value_conversion::EXTRACT) !== 0) {
			if (!($source instanceof carrier_type_definition)) {
				throw new \LogicException('Checked extraction requires its source carrier type');
			}
			$value = $source->lower_cpp_extract($value);
			$source = $source->payload_type;
			$conversion &= ~value_conversion::EXTRACT;
			if (($conversion & value_conversion::OPERATION_MASK) !== value_conversion::box_scalar) $syntax = null;
		}
		if ($conversion === value_conversion::decimal_text) return 'scpp::php::parse_decimal_int64(' . $value . ')';
		if ($conversion === value_conversion::decimal_literal) {
			$integer = (int)constant_dispatch::string_label_bytes($syntax);
			return $integer === \PHP_INT_MIN ? '(-9223372036854775807LL - 1LL)' : $integer . 'LL';
		}
		if ($conversion === value_conversion::box_scalar && $this instanceof boxed_type_definition && $syntax !== null) {
			return $this->box_cpp($syntax, $value);
		}
		if ($source instanceof boxed_type_definition && in_array($conversion,
			[value_conversion::unbox_explicit, value_conversion::unbox_typed, value_conversion::unbox_assignment], true)) {
			$context = match ($conversion) {
				value_conversion::unbox_typed => 'typed_destination',
				value_conversion::unbox_explicit => 'explicit_cast',
				value_conversion::unbox_assignment => 'assignment',
			};
			return 'scpp::boxed_' . $source->name . '_' . $context . '_' . $this->name . '(' . $value . ')';
		}
		if ($this instanceof carrier_type_definition) {
			if ($conversion === value_conversion::forwarded_error) {
				if ($source instanceof shared_record_type_definition) {
					$value = $adapters->call(Cpp_Shared_View::request($source, type_registry::exception(), false), [$value]);
				}
				return $this->cpp_type() . '{' . $value . '}';
			}
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
				$value = $this->payload_type->lower_cpp_conversion($conversion & value_conversion::OPERATION_MASK, $value, $syntax, $source, $adapters);
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
		if ($conversion === value_conversion::stringable_text) return constant_evaluation::unavailable;
		if ($conversion === value_conversion::decimal_literal) return (int)constant_dispatch::string_label_bytes($source);
		return $source->constant_known() ? $this->convert_constant($conversion, $source->constant())
			: constant_evaluation::unavailable;
	}

	/** @return string[] Includes for the prepared payload conversion and destination carrier. */
	final public function conversion_headers(int $conversion /** uint16 */): array /** list<string> */
	{
		if (($conversion & value_conversion::OPERATION_MASK) === value_conversion::decimal_text) return ['scpp/php/parse_decimal_int64.hpp'];
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
			&& $operand instanceof ast_call_expression && $operand->has_direct_signature())) {
			return false;
		}
		$function = $operand->function_def();
		if (!($function instanceof ast_function_declaration) || $function->parent instanceof ast_record_declaration) {
			return false;
		}
		$parameters = $operand instanceof ast_named_call ? $operand->named->evaluation_parameters($function->parameters) : $function->parameters;
		foreach ($parameters as $index => $parameter) {
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
