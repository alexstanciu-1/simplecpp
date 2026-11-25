<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Spells the already-selected entry exit conversion through the public runtime boundary. */
final class Cpp_Exit
{
	/** Rendering follows the settled conversion; it never infers or validates source semantics. */
	public static function expression(exit_conversion $conversion, string $value): string
	{
		return match ($conversion) {
			exit_conversion::proven => 'static_cast<int>(' . $value . ')',
				exit_conversion::checked => Cpp_Entry_Binding::checked($value),
		};
	}
}

/**
 * Lowers completed bodies to C++ using settled declaration links and value facts, without performing name resolution.
 */
final class Cpp_Emitter
{
	use Cpp_Generator;

	/** PROPERTIES */
	/** Native non-owning; null keeps standalone single-file emission self-contained. */
	private readonly ?Cpp_Headers $headers;
	private readonly string $root;
	/** Set while lowering a function that calls a generated flavor adapter. */
	private ?string $adapter_flavor = null;
	/** Initializer expressions cannot use imports declared inside the constructor body. */
	private bool $qualified_adapters = false;
	/** Non-owning field whose checked descriptor is live in the current emitted function. */
	private ?ast_field_expression $byte_view = null;
	/** Count record/handle expressions during the existing walk to preserve observable temporary destruction. */
	private int $record_expressions = 0;
	/** Incremented when this recursive lowering walk emits a native suspension. */
	private int $suspensions = 0;
	/** A staged owner needs destruction at the end of its consuming operand before a sibling runs. */
	private int $async_owned_steps = 0;
	private bool $async_body = false;
	/**
	 * Ordered statements pending in the current coroutine full expression.
	 * @var list<string>
	 */
	private array $async_steps /** list<string> */ = [];
	/** Owning lowering context for the innermost protected callable collecting exits. */
	private ?Cpp_Finally_Exit $finally_exit = null;
	/** Labels requested only by emitted transfers and consumed at their lexical destinations. @var bool[] */
	private array $transfer_labels = [];
	/** Private demand collected during this source unit’s existing lowering walk. */
	private Cpp_Adapters $inline_adapters;
	/** Native non-owning during emission of the selected function. */
	private ?Destination_Plan $destination = null;
	/** Private concrete type demand, sharing the source adapter publication and cleanup boundary. */
	private Cpp_Concrete_Collections $concrete_collections;
	/** Concrete task demand for the current unit. @var array<string,type_definition> */
	private array $async_results = [];

	/** @var int[] Private lowering requirements; no prepared AST mutation. */
	private array $enum_helpers /** array<string,uint8> */ = [];

	/** @var cpp_generator_layout[] Source-owned layouts available after the lowering join. */
	private array $generator_layouts /** array<string,cpp_generator_layout> */;
	/** @var string[] Local storage names during one producer resume-body emission. */
	private array $generator_locals /** array<uint32,string> */ = [];

	/** CONSTRUCTOR */
	/** @param cpp_generator_layout[] $generator_layouts
	 * @param Destination_Plan[] $destination_plans
	 */
	public function __construct(?Cpp_Headers $headers = null, string $root = 'App', array $generator_layouts /** array<string,cpp_generator_layout> */ = [],
		private readonly array $destination_plans /** array<string,Destination_Plan> */ = [])
	{
		$this->headers = $headers;
		$this->root = $root;
		$this->generator_layouts = $generator_layouts;
		$this->inline_adapters = new Cpp_Adapters($root);
		$this->concrete_collections = new Cpp_Concrete_Collections($root);
	}

	/** METHODS */
	/** Route prepared conversions through private adapter demand without changing ordinary type-owned lowering. */
	private function conversion(type_definition $type, int $conversion /** uint16 */, string $value,
		?ast_expression $syntax = null, ?type_definition $source_type = null): string
	{
		if ($conversion === value_conversion::guarded_intersection) {
			$from = $syntax->type_def();
			$base = $from->payload === $type->base->payload ? $value
				: $this->inline_adapters->call(Cpp_Shared_View::request($from, $type->base, false), [$value]);
			return $type->cpp_type() . '{' . $base . ', scpp_guard_' . $syntax->guard()->start . '}';
		}
		return $type->lower_cpp_conversion($conversion, $value, $syntax, $source_type, $this->inline_adapters);
	}

	/**
	 * Apply the prepared conversions and publish the operation's runtime dependencies.
	 * @param array<string,bool> $required_headers
	 */
	private function binary_operands(ast_binary_expression $value, string &$left, string &$right, array &$required_headers): void
	{
		$type = $value->execution_type();
		if ($value->left_conversion !== value_conversion::identity) {
			$left = $this->conversion($type, $value->left_conversion, $left, $value->left);
			foreach ($type->conversion_headers($value->left_conversion) as $header) {
				$required_headers[$header] = true;
			}
		}
		if ($value->right_conversion !== value_conversion::identity) {
			$right = $this->conversion($type, $value->right_conversion, $right, $value->right);
			foreach ($type->conversion_headers($value->right_conversion) as $header) {
				$required_headers[$header] = true;
			}
		}
		foreach ($type->binary_headers($value->operator) as $header) {
			$required_headers[$header] = true;
		}
	}

	/**
	 * Evaluate the needle once before the receiver. A chain already holds the needle.
	 * @param array<string,bool> $required_headers
	 */
	private function membership_test(ast_binary_expression $value, string $left, string $right, array &$required_headers /** array<string,bool> */, bool $key_captured): string
	{
		/** @var membership_type $type */
		$type = $value->execution_type();
		$literal = $value->left instanceof ast_integer_expression || $value->left instanceof ast_float_expression
			|| $value->left instanceof ast_keyword_expression;
		$local_pair = $value->left instanceof ast_name_expression && $value->left->local_binding() !== null
			&& $value->right instanceof ast_name_expression && $value->right->local_binding() !== null;
		$capture = !$key_captured && !$literal && !$local_pair;
		$key = $capture ? 'membership_key' : $left;
		$test = $type->lower_cpp_membership($value->left, $key, $right, $required_headers);
		if ($value->operator === binary_operator::key_not_in) $test = '!(' . $test . ')';
		return $capture ? '([&]() { const auto membership_key = ' . $left . '; return ' . $test . '; }())' : $test;
	}

	/**
	 * A chain can carry scalar literals verbatim. Capture other values before later operands can change them.
	 * @param array<string,ast_global_declaration> $global_references
	 * @param array<string,ast_function_declaration> $declarations
	 * @param array<string,bool> $required_headers
	 */
	private function chain_operand(ast_expression $operand, int $index, string &$steps, array &$global_references,
		array &$declarations, array &$required_headers): string
	{
		[$expression, $before] = $this->staged_operand($operand, $global_references, $declarations, $required_headers);
		if ($before !== []) $steps .= implode(' ', $before) . ' ';
		if ($operand instanceof ast_integer_expression || $operand instanceof ast_float_expression
			|| $operand instanceof ast_keyword_expression) return $expression;
		$name = 'chain_' . $index;
		$steps .= 'const auto ' . $name . ' = ' . $expression . '; ';
		return $name;
	}

	/** Concrete headers requested by the last source emission; no AST links escape into the output bundle. @return string[] */
	public function adapter_files(): array /** array<string,string> */
	{
		$files = $this->concrete_collections->files() + $this->inline_adapters->files();
		foreach ($this->async_results as $task => $type) {
			$path = async_task_binding::header_path($task);
			$files[$path] = $this->headers?->grouped_declaration($path) ?? \Scpp\Api\asyncTaskHeader(
				new cpp_concrete_type($type, $this->root), $task, $type !== type_void::instance(), $this->root);
		}
		return $files;
	}

	/** Spell an enum definition and its requested helper prototypes. @param bool[] $includes */
	public function enum_declaration(ast_enum_declaration $node, array &$includes /** array<string,bool> */): string
	{
		$type = $node->definition;
		$storage = $type->storage();
		foreach ($storage->cpp_headers() as $header) $includes[$header] = true;
		$lines = [];
		$ordinal = 0;
		foreach ($node->cases as $case) {
			$lines[] = "\t" . Cpp_Identifier::source($case->short_name) . ' = '
				. ($type->backing() === type_string::instance() ? $ordinal++ : type_int64::instance()->lower_cpp_literal($case->initializer)) . ',';
		}
		$helper = '';
		foreach (dependency_use::enum_helper_uses as $use) {
			if (!$this->needs_enum_helper($node, $use)) continue;
			$binding = $type->helper_function($use);
			$includes[$binding->header] = true;
			foreach ($binding->return_type_def()->cpp_headers() as $header) $includes[$header] = true;
			$helper .= $this->enum_helper_signature($binding) . ";\n";
		}
		return self::namespace_body($type->cpp_namespace(), 'enum class ' . Cpp_Identifier::source($node->short_name)
			. ' : ' . $storage->cpp_type() . " {\n" . implode("\n", $lines) . "\n};\n") . $helper;
	}

	/** Spell the payload layout shared by private units and published headers. @param bool[] $includes */
	public function record_declaration(ast_record_declaration $node, array &$includes /** array<string,bool> */): string
	{
		$layout = [];
		$base = $node->definition->base();
		$has_local_constructor = isset($node->methods['__construct']);
		$inherited_constructor = $has_local_constructor ? null : $base?->constructor();
		foreach ($node->fields as $field) {
			foreach ($field->type_def()->cpp_headers() as $header) $includes[$header] = true;
			$type = $field->storage_type();
			$qualifier = ($type instanceof record_type_definition || $type instanceof record_handle_definition || $type instanceof enum_type_definition || $type instanceof application_collection_type) && $this->root !== '' ? '::' . $this->root : '';
			$default = $field->initializer === null ? '{}' : ' = ' . $this->declaration_initialization(
				$type, $field->initializer, $field->conversion, $includes);
			if ($type instanceof fixed_array_type) {
				$layout[] = "\t" . $type->declarator(Cpp_Identifier::source($field->short_name)) . '{};';
				continue;
			}
			$layout[] = "\t" . $qualifier . '::' . $type->cpp_type() . ' ' . Cpp_Identifier::source($field->short_name) . $default . ';';
		}
		foreach ($node->static_properties as $property) {
			$type = $property->type_def();
			foreach ($type->cpp_headers() as $header) $includes[$header] = true;
			$layout[] = "\tstatic " . $type->cpp_type() . ' ' . self::property_name($property) . ';';
		}
		$virtual = $node->definition->polymorphic();
		$authored = isset($node->methods['__destruct']);
		if ($virtual || $authored) {
			$name = Cpp_Identifier::source($node->short_name);
			$layout[] = "\t" . ($virtual ? 'virtual ' : '') . "~$name() noexcept" . ($authored ? ';' : ' = default;');
			if (!$has_local_constructor && ($inherited_constructor?->accepts_argument_count(0) ?? true)) {
				$layout[] = "\t$name() = default;";
			}
			$layout[] = "\t$name(const $name&) = default;";
			$layout[] = "\t$name($name&&) = default;";
			$layout[] = "\t$name& operator=(const $name&) = default;";
			$layout[] = "\t$name& operator=($name&&) = default;";
		}
		foreach ($node->definition->effective_methods() as $method) {
			if (!$method->has_virtual_dispatch()) continue;
			if ($node->kind === record_kind::interface_type) {
				$inherited = self::exact_interface_slot($node, $method);
				if ($inherited !== null) {
					// Defaults belong to the source declaration. Reuse the slot without adding a final overrider.
					$layout[] = "\tusing " . $inherited->parent->definition->cpp_type() . '::' . self::virtual_name($method) . ';';
					continue;
				}
			}
			$type = $method->return_type_def();
			foreach ($type->cpp_headers() as $header) $includes[$header] = true;
			$result = $this->member_type($type);
			$layout[] = "\t" . (($method->is_virtual || $method->is_abstract || $method->introduces_slot) ? 'virtual ' : '') . $result . ' ' . self::virtual_name($method)
				. '(' . implode(', ', $this->parameters($method, $includes, false)) . ')'
				. ($method->is_override && !$method->introduces_slot ? ' override' : '') . ($method->is_final ? ' final' : '') . ($method->is_abstract ? ' = 0' : '') . ';';
		}
		foreach (self::variance_bridges($node) as [$contract, $implementation]) {
			foreach ($contract->return_type_def()->cpp_headers() as $header) $includes[$header] = true;
			$layout[] = "\t" . $this->member_type($contract->return_type_def()) . ' ' . self::virtual_name($contract)
				. '(' . implode(', ', $this->parameters($contract, $includes, false, true)) . ') override;';
		}
		$final_slots = [];
		foreach ($node->definition->interface_forwards as $method) {
			foreach ($method->return_type_def()->cpp_headers() as $header) $includes[$header] = true;
			$new_slot = !$method->has_virtual_dispatch() && !$base?->interface_dispatch($method->short_name)
				&& self::exact_interface_slot($node, $method) === null;
			$slot = "\t" . ($new_slot ? 'virtual ' : '') . $this->member_type($method->return_type_def()) . ' ' . self::virtual_name($method)
				. '(' . implode(', ', $this->parameters($method, $includes, false, true)) . ')' . ($new_slot ? '' : ' override');
			if ($method->is_final && $method->has_virtual_dispatch()) {
				$final_slots[] = $slot . ' final;';
				$layout[] = "\tusing " . $base->cpp_type() . '::' . self::virtual_name($method) . ';';
			} else {
				$layout[] = $slot . ';';
			}
		}
		if ($has_local_constructor) {
			$constructor = $node->methods['__construct'];
			$params = $this->constructor_parameters($constructor, $includes);
			if (self::self_value_constructor($constructor)) $params = 'int, ' . $params;
			$name = Cpp_Identifier::source($node->short_name);
			$layout[] = "\t" . $name . '(' . $params . ');';
			if ($params !== '' && $constructor->accepts_argument_count(0)) $layout[] = "\t" . $name . '();';
			if ($this->emits_reordered_base($constructor)) {
				// Both entry points construct the actual base subobject with the same parameter storage.
				$refs = $this->parameters($constructor, $includes, false, borrowed_inputs: true);
				$fields = array_map(static fn(int $slot) => 'values.slot_' . $slot, array_keys($refs));
				$layout[] = "\tstruct scpp_escape_parameter_refs {};";
				$layout[] = "\t" . $name . '(scpp_escape_parameter_refs, ' . implode(', ', $refs) . ');';
				$layout[] = "\tstruct scpp_escape_named_base {};";
				$layout[] = "\ttemplate<class scpp_escape_values_type> " . $name . '(scpp_escape_named_base, scpp_escape_values_type&& values) : '
					. $name . '(scpp_escape_parameter_refs{}, ' . implode(', ', $fields) . ') {}';
			}
		}
		if ($inherited_constructor !== null) {
			$layout[] = "\tusing " . $base->cpp_type() . '::' . Cpp_Identifier::source($base->declaration->short_name) . ';';
		}
		$bases = $base === null ? [] : ['public ' . $base->cpp_type()];
		$exception = $node->definition->exception_base();
		if ($exception !== null) {
			foreach ($exception->cpp_headers() as $header) $includes[$header] = true;
			$bases[] = 'public ::scpp::exception_payload';
		}
		$interface_bases = [];
		foreach ($node->definition->interfaces() as $interface) {
			if ($final_slots === [] || !$base->derives_from($interface)) $interface_bases[] = 'public virtual ' . $interface->cpp_type();
		}
		$view = '';
		if ($final_slots !== []) {
			// A sibling base can implement the new interfaces without overriding the class's final slot.
			$name = self::final_view_name($node);
			$view = 'struct ' . $name . ' : ' . implode(', ', $interface_bases) . " {\n" . implode("\n", $final_slots) . "\n};\n";
			$interface_bases = ['public ::' . ($this->root === '' ? '' : $this->root . '::') . $name];
		}
		array_push($bases, ...$interface_bases);
		return $this->record_signature_forwards($node) . $view . self::namespace_body($node->definition->cpp_namespace(), 'struct ' . Cpp_Identifier::source($node->short_name)
			. ($node->is_final ? ' final' : '') . ($bases === [] ? '' : ' : ' . implode(', ', $bases)) . " {\n" . implode("\n", $layout) . "\n};\n");
	}

	/** Spell a free prototype, including a generator layout when needed. @param bool[] $includes */
	public function function_declaration(ast_function_declaration $node, array &$includes /** array<string,bool> */): string
	{
		if ($node->has_virtual_dispatch()) return '';
		if ($node->is_async) {
			$lines = [];
			if ($this->headers === null || $this->headers->uses_helper($node, dependency_use::async_task)) $lines[] = $this->signature($node, $includes) . ';';
			if ($this->headers === null || $this->headers->uses_helper($node, dependency_use::async_wait)) $lines[] = $this->wait_signature($node, $includes) . ';';
			return implode("\n", $lines);
		}
		return ($node->return_type_def() instanceof generator_type_definition ? $this->generator_layout($node->return_type_def(), $includes) : '')
			. $this->signature($node, $includes) . ';';
	}

	/** An ordinary blocking entry shares the producer's prepared parameter declarations. @param bool[] $headers */
	private function wait_signature(ast_function_declaration $node, array &$headers): string
	{
		foreach ($node->return_type_def()->cpp_headers() as $header) $headers[$header] = true;
		return $node->return_type_def()->cpp_type() . ' ' . self::function_name($node) . 'wait(' . implode(', ', $this->parameters($node, $headers, false)) . ')';
	}

	/** Spell the extern declaration shared by private units and published headers. @param bool[] $includes */
	public function global_declaration(ast_global_declaration $node, array &$includes /** array<string,bool> */): string
	{
		foreach ($node->type_def()->cpp_headers() as $header) {
			$includes[$header] = true;
		}
		return 'extern ' . $this->signature_type($node->type_def(), false) . ' ' . self::global_name($node) . ';';
	}

	private function needs_enum_helper(ast_enum_declaration $enum, dependency_use $use): bool
	{
		return $this->headers?->uses_helper($enum, $use)
			?? ((($this->enum_helpers[$enum->symbol_key()] ?? 0) & $use->value) !== 0);
	}

	/** Each enum helper has one runtime operand and a concrete signature. */
	private function enum_helper_signature(runtime_function $binding): string
	{
		return $binding->return_type_def()->cpp_type() . ' ' . $binding->symbol . '(' . $binding->parameters[0]->cpp_type() . ' value)';
	}

	/** Emit from the single owning case inventory; preparation never builds another lookup table. */
	private function enum_helper(enum_type_definition $type, dependency_use $use): string
	{
		$signature = $this->enum_helper_signature($type->helper_function($use));
		$nullable = $use === dependency_use::enum_try_from_value;
		$checked = $use === dependency_use::enum_from_value;
		$failure = 'throw std::runtime_error("Invalid value for enum helper");';
		if ($checked && $this->needs_enum_helper($type->declaration, dependency_use::enum_try_from_value)) {
			$lookup = $type->helper_function(dependency_use::enum_try_from_value);
			return $signature . " {\n\tauto found = " . $lookup->symbol . "(std::move(value));\n\tif (found.has_value()) return *found;\n\t"
				. $failure . "\n}\n";
		}
		$lines = [];
		$string_lookup = $nullable || ($checked && $type->backing() === type_string::instance());
		foreach ($type->declaration->cases as $case) {
			if ($string_lookup) {
				$literal = type_string::instance()->lower_cpp_literal($case->initializer);
				$comparison = type_string::instance()->lower_cpp_binary(binary_operator::equal, 'value', $literal, type_string::instance());
				$lines[] = "\tif (" . $comparison . ') return ' . $type->cpp_case($case) . ';';
				continue;
			}
			$match = $use === dependency_use::enum_from_value ? type_int64::instance()->lower_cpp_literal($case->initializer) : $type->cpp_case($case);
			$result = match ($use) {
				dependency_use::enum_name => type_string::instance()->cpp_type() . '{' . Cpp_String_Literals::bytes($case->short_name) . ', ' . strlen($case->short_name) . '}',
				dependency_use::enum_value => type_string::instance()->lower_cpp_literal($case->initializer),
				dependency_use::enum_from_value, dependency_use::enum_try_from_value => $type->cpp_case($case),
			};
			$lines[] = "\tcase " . $match . ': return ' . $result . ';';
		}
		$body = implode("\n", $lines);
		if ($nullable) $failure = 'return {};';
		$body = $string_lookup ? $body . "\n\t" . $failure : "\tswitch (value) {\n" . $body . "\n\tdefault: " . $failure . "\n\t}";
		return $signature . " {\n" . $body . "\n}\n";
	}

	/** Lower an approved declaration default and collect its public header dependencies. @param bool[] $headers */
	private function declaration_initialization(type_definition $type, ast_expression $literal, int $conversion /** uint16 */,
		array &$headers /** array<string,bool> */): string
	{
		foreach (array_merge($literal->type_def()->cpp_headers(), $type->conversion_headers($conversion)) as $header) {
			$headers[$header] = true;
		}
		if ($literal instanceof ast_name_expression && $type instanceof enum_type_definition) return $type->cpp_case($literal->declaration());
		return self::plain_integer_literal($literal, $conversion)
			?? $this->conversion($type, $conversion, $literal->type_def()->lower_cpp_literal($literal), $literal);
	}

	/** Two-pointer owner layout available before either the record or its counted allocation is complete. @param bool[] $includes */
	public function owner_declaration(shared_record_type_definition $type, array &$includes /** array<string,bool> */): string
	{
		$includes['scpp/owner_access.hpp'] = true;
		$payload = $type->payload;
		$name = Cpp_Identifier::source($payload->declaration->short_name);
		$forward = $this->record_signature_forwards($payload->declaration) . self::namespace_body($payload->cpp_namespace(), "struct $name;\n");
		$owner = $type->cpp_type();
		$weak = $type->payload->weak->cpp_type();
		$record = $payload->cpp_type();
		$guard = $this->owner_guard($type) . '_DECL';
		$params = $this->constructor_parameters($payload->constructor(), $includes);
		$factory_declaration = $payload->declaration->is_abstract ? '' : "\tstatic $owner make($params);\n";
		$presence = $payload->declaration->kind !== record_kind::structure ? "\tstatic $owner require($owner value);\n" : '';
		return <<<CPP
#ifndef $guard
#define $guard
{$forward}struct $owner {
	struct allocation;
	::scpp::application_control* ptr{};
	$record* view{};
	$owner() noexcept = default;
$factory_declaration$presence	explicit operator bool() const noexcept { return ptr != nullptr; }
	$record* operator->() const;
	$owner(const $owner& other) noexcept;
	$owner($owner&& other) noexcept : ptr(other.ptr), view(other.view) { other.ptr = nullptr; other.view = nullptr; }
	$owner& operator=($owner other) noexcept {
		auto* old = ptr;
		auto* old_view = view;
		ptr = other.ptr;
		view = other.view;
		other.ptr = old;
		other.view = old_view;
		return *this;
	}
	~$owner();
};
struct $weak {
	::scpp::application_control* ptr{};
	$record* view{};
	$weak() noexcept = default;
	explicit $weak(const $owner& owner) noexcept : ptr(owner.ptr), view(owner.view) { retain(); }
	$weak(const $weak& other) noexcept : ptr(other.ptr), view(other.view) { retain(); }
	$weak($weak&& other) noexcept : ptr(other.ptr), view(other.view) { other.ptr = nullptr; other.view = nullptr; }
	$weak& operator=($weak other) noexcept {
		auto* old = ptr;
		auto* old_view = view;
		ptr = other.ptr;
		view = other.view;
		other.ptr = old;
		other.view = old_view;
		return *this;
	}
	void retain() noexcept;
	bool is_empty() const noexcept { return ptr == nullptr; }
	$owner lock() const noexcept;
	~$weak();
};
#endif

CPP;
	}

	/** Complete the typed allocation only after its inline payload layout is available. @param bool[] $includes */
	public function owner_methods(shared_record_type_definition $type, array &$includes /** array<string,bool> */): string
	{
		$payload = $type->payload->cpp_type();
		$owner = $type->cpp_type();
		$weak = $type->payload->weak->cpp_type();
		$guard = $this->owner_guard($type) . '_DEF';
		$constructor = $type->payload->constructor();
		$params = $this->constructor_parameters($constructor, $includes);
		$allocation_ctor = $constructor === null ? 'allocation() : ::scpp::application_control(destroy_value, dispose_allocation), value{} {}' : "allocation($params);";
		$factory = $constructor === null && !$type->payload->declaration->is_abstract ? $this->allocation_factory($type, $includes) : '';
		$presence = $type->payload->declaration->kind !== record_kind::structure
			? "inline $owner {$owner}::require($owner value) { if (!value.ptr) ::scpp::throw_empty_owner(); return value; }\n" : '';

		$allocation = $type->payload->declaration->is_abstract ? '' : <<<CPP
struct {$owner}::allocation : ::scpp::application_control {
	using payload_type = $payload;
	union { payload_type value; };
	$allocation_ctor
	~allocation() {} // The last strong owner ends the active union member's lifetime.
	static void destroy_value(::scpp::application_control* control) noexcept {
		static_cast<allocation*>(control)->value.~payload_type();
	}
	static void dispose_allocation(::scpp::application_control* control) noexcept {
		delete static_cast<allocation*>(control);
	}
};
$factory
CPP;

		return <<<CPP
#ifndef $guard
#define $guard
$allocation
inline $payload* {$owner}::operator->() const {
	if (!ptr) ::scpp::throw_empty_owner();
	return view;
}
$presence
inline {$owner}::$owner(const $owner& other) noexcept : ptr(other.ptr), view(other.view) {
	if (ptr) ptr->retain_strong();
}
inline {$owner}::~$owner() { if (ptr) ptr->release_strong(); }
inline void {$weak}::retain() noexcept { if (ptr) ptr->retain_weak(); }
inline $owner {$weak}::lock() const noexcept {
	$owner result;
	if (ptr && ptr->count) {
		ptr->retain_strong();
		result.ptr = ptr;
		result.view = view;
	}
	return result;
}
inline {$weak}::~$weak() { if (ptr) ptr->release_weak(); }
#endif

CPP;
	}

