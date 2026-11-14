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
	/** PROPERTIES */
	/** Native non-owning; null keeps standalone single-file emission self-contained. */
	private readonly ?Cpp_Headers $headers;
	private readonly string $root;
	/** Set while lowering a function that calls a generated flavor adapter. */
	private ?string $adapter_flavor = null;
	/** Count record/handle expressions during the existing walk to preserve observable temporary destruction. */
	private int $record_expressions = 0;

	/** CONSTRUCTOR */
	public function __construct(?Cpp_Headers $headers = null, string $root = 'App')
	{
		$this->headers = $headers;
		$this->root = $root;
	}

	/** METHODS */
	/** Share declaration spelling between local prototypes and generated headers. @param bool[] $includes */
	public function declaration(ast_function_declaration|ast_global_declaration|ast_type_declaration $node,
		array &$includes /** array<string,bool> */): string
	{
		if ($node instanceof ast_enum_declaration) {
			$type = $node->definition;
			$storage = $type->storage();
			foreach ($storage->cpp_headers() as $header) $includes[$header] = true;
			$lines = [];
			foreach ($node->cases as $case) {
				$lines[] = "\t" . Cpp_Identifier::source($case->short_name) . ' = '
					. type_int64::instance()->lower_cpp_literal($case->initializer) . ',';
			}
			return self::namespace_body($type->cpp_namespace(), 'enum class ' . Cpp_Identifier::source($node->short_name)
				. ' : ' . $storage->cpp_type() . " {\n" . implode("\n", $lines) . "\n};\n");
		}
		if ($node instanceof ast_struct_declaration) {
			$layout = [];
			foreach ($node->fields as $field) {
				foreach ($field->type_def()->cpp_headers() as $header) $includes[$header] = true;
				$type = $field->type_def();
				$qualifier = ($type instanceof struct_type_definition || $type instanceof struct_handle_definition) && $this->root !== '' ? '::' . $this->root : '';
				$default = $field->initializer === null ? '{}' : ' = ' . $this->literal_initialization(
					$type, $field->initializer, $field->conversion, $includes);
				$layout[] = "\t" . $qualifier . '::' . $type->cpp_type() . ' ' . Cpp_Identifier::source($field->short_name) . $default . ';';
			}
			foreach ($node->static_properties as $property) {
				$type = $property->type_def();
				foreach ($type->cpp_headers() as $header) $includes[$header] = true;
				$layout[] = "\tstatic " . $type->cpp_type() . ' ' . self::property_name($property) . ';';
			}
			if (isset($node->methods['__construct'])) {
				$constructor = $node->methods['__construct'];
				$params = $this->constructor_parameters($constructor, $includes);
				if (self::self_value_constructor($constructor)) $params = 'int, ' . $params;
				$name = Cpp_Identifier::source($node->short_name);
				$layout[] = "\t" . $name . '(' . $params . ');';
				if ($params !== '' && $constructor->accepts_argument_count(0)) $layout[] = "\t" . $name . '();';
			}
			return $this->constructor_forwards($node) . self::namespace_body($node->definition->cpp_namespace(), 'struct ' . Cpp_Identifier::source($node->short_name)
				. " {\n" . implode("\n", $layout) . "\n};\n");
		}
		if ($node instanceof ast_function_declaration) {
			return $this->signature($node, $includes) . ';';
		}
		foreach ($node->type_def()->cpp_headers() as $header) {
			$includes[$header] = true;
		}
		return 'extern ' . $this->signature_type($node->type_def(), false) . ' ' . self::global_name($node) . ';';
	}

	/** Lower an approved declaration literal and collect its public header dependencies. @param bool[] $headers */
	private function literal_initialization(type_definition $type, ast_expression $literal, int $conversion /** uint16 */,
		array &$headers /** array<string,bool> */): string
	{
		foreach (array_merge($literal->type_def()->cpp_headers(), $type->conversion_headers($conversion)) as $header) {
			$headers[$header] = true;
		}
		return self::plain_integer_literal($literal, $conversion)
			?? $type->lower_cpp_conversion($conversion, $literal->type_def()->lower_cpp_literal($literal), $literal);
	}

	/** Pointer-sized owner layout available before either the record or its counted allocation is complete. @param bool[] $includes */
	public function owner_declaration(shared_struct_type_definition $type, array &$includes /** array<string,bool> */): string
	{
		$payload = $type->payload;
		$name = Cpp_Identifier::source($payload->declaration->short_name);
		$forward = $this->constructor_forwards($payload->declaration) . self::namespace_body($payload->cpp_namespace(), "struct $name;\n");
		$owner = $type->cpp_type();
		$weak = $type->payload->weak->cpp_type();
		$record = $payload->cpp_type();
		$guard = $this->owner_guard($type) . '_DECL';
		$params = $this->constructor_parameters($payload->declaration->methods['__construct'] ?? null, $includes);
		return <<<CPP
#ifndef $guard
#define $guard
{$forward}struct $owner {
	struct allocation;
	allocation* ptr{};
	$owner() noexcept = default;
	static $owner make($params);
	explicit operator bool() const noexcept { return ptr != nullptr; }
	$record* operator->() const;
	$owner(const $owner& other) noexcept;
	$owner($owner&& other) noexcept : ptr(other.ptr) { other.ptr = nullptr; }
	$owner& operator=($owner other) noexcept {
		auto* old = ptr;
		ptr = other.ptr;
		other.ptr = old;
		return *this;
	}
	~$owner();
};
struct $weak {
	{$owner}::allocation* ptr{};
	$weak() noexcept = default;
	explicit $weak(const $owner& owner) noexcept : ptr(owner.ptr) { retain(); }
	$weak(const $weak& other) noexcept : ptr(other.ptr) { retain(); }
	$weak($weak&& other) noexcept : ptr(other.ptr) { other.ptr = nullptr; }
	$weak& operator=($weak other) noexcept {
		auto* old = ptr;
		ptr = other.ptr;
		other.ptr = old;
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
	public function owner_methods(shared_struct_type_definition $type, array &$includes /** array<string,bool> */): string
	{
		$payload = $type->payload->cpp_type();
		$owner = $type->cpp_type();
		$weak = $type->payload->weak->cpp_type();
		$guard = $this->owner_guard($type) . '_DEF';
		$constructor = $type->payload->declaration->methods['__construct'] ?? null;
		$params = $this->constructor_parameters($constructor, $includes);
		$allocation_ctor = $constructor === null ? 'allocation() : value{} {}' : "allocation($params);";
		$factory = $constructor === null ? $this->allocation_factory($type, $includes) : '';

		return <<<CPP
#ifndef $guard
#define $guard
struct {$owner}::allocation {
	using payload_type = $payload;
	union { payload_type value; };
	__UINT32_TYPE__ count{1};
	__UINT32_TYPE__ weak_count{1}; // Includes one hold until payload destruction completes.
	$allocation_ctor
	~allocation() {} // The last strong owner ends the active union member's lifetime.
	void retain_strong() noexcept {
		if (count == __UINT32_MAX__) __builtin_trap();
		++count;
	}
	void release_weak() noexcept { if (--weak_count == 0) delete this; }
};
$factory
inline $payload* {$owner}::operator->() const {
	if (!ptr) ::scpp::throw_empty_owner();
	return &ptr->value;
}
inline {$owner}::$owner(const $owner& other) noexcept : ptr(other.ptr) {
	if (ptr) ptr->retain_strong();
}
inline {$owner}::~$owner() {
	if (ptr && --ptr->count == 0) {
		using payload_type = $payload;
		ptr->value.~payload_type();
		ptr->release_weak();
	}
}
inline void {$weak}::retain() noexcept {
	if (ptr) {
		if (ptr->weak_count == __UINT32_MAX__) __builtin_trap();
		++ptr->weak_count;
	}
}
inline $owner {$weak}::lock() const noexcept {
	$owner result;
	if (ptr && ptr->count) {
		ptr->retain_strong();
		result.ptr = ptr;
	}
	return result;
}
inline {$weak}::~$weak() { if (ptr) ptr->release_weak(); }
#endif

CPP;
	}

	/** Define user-directed allocation once beside the payload constructor, where parameter types are complete. @param bool[] $headers */
	private function allocation_factory(shared_struct_type_definition $type, array &$headers /** array<string,bool> */): string
	{
		$owner = $type->cpp_type();
		$constructor = $type->payload->declaration->methods['__construct'] ?? null;
		$params = $this->constructor_parameters($constructor, $headers);
		$args = [];
		foreach ($constructor?->parameters ?? [] as $parameter) {
			$name = $this->name($parameter);
			$args[] = $parameter->type_def()->cpp_trivial_value() ? $name : 'static_cast<' . $parameter->type_def()->cpp_type() . '&&>(' . $name . ')';
		}
		$forward = implode(', ', $args);
		$payload_args = $constructor !== null && self::self_value_constructor($constructor) ? '0, ' . $forward : $forward;
		$prefix = $constructor === null ? 'inline ' : "{$owner}::allocation::allocation($params) : value($payload_args) {}\n";
		return "{$prefix}$owner {$owner}::make($params) {\n\t$owner result;\n\tresult.ptr = new allocation($forward);\n\treturn result;\n}\n";
	}

	/** Public layouts and unit-local uses can independently request the same concrete owner. */
	private function owner_guard(shared_struct_type_definition $type): string
	{
		return 'SCPP_OWNER_' . hash('sha256', $this->root . "\0" . $type->name);
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

	private function render(prepared_entry $program, string $function): string
	{
		$program->require_prepared();
		return $this->unit($program, $program->file, $function);
	}

	/** Assemble local definitions and deterministic forward declarations without a shared naming registry. */
	private function unit(?prepared_entry $entry, ast_file_node $file, string $entry_name): string
	{
		$declarations = [];
		$definitions = [];
		$global_references = [];
		$required_headers = [];
		foreach ($file->globals() as $global) {
			$type = $global->type_def();
			$initializer = $this->literal_initialization($type, $global->initializer, $global->initialization(), $required_headers);
			$qualifier = $global->initializer instanceof ast_string_expression ? '' : 'constinit ';
			$definitions[] = $qualifier . $type->cpp_type() . ' ' . self::global_name($global)
				. ' = ' . $initializer . ";\n";
			foreach ($type->cpp_headers() as $header) {
				$required_headers[$header] = true;
			}
		}
		foreach ($file->declaration_nodes as $node) {
			if ($node instanceof ast_type_declaration) $declarations[$node->symbol_key()] = $node;
		}
		$unit_flavor = null;
		foreach ($file->functions() as $function) {
			$name = self::function_name($function);
			if ($function->is_constructor()) {
				$owner = $function->parent->definition->shared;
				$declarations[$owner->name] = $owner;
				$this->collect_signature_types($function, $declarations);
				$definitions[] = $this->allocation_factory($owner, $required_headers);
				// Embedded fields use native {}. Keep the literal defaults in their defining unit.
				if ($function->parameters !== [] && $function->accepts_argument_count(0)) {
					$defaults = [];
					foreach ($function->parameters as $parameter) {
						$defaults[] = $this->literal_initialization($parameter->type_def(), $parameter->default_value,
							$parameter->default_conversion(), $required_headers);
					}
					$name = Cpp_Identifier::source($function->parent->short_name);
					$definitions[] = $owner->payload->cpp_type() . '::' . $name . '() : ' . $name . '(' . implode(', ', $defaults) . ") {}\n";
				}
			} else {
				$declarations[$name] = $function;
			}
			$this->adapter_flavor = null;
			$lines = $this->statements($function->body(), $declarations, $global_references, $required_headers);
			$unit_flavor ??= $this->adapter_flavor;
			if ($function->is_constructor()) array_unshift($lines, "\t[[maybe_unused]] auto& scpp_self = *this;");
			$definitions[] = $this->signature($function, $required_headers, $this->adapter_flavor !== null && $this->root !== '')
				. "\n{\n" . $this->local_imports() . implode("\n", $lines) . "\n}\n";
		}
		if ($entry !== null) {
			$this->adapter_flavor = null;
			$lines = $this->statements($entry->body(), $declarations, $global_references, $required_headers);
			$unit_flavor ??= $this->adapter_flavor;
			$definitions[] = 'int ' . $entry_name . "()\n{\n" . $this->local_imports() . implode("\n", $lines) . "\n}\n";
		}
		$prototypes = '';
		$unit_headers = [];
		// Calls and definitions need complete value and handle types even when only the signature names them.
		foreach ($declarations as $function) {
			if ($function instanceof ast_function_declaration) {
				$this->collect_signature_types($function, $declarations);
			}
		}
		foreach ($global_references as $property) {
			if ($property->parent instanceof ast_struct_declaration) $declarations[$property->parent->symbol_key()] = $property->parent;
		}
		ksort($declarations, \SORT_STRING);
		$records = [];
		$layouts = '';
		$owners = [];
		foreach ($declarations as $node) {
			if (!($node instanceof ast_enum_declaration)) continue;
			$header = $this->headers?->declaration_header($node);
			if ($header !== null) $unit_headers[$header] = true;
			else $layouts .= $this->declaration($node, $required_headers) . "\n";
		}
		foreach ($declarations as $function) {
			if ($function instanceof ast_enum_declaration) continue;
			if ($function instanceof struct_handle_definition) {
				$owners[$function->payload->name] = $function->payload->shared;
				continue;
			}
			if ($function instanceof ast_struct_declaration) {
				$this->record_prototypes($function, $records, $unit_headers, $required_headers, $layouts, $owners);
				continue;
			}
			$include = $this->headers?->declaration_header($function);
			if ($include !== null) {
				$unit_headers[$include] = true;
			}
			else {
				$prototypes .= $this->declaration($function, $required_headers) . "\n";
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
				$this->record_prototypes($owner->payload->declaration, $records, $unit_headers, $required_headers, $layouts, $owners);
				$owner_types .= $this->owner_declaration($owner, $required_headers);
				$owner_methods .= $this->owner_methods($owner, $required_headers);
				$required_headers['scpp/owner_access.hpp'] = true;
			}
		}
		ksort($global_references, \SORT_STRING);
		foreach ($global_references as $name => $global) {
			if ($global->parent instanceof ast_struct_declaration) continue;
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
		return $header . self::namespace_body($this->root, $imports . $owner_types . $layouts . $owner_methods . $prototypes . implode("\n", $definitions));
	}

	/**
	 * Emit private definitions once, with complete embedded types before their parents.
	 * @param bool[] $seen
	 * @param bool[] $unit_headers
	 * @param shared_struct_type_definition[] $owners
	 * @param bool[] $runtime_headers
	 */
	private function record_prototypes(ast_struct_declaration $record, array &$seen /** array<string,bool> */,
		array &$unit_headers /** array<string,bool> */, array &$runtime_headers /** array<string,bool> */, string &$text,
		array &$owners /** array<string,shared_struct_type_definition> */): void
	{
		$key = $record->symbol_key();
		if (isset($seen[$key])) return;
		$seen[$key] = true;
		$header = $this->headers?->declaration_header($record);
		if ($header !== null) {
			$unit_headers[$header] = true;
			return;
		}
		foreach ($record->fields as $field) {
			$type = $field->type_def();
			if ($type instanceof struct_type_definition) {
				$this->record_prototypes($type->declaration, $seen, $unit_headers, $runtime_headers, $text, $owners);
			} elseif ($type instanceof struct_handle_definition) {
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
	 */
	private function statements(ast_statement_block $body, array &$declarations /** array<string,ast_function_declaration> */,
		array &$global_references /** array<string,ast_global_declaration> */,
		array &$required_headers /** array<string,bool> */, ?ast_function_declaration $function = null): array /** list<string> */
	{
		if ($body instanceof ast_function_body) {
			$body->require_prepared();
			/** @var ast_function_declaration $function */
			$function = $body->parent;
		}
		$lines = [];
		foreach ($body->statements as $statement)
		{
			if (($statement instanceof ast_break_statement) || ($statement instanceof ast_continue_statement)) {
				$lines[] = "\t" . ($statement instanceof ast_break_statement ? 'break;' : 'continue;');
			}
			elseif ($statement instanceof ast_loop_statement) {
				$condition = $this->condition($statement->condition, $global_references, $declarations, $required_headers);
				$post_test = $statement instanceof ast_do_while_statement;
				$update_snapshot = null;
				if ($statement instanceof ast_for_statement) {
					$initializer = $this->binding($statement->initializer, $global_references, $declarations, $required_headers, $init_snapshot);
					if ($init_snapshot !== null) {
						$lines[] = "\t{ const auto " . $init_snapshot . '; ' . $initializer . '; }';
						$initializer = '';
					}
					$update = $this->binding($statement->update, $global_references, $declarations, $required_headers, $update_snapshot);
					if ($update_snapshot !== null) {
						$lines[] = "\t{ " . $statement->update->local_def()->type_def()->cpp_type() . ' compound_previous;';
						$update = '(' . $update_snapshot . ', ' . $update . ')';
					}
					$lines[] = "\tfor (" . $initializer . '; ' . $condition . '; ' . $update . ') {';
				}
				else {
					$lines[] = "\t" . ($post_test ? 'do' : 'while (' . $condition . ')') . ' {';
				}
				foreach ($this->statements($statement->body, $declarations, $global_references, $required_headers, $function) as $line) {
					$lines[] = "\t" . $line;
				}
				$lines[] = "\t}" . ($post_test ? ' while (' . $condition . ');' : '');
				if ($update_snapshot !== null) {
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
					foreach ($this->statements($arm->body, $declarations, $global_references, $required_headers, $function) as $line) {
						$lines[] = "\t" . $line;
					}
					$lines[] = "\t}";
				}
			}
			elseif ($statement instanceof ast_field_assignment) {
				$field = $this->value($statement->target, $global_references, $declarations, $required_headers);
				$value = $this->value($statement->value, $global_references, $declarations, $required_headers);
				$type = $statement->target->type_def();
				foreach ($type->conversion_headers($statement->conversion) as $header) $required_headers[$header] = true;
				$lines[] = "\t" . $field . ' = ' . $type->lower_cpp_conversion($statement->conversion, $value, $statement->value) . ';';
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
				$store = $type->lower_cpp_store($statement, $receiver, $key, $value);
				$lines[] = "\t" . ($steps === [] ? $store : '{ ' . implode(' ', $steps) . ' ' . $store . ' }');
			}
			elseif ($statement instanceof ast_append_statement) {
				/** @var appendable_collection_type $type */
				$type = $statement->receiver->type_def();
				$receiver = $this->value($statement->receiver, $global_references, $declarations, $required_headers);
				$value = $this->value($statement->value, $global_references, $declarations, $required_headers);
				$conversion = $statement->conversion();
				foreach ($type->write_type()->conversion_headers($conversion) as $header) {
					$required_headers[$header] = true;
				}
				$lines[] = "\t" . $type->lower_cpp_append($statement, $receiver, $value) . ';';
			}
			elseif (($statement instanceof ast_local_declaration) || ($statement instanceof ast_storage_write)) {
				$write = $this->binding($statement, $global_references, $declarations, $required_headers, $snapshot);
				$lines[] = "\t" . ($snapshot === null ? $write . ';' : '{ const auto ' . $snapshot . '; ' . $write . '; }');
			}
			elseif (($statement instanceof ast_return_statement) && ($statement->value !== null)) {
				$conversion = $statement->conversion();
				if ($conversion instanceof exit_conversion) {
					$value = self::plain_integer_literal($statement->value, $conversion)
						?? Cpp_Exit::expression($conversion,
							$this->value($statement->value, $global_references, $declarations, $required_headers));
				}
				else {
					$value = $this->value($statement->value, $global_references, $declarations, $required_headers,
						logical_root: $conversion === value_conversion::identity);
					$type = $function->return_type_def();
					$value = $type->lower_cpp_conversion($conversion, $value, $statement->value);
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
				$lines[] = "\treturn;";
			}
			elseif ($statement instanceof ast_call_statement) {
				$lines[] = "\t" . $this->value($statement->value, $global_references, $declarations, $required_headers) . ';';
			}
			else {
				throw new \LogicException('Unhandled prepared statement');
			}
		}
		return $lines;
	}

	private function name(ast_local_binding $binding): string
	{
		return 'var_' . self::name_component($binding->declaration_key()) . '_' . $binding->storage_id();
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
		$property = $statement instanceof ast_property_compound;
		$destination = $property ? $statement->target : $statement->local_def();
		$type = $destination->type_def();
		$simple_rhs = $statement->value instanceof ast_integer_expression || ($statement->value instanceof ast_name_expression
			&& $statement->value->declaration() instanceof ast_local_binding);
		$steps = [];
		if ($destination instanceof ast_field_expression && !$simple_rhs) {
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
		if ($conversion !== value_conversion::identity) {
			$right = $execution->lower_cpp_conversion($conversion, $right, $statement->value);
			foreach ($execution->conversion_headers($conversion) as $header) {
				$headers[$header] = true;
			}
		}
		foreach ($execution->binary_headers($statement->operator) as $header) {
			$headers[$header] = true;
		}
		$direct_read = $simple_rhs && ($destination instanceof ast_field_expression
			|| ($destination instanceof ast_local_binding && $type->cpp_trivial_value()));
		$left = $direct_read ? $name : 'compound_previous';
		if ($statement->left_conversion() !== value_conversion::identity) {
			$left = $execution->lower_cpp_conversion($statement->left_conversion(), $left, source_type: $type);
			foreach ($execution->conversion_headers($statement->left_conversion()) as $header) {
				$headers[$header] = true;
			}
		}
		$result = $execution->lower_cpp_binary($statement->operator, $left, $right, $execution);
		if ($statement->write_back() !== value_conversion::identity) {
			$result = $type->lower_cpp_conversion($statement->write_back(), $result, source_type: $execution);
			foreach ($type->conversion_headers($statement->write_back()) as $header) {
				$headers[$header] = true;
			}
		}
		if ($direct_read) return $name . ' = ' . $result;
		if ($steps !== []) {
			$steps[] = 'const auto compound_previous = ' . $name . ';';
			$steps[] = $name . ' = ' . $result . ';';
			return "{\n\t\t" . implode("\n\t\t", $steps) . "\n\t}";
		}
		$snapshot = 'compound_previous = ' . $name;
		return $name . ' = ' . $result;
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
		if (($statement instanceof ast_assignment_statement) && ($statement->operator !== null)) {
			return $this->compound($statement, $global_references, $declarations, $headers, $snapshot);
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
			if ($type instanceof struct_type_definition) {
				$declarations[$type->declaration->symbol_key()] = $type->declaration;
			}
			if ($type instanceof struct_handle_definition) $declarations[$type->name] = $type;
			$conversion = $statement->initialization();
			$value = $statement->initializer;
			$prefix = '[[maybe_unused]] ' . $type->cpp_type() . ' ' . $this->name($statement);
			foreach ($type->cpp_headers() as $header) {
				$headers[$header] = true;
			}
			$literal = self::plain_integer_literal($value, $conversion);
			if ($literal !== null) {
				return $prefix . ' = ' . $literal;
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
		return $prefix . ' = ' . $type->lower_cpp_conversion($conversion, $operand, $value);
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
		if ($value_type instanceof struct_handle_definition || $value_type instanceof struct_type_definition) {
			++$this->record_expressions;
		}
		foreach ($value_type->cpp_headers() as $header) {
			$required_headers[$header] = true;
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
			$constructor = $record->declaration->methods['__construct'] ?? null;
			if ($constructor === null) return $type->cpp_type() . '::make()';
			$this->collect_signature_types($constructor, $declarations);
			return $this->invocation($value, $constructor, $type->cpp_type() . ($shared ? '::make' : ''), $type,
				$global_references, $declarations, $required_headers);
		}
		if ($value instanceof ast_field_expression) {
			$receiver = $this->value($value->receiver, $global_references, $declarations, $required_headers);
			$access = $value->receiver->type_def() instanceof shared_struct_type_definition ? '->' : '.';
			return '(' . $receiver . ')' . $access . Cpp_Identifier::source($value->field);
		}
		if ($value instanceof ast_empty_collection_expression) {
			/** @var indexed_collection_type $type */
			$type = $value->type_def();
			return $type->lower_cpp_empty();
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
			return '(' . $type->lower_cpp_conversion($conversion, $operand, $value->operand) . ')';
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
				$left = $type->lower_cpp_conversion($value->left_conversion, $left, $value->left);
				foreach ($type->conversion_headers($value->left_conversion) as $header) {
					$required_headers[$header] = true;
				}
			}
			if ($value->right_conversion !== value_conversion::identity) {
				$right = $type->lower_cpp_conversion($value->right_conversion, $right, $value->right);
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
			$function = $value->function_def();
			if ($function instanceof runtime_function) {
				$required_headers[$function->header] = true;
				$symbol = $function->symbol;
				if ($function->adapter_flavor !== null) {
					$this->adapter_flavor = $function->adapter_flavor;
					$short = substr($symbol, strrpos($symbol, '::') + 2);
					// Generated app declarations and locals are encoded. A namespace component can still hide the spelling.
					$symbol = in_array($short, explode('::', $this->root), true) ? '::' . $symbol : $short;
				}
				$operands = $value->runtime_operands();
				if (count($operands) === 1 && !$function->overloaded_first) {
					return $symbol . '(' . $this->value($operands[0], $global_references, $declarations, $required_headers) . ')';
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
			$reorderable = $value->type_def()->cpp_trivial_value();
			$declaration = $value->declaration();
			if ($declaration instanceof ast_enum_case) {
				return $declaration->parent->definition->cpp_type() . '::' . Cpp_Identifier::source($declaration->short_name);
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
				return $type->lower_cpp_conversion($conversion, $literal, $declaration->initializer);
			}
			if ($declaration instanceof ast_global_declaration) {
				$name = self::global_name($declaration);
				$global_references[$name] = $declaration;
				return '::' . ($this->root === '' ? '' : $this->root . '::') . $name;
			}
			return $this->name($declaration);
		}
		throw new \LogicException('Unhandled prepared value');
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
		while ($base instanceof ast_field_expression && !($base->type_def() instanceof shared_struct_type_definition)) {
			$fields[] = Cpp_Identifier::source($base->field);
			$base = $base->receiver;
		}
		// Inline fields share the storage duration of their root. A temporary record
		// still needs the named receiver binding for the non-const reference parameter.
		$inline_storage = $base->type_def() instanceof struct_type_definition
			&& ($base instanceof ast_this_expression || ($base instanceof ast_name_expression
				&& $base->declaration() instanceof ast_local_binding));
		$operand = $this->value($base, $globals, $declarations, $headers);
		if ($base->type_def() instanceof shared_struct_type_definition) {
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
		if ($method) {
			$inline_storage = false;
			$place = $this->retained_place($value->receiver, $global_references, $declarations, $required_headers, $steps, $inline_storage);
			if (!$inline_storage) {
				$steps[] = 'auto&& receiver_payload = ' . $place . ';';
				$place = 'receiver_payload';
			}
			$arguments[] = $place;
		} elseif ($function->parameters === []) {
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
				$operand = $type->lower_cpp_conversion($conversion, $operand, $argument);
			}
			// Preparation already validated stable, exact storage for a reference parameter.
			if (!$method && count($function->parameters) === 1) {
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
		if ($direct || (!$method && $value instanceof ast_call_expression && !$has_reference && count($order_sensitive) <= 1)) {
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

	/** Calls and definitions use complete record types. @param (ast_declaration_interface|struct_handle_definition)[] $declarations */
	private function collect_signature_types(ast_function_declaration $function, array &$declarations): void
	{
		foreach (self::signature_application_types($function) as $type) {
			if ($type instanceof struct_type_definition || $type instanceof enum_type_definition) $declarations[$type->declaration->symbol_key()] = $type->declaration;
			else $declarations[$type->name] = $type;
		}
	}

	/** Parameter types need only forward declarations in the native payload and owner declarations. */
	private function constructor_forwards(ast_struct_declaration $record): string
	{
		$forwards = [];
		foreach ($record->methods['__construct']->parameters ?? [] as $parameter) {
			$type = $parameter->type_def();
			if ($type instanceof enum_type_definition) {
				$forwards[$type->name] = self::namespace_body($type->cpp_namespace(), 'enum class '
					. Cpp_Identifier::source($type->declaration->short_name) . ' : ' . $type->storage()->cpp_type() . ";\n");
			} elseif ($type instanceof struct_type_definition) {
				$forwards[$type->name] = self::namespace_body($type->cpp_namespace(), 'struct ' . Cpp_Identifier::source($type->declaration->short_name) . ";\n");
			} elseif ($type instanceof struct_handle_definition) $forwards[$type->name] = 'struct ' . $type->cpp_type() . ";\n";
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
		$parameters = $this->parameters($function, $headers, $short_types);
		if ($function->is_constructor()) {
			if (self::self_value_constructor($function)) array_unshift($parameters, 'int');
			return $function->parent->definition->cpp_type() . '::' . Cpp_Identifier::source($function->parent->short_name)
				. '(' . implode(', ', $parameters) . ')';
		}
		if ($function->has_receiver()) {
			array_unshift($parameters, '[[maybe_unused]] ' . $function->parent->definition->cpp_type() . '& scpp_self');
		}
		foreach ($function->return_type_def()->cpp_headers() as $header) $headers[$header] = true;
		return $this->signature_type($function->return_type_def(), $short_types) . ' ' . self::function_name($function)
			. '(' . implode(', ', $parameters) . ')';
	}

	/** Share native parameter names, types, passing modes and includes. @param bool[] $headers @return string[] */
	private function parameters(ast_function_declaration $function, array &$headers /** array<string,bool> */, bool $short_types): array /** list<string> */
	{
		$parameters = [];
		foreach ($function->parameters as $parameter) {
			$type = $parameter->type_def();
			$native = $this->signature_type($type, $short_types);
			if ($function->is_constructor() && ($type instanceof struct_handle_definition || $type instanceof struct_type_definition)) {
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
	/** C++ reserves T(T) for copy construction; distinguish explicit source construction from memberwise copying. */
	private static function self_value_constructor(ast_function_declaration $constructor): bool
	{
		return count($constructor->parameters) === 1 && $constructor->parameters[0]->type_def() === $constructor->parent->definition;
	}

	/** Application types named by a callable signature. @return \Generator<struct_handle_definition|struct_type_definition|enum_type_definition> */
	public static function signature_application_types(ast_function_declaration $function): \Generator
	{
		$type = $function->return_type_def();
		if ($type instanceof struct_handle_definition || $type instanceof struct_type_definition || $type instanceof enum_type_definition) yield $type;
		foreach ($function->parameters as $parameter) {
			$type = $parameter->type_def();
			if ($type instanceof struct_handle_definition || $type instanceof struct_type_definition || $type instanceof enum_type_definition) yield $type;
		}
	}

	/** Follow only prepared casts of literal values and immutable constant declarations. */
	private static function constant_compound_operand(ast_expression $value): bool
	{
		while ($value instanceof ast_cast_expression) {
			if (!self::inert_scalar_conversion($value->type_def(), $value->conversion())) return false;
			$value = $value->operand;
		}
		if ($value instanceof ast_name_expression) {
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
		if ($declaration->parent instanceof ast_struct_declaration) {
			return $declaration->parent->definition->cpp_type() . '::' . self::property_name($declaration);
		}
		return 'var_' . self::qualified_name($declaration);
	}

	public static function function_name(ast_function_declaration $declaration): string
	{
		if ($declaration->parent instanceof ast_struct_declaration) {
			$owner = self::qualified_name($declaration->parent);
			return 'method_' . strlen($owner) . '_' . $owner . '_' . self::name_component($declaration->short_name);
		}
		return self::qualified_name($declaration) . '_';
	}
}
