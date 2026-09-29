<?php

/* C++ representation data stays separate from canonical language type identity. */
namespace scpp\compiler;

enum cpp_literal_kind {
	case signed_integer;
	case boolean;
	case floating;
	case none;
}

final class cpp_type {
	public string $spelling;
	public string $header;
	public cpp_literal_kind $literal;
}

/** Final bytes own no references into preparation or source syntax. */
final class cpp_module {
	public string $file_name;
	public string $text;
}

/** Invocation-local progress for ordering complete by-value struct definitions. */
enum cpp_record_state {
	case visiting;
	case complete;
}

/** One emission invocation; syntax and prepared facts never retain it. */
final class cpp_generation_context
{
	public array $headers /** hash<bool> */ = [];
	/** Scoped source names are stable across token movement in the current single-source grammar. */
	public array $record_states /** hash<cpp_record_state> */ = [];

	/** Output sections are assembled after traversal so declarations precede all uses. */
	public string $records = '';
	public string $prototypes = '';
	public string $functions = '';

	/** Null selects native main-return spelling; a function uses its prepared return type. */
	public ?type_definition $return_type = null;
	/** Invocation-local allocation keeps nested argument temporaries distinct. */
	public int $next_temporary = 0;
	/** Fragment rendering leaves dependency ordering to assembly. */
	public bool $expand_records = true;
}

/** One independently replaceable C++ fragment; no references to transient generation workers. */
final class cpp_fragment
{
	public change_state $change_status = change_state::added;
	/** Completion version is meaningful only for an unchanged fragment. */
	public int $version = 0;
	public string $text = '';
	public array $headers /** hash<bool> */ = [];
	/** Declaration dependencies needed before a value-record definition. */
	public Storage $records /** Storage<collected_name> */;

	public function __construct()
	{
		$this->records = new Storage /** Storage<collected_name> */();
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