	/** Define user-directed allocation in the payload source, where parameter types are complete. @param bool[] $headers */
	private function allocation_factory(shared_record_type_definition $type, array &$headers /** array<string,bool> */): string
	{
		$owner = $type->cpp_type();
		$constructor = $type->payload->constructor();
		$params = $this->constructor_parameters($constructor, $headers);
		$args = [];
		foreach ($constructor?->parameters ?? [] as $parameter) {
			$name = $this->name($parameter);
			$args[] = $parameter->type_def()->cpp_trivial_value() ? $name : 'static_cast<' . $parameter->type_def()->cpp_type() . '&&>(' . $name . ')';
		}
		$forward = implode(', ', $args);
		$payload_args = $constructor !== null && self::self_value_constructor($constructor) ? '0, ' . $forward : $forward;
		$prefix = $constructor === null ? 'inline ' : "{$owner}::allocation::allocation($params) : ::scpp::application_control(destroy_value, dispose_allocation), value($payload_args) {}\n";
		return "{$prefix}$owner {$owner}::make($params) {\n\t$owner result;\n\tauto* storage = new allocation($forward);\n\tresult.ptr = storage;\n\tresult.view = &storage->value;\n\treturn result;\n}\n";
	}

	/** Public layouts and unit-local uses can independently request the same concrete owner. */
	private function owner_guard(shared_record_type_definition $type): string
	{
		return 'SCPP_OWNER_' . hash('sha256', $this->root . "\0" . $type->payload->name);
	}

	/** Only a completed semantic program is accepted; output is assembled privately before publication. */
	public function emit(prepared_entry $program): string
	{
		return $this->entry_body($program) . Cpp_Program::main_adapter($this->root);
	}

	public function entry_body(prepared_entry $program): string
	{
		return $this->render($program, 'scpp_entry_body');
	}

	/** Emit this file's settled function and global definitions, including declaration-only source files. */
	public function source_definitions(ast_file_node $file): string
	{
		$file->prepared_revision();
		if ($file->published_declarations() === []) {
			return Cpp_Program::empty_unit();
		}
		return $this->unit(null, $file, '');
	}

	/** Render the selected entry, sharing concrete field publication with project builds. */
	private function render(prepared_entry $program, string $function): string
	{
		$program->require_prepared();
		if ($this->headers === null) {
			$headers = new Cpp_Headers();
			$headers->publish_layout_dependencies($program->file, $this, $this->root);
			if ($headers->files() !== []) {
				$emitter = new self($headers, $this->root, $this->generator_layouts);
				$text = $emitter->unit($program, $program->file, $function);
				return self::inline_headers($text, $headers->files() + $emitter->adapter_files());
			}
		}
		return $this->unit($program, $program->file, $function);
	}

	/** Assemble local definitions and deterministic forward declarations without a shared naming registry. */
	private function unit(?prepared_entry $entry, ast_file_node $file, string $entry_name): string
	{
		$this->enum_helpers = [];
		$this->inline_adapters = new Cpp_Adapters($this->root);
		$this->concrete_collections = new Cpp_Concrete_Collections($this->root);
		$this->async_results = [];
		$declarations = [];
		$definitions = [];
		$global_references = [];
		$required_headers = [];
		foreach ($file->globals() as $global) {
			$type = $global->type_def();
			$initializer = $global->initializer === null ? '{}' : $this->declaration_initialization($type, $global->initializer, $global->initialization(), $required_headers);
			$qualifier = $global->initializer === null || $global->initializer instanceof ast_string_expression ? '' : 'constinit ';
			$definitions[] = $qualifier . $type->cpp_type() . ' ' . self::global_name($global)
				. ' = ' . $initializer . ";\n";
			foreach ($type->cpp_headers() as $header) {
				$required_headers[$header] = true;
			}
		}
		foreach ($file->declaration_nodes as $node) {
			if (!($node instanceof ast_type_declaration)) continue;
			$declarations[$node->symbol_key()] = $node;
			if (!($node instanceof ast_record_declaration)) continue;
			foreach (self::variance_bridges($node) as [$contract, $implementation]) {
				$this->collect_signature_types($contract, $declarations);
				$this->collect_signature_types($implementation, $declarations);
				$definitions[] = $this->variance_forward($node, $contract, $implementation, $required_headers);
			}
			foreach ($node->definition->interface_forwards as $method) {
				$declarations[self::function_name($method)] = $method;
				$this->collect_signature_types($method, $declarations);
				$definitions[] = $this->interface_forward($node, $method, $required_headers);
			}
			$constructor = $node->definition->constructor();
			if ($constructor !== null && !$node->is_abstract) {
				// Each payload's source owns its factory, including inherited constructor contracts.
				$owner = $node->definition->shared;
				$declarations[$owner->name] = $owner;
				$this->collect_signature_types($constructor, $declarations);
				$definitions[] = $this->allocation_factory($owner, $required_headers);
			}
		}
		$unit_flavor = null;
		foreach ($file->implementations() as $function) {
			if ($function->is_abstract) continue;
			$name = self::function_name($function);
			if ($function->is_constructor()) {
				$owner = $function->parent->definition->shared;
				// Embedded fields use native {}. Keep the prepared defaults in their defining unit.
				if ($function->parameters !== [] && $function->accepts_argument_count(0)) {
					$defaults = [];
					foreach ($function->parameters as $parameter) {
						$defaults[] = $parameter->type_reference instanceof ast_variadic_type_reference
							? $parameter->type_def()->lower_cpp_empty()
							: $this->declaration_initialization($parameter->type_def(), $parameter->default_value,
								$parameter->default_conversion(), $required_headers);
					}
					$name = Cpp_Identifier::source($function->parent->short_name);
					$definitions[] = $owner->payload->cpp_type() . '::' . $name . '() : ' . $name . '(' . implode(', ', $defaults) . ") {}\n";
				}
			} elseif (!$function->is_destructor()) {
				$declarations[$name] = $function;
			}
			$this->adapter_flavor = null;
			if ($function->return_type_def() instanceof generator_type_definition) {
				$definitions[] = $this->generator_definition($function->return_type_def(), $declarations, $global_references, $required_headers);
				$unit_flavor ??= $this->adapter_flavor;
				continue;
			}
			if ($function->is_async) $this->require_task($function->return_type_def(), $required_headers);
			$this->async_body = $function->is_async;
			$lines = $this->statements($function->body(), $declarations, $global_references, $required_headers);
			$this->async_body = false;
			if ($function->is_constructor() || $function->is_destructor() || $function->has_virtual_dispatch()) array_unshift($lines, "\t[[maybe_unused]] auto& scpp_self = *this;");
			if ($function->is_async) {
				$body = $function->body()->statements;
				$last = $body[array_key_last($body)] ?? null;
				// Even an empty, throw-only or nonterminating body must create a coroutine frame.
				if (!($last instanceof ast_return_statement)) {
					$lines[] = $function->return_type_def() === type_void::instance() ? "\tco_return;" : "\tco_return []() -> "
						. $this->signature_type($function->return_type_def(), false) . ' { __builtin_unreachable(); }();';
				}
			}
			$base_initializer = '';
			$first = $function->body()->statements[0] ?? null;
			if ($first instanceof ast_base_statement) {
				$this->qualified_adapters = true;
				try {
					$base_initializer = $this->base_initializer($first->value, $global_references, $declarations, $required_headers);
				} finally {
					$this->qualified_adapters = false;
				}
			}
			$unit_flavor ??= $this->adapter_flavor;
			$signature = $this->signature($function, $required_headers, $this->adapter_flavor !== null && $this->root !== '');
			if ($function->is_constructor() && $this->emits_reordered_base($function)) {
				$name = Cpp_Identifier::source($function->parent->short_name);
				$names = array_map($this->name(...), $function->parameters);
				$definitions[] = $signature . ' : ' . $name . '(scpp_escape_parameter_refs{}, ' . implode(', ', $names) . ") {}\n";
				$refs = $this->parameters($function, $required_headers, false, borrowed_inputs: true);
				$signature = $function->parent->definition->cpp_type() . '::' . $name . '(scpp_escape_parameter_refs, ' . implode(', ', $refs) . ')';
			}
			$definitions[] = $signature . $base_initializer . "\n{\n" . $this->local_imports() . implode("\n", $lines) . "\n}\n";
			if ($function->is_async && ($this->headers === null || $this->headers->uses_helper($function, dependency_use::async_wait))) {
				$arguments = array_map($this->forward_argument(...), $function->parameters);
				$definitions[] = $this->wait_signature($function, $required_headers) . "\n{\n\treturn sync_wait("
					. $name . '(' . implode(', ', $arguments) . "));\n}\n";
			}
		}
		if ($entry !== null) {
			$this->adapter_flavor = null;
			$lines = $this->statements($entry->body(), $declarations, $global_references, $required_headers);
			$unit_flavor ??= $this->adapter_flavor;
			$definitions[] = 'int ' . $entry_name . "()\n{\n" . $this->local_imports() . implode("\n", $lines) . "\n}\n";
		}
		foreach ($this->inline_adapters->dependencies() as $type) {
			if ($type instanceof application_collection_type) $this->concrete_collections->request($type);
		}
		foreach ([...$this->inline_adapters->dependencies(), ...$this->concrete_collections->dependencies(), ...$this->async_results] as $type) {
			if ($type instanceof record_handle_definition || $type instanceof intersection_type_definition) $declarations[$type->name] = $type;
			elseif ($type instanceof record_type_definition || $type instanceof enum_type_definition) $declarations[$type->declaration->symbol_key()] = $type->declaration;
			foreach ($type->cpp_headers() as $include) $required_headers[$include] = true;
		}
		$prototypes = '';
		$unit_headers = [];
		foreach ($declarations as $node) {
			if (!($node instanceof ast_function_declaration) || !($node->return_type_def() instanceof generator_type_definition)) continue;
			$this->visit_application_types($node, function (type_definition $type) use (&$unit_headers): void {
				if ($type instanceof application_collection_type) $unit_headers[$this->headers->collection_header($type, $this->root)] = true;
			});
		}
		// Calls and definitions need complete value and handle types even when only the signature names them.
		foreach ($declarations as $function) {
			if ($function instanceof ast_function_declaration) {
				$this->collect_signature_types($function, $declarations);
			}
		}
		foreach ($global_references as $property) {
			if ($property->parent instanceof ast_record_declaration) $declarations[$property->parent->symbol_key()] = $property->parent;
		}
		foreach ($file->declaration_nodes as $node) {
			if (!($node instanceof ast_enum_declaration)) continue;
			foreach (dependency_use::enum_helper_uses as $use) {
				if (!$this->needs_enum_helper($node, $use)) continue;
				if ($use !== dependency_use::enum_try_from_value) $required_headers['stdexcept'] = true;
				if ($use === dependency_use::enum_from_value && $this->needs_enum_helper($node, dependency_use::enum_try_from_value)) {
					$required_headers['utility'] = true;
				}
				$required_headers[$node->definition->helper_function($use)->header] = true;
				$definitions[] = $this->enum_helper($node->definition, $use);
			}
		}
		ksort($declarations, \SORT_STRING);
		$records = [];
		$layouts = '';
		$intersections = '';
		$owners = [];
		foreach ($declarations as $node) {
			if (!($node instanceof ast_enum_declaration)) continue;
			$this->type_prototypes($node, $records, $unit_headers, $required_headers, $layouts, $owners);
		}
		foreach ($declarations as $function) {
			if ($function instanceof ast_enum_declaration) continue;
			if ($function instanceof intersection_type_definition) {
				$owners[$function->base->payload->name] = $function->base;
				$owners[$function->capability->payload->name] = $function->capability;
				$intersections .= 'struct ' . $function->cpp_type() . ' { ' . $function->base->cpp_type() . ' base; '
					. $function->capability->payload->cpp_type() . "* capability{}; };\n";
				continue;
			}
			if ($function instanceof record_handle_definition) {
				$owners[$function->payload->name] = $function->payload->shared;
				continue;
			}
			if ($function instanceof ast_record_declaration) {
				$this->type_prototypes($function, $records, $unit_headers, $required_headers, $layouts, $owners);
				continue;
			}
			$include = $this->headers?->declaration_header($function);
			if ($include !== null) {
				$unit_headers[$include] = true;
			}
			else {
				$prototype = $this->function_declaration($function, $required_headers);
				if ($prototype !== '') $prototypes .= $prototype . "\n";
			}
		}
		$owner_types = '';
		$owner_methods = '';
		$done = [];
		while ($owners !== []) {
			$pending = $owners;
			$owners = [];
			foreach ($pending as $key => $owner) {
				if (isset($done[$key])) continue;
				$done[$key] = true;
				$this->type_prototypes($owner->payload->declaration, $records, $unit_headers, $required_headers, $layouts, $owners);
				$owner_types .= $this->owner_declaration($owner, $required_headers);
				$owner_methods .= $this->owner_methods($owner, $required_headers);
				$required_headers['scpp/owner_access.hpp'] = true;
			}
		}
		ksort($global_references, \SORT_STRING);
		foreach ($global_references as $name => $global) {
			if ($global->parent instanceof ast_record_declaration) continue;
			$include = $this->headers?->declaration_header($global);
			if ($include !== null) {
				$unit_headers[$include] = true;
			}
			else {
				$prototypes .= $this->global_declaration($global, $required_headers) . "\n";
			}
		}
		foreach ($file->globals() as $global) {
			$include = $this->headers?->declaration_header($global);
			if ($include !== null) {
				$unit_headers[$include] = true;
			}
		}
		$header = "// Generated from completed source-owned bodies.\n";
		if ($entry !== null) {
			$header .= '#include <' . Cpp_Entry_Binding::header() . ">\n";
			$header .= 'SCPP_REQUIRE_ENTRY_TARGET(' . $entry->target->digits . ");\n\n";
		}
		ksort($required_headers, \SORT_STRING);
		foreach ($required_headers as $include => $unused) {
			$header .= '#include <' . $include . ">\n";
		}
		ksort($unit_headers, \SORT_STRING);
		foreach ($unit_headers as $include => $_) {
			$header .= '#include "' . $include . "\"\n";
		}
		$imports = $unit_flavor !== null && $this->root !== '' ? self::imports($unit_flavor) : '';
		$adapter_files = $this->adapter_files();
		if ($adapter_files === []) {
			return $header . self::namespace_body($this->root, $imports . $owner_types . $layouts . $owner_methods . $intersections . $prototypes . implode("\n", $definitions));
		}
		$text = $header . self::namespace_body($this->root, $this->concrete_collections->forwards() . $imports . $owner_types . $layouts . $owner_methods . $intersections . $prototypes);
		foreach ($adapter_files as $path => $body) {
			$text .= $this->headers === null ? $body : '#include "' . basename($path) . "\"\n";
		}
		return $text . self::namespace_body($this->root, implode("\n", $definitions));
	}

	/**
	 * Emit private definitions once, with complete embedded types before their parents.
	 * @param bool[] $seen
	 * @param bool[] $unit_headers
	 * @param shared_record_type_definition[] $owners
	 * @param bool[] $runtime_headers
	 */
	private function type_prototypes(ast_type_declaration $record, array &$seen /** array<string,bool> */,
		array &$unit_headers /** array<string,bool> */, array &$runtime_headers /** array<string,bool> */, string &$text,
		array &$owners /** array<string,shared_record_type_definition> */): void
	{
		$key = $record->symbol_key();
		if (isset($seen[$key])) return;
		$seen[$key] = true;
		$header = $this->headers?->declaration_header($record);
		if ($header !== null) {
			$unit_headers[$header] = true;
			return;
		}
		if ($record instanceof ast_enum_declaration) {
			$text .= $this->enum_declaration($record, $runtime_headers) . "\n";
			return;
		}
		if ($record->definition->base() !== null) {
			$this->type_prototypes($record->definition->base()->declaration, $seen, $unit_headers, $runtime_headers, $text, $owners);
		}
		foreach ($record->definition->interfaces() as $interface) {
			$this->type_prototypes($interface->declaration, $seen, $unit_headers, $runtime_headers, $text, $owners);
		}
		foreach ($record->fields as $field) {
			$type = $field->storage_type();
			if ($type instanceof record_type_definition || $type instanceof enum_type_definition) {
				$this->type_prototypes($type->declaration, $seen, $unit_headers, $runtime_headers, $text, $owners);
			} elseif ($type instanceof record_handle_definition) {
				$owners[$type->payload->name] = $type->payload->shared;
			}
		}
		$text .= $this->record_declaration($record, $runtime_headers) . "\n";
	}

	/**
	 * Render the admitted test through its generated type handler; no backend type admission.
	 * @param ast_global_declaration[] $global_references
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $headers
	 */
	private function condition(ast_expression $condition, array &$global_references /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$headers /** array<string,bool> */): string
	{
		$type = $condition->type_def();
		$direct = false;
		$logical_parent = $condition->parent;
		$negated = $logical_parent instanceof ast_not_expression;
		$operand = $this->value($condition, $global_references, $declarations, $headers, $direct,
			logical_root: true, bare_comparison: !$negated);
		if ($condition instanceof ast_binary_expression && $condition->operator->lazy()
			&& ($negated || ($logical_parent instanceof ast_binary_expression && $logical_parent->operator->lazy()
				&& $logical_parent->operator !== $condition->operator))) {
			// Mixed && and || need explicit grouping for precedence or Clang's -Wlogical-op-parentheses.
			$operand = '(' . $operand . ')';
		}
		elseif (!$negated && $direct && ($condition instanceof ast_binary_expression) && ($type === type_bool::instance())) {
			// Direct boolean comparisons already have one outer pair. A negated comparison needs it.
			$operand = substr($operand, 1, -1);
		}
		foreach ($type->condition_headers() as $header) {
			$headers[$header] = true;
		}
		return $type->lower_cpp_condition($operand, $negated);
	}

	/**
	 * Keep an operand's prerequisite statements together until its evaluation point.
	 * @param ast_global_declaration[] $globals
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $headers
	 * @return array{string,list<string>}
	 */
	private function staged_operand(ast_expression $operand, array &$globals, array &$declarations, array &$headers,
		bool $condition = false, ?Cpp_Staged_Result $destination = null): array
	{
		$start = count($this->async_steps);
		$text = $condition ? $this->condition($operand, $globals, $declarations, $headers)
			: $this->value($operand, $globals, $declarations, $headers, inside_scope: $destination !== null, destination: $destination);
		return [$text, count($this->async_steps) === $start ? [] : array_splice($this->async_steps, $start)];
	}

	/** Keep staged values in the full expression's scope on success and exception. */
	private function async_statement(string $statement): string
	{
		if ($this->async_steps === []) return "\t" . $statement;
		$steps = implode("\n\t\t", $this->async_steps);
		$this->async_steps = [];
		return "\t{\n\t\t" . $steps . "\n\t\t" . $statement . "\n\t}";
	}

