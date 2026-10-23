<?php

/*
 * Role: file-local occurrence identities and work lists.
 * Used by: Symbol_Collector, File_Preparation and parked LLVM preparation.
 */
namespace scpp\compiler;

/** Invocation-local result of a synchronized source-type association lookup. */
final class source_type_snapshot
{
	public array $declarations /** vector<collected_type_definition> */ = [];
}

enum collected_name_kind
{
	case record_declaration;
	case interface_declaration;
	case trait_declaration;
	case enum_declaration;
	case field_declaration;
	case field_reference;
	case variable_declaration;
	case function_declaration;
	case method_declaration;
	case call_reference;
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

	public function __construct(
		/** Backlink to the owning occurrence collection. @reference.source parsed_file.collection @reference.weak */
		public collected_file $collection) {}

	/** Concrete records own exactly one typed link to the existing syntax graph. */
	abstract public function syntax(): ast_node;

	/** Compatibility classification for diagnostics and the parked LLVM path. */
	abstract public function kind(): collected_name_kind;

	/** Dispatch only; the collector owns occurrence-list maintenance. */
	abstract public function index_collection(Symbol_Collector $collector): void;

	/** Occurrence categories supply their retention and publication policy. */
	abstract public function is_retained(): bool;
	abstract public function is_exported(): bool;

	/** Only independently prepared declarations own this slot. */
	abstract public function preparation_work_owner(): ?preparation_owner;

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

	/** Local/member declarations have no independent work; definitions override this query. */
	public function preparation_work_owner(): ?preparation_owner
	{
		return null;
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
	abstract public function qualified_name(): string;

	/** Shared namespace naming for definitions that are not methods. */
	protected function scope_qualified_name(): string
	{
		$declaration_scope = object_cast(weakref_get($this->scope), \scpp\compiler\scope::class);
		$identity = $declaration_scope->source_namespace();
		if ($identity === null) {
			return $this->name;
		}
		return $identity->member_name($this->name);
	}

	public ?contract_work $preparation = null;

	public function preparation_work_owner(): ?preparation_owner
	{
		return $this->preparation;
	}

}

/** Unresolved reads and named lookups have no declaration/publication or preparation-owner slot. */
abstract class collected_reference extends collected_name
{
	public function is_retained(): bool { return false; }
	public function is_exported(): bool { return false; }
	public function preparation_work_owner(): ?preparation_owner { return null; }
}

/** Shared source identity; semantic work never needs to pretend a member is a free function. */
abstract class collected_source_callable extends collected_definition implements callable_target_i
{
	/** @reference.source parsed_file.root (syntax graph) */
	private callable_declaration_node $syntax_node;

	public function __construct(collected_file $collection, callable_declaration_node $node)
	{
		parent::__construct($collection);
		$this->syntax_node = $node;
	}

	public function syntax(): ast_node
	{
		return $this->syntax_node;
	}

	public function callable_syntax(): callable_declaration_node
	{
		return $this->syntax_node;
	}

	public function signature(): callable_signature
	{
		return $this->syntax_node->require_preparation()->signature;
	}
}

final class collected_function extends collected_source_callable
{
	public function qualified_name(): string { return $this->scope_qualified_name(); }

	public function kind(): collected_name_kind
	{
		return collected_name_kind::function_declaration;
	}
}

/** The type owns member membership; the method owns its independent signature and body work. */
final class collected_method extends collected_source_callable
{
	/** Private implementations, keyed by called-type identity; not member lookup entries. */
	public Storage $called_instances /** Storage<callable_instance> */;
	/** Working-method backlink; its origin owns the instance. */
	public ?callable_instance $instance_context /** weak<callable_instance> */ = null;
	/** @reference.source type_declaration_node occurrence; no additional owner */
	private collected_member_owner $type_owner /** weak<collected_member_owner> */;

	public function __construct(collected_file $collection, method_node $node, collected_member_owner $owner)
	{
		parent::__construct($collection, $node);
		$this->type_owner = weak($owner);
		$this->called_instances = new Storage /** Storage<callable_instance> */();
	}

	public function declaring_type(): collected_member_owner
	{
		return weakref_get($this->type_owner);
	}

	public function qualified_name(): string
	{
		return $this->declaring_type()->qualified_name() . '::' . $this->name;
	}

	public function kind(): collected_name_kind
	{
		return collected_name_kind::method_declaration;
	}
}

final class collected_constant extends collected_definition implements constant_target_i
{
	public function qualified_name(): string { return $this->scope_qualified_name(); }

