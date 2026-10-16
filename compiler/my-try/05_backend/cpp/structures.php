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

final class cpp_type {
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
}

/** One final artifact; its logical identity is independent of storage position. */
final class cpp_artifact {
	public readonly string $identity;
	private cpp_artifact_kind $artifact_kind;
	public readonly string $file_name;
	public readonly string $text;
	/** Explicit preamble dependencies; unknown adapters retain the scanning fallback. */
	public array $includes /** vector<string> */ = [];
	public bool $includes_complete = false;

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

/** Invocation-local pending return transport for one emitted protected lambda. */
final class cpp_finally_return
{
	public string $flag;
	public ?string $value = null;
}

/** One emission invocation; syntax and prepared facts never retain it. */
final class cpp_generation_context
{
	public ?cpp_finally_return $finally_return = null;
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
	public \SplObjectStorage $fragments /** hash<cpp_fragment, shared<preparation_owner>> */;

	public function __construct()
	{
		$this->fragments = new \SplObjectStorage /** hash<cpp_fragment, shared<preparation_owner>> */();
	}
}
