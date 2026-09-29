<?php

/*
 * Role: file-local occurrence identities and work lists.
 * Used by: Symbol_Collector, File_Preparation and parked LLVM preparation.
 */
namespace scpp\compiler;

enum collected_name_kind
{
	case struct_declaration;
	case field_declaration;
	case field_reference;
	case variable_declaration;
	case function_declaration;
	case function_reference;
	case variable_reference;
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

	/** Compatibility classification for lookup lists and the parked LLVM path. */
	abstract public function kind(): collected_name_kind;

	public function is_retained(): bool
	{
		return false;
	}

	public function is_exported(): bool
	{
		return false;
	}

	/** Only independently prepared declarations own this slot. */
	public function preparation_owner(): ?preparation_owner
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

/** Functions and records are independently scheduled; members settle with their owner. */
abstract class collected_definition extends collected_declaration
{
	public ?preparation_owner $preparation = null;

	public function preparation_owner(): ?preparation_owner
	{
		return $this->preparation;
	}
}

/** Unresolved reads and named lookups have no declaration/publication or preparation-owner slot. */
abstract class collected_reference extends collected_name {
}

final class collected_function extends collected_definition
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

	public function kind(): collected_name_kind
	{
		return collected_name_kind::function_declaration;
	}
}

final class collected_struct extends collected_definition
{
	/** @reference.source parsed_file.root (syntax graph) */
	private struct_node $syntax_node;

	public function __construct(collected_file $collection, struct_node $node)
	{
		parent::__construct($collection);
		$this->syntax_node = $node;
	}

	public function syntax(): struct_node
	{
		return $this->syntax_node;
	}

	public function kind(): collected_name_kind
	{
		return collected_name_kind::struct_declaration;
	}
}

final class collected_field extends collected_declaration
{
	/** @reference.source parsed_file.root (syntax graph) */
	private field_node $syntax_node;

	public function __construct(collected_file $collection, field_node $node)
	{
		parent::__construct($collection);
		$this->syntax_node = $node;
	}

	public function syntax(): field_node
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

final class collected_type_reference extends collected_reference
{
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