	public ?collected_member_owner $member_owner /** weak<collected_member_owner> */ = null;
	private constant_declaration_node $syntax_node;
	public function __construct(collected_file $collection, constant_declaration_node $node)
	{
		parent::__construct($collection);
		$this->syntax_node = $node;
	}
	public function syntax(): constant_declaration_node { return $this->syntax_node; }
	public function kind(): collected_name_kind { return collected_name_kind::constant_declaration; }
	public function constant_name(): string { return $this->name; }
	public function value(): constant_value { return $this->syntax_node->require_preparation()->value; }
}

/** Shared source-type identity; record-only consumers must explicitly narrow to collected_record. */
abstract class collected_type_definition extends collected_definition
{
	public function qualified_name(): string { return $this->scope_qualified_name(); }
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

/** Shared member ownership does not imply instance layout, storage or construction. */
abstract class collected_member_owner extends collected_type_definition
{
	/** Independent analysis work; completing relationships never completes layout. */
	public ?type_relationship_work $relationship_work = null;
	public ?type_contract_work $contract_work = null;
	public ?type_lifecycle_work $lifecycle_work = null;
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
}

final class collected_record extends collected_member_owner
{
	/** Composition-owned working members, never authored AST membership. @storage.owner */
	public Storage $trait_instances /** Storage<trait_instance> */;

	public function __construct(collected_file $collection, type_declaration_node $node)
	{
		parent::__construct($collection, $node);
		$this->trait_instances = new Storage /** Storage<trait_instance> */();
	}

	public function kind(): collected_name_kind
	{
		return collected_name_kind::record_declaration;
	}
}

/** Interface identity shares method ownership, never record layout or construction capabilities. */
final class collected_interface extends collected_member_owner
{
	public function kind(): collected_name_kind
	{
		return collected_name_kind::interface_declaration;
	}
}

/** Authored composition source; never a record layout, receiver instance or callable implementation. */
final class collected_trait extends collected_member_owner
{
	public function kind(): collected_name_kind
	{
		return collected_name_kind::trait_declaration;
	}
}

final class collected_field extends collected_declaration implements preparation_lookup_candidate_i
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

final class collected_call_reference extends collected_reference
{
	public function index_collection(Symbol_Collector $collector): void
	{
		$collector->index_call_reference($this);
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
		return collected_name_kind::call_reference;
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
	public function is_retained(): bool { return false; }
	public function is_exported(): bool { return false; }
	public function preparation_work_owner(): ?preparation_owner { return null; }

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

/** Anonymous source occurrence, never a name-pool member or backend symbol. */
final class anonymous_callable_identity implements callable_target_i
{
	public ?canonical_type_use $value_type = null;
	public function signature(): callable_signature { return $this->signature_work->require_facts()->signature; }
	public ?anonymous_signature_work $signature_work = null;
	private collected_file $source_collection /** weak<collected_file> */;
	private closure_node $syntax_node;
	/** Stable, non-reused position within the owning collection's anonymous inventory. */
	public int $local_index;
	public int $revision = 0;
	/** Frontend membership tombstone, not preparation state. */
	public bool $deleted = false;

	public function __construct(collected_file $collection, closure_node $syntax)
	{
		$this->source_collection = weak($collection);
		$this->syntax_node = $syntax;
	}

	public function collection(): collected_file
	{
		return weakref_get($this->source_collection);
	}

	public function syntax(): closure_node
	{
		return $this->syntax_node;
	}
}

/** Mutable file inventory: persistent declarations and the current parse's unresolved occurrences. */
final class collected_file
{
	/** Owns anonymous occurrences independently of named declaration lookup/publication. */
	public Storage $anonymous_callables /** Storage<anonymous_callable_identity> */;
	/** Prior lexical owners survive parsing until semantic retirement and frontend disposal join. */
	public Storage $retired_scopes /** Storage<scope> */;
	public bool $parse_complete = false;
	public bool $deleted = false;
	public ?prepared_file $prepared = null;
	/** Completed changes awaiting backend consumption, including retired owners. */
	public \SplObjectStorage $preparation_changes /** hash<bool, preparation_owner> */;
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
	 * Local indexes of fixed named calls awaiting free/member target selection.
	 * @storage.index collected_file.entries
	 */
	public array $call_references /** vector<int> */ = [];
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
		$this->anonymous_callables = new Storage /** Storage<anonymous_callable_identity> */();
		$this->retired_scopes = new Storage /** Storage<scope> */();
		$this->preparation_changes = new \SplObjectStorage /** hash<bool, preparation_owner> */();
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
