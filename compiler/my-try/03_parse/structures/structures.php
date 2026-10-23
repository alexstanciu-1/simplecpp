<?php

namespace scpp\compiler;

final class parsed_file
{
	/** False while parsing or after failure; consumers must wait for a successful join. */
	public bool $complete = false;
	/** Publish a completed parse with initialized syntax, provenance and owned scopes. */
	public function __construct(
		/** @storage.reference model.tokens */
		public token_list $tokens,
		/** Syntax child owned through this link. @ownership owner */
		public file_node $root,
		/** Convenience link. @storage.reference model.collected_files @reference.weak */
		public collected_file $collection,
		/** Local scopes; owns the root scope when parsing standalone. @storage.owner */
		public Storage $scopes /** Storage<scope> */) {}

	public function source_file(): file
	{
		return $this->tokens->file;
	}

	public function root_scope(): scope
	{
		return $this->root->single_body()->body_scope();
	}
}