	/**
	 * Lower already resolved statements and retain only contract-derived external declarations.
	 *
	 * @param ast_function_declaration[] $declarations
	 * @param ast_global_declaration[] $global_references
	 * @param bool[] $required_headers
	 * @return string[]
	 * Producer context is private lowering state; ordinary functions retain native automatic cleanup.
	 */
	private function statements(ast_statement_block $body, array &$declarations /** array<string,ast_function_declaration> */,
		array &$global_references /** array<string,ast_global_declaration> */,
		array &$required_headers /** array<string,bool> */, ?statement_return_target $function = null,
		?cpp_resume_context $resume = null, ?string $first_initializer = null): array /** list<string> */
	{
		if ($body instanceof ast_function_body) {
			$body->require_prepared();
			$this->byte_view = null;
			/** @var ast_function_declaration $function */
			$function = $body->parent;
			$this->destination = $this->destination_plans[$function->name()] ?? null;
		}
		$lines = [];
		if ($resume !== null) $resume->scopes[] = $body;
		foreach ($body->statements as $statement)
		{
			if (($writes = $this->destination?->writes($statement)) !== null) {
				if ($writes !== []) {
					$inputs = [];
					foreach ($writes as $write) $inputs[] = $this->value($write->value, $global_references, $declarations, $required_headers);
					$receiver = $this->value($statement->receiver, $global_references, $declarations, $required_headers);
					$lines[] = "\t" . $this->concrete_collections->destination_append($statement->receiver->type_def(), $writes, $receiver, $inputs) . ';';
				}
				continue;
			}
			if ($statement instanceof ast_base_statement) continue;
			if ($statement instanceof ast_local_alias) {
				if ($statement->initializer instanceof ast_call_expression) {
					$call = $this->value($statement->initializer, $global_references, $declarations, $required_headers, borrow_result: true);
					$lines[] = $this->async_statement($call . ';');
				}
				continue;
			}
			if ($statement instanceof ast_block_statement) {
				$lines[] = "\t{";
				foreach ($this->statements($statement->body, $declarations, $global_references, $required_headers, $function, $resume) as $line) {
					$lines[] = "\t" . $line;
				}
				$lines[] = "\t}";
				continue;
			}
			$leaf = $resume !== null && !($statement instanceof ast_if_statement || $statement instanceof ast_loop_statement
				|| $statement instanceof ast_yield_statement);
			if ($leaf) $lines[] = '{';
			if ($statement instanceof ast_yield_statement) {
				$lines[] = $this->generator_yield($statement, $function->return_type_def(), $resume, $declarations, $global_references, $required_headers);
			}
			elseif ($statement instanceof ast_loop_transfer) {
				$continuing = $statement instanceof ast_continue_statement;
				$transfer = $continuing ? 'continue;' : 'break;';
				if (($statement->numbered() && $statement->depth() > 1)
					|| (!$continuing && $statement->target instanceof ast_while_else_statement)) {
					if ($resume !== null) {
						$loop = $resume->loops[array_key_last($resume->loops)];
						$lines[] = $this->generator_cleanup($resume, $loop['depth']);
					}
					$transfer = $this->labelled_transfer($statement);
				}
				elseif ($this->finally_exit !== null && $this->finally_exit->transfer_target($continuing ? statement_completion::continues : statement_completion::breaks) === $statement->target) {
					$transfer = $this->finally_exit->save($continuing ? statement_completion::continues : statement_completion::breaks);
				}
				elseif ($resume !== null) {
					$loop = $resume->loops[array_key_last($resume->loops)];
					$lines[] = $this->generator_cleanup($resume, $loop['depth']);
					if ($continuing && $loop['update'] !== null) $transfer = 'goto ' . $loop['update'] . ';';
				}
				$lines[] = "\t" . $transfer;
			}
			elseif ($statement instanceof ast_foreach_statement && $statement->value instanceof ast_writable_element) {
				$source = $statement->source_value;
				$owner = $this->name($source->local_def());
				$index = 'iteration_index_' . $statement->start;
				$limit = 'iteration_limit_' . $statement->start;
				$count = $source->type_def()->lower_cpp_cardinality(collection_cardinality::count, $owner);
				$lines[] = "for (scpp::int64 $index = 0, $limit = $count; $index < $limit; ++$index) {";
				if ($statement->key !== null) $lines[] = '[[maybe_unused]] auto ' . $this->name($statement->key) . ' = ' . $index . ';';
				foreach ($this->loop_body_lines($statement, $declarations, $global_references, $required_headers, $function, null) as $line) $lines[] = "\t" . $line;
				$lines[] = '}';
			}
			elseif ($statement instanceof ast_foreach_statement && ($source_type = $statement->source_value->type_def()) instanceof segment_type
				&& $source_type->key !== null) {
				$receiver = $this->value($statement->source_value, $global_references, $declarations, $required_headers);
				$lines[] = "{ const auto hash_view = $receiver; int64_t hash_cursor = 0; while (hash_view.next(hash_cursor)) {";
				$key = $statement->keys_only ? $statement->value : $statement->key;
				if ($key !== null) $lines[] = '[[maybe_unused]] const auto& ' . $this->name($key) . ' = *static_cast<const '
					. $source_type->key->cpp_type() . '*>(hash_view.key(hash_cursor));';
				if (!$statement->keys_only) {
					$method = $source_type->read_only ? 'value' : 'writable_value';
					$qualifier = $source_type->read_only ? 'const ' : '';
					$lines[] = '[[maybe_unused]] auto& ' . $this->name($statement->value) . ' = *static_cast<' . $qualifier
						. $source_type->element->cpp_type() . "*>(hash_view.$method(hash_cursor));";
				}
				array_push($lines, ...$this->loop_body_lines($statement, $declarations, $global_references, $required_headers, $function, $resume));
				$lines[] = '} }';
			}
			elseif ($statement instanceof ast_foreach_statement) {
				$source_type = $statement->source_value->type_def();
				$type = $source_type->iteration();
				if ($source_type instanceof generator_type_definition) $declarations[self::function_name($source_type->function)] = $source_type->function;
				foreach ([$source_type, $statement->value->type_def(), $statement->destination->type_def(), $statement->key?->type_def()] as $used) {
					if ($used !== null) foreach ($used->cpp_headers() as $header) $required_headers[$header] = true;
				}
				$prefix = '';
				$iteration_source = 'iteration_source';
				$lines[] = "\t{";
				if ($resume !== null) {
					$slot = 'iteration_' . $statement->start;
					$prefix = $slot . '.value.';
					$capture = $this->generator_iteration_capture($statement->source_value);
					$initial = $capture === null ? '' : $this->value($capture, $global_references, $declarations, $required_headers);
					$lines[] = "{ new (&$slot.value) {$slot}_type{ $initial }; live_$slot = true; }";
					$iteration_source = $this->generator_iteration_receiver($statement, $prefix, $global_references, $declarations, $required_headers);
					$resume->scopes[] = $statement;
				} else {
					$steps = [];
					$receiver = $this->retained_place($statement->source_value, $global_references, $declarations, $required_headers, $steps);
					foreach ($this->async_steps as $step) $lines[] = "\t" . $step;
					$this->async_steps = [];
					foreach ($steps as $step) $lines[] = "\t" . $step;
					$lines[] = "\tauto&& iteration_source = " . $receiver . ';';
				}
				$destination = $statement->destination;
				$method = $type->iteration_method();
				$direct = $this->iteration_direct($statement);
				$output_type = $statement->keys_only ? null : $type->iteration_output_type();
				if (!$direct) foreach ($destination->type_def()->conversion_headers($statement->conversion) as $header) $required_headers[$header] = true;
				$output = $output_type !== null ? ($resume === null ? 'iteration_output' : $prefix . 'output')
					: ($direct ? $this->name($destination) : ($resume === null ? 'iteration_value' : $prefix . 'output'));
				if ($resume === null && $output_type !== null) {
					$lines[] = "\t" . $output_type . ' ' . $output . ';';
				} elseif ($resume === null && (!$direct || $destination === $statement->value)) {
					$lines[] = "\t" . $statement->value->type_def()->cpp_type() . ' ' . $output . '{};';
				}
				$key = $statement->key === null ? null : $this->name($statement->key);
				if ($resume === null && $key !== null) $lines[] = "\t" . $statement->key->type_def()->cpp_type() . ' ' . $key . '{};';
				$lines[] = ($resume === null ? $type->lower_cpp_iteration_storage($key !== null) : '')
					. $type->lower_cpp_iteration_initialize($iteration_source, $prefix);
				if ($method !== null) {
					$name = self::function_name($method);
					$declarations[$name] = $method;
					if ($key === null && $type->iteration_key_type() !== null) {
						$key = $resume === null ? 'iteration_key' : $prefix . 'key';
						$key_type = $type->iteration_key_type();
						foreach ($key_type->cpp_headers() as $header) $required_headers[$header] = true;
						if ($resume === null) $lines[] = "\t" . $key_type->cpp_type() . ' ' . $key . '{};';
					}
					$next = $name . '(' . $iteration_source . ', ' . $output . ($key === null ? '' : ', ' . $key) . ')';
				} elseif ($statement->keys_only) {
					$next = $type->lower_cpp_iteration_next_key($iteration_source, $output, $prefix);
				} else $next = $type->lower_cpp_iteration_next($iteration_source, $output, $key, $prefix);
				$lines[] = "\twhile (" . $next . ') {';
				if ($output_type !== null) {
					if ($destination === $statement->value) {
						if ($resume === null) $lines[] = "\t[[maybe_unused]] auto& " . $this->name($destination) . ' = ' . $output . '.value;';
					} else {
						$operand = 'static_cast<' . $statement->value->type_def()->cpp_type() . '&&>(' . $output . '.value)';
						$lines[] = "\t" . $this->name($destination) . ' = ' . $this->conversion($destination->type_def(), $statement->conversion, $operand, $statement->value) . ';';
					}
				} elseif (!$direct) {
					$operand = $method !== null && $statement->conversion === value_conversion::identity
						? 'static_cast<' . $statement->value->type_def()->cpp_type() . '&&>(' . $output . ')' : $output;
					$converted = $this->conversion($destination->type_def(), $statement->conversion, $operand, $statement->value);
					$lines[] = "\t" . $this->name($destination) . ' = ' . $converted . ';';
				}
				if ($resume !== null) $resume->loops[] = ['depth' => count($resume->scopes), 'update' => null];
				$first_initializer = $resume === null && $statement->keys_only && $type instanceof key_iterable_type
					&& $this->matches_first_hash_read($statement) ? $type->lower_cpp_iteration_current_value($iteration_source) : null;
				foreach ($this->loop_body_lines($statement, $declarations, $global_references, $required_headers, $function, $resume, $first_initializer) as $line) $lines[] = "\t" . $line;
				$lines[] = "\t}";
				if ($resume !== null) {
					array_pop($resume->loops);
					$lines[] = $this->generator_cleanup($resume, count($resume->scopes) - 1);
					array_pop($resume->scopes);
				}
				$lines[] = "\t}";
			}
			elseif ($statement instanceof ast_loop_statement) {
				$post_test = $statement instanceof ast_do_while_statement;
				$update_label = null;
				$header_scope = false;
				$resume_updates = [];
				if ($statement instanceof ast_for_statement) {
					$tests = [];
					$staged_tests = [];
					$last = array_key_last($statement->conditions);
					foreach ($statement->conditions as $i => $test) {
						$tests[$i] = $i === $last ? $this->condition($test, $global_references, $declarations, $required_headers)
							: '(void)(' . $this->value($test, $global_references, $declarations, $required_headers) . ')';
						if ($this->async_steps !== []) {
							$staged_tests[$i] = $this->async_statement($i === $last ? "if (!($tests[$i])) break;" : "$tests[$i];");
						}
					}
					$condition = $staged_tests === [] ? implode(', ', $tests) : '';
					$init = [];
					$separate_init = $resume !== null;
					foreach ($statement->initializers as $item) {
						$text = $this->for_action($item, $global_references, $declarations, $required_headers, $snapshot);
						// A coroutine header declaration can include a scoped assignment from binding().
						if ($snapshot !== null || ($item instanceof ast_local_declaration && ($function?->is_async || count($statement->initializers) > 1))) $separate_init = true;
						if ($this->async_steps !== []) {
							$separate_init = true;
							$text = $this->async_statement($text . ';');
						}
						$init[] = $snapshot === null ? $text : "{ const auto $snapshot; $text; }";
					}
					$initializer = $separate_init ? '' : implode(', ', $init);
					$prelude = $separate_init ? array_map(static fn(string $text): string => $text . ';', $init) : [];
					$updates = [];
					$staged_updates = [];
					foreach ($statement->updates as $index => $item) {
						$name = $index === 0 ? 'compound_previous' : 'compound_previous_' . $index;
						$text = $this->for_action($item, $global_references, $declarations, $required_headers, $snapshot, $name);
						if ($resume !== null) $resume_updates[] = $snapshot === null ? "$text;" : "{ const auto $snapshot; $text; }";
						elseif ($this->async_steps !== []) {
							$staged_updates[$index] = $this->async_statement("$text;");
							$updates[] = $text;
						} elseif ($snapshot === null) $updates[] = $text;
						else {
							$prelude[] = $item->local_def()->type_def()->cpp_type() . " $name;";
							$updates[] = "($snapshot, $text)";
						}
					}
					if ($staged_updates !== []) $prelude[] = 'bool scpp_first = true;';
					$header_scope = $prelude !== [];
					if ($header_scope) {
						$lines[] = "\t{";
						foreach ($prelude as $text) $lines[] = "\t" . $text;
					}
					if ($resume !== null) {
						if ($resume_updates !== []) $update_label = 'loop_update_' . ++$resume->loop_id;
						$lines[] = "\twhile (" . ($condition === '' ? 'true' : $condition) . ') {';
					} elseif ($staged_updates !== []) {
						$lines[] = "\tfor (" . $initializer . ';; scpp_first = false) {';
						$lines[] = "\t\tif (!scpp_first) {";
						foreach ($updates as $i => $update) $lines[] = "\t\t" . ($staged_updates[$i] ?? "$update;");
						$lines[] = "\t\t}";
						if ($condition !== '') $lines[] = "\t\tif (!(" . $condition . ")) break;";
					} else $lines[] = "\tfor (" . $initializer . ';' . ($condition === '' ? '' : ' ' . $condition) . ';'
						. ($updates === [] ? '' : ' ' . implode(', ', $updates)) . ') {';
					if ($staged_tests !== []) {
						foreach ($tests as $i => $test) {
							$lines[] = "\t" . ($staged_tests[$i] ?? ($i === $last ? "if (!($test)) break;" : "$test;"));
						}
					}
				} else {
					$condition = $this->condition($statement->condition, $global_references, $declarations, $required_headers);
					if ($this->async_steps !== []) {
						// Native continue runs the update before re-entering the condition. Break bypasses both.
						$lines[] = $post_test ? "\tfor (bool scpp_first = true; ; scpp_first = false) {" : "\twhile (true) {";
						if ($post_test) $lines[] = "\t\tif (!scpp_first)";
						$post_test = false;
						$lines[] = "\t" . $this->async_statement("if (!($condition)) break;");
					} else $lines[] = "\t" . ($post_test ? 'do' : 'while (' . $condition . ')') . ' {';
				}
				if ($resume !== null) $resume->loops[] = ['depth' => count($resume->scopes), 'update' => $update_label];
				foreach ($this->loop_body_lines($statement, $declarations, $global_references, $required_headers, $function, $resume) as $line) {
					$lines[] = "\t" . $line;
				}
				if ($resume !== null) {
					array_pop($resume->loops);
					if ($update_label !== null) {
						$lines[] = "goto $update_label;\n$update_label:;";
						foreach ($resume_updates as $text) $lines[] = $text;
					}
				}
				$lines[] = "\t}" . ($post_test ? ' while (' . $condition . ');' : '');
				if ($resume !== null && $statement instanceof ast_for_statement) {
					foreach (array_reverse($statement->initializers) as $item) {
						if ($item instanceof ast_local_declaration) $lines[] = $this->generator_destroy($item);
					}
				}
				if ($header_scope) $lines[] = "\t}";
				if ($statement instanceof ast_while_else_statement) {
					$lines[] = "\t{";
					foreach ($this->statements($statement->otherwise, $declarations, $global_references, $required_headers, $function, $resume) as $line) {
						$lines[] = "\t" . $line;
					}
					$lines[] = "\t}";
				}
			}
			elseif ($statement instanceof ast_rethrow_statement) {
				// A nested finally may execute under a different native unwind. Use the lexical transport there.
				$rethrow = 'throw;';
				for ($node = $statement; !($node instanceof ast_catch_clause); $node = $node->parent) {
					if ($node->parent instanceof ast_try_statement && $node === $node->parent->finalizer) {
						$rethrow = 'throw caught_exception;';
						break;
					}
				}
				if ($node instanceof ast_catch_clause && $node->parent->suspending_handlers) $rethrow = 'throw caught_exception;';
				$lines[] = "\t" . $rethrow;
			}
			elseif ($statement instanceof ast_throw_statement) {
				$raise = $this->throw_call($statement->operation, $global_references, $declarations, $required_headers);
				$lines[] = $this->async_statement($raise . ';');
			}
			elseif ($statement instanceof ast_try_statement) {
				array_push($lines, ...$this->exception_region($statement, $function, $declarations, $global_references, $required_headers));
			}
			elseif ($statement instanceof ast_switch_statement) {
				array_push($lines, ...$this->switch_lines($statement, $function, $declarations, $global_references, $required_headers));
			}
			elseif ($statement instanceof ast_if_statement) {
				$nested_else_blocks = 0;
				foreach ($statement->arms as $index => $arm) {
					$head = $index === 0 ? 'if' : 'else';
					if ($arm->condition !== null) {
						$guard = $arm->condition instanceof ast_not_expression ? $arm->condition->operand : $arm->condition;
						if ($guard instanceof ast_object_access_expression && $guard->used_as_guard) {
							$operand = $this->value($guard->operand, $global_references, $declarations, $required_headers);
							$request = Cpp_Object_Access::request($guard->operand->type_def(), $guard->target_type(), true, $guard->dynamic_check(), true);
							$slot = 'scpp_guard_' . $guard->start;
							$init = 'auto* ' . $slot . ' = ' . $this->inline_adapters->call($request, [$operand]);
							$test = $slot . ($guard === $arm->condition ? ' != nullptr' : ' == nullptr');
							if ($index === 0) {
								$lines[] = "\t" . $init . ';';
								$head = 'if (' . $test . ')';
							} else $head = 'else if (' . $init . '; ' . $test . ')';
						} else {
							$test = $this->condition($arm->condition, $global_references, $declarations, $required_headers);
							if ($this->async_steps !== []) {
								if ($index !== 0) {
									$lines[] = "\telse {";
									++$nested_else_blocks;
								}
								$head = 'if';
								$guard_name = 'condition_' . $arm->start;
								$lines[] = "\tbool $guard_name;";
								$lines[] = $this->async_statement("$guard_name = $test;");
								$test = $guard_name;
							} elseif ($index !== 0) $head = 'else if';
							$head .= ' (' . $test . ')';
						}
					}
					$lines[] = "\t" . $head . ' {';
					foreach ($this->statements($arm->body, $declarations, $global_references, $required_headers, $function, $resume) as $line) {
						$lines[] = "\t" . $line;
					}
					$lines[] = "\t}";
				}
				while ($nested_else_blocks-- > 0) $lines[] = "\t}";
			}
			elseif ($statement instanceof ast_field_assignment) {
				$before = $this->suspensions;
				[$value, $rhs_steps] = $this->staged_operand($statement->value, $global_references, $declarations, $required_headers);
				if ($statement->target instanceof ast_qualified_data) {
					$qualifier = $this->discard_qualifier($statement->target->receiver, $global_references, $declarations, $required_headers);
					$lines[] = $this->async_statement($qualifier . ';');
					$field = $this->name_value($statement->target, $global_references, $declarations, $required_headers);
				} elseif ($statement->target instanceof ast_field_expression && $before !== $this->suspensions) {
					$steps = [];
					$owner = $this->retained_place($statement->target->receiver, $global_references, $declarations, $required_headers, $steps);
					array_push($this->async_steps, ...$steps);
					$this->async_steps[] = 'auto&& field_owner = ' . $owner . ';';
					$this->async_steps[] = 'auto& write_target = field_owner.' . Cpp_Identifier::source($statement->target->field) . ';';
					$field = 'write_target';
				} else $field = $this->value($statement->target, $global_references, $declarations, $required_headers);
				array_push($this->async_steps, ...$rhs_steps);
				$type = $statement->target->type_def();
				foreach ($type->conversion_headers($statement->conversion) as $header) $required_headers[$header] = true;
				if ($statement->transfer) {
					$required_headers['utility'] = true;
					$rhs = 'std::move(' . $value . ')';
				} else {
					$rhs = $this->conversion($type, $statement->conversion, $value, $statement->value);
				}
				$lines[] = $this->async_statement($field . ' = ' . $rhs . ';');
			}
			elseif ($statement instanceof ast_index_assignment) {
				/** @var index_write_type $type */
				$type = $statement->receiver->type_def();
				$steps = [];
				$before = $this->suspensions;
				$receiver = $this->write_receiver($statement->receiver, $global_references, $declarations, $required_headers, $steps);
				[$key, $key_steps] = $this->staged_operand($statement->index, $global_references, $declarations, $required_headers);
				[$value, $rhs_steps] = $this->staged_operand($statement->value, $global_references, $declarations, $required_headers);
				$suspends = $before !== $this->suspensions;
				if ($suspends) {
					$steps[] = 'auto&& write_target = ' . $receiver . ';';
					$receiver = 'write_target';
					array_push($steps, ...$key_steps);
				}
				$conversion = $statement->conversion();
				$destination = $type->write_type();
				foreach ($destination->conversion_headers($conversion) as $header) {
					$required_headers[$header] = true;
				}
				$store = $type->lower_cpp_store($statement, $receiver, $key, $value, $this->inline_adapters, $suspends ? implode(' ', $rhs_steps) : null);
				$lines[] = $this->async_statement($steps === [] ? $store : '{ ' . implode(' ', $steps) . ' ' . $store . ' }');
			}
			elseif ($statement instanceof ast_append_statement) {
				/** @var appendable_collection_type $type */
				$type = $statement->receiver->type_def();
				$steps = [];
				$receiver = $statement->receiver instanceof ast_field_expression
					? $this->retained_place($statement->receiver, $global_references, $declarations, $required_headers, $steps)
					: $this->value($statement->receiver, $global_references, $declarations, $required_headers);
				$before = $this->suspensions;
				[$value, $rhs_steps] = $this->staged_operand($statement->value, $global_references, $declarations, $required_headers);
				if ($before !== $this->suspensions) {
					$steps[] = 'auto&& write_target = ' . $receiver . ';';
					$receiver = 'write_target';
					array_push($steps, ...$rhs_steps);
				}
				$conversion = $statement->conversion();
				foreach ($type->write_type()->conversion_headers($conversion) as $header) {
					$required_headers[$header] = true;
				}
				$append = $type->lower_cpp_append($statement, $receiver, $value, $this->inline_adapters) . ';';
				$lines[] = $this->async_statement($steps === [] ? $append : '{ ' . implode(' ', $steps) . ' ' . $append . ' }');
			}
			elseif (($statement instanceof ast_local_declaration) || ($statement instanceof ast_storage_write)) {
				if ($body instanceof ast_function_body && $statement === $body->byte_view_length) {
					$this->byte_view = $statement->initializer->arguments[0];
					/** @var scoped_byte_read_type $type */
					$type = $this->byte_view->type_def();
					$required_headers[$type->byte_view_header()] = true;
					$field = $this->value($this->byte_view, $global_references, $declarations, $required_headers);
					$lines[] = "\tconst auto scpp_byte_view = " . $field . '.checked_bytes();';
				}
				$write = $this->binding($statement, $global_references, $declarations, $required_headers, $snapshot,
					operand_override: $first_initializer !== null && $statement === $body->statements[0] ? $first_initializer : null);
				if ($statement instanceof ast_assignment_statement && $statement->operator === binary_operator::coalesce) {
					/** @var carrier_type_definition $type */
					$type = $statement->local_def()->type_def();
					$lines[] = "\tif (!(" . $type->lower_cpp_presence($this->name($statement->local_def())) . ')) { ' . ltrim($this->async_statement($write . ';')) . ' }';
				} else $lines[] = $this->async_statement($snapshot === null ? $write . ';' : '{ const auto ' . $snapshot . '; ' . $write . '; }');
			}
			elseif ($statement instanceof ast_return_statement && ($statement->value === null || $statement->value->type_def() === type_void::instance())) {
				if ($statement->value !== null) {
					$lines[] = $this->async_statement($this->value($statement->value, $global_references, $declarations, $required_headers) . ';');
				}
				$lines[] = $resume === null ? "\t" . ($this->finally_exit?->save(statement_completion::returns) ?? ($function->is_async ? 'co_return;' : 'return;')) : "close(); return false;";
			}
			elseif ($statement instanceof ast_return_statement) {
				$conversion = $statement->conversion();
				if (!$function->is_async && $this->finally_exit === null && $statement->value instanceof ast_match_expression && $conversion === value_conversion::identity) {
					$lines[] = "\t" . $this->match_selection($statement->value, $global_references, $declarations, $required_headers);
					continue;
				}
				if ($function->is_implicit_entry) {
					$value = self::plain_integer_literal($statement->value, $conversion)
						?? Cpp_Exit::expression(exit_conversion::from($conversion),
							$this->value($statement->value, $global_references, $declarations, $required_headers));
				}
				else {
					$value = $this->value($statement->value, $global_references, $declarations, $required_headers,
						logical_root: $conversion === value_conversion::identity, borrow_result: $function->returns_reference);
					$type = $function->return_type_def();
					$value = $this->conversion($type, $conversion, $value, $statement->value);
					foreach ($type->conversion_headers($conversion) as $header) {
						$required_headers[$header] = true;
					}
				}
				$lines[] = $this->async_statement($this->finally_exit?->save(statement_completion::returns, $value) ?? (($function->is_async ? 'co_return ' : 'return ') . $value . ';'));
			}
			elseif ($statement instanceof ast_echo_statement)
			{
				foreach ($statement->values as $value) {
					$type = $value->type_def();
					foreach ($type->output_headers() as $header) {
						$required_headers[$header] = true;
					}
					$operand = $value instanceof ast_string_expression ? null
						: $this->value($value, $global_references, $declarations, $required_headers);
					$lines[] = $this->async_statement($type->lower_cpp_output($value, $operand));
				}
			}
			elseif ($statement instanceof ast_delay_statement) {
				$required_headers['scpp/async_timer.hpp'] = true;
				$lines[] = $this->async_statement('co_await ::scpp::async::delay_ms(' . $this->value($statement->value, $global_references, $declarations, $required_headers) . ');');
			}
			elseif ($statement instanceof ast_call_statement || $statement instanceof ast_await_statement) {
				// A discarded owning result can outlive the staged operands in expression form.
				$type = $statement->value->type_def();
				$setup = $statement instanceof ast_call_statement && ($type === type_void::instance() || $type->cpp_trivial_value()) ? [] : null;
				$call = $this->value($statement->value, $global_references, $declarations, $required_headers, statement_setup: $setup) . ';';
				$lines[] = $this->async_statement($setup ? '{ ' . implode(' ', $setup) . ' ' . $call . ' }' : $call);
			}
			else {
				throw new \LogicException('Unhandled prepared statement');
			}
			if ($this->transfer_labels !== [] && ($statement instanceof ast_repeating_statement || $statement instanceof ast_switch_statement)
				&& isset($this->transfer_labels[$label = 'scpp_break_' . $statement->start])) {
				$lines[] = $label . ':;';
				unset($this->transfer_labels[$label]);
			}
			if ($leaf) $lines[] = '}';
		}
		if ($resume !== null) {
			$lines[] = $this->generator_cleanup($resume, count($resume->scopes) - 1);
			array_pop($resume->scopes);
		}
		if ($body instanceof ast_function_body) $this->byte_view = null;
		return $lines;
	}

	/** Emit a jump only once its intervening finalizers have replayed the transfer. */
	private function labelled_transfer(ast_loop_transfer $statement): string
	{
		$label = ($statement instanceof ast_continue_statement ? 'scpp_continue_' : 'scpp_break_') . $statement->target->start;
		if ($this->finally_exit?->crosses($statement)) return $this->finally_exit->save_route($label, $statement);
		$this->transfer_labels[$label] = true;
		return 'goto ' . $label . ';';
	}

	/** Match just the first initializer using settled local identities, before any body effect. */
	private function matches_first_hash_read(ast_foreach_statement $loop): bool
	{
		$first = $loop->body->statements[0] ?? null;
		if (!$first instanceof ast_local_declaration || $first->initialization() !== value_conversion::identity
			|| !($first->initializer instanceof ast_index_expression) || $loop->destination !== $loop->value) return false;
		$source = $loop->source_value;
		$read = $first->initializer;
		return $source instanceof ast_name_expression && ($local = $source->local_binding()) instanceof ast_local_declaration
			&& $read->receiver instanceof ast_name_expression && $read->receiver->local_binding() === $local
			&& $read->index instanceof ast_name_expression && $read->index->local_binding() === $loop->value;
	}

	/** Keep a continue landing outside body locals, immediately before the native update or test.
	 * @param ast_function_declaration[] $declarations
	 * @param ast_global_declaration[] $globals
	 * @param bool[] $headers
	 * @return string[]
	 */
	private function loop_body_lines(ast_repeating_statement $loop, array &$declarations, array &$globals, array &$headers,
		statement_return_target $function, ?cpp_resume_context $resume, ?string $first_initializer = null): array
	{
		$body = $this->statements($loop->body, $declarations, $globals, $headers, $function, $resume, $first_initializer);
		if ($this->transfer_labels === []) return $body;
		$label = 'scpp_continue_' . $loop->start;
		if (!isset($this->transfer_labels[$label])) return $body;
		unset($this->transfer_labels[$label]);
		return ['{', ...$body, '}', $label . ':;'];
	}

	/**
	 * Lower entry selection independently of clause order, then let native switch handle transfers.
	 * @param array<string,ast_function_declaration> $declarations
	 * @param array<string,ast_global_declaration> $globals
	 * @param array<string,bool> $headers
	 * @return list<string>
	 */
	private function switch_lines(ast_switch_statement $syntax, statement_return_target $function,
		array &$declarations, array &$globals, array &$headers): array
	{
		$type = $syntax->subject->type_def();
		$labels = $type->dispatch_label_type();
		$dispatch = $labels->constant_dispatch();
		$subject = $this->value($syntax->subject, $globals, $declarations, $headers);
		if ($syntax->arms === []) return [$this->async_statement('(void)(' . $subject . ');')];
		$lines = [];
		$staged = $this->async_steps !== [];
		if ($staged) {
			$selected = new Cpp_Staged_Result($syntax->subject->type_def(), 'selected_' . $syntax->start);
			$lines[] = "\t{";
			$lines[] = $selected->declaration($headers);
			$lines[] = $this->async_statement($selected->initialize($subject));
			$subject = $selected->operand();
		}
		$owns_subject = false;
		if ($labels !== $type) {
			$subject = $this->carrier_clause_index($type, $dispatch, $subject, $syntax->arms, $globals, $declarations, $headers);
		} elseif ($dispatch === constant_dispatch::byte_equality) {
			$local = $syntax->subject instanceof ast_name_expression && $syntax->subject->local_binding() !== null;
			$place = $local ? $subject : 'switch_subject';
			$tests = [];
			foreach ($syntax->arms as $i => $arm) {
				if ($arm->condition === null) continue;
				$bytes = constant_dispatch::string_label_bytes($arm->condition);
				$tests[] = Cpp_String_Literals::equal_bytes($place, $bytes, $headers) . ' ? ' . $i . ' : ';
			}
			if ($tests === []) $lines[] = "\t(void)(" . $subject . ');';
			elseif (!$local) {
				$owns_subject = true;
				$lines[] = "\t{";
				$lines[] = "\tconst auto switch_subject = " . $subject . ';';
			}
			$subject = implode('', $tests) . '-1';
		}
		$head = count($lines);
		$lines[] = "\tswitch (" . $subject . ') {';
		$has_default = false;
		foreach ($syntax->arms as $i => $arm) {
			if ($arm->condition === null) {
				$has_default = true;
				$label = 'default';
			} else {
				$label = 'case ' . ($labels !== $type || $dispatch === constant_dispatch::byte_equality ? $i
					: $this->native_case($arm->condition, $dispatch, $globals, $declarations, $headers));
			}
			if ($arm->body->statements === []) {
				$lines[] = "\t" . $label . ($i === count($syntax->arms) - 1 ? ':;' : ':');
				continue;
			}
			$lines[] = "\t" . $label . ': {';
			foreach ($this->statements($arm->body, $declarations, $globals, $headers, $function) as $line) $lines[] = "\t" . $line;
			$lines[] = "\t}";
			if ($arm->falls_through && $i < count($syntax->arms) - 1) $lines[] = "\t[[fallthrough]];";
		}
		// A synthetic miss also keeps native constant and partial enum switches warning-safe.
		if (!$has_default) $lines[$head] .= ' default: break;';
		$lines[] = "\t}";
		if ($owns_subject) $lines[] = "\t}";
		if ($staged) $lines[] = "\t}";
		return $lines;
	}

