<?php

/*
 * Role: coordinate the currently imported compiler stages.
 * Call map: main.php -> init -> exec -> tokenize -> parse -> llvm.
 * Output: retained sources, syntax, collection and per-source LLVM modules.
 */
namespace scpp\compiler;

final class Compiler
{
	public int $jobs = 1;
	/** Load one module per input folder, retaining the requested order. */
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
		$this->tokenize();
		$this->parse();
		$this->llvm();
	}

	/** Tokenize each module's files and retain the completed collections. */
	public function tokenize(): void
	{
		Model::reset_tokens();

		foreach (Model::$modules as $m)
		{
			foreach ($m->files as $file) {
				$tok = new Tokenizer($file);
				$tokens = $tok->tokenize();
				$file->tokens = $tokens;
				Model::$tokens[] = $tokens;
			}
		}
	}

	/** Parse privately, then publish each completed file through the compiler. */
	public function parse(): void
	{
		Model::reset_syntax();
		if ($this->jobs < 1) {
			throw new \LogicException('Compiler job limit must be positive');
		}

		$queue = new Parse_Work_Queue();
		foreach (Model::$tokens as $tokens) {
			$queue->enqueue($tokens);
		}
		$items /** vector<parse_work> */ = $queue->items();
		$published = task_run_publish_unordered($items, $this->jobs,
		function (parse_work $work) use ($queue): parse_work
		{
			$queue->start($work);
			try {
				$work->result = (new Parser($work->tokens))->parse();
			}
			catch (\Throwable $error) {
				$queue->fail($work);
				throw $error;
			}
			return $work;
		},
		function (parse_work $work) use ($queue): bool {
			Compiler::publish_parsed(object_cast($work->result, parsed_file::class));
			$queue->complete($work);
			return true;
		});
		if (($published !== q_count($items)) || (!$queue->finished())) {
			throw new \LogicException('Parsing barrier reached before work completed');
		}
		// Publication happens immediately; retained output order follows input order only after joining.
		$syntax_files /** Storage<parsed_file> */ = new Storage();
		$collected_files /** Storage<collected_file> */ = new Storage();
		foreach ($items as $work) {
			$parsed = object_cast($work->result, parsed_file::class);
			$syntax_files->append($parsed);
			$collected_files->append($parsed->collection);
		}
		Model::$syntax_files = $syntax_files;
		Model::$collected_files = $collected_files;
	}

	/** Publication boundary: caller serializes this operation; parser workers never edit global indexes. */
	public static function publish_parsed(parsed_file $parsed): void
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
		Model::$syntax_files[] = $parsed;
		Model::$collected_files[] = $parsed->collection;
	}

	/** Prepare all sources and emit one LLVM module per source file. */
	public function llvm(): void
	{
		Model::reset_llvm();
		$policy = new llvm_policy();
		$prepared_files = (new LLVM_Preparation())->prepare_program(Model::$collected_files, $policy);
		Model::$llvm_files = (new LLVM_Generator())->generate($prepared_files, $policy);
	}
}
