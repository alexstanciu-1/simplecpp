<?php

/*
 * Role: coordinate the currently imported compiler stages.
 * Call map: init -> exec/update -> sync (private read/tokenize/parse, locked replacement) -> llvm.
 * Output: retained sources, syntax, collection and per-source LLVM modules.
 */
namespace scpp\compiler;

/** Shared default concurrency budget for the per-file frontend pipeline. */
const DEFAULT_COMPILER_JOBS = 12;

final class Compiler
{
	/** Constructor installs the configured default before use. */
	public int $jobs = 0;
	public function __construct()
	{
		$this->jobs = \scpp\compiler\DEFAULT_COMPILER_JOBS;
	}

	/** Discover one module per input folder, retaining the requested order. */
	public function init(array $paths /** vector<string> */): void
	{
		Model::reset();

		foreach ($paths as $path) {
			$input_module = new module();
			Module_Loader::init($input_module, $path);
			Model::$modules[] = $input_module;
		}
	}
	/** Run the source pipeline through collection and the initial LLVM pass. */
	public function exec(): void
	{
		$paths /** vector<string> */ = [];
		foreach (Model::$modules as $input_module) {
			foreach ($input_module->files as $source) {
				if ($source->changes !== \scpp\compiler\SYNC_DELETED) {
					$paths[] = $source->path;
				}
			}
		}
		$this->sync($paths);
		$this->llvm();
	}

	/** Apply file notifications, then rerun all existing resolution/preparation and generation. */
	public function update(array $paths /** vector<string> */): void
	{
		$this->sync($paths);
		$this->llvm();
	}

	/** Initial compilation and later updates share private work and the same publication path. */
	public function sync(array $paths /** vector<string> */): void
	{
		if ($this->jobs < 1) {
			throw new \LogicException('Compiler job limit must be positive');
		}
		Model::reset_llvm();
		foreach (Model::$modules as $input_module) {
			foreach ($input_module->files as $source) {
				$source->changes = $source->changes === \scpp\compiler\SYNC_DELETED ? \scpp\compiler\SYNC_DELETED : 0;
			}
		}
		foreach (Model::$collected_files as $collection) {
			foreach ($collection->entries as $entry) {
				$entry->changes = $entry->changes === \scpp\compiler\SYNC_DELETED ? \scpp\compiler\SYNC_DELETED : 0;
			}
		}
		$queue = new Source_Work_Queue();
		$seen /** hash<bool> */ = [];
		foreach ($paths as $path)
		{
			if (isset($seen[$path])) {
				continue;
			}
			$seen[$path] = true;
			$owner_found = false;
			$previous = self::find_source($path);
			foreach (Model::$modules as $input_module) {
				if ($input_module->path === fs_dirname($path)) {
					$owner_found = true;
				}
			}
			if (!$owner_found) {
				throw new \LogicException('Module membership changed: call init with the complete module list, then exec');
			}
			$candidate = new file();
			$candidate->path = $path;
			$candidate->disk_source = true;
			if ($previous !== null) {
				$old = object_cast($previous, file::class);
				$candidate->disk_source = $old->disk_source;
				$candidate->content = $old->content;
			}
			if ($candidate->disk_source)
			{
				if (!fs_is_file($path))
				{
					if ($previous !== null) {
						$old = object_cast($previous, file::class);
						if ($old->tokens !== null) {
							$candidate->changes = \scpp\compiler\SYNC_DELETED;
						}
					}
				}
			}
			$queue->enqueue($candidate);
		}
		$items /** vector<source_work> */ = $queue->items();
		$published = task_run_publish_unordered($items, $this->jobs,
		function (source_work $work) use ($queue): source_work
		{
			$queue->start($work);
			try {
				if ($work->source->changes !== \scpp\compiler\SYNC_DELETED) {
					$work->tokens = (new Tokenizer($work->source))->tokenize();
					$work->result = (new Parser(object_cast($work->tokens, token_list::class)))->parse();
				}
			}
			catch (\Throwable $error) {
				$queue->fail($work);
				throw $error;
			}
			return $work;
		},
		function (source_work $work) use ($queue): bool {
			Compiler::publish_update($work);
			$queue->complete($work);
			return true;
		});
		if (($published !== q_count($items)) || (!$queue->finished())) {
			throw new \LogicException('Update barrier reached before publication completed');
		}
		self::order_roots();
	}