	/**
	 * Select a clause before executing its body. A miss index is outside the clause index domain.
	 * @param list<ast_switch_arm|ast_match_arm> $arms
	 * @param array<string,ast_global_declaration> $globals
	 * @param array<string,ast_function_declaration> $declarations
	 * @param array<string,bool> $headers
	 */
	private function carrier_clause_index(carrier_type_definition $carrier, constant_dispatch $dispatch, string $operand,
		array $arms, array &$globals, array &$declarations, array &$headers): string
	{
		$cases = [];
		foreach ($arms as $index => $arm) {
			$labels = $arm instanceof ast_switch_arm ? ($arm->condition === null ? [] : [$arm->condition]) : $arm->labels;
			foreach ($labels as $label) {
				if ($dispatch === constant_dispatch::byte_equality) {
					$test = Cpp_String_Literals::equal_bytes('payload', constant_dispatch::string_label_bytes($label), $headers);
					$cases[] = 'if (' . $test . ') return ' . $index . ';';
				} else {
					$cases[] = 'case ' . $this->native_case($label, $dispatch, $globals, $declarations, $headers) . ': return ' . $index . ';';
				}
			}
		}
		if ($cases === []) return '((void)(' . $operand . '), -1)';
		$selection = implode(' ', $cases);
		if ($dispatch !== constant_dispatch::byte_equality) $selection = 'switch (payload) { ' . $selection . ' default: break; }';
		// The call keeps a temporary field receiver alive while the selector borrows its carrier.
		return '([](const ' . $carrier->cpp_type() . ' &carrier_subject) { if (!' . $carrier->lower_cpp_presence('carrier_subject')
			. ') return -1; const auto &payload = ' . $carrier->lower_cpp_extract('carrier_subject') . '; '
			. $selection . ' return -1; }(' . $operand . '))';
	}

	/**
	 * @param array<string,ast_global_declaration> $globals
	 * @param array<string,ast_function_declaration> $declarations
	 * @param array<string,bool> $headers
	 */
	private function native_case(ast_expression $label, constant_dispatch $dispatch, array &$globals, array &$declarations, array &$headers): string
	{
		if ($dispatch === constant_dispatch::enum_switch) return $this->value($label, $globals, $declarations, $headers);
		$integer = constant_dispatch::integer_label($label) ?? throw new \LogicException('Invalid prepared integer label');
		return self::integer_spelling($integer);
	}

	/**
	 * Enclose the entire protected operation. A finalizer thrown from the outer handler replaces its pending exception.
	 * @param ast_function_declaration[] $declarations
	 * @param ast_global_declaration[] $globals
	 * @param bool[] $headers
	 * @return string[]
	 */
	private function exception_region(ast_try_statement $syntax, statement_return_target $function,
		array &$declarations /** array<string,ast_function_declaration> */,
		array &$globals /** array<string,ast_global_declaration> */, array &$headers /** array<string,bool> */): array /** list<string> */
	{
		if ($syntax->finalizer === null) {
			return $this->protected_region($syntax, $function, $declarations, $globals, $headers);
		}
		$lines = ["\t{"];
		$finalizer = $this->statements($syntax->finalizer, $declarations, $globals, $headers, $function);
		if ($function->is_async) {
			$headers['exception'] = true;
			$lines[] = "\t\tstd::exception_ptr scpp_pending_exception;";
		} else {
			$lines[] = "\t\tauto scpp_finalizer = [&]() {";
			foreach ($finalizer as $line) $lines[] = "\t\t" . $line;
			$lines[] = "\t\t};";
		}
		$outer = $this->finally_exit;
		$pending = null;
		$returns = ($syntax->protected_completion & statement_completion::returns) !== 0;
		if ($returns || ($syntax->protected_completion & (statement_completion::loop_transfers | statement_completion::numbered))) {
			$result = $function->return_type_def();
			$type = !$returns || $result === type_void::instance() ? null
				: ($function->is_implicit_entry ? 'int' : $this->signature_type($result, false));
			if ($function->is_async && $type !== null) $headers['new'] = true;
			$pending = new Cpp_Finally_Exit($syntax, $type, $function->is_async);
			$declaration_line = count($lines);
			$lines[] = '';
			$lines[] = "\t\t" . $pending->start();
		}
		$lines[] = "\t\ttry {";
		$this->finally_exit = $pending ?? $outer;
		try {
			foreach ($this->protected_region($syntax, $function, $declarations, $globals, $headers) as $line) {
				$lines[] = "\t\t" . $line;
			}
		} finally {
			$this->finally_exit = $outer;
		}
		$lines[] = $function->is_async ? "\t\t} catch (...) { scpp_pending_exception = std::current_exception(); }"
			: "\t\t} catch (...) { scpp_finalizer(); throw; }";
		if ($pending !== null) {
			$lines[$declaration_line] = "\t\t" . $pending->declaration();
			if ($function->is_async) {
				$lines[] = "\t\t[[maybe_unused]] " . $pending->cleanup_label() . ":;";
			} else {
				$lines[] = "\t\t" . (($syntax->protected_completion & statement_completion::fallthrough) !== 0
					? 'return {};' : '__builtin_trap();');
				$lines[] = "\t\t}();";
			}
		}
		if ($function->is_async) {
			$lines[] = "\t\t{";
			foreach ($finalizer as $line) $lines[] = "\t\t" . $line;
			$lines[] = "\t\t}";
			$lines[] = "\t\tif (scpp_pending_exception) std::rethrow_exception(scpp_pending_exception);";
		} else $lines[] = "\t\tscpp_finalizer();";
		if ($pending !== null) {
			if ($syntax->completion & statement_completion::numbered) {
				foreach ($pending->routes as $label => $route) $lines[] = "\t\t" . $pending->selected($label) . $this->labelled_transfer($route);
			}
			foreach ([statement_completion::returns, statement_completion::breaks, statement_completion::continues] as $exit) {
				if (($syntax->completion & $exit) !== 0) $lines[] = "\t\t" . $pending->replay($exit, $outer);
			}
		}
		if (($syntax->completion & statement_completion::fallthrough) === 0) $lines[] = "\t\t__builtin_trap();";
		$lines[] = "\t}";
		return $lines;
	}

	/**
	 * Lower sibling native blocks; only the typed handler binds the language exception transport.
	 * @param ast_function_declaration[] $declarations
	 * @param ast_global_declaration[] $globals
	 * @param bool[] $headers
	 * @return string[]
	 */
	private function protected_region(ast_try_statement $syntax, statement_return_target $function,
		array &$declarations /** array<string,ast_function_declaration> */,
		array &$globals /** array<string,ast_global_declaration> */, array &$headers /** array<string,bool> */): array /** list<string> */
	{
		$saved = 'saved_exception_' . $syntax->start;
		$lines = $syntax->suspending_handlers ? ["\t{", "\t" . type_registry::exception()->cpp_type() . " $saved;"] : [];
		$lines[] = $syntax->handlers === [] ? "\t{" : "\ttry {";
		foreach ($this->statements($syntax->body, $declarations, $globals, $headers, $function) as $line) {
			$lines[] = "\t" . $line;
		}
		if ($syntax->handlers !== []) {
			$root = type_registry::exception();
			foreach ($root->cpp_headers() as $header) $headers[$header] = true;
			$lines[] = "\t} catch (const " . $root->cpp_type() . '& caught_exception) {';
			if ($syntax->suspending_handlers) {
				$lines[] = "\t\t$saved = caught_exception;";
				$lines[] = "\t}";
				$lines[] = "\tif ($saved.ptr) {";
				$lines[] = "\t\tconst auto& caught_exception = $saved;";
			}
			foreach ($syntax->handlers as $index => $handler) {
				$binding = $this->name($handler->binding);
				$type = $handler->binding->type_def();
				$branch = $index === 0 ? '' : 'else ';
				if ($type === $root) {
					$lines[] = "\t\t" . $branch . '{';
					$lines[] = "\t\t\t[[maybe_unused]] auto " . $binding . ' = caught_exception;';
				} else {
					$match = $this->inline_adapters->call(Cpp_Exception_Catch::request($root, $type), ['caught_exception']);
					$lines[] = "\t\t" . $branch . "if (auto $binding = $match) {";
				}
				foreach ($this->statements($handler->body, $declarations, $globals, $headers, $function) as $line) {
					$lines[] = "\t\t" . $line;
				}
				$lines[] = "\t\t}";
			}
			if ($type !== $root) $lines[] = $syntax->suspending_handlers ? "\t\telse throw caught_exception;" : "\t\telse throw;";
		}
		$lines[] = "\t}";
		if ($syntax->suspending_handlers) {
			if (($syntax->protected_completion & statement_completion::fallthrough) === 0) $lines[] = "\t__builtin_trap();";
			$lines[] = "\t}";
		}
		return $lines;
	}

	private function name(ast_local_binding $binding): string
	{
		if ($binding instanceof ast_writable_element) {
			$loop = $binding->parent;
			$source = $loop->source_value;
			return $source->type_def()->lower_cpp_element_read($this->name($source->local_def()), 'iteration_index_' . $loop->start);
		}
		return ($this->generator_locals[$binding->storage_id()] ?? null)
			?? 'var_' . self::name_component($binding->declaration_key()) . '_' . $binding->storage_id();
	}

	/** Write immediately through the same checked vector setter as an indexed assignment. */
	private function iteration_store(ast_writable_element $element, string $value): string
	{
		$loop = $element->parent;
		$source = $loop->source_value;
		$value = 'static_cast<' . $element->type_def()->cpp_type() . '>(' . $value . ')';
		return $source->type_def()->lower_cpp_write($this->name($source->local_def()), 'iteration_index_' . $loop->start, $value);
	}

	/**
	 * A statement owns its snapshot locally. A for update assigns to storage supplied by its loop.
	 * @param ast_global_declaration[] $global_references
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $headers
	 */
	private function compound(ast_assignment_statement $statement,
		array &$global_references /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$headers /** array<string,bool> */, ?string &$snapshot, string $snapshot_name = 'compound_previous'): string
	{
		if ($statement instanceof ast_index_compound) return $this->indexed_compound($statement, $global_references, $declarations, $headers);
		$property = $statement instanceof ast_property_compound;
		$destination = $property ? $statement->target : $statement->local_def();
		$type = $destination->type_def();
		$simple_rhs = $statement->value instanceof ast_integer_expression || ($statement->value instanceof ast_name_expression
			&& $statement->value->local_binding() !== null);
		$steps = [];
		if ($destination instanceof ast_qualified_data) {
			$steps[] = $this->discard_qualifier($destination->receiver, $global_references, $declarations, $headers) . ';';
			$name = $this->name_value($destination, $global_references, $declarations, $headers);
		} elseif ($destination instanceof ast_field_expression && !$simple_rhs) {
			$place = $this->retained_place($destination, $global_references, $declarations, $headers, $steps);
			$steps[] = 'auto& compound_target = ' . $place . ';';
			$name = 'compound_target';
		} else {
			$name = $property ? $this->value($destination, $global_references, $declarations, $headers) : $this->name($destination);
		}
		if ($destination instanceof ast_writable_element) {
			$snapshot = $snapshot_name . ' = ' . $this->compound_left($statement, $type, $name, $headers);
			$right = $this->value($statement->value, $global_references, $declarations, $headers);
			return $this->iteration_store($destination, $this->compound_result($statement, $type, $snapshot_name, $right, $headers));
		}
		$execution = $statement->execution_type();
		$before = $this->suspensions;
		[$right, $rhs_steps] = $this->staged_operand($statement->value, $global_references, $declarations, $headers);
		if ($before !== $this->suspensions) {
			$old = $this->compound_left($statement, $type, $name, $headers);
			$steps[] = 'const auto ' . $snapshot_name . ' = ' . $old . ';';
			array_push($this->async_steps, ...$steps, ...$rhs_steps);
			return $name . ' = ' . $this->compound_result($statement, $type, $snapshot_name, $right, $headers);
		}
		$conversion = $statement->conversion();
		if (($type === $execution) && ($destination instanceof ast_local_binding || $destination instanceof ast_field_expression)
			&& ($conversion === value_conversion::identity) && ($statement->left_conversion() === value_conversion::identity)
			&& ($statement->write_back() === value_conversion::identity)) {
			$operator = $execution->cpp_compound_operator($statement->operator);
			if ($operator !== null && ($simple_rhs || ($destination instanceof ast_local_binding
				&& self::constant_compound_operand($statement->value)))) {
				return $name . ' ' . $operator . ' ' . $right;
			}
		}
		$direct_read = $simple_rhs && ($destination instanceof ast_field_expression
			|| ($destination instanceof ast_local_binding && $type->cpp_trivial_value()));
		$left = $this->compound_left($statement, $type, $direct_read ? $name : $snapshot_name, $headers);
		$result = $this->compound_result($statement, $type, $left, $right, $headers);
		if ($direct_read) return $name . ' = ' . $result;
		if ($steps !== []) {
			$steps[] = 'const auto ' . $snapshot_name . ' = ' . $name . ';';
			$steps[] = $name . ' = ' . $result . ';';
			return "{\n\t\t" . implode("\n\t\t", $steps) . "\n\t}";
		}
		$snapshot = $snapshot_name . ' = ' . $name;
		return $name . ' = ' . $result;
	}

	/** Convert the old value at the read, before a suspending RHS can have effects. @param bool[] $headers */
	private function compound_left(ast_assignment_statement $statement, type_definition $type, string $left, array &$headers /** array<string,bool> */): string
	{
		$execution = $statement->execution_type();
		if ($statement->left_conversion() !== value_conversion::identity) {
			$left = $this->conversion($execution, $statement->left_conversion(), $left, source_type: $type);
			foreach ($execution->conversion_headers($statement->left_conversion()) as $header) {
				$headers[$header] = true;
			}
		}
		return $left;
	}

	/** Lower the settled operation and checked write-back identically for locals, properties and indexed elements. @param bool[] $headers */
	private function compound_result(ast_assignment_statement $statement, type_definition $type, string $left, string $right,
		array &$headers /** array<string,bool> */): string
	{
		$execution = $statement->execution_type();
		$conversion = $statement->conversion();
		if ($conversion !== value_conversion::identity) {
			$right = $this->conversion($execution, $conversion, $right, $statement->value);
			foreach ($execution->conversion_headers($conversion) as $header) {
				$headers[$header] = true;
			}
		}
		foreach ($execution->binary_headers($statement->operator) as $header) {
			$headers[$header] = true;
		}
		$result = $execution->lower_prepared_compound($statement, $left, $right);
		if ($statement->write_back() !== value_conversion::identity) {
			$result = $this->conversion($type, $statement->write_back(), $result, source_type: $execution);
			foreach ($type->conversion_headers($statement->write_back()) as $header) {
				$headers[$header] = true;
			}
		}
		return $result;
	}

	/**
	 * Snapshot the selected collection/key/value, never an element address across a potentially mutating RHS.
	 * @param ast_global_declaration[] $globals
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $headers
	 */
	private function indexed_compound(ast_index_compound $statement, array &$globals /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$headers /** array<string,bool> */): string
	{
		$target = $statement->target;
		$steps = [];
		$key = $this->indexed_place($target, $globals, $declarations, $headers, $steps);
		/** @var indexed_mutation_type $collection */
		$collection = $target->receiver->type_def();
		$old = $collection->lower_cpp_mutation_read($target->index, 'compound_collection', $key);
		$steps[] = 'const auto compound_previous = ' . $this->compound_left($statement, $target->type_def(), $old, $headers) . ';';
		[$right, $rhs_steps] = $this->staged_operand($statement->value, $globals, $declarations, $headers);
		array_push($steps, ...$rhs_steps);
		$result = $this->compound_result($statement, $target->type_def(), 'compound_previous', $right, $headers);
		// Runtime setters enforce exact C++ representations, including native literal/arithmetic result spellings.
		$result = 'static_cast<' . $target->type_def()->cpp_type() . '>(' . $result . ')';
		// Existing-element compounds must not recreate a key removed by RHS replacement.
		$steps[] = 'const auto compound_result = ' . $result . ';';
		$steps[] = '(void)' . $collection->lower_cpp_mutation_read($target->index, 'compound_collection', $key) . ';';
		$steps[] = $collection->lower_cpp_replace($target->index, 'compound_collection', $key, 'compound_result');
		return '{ ' . implode(' ', $steps) . ' }';
	}

	/**
	 * Share owner retention and single key evaluation for indexed compounds and updates.
	 * @param ast_global_declaration[] $globals
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $headers
	 * @param string[] $steps
	 */
	private function indexed_place(ast_index_expression $target, array &$globals /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$headers /** array<string,bool> */,
		array &$steps /** list<string> */): string
	{
		$place = $this->write_receiver($target->receiver, $globals, $declarations, $headers, $steps);
		$steps[] = 'auto&& compound_collection = ' . $place . ';';
		[$key, $key_steps] = $this->staged_operand($target->index, $globals, $declarations, $headers);
		array_push($steps, ...$key_steps);
		if (!($target->index instanceof ast_integer_expression)) {
			$steps[] = 'const auto compound_key = ' . $key . ';';
			$key = 'compound_key';
		}
		return $key;
	}

	/**
	 * Select each destination subscript before evaluating the next key.
	 * @param ast_global_declaration[] $globals
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $headers
	 * @param string[] $steps
	 */
	private function write_receiver(ast_expression $receiver, array &$globals /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$headers /** array<string,bool> */,
		array &$steps /** list<string> */): string
	{
		if (!($receiver instanceof ast_index_expression)) {
			return $this->retained_place($receiver, $globals, $declarations, $headers, $steps);
		}
		$base = $this->write_receiver($receiver->receiver, $globals, $declarations, $headers, $steps);
		$name = 'write_dimension_' . $receiver->end;
		$steps[] = 'auto&& ' . $name . ' = ' . $base . ';';
		[$key, $key_steps] = $this->staged_operand($receiver->index, $globals, $declarations, $headers);
		array_push($steps, ...$key_steps);
		return $receiver->receiver->type_def()->lower_cpp_index($receiver->index, $name, $key);
	}

	/**
	 * Apply generated updates to a copied element, then store it; no element address escapes.
	 * @param ast_global_declaration[] $globals
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $headers
	 */
	private function indexed_update(ast_index_update $statement, array &$globals /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$headers /** array<string,bool> */): string
	{
		$target = $statement->target;
		$steps = [];
		$key = $this->indexed_place($target, $globals, $declarations, $headers, $steps);
		/** @var indexed_mutation_type $collection */
		$collection = $target->receiver->type_def();
		$steps[] = 'auto updated_element = ' . $collection->lower_cpp_mutation_read($target->index, 'compound_collection', $key) . ';';
		$steps[] = $target->type_def()->lower_cpp_update($statement->operator, 'updated_element', $statement->postfix) . ';';
		$steps[] = $collection->lower_cpp_replace($target->index, 'compound_collection', $key, 'updated_element');
		return '{ ' . implode(' ', $steps) . ' }';
	}

	/**
	 * Emit one subject evaluation and lazy return arms; the surrounding context owns any result conversion.
	 * @param ast_global_declaration[] $globals
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $headers
	 */
	private function match_selection(ast_match_expression $match, array &$globals /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$headers /** array<string,bool> */, ?Cpp_Staged_Result $result = null): string
	{
		$conditional = ast_match_expression::is_condition_subject($match->subject);
		$type = $match->subject->type_def();
		$labels = $type->dispatch_label_type();
		$dispatch = $labels->constant_dispatch();
		if ($conditional || ($labels === $type && $dispatch === constant_dispatch::byte_equality)) {
			$code = '';
			$scope = false;
			if (!$conditional) {
				$subject = $this->value($match->subject, $globals, $declarations, $headers);
				if (count($match->arms) === 1) $code = '(void)(' . $subject . '); ';
				elseif (!($match->subject instanceof ast_name_expression && $match->subject->local_binding() !== null)) {
					$scope = true;
					// A field may borrow a temporary receiver that dies at this semicolon. Own the selected bytes.
					$code = 'const auto dispatch_subject = ' . $subject . '; ';
					$subject = 'dispatch_subject';
				}
			}
			$default = null;
			foreach ($match->arms as $arm) {
				if ($arm->labels === []) {
					$default = $arm;
					continue;
				}
				if ($conditional) {
					[$test, $prerequisites] = $this->staged_operand($arm->labels[0], $globals, $declarations, $headers, condition: true);
					if ($prerequisites !== []) {
						$name = 'match_condition_' . $arm->start;
						$code .= "bool $name; { " . implode(' ', $prerequisites) . " $name = $test; } ";
						$test = $name;
					}
				}
				else {
					$tests = [];
					foreach ($arm->labels as $label) {
						$bytes = constant_dispatch::string_label_bytes($label);
						$tests[] = '(' . Cpp_String_Literals::equal_bytes($subject, $bytes, $headers) . ')';
					}
					$test = implode(' || ', $tests);
				}
				$code .= 'if (' . $test . ') { '
					. $this->selection_exit($arm->result, $globals, $declarations, $headers, $result) . ' } ';
			}
			$code .= $this->selection_exit($default->result, $globals, $declarations, $headers, $result);
			return $scope ? '{ ' . $code . ' }' : $code;
		}
		$subject = $this->value($match->subject, $globals, $declarations, $headers);
		if ($labels !== $type) $subject = $this->carrier_clause_index($type, $dispatch, $subject, $match->arms, $globals, $declarations, $headers);
		$code = 'switch (' . $subject . ') { ';
		foreach ($match->arms as $index => $arm) {
			if ($arm->labels === []) $code .= 'default: ';
			elseif ($labels !== $type) $code .= 'case ' . $index . ': ';
			else foreach ($arm->labels as $label) {
				$code .= 'case ' . $this->native_case($label, $dispatch, $globals, $declarations, $headers) . ': ';
			}
			$code .= $this->selection_exit($arm->result, $globals, $declarations, $headers, $result) . ' ';
		}
		return $code . '}';
	}

	/**
	 * Use the prepared root view and unified native transport for all throws.
	 * @param ast_global_declaration[] $globals
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $headers
	 */
	private function throw_call(ast_throw_expression $throw, array &$globals /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$headers /** array<string,bool> */): string
	{
		$root = type_registry::exception();
		foreach ($root->cpp_headers() as $header) $headers[$header] = true;
		$operand = $this->value($throw->operand, $globals, $declarations, $headers);
		return '::scpp::throw_exception(' . $this->conversion($root, $throw->conversion, $operand, $throw->operand) . ')';
	}

	/**
	 * Exit only the selected lazy branch, with its value or the language throw.
	 * @param ast_global_declaration[] $globals
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $headers
	 */
	private function selection_exit(ast_expression $branch, array &$globals /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$headers /** array<string,bool> */, ?Cpp_Staged_Result $result = null): string
	{
		if ($branch instanceof ast_throw_expression) {
			$start = count($this->async_steps);
			$raise = $this->throw_call($branch, $globals, $declarations, $headers) . ';';
			if ($start === count($this->async_steps)) return $raise;
			return '{ ' . implode(' ', array_splice($this->async_steps, $start)) . ' ' . $raise . ' }';
		}
		if ($result !== null) {
			[$operand, $steps] = $this->staged_operand($branch, $globals, $declarations, $headers, destination: $result);
			$steps[] = $result->initialize($operand);
			return '{ ' . implode(' ', $steps) . ' break; }';
		}
		return 'return ' . $this->value($branch, $globals, $declarations, $headers) . ';';
	}

	/** Lower one discarded header action with the ordinary call or binding emitter.
	 * @param ast_global_declaration[] $globals
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $headers
	 */
	private function for_action(ast_statement $item, array &$globals, array &$declarations, array &$headers,
		?string &$snapshot, string $snapshot_name = 'compound_previous'): string
	{
		$snapshot = null;
		return $item instanceof ast_call_statement ? $this->value($item->value, $globals, $declarations, $headers)
			: $this->binding($item, $globals, $declarations, $headers, $snapshot, $snapshot_name);
	}

