<?php

/*
 * Role: file-local occurrence identities and work lists.
 * Used by: Symbol_Collector, Name_Preparation and LLVM_Preparation.
 */
namespace scpp\compiler;

enum collected_name_kind
{
	case struct_declaration;
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
	/**
	 * Backlink to the owning occurrence collection.
	 * @storage.reference model.collected_files
	 * @reference.weak
	 */
	public collected_file $file;
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
}

/** One parse's occurrence list; entries retain their order and never merge by name. */
final class collected_file
{
	/**
	 * @storage.reference model.tokens
	 */
	public token_list $source;
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
	 * Local indexes of explicit declarations with known identity.
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

	public function __construct()
	{
		$this->entries = new Storage /** Storage<collected_name> */();
	}
}
