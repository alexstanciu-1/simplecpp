<?php

namespace scpp\compiler;

final class parsed_file
{
	/** False while parsing or after failure; consumers must wait for a successful join. */
	public bool $complete = false;
	/** @storage.reference model.tokens */
	public token_list $tokens;
	/**
	 * Syntax child owned through this link.
	 * @ownership owner
	 */
	public file_node $root;
	/**
	 * Convenience link to this file's published collection result.
	 * @storage.reference model.collected_files
	 * @reference.weak
	 */
	public collected_file $collection;
	/**
	 * Local scopes; also owns the root scope when parsing without an external scope.
	 * @storage.owner
	 */
	public Storage $scopes /** Storage<scope> */;

	/** Publish a completed parse with initialized syntax, provenance and owned scopes. */
	public function __construct(token_list $tokens, file_node $root, collected_file $collection, Storage $scopes /** Storage<scope> */)
	{
		$this->tokens = $tokens;
		$this->root = $root;
		$this->collection = $collection;
		$this->scopes = $scopes;
	}

	public function source_file(): file
	{
		return $this->tokens->file;
	}

	public function root_scope(): scope
	{
		return $this->root->file_scope();
	}
}