	/**
	 * Render a binding without its delimiter and report any required prior read to its enclosing statement or loop.
	 * @param ast_global_declaration[] $global_references
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $headers
	 */
	private function binding(ast_local_declaration|ast_storage_write $statement,
		array &$global_references /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$headers /** array<string,bool> */, ?string &$snapshot, string $snapshot_name = 'compound_previous', ?string $operand_override = null): string
	{
		$snapshot = null;
		if (($statement instanceof ast_assignment_statement) && $statement->calculates_value()) {
			return $this->compound($statement, $global_references, $declarations, $headers, $snapshot, $snapshot_name);
		}
		if ($statement instanceof ast_index_update) {
			return $this->indexed_update($statement, $global_references, $declarations, $headers);
		}
		if ($statement instanceof ast_property_update) {
			$target = $this->value($statement->target, $global_references, $declarations, $headers);
			return $statement->target->type_def()->lower_cpp_update($statement->operator, $target, $statement->postfix);
		}
		if ($statement instanceof ast_reset_statement) {
			$local = $statement->local_def();
			$empty = $local->type_def()->cpp_type() . '{}';
			return $local instanceof ast_writable_element ? $this->iteration_store($local, $empty) : $this->name($local) . ' = ' . $empty;
		}
		if ($statement instanceof ast_update_statement) {
			return $this->local_update($statement);
		}
		if ($statement instanceof ast_local_declaration) {
			$type = $statement->type_def();
			if ($type instanceof application_collection_type) $this->concrete_collections->request($type);
			if ($type instanceof record_type_definition) {
				$declarations[$type->declaration->symbol_key()] = $type->declaration;
			}
			if ($type instanceof record_handle_definition) $declarations[$type->name] = $type;
			$conversion = $statement->initialization();
			$value = $statement->initializer;
			if ($type instanceof fixed_array_type) {
				foreach ($type->cpp_headers() as $header) $headers[$header] = true;
				if ($value instanceof ast_collection_expression && $value->suspends) {
					$name = $this->name($statement);
					$fill = $this->initialize_fixed_elements($value, $name, $global_references, $declarations, $headers);
					return '[[maybe_unused]] ' . $type->declarator($name) . '; { ' . implode(' ', $fill) . ' }';
				}
				$initializer = $value instanceof ast_struct_default ? '{}' : $this->value($value, $global_references, $declarations, $headers);
				$name = $this->name($statement);
				$storage = '[[maybe_unused]] ' . $type->declarator($name);
				if ($value instanceof ast_struct_default || $value instanceof ast_collection_expression) return $storage . ' = ' . $initializer;
				$copy = '__builtin_memmove(' . $name . ', ' . $initializer . ', sizeof(' . $name . '))';
				return $storage . '; ' . ($this->async_steps === [] ? $copy : $this->async_statement($copy . ';'));
			}
			if ($type instanceof owned_callable_type) $this->callable_dependencies($type->signature, $declarations);
			$native = $type instanceof lambda_type_definition ? 'auto' : $type->cpp_type();
			$prefix = '[[maybe_unused]] ' . $native . ' ' . $this->name($statement);
			foreach ($type->cpp_headers() as $header) {
				$headers[$header] = true;
			}
			$literal = self::plain_integer_literal($value, $conversion);
			if ($literal !== null) {
				return $this->binding_initialization($statement, $prefix, $literal);
			}
		}
		else {
			$type = $statement->local_def()->type_def();
			$conversion = $statement->conversion();
			$value = $statement->value;
			$prefix = $this->name($statement->local_def());
			if (($conversion === value_conversion::identity) && ($value instanceof ast_name_expression)
				&& $value->refers_to($statement->local_def())) {
				return '(void)' . $prefix;
			}
		}
		// A staged choice can construct either arm directly in an identity initializer's final storage.
		$destination = $this->async_body && $statement instanceof ast_local_declaration && ($value instanceof ast_ternary_expression || $value instanceof ast_match_expression)
			&& $conversion === value_conversion::identity && !$type->cpp_trivial_value()
			? new Cpp_Staged_Result($type, $this->name($statement) . '_storage') : null;
		$operand = $operand_override ?? $this->value($value, $global_references, $declarations, $headers,
			typed_initializer: ($statement instanceof ast_local_declaration) && ($conversion === value_conversion::identity),
			logical_root: $conversion === value_conversion::identity, destination: $destination);
		if ($type instanceof fixed_array_type) return '__builtin_memmove(' . $prefix . ', ' . $operand . ', sizeof(' . $prefix . '))';
		foreach ($type->conversion_headers($conversion) as $header) {
			$headers[$header] = true;
		}
		if ($statement instanceof ast_local_declaration && $this->async_steps !== []) {
			if (!$type->cpp_trivial_value()) {
				$name = $this->name($statement);
				$result = $destination ?? new Cpp_Staged_Result($type, $name . '_storage');
				$initialize = $result->initialize($this->conversion($type, $conversion, $operand, $value));
				return $result->declaration($headers) . "\n" . $this->async_statement($initialize)
					. "\n\t[[maybe_unused]] auto& $name = " . $name . '_storage.value';
			}
			// Scalar storage lives in the source scope; operand temporaries end immediately after assignment.
			$assignment = $this->name($statement) . ' = ' . $this->conversion($type, $conversion, $operand, $value) . ';';
			return $prefix . ";\n" . $this->async_statement($assignment);
		}
		$converted = $this->conversion($type, $conversion, $operand, $value);
		if ($statement instanceof ast_storage_write && $statement->local_def() instanceof ast_writable_element) {
			return $this->iteration_store($statement->local_def(), $converted);
		}
		return $this->binding_initialization($statement, $prefix, $converted);
	}

	private function local_update(ast_update_statement $update): string
	{
		$local = $update->local_def();
		if ($local instanceof ast_writable_element) {
			$operation = $local->type_def()->lower_cpp_update($update->operator, 'element', $update->postfix);
			return '([&]() { auto element = ' . $this->name($local) . '; auto result = ' . $operation . '; '
				. $this->iteration_store($local, 'element') . '; return result; }())';
		}
		return $local->type_def()->lower_cpp_update($update->operator, $this->name($local), $update->postfix);
	}

	/** A suspended declaration starts its lifetime only after its checked initializer succeeds. */
	private function binding_initialization(ast_local_declaration|ast_storage_write $statement, string $prefix, string $value): string
	{
		if ($statement instanceof ast_local_declaration && isset($this->generator_locals[$statement->storage_id()])) {
			$id = $statement->storage_id();
			return 'new (&local_' . $id . '.value) local_' . $id . '_type(' . $value . '); live_' . $id . ' = true';
		}
		return $prefix . ' = ' . $value;
	}

	/**
	 * Fill inline dimensions in their enclosing declaration, in source order. Only byte leaves hold values.
	 * @param ast_global_declaration[] $globals
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $headers
	 * @return string[]
	 */
	private function initialize_fixed_elements(ast_collection_expression $literal, string $target,
		array &$globals, array &$declarations, array &$headers): array
	{
		$element_type = $literal->type_def()->element;
		$steps = [];
		foreach ($literal->elements as $i => $element) {
			$slot = $target . '[' . $i . ']';
			if ($element_type instanceof fixed_array_type && $element instanceof ast_collection_expression) {
				array_push($steps, ...$this->initialize_fixed_elements($element, $slot, $globals, $declarations, $headers));
				continue;
			}
			[$operand, $before] = $this->staged_operand($element, $globals, $declarations, $headers);
			$conversion = $literal->element_conversion($i);
			foreach ($element_type->conversion_headers($conversion) as $header) $headers[$header] = true;
			$before[] = $element_type instanceof fixed_array_type ? "__builtin_memmove($slot, $operand, sizeof($slot));"
				: $slot . ' = ' . $element_type->lower_cpp_exact_argument($element, $conversion, $operand, $this->inline_adapters) . ';';
			$steps[] = '{ ' . implode(' ', $before) . ' }';
		}
		return $steps;
	}

	/**
	 * Populate private input in the coroutine so each conversion finishes before the next entry starts.
	 * @param ast_global_declaration[] $globals
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $headers
	 */
	private function suspending_collection(ast_keyed_collection_expression|ast_collection_expression $literal,
		array &$globals, array &$declarations, array &$headers): string
	{
		$type = $literal->type_def();
		$keyed = $literal instanceof ast_keyed_collection_expression;
		$entries = $keyed ? $literal->entries : $literal->elements;
		$binding = $keyed ? $type->keyed_initialization() : $type->list_initialization();
		$name = 'input_' . $literal->start . '_' . $literal->end;
		$count = count($entries);
		if ($type instanceof application_collection_type) $this->concrete_collections->request($type);
		if ($binding === null) {
			$this->async_steps[] = $type->cpp_type() . " $name = " . $type->lower_cpp_empty() . ';';
		} else {
			$headers['new'] = true;
			$this->async_steps[] = Cpp_List_Initialization::staged_input($binding, $count, $name);
		}
		foreach ($entries as $i => $entry) {
			$step = [];
			if ($keyed) {
				[$key, $step] = $this->staged_operand($entry->key, $globals, $declarations, $headers);
				$step[] = 'const auto input_key = ' . $type->lower_cpp_key($entry->key, $key) . ';';
			}
			$element = $keyed ? $entry->value : $entry;
			$conversion = $keyed ? $entry->conversion : $literal->element_conversion($i);
			[$operand, $prerequisites] = $this->staged_operand($element, $globals, $declarations, $headers);
			array_push($step, ...$prerequisites);
			foreach ($type->write_type()->conversion_headers($conversion) as $header) $headers[$header] = true;
			if ($binding === null) {
				$step[] = $type->lower_cpp_sequence_append($element, $conversion, $name, $operand, $this->inline_adapters) . ';';
			} else {
				$converted = $type->write_type()->lower_cpp_exact_argument($element, $conversion, $operand, $this->inline_adapters);
				$initializer = $keyed ? '{input_key, ' . $converted . '}' : '(' . $converted . ')';
				$step[] = "::new (static_cast<void*>($name.data + $i)) $binding->element" . $initializer . "; ++$name.live;";
			}
			$this->async_steps[] = '{ ' . implode(' ', $step) . ' }';
		}
		++$this->async_owned_steps;
		return $binding === null ? 'static_cast<' . $type->cpp_type() . "&&>($name)" : "$binding->factory($name.data, $count)";
	}

	/**
	 * Release a nested operand's staged owners before evaluating the next sibling.
	 * @param array<string,ast_global_declaration> $global_references
	 * @param array<string,ast_function_declaration> $declarations
	 * @param array<string,bool> $required_headers
	 */
	private function scoped_async_operand(ast_expression $value, array &$global_references, array &$declarations, array &$required_headers,
		bool &$reorderable, bool $typed_initializer, bool $logical_root, bool $bare_comparison, ?Cpp_Staged_Result $destination): string
	{
		$start = count($this->async_steps);
		$owned = $this->async_owned_steps;
		$operand = $this->value($value, $global_references, $declarations, $required_headers, $reorderable,
			$typed_initializer, $logical_root, $bare_comparison, inside_scope: true);
		if ($owned === $this->async_owned_steps || $start === count($this->async_steps)) return $operand;
		$name = 'result_' . $value->start . '_' . $value->end;
		$inner = array_splice($this->async_steps, $start);
		$result = $destination ?? new Cpp_Staged_Result($value->type_def(), $name);
		if ($destination === null) $this->async_steps[] = $result->declaration($required_headers);
		$this->async_steps[] = '{ ' . implode(' ', $inner) . ' ' . $result->initialize($operand) . ' }';
		$this->async_owned_steps = $owned + ($value->type_def()->cpp_trivial_value() ? 0 : 1);
		$reorderable = false;
		return $destination === null ? $result->operand() : '';
	}

