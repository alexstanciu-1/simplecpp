<?php

/*
 * Role: file-local occurrence identities and work lists.
 * Used by: Symbol_Collector, File_Preparation and parked LLVM preparation.
 */
namespace scpp\compiler;

enum collected_name_kind
{
	case record_declaration;
	case enum_declaration;
	case field_declaration;
	case field_reference;
	case variable_declaration;
	case function_declaration;
	case function_reference;
	case variable_reference;
	case constant_reference;
	case constant_declaration;
	case type_reference;
	case binding;
}

/** Retained declarations keep identity and index; replaced body/reference rows leave holes. */
abstract class collected_name
{
	public int $revision /** uint32 */ = 0;
	public change_state $change_status = change_state::added;

	/** Legacy combined-pipeline flags. The incremental parse path uses change_status and revision. */
	public int $changes = 0;
	/**
	 * Backlink to the owning occurrence collection.
	 * @reference.source parsed_file.collection
	 * @reference.weak
	 */
	public collected_file $collection;
	/** @storage.index collected_file.entries */
	public int $local_index;
	public string $name;
	/**
	 * Lexical context; owned by the global model or parsed-file scope store.
	 * @reference.source model.global_scope
	 * @storage.reference parsed_file.scopes
	 * @reference.weak
	 */
	public scope $scope /** weak<scope> */;
	/** @storage.index token_list.tokens */
	public int $token_index;

	public function __construct(collected_file $collection)
	{
		$this->collection = $collection;
	}

	/** Concrete records own exactly one typed link to the existing syntax graph. */
	abstract public function syntax(): ast_node;

	/** Compatibility classification for diagnostics and the parked LLVM path. */
	abstract public function kind(): collected_name_kind;

	/** Dispatch only; the collector owns occurrence-list maintenance. */
	abstract public function index_collection(Symbol_Collector $collector): void;

	public function is_retained(): bool
	{
		return false;
	}

	public function is_exported(): bool
	{
		return false;
	}

	/** Only independently prepared declarations own this slot. */
	public function preparation_work_owner(): ?preparation_owner
	{
		return null;
	}

	public function source_file(): file
	{
		return $this->collection->source_file();
	}

	public function token_snapshot(): token_list
	{
		return $this->collection->token_snapshot();
	}
}

/** Declaration membership differs from unresolved uses; locals are replaced with their body. */
abstract class collected_declaration extends collected_name
{
	public function index_collection(Symbol_Collector $collector): void
	{
		$collector->index_declaration($this);
	}

	public bool $retained_symbol = false;
	public bool $exported = false;

	public function is_retained(): bool
	{
		return $this->retained_symbol;
	}

	public function is_exported(): bool
	{
		return $this->exported;
	}
}

/** Published definitions are independently scheduled; members settle with their owner. */
abstract class collected_definition extends collected_declaration implements preparation_lookup_candidate_i
{
	/** Stable source identity is separate from local lookup spelling and backend encoding. */
	public function qualified_name(): string
	{
		$declaration_scope = object_cast(weakref_get($this->scope), \scpp\compiler\scope::class);
		$identity = $declaration_scope->source_namespace();
		if ($identity === null) {
			return $this->name;
		}
		return $identity->member_name($this->name);
	}

	public ?declaration_work $preparation = null;

	public function preparation_work_owner(): ?preparation_owner
	{
		return $this->preparation;
	}

}

/** Unresolved reads and named lookups have no declaration/publication or preparation-owner slot. */
abstract class collected_reference extends collected_name {
}

final class collected_function extends collected_definition implements callable_target_i
{
	/** @reference.source parsed_file.root (syntax graph) */
	private function_node $syntax_node;

	public function __construct(collected_file $collection, function_node $node)
	{
		parent::__construct($collection);
		$this->syntax_node = $node;
	}

	public function syntax(): function_node
	{
		return $this->syntax_node;
	}

	public function signature(): callable_signature
	{
		return $this->syntax_node->require_preparation()->signature;
	}

	public function kind(): collected_name_kind
	{
		return collected_name_kind::function_declaration;
	}
}

final class collected_constant extends collected_definition implements constant_target_i
{
	private namespace_constant_node $syntax_node;
	public function __construct(collected_file $collection, namespace_constant_node $node)
	{
		parent::__construct($collection);
		$this->syntax_node = $node;
	}
	public function syntax(): namespace_constant_node { return $this->syntax_node; }
	public function kind(): collected_name_kind { return collected_name_kind::constant_declaration; }
	public function constant_name(): string { return $this->name; }
	public function value(): constant_value { return $this->syntax_node->require_preparation(); }
}

