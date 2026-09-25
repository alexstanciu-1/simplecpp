<?php

/*
 * Role: coordinate the currently imported compiler stages.
 * Call map: main.php -> init -> exec -> frontend (read/tokenize/parse per file) -> llvm.
 * Output: retained sources, syntax, collection and per-source LLVM modules.
 */
namespace scpp\compiler;

/** Shared default concurrency budget for the per-file frontend pipeline. */
const DEFAULT_COMPILER_JOBS = 12;

final class Compiler
{
	public int $jobs;
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
		$this->frontend(true, true);
		$this->llvm();
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