	/**
	 * A staged operand with a supplied destination returns an empty operand after constructing that destination.
	 * Only the root of an identity initializer may rely on its destination for the native result type.
	 * Typed values carry normalized constants or settled bindings; no source-name lookup occurs here.
	 * $logical_root: the caller supplies any required grouping for a logical expression.
	 * $bare_comparison: the condition accepts an unparenthesized comparison (not the operand of !).
	 * $reorderable: output proof for this subtree, computed during lowering, never retained in preparation.
	 * True permits unsequenced evaluation only alongside other proven subtrees; calls always remain false.
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $required_headers
	 * @param ast_global_declaration[] $global_references
	 * @param list<string>|null $statement_setup
	 */
	private function value(ast_expression $value, array &$global_references /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$required_headers /** array<string,bool> */,
		bool &$reorderable = false, bool $typed_initializer = false, bool $logical_root = false, bool $bare_comparison = false, bool $borrow_result = false, bool $inside_scope = false, ?Cpp_Staged_Result $destination = null, ?array &$statement_setup = null): string
	{
		if ($this->async_body && !$inside_scope && ($value->parent instanceof ast_expression || $value->parent instanceof ast_foreach_statement)
			&& ($value instanceof ast_call_expression || $value instanceof ast_binary_expression || $value instanceof ast_await_expression || $value instanceof ast_construction_expression)) {
			return $this->scoped_async_operand($value, $global_references, $declarations, $required_headers, $reorderable,
				$typed_initializer, $logical_root, $bare_comparison, $destination);
		}
		$reorderable = false;
		$value_type = $value->type_def();
		if ($value_type instanceof enum_type_definition || $value_type instanceof nullable_enum_type_definition) {
			$enum = $value_type instanceof nullable_enum_type_definition ? $value_type->payload_type : $value_type;
			$declarations[$enum->declaration->symbol_key()] = $enum->declaration;
		}
		if ($value_type instanceof record_handle_definition || $value_type instanceof record_type_definition) {
			++$this->record_expressions;
		}
		foreach ($value_type->cpp_headers() as $header) {
			$required_headers[$header] = true;
		}
		if ($this->byte_view !== null && $value === $this->byte_view->parent) return 'scpp_byte_view.size()';
		$producer = $value instanceof ast_await_expression ? $value->operand
			: ($value instanceof ast_call_expression ? $value->blocking_producer() : null);
		if ($producer !== null) {
			$suspends = $value instanceof ast_await_expression && $value->suspends;
			if ($suspends) ++$this->suspensions;
			$callee = $producer->function_def();
			$name = self::function_name($callee);
			$task_name = $suspends ? $this->require_task($callee->return_type_def(), $required_headers) : null;
			$declarations[$name] = $callee;
			$call = $this->invocation($producer, $callee, $suspends ? $name : $name . 'wait',
				$callee->return_type_def(), $global_references, $declarations, $required_headers,
				$task_name, $statement_setup);
			return $suspends ? '(co_await ' . $call . ')' : $call;
		}
		if ($value instanceof ast_update_expression) {
			return $this->local_update($value->update);
		}
		if ($value instanceof ast_condition_assignment) {
			$local = $value->target->local_def();
			$name = $this->name($local);
			if ($value->conversion === value_conversion::identity && $value->value instanceof ast_name_expression
				&& $value->value->refers_to($local)) return $name;
			foreach ($value_type->conversion_headers($value->conversion) as $header) $required_headers[$header] = true;
			$rhs = $this->value($value->value, $global_references, $declarations, $required_headers);
			return '(' . $name . ' = ' . $this->conversion($value_type, $value->conversion, $rhs, $value->value) . ')';
		}
		if ($value instanceof ast_ternary_expression) {
			$test = $this->condition($value->condition, $global_references, $declarations, $required_headers);
			if ($this->async_body) {
				$outer_owned = $this->async_owned_steps;
				$result = $destination ?? new Cpp_Staged_Result($value_type, 'selection_' . $value->start . '_' . $value->end);
				[$yes, $true_steps] = $this->staged_operand($value->when_true, $global_references, $declarations, $required_headers, destination: $result);
				[$no, $false_steps] = $this->staged_operand($value->when_false, $global_references, $declarations, $required_headers, destination: $result);
				if ($true_steps !== [] || $false_steps !== []) {
					if ($destination === null) $this->async_steps[] = $result->declaration($required_headers);
					$true_steps[] = $result->initialize($yes);
					$false_steps[] = $result->initialize($no);
					$this->async_steps[] = "if ($test) { " . implode(' ', $true_steps) . ' } else { ' . implode(' ', $false_steps) . ' }';
					$this->async_owned_steps = $outer_owned + ($value_type->cpp_trivial_value() ? 0 : 1);
					return $destination === null ? $result->operand() : '';
				}
			} else {
				$yes = $this->value($value->when_true, $global_references, $declarations, $required_headers);
				$no = $this->value($value->when_false, $global_references, $declarations, $required_headers);
			}
			$selection = '(' . $test . ' ? ' . $yes . ' : ' . $no . ')';
			// Two native lvalue arms can yield a reference. Publish an owned value at an untyped use site.
			return !$typed_initializer && !$value_type->cpp_trivial_value()
				? $this->signature_type($value_type, false) . $selection : $selection;
		}
		if ($value instanceof ast_match_expression) {
			if ($this->async_body && $value->suspends) {
				$result = $destination ?? new Cpp_Staged_Result($value_type, 'matched_' . $value->start . '_' . $value->end);
				if ($destination === null) $this->async_steps[] = $result->declaration($required_headers);
				$body = $this->match_selection($value, $global_references, $declarations, $required_headers, $result);
				$this->async_steps[] = 'do { ' . $body . ' } while (false);';
				if (!$value_type->cpp_trivial_value()) ++$this->async_owned_steps;
				return $destination === null ? $result->operand() : '';
			}
			return '([&]() -> ' . $this->signature_type($value_type, false) . ' { ' . $this->match_selection($value, $global_references, $declarations, $required_headers) . ' }())';
		}
		if ($value instanceof ast_object_access_expression) {
			$operand = $this->value($value->operand, $global_references, $declarations, $required_headers);
			$request = Cpp_Object_Access::request($value->operand->type_def(), $value->target_type(), $value->test, $value->dynamic_check());
			return $this->inline_adapters->call($request, [$operand]);
		}
		if ($value instanceof ast_ownership_expression) {
			$type = $value->type_def();
			$declarations[$type->name] = $type;
			$operand = $this->value($value->operand, $global_references, $declarations, $required_headers);
			return $value->lock ? '(' . $operand . ').lock()' : $type->cpp_type() . '(' . $operand . ')';
		}
		if ($value instanceof ast_this_expression) return 'scpp_self';
		if ($value instanceof ast_construction_expression || ($value instanceof ast_call_expression && $value->constructed_record() !== null)) {
			$type = $value->type_def();
			$shared = $value instanceof ast_new_expression || $value instanceof ast_call_expression;
			if ($value instanceof ast_new_expression && $type instanceof runtime_handle_type) {
				$factory = runtime_functions::resolve($value->source()->flavor->name . ':new:' . $type->name);
				if ($factory instanceof runtime_function) {
					$required_headers[$factory->header] = true;
					return $this->invocation($value, $factory, $factory->symbol, $type, $global_references, $declarations, $required_headers);
				}
			}
			if ($shared && $type instanceof type_exception) {
				$message = $this->value($value->arguments[0], $global_references, $declarations, $required_headers);
				return $type->factory->symbol . '(' . $message . ')';
			}
			if ($shared) $declarations[$type->name] = $type;
			if ($value->arguments === [] && !$shared) return '{}';
			$record = $shared ? $type->payload : $type;
			$constructor = $record->constructor();
			if ($constructor === null) return $type->cpp_type() . '::make()';
			$this->collect_signature_types($constructor, $declarations);
			return $this->invocation($value, $constructor, $type->cpp_type() . ($shared ? '::make' : ''), $type,
				$global_references, $declarations, $required_headers);
		}
		if ($value instanceof ast_field_expression) {
			if ($value_type instanceof fixed_array_type) {
				$steps = [];
				return $this->retained_place($value, $global_references, $declarations, $required_headers, $steps, full_expression: true);
			}
			if ($value->receiver instanceof ast_name_expression && $value->receiver->type_def() instanceof shared_record_type_definition
				&& ($local = $value->receiver->local_binding()) !== null && $local->type_def() instanceof class_union_type_definition) {
				$pointer = $this->union_pointer($value->receiver, $local, $declarations);
				if ($pointer !== null) return $pointer . '->' . Cpp_Identifier::source($value->field);
			}
			$receiver = $this->value($value->receiver, $global_references, $declarations, $required_headers);
			if ($value->receiver->type_def() instanceof runtime_handle_type) {
				$property = runtime_functions::resolve($value->source()->flavor->name . ':property:' . $value->receiver->type_def()->name . ':' . $value->field);
				if ($property instanceof runtime_function) {
					$required_headers[$property->header] = true;
					return $property->symbol . '(' . $receiver . ')';
				}
			}
			$access = $value->receiver->type_def() instanceof shared_record_type_definition ? '->' : '.';
			return '(' . $receiver . ')' . $access . Cpp_Identifier::source($value->field);
		}
		if (($value instanceof ast_collection_expression || $value instanceof ast_keyed_collection_expression)
			&& $value_type instanceof boxed_type_definition && $value->source()->flavor === flavor::python) {
			$list = $value instanceof ast_collection_expression;
			$factory = $value_type->cpp_type() . ($list ? '::python_list()' : '::container()');
			$items = $list ? $value->elements : $value->entries;
			if ($items === []) return $factory;
			$box = $value->suspends ? 'boxed_' . $value->start . '_' . $value->end : 'boxed';
			$code = "auto $box = $factory; ";
			if ($value->suspends) $this->async_steps[] = $code;
			foreach ($items as $i => $item) {
				$operand = $list ? $item : $item->value;
				$conversion = $list ? $value->element_conversion($i) : $item->conversion;
				foreach ($value_type->conversion_headers($conversion) as $header) $required_headers[$header] = true;
				$steps = [];
				if (!$list) {
					[$key, $steps] = $this->staged_operand($item->key, $global_references, $declarations, $required_headers);
					$key = $item->key->type_def()->lower_cpp_exact_argument($item->key, value_conversion::identity, $key);
					$steps[] = "const auto key = $key;";
				}
				[$input, $before] = $this->staged_operand($operand, $global_references, $declarations, $required_headers);
				array_push($steps, ...$before);
				$input = $value_type->lower_cpp_exact_argument($operand, $conversion, $input, $this->inline_adapters);
				$steps[] = $list ? "$box.append($input);" : "$box.set(key, $input);";
				$entry = '{ ' . implode(' ', $steps) . ' } ';
				if ($value->suspends) $this->async_steps[] = $entry;
				else $code .= $entry;
			}
			if ($value->suspends) {
				++$this->async_owned_steps;
				return 'static_cast<' . $value_type->cpp_type() . "&&>($box)";
			}
			return '([&]() { ' . $code . 'return boxed; }())';
		}
		if (($value instanceof ast_keyed_collection_expression || $value instanceof ast_collection_expression) && $value->suspends) {
			return $this->suspending_collection($value, $global_references, $declarations, $required_headers);
		}
		if ($value instanceof ast_keyed_collection_expression) {
			$type = $value->type_def();
			if ($value->entries === []) return $type->lower_cpp_empty();
			/** @var type_definition&keyed_initializer_type $type */
			$elements = [];
			foreach ($value->entries as $entry) {
				$key = $this->value($entry->key, $global_references, $declarations, $required_headers);
				$operand = $this->value($entry->value, $global_references, $declarations, $required_headers);
				$conversion = $entry->conversion ?? throw new \LogicException('Unprepared keyed entry');
				foreach ($type->write_type()->conversion_headers($conversion) as $header) $required_headers[$header] = true;
				$elements[] = '{' . $type->lower_cpp_key($entry->key, $key) . ', '
					. $type->write_type()->lower_cpp_exact_argument($entry->value, $conversion, $operand, $this->inline_adapters) . '}';
			}
			return Cpp_List_Initialization::expression($type->keyed_initialization(), $elements);
		}
		if ($value instanceof ast_collection_expression) {
			/** @var indexed_collection_type $type */
			$type = $value->type_def();
			if ($value->elements === []) return $type->lower_cpp_empty();
			if ($type instanceof fixed_array_type) {
				$items = [];
				foreach ($value->elements as $i => $element) {
					$operand = $this->value($element, $global_references, $declarations, $required_headers);
					$conversion = $value->element_conversion($i);
					foreach ($type->element->conversion_headers($conversion) as $header) $required_headers[$header] = true;
					$items[] = $type->element instanceof fixed_array_type ? $operand
						: $type->element->lower_cpp_exact_argument($element, $conversion, $operand, $this->inline_adapters);
				}
				return '{' . implode(', ', $items) . '}';
			}
			if (!($type instanceof sequence_initializer_type)) throw new \LogicException('Unprepared sequence initializer');
			if ($type instanceof application_collection_type) $this->concrete_collections->request($type);
			$binding = $type->list_initialization();
			$elements = [];
			$code = $binding === null ? '[&]() { ' . $type->cpp_type() . ' sequence = ' . $type->lower_cpp_empty() . '; ' : '';
			foreach ($value->elements as $position => $element) {
				$operand = $this->value($element, $global_references, $declarations, $required_headers);
				$conversion = $value->element_conversion($position);
				foreach ($type->write_type()->conversion_headers($conversion) as $header) $required_headers[$header] = true;
				if ($binding !== null) {
					$elements[] = $type->write_type()->lower_cpp_exact_argument($element, $conversion, $operand, $this->inline_adapters);
				} else $code .= $type->lower_cpp_sequence_append($element, $conversion, 'sequence', $operand, $this->inline_adapters) . '; ';
			}
			return $binding !== null ? Cpp_List_Initialization::expression($binding, $elements) : $code . 'return sequence; }()';
		}
		if ($value instanceof ast_index_expression) {
			/** @var index_read_type $type */
			$type = $value->receiver->type_def();
			$field = $value->receiver;
			if ($this->byte_view !== null && $field instanceof ast_field_expression
				&& $field->field === $this->byte_view->field && $field->receiver instanceof ast_name_expression
				&& $field->receiver->refers_to($this->byte_view->receiver->local_def())) {
				return 'scpp_byte_view.read(' . $this->value($value->index, $global_references, $declarations, $required_headers) . ')';
			}
			$before = $this->suspensions;
			[$index, $key_steps] = $this->staged_operand($value->index, $global_references, $declarations, $required_headers);
			if ($before !== $this->suspensions) {
				$steps = [];
				$receiver = 'indexed_receiver_' . $value->start . '_' . $value->end;
				$place = $this->retained_place($field, $global_references, $declarations, $required_headers, $steps, owner_name: $receiver . '_owner');
				$steps[] = 'auto&& ' . $receiver . ' = ' . $place . ';';
				array_push($this->async_steps, ...$steps, ...$key_steps);
			} else $receiver = $this->value($field, $global_references, $declarations, $required_headers);
			return $type->lower_cpp_index($value->index, $receiver, $index);
		}
		if ($value instanceof ast_interpolated_string) {
			$type = $value->type_def();
			$code = '';
			foreach ($value->parts as $part) {
				$operand = $this->value($part, $global_references, $declarations, $required_headers);
				if ($part instanceof ast_interpolation_name) {
					$conversion = $part->conversion();
					foreach ($type->scalar_conversion_headers($conversion) as $header) {
						$required_headers[$header] = true;
					}
					$operand = $type->lower_scalar_conversion($conversion, $operand);
				}
				if ($code === '') {
					if (count($value->parts) === 1) {
						return $type->cpp_type() . '(' . $operand . ')';
					}
					$code = '([&]() { ' . $type->cpp_type() . ' result = ' . $operand . ';';
				}
				else {
					$code .= ' result.append(' . $operand . ');';
				}
			}
			return $code . ' return result; }())';
		}
		if ($value instanceof ast_cast_expression) {
			$type = $value->type_def();
			$conversion = $value->conversion();
			$operand_safe = false;
			$operand = $this->value($value->operand, $global_references, $declarations, $required_headers, $operand_safe);
			foreach (array_merge($type->cpp_headers(), $value->operand->type_def()->cpp_headers(),
				$type->conversion_headers($conversion)) as $header) {
				$required_headers[$header] = true;
			}
			$reorderable = $operand_safe && $type->cpp_trivial_value() && ($conversion === value_conversion::identity);
			return '(' . $this->conversion($type, $conversion, $operand, $value->operand) . ')';
		}
		if ($value instanceof ast_unary_expression) {
			$operand_safe = false;
			$operand = $this->value($value->operand, $global_references, $declarations, $required_headers, $operand_safe);
			$type = $value->operand->value_type();
			if ($value->operand->type_def() instanceof carrier_type_definition) {
				$operand = $value->operand->type_def()->lower_cpp_extract($operand);
				$operand_safe = false;
			}
			$reorderable = $operand_safe && $type->cpp_unary_reorderable($value->operator);
			return $type->lower_cpp_unary($value->operator, $operand);
		}
		if ($value instanceof ast_isset_expression) {
			if ($value->operand instanceof ast_index_expression && $value->operand->type_def() instanceof owned_callable_type) {
				return $this->indexed_callable_presence($value, $global_references, $declarations, $required_headers);
			}
			/** @var presence_test_type $type */
			$type = $value->operand->type_def();
			return $type->lower_cpp_presence($this->value($value->operand, $global_references, $declarations, $required_headers));
		}
		if ($value instanceof ast_not_expression) {
			$expression = '!' . $this->condition($value->operand, $global_references, $declarations, $required_headers);
			return $logical_root ? $expression : '(' . $expression . ')';
		}
		if ($value instanceof ast_comparison_chain) {
			$steps = '';
			$result = 'chain_result_' . $value->start . '_' . $value->end;
			$middle = $this->chain_operand($value->links[0]->left, 0, $steps, $global_references, $declarations, $required_headers);
			$last = array_key_last($value->links);
			foreach ($value->links as $index => $link) {
				$left = $middle;
				$membership = $link->operator->membership();
				$middle = !$value->suspends && $membership && $index === $last
					? $this->value($link->right, $global_references, $declarations, $required_headers)
					: $this->chain_operand($link->right, $index + 1, $steps, $global_references, $declarations, $required_headers);
				$right = $middle;
				if ($membership) {
					$test = $this->membership_test($link, $left, $right, $required_headers, true);
				} else {
					$this->binary_operands($link, $left, $right, $required_headers);
					$test = $link->execution_type()->lower_prepared_binary($link, $left, $right);
				}
				if ($value->suspends) {
					$steps .= "$result = $test; ";
					if ($index !== $last) $steps .= "if (!$result) break; ";
					continue;
				}
				$steps .= $index === $last ? 'return ' . $test . ';' : 'if (!(' . $test . ')) return false; ';
			}
			if ($value->suspends) {
				$this->async_steps[] = "bool $result; do { $steps } while (false);";
				return $result;
			}
			return '([&]() -> bool { ' . $steps . ' }())';
		}
		if ($value instanceof ast_key_comparison && $value->tests_key()) {
			$index = $value->left;
			$receiver = $this->value($index->receiver, $global_references, $declarations, $required_headers);
			$key_safe = false;
			$key = $this->value($index->index, $global_references, $declarations, $required_headers, $key_safe);
			$type = $index->receiver->type_def();
			// Capture the boxed value before a key call can replace its source storage.
			if (!$key_safe) $receiver = $type->cpp_type() . '(' . $receiver . ')';
			$test = $type->lower_cpp_key_presence($receiver, $key);
			return $value->operator === binary_operator::identical ? '(!' . $test . ')' : $test;
		}
		if ($value instanceof ast_binary_expression) {
			if ($value->operator === binary_operator::coalesce) {
				/** @var carrier_type_definition $carrier */
				$carrier = $value->left->type_def();
				$before = $this->suspensions;
				$start = count($this->async_steps);
				$steps = [];
				$left = $this->retained_place($value->left, $global_references, $declarations, $required_headers, $steps);
				$steps = [...array_splice($this->async_steps, $start), ...$steps, 'const auto& coalesce_value = ' . $left . ';'];
				$presence = $carrier->lower_cpp_presence('coalesce_value');
				$present = $carrier->lower_cpp_extract('coalesce_value');
				$throws = $value->right instanceof ast_throw_expression;
				$fallback = $throws ? $this->throw_call($value->right, $global_references, $declarations, $required_headers)
					: $this->value($value->right, $global_references, $declarations, $required_headers, inside_scope: true);
				if ($before === $this->suspensions) {
					return '([&]() -> ' . $this->signature_type($value_type, false) . ' { ' . implode(' ', $steps)
						. " if ($presence) { return $present; } " . ($throws ? '' : 'return ') . $fallback . '; }())';
				}
				$absent = array_splice($this->async_steps, $start);
				$result = $destination ?? new Cpp_Staged_Result($value_type, 'coalesced_' . $value->start . '_' . $value->end);
				if ($destination === null) $this->async_steps[] = $result->declaration($required_headers);
				$absent[] = $throws ? $fallback . ';' : $result->initialize($fallback);
				$steps[] = "if ($presence) { " . $result->initialize($present) . ' } else { ' . implode(' ', $absent) . ' }';
				$this->async_steps[] = '{ ' . implode(' ', $steps) . ' }';
				if (!$value_type->cpp_trivial_value()) ++$this->async_owned_steps;
				return $destination === null ? $result->operand() : '';
			}
			$before = $this->suspensions;
			if ($value->operator->logical()) {
				$left = $this->condition($value->left, $global_references, $declarations, $required_headers);
				if ($this->async_body) {
					[$right, $rhs] = $this->staged_operand($value->right, $global_references, $declarations, $required_headers, condition: true);
					if ($before !== $this->suspensions && (!$value->operator->lazy() || $rhs !== [])) {
						$temp = 'logical_' . $value->start . '_' . $value->end;
						$this->async_steps[] = "bool $temp = $left;";
						if ($value->operator->lazy()) {
							$guard = $value->operator === binary_operator::logical_and ? $temp : '!' . $temp;
							$this->async_steps[] = "if ($guard) { " . implode(' ', $rhs) . " $temp = $right; }";
							return $temp;
						}
						array_push($this->async_steps, ...$rhs);
						return "($temp != ($right))";
					}
				} else $right = $this->condition($value->right, $global_references, $declarations, $required_headers);
				if (!$value->operator->lazy()) {
					if ($value->left instanceof ast_name_expression && $value->right instanceof ast_name_expression
						&& $value->left->local_binding() !== null && $value->right->local_binding() !== null
						&& $value->left->type_def() === type_bool::instance() && $value->right->type_def() === type_bool::instance()
						&& $left !== $right) {
						return '(' . $left . ' != ' . $right . ')';
					}
					return '([&]() -> bool { const bool logical_left = ' . $left
						. '; const bool logical_right = ' . $right . '; return logical_left != logical_right; }())';
				}
				$expression = $left . ($value->operator === binary_operator::logical_and ? ' && ' : ' || ') . $right;
				return $logical_root ? $expression : '(' . $expression . ')';
			}
			$type = $value->execution_type();
			$left_safe = false;
			$right_safe = false;
			$left = $this->value($value->left, $global_references, $declarations, $required_headers, $left_safe);
			$right_start = count($this->async_steps);
			$right = $this->value($value->right, $global_references, $declarations, $required_headers, $right_safe);
			if ($value->operator->membership() && $before === $this->suspensions) {
				return $this->membership_test($value, $left, $right, $required_headers, false);
			}
			$this->binary_operands($value, $left, $right, $required_headers);
			if ($before !== $this->suspensions) {
				$rhs = array_splice($this->async_steps, $right_start);
				if (!$value->left->type_def()->cpp_trivial_value() || !$value->right->type_def()->cpp_trivial_value()
					|| !$type->cpp_trivial_value()) ++$this->async_owned_steps;
				$prefix = 'operand_' . $value->start . '_' . $value->end;
				$this->async_steps[] = "[[maybe_unused]] const auto {$prefix}_left = $left;";
				array_push($this->async_steps, ...$rhs);
				$this->async_steps[] = "[[maybe_unused]] const auto {$prefix}_right = $right;";
				if ($value->operator->membership()) return $this->membership_test($value, $prefix . '_left', $prefix . '_right', $required_headers, true);
				return $type->lower_prepared_binary($value, $prefix . '_left', $prefix . '_right', $typed_initializer, $bare_comparison);
			}
			$same_spelling = $left === $right;
			$operands_safe = $left_safe && $right_safe;
			$native = $type->cpp_binary_reorderable($value->operator, $same_spelling);
			$reorderable = $operands_safe && $native;
			// An integer literal cannot observe a sibling's effects or failure.
			$literal_peer = $value->left instanceof ast_integer_expression || $value->right instanceof ast_integer_expression;
			// Direct spelling does not make a checked result reorderable in its parent.
			if ($reorderable || $type->cpp_value_helper($value->operator) || ($value->left_conversion === value_conversion::identity
				&& $value->right_conversion === value_conversion::identity
				&& (($literal_peer && $native) || $type->cpp_direct_binary($value, $operands_safe, $same_spelling)))) {
				return $type->lower_prepared_binary($value, $left, $right, $typed_initializer, $bare_comparison);
			}
			// Snapshot before RHS effects, including writes through reference parameters. Nested lambdas own their names.
			return '([&]() -> ' . $value->type_def()->cpp_type() . ' { const auto binary_left = ' . $left
				. '; const auto binary_right = ' . $right . '; return '
				. $type->lower_prepared_binary($value, 'binary_left', 'binary_right', $typed_initializer) . '; }())';
		}
		if ($value instanceof ast_lambda_expression) {
			$reorderable = true;
			return $this->lambda($value, $global_references, $declarations, $required_headers);
		}
		if ($value instanceof ast_callable_reference) {
			$function = $value->implementation();
			$name = self::function_name($function);
			$declarations[$name] = $function;
			$reorderable = true;
			return '&' . $name;
		}
		if ($value instanceof ast_call_expression) {
			if ($value->qualifier === call_qualifier::stored_value) {
				$target = $value->receiver;
				$signature = $target->type_def()->signature;
				$this->callable_dependencies($signature, $declarations);
				$operand = $this->value($target, $global_references, $declarations, $required_headers);
				$call = $this->invocation($value, $signature, $operand, $value->type_def(), $global_references, $declarations, $required_headers);
				return $signature->reference_origin !== null && !$borrow_result ? 'static_cast<' . $value->type_def()->cpp_type() . '>(' . $call . ')' : $call;
			}
			$callback = $value->callable_binding();
			if ($callback !== null) {
				$type = $callback->type_def();
				$target = $type instanceof lambda_type_definition ? $callback->initializer : ($type instanceof owned_callable_type ? $type->signature : $type);
				$call = $this->invocation($value, $target, $this->name($callback), $value->type_def(), $global_references, $declarations, $required_headers, statement_setup: $statement_setup);
				$reference = $target instanceof ast_lambda_expression ? $target->returns_reference : $target->reference_origin !== null;
				return $reference && !$borrow_result
					? 'static_cast<' . $value->type_def()->cpp_type() . '>(' . $call . ')' : $call;
			}
			if ($value->mt_operation === 'mt_join') return $this->joined_batch($value, $global_references, $declarations, $required_headers);
			if ($value->cardinality !== null) {
				$receiver = $value->arguments[0];
				/** @var counted_collection_type $type */
				$type = $receiver->type_def();
				return $type->lower_cpp_cardinality($value->cardinality,
					$this->value($receiver, $global_references, $declarations, $required_headers));
			}
			$function = $value->implementation();
			if ($function instanceof runtime_function) {
				if ($value_type instanceof application_collection_type) $this->concrete_collections->request($value_type);
				if ($function instanceof collection_transform_function) {
					return $this->collection_transform($value, $function, $global_references, $declarations, $required_headers);
				}
				$use = match ($function->name()) {
					'enum_from_value' => dependency_use::enum_from_value,
					'enum_try_from_value' => dependency_use::enum_try_from_value,
					'enum_name' => dependency_use::enum_name,
					'enum_value' => $value->type_def() === type_string::instance() ? dependency_use::enum_value : null,
					default => null,
				};
				if ($use !== null) {
					$type = match ($use) {
						dependency_use::enum_from_value => $value_type,
						dependency_use::enum_try_from_value => $value_type->payload_type,
						default => $value->arguments[0]->type_def(),
					};
					$key = $type->declaration->symbol_key();
					$this->enum_helpers[$key] = ($this->enum_helpers[$key] ?? 0) | $use->value;
				}
				$required_headers[$function->header] = true;
				$symbol = $function->symbol;
				if ($function->adapter_flavor !== null) {
					// A contributed body can use a different flavor from its C++ translation unit.
					$foreign = $function->adapter_flavor !== $value->root()->tokens->source->flavor->value;
					if (!$foreign) $this->adapter_flavor = $function->adapter_flavor;
					$short = substr($symbol, strrpos($symbol, '::') + 2);
					// Generated app declarations and locals are encoded. A namespace component can still hide the spelling.
					$symbol = $foreign || $this->qualified_adapters || in_array($short, explode('::', $this->root), true) ? '::' . $symbol : $short;
				}
				if ($value->suspending_operands || $value instanceof ast_named_call) {
					return $this->invocation($value, $function, $symbol, $function->return_type_def(), $global_references, $declarations, $required_headers, statement_setup: $statement_setup);
				}
				$operands = $value->runtime_operands();
				if ($function->receiver_method) {
					$arguments = [];
					$direct = true;
					foreach (array_slice($operands, 1) as $index => $argument) {
						$safe = false;
						$operand = $this->value($argument, $global_references, $declarations, $required_headers, $safe);
						$arguments[] = $function->parameters[$index + 1]->lower_cpp_exact_argument($argument, value_conversion::identity, $operand);
						$direct = $direct && ($safe || $argument instanceof ast_string_expression
							|| ($argument instanceof ast_name_expression && $argument->local_binding() !== null));
					}
					$steps = [];
					$receiver = $direct
						? $this->value($operands[0], $global_references, $declarations, $required_headers)
						: $this->retained_place($operands[0], $global_references, $declarations, $required_headers, $steps);
					if ($steps !== []) {
						// Retain the selected shared allocation if argument evaluation can replace its owner.
						$steps[] = 'auto&& method_receiver = ' . $receiver . ';';
						$receiver = 'method_receiver';
					}
					$access = $function->parameters[0] instanceof type_exception
						&& $operands[0]->type_def() instanceof shared_record_type_definition ? '->' : '.';
					$call = '(' . $receiver . ')' . $access . $symbol . '(' . implode(', ', $arguments) . ')';
					return self::staged_call($call, $steps, '', $statement_setup);
				}
				if (count($operands) === 1 && !$function->overloaded_first) {
					$operand = $this->value($operands[0], $global_references, $declarations, $required_headers);
					$conversion = $value->conversion(0);
					if ($conversion !== value_conversion::identity) {
						$type = $function->parameters[0];
						foreach ($type->conversion_headers($conversion) as $header) $required_headers[$header] = true;
						$operand = $this->conversion($type, $conversion, $operand, $operands[0]);
					} elseif ($function->exact_arguments) {
						$operand = $function->parameters[0]->lower_cpp_exact_argument($operands[0], $conversion, $operand, $this->inline_adapters);
					}
					return $symbol . '(' . $operand . ')';
				}
				$arguments = [];
				$direct = true;
				$capture_indices = [];
				$inline_index = null;
				$records_before = $this->record_expressions;
				foreach ($operands as $index => $argument) {
					$operand_records_before = $this->record_expressions;
					$safe = false;
					$operand = $this->value($argument, $global_references, $declarations, $required_headers, $safe);
					if ($function->exact_arguments) $operand = $function->parameters[$index]->lower_cpp_exact_argument($argument, value_conversion::identity, $operand, $this->inline_adapters);
					$arguments[] = $index === 0 && $function->overloaded_first
						? 'static_cast<' . $function->parameters[0]->cpp_type() . '>(' . $operand . ')' : $operand;
					// A prepared writable reference names stable storage; binding it does not read its value.
					if (!$function->is_reference($index) && !self::inert_literal_argument(
						$argument, $function->parameters[$index], value_conversion::identity)) {
						$capture_indices[] = $index;
						// Only the last order-sensitive operand can execute after the earlier captures.
						$type = $function->parameters[$index];
						$can_bind = $type->cpp_trivial_value() || ($function->is_const_reference($index)
							&& $type instanceof type_string && $argument instanceof ast_call_expression);
						$inline_index = $can_bind && $this->record_expressions === $operand_records_before ? $index : null;
					}
					// Reading a local string or literal cannot mutate another argument.
					$direct = $direct && ($safe || $argument instanceof ast_string_expression
						|| ($argument instanceof ast_name_expression && $argument->local_binding() !== null));
				}
				$steps = [];
				$direct = $direct || (count($capture_indices) <= 1 && $this->record_expressions === $records_before);
				if (!$direct && count($arguments) > 1) {
					foreach ($capture_indices as $index) {
						if ($index === $inline_index) break;
						$operand = $arguments[$index];
						$arguments[$index] = 'argument_' . $value->start . '_' . $index;
						$steps[] = 'const auto ' . $arguments[$index] . ' = ' . $operand . ';';
					}
				}
				if ($function->full_arity_argument !== null && count($arguments) === count($function->parameters)) {
					$arguments[] = $function->full_arity_argument;
				}
				$call = $function instanceof regex_callback_function
					? $this->inline_adapters->call($function->adapter(count($arguments)), $arguments)
					: $symbol . '(' . implode(', ', $arguments) . ')';
				return self::staged_call($call, $steps, '', $statement_setup);
			}
			$name = self::function_name($function);
			$declarations[$name] = $function;
			$result = $function->return_type_def();
			// The value read through a returned reference follows argument cleanup.
			if ($function->returns_reference) $statement_setup = null;
			$call = $this->invocation($value, $function, $name, $result, $global_references, $declarations, $required_headers, statement_setup: $statement_setup);
			return $function->returns_reference && !$borrow_result ? 'static_cast<' . $result->cpp_type() . '>(' . $call . ')' : $call;
		}
		if (($value instanceof ast_integer_expression) || ($value instanceof ast_float_expression)
			|| ($value instanceof ast_keyword_expression) || ($value instanceof ast_string_expression)) {
			$reorderable = $value->type_def()->cpp_trivial_value();
			return $value->type_def()->lower_cpp_literal($value);
		}
		if ($value instanceof ast_name_expression) {
			$member = $this->name_value($value, $global_references, $declarations, $required_headers, $reorderable);
			if (!($value instanceof ast_qualified_data)) return $member;
			$reorderable = false;
			$steps = [];
			$before = $this->suspensions;
			$member = $this->qualified_operand($value->receiver, $member, $steps, $global_references, $declarations, $required_headers);
			if ($before !== $this->suspensions) {
				array_push($this->async_steps, ...$steps);
				return $member;
			}
			return $steps === [] ? $member : '([&]() -> decltype(auto) { ' . implode(' ', $steps) . ' return (' . $member . '); }())';
		}
		throw new \LogicException('Unhandled prepared value');
	}

	/**
	 * Emit the bound declaration independently of its written qualifier.
	 * @param ast_global_declaration[] $global_references
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $required_headers
	 */
	private function name_value(ast_name_expression $value, array &$global_references /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$required_headers /** array<string,bool> */,
		bool &$reorderable = false): string
	{
		$declaration = $value->declaration();
		$reorderable = !($declaration instanceof ast_writable_element) && $value->type_def()->cpp_trivial_value();
		if ($declaration instanceof ast_enum_case) {
			return $declaration->parent->definition->cpp_case($declaration);
		}
		if ($declaration instanceof runtime_integer_constant) {
			return $declaration->value . 'LL';
		}
		if ($declaration instanceof ast_computed_integer_constant) return self::integer_spelling($declaration->constant());
		if ($declaration instanceof ast_constant_declaration) {
			$type = $declaration->type_def();
			$conversion = $declaration->initialization();
			$reorderable = $reorderable && ($conversion === value_conversion::identity);
			$literal = $this->value($declaration->initializer, $global_references, $declarations, $required_headers);
			foreach (array_merge($type->cpp_headers(), $declaration->initializer->type_def()->cpp_headers(),
				$type->conversion_headers($conversion)) as $header) {
				$required_headers[$header] = true;
			}
			return $this->conversion($type, $conversion, $literal, $declaration->initializer);
		}
		if ($declaration instanceof ast_global_declaration) {
			$name = self::global_name($declaration);
			$global_references[$name] = $declaration;
			return '::' . ($this->root === '' ? '' : $this->root . '::') . $name;
		}
		$name = $this->name($declaration);
		$type = $declaration->type_def();
		if ($type instanceof class_union_type_definition && ($pointer = $this->union_pointer($value, $declaration, $declarations)) !== null) {
			return $this->inline_adapters->call(Cpp_Object_Access::guarded_owner($type, $value->type_def()), [$name, '*' . $pointer]);
		}
		if ($type instanceof intersection_type_definition && $value->type_def() !== $type) {
			if ($value->type_def() === $type->base) return $name . '.base';
			return $this->inline_adapters->call($type->project($type->capability, value_conversion::intersection_capability), [$name]);
		}
		return $name;
	}

	/** Project a prepared union read through the successful guard or its proven complement. @param ast_declaration_interface[] $declarations */
	private function union_pointer(ast_name_expression $value, ast_local_binding $local, array &$declarations /** array<string,ast_declaration_interface> */): ?string
	{
		if (($guard = $value->guard()) !== null) return 'scpp_guard_' . $guard->start;
		$arm = $value->type_def();
		if (!($arm instanceof shared_record_type_definition) || $arm === $local->type_def()->base) return null;
		$record = $arm->payload->declaration;
		$declarations[$record->symbol_key()] = $record;
		return 'static_cast<' . $arm->payload->cpp_type() . '*>(' . $this->name($local) . '.view)';
	}

	/**
	 * End an effectful qualifier's full expression before the selected read or call.
	 * @param string[] $steps
	 * @param ast_global_declaration[] $globals
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $headers
	 */
	private function qualified_operand(ast_expression $receiver, string $operand, array &$steps /** list<string> */,
		array &$globals /** array<string,ast_global_declaration> */, array &$declarations /** array<string,ast_function_declaration> */,
		array &$headers /** array<string,bool> */): string
	{
		$discard = $this->discard_qualifier($receiver, $globals, $declarations, $headers);
		if (($receiver instanceof ast_name_expression && !($receiver instanceof ast_qualified_data)) || $receiver instanceof ast_this_expression) {
			return '(' . $discard . ', ' . $operand . ')';
		}
		$steps[] = $discard . ';';
		return $operand;
	}

	/**
	 * @param ast_global_declaration[] $globals
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $headers
	 */
	private function discard_qualifier(ast_expression $receiver, array &$globals /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$headers /** array<string,bool> */): string
	{
		return 'static_cast<void>(' . $this->value($receiver, $globals, $declarations, $headers) . ')';
	}

