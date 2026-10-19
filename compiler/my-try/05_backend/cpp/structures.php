<?php

/* C++ representation data stays separate from canonical language type identity. */
namespace scpp\compiler;

enum cpp_literal_kind {
	case signed_integer;
	case boolean;
	case floating;
	case string_value;
	case none;
}

/** Required visibility at a C++ use site, not a semantic type capability. */
enum cpp_type_requirement {
	case declaration;
	case complete;
}

/** Representation choices only; native optimization/debug belong to the native builder. */
final class cpp_lowering_options
{
	public bool $prelower_templates = false;
	/** Opt-in runtime ABI integration; accounting recipes remain a comparison baseline. */
	public bool $runtime_template_abi = false;
	/** Temporary integration selector; replaces comparison paths after coverage parity. */
	public bool $concrete_type_substitution = false;
}

/** Engine-owned output parts; references only carry the entry position.
 * A member entry contributes declaration/implementation to member_owner's class/header.
 * A class entry keeps its closing declaration_tail separate for demand-time assembly.
 */
final class cpp_specialization_entry
{
	public function __construct(public readonly string $name, public readonly string $header,
		public readonly string $declaration, public readonly string $implementation,
		public readonly array $layout_dependencies /** hash<bool> */, // Headers required by the native layout probe.
		public readonly string $layout_declarations = '', public readonly string $layout_statements = '',
		public readonly string $declaration_tail = '', public readonly string $member_owner = '',
		public readonly string $declaration_prefix = '')
	{
	}
}

// Invocation-local entry ID, written only by the engine; never a durable identity.
/** @scpp-struct */
final class cpp_specialization_ref
{
	public int $entry_id /** uint32 */ = 0;
}

final class cpp_type {
	public ?canonical_type_use $specialization = null;
	public bool $opaque = false;
	public string $spelling;
	public array $headers /** hash<bool> */ = [];
	public array $forward_declarations /** hash<bool> */ = [];
	public cpp_literal_kind $literal;
}

/** Transient result of flattening one assignment step into surrounding statements. */
final class cpp_assignment_sequence
{
	public string $statements = '';
	public string $value;
}

enum cpp_artifact_kind {
	case translation_unit;
	case header;
	case layout_probe;
}

/** One final artifact; its logical identity is independent of storage position. */
final class cpp_artifact {
	/** Assembly-only generated header dependencies; copied from emission facts, never parsed from text. */
	public array $header_dependencies /** hash<bool> */ = [];
	public readonly string $identity;
	private cpp_artifact_kind $artifact_kind;
	public readonly string $file_name;
	public readonly string $text;

	public function __construct(string $identity, cpp_artifact_kind $artifact_kind, string $file_name, string $text)
	{
		if (($identity === '') || ($file_name === '')) {
			throw new \InvalidArgumentException('C++ artifact identity and path must not be empty');
		}
		$this->identity = $identity;
		$this->artifact_kind = $artifact_kind;
		$this->file_name = $file_name;
		$this->text = $text;
	}

	public function kind_name(): string
	{
		if ($this->artifact_kind === cpp_artifact_kind::layout_probe) { return 'layout_probe'; }
		return $this->artifact_kind === cpp_artifact_kind::header ? 'header' : 'translation_unit';
	}

	public function kind(): cpp_artifact_kind
	{
		return $this->artifact_kind;
	}
}

/** Invocation-local progress for ordering complete by-value struct definitions. */
enum cpp_record_state {
	case visiting;
	case complete;
}

/** One emission invocation; syntax and prepared facts never retain it. */
final class cpp_generation_context
{
	/** Native source payloads share ordinary bindings; calls back into application ABI need transport. */
	public bool $native_source = false;
	/** Middleware expressions use native outer implementations with concrete physical arguments. */
	public ?CPP_Specializations $implementation_engine = null;
	public array $specializations /** hash<canonical_type_use,int> */ = [];
	public array $runtime_operations /** hash<cpp_runtime_operation> */ = [];
	public function __construct(public ?CPP_Specializations $specialization_engine = null)
	{
	}
	public function concrete_types(): bool
	{
		if ($this->specialization_engine === null) {
			return false;
		}
		return $this->specialization_engine->concrete_types();
	}
	public function runtime_abi(): bool
	{
		if ($this->specialization_engine === null) { return false; }
		return $this->specialization_engine->runtime_abi();
	}
	public array $compiler_helpers /** hash<prepared_compiler_call> */ = [];
	public ?breakable_node $break_target = null;
	public ?loop_node $continue_target = null;
	public array $headers /** hash<bool> */ = [];
	public array $forward_declarations /** hash<bool> */ = [];
	/** Scoped source names are stable across token movement. */
	public array $record_states /** hash<cpp_record_state> */ = [];

