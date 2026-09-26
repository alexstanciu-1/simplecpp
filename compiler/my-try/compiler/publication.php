<?php

/* Role: publish completed source work and maintain the retained root graph. */
namespace scpp\compiler;

final class Source_Publication
{
	/** Locate a live source by its module-qualified path; tombstones never participate. */
	public static function find_source(string $path): ?file
	{
		foreach (Model::$modules as $input_module) {
			foreach ($input_module->files as $source) {
				if (($source->path === $path) && ($source->changes !== \scpp\compiler\SYNC_DELETED)) {
					return $source;
				}
			}
		}
		return null;
	}

	/** Replace only one file; old deleted declarations remain observable but are never resolved. */
	public static function publish_update(source_work $work): void
	{
		$source = $work->source;
		$syntax /** Storage<parsed_file> */ = Model::$syntax_files;
		$previous /** ?parsed_file */ = null;
		$position = -1;
		foreach ($syntax as $index => $parsed)
		{
			if ($parsed->source_file()->path === $source->path) {
				if ($parsed->source_file()->changes !== \scpp\compiler\SYNC_DELETED) {
					$previous = $parsed;
					$position = $index;
				}
			}
		}
		if ($source->changes === \scpp\compiler\SYNC_DELETED)
		{
			if ($previous !== null)
			{
				$old = object_cast($previous, parsed_file::class);
				$old->source_file()->changes = \scpp\compiler\SYNC_DELETED;
				$entries /** Storage<collected_name> */ = $old->collection->entries;
				foreach ($old->collection->defined_elements as $index) {
					$entries[$index]->changes = \scpp\compiler\SYNC_DELETED;
				}
			}
			return;
		}
		$candidate = object_cast($work->result, parsed_file::class);
		$entries /** Storage<collected_name> */ = $candidate->collection->entries;
		foreach ($candidate->collection->defined_elements as $index) {
			$entries[$index]->changes = \scpp\compiler\SYNC_ADDED;
		}
		$source->changes = \scpp\compiler\SYNC_ADDED;
		if ($previous !== null)
		{
			$old = object_cast($previous, parsed_file::class);
			Declaration_Changes::compare($old, $candidate);
			$source->changes = 0;
			if ($old->tokens->content !== $candidate->tokens->content) {
				$source->changes = \scpp\compiler\SYNC_CHANGED;
			}
		}
		// Remove replaced live references; keep actual deletions as tombstones in global indexes.
		$global = Model::$global_scope;
		Scope_Publication::replace_source($global, $source->path);
		if ($position >= 0) {
			$syntax->replace($position, $candidate);
		}
		else {
			$syntax->append($candidate);
		}
		self::publish_scope($candidate);
		$source->tokens = $candidate->tokens;
		foreach (Model::$modules as $input_module)
		{
			$files /** Storage<file> */ = $input_module->files;
			foreach ($files as $index => $old_source)
			{
				if ($old_source->path === $source->path) {
					if ($old_source->changes !== \scpp\compiler\SYNC_DELETED) {
						$files->replace($index, $source);
						self::order_roots();
						return;
					}
				}
			}
		}
		foreach (Model::$modules as $input_module) {
			if ($input_module->path === fs_dirname($source->path)) {
				$files /** Storage<file> */ = $input_module->files;
				$files->append($source);
				break;
			}
		}
		self::order_roots();
	}

	/** Restore module/file order using a temporary identity index, including retained deleted files. */
	public static function order_roots(): void
	{
		$by_source /** hash<parsed_file, shared<file>> */ = new \SplObjectStorage /** hash<parsed_file, shared<file>> */();
		foreach (Model::$syntax_files as $parsed) {
			$by_source[$parsed->source_file()] = $parsed;
		}
		$syntax /** Storage<parsed_file> */ = new Storage();
		$tokens /** Storage<token_list> */ = new Storage();
		$collections /** Storage<collected_file> */ = new Storage();
		foreach (Model::$modules as $input_module)
		{
			foreach ($input_module->files as $source)
			{
				if (!isset($by_source[$source])) {
					continue;
				}
				$parsed /** parsed_file */ = $by_source[$source];
				$syntax->append($parsed);
				$tokens->append($parsed->tokens);
				$collections->append($parsed->collection);
			}
		}
		Model::$syntax_files = $syntax;
		Model::$tokens = $tokens;
		Model::$collected_files = $collections;
	}

	/** Publication boundary: caller serializes this operation; parser workers never edit global indexes. */
	public static function publish_parsed(parsed_file $parsed): void
	{
		self::publish_scope($parsed);
		Model::$syntax_files[] = $parsed;
		Model::$collected_files[] = $parsed->collection;
	}

	/** Export live file-root declarations; duplicate candidates remain separate entries. */
	private static function publish_scope(parsed_file $parsed): void
	{
		$root_scope = $parsed->root_scope();
		Scope_Publication::publish($root_scope, Model::$global_scope);
	}

	/** Publish only the completed standalone stage while the caller holds serialization. */
	public static function publish_stage(source_work $work, frontend_operation $operation): void
	{
		if ($operation === frontend_operation::scan) {
			$tokens = object_cast($work->tokens, token_list::class);
			$work->source->tokens = $tokens;
			Model::$tokens[] = $tokens;
		}
		if ($operation === frontend_operation::parse) {
			self::publish_parsed(object_cast($work->result, parsed_file::class));
		}
	}

	/** Restore input order after all standalone stage workers have joined. */
	public static function order_stage(array $items /** vector<source_work> */, frontend_operation $operation): void
	{
		// Work is unordered; restore retained file order only after every worker has joined.
		$token_files /** Storage<token_list> */ = new Storage();
		$syntax_files /** Storage<parsed_file> */ = new Storage();
		$collected_files /** Storage<collected_file> */ = new Storage();
		foreach ($items as $work)
		{
			if ($operation === frontend_operation::scan) {
				$token_files->append(object_cast($work->tokens, token_list::class));
			}
			if ($operation === frontend_operation::parse) {
				$parsed = object_cast($work->result, parsed_file::class);
				$syntax_files->append($parsed);
				$collected_files->append($parsed->collection);
			}
		}
		if ($operation === frontend_operation::scan) {
			Model::$tokens = $token_files;
		}
		if ($operation === frontend_operation::parse) {
			Model::$syntax_files = $syntax_files;
			Model::$collected_files = $collected_files;
		}
	}
}