	/** Probe each index once, evaluating later keys only inside successful outer lookups.
	 * @param ast_global_declaration[] $globals @param ast_function_declaration[] $declarations @param bool[] $headers */
	private function indexed_callable_presence(ast_isset_expression $test, array &$globals, array &$declarations, array &$headers): string
	{
		$path = [];
		$base = $test->operand;
		while ($base instanceof ast_index_expression) {
			$path[] = $base;
			$base = $base->receiver;
		}
		$before = $this->suspensions;
		$steps = [];
		$prefix = 'probe_' . $test->start . '_';
		$place = $this->retained_place($base, $globals, $declarations, $headers, $steps, owner_name: $prefix . 'owner');
		$receiver = $prefix . 'base';
		$steps[] = 'const auto& ' . $receiver . ' = ' . $place . ';';
		$result = $prefix . 'present';
		$steps[] = 'bool ' . $result . ' = false;';
		foreach (array_reverse($path) as $i => $access) {
			[$key, $staged] = $this->staged_operand($access->index, $globals, $declarations, $headers);
			array_push($steps, ...$staged);
			$index = $prefix . 'key_' . $i;
			$steps[] = 'const auto ' . $index . ' = ' . $access->receiver->type_def()->lower_cpp_key($access->index, $key) . ';';
			$steps[] = 'if (' . $receiver . '.has(' . $index . ')) {';
			$read = $receiver . '.at(' . $index . ')';
			$receiver = $prefix . 'item_' . $i;
			if ($access === $test->operand) $steps[] = $result . ' = ' . $access->type_def()->lower_cpp_presence($read) . ';';
			else $steps[] = 'const auto& ' . $receiver . ' = ' . $read . ';';
		}
		$steps[] = str_repeat('}', count($path));
		if ($before !== $this->suspensions) {
			array_push($this->async_steps, ...$steps);
			return $result;
		}
		return '([&]() { ' . implode(' ', $steps) . ' return ' . $result . '; }())';
	}

	/**
	 * Select a receiver or writable field and retain its nearest enclosing allocation across operand effects.
	 * @param ast_global_declaration[] $globals
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $headers
	 * @param string[] $steps
	 */
	private function retained_place(ast_expression $receiver, array &$globals /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$headers /** array<string,bool> */,
		array &$steps /** list<string> */, ?bool &$inline_storage = null, string $owner_name = 'receiver_owner', bool $full_expression = false): string
	{
		if ($receiver instanceof ast_index_expression && $receiver->receiver->type_def() instanceof fixed_array_type) {
			$type = $receiver->receiver->type_def();
			$place = $this->retained_place($receiver->receiver, $globals, $declarations, $headers, $steps);
			$key = $this->value($receiver->index, $globals, $declarations, $headers);
			return $type->lower_cpp_index($receiver->index, $place, $key);
		}
		$fields = [];
		$base = $receiver;
		while ($base instanceof ast_field_expression && !($base->type_def() instanceof shared_record_type_definition)
			&& !($base->receiver->type_def() instanceof runtime_handle_type)) {
			$fields[] = Cpp_Identifier::source($base->field);
			$base = $base->receiver;
		}
		// Inline fields share the storage duration of their root. A temporary record
		// still needs the named receiver binding for the non-const reference parameter.
		$inline_storage = $base->type_def() instanceof record_type_definition
			&& ($base instanceof ast_this_expression || ($base instanceof ast_name_expression
				&& $base->local_binding() !== null));
		$operand = $this->value($base, $globals, $declarations, $headers);
		if ($base->type_def() instanceof shared_record_type_definition) {
			$owner = $owner_name;
			if ($full_expression) {
				// Keep the payload alive through the final index or array copy in this expression.
				$owner = $base->type_def()->cpp_type() . '(' . $operand . ')';
			} else {
				$steps[] = 'auto ' . $owner_name . ' = ' . $operand . ';';
			}
			if ($fields === []) return '*' . $owner . '.operator->()';
			$operand = $owner . '->' . array_pop($fields);
		}
		foreach (array_reverse($fields) as $field) $operand .= '.' . $field;
		return $operand;
	}

	/**
	 * Emit one prepared source invocation with the ordinary conversion and left-to-right argument rules.
	 * @param ast_global_declaration[] $global_references
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $required_headers
	 * @param list<string>|null $statement_setup
	 */
	private function invocation(ast_call_expression|ast_construction_expression $value, ast_function_declaration|ast_lambda_expression|callback_type_definition|runtime_function $function,
		string $name, type_definition $result, array &$global_references /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$required_headers /** array<string,bool> */, ?string $native_result = null, ?array &$statement_setup = null): string
	{
		$steps = [];
		$arguments = [];
		$callback = $function instanceof callback_type_definition;
		$runtime = $function instanceof runtime_function;
		$operands = $runtime && $value instanceof ast_call_expression ? $value->runtime_operands() : $value->arguments;
		$parameters = $callback ? $function->inputs : ($runtime ? array_slice($function->parameters, 0, count($operands)) : $function->parameters);
		$variadic = $callback ? $function->variadic_slot : ($runtime ? null : $function->variadic_slot());
		// An unpacked operand already supplies the native by-value vector parameter.
		if ($value->unpacked ?? false) $variadic = null;
		if ($variadic !== null) {
			$last = array_pop($parameters);
			$pack = $callback ? $last : $last->type_def();
		}
		if (isset($value->named)) {
			$parameters = $value->named->evaluation_parameters($parameters);
		}
		$before = $this->suspensions;
		$suspending = $value instanceof ast_call_expression && $value->suspending_operands;
		$suffix = $suspending ? '_' . $value->start . '_' . $value->end : '';
		$receiver_reorderable = true;
		$method = $runtime ? $function->receiver_method : $function instanceof ast_function_declaration && $value instanceof ast_call_expression && $function->has_receiver() && !$function->is_constructor();
		if (!$runtime && $value instanceof ast_call_expression && $value->expression_qualified()) {
			$name = $this->qualified_operand($value->receiver, $name, $steps, $global_references, $declarations, $required_headers);
		}
		if ($callback && $value->qualifier === call_qualifier::stored_value) {
			$selected = 'selected_callable_' . $value->start;
			$steps[] = 'const auto ' . $selected . ' = ' . $name . ';';
			$name = $selected;
		} elseif ($callback && $value->callable_binding()->type_def() instanceof owned_callable_type) {
			foreach ($operands as $index => $argument) {
				if ($argument instanceof ast_name_expression || self::inert_literal_argument($argument, $parameters[$index] ?? $pack->element_type, $value->conversion($index))) continue;
				// An argument can replace the slot through a closure that borrows it.
				$selected = 'selected_callable_' . $value->start;
				$steps[] = 'const auto ' . $selected . ' = ' . $name . ';';
				$name = $selected;
				break;
			}
		}
		if ($runtime && $method) {
			$receiver = $this->retained_place($operands[0], $global_references, $declarations, $required_headers, $steps, owner_name: 'receiver_owner' . $suffix);
			$place = 'method_receiver' . $suffix;
			$steps[] = 'auto&& ' . $place . ' = ' . $receiver . ';';
			$name = $place . '.' . $name;
			unset($operands[0], $parameters[0]);
		} elseif ($method && $function->parent instanceof ast_enum_declaration) {
			// Enum receivers are values; capture only when argument evaluation cannot be emitted directly.
			$receiver = $this->value($value->receiver, $global_references, $declarations, $required_headers, $receiver_reorderable);
			if ($function->parameters === []) return $name . '(' . $receiver . ')';
			$arguments[] = $receiver;
		} elseif ($method) {
			$inline_storage = $value->ancestor_dispatch;
			$place = $value->ancestor_dispatch ? 'scpp_self'
				: $this->retained_place($value->receiver, $global_references, $declarations, $required_headers, $steps, $inline_storage, 'receiver_owner' . $suffix);
			if (!$inline_storage) {
				$steps[] = 'auto&& receiver_payload' . $suffix . ' = ' . $place . ';';
				$place = 'receiver_payload' . $suffix;
			}
			if ($function->has_virtual_dispatch() || $value->interface_dispatch) $name = '(' . $place . ').'
				. ($value->ancestor_dispatch ? $function->parent->definition->cpp_type() . '::' : '') . self::virtual_name($function);
			else $arguments[] = $place;
		} elseif ($variadic === null && $parameters === [] && $steps === []) {
			return $name . '()';
		}
		$direct_arguments = [];
		$direct = $steps === [] && $receiver_reorderable;
		$has_reference = false;
		$order_sensitive = [];
		$inline_index = null;
		if ($suspending && $steps !== []) ++$this->async_owned_steps;
		$argument_steps = [];
		$constructed_arguments = [];
		foreach ($parameters as $index => $parameter) {
			$argument = $operands[$index] ?? $parameter->default_value;
			$conversion = isset($operands[$index]) ? $value->conversion($index) : $parameter->default_conversion();
			$type = $callback || $runtime ? $parameter : $parameter->type_def();
			$by_value = $runtime ? !$function->is_reference($value->named->slots[$index] ?? $index) : ($callback ? $function->input_mode($index) : $parameter->mode) === passing_mode::value;
			$operand_safe = false;
			$records_before = $this->record_expressions;
			$step_start = count($this->async_steps);
			$destination = $this->async_body && count($parameters) > 1 && $by_value
				&& !$type->cpp_trivial_value() && $conversion === value_conversion::identity && $argument->type_def() === $type
				? new Cpp_Staged_Result($type, 'argument_' . $value->start . '_' . $index) : null;
			$operand = $by_value ? $this->value($argument, $global_references, $declarations, $required_headers, $operand_safe,
				logical_root: $conversion === value_conversion::identity, destination: $destination) : $this->name($argument->local_def());
			if (($variadic !== null || count($parameters) > 1 || $method || $steps !== []) && count($this->async_steps) !== $step_start) {
				$argument_steps[$index] = array_splice($this->async_steps, $step_start);
				if ($operand === '' && $destination !== null) {
					array_unshift($argument_steps[$index], $destination->declaration($required_headers));
					$operand = $destination->operand();
					$constructed_arguments[$index] = true;
				}
			}
			foreach ($type->conversion_headers($conversion) as $header) {
				$required_headers[$header] = true;
			}
			if ($by_value) {
				$operand = $runtime
					? $type->lower_cpp_exact_argument($argument, $conversion, $operand, $this->inline_adapters)
					: $this->conversion($type, $conversion, $operand, $argument);
			}
			// Preparation already validated stable, exact storage for a reference parameter.
			if ($variadic === null && !$method && $steps === [] && count($parameters) === 1) {
				$tag = $value instanceof ast_struct_default && self::self_value_constructor($function) ? '0, ' : '';
				return $name . '(' . $tag . $operand . ')';
			}
			$direct_arguments[$index] = $operand;
			$direct = $direct && $operand_safe && $type->cpp_trivial_value()
				&& ($conversion === value_conversion::identity) && $by_value;
			// A prepared reference binds a stable named slot without reading its value.
			if (!$by_value) {
				$has_reference = true;
			} elseif (!self::inert_literal_argument($argument, $type, $conversion)) {
				$order_sensitive[$index] = true;
				$inline_index = $type->cpp_trivial_value() && $this->record_expressions === $records_before ? $index : null;
			}
		}
		if ($method && !$runtime && $function->parent instanceof ast_enum_declaration && !$direct && $order_sensitive !== []) {
			$steps[] = 'const auto enum_receiver' . $suffix . ' = ' . $arguments[0] . ';';
			$arguments[0] = 'enum_receiver' . $suffix;
			$direct = false;
		}
		if ($variadic === null && $argument_steps === [] && ($direct || ($steps === [] && !$method && $value instanceof ast_call_expression && (!($function instanceof ast_function_declaration) || !$function->is_constructor())
			&& !$has_reference && count($order_sensitive) <= 1))) {
			if (isset($value->named)) $direct_arguments = $value->named->parameter_order($direct_arguments);
			return $name . '(' . implode(', ', $arguments ? [...$arguments, ...$direct_arguments] : $direct_arguments) . ')';
		}
		// Literal peers cannot observe effects. The last remaining scalar can run in the call
		// after its predecessors, unless its expression needs a record destruction boundary.
		foreach ($parameters as $index => $parameter) {
			$type = $callback || $runtime ? $parameter : $parameter->type_def();
			$operand = $direct_arguments[$index];
			if (isset($argument_steps[$index])) array_push($steps, ...$argument_steps[$index]);
			if (isset($constructed_arguments[$index]) || !isset($order_sensitive[$index]) || ($index === $inline_index && $variadic === null)) {
				continue;
			}
			// Position-derived names can shadow outer lambda snapshots during nested calls; use source positions.
			$temporary = 'argument_' . $value->start . '_' . $index;
			$declaration = $type instanceof callback_type_definition ? $type->cpp_declarator($temporary) : $type->cpp_type() . ' ' . $temporary;
			$steps[] = $declaration . ' = ' . $operand . ';';
			if ($before !== $this->suspensions && !$type->cpp_trivial_value()) ++$this->async_owned_steps;
			$direct_arguments[$index] = $type->cpp_trivial_value() ? $temporary
				: 'static_cast<' . $type->cpp_type() . '&&>(' . $temporary . ')';
		}
		if (isset($value->named)) $direct_arguments = $value->named->parameter_order($direct_arguments);
		array_push($arguments, ...$direct_arguments);
		if ($variadic !== null) {
			$name_pack = $this->variadic_pack($value, $variadic, $pack, $global_references, $declarations, $required_headers, $steps);
			$arguments[] = 'static_cast<' . $pack->cpp_type() . "&&>($name_pack)";
			if ($before !== $this->suspensions) ++$this->async_owned_steps;
		}
		$call = $name . '(' . implode(', ', $arguments) . ')';
		if ($before !== $this->suspensions) {
			array_push($this->async_steps, ...$steps);
			return $call;
		}
		$return_reference = $callback ? $function->result_suffix() : ($function instanceof statement_return_target && $function->returns_reference ? '&' : '');
		if ($steps === []) return $call;
		return self::staged_call($call, $steps, ' -> ' . ($native_result ?? $result->cpp_type() . $return_reference), $statement_setup);
	}

	/**
	 * Consume the selected typed traversal; its operand temporaries live through the entire loop.
	 * @param ast_global_declaration[] $globals
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $headers
	 */
	private function collection_transform(ast_call_expression $call, collection_transform_function $function,
		array &$globals, array &$declarations, array &$headers): string
	{
		[$input, $callback] = $call->arguments;
		$type = $input->type_def();
		$out = $function->return_type_def();
		$result = $out->cpp_type();
		$owner_steps = [];
		$receiver = $this->retained_place($input, $globals, $declarations, $headers, $owner_steps, full_expression: true);
		$callable = $this->value($callback, $globals, $declarations, $headers);
		$boxed = $type instanceof boxed_type_definition;
		$keyed = $boxed || $type instanceof hash_type_definition;
		$key = $keyed ? 'key' : null;
		$output = $type->iteration_output_type();
		$element = $type->iteration_value_type()->cpp_type();
		$item = $output === null ? 'item' : 'item.value';
		$initial = $boxed ? ' = ' . $type->lower_cpp_empty() : '';
		$lines = ["$result result$initial;", ($output ?? $element) . ' item{};'];
		if ($keyed) $lines[] = ($boxed ? 'scpp::boxed_text_key' : $type->key_type->cpp_type()) . ' key{};';
		$lines[] = $type->lower_cpp_iteration_storage($keyed && !$boxed) . $type->lower_cpp_iteration_initialize('source');
		$next = $boxed ? $type->lower_cpp_iteration_next_classified('source', 'item', 'key') : $type->lower_cpp_iteration_next('source', 'item', $key);
		$lines[] = "while ($next) {";
		$value = $function->filter ? "static_cast<$element&&>($item)" : "callback(static_cast<$element&&>($item))";
		$insert = ($keyed ? $out->lower_cpp_write('result', 'key', $value) : $out->lower_cpp_append_value('result', $value)) . ';';
		$lines[] = $function->filter ? "if (callback($item)) $insert" : $insert;
		$lines[] = '} return result;';
		// A selected owned callable survives reassignment of its source slot by the callback.
		return '[](const auto& source, auto callback) { ' . implode(' ', $lines) . ' }(' . $receiver . ', ' . $callable . ')';
	}

	/**
	 * Fill one owned pack in source order, sharing append and conversion lowering with base construction.
	 * @param ast_global_declaration[] $globals
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $headers
	 * @param list<string> $steps
	 */
	private function variadic_pack(ast_call_expression|ast_construction_expression $site, int $start, vector_type_definition $pack,
		array &$globals, array &$declarations, array &$headers, array &$steps): string
	{
		$name = 'pack_' . $site->start . '_' . $site->end;
		$steps[] = $pack->cpp_type() . " $name{};";
		if ($pack instanceof application_collection_type) $this->concrete_collections->request($pack);
		for ($i = $start; $i < count($site->arguments); ++$i) {
			$element = $site->arguments[$i];
			[$operand, $prerequisites] = $this->staged_operand($element, $globals, $declarations, $headers);
			array_push($steps, ...$prerequisites);
			$conversion = $site->conversion($i);
			foreach ($pack->element_type->conversion_headers($conversion) as $header) $headers[$header] = true;
			$steps[] = $pack->lower_cpp_sequence_append($element, $conversion, $name, $operand, $this->inline_adapters) . ';';
		}
		return $name;
	}

	/** Collect declarations for a callable's result and inputs even when the local starts absent. @param (ast_declaration_interface|type_definition)[] $declarations */
	private function callable_dependencies(callback_type_definition $signature, array &$declarations /** array<string,ast_declaration_interface|type_definition> */): void
	{
		self::visit_signature_type_dependencies($signature, function (type_definition $component) use (&$declarations): void {
			if ($component instanceof application_collection_type) $this->concrete_collections->request($component);
			elseif ($component instanceof enum_type_definition || $component instanceof record_type_definition) $declarations[$component->declaration->symbol_key()] = $component->declaration;
			else $declarations[$component->name] = $component;
		});
	}

	/**
	 * Snapshot caller operands, materialize grants with stable storage, then submit one joined batch.
	 * @param ast_global_declaration[] $global_references
	 * @param (ast_declaration_interface|type_definition)[] $declarations
	 * @param bool[] $required_headers
	 */
	private function joined_batch(ast_call_expression $value, array &$global_references /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_declaration_interface|type_definition> */, array &$required_headers /** array<string,bool> */): string
	{
		$required_headers['scpp/mt_segments.hpp'] = true;
		$jobs = [];
		$snapshots = [];
		$lines = [];
		$dynamic = false;
		$entries = [];
		$capped = false;
		foreach ($value->arguments as $launch) {
			if ($launch instanceof ast_inline_job && $launch->iteration !== null) {
				$dynamic = true;
				if ($launch->arguments[0] instanceof ast_job_repetition) $capped = true;
			}
		}
		$cap = '';
		if ($value instanceof ast_parallel_expression && $value->receiver !== null) {
			$expression = $this->value($value->receiver, $global_references, $declarations, $required_headers);
			$required_headers['stdexcept'] = true;
			$snapshots[] = "const auto workers = $expression;";
			$snapshots[] = 'if (workers <= 0) throw std::invalid_argument("Group workers must be positive");';
			$cap = ', workers';
		}
		if ($dynamic) {
			$required_headers['vector'] = true;
			$required_headers['stdexcept'] = true;
			$lines[] = 'std::vector<::scpp::mt::job> jobs;';
		}
		foreach ($value->arguments as $i => $launch) {
			$index = $launch instanceof ast_inline_job && $launch->iteration !== null ? $this->name($launch->iteration) : null;
			if ($launch instanceof ast_inline_job) {
				$name = $this->lambda($launch->receiver, $global_references, $declarations, $required_headers);
			} else {
				$worker = $launch->implementation();
				$name = self::function_name($worker);
				$declarations[$name] = $worker;
				$this->collect_signature_types($worker, $declarations);
			}
			$args = [];
			$forwarded = [];
			$fields = [];
			$initializers = [];
			$scalar_inputs = false;
			foreach ($launch->arguments as $slot => $input) {
				if ($launch instanceof ast_inline_job && in_array($launch->binding_kinds[$slot], ['each', 'value'], true)) {
					$scalar_inputs = true;
					$fields[] = $input->type_def()->cpp_type() . " v$slot;";
					$initializers[] = $launch->binding_kinds[$slot] === 'each' ? $index : $this->value($input, $global_references, $declarations, $required_headers);
					$forwarded[] = "static_cast<const values_$i*>(context)->v$slot";
					continue;
				}
				$operands = [];
				foreach ($input->arguments as $arg) $operands[] = $this->value($arg, $global_references, $declarations, $required_headers);
				$owner = $operands[0];
				if ($index !== null || count($operands) === 2) {
					$fields[] = "::scpp::mt::segment_range range$slot;";
					$initializers[] = count($operands) === 2 ? $operands[1] : '{' . $operands[1] . ', ' . $operands[2] . '}';
					$offset = "snapshot_$i.range$slot.offset";
					$length = "snapshot_$i.range$slot.length";
				} else [, $offset, $length] = $operands;
				$forwarded[] = 'inputs[' . count($args) . ']';
				$borrow = ($input->type_def()->key === null ? 'borrow' : 'borrow_hash') . ($input->type_def()->read_only ? '_read' : '');
				$args[] = "::scpp::mt::job::{$borrow}($owner, $offset, $length)";
			}
			$grant_list = implode(', ', $args);
			$context = $scalar_inputs ? "snapshot_$i, " : '';
			$parameter = $scalar_inputs ? ', const void* context' : '';
			$worker_call = "[](const ::scpp::mt::segment* inputs$parameter) { return $name(" . implode(', ', $forwarded) . '); }';
			$layout = "struct values_$i { " . implode(' ', $fields) . ' }';
			$values = '{' . implode(', ', $initializers) . '}';
			if ($index !== null) {
				$settings = $launch->arguments[0];
				$bound_expr = $settings instanceof ast_job_repetition ? $settings->arguments[0] : $settings;
				$bound = $this->value($bound_expr, $global_references, $declarations, $required_headers);
				$snapshots[] = "const auto count_$i = $bound;";
				$snapshots[] = "if (count_$i < 0) throw std::invalid_argument(\"each bound must be nonnegative\");";
				$limit = 'INT64_MAX';
				if ($settings instanceof ast_job_repetition) {
					$limit = "workers_$i";
					$expression = $this->value($settings->arguments[1], $global_references, $declarations, $required_headers);
					$snapshots[] = "const auto $limit = $expression;";
					$snapshots[] = "if ($limit <= 0) throw std::invalid_argument(\"Job workers must be positive\");";
				}
				if ($capped) $entries[] = "{values_list_$i.size(), $limit}";
				$snapshots[] = "$layout; std::vector<values_$i> values_list_$i; values_list_$i.reserve(count_$i);";
				$snapshots[] = "for (::scpp::int64 $index = 0; $index < count_$i; ++$index) values_list_$i.push_back($values);";
				// Reserve all grant storage before publishing pointers into it. Snapshots are already complete.
				$lines[] = "struct grants_$i { ::scpp::mt::segment slots[" . count($args) . "]; }; std::vector<grants_$i> grants_list_$i; grants_list_$i.reserve(values_list_$i.size());";
				$lines[] = "for (const auto& snapshot_$i : values_list_$i) { grants_list_$i.push_back({{$grant_list}}); jobs.push_back({grants_list_$i.back().slots, $context$worker_call}); }";
			} else {
				if ($capped) $entries[] = '{1, 1}';
				if ($fields !== []) $snapshots[] = "const $layout snapshot_$i$values;";
				$lines[] = "::scpp::mt::segment grants_{$i}[]{ $grant_list };";
				$job = "{grants_$i, $context$worker_call}";
				if ($dynamic) $lines[] = "jobs.push_back($job);";
				else $jobs[] = $job;
			}
		}
		if ($capped) {
			$lines[] = 'const ::scpp::mt::job_entry entries[]{' . implode(', ', $entries) . '};';
			$group = $cap === '' ? 'INT64_MAX' : 'workers';
			$lines[] = "::scpp::mt::join(jobs.data(), jobs.size(), $group, entries, " . count($entries) . ');';
		} elseif ($dynamic) $lines[] = "::scpp::mt::join(jobs.data(), jobs.size()$cap);";
		else {
			$lines[] = '::scpp::mt::job jobs[]{' . implode(', ', $jobs) . '};';
			$lines[] = '::scpp::mt::join(jobs, ' . count($jobs) . "$cap);";
		}
		$lines[] = $value->type_def()->cpp_type() . ' results; for (const auto& job : jobs) results.append(static_cast<::scpp::int64>(job.result)); return results;';
		return '([&]() { ' . implode(' ', [...$snapshots, ...$lines]) . ' }())';
	}