	/** Output sections are assembled after traversal so declarations precede all uses. */
	public string $records = '';

	/** Null selects native main-return spelling; a function uses its prepared return type. */
	public ?canonical_type_use $return_type = null;
	/** Invocation-local allocation keeps nested argument temporaries distinct. */
	public int $next_temporary = 0;
}

/** One independently replaceable C++ fragment; no references to transient generation workers. */
final class cpp_fragment
{
	public array $specializations /** hash<canonical_type_use,int> */ = [];
	public array $runtime_operations /** hash<cpp_runtime_operation> */ = [];
	/** Used helper requests survive cached body reuse; assembly deduplicates live fragments. */
	public array $compiler_helpers /** hash<prepared_compiler_call> */ = [];
	public change_state $change_status = change_state::added;
	/** Completion version is meaningful only for an unchanged fragment. */
	public int $version = 0;
	public string $text = '';
	/** Function declaration spelling, including defaults; text remains the definition signature. */
	public string $prototype_text = '';
	/** Constructor initialization depends on executable body facts, not only the signature. */
	public string $initializer_text = '';
	/** Type-owned storage definitions belong to the declaring source implementation unit. */
	public string $storage_text = '';
	public array $headers /** hash<bool> */ = [];
	public array $forward_declarations /** hash<bool> */ = [];
	/** Definition signatures can need layouts that their public prototypes do not. */
	public array $definition_headers /** hash<bool> */ = [];
	/** Declaration dependencies needed before a value-record definition. */
	public Storage $records /** Storage<collected_record> */;

	public function __construct()
	{
		$this->records = new Storage /** Storage<collected_record> */();
	}
}

/** Retained fragments are keyed by independent signature/body preparation identities. */
final class cpp_program {
	public bool $prelower_templates = false;
	/** Opt-in runtime ABI integration; accounting recipes remain a comparison baseline. */
	public bool $runtime_template_abi = false;
	/** Temporary integration selector; replaces comparison paths after coverage parity. */
	public bool $concrete_type_substitution = false;
	public \SplObjectStorage $fragments /** hash<cpp_fragment, shared<preparation_owner>> */;

	public function __construct()
	{
		$this->fragments = new \SplObjectStorage /** hash<cpp_fragment, shared<preparation_owner>> */();
	}
}

/** Backend recipes retain settled identities, never syntax or generation workers. */
enum cpp_runtime_operation_kind {
	case method_call;
	case concrete_member;
	case concrete_index;
	case concrete_iteration;
	case receiver_check;
	case construct_default;
	case construct_call;
	case observation;
	case runtime_call;
	case initialize_empty;
	case initialize_buffer;
	case binary;
	case unary;
	case conversion;
	case index_read;
	case index_write;
	case write_key;
}

final class cpp_runtime_operation
{
	public ?prepared_call $call = null;
	public ?prepared_iteration $iteration = null;
	public ?prepared_object_construction $construction = null;
	public function kind_name(): string { return enum_name($this->kind); }
	public Storage $inputs /** Storage<canonical_type_use> */;
	public ?canonical_type_use $result = null;
	public ?cpp_function_binding $binding = null;
	public ?initialization_binding $initialization = null;
	public ?operator_decision $decision = null;
	public ?conversion_decision $conversion = null;
	public string $spelling = '';
	public bool $append = false;
	/** Path element types, without the source key syntax retained by prepared paths. */
	public Storage $path /** Storage<canonical_type_use> */;
	public function __construct(public cpp_runtime_operation_kind $kind)
	{
		$this->inputs = new Storage /** Storage<canonical_type_use> */();
		$this->path = new Storage /** Storage<canonical_type_use> */();
	}
}

/** Invocation-local physical operand; this record has no source ownership semantics. */
final class cpp_runtime_operand
{
	public function __construct(public canonical_type_use $type, public string $expression) {}
}
