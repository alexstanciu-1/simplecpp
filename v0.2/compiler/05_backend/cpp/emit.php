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
	/** Count record/handle expressions during the existing walk to preserve observable temporary destruction. */
	private int $record_expressions = 0;
	/** Private demand collected during this source unit’s existing lowering walk. */
	private Cpp_Adapters $inline_adapters;
	/** Private concrete type demand, sharing the source adapter publication and cleanup boundary. */
	private Cpp_Concrete_Collections $concrete_collections;

	/** @var int[] Private lowering requirements; no prepared AST mutation. */
	private array $enum_helpers /** array<string,uint8> */ = [];

	/** @var cpp_generator_layout[] Source-owned layouts available after the lowering join. */
	private array $generator_layouts /** array<string,cpp_generator_layout> */;
	/** @var string[] Local storage names during one producer resume-body emission. */
	private array $generator_locals /** array<uint32,string> */ = [];

	/** CONSTRUCTOR */
	/** @param cpp_generator_layout[] $generator_layouts */
	public function __construct(?Cpp_Headers $headers = null, string $root = 'App', array $generator_layouts /** array<string,cpp_generator_layout> */ = [])
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
		return $type->lower_cpp_conversion($conversion, $value, $syntax, $source_type, $this->inline_adapters);
	}

	/** Concrete headers requested by the last source emission; no AST links escape into the output bundle. @return string[] */
	public function adapter_files(): array /** array<string,string> */
	{
		return $this->inline_adapters->files() + $this->concrete_collections->files();
	}

	/** Share declaration spelling between local prototypes and generated headers. @param bool[] $includes */
	public function declaration(ast_function_declaration|ast_global_declaration|ast_type_declaration $node,
		array &$includes /** array<string,bool> */): string
	{
		if ($node instanceof ast_enum_declaration) {
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
			foreach (dependency_use::cases() as $use) {
				if (!$this->needs_enum_helper($node, $use)) continue;
				$binding = $type->helper_function($use);
				$includes[$binding->header] = true;
				$helper .= $this->enum_helper_signature($binding) . ";\n";
			}
			return self::namespace_body($type->cpp_namespace(), 'enum class ' . Cpp_Identifier::source($node->short_name)
				. ' : ' . $storage->cpp_type() . " {\n" . implode("\n", $lines) . "\n};\n") . $helper;
		}
		if ($node instanceof ast_record_declaration) {
			$layout = [];
			$base = $node->definition->base();
			foreach ($node->fields as $field) {
				foreach ($field->type_def()->cpp_headers() as $header) $includes[$header] = true;
				$type = $field->type_def();
				$qualifier = ($type instanceof record_type_definition || $type instanceof record_handle_definition || $type instanceof enum_type_definition || $type instanceof application_collection_type) && $this->root !== '' ? '::' . $this->root : '';
				$default = $field->initializer === null ? '{}' : ' = ' . $this->declaration_initialization(
					$type, $field->initializer, $field->conversion, $includes);
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
				if (!isset($node->methods['__construct'])) $layout[] = "\t$name() = default;";
				$layout[] = "\t$name(const $name&) = default;";
				$layout[] = "\t$name($name&&) = default;";
				$layout[] = "\t$name& operator=(const $name&) = default;";
				$layout[] = "\t$name& operator=($name&&) = default;";
			}
			foreach ($node->definition->effective_methods() as $method) {
				if (!$method->has_virtual_dispatch()) continue;
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
					&& !self::has_exact_interface_slot($node, $method);
				$slot = "\t" . ($new_slot ? 'virtual ' : '') . $this->member_type($method->return_type_def()) . ' ' . self::virtual_name($method)
					. '(' . implode(', ', $this->parameters($method, $includes, false, true)) . ')' . ($new_slot ? '' : ' override');
				if ($method->is_final && $method->has_virtual_dispatch()) {
					$final_slots[] = $slot . ' final;';
					$layout[] = "\tusing " . $base->cpp_type() . '::' . self::virtual_name($method) . ';';
				} else {
					$layout[] = $slot . ';';
				}
			}
			if (isset($node->methods['__construct'])) {
				$constructor = $node->methods['__construct'];
				$params = $this->constructor_parameters($constructor, $includes);
				if (self::self_value_constructor($constructor)) $params = 'int, ' . $params;
				$name = Cpp_Identifier::source($node->short_name);
				$layout[] = "\t" . $name . '(' . $params . ');';
				if ($params !== '' && $constructor->accepts_argument_count(0)) $layout[] = "\t" . $name . '();';
			}
			if ($base !== null && !isset($node->methods['__construct']) && $base->constructor() !== null) {
				$layout[] = "\tusing " . $base->cpp_type() . '::' . Cpp_Identifier::source($base->declaration->short_name) . ';';
			}
			$bases = $base === null ? [] : ['public ' . $base->cpp_type()];
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
		if ($node instanceof ast_function_declaration) {
			if ($node->has_virtual_dispatch()) return '';
			return ($node->return_type_def() instanceof generator_type_definition ? $this->generator_layout($node->return_type_def(), $includes) : '')
				. $this->signature($node, $includes) . ';';
		}
		foreach ($node->type_def()->cpp_headers() as $header) {
			$includes[$header] = true;
		}
		return 'extern ' . $this->signature_type($node->type_def(), false) . ' ' . self::global_name($node) . ';';
	}

	private function needs_enum_helper(ast_enum_declaration $enum, dependency_use $use): bool
	{
		return $this->headers?->needs_enum_helper($enum, $use)
			?? ((($this->enum_helpers[$enum->symbol_key()] ?? 0) & $use->value) !== 0);
	}

	/** Both concrete enum helpers have one runtime operand and no templates. */
	private function enum_helper_signature(runtime_function $binding): string
	{
		return $binding->return_type_def()->cpp_type() . ' ' . $binding->symbol . '(' . $binding->parameters[0]->cpp_type() . ' value)';
	}

	/** Emit from the single owning case inventory; preparation never builds another lookup table. */
	private function enum_helper(enum_type_definition $type, dependency_use $use): string
	{
		$lines = [];
		$string_lookup = $use === dependency_use::enum_from_value && $type->backing() === type_string::instance();
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
				dependency_use::enum_from_value => $type->cpp_case($case),
			};
			$lines[] = "\tcase " . $match . ': return ' . $result . ';';
		}
		$body = implode("\n", $lines);
		$failure = 'throw std::runtime_error("Invalid value for enum helper");';
		$body = $string_lookup ? $body . "\n\t" . $failure : "\tswitch (value) {\n" . $body . "\n\tdefault: " . $failure . "\n\t}";
		return $this->enum_helper_signature($type->helper_function($use)) . " {\n" . $body . "\n}\n";
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
		$declarations = [];
		$definitions = [];
		$global_references = [];
		$required_headers = [];
		foreach ($file->globals() as $global) {
			$type = $global->type_def();
			$initializer = $this->declaration_initialization($type, $global->initializer, $global->initialization(), $required_headers);
			$qualifier = $global->initializer instanceof ast_string_expression ? '' : 'constinit ';
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
						$defaults[] = $this->declaration_initialization($parameter->type_def(), $parameter->default_value,
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
			$lines = $this->statements($function->body(), $declarations, $global_references, $required_headers);
			if ($function->is_constructor() || $function->is_destructor() || $function->has_virtual_dispatch()) array_unshift($lines, "\t[[maybe_unused]] auto& scpp_self = *this;");
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
			$definitions[] = $this->signature($function, $required_headers, $this->adapter_flavor !== null && $this->root !== '')
				. $base_initializer . "\n{\n" . $this->local_imports() . implode("\n", $lines) . "\n}\n";
		}
		if ($entry !== null) {
			$this->adapter_flavor = null;
			$lines = $this->statements($entry->body(), $declarations, $global_references, $required_headers);
			$unit_flavor ??= $this->adapter_flavor;
			$definitions[] = 'int ' . $entry_name . "()\n{\n" . $this->local_imports() . implode("\n", $lines) . "\n}\n";
		}
		foreach ([...$this->inline_adapters->dependencies(), ...$this->concrete_collections->dependencies()] as $type) {
			if ($type instanceof record_handle_definition) $declarations[$type->name] = $type;
			elseif ($type instanceof record_type_definition || $type instanceof enum_type_definition) $declarations[$type->declaration->symbol_key()] = $type->declaration;
			foreach ($type->cpp_headers() as $include) $required_headers[$include] = true;
		}
		$prototypes = '';
		$unit_headers = [];
		foreach ($declarations as $node) {
			if (!($node instanceof ast_function_declaration) || !($node->return_type_def() instanceof generator_type_definition)) continue;
			foreach ($this->application_types($node) as $type) {
				if ($type instanceof application_collection_type) $unit_headers[$this->headers->collection_header($type, $this->root)] = true;
			}
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
			foreach (dependency_use::cases() as $use) {
				if (!$this->needs_enum_helper($node, $use)) continue;
				$required_headers['stdexcept'] = true;
				$required_headers[$node->definition->helper_function($use)->header] = true;
				$definitions[] = $this->enum_helper($node->definition, $use);
			}
		}
		ksort($declarations, \SORT_STRING);
		$records = [];
		$layouts = '';
		$owners = [];
		foreach ($declarations as $node) {
			if (!($node instanceof ast_enum_declaration)) continue;
			$this->type_prototypes($node, $records, $unit_headers, $required_headers, $layouts, $owners);
		}
		foreach ($declarations as $function) {
			if ($function instanceof ast_enum_declaration) continue;
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
				$prototype = $this->declaration($function, $required_headers);
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
				$prototypes .= $this->declaration($global, $required_headers) . "\n";
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
			return $header . self::namespace_body($this->root, $imports . $owner_types . $layouts . $owner_methods . $prototypes . implode("\n", $definitions));
		}
		$text = $header . self::namespace_body($this->root, $this->concrete_collections->forwards() . $imports . $owner_types . $layouts . $owner_methods . $prototypes);
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
			$text .= $this->declaration($record, $runtime_headers) . "\n";
			return;
		}
		if ($record->definition->base() !== null) {
			$this->type_prototypes($record->definition->base()->declaration, $seen, $unit_headers, $runtime_headers, $text, $owners);
		}
		foreach ($record->definition->interfaces() as $interface) {
			$this->type_prototypes($interface->declaration, $seen, $unit_headers, $runtime_headers, $text, $owners);
		}
		foreach ($record->fields as $field) {
			$type = $field->type_def();
			if ($type instanceof record_type_definition || $type instanceof enum_type_definition) {
				$this->type_prototypes($type->declaration, $seen, $unit_headers, $runtime_headers, $text, $owners);
			} elseif ($type instanceof record_handle_definition) {
				$owners[$type->payload->name] = $type->payload->shared;
			}
		}
		$text .= $this->declaration($record, $runtime_headers) . "\n";
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
		$operand = $this->value($condition, $global_references, $declarations, $headers, $direct, logical_root: true);
		$logical_parent = $condition->parent;
		$negated = $logical_parent instanceof ast_not_expression;
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
		array &$required_headers /** array<string,bool> */, ?ast_function_declaration $function = null,
		?cpp_resume_context $resume = null): array /** list<string> */
	{
		if ($body instanceof ast_function_body) {
			$body->require_prepared();
			/** @var ast_function_declaration $function */
			$function = $body->parent;
		}
		$lines = [];
		if ($resume !== null) $resume->scopes[] = $body;
		foreach ($body->statements as $statement)
		{
			if ($statement instanceof ast_base_statement) continue;
			$leaf = $resume !== null && !($statement instanceof ast_if_statement || $statement instanceof ast_loop_statement
				|| $statement instanceof ast_yield_statement);
			if ($leaf) $lines[] = '{';
			if ($statement instanceof ast_yield_statement) {
				$lines[] = $this->generator_yield($statement, $function->return_type_def(), $resume, $declarations, $global_references, $required_headers);
			}
			elseif (($statement instanceof ast_break_statement) || ($statement instanceof ast_continue_statement)) {
				$transfer = $statement instanceof ast_break_statement ? 'break;' : 'continue;';
				if ($resume !== null) {
					$loop = $resume->loops[array_key_last($resume->loops)];
					$lines[] = $this->generator_cleanup($resume, $loop['depth']);
					if ($statement instanceof ast_continue_statement && $loop['update'] !== null) $transfer = 'goto ' . $loop['update'] . ';';
				}
				$lines[] = "\t" . $transfer;
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
					foreach ($steps as $step) $lines[] = "\t" . $step;
					$lines[] = "\tauto&& iteration_source = " . $receiver . ';';
				}
				$destination = $statement->destination;
				$method = $type->iteration_method();
				$direct = $this->iteration_direct($statement);
				$output_type = $type->iteration_output_type();
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
				foreach ($this->statements($statement->body, $declarations, $global_references, $required_headers, $function, $resume) as $line) $lines[] = "\t" . $line;
				$lines[] = "\t}";
				if ($resume !== null) {
					array_pop($resume->loops);
					$lines[] = $this->generator_cleanup($resume, count($resume->scopes) - 1);
					array_pop($resume->scopes);
				}
				$lines[] = "\t}";
			}
			elseif ($statement instanceof ast_loop_statement) {
				$condition = $this->condition($statement->condition, $global_references, $declarations, $required_headers);
				$post_test = $statement instanceof ast_do_while_statement;
				$update_snapshot = null;
				$update_label = null;
				if ($statement instanceof ast_for_statement) {
					$initializer = $this->binding($statement->initializer, $global_references, $declarations, $required_headers, $init_snapshot);
					if ($resume !== null) {
						$lines[] = $init_snapshot === null ? "{ $initializer; }" : "{ const auto $init_snapshot; $initializer; }";
						$initializer = '';
					}
					elseif ($init_snapshot !== null) {
						$lines[] = "\t{ const auto " . $init_snapshot . '; ' . $initializer . '; }';
						$initializer = '';
					}
					$update = $this->binding($statement->update, $global_references, $declarations, $required_headers, $update_snapshot);
					if ($resume === null && $update_snapshot !== null) {
						$lines[] = "\t{ " . $statement->update->local_def()->type_def()->cpp_type() . ' compound_previous;';
						$update = '(' . $update_snapshot . ', ' . $update . ')';
					}
					if ($resume !== null) {
						$update_label = 'loop_update_' . ++$resume->loop_id;
						$lines[] = "\twhile ($condition) {";
					} else $lines[] = "\tfor (" . $initializer . '; ' . $condition . '; ' . $update . ') {';
				}
				else {
					$lines[] = "\t" . ($post_test ? 'do' : 'while (' . $condition . ')') . ' {';
				}
				if ($resume !== null) $resume->loops[] = ['depth' => count($resume->scopes), 'update' => $update_label];
				foreach ($this->statements($statement->body, $declarations, $global_references, $required_headers, $function, $resume) as $line) {
					$lines[] = "\t" . $line;
				}
				if ($resume !== null) {
					array_pop($resume->loops);
					if ($update_label !== null) {
						$lines[] = "goto $update_label;\n$update_label:;";
						$lines[] = $update_snapshot === null ? "{ $update; }" : "{ const auto $update_snapshot; $update; }";
					}
				}
				$lines[] = "\t}" . ($post_test ? ' while (' . $condition . ');' : '');
				if ($resume !== null && $statement instanceof ast_for_statement && $statement->initializer instanceof ast_local_declaration) {
					$lines[] = $this->generator_destroy($statement->initializer);
				}
				if ($resume === null && $update_snapshot !== null) {
					$lines[] = "\t}";
				}
			}
			elseif ($statement instanceof ast_if_statement) {
				foreach ($statement->arms as $index => $arm) {
					$head = $index === 0 ? 'if' : 'else';
					if ($arm->condition !== null) {
						$head = ($index === 0 ? 'if' : 'else if') . ' ('
							. $this->condition($arm->condition, $global_references, $declarations, $required_headers) . ')';
					}
					$lines[] = "\t" . $head . ' {';
					foreach ($this->statements($arm->body, $declarations, $global_references, $required_headers, $function, $resume) as $line) {
						$lines[] = "\t" . $line;
					}
					$lines[] = "\t}";
				}
			}
			elseif ($statement instanceof ast_field_assignment) {
				if ($statement->target instanceof ast_qualified_data) {
					$lines[] = "\t" . $this->discard_qualifier($statement->target->receiver, $global_references, $declarations, $required_headers) . ';';
					$field = $this->name_value($statement->target, $global_references, $declarations, $required_headers);
				} else $field = $this->value($statement->target, $global_references, $declarations, $required_headers);
				$value = $this->value($statement->value, $global_references, $declarations, $required_headers);
				$type = $statement->target->type_def();
				foreach ($type->conversion_headers($statement->conversion) as $header) $required_headers[$header] = true;
				if ($statement->transfer) {
					$required_headers['utility'] = true;
					$rhs = 'std::move(' . $value . ')';
				} else {
					$rhs = $this->conversion($type, $statement->conversion, $value, $statement->value);
				}
				$lines[] = "\t" . $field . ' = ' . $rhs . ';';
			}
			elseif ($statement instanceof ast_index_assignment) {
				/** @var index_write_type $type */
				$type = $statement->receiver->type_def();
				$steps = [];
				$receiver = $statement->receiver instanceof ast_field_expression
					? $this->retained_place($statement->receiver, $global_references, $declarations, $required_headers, $steps)
					: $this->value($statement->receiver, $global_references, $declarations, $required_headers);
				$key = $this->value($statement->index, $global_references, $declarations, $required_headers);
				$value = $this->value($statement->value, $global_references, $declarations, $required_headers);
				$conversion = $statement->conversion();
				$destination = $type->write_type();
				foreach ($destination->conversion_headers($conversion) as $header) {
					$required_headers[$header] = true;
				}
				$store = $type->lower_cpp_store($statement, $receiver, $key, $value, $this->inline_adapters);
				$lines[] = "\t" . ($steps === [] ? $store : '{ ' . implode(' ', $steps) . ' ' . $store . ' }');
			}
			elseif ($statement instanceof ast_append_statement) {
				/** @var appendable_collection_type $type */
				$type = $statement->receiver->type_def();
				$steps = [];
				$receiver = $statement->receiver instanceof ast_field_expression
					? $this->retained_place($statement->receiver, $global_references, $declarations, $required_headers, $steps)
					: $this->value($statement->receiver, $global_references, $declarations, $required_headers);
				$value = $this->value($statement->value, $global_references, $declarations, $required_headers);
				$conversion = $statement->conversion();
				foreach ($type->write_type()->conversion_headers($conversion) as $header) {
					$required_headers[$header] = true;
				}
				$append = $type->lower_cpp_append($statement, $receiver, $value, $this->inline_adapters) . ';';
				$lines[] = "\t" . ($steps === [] ? $append : '{ ' . implode(' ', $steps) . ' ' . $append . ' }');
			}
			elseif (($statement instanceof ast_local_declaration) || ($statement instanceof ast_storage_write)) {
				$write = $this->binding($statement, $global_references, $declarations, $required_headers, $snapshot);
				if ($statement instanceof ast_assignment_statement && $statement->operator === binary_operator::coalesce) {
					/** @var carrier_type_definition $type */
					$type = $statement->local_def()->type_def();
					$lines[] = "\tif (!(" . $type->lower_cpp_presence($this->name($statement->local_def())) . ')) { ' . $write . '; }';
				} else $lines[] = "\t" . ($snapshot === null ? $write . ';' : '{ const auto ' . $snapshot . '; ' . $write . '; }');
			}
			elseif (($statement instanceof ast_return_statement) && ($statement->value !== null)) {
				$conversion = $statement->conversion();
				if ($statement->value instanceof ast_match_expression && $conversion === value_conversion::identity) {
					$lines[] = "\t" . $this->match_selection($statement->value, $global_references, $declarations, $required_headers);
					continue;
				}
				if ($conversion instanceof exit_conversion) {
					$value = self::plain_integer_literal($statement->value, $conversion)
						?? Cpp_Exit::expression($conversion,
							$this->value($statement->value, $global_references, $declarations, $required_headers));
				}
				else {
					$value = $this->value($statement->value, $global_references, $declarations, $required_headers,
						logical_root: $conversion === value_conversion::identity);
					$type = $function->return_type_def();
					$value = $this->conversion($type, $conversion, $value, $statement->value);
					foreach ($type->conversion_headers($conversion) as $header) {
						$required_headers[$header] = true;
					}
				}
				$lines[] = "\treturn " . $value . ';';
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
					$lines[] = "\t" . $type->lower_cpp_output($value, $operand);
				}
			}
			elseif ($statement instanceof ast_return_statement) {
				$lines[] = $resume === null ? "\treturn;" : "close(); return false;";
			}
			elseif ($statement instanceof ast_call_statement) {
				$lines[] = "\t" . $this->value($statement->value, $global_references, $declarations, $required_headers) . ';';
			}
			else {
				throw new \LogicException('Unhandled prepared statement');
			}
			if ($leaf) $lines[] = '}';
		}
		if ($resume !== null) {
			$lines[] = $this->generator_cleanup($resume, count($resume->scopes) - 1);
			array_pop($resume->scopes);
		}
		return $lines;
	}

	private function name(ast_local_binding $binding): string
	{
		return ($this->generator_locals[$binding->storage_id()] ?? null)
			?? 'var_' . self::name_component($binding->declaration_key()) . '_' . $binding->storage_id();
	}

	/**
	 * A statement owns its snapshot locally. A for update assigns to storage supplied by its loop.
	 * @param ast_global_declaration[] $global_references
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $headers
	 */
	private function compound(ast_assignment_statement $statement,
		array &$global_references /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$headers /** array<string,bool> */, ?string &$snapshot): string
	{
		if ($statement instanceof ast_index_compound) return $this->indexed_compound($statement, $global_references, $declarations, $headers);
		$property = $statement instanceof ast_property_compound;
		$destination = $property ? $statement->target : $statement->local_def();
		$type = $destination->type_def();
		$simple_rhs = $statement->value instanceof ast_integer_expression || ($statement->value instanceof ast_name_expression
			&& $statement->value->declaration() instanceof ast_local_binding);
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
		$execution = $statement->execution_type();
		$right = $this->value($statement->value, $global_references, $declarations, $headers);
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
		$left = $direct_read ? $name : 'compound_previous';
		$result = $this->compound_result($statement, $type, $left, $right, $headers);
		if ($direct_read) return $name . ' = ' . $result;
		if ($steps !== []) {
			$steps[] = 'const auto compound_previous = ' . $name . ';';
			$steps[] = $name . ' = ' . $result . ';';
			return "{\n\t\t" . implode("\n\t\t", $steps) . "\n\t}";
		}
		$snapshot = 'compound_previous = ' . $name;
		return $name . ' = ' . $result;
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
		if ($statement->left_conversion() !== value_conversion::identity) {
			$left = $this->conversion($execution, $statement->left_conversion(), $left, source_type: $type);
			foreach ($execution->conversion_headers($statement->left_conversion()) as $header) {
				$headers[$header] = true;
			}
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
		$steps[] = 'const auto compound_previous = ' . $collection->lower_cpp_mutation_read($target->index, 'compound_collection', $key) . ';';
		$right = $this->value($statement->value, $globals, $declarations, $headers);
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
		$place = $this->retained_place($target->receiver, $globals, $declarations, $headers, $steps);
		$steps[] = 'auto&& compound_collection = ' . $place . ';';
		$key = $this->value($target->index, $globals, $declarations, $headers);
		if (!($target->index instanceof ast_integer_expression)) {
			$steps[] = 'const auto compound_key = ' . $key . ';';
			$key = 'compound_key';
		}
		return $key;
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
		array &$declarations /** array<string,ast_function_declaration> */, array &$headers /** array<string,bool> */): string
	{
		if (ast_match_expression::is_condition_subject($match->subject)) {
			$code = '';
			$default = null;
			foreach ($match->arms as $arm) {
				if ($arm->label === null) {
					$default = $arm;
					continue;
				}
				$code .= 'if (' . $this->condition($arm->label, $globals, $declarations, $headers) . ') { return '
					. $this->value($arm->result, $globals, $declarations, $headers) . '; } ';
			}
			return $code . 'return ' . $this->value($default->result, $globals, $declarations, $headers) . ';';
		}
		$code = 'switch (' . $this->value($match->subject, $globals, $declarations, $headers) . ') { ';
		foreach ($match->arms as $arm) {
			if ($arm->label === null) $code .= 'default: ';
			elseif ($arm->label->type_def() instanceof enum_type_definition) $code .= 'case ' . $this->value($arm->label, $globals, $declarations, $headers) . ': ';
			else {
				$integer = $arm->label->constant();
				$code .= 'case ' . ($integer === \PHP_INT_MIN ? '(-9223372036854775807LL - 1LL)' : (string)$integer . 'LL') . ': ';
			}
			$code .= 'return ' . $this->value($arm->result, $globals, $declarations, $headers) . '; ';
		}
		return $code . '}';
	}

	/**
	 * Render a binding without its delimiter and report any required prior read to its enclosing statement or loop.
	 * @param ast_global_declaration[] $global_references
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $headers
	 */
	private function binding(ast_local_declaration|ast_storage_write $statement,
		array &$global_references /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$headers /** array<string,bool> */, ?string &$snapshot): string
	{
		$snapshot = null;
		if (($statement instanceof ast_assignment_statement) && $statement->calculates_value()) {
			return $this->compound($statement, $global_references, $declarations, $headers, $snapshot);
		}
		if ($statement instanceof ast_index_update) {
			return $this->indexed_update($statement, $global_references, $declarations, $headers);
		}
		if ($statement instanceof ast_property_update) {
			$target = $this->value($statement->target, $global_references, $declarations, $headers);
			return $statement->target->type_def()->lower_cpp_update($statement->operator, $target, $statement->postfix);
		}
		if ($statement instanceof ast_update_statement) {
			return $statement->local_def()->type_def()->lower_cpp_update($statement->operator,
				$this->name($statement->local_def()), $statement->postfix);
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
			$prefix = '[[maybe_unused]] ' . $type->cpp_type() . ' ' . $this->name($statement);
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
				&& ($value->declaration() === $statement->local_def())) {
				return '(void)' . $prefix;
			}
		}
		$operand = $this->value($value, $global_references, $declarations, $headers,
			typed_initializer: ($statement instanceof ast_local_declaration) && ($conversion === value_conversion::identity),
			logical_root: $conversion === value_conversion::identity);
		foreach ($type->conversion_headers($conversion) as $header) {
			$headers[$header] = true;
		}
		return $this->binding_initialization($statement, $prefix, $this->conversion($type, $conversion, $operand, $value));
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
	 * Only the root of an identity initializer may rely on its destination for the native result type.
	 * Typed values carry normalized constants or settled bindings; no source-name lookup occurs here.
	 * $logical_root: the caller supplies any required grouping for a logical expression.
	 * $reorderable: output proof for this subtree, computed during lowering, never retained in preparation.
	 * True permits unsequenced evaluation only alongside other proven subtrees; calls always remain false.
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $required_headers
	 * @param ast_global_declaration[] $global_references
	 */
	private function value(ast_expression $value, array &$global_references /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$required_headers /** array<string,bool> */,
		bool &$reorderable = false, bool $typed_initializer = false, bool $logical_root = false): string
	{
		$reorderable = false;
		$value_type = $value->type_def();
		if ($value_type instanceof enum_type_definition) $declarations[$value_type->declaration->symbol_key()] = $value_type->declaration;
		if ($value_type instanceof record_handle_definition || $value_type instanceof record_type_definition) {
			++$this->record_expressions;
		}
		foreach ($value_type->cpp_headers() as $header) {
			$required_headers[$header] = true;
		}
		if ($value instanceof ast_match_expression) {
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
		if ($value instanceof ast_construction_expression) {
			$type = $value->type_def();
			$shared = $value instanceof ast_new_expression;
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
			$receiver = $this->value($value->receiver, $global_references, $declarations, $required_headers);
			$access = $value->receiver->type_def() instanceof shared_record_type_definition ? '->' : '.';
			return '(' . $receiver . ')' . $access . Cpp_Identifier::source($value->field);
		}
		if ($value instanceof ast_keyed_collection_expression) {
			/** @var type_definition&keyed_initializer_type $type */
			$type = $value->type_def();
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
			$receiver = $this->value($value->receiver, $global_references, $declarations, $required_headers);
			$index = $this->value($value->index, $global_references, $declarations, $required_headers);
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
			/** @var carrier_type_definition $type */
			$type = $value->operand->type_def();
			return $type->lower_cpp_presence($this->value($value->operand, $global_references, $declarations, $required_headers));
		}
		if ($value instanceof ast_not_expression) {
			$expression = '!' . $this->condition($value->operand, $global_references, $declarations, $required_headers);
			return $logical_root ? $expression : '(' . $expression . ')';
		}
		if ($value instanceof ast_binary_expression) {
			if ($value->operator === binary_operator::coalesce) {
				/** @var carrier_type_definition $carrier */
				$carrier = $value->left->type_def();
				$steps = [];
				$left = $this->retained_place($value->left, $global_references, $declarations, $required_headers, $steps);
				$right = $this->value($value->right, $global_references, $declarations, $required_headers);
				return '([&]() -> ' . $this->signature_type($value_type, false) . ' { ' . implode(' ', $steps) . ' const auto& coalesce_value = ' . $left
					. '; if (' . $carrier->lower_cpp_presence('coalesce_value') . ') { return '
					. $carrier->lower_cpp_extract('coalesce_value') . '; } return ' . $right . '; }())';
			}
			if ($value->operator->logical()) {
				$left = $this->condition($value->left, $global_references, $declarations, $required_headers);
				$right = $this->condition($value->right, $global_references, $declarations, $required_headers);
				if (!$value->operator->lazy()) {
					if ($value->left instanceof ast_name_expression && $value->right instanceof ast_name_expression
						&& $value->left->declaration() instanceof ast_local_binding && $value->right->declaration() instanceof ast_local_binding
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
			$right = $this->value($value->right, $global_references, $declarations, $required_headers, $right_safe);
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
				return $type->lower_prepared_binary($value, $left, $right, $typed_initializer);
			}
			// Snapshot before RHS effects, including writes through reference parameters. Nested lambdas own their names.
			return '([&]() -> ' . $value->type_def()->cpp_type() . ' { const auto binary_left = ' . $left
				. '; const auto binary_right = ' . $right . '; return '
				. $type->lower_prepared_binary($value, 'binary_left', 'binary_right', $typed_initializer) . '; }())';
		}
		if ($value instanceof ast_call_expression) {
			if ($value->cardinality !== null) {
				$receiver = $value->arguments[0];
				/** @var counted_collection_type $type */
				$type = $receiver->type_def();
				return $type->lower_cpp_cardinality($value->cardinality,
					$this->value($receiver, $global_references, $declarations, $required_headers));
			}
			$function = $value->implementation();
			if ($function instanceof runtime_function) {
				$use = match ($function->name()) {
					'enum_from_value' => dependency_use::enum_from_value,
					'enum_name' => dependency_use::enum_name,
					'enum_value' => $value->type_def() === type_string::instance() ? dependency_use::enum_value : null,
					default => null,
				};
				if ($use !== null) {
					$type = $use === dependency_use::enum_from_value ? $value->type_def() : $value->arguments[0]->type_def();
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
				$operands = $value->runtime_operands();
				if ($function->receiver_method) {
					$arguments = [];
					$direct = true;
					foreach (array_slice($operands, 1) as $index => $argument) {
						$safe = false;
						$operand = $this->value($argument, $global_references, $declarations, $required_headers, $safe);
						$arguments[] = $function->parameters[$index + 1]->lower_cpp_exact_argument($argument, value_conversion::identity, $operand);
						$direct = $direct && ($safe || $argument instanceof ast_string_expression
							|| ($argument instanceof ast_name_expression && $argument->declaration() instanceof ast_local_binding));
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
					$call = '(' . $receiver . ').' . $symbol . '(' . implode(', ', $arguments) . ')';
					return $steps === [] ? $call : '([&]() { ' . implode(' ', $steps) . ' return ' . $call . '; }())';
				}
				if (count($operands) === 1 && !$function->overloaded_first) {
					$operand = $this->value($operands[0], $global_references, $declarations, $required_headers);
					$conversion = $value->conversion(0);
					if ($conversion !== value_conversion::identity) {
						$type = $function->parameters[0];
						foreach ($type->conversion_headers($conversion) as $header) $required_headers[$header] = true;
						$operand = $this->conversion($type, $conversion, $operand, $operands[0]);
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
						|| ($argument instanceof ast_name_expression && $argument->declaration() instanceof ast_local_binding));
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
				$call = $symbol . '(' . implode(', ', $arguments) . ')';
				return $steps === [] ? $call : '([&]() { ' . implode(' ', $steps) . ' return ' . $call . '; }())';
			}
			$name = self::function_name($function);
			$declarations[$name] = $function;
			return $this->invocation($value, $function, $name, $function->return_type_def(), $global_references, $declarations, $required_headers);
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
			$member = $this->qualified_operand($value->receiver, $member, $steps, $global_references, $declarations, $required_headers);
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
		$reorderable = $value->type_def()->cpp_trivial_value();
		$declaration = $value->declaration();
		if ($declaration instanceof ast_enum_case) {
			return $declaration->parent->definition->cpp_case($declaration);
		}
		if ($declaration instanceof runtime_integer_constant) {
			return $declaration->value . 'LL';
		}
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
		return $this->name($declaration);
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

	/**
	 * Select a receiver or writable field and retain its nearest enclosing allocation across operand effects.
	 * @param ast_global_declaration[] $globals
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $headers
	 * @param string[] $steps
	 */
	private function retained_place(ast_expression $receiver, array &$globals /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$headers /** array<string,bool> */,
		array &$steps /** list<string> */, ?bool &$inline_storage = null): string
	{
		$fields = [];
		$base = $receiver;
		while ($base instanceof ast_field_expression && !($base->type_def() instanceof shared_record_type_definition)) {
			$fields[] = Cpp_Identifier::source($base->field);
			$base = $base->receiver;
		}
		// Inline fields share the storage duration of their root. A temporary record
		// still needs the named receiver binding for the non-const reference parameter.
		$inline_storage = $base->type_def() instanceof record_type_definition
			&& ($base instanceof ast_this_expression || ($base instanceof ast_name_expression
				&& $base->declaration() instanceof ast_local_binding));
		$operand = $this->value($base, $globals, $declarations, $headers);
		if ($base->type_def() instanceof shared_record_type_definition) {
			$steps[] = 'auto receiver_owner = ' . $operand . ';';
			if ($fields === []) return '*receiver_owner.operator->()';
			$operand = 'receiver_owner->' . array_pop($fields);
		}
		foreach (array_reverse($fields) as $field) $operand .= '.' . $field;
		return $operand;
	}

	/**
	 * Emit one prepared source invocation with the ordinary conversion and left-to-right argument rules.
	 * @param ast_global_declaration[] $global_references
	 * @param ast_function_declaration[] $declarations
	 * @param bool[] $required_headers
	 */
	private function invocation(ast_call_expression|ast_construction_expression $value, ast_function_declaration $function,
		string $name, type_definition $result, array &$global_references /** array<string,ast_global_declaration> */,
		array &$declarations /** array<string,ast_function_declaration> */, array &$required_headers /** array<string,bool> */): string
	{
		$steps = [];
		$arguments = [];
		$method = $value instanceof ast_call_expression && $function->has_receiver();
		if ($value instanceof ast_call_expression && $value->expression_qualified()) {
			$name = $this->qualified_operand($value->receiver, $name, $steps, $global_references, $declarations, $required_headers);
		}
		if ($method && $function->parent instanceof ast_enum_declaration) {
			// Enum receivers are values; capture only when argument evaluation cannot be emitted directly.
			$receiver = $this->value($value->receiver, $global_references, $declarations, $required_headers);
			if ($function->parameters === []) return $name . '(' . $receiver . ')';
			$arguments[] = $receiver;
		} elseif ($method) {
			$inline_storage = $value->ancestor_dispatch;
			$place = $value->ancestor_dispatch ? 'scpp_self'
				: $this->retained_place($value->receiver, $global_references, $declarations, $required_headers, $steps, $inline_storage);
			if (!$inline_storage) {
				$steps[] = 'auto&& receiver_payload = ' . $place . ';';
				$place = 'receiver_payload';
			}
			if ($function->has_virtual_dispatch() || $value->interface_dispatch) $name = '(' . $place . ').'
				. ($value->ancestor_dispatch ? $function->parent->definition->cpp_type() . '::' : '') . self::virtual_name($function);
			else $arguments[] = $place;
		} elseif ($function->parameters === [] && $steps === []) {
			return $name . '()';
		}
		$direct_arguments = [];
		$direct = $steps === [];
		$has_reference = false;
		$order_sensitive = [];
		$inline_index = null;
		foreach ($function->parameters as $index => $parameter) {
			$argument = $value->arguments[$index] ?? $parameter->default_value;
			$conversion = isset($value->arguments[$index]) ? $value->conversion($index) : $parameter->default_conversion();
			$type = $parameter->type_def();
			$operand_safe = false;
			$records_before = $this->record_expressions;
			$operand = $this->value($argument, $global_references, $declarations, $required_headers, $operand_safe,
				logical_root: $conversion === value_conversion::identity);
			foreach ($type->conversion_headers($conversion) as $header) {
				$required_headers[$header] = true;
			}
			if ($parameter->mode === passing_mode::value) {
				$operand = $this->conversion($type, $conversion, $operand, $argument);
			}
			// Preparation already validated stable, exact storage for a reference parameter.
			if (!$method && $steps === [] && count($function->parameters) === 1) {
				$tag = $value instanceof ast_struct_default && self::self_value_constructor($function) ? '0, ' : '';
				return $name . '(' . $tag . $operand . ')';
			}
			$direct_arguments[] = $operand;
			$direct = $direct && $operand_safe && $type->cpp_trivial_value()
				&& ($conversion === value_conversion::identity) && ($parameter->mode === passing_mode::value);
			// A prepared reference binds a stable named slot without reading its value.
			if ($parameter->mode === passing_mode::reference) {
				$has_reference = true;
			} elseif (!self::inert_literal_argument($argument, $type, $conversion)) {
				$order_sensitive[$index] = true;
				$inline_index = $type->cpp_trivial_value() && $this->record_expressions === $records_before ? $index : null;
			}
		}
		if ($method && $function->parent instanceof ast_enum_declaration && !$direct && $order_sensitive !== []) {
			$steps[] = 'const auto enum_receiver = ' . $arguments[0] . ';';
			$arguments[0] = 'enum_receiver';
			$direct = false;
		}
		if ($direct || ($steps === [] && !$method && $value instanceof ast_call_expression && !$has_reference && count($order_sensitive) <= 1)) {
			return $name . '(' . implode(', ', $arguments ? [...$arguments, ...$direct_arguments] : $direct_arguments) . ')';
		}
		// Literal peers cannot observe effects. The last remaining scalar can run in the call
		// after its predecessors, unless its expression needs a record destruction boundary.
		foreach ($function->parameters as $index => $parameter) {
			$type = $parameter->type_def();
			$operand = $direct_arguments[$index];
			if (!isset($order_sensitive[$index]) || $index === $inline_index) {
				$arguments[] = $operand;
				continue;
			}
			// Position-derived names can shadow outer lambda snapshots during nested calls; use source positions.
			$temporary = 'argument_' . $value->start . '_' . $index;
			$steps[] = $type->cpp_type() . ' ' . $temporary . ' = ' . $operand . ';';
			$arguments[] = $type->cpp_trivial_value() ? $temporary
				: 'static_cast<' . $type->cpp_type() . '&&>(' . $temporary . ')';
		}
		$call = $name . '(' . implode(', ', $arguments) . ')';
		return $steps === [] ? $call : '([&]() -> ' . $result->cpp_type() . ' { ' . implode(' ', $steps)
			. ' return ' . $call . '; }())';
	}

	/** Calls and definitions use complete record types. @param (ast_declaration_interface|record_handle_definition)[] $declarations */
	private function collect_signature_types(ast_function_declaration $function, array &$declarations): void
	{
		foreach (($this->headers?->declaration_header($function) !== null
			? self::signature_application_types($function) : $this->application_types($function)) as $type) {
			if ($type instanceof application_collection_type) $this->concrete_collections->request($type);
			elseif ($type instanceof record_type_definition || $type instanceof enum_type_definition) $declarations[$type->declaration->symbol_key()] = $type->declaration;
			else $declarations[$type->name] = $type;
		}
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
			foreach ($constructor->parameters as $index => $parameter) {
				$argument = $site->arguments[$index] ?? $parameter->default_value;
				$conversion = isset($site->arguments[$index]) ? $site->conversion($index) : $parameter->default_conversion();
				$type = $parameter->type_def();
				foreach ($type->conversion_headers($conversion) as $header) $headers[$header] = true;
				$value = $this->value($argument, $globals, $declarations, $headers);
				$arguments[] = $this->conversion($type, $conversion, $value, $argument);
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
		return $parameter->mode === passing_mode::reference || $parameter->type_def()->cpp_trivial_value() ? $value
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
		$types = [];
		foreach ($record->definition->constructor()?->parameters ?? [] as $parameter) $types[] = $parameter->type_def();
		foreach (self::variance_bridges($record) as [$contract, $implementation]) {
			foreach (self::signature_application_types($contract) as $type) $types[] = $type;
		}
		foreach (array_merge($record->definition->effective_methods(), $record->definition->interface_forwards) as $method) {
			if (!$method->has_virtual_dispatch() && !isset($record->definition->interface_forwards[$method->short_name])) continue;
			foreach (self::signature_application_types($method) as $type) $types[] = $type;
		}
		foreach ($types as $type) {
			if ($type instanceof application_collection_type) {
				$forwards[$type->name] = 'class ' . $type->cpp_type() . ";\n";
			} elseif ($type instanceof enum_type_definition) {
				$forwards[$type->name] = self::namespace_body($type->cpp_namespace(), 'enum class '
					. Cpp_Identifier::source($type->declaration->short_name) . ' : ' . $type->storage()->cpp_type() . ";\n");
			} elseif ($type instanceof record_type_definition) {
				$forwards[$type->name] = self::namespace_body($type->cpp_namespace(), 'struct ' . Cpp_Identifier::source($type->declaration->short_name) . ";\n");
			} elseif ($type instanceof record_handle_definition) $forwards[$type->name] = 'struct ' . $type->cpp_type() . ";\n";
		}
		return implode('', $forwards);
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
		return $this->signature_type($function->return_type_def(), $short_types) . ' ' . ($function->has_virtual_dispatch() ? $function->parent->definition->cpp_type() . '::' . self::virtual_name($function) : self::function_name($function))
			. '(' . implode(', ', $parameters) . ')';
	}

	/** Payload member declarations live inside scpp_records; qualify application signature types. */
	private function member_type(type_definition $type): string
	{
		return ($type instanceof record_handle_definition || $type instanceof record_type_definition || $type instanceof enum_type_definition || $type instanceof application_collection_type)
			? '::' . ($this->root === '' ? '' : $this->root . '::') . $type->cpp_type() : $this->signature_type($type, false);
	}

	/** Share native parameter names, types, passing modes and includes. @param bool[] $headers @return string[] */
	private function parameters(ast_function_declaration $function, array &$headers /** array<string,bool> */, bool $short_types, bool $member = false): array /** list<string> */
	{
		$parameters = [];
		foreach ($function->parameters as $parameter) {
			$type = $parameter->type_def();
			$native = $this->signature_type($type, $short_types);
			if (($member || $function->is_constructor() || $function->has_virtual_dispatch()) && ($type instanceof record_handle_definition || $type instanceof record_type_definition || $type instanceof enum_type_definition)) {
				$native = '::' . ($this->root === '' ? '' : $this->root . '::') . $native;
			}
			$parameters[] = '[[maybe_unused]] ' . $native . ' '
				. ($parameter->mode === passing_mode::reference ? '&' : '') . $this->name($parameter);
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
	private static function has_exact_interface_slot(ast_record_declaration $record, ast_function_declaration $method): bool
	{
		foreach ($record->definition->interface_requirements($method->short_name) as $contract) {
			if (self::virtual_name($contract) === self::virtual_name($method)) return true;
		}
		return false;
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
		$variant = $result instanceof shared_record_type_definition;
		foreach ($method->parameters as $parameter) {
			if ($parameter->mode === passing_mode::value && $parameter->type_def() instanceof shared_record_type_definition) $variant = true;
		}
		if (!$variant) return 'scpp_virtual_' . $name;
		$parts = [$result->name];
		foreach ($method->parameters as $parameter) $parts[] = $parameter->mode->name . ':' . $parameter->type_def()->name;
		return 'scpp_slot_' . $name . '_' . bin2hex(implode("\0", $parts));
	}

	/** C++ reserves T(T) for copy construction; distinguish explicit source construction from memberwise copying. */
	private static function self_value_constructor(ast_function_declaration $constructor): bool
	{
		return count($constructor->parameters) === 1 && $constructor->parameters[0]->type_def() === $constructor->parent->definition;
	}

	/** Application types named by a callable signature. @return \Generator<type_definition> */
	public static function signature_application_types(ast_function_declaration $function): \Generator
	{
		if ($function->has_receiver() && $function->parent instanceof ast_enum_declaration) yield $function->parent->type_definition();
		$type = $function->return_type_def();
		if ($type instanceof generator_type_definition) {
			foreach ([$type->iteration_value_type(), $type->iteration_key_type()] as $output) {
				if ($output instanceof record_handle_definition || $output instanceof record_type_definition || $output instanceof enum_type_definition) yield $output;
			}
		}
		yield from self::signature_type_dependencies($type);
		foreach ($function->parameters as $parameter) {
			$type = $parameter->type_def();
			yield from self::signature_type_dependencies($type);
		}
	}

	/** A concrete signature needs its nominal element too; retain no additional dependency inventory. @return \Generator<type_definition> */
	private static function signature_type_dependencies(type_definition $type): \Generator
	{
		if ($type instanceof application_collection_type) {
			yield $type;
			foreach ($type->concrete_types() as $argument) yield from self::signature_type_dependencies($argument);
			return;
		}
		if ($type instanceof record_handle_definition || $type instanceof record_type_definition || $type instanceof enum_type_definition) yield $type;
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
		return ($argument instanceof ast_integer_expression || $argument instanceof ast_float_expression
			|| $argument instanceof ast_keyword_expression) && self::inert_scalar_conversion($type, $conversion);
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
	private static function plain_integer_literal(ast_expression $value, int|exit_conversion $conversion /** uint16|exit_conversion */): ?string
	{
		if (!($value instanceof ast_integer_expression)
			|| ($conversion !== value_conversion::identity && $conversion !== value_conversion::integer_proven
				&& $conversion !== exit_conversion::proven)) {
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