	/**
	 * Emit the concrete closure in place, with its own expression and result conversion.
	 * @param array<string,ast_global_declaration> $globals
	 * @param array<string,ast_function_declaration> $declarations
	 * @param array<string,bool> $headers
	 */
	private function lambda(ast_lambda_expression $lambda, array &$globals, array &$declarations, array &$headers): string
	{
		$type = $lambda->type_def();
		$signature = $type->signature;
		foreach ($signature->cpp_headers() as $header) $headers[$header] = true;
		$this->callable_dependencies($signature, $declarations);
		foreach ($signature->result->conversion_headers($lambda->conversion) as $header) $headers[$header] = true;
		$parameters = [];
		foreach ($lambda->parameters as $parameter) {
			$parameters[] = '[[maybe_unused]] ' . $this->signature_type($parameter->type_def(), false) . ' ' . ($parameter->mode === passing_mode::reference ? '&' : '') . $this->name($parameter);
		}
		$environment = $type->borrows_receiver ? ['&scpp_self'] : [];
		$capture_uses = $type->borrows_receiver ? ["\t(void)scpp_self;"] : [];
		foreach ($lambda->captures as $capture) {
			$name = $this->name($capture);
			$source = $this->name($capture->initializer->local_def());
			$environment[] = $capture->by_reference ? '&' . $name . ' = ' . $source
				: $name . ' = ' . $this->conversion($capture->type_def(), $capture->initialization(), $source, $capture->initializer);
			// An explicit capture may be unused in the source body.
			$capture_uses[] = "\t(void)" . $name . ';';
		}
		$header = '[' . implode(', ', $environment) . '](' . implode(', ', $parameters) . ') -> ' . $this->signature_type($signature->result, false) . ($lambda->returns_reference ? '&' : '');
		// Both closure forms lower in a synchronous context, independent of the enclosing operand.
		$outer = [$this->finally_exit, $this->destination, $this->byte_view, $this->transfer_labels,
			$this->async_body, $this->async_steps, $this->async_owned_steps, $this->suspensions];
		[$this->finally_exit, $this->destination, $this->byte_view, $this->transfer_labels,
			$this->async_body, $this->async_steps, $this->async_owned_steps, $this->suspensions] = [null, null, null, [], false, [], 0, 0];
		try {
			if ($lambda->body instanceof ast_block) {
				$lines = $this->statements($lambda->body, $declarations, $globals, $headers, $lambda);
				if ($capture_uses !== []) array_unshift($lines, ...$capture_uses);
				return $header . " {\n" . implode("\n", $lines) . "\n}";
			}
			$body = $this->value($lambda->body, $globals, $declarations, $headers, logical_root: $lambda->conversion === value_conversion::identity, borrow_result: $lambda->returns_reference);
			$result = $this->conversion($signature->result, $lambda->conversion, $body, $lambda->body);
			return $header . ' { return ' . $result . '; }';
		} finally {
			[$this->finally_exit, $this->destination, $this->byte_view, $this->transfer_labels,
				$this->async_body, $this->async_steps, $this->async_owned_steps, $this->suspensions] = $outer;
		}
	}

	/** Calls and definitions use complete record types. @param (ast_declaration_interface|record_handle_definition)[] $declarations */
	private function collect_signature_types(ast_function_declaration $function, array &$declarations): void
	{
		$collect = function (type_definition $type) use (&$declarations): void {
			if ($type instanceof application_collection_type) $this->concrete_collections->request($type);
			elseif ($type instanceof record_type_definition || $type instanceof enum_type_definition) $declarations[$type->declaration->symbol_key()] = $type->declaration;
			else $declarations[$type->name] = $type;
		};
		if ($this->headers?->declaration_header($function) !== null) {
			self::visit_signature_application_types($function, $collect);
		} else {
			$this->visit_application_types($function, $collect);
		}
	}

	/** Project emission only generates a reference entry for an observed base argument permutation. */
	private function emits_reordered_base(ast_function_declaration $constructor): bool
	{
		return !$constructor->parent->is_final && count($constructor->parameters) > 1
			&& ($this->headers?->uses_helper($constructor, dependency_use::reordered_base) ?? true);
	}

	/**
	 * Braced base initialization sequences converted arguments without a temporary base payload.
	 * @param ast_global_declaration[] $globals
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $headers
	 */
	private function base_initializer(ast_base_initialization $site, array &$globals /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$headers /** array<string,bool> */): string
	{
		if ($site->type_def() === type_void::instance()) return '';
		/** @var record_type_definition $base */
		$base = $site->type_def();
		$constructor = $base->constructor();
		$arguments = [];
		if ($constructor !== null) {
			$this->collect_signature_types($constructor, $declarations);
			if (self::self_value_constructor($constructor)) $arguments[] = '0';
			$parameters = $site->named?->evaluation_parameters($constructor->parameters) ?? $constructor->parameters;
			$fields = [];
			$reordered = $site->named?->reordered();
			foreach ($parameters as $index => $parameter) {
				if (!$site->unpacked && $parameter->type_reference instanceof ast_variadic_type_reference) {
					$pack = $parameter->type_def();
					if (!isset($site->arguments[$index])) $arguments[] = $pack->lower_cpp_empty();
					else {
						$steps = [];
						$name = $this->variadic_pack($site, $index, $pack, $globals, $declarations, $headers, $steps);
						$arguments[] = '[&]() { ' . implode(' ', $steps) . ' return ' . $name . '; }()';
					}
					continue;
				}
				$argument = $site->arguments[$index] ?? $parameter->default_value;
				$conversion = isset($site->arguments[$index]) ? $site->conversion($index) : $parameter->default_conversion();
				$type = $parameter->type_def();
				foreach ($type->conversion_headers($conversion) as $header) $headers[$header] = true;
				$value = $this->value($argument, $globals, $declarations, $headers);
				if ($reordered) {
					$field = 'slot_' . $site->named->slots[$index];
					$fields[] = ($type instanceof callback_type_definition ? $type->cpp_declarator($field) : $this->member_type($type) . ' ' . $field) . ';';
				}
				$arguments[] = $this->conversion($type, $conversion, $value, $argument);
			}
			// With labels alone the binder selects the fixed prefix and the tail defaults to empty.
			if ($site->named !== null && !$site->unpacked && ($tail = $constructor->variadic_slot()) !== null) {
				$type = $constructor->parameters[$tail]->type_def();
				$arguments[] = $type->lower_cpp_empty();
				if ($reordered) $fields[] = $this->member_type($type) . ' slot_' . $tail . ';';
			}
			if ($reordered) {
				// Aggregate fields are the actual value parameters, initialized once in written order.
				// Their lifetime ends after base initialization, before the derived fields and body.
				$values = '[&]() { struct scpp_escape_inputs { ' . implode(' ', $fields) . ' }; return scpp_escape_inputs{' . implode(', ', $arguments) . '}; }()';
				$arguments = [$constructor->parent->definition->cpp_type() . '::scpp_escape_named_base{}', $values];
			}
		}
		return ' : ' . $base->cpp_type() . '{' . implode(', ', $arguments) . '}';
	}

	/** Adapt agreed value positions and dispatch once through the implementation slot. @param bool[] $headers */
	private function variance_forward(ast_record_declaration $record, ast_function_declaration $contract,
		ast_function_declaration $method, array &$headers /** array<string,bool> */): string
	{
		$arguments = [];
		foreach ($contract->parameters as $i => $parameter) {
			$type = $parameter->type_def();
			$value = $this->forward_argument($parameter);
			$target = $method->parameters[$i]->type_def();
			$arguments[] = $target === $type ? $value : $this->conversion($target, value_conversion::owner_view, $value, null, $type);
		}
		$result = 'this->' . self::virtual_name($method) . '(' . implode(', ', $arguments) . ')';
		$type = $contract->return_type_def();
		if ($type !== $method->return_type_def()) $result = $this->conversion($type, value_conversion::owner_view, $result, null, $method->return_type_def());
		return $this->member_type($type) . ' ' . $record->definition->cpp_type() . '::' . self::virtual_name($contract)
			. '(' . implode(', ', $this->parameters($contract, $headers, false, true)) . ') { return ' . $result . "; }\n";
	}

	private function forward_argument(ast_parameter_declaration $parameter): string
	{
		$value = $this->name($parameter);
		return $parameter->mode !== passing_mode::value || $parameter->type_def()->cpp_trivial_value() ? $value
			: 'static_cast<' . $this->member_type($parameter->type_def()) . '&&>(' . $value . ')';
	}

	/** Emit only a settled bridge; forward values by move and references unchanged, without visiting the source body. @param bool[] $headers */
	private function interface_forward(ast_record_declaration $record, ast_function_declaration $method, array &$headers /** array<string,bool> */): string
	{
		$arguments = [];
		foreach ($method->parameters as $parameter) {
			$arguments[] = $this->forward_argument($parameter);
		}
		$owner = $record->definition->cpp_type();
		$receiver = 'this';
		if ($method->is_final && $method->has_virtual_dispatch()) {
			$receiver = 'static_cast<' . $owner . '*>(this)';
			$owner = self::final_view_name($record);
		}
		if ($method->has_virtual_dispatch()) $target = $receiver . '->' . $method->parent->definition->cpp_type() . '::' . self::virtual_name($method);
		else {
			$target = self::function_name($method);
			array_unshift($arguments, '*this');
		}
		foreach ($method->return_type_def()->cpp_headers() as $header) $headers[$header] = true;
		return $this->member_type($method->return_type_def()) . ' ' . $owner . '::' . self::virtual_name($method)
			. '(' . implode(', ', $this->parameters($method, $headers, false, true)) . ') { return ' . $target . '(' . implode(', ', $arguments) . "); }\n";
	}

	/** Parameter types need only forward declarations in the native payload and owner declarations. */
	private function record_signature_forwards(ast_record_declaration $record): string
	{
		$forwards = [];
		$collect = self::signature_forward_collector($forwards);
		foreach ($record->fields as $field) {
			$type = $field->type_def();
			if ($type instanceof owned_callable_type) self::visit_signature_type_dependencies($type, $collect);
		}
		foreach ($record->static_properties as $property) {
			$type = $property->type_def();
			if ($type instanceof owned_callable_type) self::visit_signature_type_dependencies($type, $collect);
		}
		foreach ($record->definition->constructor()?->parameters ?? [] as $parameter) {
			self::visit_signature_type_dependencies($parameter->type_def(), $collect);
		}
		foreach (self::variance_bridges($record) as [$contract, $implementation]) {
			self::visit_signature_application_types($contract, $collect);
		}
		foreach (array_merge($record->definition->effective_methods(), $record->definition->interface_forwards) as $method) {
			if (!$method->has_virtual_dispatch() && !isset($record->definition->interface_forwards[$method->short_name])) continue;
			self::visit_signature_application_types($method, $collect);
		}
		return implode('', $forwards);
	}

	/** Request the complete promise through the resolved type's task publication. @param bool[] $headers */
	private function require_task(type_definition $result, array &$headers): string
	{
		return $result->async_task()->require_definition($this->root, $result, $headers, $this->async_results);
	}

	/** Format a native constructor declaration or allocation factory. @param bool[] $headers */
	private function constructor_parameters(?ast_function_declaration $constructor, array &$headers /** array<string,bool> */): string
	{
		return $constructor === null ? '' : implode(', ', $this->parameters($constructor, $headers, false));
	}

	/**
	 * Emit declarations from settled signatures, without looking inside foreign bodies.
	 * @param bool[] $headers
	 */
	private function signature(ast_function_declaration $function, array &$headers /** array<string,bool> */, bool $short_types = false): string
	{
		if ($function->is_async) {
			$task = $function->return_type_def()->async_task()->forward_name($this->root);
			return $task . ' ' . self::function_name($function) . '(' . implode(', ', $this->parameters($function, $headers, $short_types)) . ')';
		}
		if ($function->is_destructor()) return $function->parent->definition->cpp_type() . '::~' . Cpp_Identifier::source($function->parent->short_name) . '() noexcept';
		$parameters = $this->parameters($function, $headers, $short_types);
		if ($function->is_constructor()) {
			if (self::self_value_constructor($function)) array_unshift($parameters, 'int');
			return $function->parent->definition->cpp_type() . '::' . Cpp_Identifier::source($function->parent->short_name)
				. '(' . implode(', ', $parameters) . ')';
		}
		if ($function->has_receiver() && !$function->has_virtual_dispatch()) {
			array_unshift($parameters, '[[maybe_unused]] ' . $function->parent->definition->cpp_type()
				. ($function->parent instanceof ast_enum_declaration ? ' scpp_self' : '& scpp_self'));
		}
		foreach ($function->return_type_def()->cpp_headers() as $header) $headers[$header] = true;
		return $this->signature_type($function->return_type_def(), $short_types) . ($function->returns_reference ? '& ' : ' ') . ($function->has_virtual_dispatch() ? $function->parent->definition->cpp_type() . '::' . self::virtual_name($function) : self::function_name($function))
			. '(' . implode(', ', $parameters) . ')';
	}

	/** Payload member declarations live inside scpp_records; qualify application signature types. */
	private function member_type(type_definition $type): string
	{
		if ($type instanceof class_union_type_definition) $type = $type->base;
		return ($type instanceof record_handle_definition || $type instanceof record_type_definition || $type instanceof enum_type_definition || $type instanceof application_collection_type)
			? '::' . ($this->root === '' ? '' : $this->root . '::') . $type->cpp_type() : $this->signature_type($type, false);
	}

	/** Share native parameter names, types, passing modes and includes. @param bool[] $headers @return string[] */
	private function parameters(ast_function_declaration $function, array &$headers /** array<string,bool> */, bool $short_types, bool $member = false, bool $borrowed_inputs = false): array /** list<string> */
	{
		$parameters = [];
		foreach ($function->parameters as $parameter) {
			$type = $parameter->type_def();
			if ($type instanceof class_union_type_definition) $type = $type->base;
			$native = $type instanceof callback_type_definition ? null : $this->signature_type($type, $short_types);
			if (($member || $function->is_constructor() || $function->has_virtual_dispatch()) && ($type instanceof record_handle_definition || $type instanceof record_type_definition || $type instanceof enum_type_definition || $type instanceof intersection_type_definition)) {
				$native = '::' . ($this->root === '' ? '' : $this->root . '::') . $native;
			}
			if ($parameter->mode === passing_mode::readonly_reference) $native = 'const ' . $native;
			$name = $this->name($parameter);
			$parameters[] = '[[maybe_unused]] ' . ($type instanceof callback_type_definition
				? $type->cpp_declarator(($borrowed_inputs ? '&' : '') . $name) : $native . ' ' . ($borrowed_inputs || $parameter->mode !== passing_mode::value ? '&' : '') . $name);
			foreach ($type->cpp_headers() as $header) {
				$headers[$header] = true;
			}
		}
		return $parameters;
	}

	/** Public headers qualify runtime names; private adapter units may use provider-exported type names. */
	private function signature_type(type_definition $type, bool $short): string
	{
		$name = $type->cpp_type();
		if (!str_starts_with($name, 'scpp::')) {
			return $name;
		}
		$word = substr($name, 6);
		if ($short && type_registry::has_cpp_type_alias($word) && !in_array($word, explode('::', $this->root), true)) {
			return $word;
		}
		return '::' . $name;
	}

	private function local_imports(): string
	{
		return $this->root === '' && $this->adapter_flavor !== null ? self::imports($this->adapter_flavor) : '';
	}

	/** STATIC METHODS */
	/** @param list<string> $steps @param ?list<string> $statement_setup */
	private static function staged_call(string $call, array $steps, string $return_clause, ?array &$statement_setup): string
	{
		if ($statement_setup !== null) {
			$statement_setup = $steps;
			return $call;
		}
		return $steps === [] ? $call : '([&]()' . $return_clause . ' { ' . implode(' ', $steps) . ' return ' . $call . '; }())';
	}

	private static function integer_spelling(int $integer /** int64 */): string
	{
		return $integer === \PHP_INT_MIN ? '(-9223372036854775807LL - 1LL)' : (string)$integer . 'LL';
	}
	/** Each distinct inherited signature needs one override, even when multiple interfaces introduce it. */
	private static function variance_bridges(ast_record_declaration $record): \Generator
	{
		$bridges = $record->definition->interface_adaptations;
		foreach ($record->methods as $method) {
			if ($method->adapted_base !== null) $bridges[] = [$method->adapted_base, $method];
		}
		$seen = [];
		foreach ($bridges as [$contract, $method]) {
			$key = self::virtual_name($contract);
			if (isset($seen[$key])) continue;
			$seen[$key] = true;
			yield [$contract, $method];
		}
	}

	/** An inherited ordinary implementation may introduce its own dispatch slot alongside an adapted interface. */
	private static function exact_interface_slot(ast_record_declaration $record, ast_function_declaration $method): ?ast_function_declaration
	{
		foreach ($record->definition->interface_requirements($method->short_name) as $contract) {
			if (self::virtual_name($contract) === self::virtual_name($method)) return $contract;
		}
		return null;
	}

	/** Standalone inspection uses the same publication plan, embedding each generated header once. @param string[] $files */
	private static function inline_headers(string $text, array $files /** array<string,string> */): string
	{
		$seen = [];
		$expand = static function (string $body) use (&$expand, &$seen, $files): string {
			return preg_replace_callback('/^#include "([a-f0-9]{64}\.hpp)"$/m', static function (array $match) use (&$expand, &$seen, $files): string {
				$path = 'units/' . $match[1];
				if (!isset($files[$path])) throw new \LogicException('Missing standalone header: ' . $path);
				if (isset($seen[$path])) return '';
				$seen[$path] = true;
				return $expand(str_replace("#pragma once\n", '', $files[$path]));
			}, $body);
		};
		return $expand($text);
	}

	/** The descendant owns one native interface view for its inherited final virtual slots. */
	private static function final_view_name(ast_record_declaration $record): string
	{
		return 'scpp_final_view_' . self::qualified_name($record);
	}

	/** A virtual slot has the same native member name throughout its inheritance chain. */
	private static function virtual_name(ast_function_declaration $method): string
	{
		$name = self::name_component($method->short_name);
		$result = $method->return_type_def();
		$variant = $result instanceof shared_record_type_definition || $result instanceof class_union_type_definition;
		foreach ($method->parameters as $parameter) {
			if ($parameter->mode === passing_mode::value && ($parameter->type_def() instanceof shared_record_type_definition
				|| $parameter->type_def() instanceof class_union_type_definition)) $variant = true;
		}
		if (!$variant) return 'scpp_virtual_' . $name;
		$parts = [$result->name];
		foreach ($method->parameters as $parameter) $parts[] = $parameter->mode->name . ':' . $parameter->type_def()->name;
		return 'scpp_slot_' . $name . '_' . bin2hex(implode("\0", $parts));
	}

	/** Distinguish an explicit self-value constructor from native memberwise copy construction. */
	private static function self_value_constructor(ast_function_declaration $constructor): bool
	{
		return count($constructor->parameters) === 1 && $constructor->parameters[0]->type_def() === $constructor->parent->definition;
	}

	/** Collect the native declarations needed to name a signature before its component definitions.
	 * @param string[] $forwards */
	private static function signature_forward_collector(array &$forwards): \Closure
	{
		return static function (type_definition $type) use (&$forwards): void {
			if ($type instanceof intersection_type_definition) {
				$forwards[$type->name] = 'struct ' . $type->cpp_type() . ";\n";
			} elseif ($type instanceof application_collection_type) {
				$forwards[$type->name] = 'class ' . $type->cpp_type() . ";\n";
			} elseif ($type instanceof enum_type_definition) {
				$forwards[$type->name] = self::namespace_body($type->cpp_namespace(), 'enum class '
					. Cpp_Identifier::source($type->declaration->short_name) . ' : ' . $type->storage()->cpp_type() . ";\n");
			} elseif ($type instanceof record_type_definition) {
				$forwards[$type->name] = self::namespace_body($type->cpp_namespace(), 'struct ' . Cpp_Identifier::source($type->declaration->short_name) . ";\n");
			} elseif ($type instanceof record_handle_definition) $forwards[$type->name] = 'struct ' . $type->cpp_type() . ";\n";
		};
	}

	/** A concrete container header can name a callable whose arguments include its enclosing class. */
	public static function callable_forwards(owned_callable_type $type): string
	{
		$forwards = [];
		self::visit_signature_type_dependencies($type, self::signature_forward_collector($forwards));
		return implode('', $forwards);
	}

	/** Visit the receiver and outputs, then signature parameters in declaration order. @param \Closure(type_definition):void $visit */
	public static function visit_signature_application_types(ast_function_declaration $function, \Closure $visit): void
	{
		if ($function->has_receiver() && $function->parent instanceof ast_enum_declaration) $visit($function->parent->type_definition());
		$type = $function->return_type_def();
		if ($type instanceof generator_type_definition) {
			foreach ([$type->iteration_value_type(), $type->iteration_key_type()] as $output) {
				if ($output instanceof record_handle_definition || $output instanceof record_type_definition || $output instanceof enum_type_definition) $visit($output);
			}
		}
		self::visit_signature_type_dependencies($type, $visit);
		foreach ($function->parameters as $parameter) {
			self::visit_signature_type_dependencies($parameter->type_def(), $visit);
		}
	}

	/** Visit concrete and nominal dependencies at their position in the signature. @param \Closure(type_definition):void $visit */
	private static function visit_signature_type_dependencies(type_definition $type, \Closure $visit): void
	{
		if ($type instanceof class_union_type_definition) {
			$visit($type->base);
			return;
		}
		if ($type instanceof owned_callable_type) $type = $type->signature;
		if ($type instanceof callback_type_definition) {
			self::visit_signature_type_dependencies($type->result, $visit);
			foreach ($type->inputs as $input) self::visit_signature_type_dependencies($input, $visit);
			return;
		}
		if ($type instanceof intersection_type_definition) {
			$visit($type);
			$visit($type->base);
			$visit($type->capability);
			return;
		}
		if ($type instanceof application_collection_type) {
			$visit($type);
			foreach ($type->concrete_types() as $argument) self::visit_signature_type_dependencies($argument, $visit);
			return;
		}
		if ($type instanceof segment_type) self::visit_signature_type_dependencies($type->element, $visit);
		if ($type instanceof nullable_enum_type_definition) $visit($type->payload_type);
		if ($type instanceof record_handle_definition || $type instanceof record_type_definition || $type instanceof enum_type_definition) $visit($type);
	}

	/** Follow only prepared casts of literal values and immutable constant declarations. */
	private static function constant_compound_operand(ast_expression $value): bool
	{
		while ($value instanceof ast_cast_expression) {
			if (!self::inert_scalar_conversion($value->type_def(), $value->conversion())) return false;
			$value = $value->operand;
		}
		if ($value instanceof ast_name_expression && !($value instanceof ast_qualified_data)) {
			$constant = $value->declaration();
			return $constant instanceof runtime_integer_constant || ($constant instanceof ast_constant_declaration
				&& self::inert_literal_argument($constant->initializer, $constant->type_def(), $constant->initialization()));
		}
		return $value instanceof ast_integer_expression || $value instanceof ast_float_expression || $value instanceof ast_keyword_expression;
	}

	/** Literal initialization cannot observe a sibling when the prepared scalar conversion cannot fail. */
	private static function inert_literal_argument(ast_expression $argument, type_definition $type, int $conversion): bool
	{
		// An owning conversion can allocate, and a lambda can snapshot a sibling's mutable storage.
		if ($type instanceof owned_callable_type) return false;
		return $argument instanceof ast_callable_reference || $argument instanceof ast_lambda_expression || (($argument instanceof ast_integer_expression || $argument instanceof ast_float_expression
			|| $argument instanceof ast_keyword_expression) && self::inert_scalar_conversion($type, $conversion));
	}

	/** Only plain, nonthrowing scalar conversions can move across an old-value read. */
	private static function inert_scalar_conversion(type_definition $type, int $conversion): bool
	{
		if (!$type->cpp_trivial_value()) return false;
		return match ($conversion) {
			value_conversion::identity, value_conversion::boolean_integer, value_conversion::integer_boolean,
			value_conversion::integer_widen, value_conversion::integer_proven, value_conversion::numeric_floating,
			value_conversion::floating_boolean, value_conversion::floating_integer_proven => true,
			default => false,
		};
	}

	private static function imports(string $flavor): string
	{
		return "using namespace ::scpp::types;\nusing namespace ::scpp::$flavor;\n";
	}

	public static function namespace_body(string $root, string $body): string
	{
		return $root === '' ? $body : "namespace $root {\n" . $body . "}\n";
	}

	/** The typed destination supplies the native type. Leave the signed minimum to the literal renderer. */
	private static function plain_integer_literal(ast_expression $value, int $conversion /** uint16 */): ?string
	{
		if (!($value instanceof ast_integer_expression)
			|| ($conversion !== value_conversion::identity && $conversion !== value_conversion::integer_proven)) {
			return null;
		}
		$integer = $value->integer();
		return $integer === \PHP_INT_MIN ? null : (string)$integer;
	}

	/** Escape individual bytes as Zhh. Keep C++ reserved underscores and our namespace delimiter out of components. */
	private static function name_component(string $name): string
	{
		return preg_replace_callback('/Z|[^A-Za-z0-9_]|^[_0-9]|_(?=_|$|ns(?:_|$))/',
			static fn(array $match): string => 'Z' . bin2hex($match[0]), $name);
	}

	public static function qualified_name(ast_declaration_interface $declaration): string
	{
		return self::qualified_path($declaration->name());
	}

	public static function qualified_path(string $name): string
	{
		return implode('_ns_', array_map(self::name_component(...), explode('\\', $name)));
	}

	private static function property_name(ast_global_declaration $property): string
	{
		// A C++ member with the record's own name conflicts with an explicit constructor.
		return $property->short_name === $property->parent->short_name ? 'scpp_escape_static_self' : Cpp_Identifier::source($property->short_name);
	}

	public static function global_name(ast_global_declaration $declaration): string
	{
		if ($declaration->parent instanceof ast_record_declaration) {
			return $declaration->parent->definition->cpp_type() . '::' . self::property_name($declaration);
		}
		return 'var_' . self::qualified_name($declaration);
	}

	/** Encode inherited implementations by called and declaring types while retaining direct method names. */
	public static function function_name(ast_function_declaration $declaration): string
	{
		if ($declaration->parent instanceof ast_record_declaration) {
			$owner = self::qualified_name($declaration->parent);
			if ($declaration->instance !== null && $declaration->instance->called->declaration() !== $declaration->parent) {
				$called = self::qualified_name($declaration->instance->called->declaration());
				return 'called_' . strlen($called) . '_' . $called . '_' . strlen($owner) . '_' . $owner . '_' . self::name_component($declaration->short_name);
			}
			return 'method_' . strlen($owner) . '_' . $owner . '_' . self::name_component($declaration->short_name);
		}
		return self::qualified_name($declaration) . '_';
	}
}