/** Shared source-type identity; record-only consumers must explicitly narrow to collected_record. */
abstract class collected_type_definition extends collected_definition
{
}

final class collected_enum extends collected_type_definition
{
	/** @reference.source parsed_file.root (syntax graph) */
	private enum_node $syntax_node;

	public function __construct(collected_file $collection, enum_node $node)
	{
		parent::__construct($collection);
		$this->syntax_node = $node;
	}

	public function syntax(): ast_node
	{
		return $this->syntax_node;
	}

	public function enum_syntax(): enum_node
	{
		return $this->syntax_node;
	}

	public function kind(): collected_name_kind
	{
		return collected_name_kind::enum_declaration;
	}
}

final class collected_record extends collected_type_definition
{
	/** @reference.source parsed_file.root (syntax graph) */
	private type_declaration_node $syntax_node;

	public function __construct(collected_file $collection, type_declaration_node $node)
	{
		parent::__construct($collection);
		$this->syntax_node = $node;
	}

	public function syntax(): type_declaration_node
	{
		return $this->syntax_node;
	}

	public function kind(): collected_name_kind
	{
		return collected_name_kind::record_declaration;
	}
}

final class collected_field extends collected_declaration
{
	/** @reference.source parsed_file.root (syntax graph) */
	private property_node $syntax_node;

	public function __construct(collected_file $collection, property_node $node)
	{
		parent::__construct($collection);
		$this->syntax_node = $node;
	}

	public function syntax(): property_node
	{
		return $this->syntax_node;
	}

	public function kind(): collected_name_kind
	{
		return collected_name_kind::field_declaration;
	}
}

final class collected_parameter extends collected_declaration
{
	/** @reference.source parsed_file.root (syntax graph) */
	private parameter_node $syntax_node;

	public function __construct(collected_file $collection, parameter_node $node)
	{
		parent::__construct($collection);
		$this->syntax_node = $node;
	}

	public function syntax(): parameter_node
	{
		return $this->syntax_node;
	}

	public function kind(): collected_name_kind
	{
		return collected_name_kind::variable_declaration;
	}
}

final class collected_variable extends collected_declaration
{
	/** @reference.source parsed_file.root (syntax graph) */
	private variable_declaration_node $syntax_node;

	public function __construct(collected_file $collection, variable_declaration_node $node)
	{
		parent::__construct($collection);
		$this->syntax_node = $node;
	}

	public function syntax(): variable_declaration_node
	{
		return $this->syntax_node;
	}

	public function kind(): collected_name_kind
	{
		return collected_name_kind::variable_declaration;
	}
}

final class collected_function_reference extends collected_reference
{
	public function index_collection(Symbol_Collector $collector): void
	{
		$collector->index_function_reference($this);
	}

	/** @reference.source parsed_file.root (syntax graph) */
	private call_node $syntax_node;

	public function __construct(collected_file $collection, call_node $node)
	{
		parent::__construct($collection);
		$this->syntax_node = $node;
	}

	public function syntax(): call_node
	{
		return $this->syntax_node;
	}

	public function kind(): collected_name_kind
	{
		return collected_name_kind::function_reference;
	}
}

final class collected_field_reference extends collected_reference
{
	public function index_collection(Symbol_Collector $collector): void
	{
		$collector->index_field_reference($this);
	}

	/** @reference.source parsed_file.root (syntax graph) */
	private field_access_node $syntax_node;

	public function __construct(collected_file $collection, field_access_node $node)
	{
		parent::__construct($collection);
		$this->syntax_node = $node;
	}

	public function syntax(): field_access_node
	{
		return $this->syntax_node;
	}

	public function kind(): collected_name_kind
	{
		return collected_name_kind::field_reference;
	}
}

final class collected_variable_reference extends collected_reference
{
	public function index_collection(Symbol_Collector $collector): void
	{
		$collector->index_variable_reference($this);
	}

	/** @reference.source parsed_file.root (syntax graph) */
	private variable_reference_node $syntax_node;

	public function __construct(collected_file $collection, variable_reference_node $node)
	{
		parent::__construct($collection);
		$this->syntax_node = $node;
	}

	public function syntax(): variable_reference_node
	{
		return $this->syntax_node;
	}

	public function kind(): collected_name_kind
	{
		return collected_name_kind::variable_reference;
	}
}

final class collected_constant_reference extends collected_reference
{
	public function index_collection(Symbol_Collector $collector): void
	{
		$collector->index_constant_reference($this);
	}