	/** Locate a live source by its module-qualified path; tombstones never participate. */
	private static function find_source(string $path): ?file
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

	/** Return only the previous live syntax; retained deletions are not a version history. */
	private static function find_syntax(string $path): ?parsed_file
	{
		foreach (Model::$syntax_files as $parsed) {
			if (($parsed->tokens->file->path === $path) && ($parsed->tokens->file->changes !== \scpp\compiler\SYNC_DELETED)) {
				return $parsed;
			}
		}
		return null;
	}

	/** Compare significant token spellings within AST-selected declaration/body boundaries. */
	private static function spelling(token_list $tokens, int $start, int $end): string
	{
		$rows /** Storage<token> */ = $tokens->tokens;
		$result = '';
		for ($index = $start; $index < $end; $index++) {
			$text = $rows[$index]->text();
			$result .= string_byte_len($text) . ':' . $text;
		}
		return $result;
	}

	/** Function headers and bodies are independent; other declarations compare their full syntax. */
	private static function declaration_text(collected_name $entry, bool $body): string
	{
		$node = $entry->node;
		$start = (int) $node->token_index;
		$end = (int) $node->end_token_index;
		if ($node->kind === node_kind::function_declaration)
		{
			$function = Syntax_Nodes::function_data($node);
			if ($body) {
				$start = (int) $function->body->token_index;
			}
			else {
				$end = (int) $function->body->token_index;
			}
		}
		elseif ($body) {
			return '';
		}
		return self::spelling($entry->file->source, $start, $end);
	}

	/** Names identify candidate groups; enclosing syntax distinguishes members and function locals. */
	private static function declaration_key(collected_name $entry): string
	{
		$key = Node_Kind_Name::text($entry->node->kind) . ':' . $entry->name;
		$parent = $entry->node->parent();
		$tokens /** Storage<token> */ = $entry->file->source->tokens;
		while ($parent !== null)
		{
			$node = object_cast($parent, ast_node::class);
			if ($node->kind === node_kind::function_declaration) {
				$index = Syntax_Nodes::function_data($node)->name_token_index;
				$key = 'function:' . $tokens[$index]->text() . '/' . $key;
			}
			elseif ($node->kind === node_kind::struct_declaration) {
				$index = Syntax_Nodes::struct_data($node)->name_token_index;
				$key = 'struct:' . $tokens[$index]->text() . '/' . $key;
			}
			$parent = $node->parent();
		}
		return $key;
	}

	/** Match equal duplicates first, then unambiguous remaining keys; retain unmatched old rows deleted. */
	private static function compare_declarations(parsed_file $previous, parsed_file $candidate): void
	{
		$old_entries /** Storage<collected_name> */ = $previous->collection->entries;
		$new_entries /** Storage<collected_name> */ = $candidate->collection->entries;
		$matched /** hash<bool, int> */ = [];
		$paired /** hash<bool, int> */ = [];
		for ($pass = 0; $pass < 2; $pass++)
		{
			foreach ($candidate->collection->defined_elements as $new_index)
			{
				if (isset($paired[$new_index])) {
					continue;
				}
				$entry = $new_entries[$new_index];
				$key = self::declaration_key($entry);
				$found = -1;
				$count = 0;
				foreach ($previous->collection->defined_elements as $old_index)
				{
					$old = $old_entries[$old_index];
					if (isset($matched[$old_index]) || ($old->changes === \scpp\compiler\SYNC_DELETED)) {
						continue;
					}
					if (self::declaration_key($old) !== $key) {
						continue;
					}
					if ($pass === 0) {
						if ((self::declaration_text($old, false) !== self::declaration_text($entry, false)) || (self::declaration_text($old, true) !== self::declaration_text($entry, true))) {
							continue;
						}
					}
					$found = $old_index;
					$count++;
					if ($pass === 0) {
						break;
					}
				}
				if ($count !== 1) {
					continue;
				}
				if ($pass === 1)
				{
					$remaining = 0;
					foreach ($candidate->collection->defined_elements as $other_index) {
						if (!isset($paired[$other_index])) {
							if (self::declaration_key($new_entries[$other_index]) === $key) {
								$remaining++;
							}
						}
					}
					if ($remaining !== 1) {
						continue;
					}
				}
				$old = $old_entries[$found];
				$entry->changes = 0;
				if (self::declaration_text($old, false) !== self::declaration_text($entry, false)) {
					$entry->changes = $entry->changes + \scpp\compiler\SYNC_CHANGED;
				}
				if (self::declaration_text($old, true) !== self::declaration_text($entry, true)) {
					$entry->changes = $entry->changes + \scpp\compiler\SYNC_BODY_CHANGED;
				}
				$paired[$new_index] = true;
				$matched[$found] = true;
			}
		}
		foreach ($previous->collection->defined_elements as $old_index)
		{
			if (isset($matched[$old_index])) {
				continue;
			}
			$old = $old_entries[$old_index];
			$old->changes = \scpp\compiler\SYNC_DELETED;
			$position = $new_entries->append($old);
			$candidate->collection->defined_elements[] = $position;
		}
	}

