<?php

/*
 * Role: coordinate the currently imported compiler stages.
 * Call map: init -> exec/update -> sync (private read/tokenize/parse, locked replacement) -> cpp/llvm.
 * Output: retained sources, syntax, collection and separately selected C++/LLVM artifacts.
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
	/** Experimental LLVM entry remains available for existing regression callers. */
	public function exec(): void
	{
		$this->sync_live();
		$this->llvm();
	}

	/** Run the initial v0.2 C++ path without invoking LLVM preparation or emission. */
	public function exec_cpp(): void
	{
		$this->sync_live();
		$this->cpp();
	}

	/** Initial generation paths share the same source synchronization. */
	private function sync_live(): void
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
	}

	/** Apply file notifications, then rerun all existing resolution/preparation and generation. */
	public function update(array $paths /** vector<string> */): void
	{
		$this->sync($paths);
		$this->llvm();
	}

	public function update_cpp(array $paths /** vector<string> */): void
	{
		$this->sync($paths);
		$this->cpp();
	}

	/** Initial compilation and later updates share private work and the same publication path. */
	public function sync(array $paths /** vector<string> */): void
	{
		if ($this->jobs < 1) {
			throw new \LogicException('Compiler job limit must be positive');
		}
		Model::reset_llvm();
		Model::reset_cpp();
		$queue = Source_Synchronization::plan($paths);
		$items /** vector<source_work> */ = $queue->items();
		$published = task_run_publish_unordered($items, $this->jobs,
		function (source_work $work) use ($queue): source_work {
			return Source_Frontend::run($work, $queue, frontend_operation::synchronize);
		},
		function (source_work $work) use ($queue): bool {
			Source_Publication::publish_update($work);
			$queue->complete($work);
			return true;
		});
		if (($published !== q_count($items)) || (!$queue->finished())) {
			throw new \LogicException('Update barrier reached before publication completed');
		}
		Source_Publication::order_roots();
	}

	/** Standalone parallel scanning; exec uses the combined pipeline without this barrier. */
	public function tokenize(): void
	{
		$this->frontend(frontend_operation::scan);
	}

	/** Explicitly reparse existing snapshots without rereading their source files. */
	public function parse(): void
	{
		$this->frontend(frontend_operation::parse);
	}

	/** One bounded worker reads, tokenizes and immediately parses its file before publication. */
	private function frontend(frontend_operation $operation): void
	{
		if ($operation === frontend_operation::scan) {
			Model::reset_tokens();
		}
		else {
			Model::reset_syntax();
		}
		if ($this->jobs < 1) {
			throw new \LogicException('Compiler job limit must be positive');
		}
		$queue = new Source_Work_Queue();
		if ($operation === frontend_operation::scan) {
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
		function (source_work $work) use ($queue, $operation): source_work {
			return Source_Frontend::run($work, $queue, $operation);
		},
		function (source_work $work) use ($queue, $operation): bool {
			Source_Publication::publish_stage($work, $operation);
			$queue->complete($work);
			return true;
		});
		if (($published !== q_count($items)) || (!$queue->finished())) {
			throw new \LogicException('Frontend barrier reached before work completed');
		}
		Source_Publication::order_stage($items, $operation);
	}

	/** Compatibility entry for callers publishing a completed parse under serialization. */
	public static function publish_parsed(parsed_file $parsed): void
	{
		Source_Publication::publish_parsed($parsed);
	}

	/** Publish complete preparation and C++ together; unsupported input leaves no stale output. */
	public function cpp(): void
	{
		Model::reset_cpp();
		$sources /** Storage<collected_file> */ = new Storage();
		foreach (Model::$collected_files as $source) {
			if ($source->source->file->changes !== \scpp\compiler\SYNC_DELETED) {
				$sources->append($source);
			}
		}
		if (q_count($sources) !== 1) {
			throw new \RuntimeException('The first C++ slice requires exactly one source file');
		}
		try {
			$prepared = (new File_Preparation($sources[0], Model::$language_scope))->prepare();
			$output = (new CPP_Generator())->generate($prepared);
			Model::$prepared_files[] = $prepared;
			Model::$cpp_files[] = $output;
		}
		catch (\Throwable $error) {
			Model::reset_cpp();
			throw $error;
		}
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