	/** @reference.source parsed_file.root (syntax graph) */
	private constant_reference_node $syntax_node;

	public function __construct(collected_file $collection, constant_reference_node $node)
	{
		parent::__construct($collection);
		$this->syntax_node = $node;
	}

	public function syntax(): constant_reference_node
	{
		return $this->syntax_node;
	}

	public function kind(): collected_name_kind
	{
		return collected_name_kind::constant_reference;
	}
}

final class collected_type_reference extends collected_reference
{
	public function index_collection(Symbol_Collector $collector): void
	{
		$collector->index_type_reference($this);
	}

	/** @reference.source parsed_file.root (syntax graph) */
	private named_type_node $syntax_node;

	public function __construct(collected_file $collection, named_type_node $node)
	{
		parent::__construct($collection);
		$this->syntax_node = $node;
	}

	public function syntax(): named_type_node
	{
		return $this->syntax_node;
	}

	public function kind(): collected_name_kind
	{
		return collected_name_kind::type_reference;
	}
}

/** An unresolved write keeps its identity when preparation discovers declaration or assignment. */
final class collected_variable_write extends collected_name
{
	public function index_collection(Symbol_Collector $collector): void
	{
		$collector->index_variable_write($this);
	}

	/** @reference.source parsed_file.root (syntax graph) */
	private variable_reference_node $syntax_node;

	public function __construct(collected_file $collection, variable_reference_node $node)
	{
		parent::__construct($collection);
		$this->syntax_node = $node;
	}

	public function syntax(): variable_reference_node
	{
		return $this->syntax_node;
	}

	public function kind(): collected_name_kind
	{
		return collected_name_kind::binding;
	}
}

/** Mutable file inventory: persistent declarations and the current parse's unresolved occurrences. */
final class collected_file
{
	/** Prior lexical owners survive parsing until semantic retirement and frontend disposal join. */
	public Storage $retired_scopes /** Storage<scope> */;
	public bool $parse_complete = false;
	public bool $deleted = false;
	public ?prepared_file $prepared = null;
	/** Completed changes awaiting backend consumption, including retired owners. */
	public \SplObjectStorage $preparation_changes /** hash<bool, shared<preparation_owner>> */;
	public int $revision /** uint32 */ = 0;
	/**
	 * @storage.reference model.tokens
	 */
	private token_list $tokens;
	/**
	 * Convenience mirror of parsed_file.root; not a second AST owner.
	 * @reference.source parsed_file.root (syntax graph)
	 * @reference.weak
	 */
	public file_node $root;
	/**
	 * Numeric storage of collected_name records.
	 * @storage.owner
	 */
	public Storage $entries /** Storage<collected_name> */;

	/**
	 * Positions in this entries store, including retained deleted declarations.
	 * Deleted rows retain old provenance; inspect changes before other fields.
	 * @storage.index collected_file.entries
	 */
	public array $defined_elements /** vector<int> */ = [];
	/**
	 * Local indexes awaiting variable lookup.
	 * @storage.index collected_file.entries
	 */
	public array $variable_references /** vector<int> */ = [];
	/**
	 * Local indexes awaiting immutable constant lookup.
	 * @storage.index collected_file.entries
	 */
	public array $constant_references /** vector<int> */ = [];
	/**
	 * Local indexes of direct named calls awaiting function lookup.
	 * @storage.index collected_file.entries
	 */
	public array $function_references /** vector<int> */ = [];
	/**
	 * Local indexes awaiting type lookup.
	 * @storage.index collected_file.entries
	 */
	public array $type_references /** vector<int> */ = [];
	/**
	 * Member accesses awaiting field resolution.
	 * @storage.index collected_file.entries
	 */
	public array $field_references /** vector<int> */ = [];
	/**
	 * Local indexes awaiting declaration/assignment classification.
	 * @storage.index collected_file.entries
	 */
	public array $pending_bindings /** vector<int> */ = [];

	public function __construct(token_list $tokens)
	{
		$this->tokens = $tokens;
		$this->retired_scopes = new Storage /** Storage<scope> */();
		$this->preparation_changes = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
		$this->entries = new Storage /** Storage<collected_name> */();
	}

	/** Select the current input; retained occurrences still address its appended token storage. */
	public function set_tokens(token_list $tokens): void
	{
		$this->tokens = $tokens;
	}

	public function source_file(): file
	{
		return $this->tokens->file;
	}

	public function token_snapshot(): token_list
	{
		return $this->tokens;
	}
}