	/** Replace only one file; old deleted declarations remain observable but are never resolved. */
	private static function publish_update(source_work $work): void
	{
		$source = $work->source;
		$syntax /** Storage<parsed_file> */ = Model::$syntax_files;
		$previous = self::find_syntax($source->path);
		$position = -1;
		foreach ($syntax as $index => $parsed)
		{
			if ($parsed->tokens->file->path === $source->path) {
				if ($parsed->tokens->file->changes !== \scpp\compiler\SYNC_DELETED) {
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
				$old->tokens->file->changes = \scpp\compiler\SYNC_DELETED;
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
			self::compare_declarations($old, $candidate);
			$source->changes = 0;
			if ($old->tokens->content !== $candidate->tokens->content) {
				$source->changes = \scpp\compiler\SYNC_CHANGED;
			}
		}
		// Remove replaced live references; keep actual deletions as tombstones in global indexes.
		$global = Model::$global_scope;
		$global->functions = self::retain_other($global->functions, $source->path);
		$global->types = self::retain_other($global->types, $source->path);
		$global->variables = self::retain_other($global->variables, $source->path);
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

	/** A replacement drops obsolete live references, never other files or deleted candidates. */
	private static function retain_other(array $index /** hash<vector<collected_name>> */, string $path): array /** hash<vector<collected_name>> */
	{
		$result /** hash<vector<collected_name>> */ = [];
		foreach ($index as $name => $entries)
		{
			foreach ($entries as $entry)
			{
				if ($entry->changes === \scpp\compiler\SYNC_DELETED) {
					$result[$name][] = $entry;
					continue;
				}
				if ($entry->file->source->file->path !== $path) {
					$result[$name][] = $entry;
				}
			}
		}
		return $result;
	}

	/** Rebuild root membership in module/file order; deleted files keep their last complete records. */
	private static function order_roots(): void
	{
		$syntax /** Storage<parsed_file> */ = new Storage();
		$tokens /** Storage<token_list> */ = new Storage();
		$collections /** Storage<collected_file> */ = new Storage();
		foreach (Model::$modules as $input_module)
		{
			foreach ($input_module->files as $source)
			{
				foreach (Model::$syntax_files as $parsed)
				{
					if ($parsed->tokens->file === $source) {
						$syntax->append($parsed);
						$tokens->append($parsed->tokens);
						$collections->append($parsed->collection);
						break;
					}
				}
			}
		}
		Model::$syntax_files = $syntax;
		Model::$tokens = $tokens;
		Model::$collected_files = $collections;
	}

	/** Standalone parallel scanning; exec uses the combined pipeline without this barrier. */
	public function tokenize(): void
	{
		$this->frontend(true, false);
	}

	/** Explicitly reparse existing snapshots without rereading their source files. */
	public function parse(): void
	{
		$this->frontend(false, true);
	}

	/** One bounded worker reads, tokenizes and immediately parses its file before publication. */
	private function frontend(bool $scan, bool $parse): void
	{
		if ($scan) {
			Model::reset_tokens();
		}
		else {
			Model::reset_syntax();
		}
		if ($this->jobs < 1) {
			throw new \LogicException('Compiler job limit must be positive');
		}
		$queue = new Source_Work_Queue();
		if ($scan) {
			foreach (Model::$modules as $input_module) {
				foreach ($input_module->files as $source) {
					$queue->enqueue($source);
				}
			}
		}
		else {
			foreach (Model::$tokens as $tokens) {
				$queue->enqueue($tokens->file, $tokens);
			}
		}
		$items /** vector<source_work> */ = $queue->items();
		$published = task_run_publish_unordered($items, $this->jobs,
		function (source_work $work) use ($queue, $scan, $parse): source_work
		{
			$queue->start($work);
			try
			{
				if ($scan) {
					$work->tokens = (new Tokenizer($work->source))->tokenize();
				}
				if ($parse) {
					$tokens = object_cast($work->tokens, token_list::class);
					$work->result = (new Parser($tokens))->parse();
				}
			}
			catch (\Throwable $error) {
				$queue->fail($work);
				throw $error;
			}
			return $work;
		},
		function (source_work $work) use ($queue, $scan, $parse): bool
		{
			if ($scan) {
				$tokens = object_cast($work->tokens, token_list::class);
				$work->source->tokens = $tokens;
				Model::$tokens[] = $tokens;
			}
			if ($parse) {
				Compiler::publish_parsed(object_cast($work->result, parsed_file::class));
			}
			$queue->complete($work);
			return true;
		});
		if (($published !== q_count($items)) || (!$queue->finished())) {
			throw new \LogicException('Frontend barrier reached before work completed');
		}
		// Work is unordered; restore retained file order only after every worker has joined.
		$token_files /** Storage<token_list> */ = new Storage();
		$syntax_files /** Storage<parsed_file> */ = new Storage();
		$collected_files /** Storage<collected_file> */ = new Storage();
		foreach ($items as $work)
		{
			if ($scan) {
				$token_files->append(object_cast($work->tokens, token_list::class));
			}
			if ($parse) {
				$parsed = object_cast($work->result, parsed_file::class);
				$syntax_files->append($parsed);
				$collected_files->append($parsed->collection);
			}
		}
		if ($scan) {
			Model::$tokens = $token_files;
		}
		if ($parse) {
			Model::$syntax_files = $syntax_files;
			Model::$collected_files = $collected_files;
		}
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
		$root_scope = object_cast(weakref_get(Syntax_Nodes::block_data($parsed->root)->scope), scope::class);
		if (weakref_get($root_scope->publication) !== null) {
			throw new \LogicException('Parsed file was already published');
		}
		$global = Model::$global_scope;
		$entries /** Storage<collected_name> */ = $parsed->collection->entries;
		foreach ($parsed->collection->defined_elements as $index)
		{
			$entry = $entries[$index];
			if (($entry->changes === \scpp\compiler\SYNC_DELETED) || ($entry->kind === collected_name_kind::field_declaration)) {
				continue;
			}
			$entry_scope = object_cast(weakref_get($entry->scope), scope::class);
			if ($entry_scope !== $root_scope) {
				continue;
			}
			if ($entry->kind === collected_name_kind::function_declaration) {
				$global->functions[$entry->name][] = $entry;
			}
			elseif ($entry->kind === collected_name_kind::struct_declaration) {
				$global->types[$entry->name][] = $entry;
			}
			else {
				$global->variables[$entry->name][] = $entry;
			}
		}
		$root_scope->publication = $global;
	}

	/** Prepare all sources and emit one LLVM module per source file. */
	public function llvm(): void
	{
		Model::reset_llvm();
		$policy = new llvm_policy();
		$sources /** Storage<collected_file> */ = new Storage();
		foreach (Model::$collected_files as $source) {
			if ($source->source->file->changes !== \scpp\compiler\SYNC_DELETED) {
				$sources->append($source);
			}
		}
		$prepared_files = (new LLVM_Preparation())->prepare_program($sources, $policy);
		Model::$llvm_files = (new LLVM_Generator())->generate($prepared_files, $policy);
	}
}
