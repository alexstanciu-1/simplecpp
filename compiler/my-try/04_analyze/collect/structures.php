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
final class collected_name
{
	/** Only named declarations outside replaceable bodies participate in symbol reconciliation. */
	public bool $retained_symbol = false;
	public ?preparation_owner $preparation = null;
	public bool $exported = false;
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
	public collected_name_kind $kind;
	/**
	 * Lexical context; owned by the global model or parsed-file scope store.
	 * @reference.source model.global_scope
	 * @storage.reference parsed_file.scopes
	 * @reference.weak
	 */
	public scope $scope /** weak<scope> */;
	/**
	 * Occurrence syntax in the same parsed file.
	 * @reference.source parsed_file.root (syntax graph)
	 */
	public ast_node $node;
	/** @storage.index token_list.tokens */
	public int $token_index;

	public function __construct(collected_file $collection)
	{
		$this->collection = $collection;
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
