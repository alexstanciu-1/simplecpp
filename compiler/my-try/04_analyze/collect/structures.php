<?php

/*
 * Role: file-local occurrence identities and work lists.
 * Used by: Symbol_Collector, Name_Preparation and LLVM_Preparation.
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

/** Identity is the owning file collection plus its append-only local index. */
final class collected_name
{
	/** Current-update flags; deleted entries must not be dereferenced by consumers. */
	public int $changes = 0;
	/**
	 * Backlink to the owning occurrence collection.
	 * @storage.reference model.collected_files
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

/** One parse's occurrences plus retained deleted declarations; duplicate names never merge. */
final class collected_file
{
	/**
	 * @storage.reference model.tokens
	 */
	private token_list $tokens;
	/**
	 * Convenience mirror of parsed_file.root; not a second AST owner.
	 * @reference.source parsed_file.root (syntax graph)
	 * @reference.weak
	 */
	public ast_node $root;
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
		$this->entries = new Storage /** Storage<collected_name> */();
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
