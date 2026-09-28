<?php

/*
 * Role: coordinate the currently imported compiler stages.
 * Call map: init -> exec_cpp/update_cpp or exec_llvm/update_llvm -> sync (private read/tokenize/parse, locked replacement) -> prepare + cpp / llvm.
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

	/** Unnamed modules use their declared path as the reconciliation key. */
	public function init(array $paths /** vector<string> */): void
	{
		$inputs /** Storage<module_input> */ = new Storage();
		foreach ($paths as $path) {
			$inputs->append(new module_input($path));
		}
		$this->init_modules($inputs);
	}

	/** Reconcile in place; only configuration changes replace the ordered module store. */
	public function init_modules(Storage $inputs /** Storage<module_input> */): bool
	{
		$incoming /** Keyed_Storage<module> */ = Module_Loader::configuration($inputs);
		if (!Compiler_Lifecycle::initialized()) {
			Compiler_Lifecycle::reset();
		}
		$modules /** Keyed_Storage<module> */ = Model::$modules;

		$revision = Compiler_Lifecycle::next_revision();
		$changed = !Model::$modules_ready;

		// Match by key, retaining existing identities while comparing the input position.
		foreach ($incoming as $candidate)
		{
			if (isset($modules[$candidate->name]))
			{
				$record = $modules[$candidate->name];
				if ($record->changes === change_state::deleted) {
					$record->changes = change_state::added;
				}
				elseif (($record->declared_path !== $candidate->declared_path) || ($record->resolved_path !== $candidate->resolved_path) || ($record->position !== $candidate->position) || ($record->disk_source !== $candidate->disk_source)) {
					$record->changes = change_state::changed;
				}
				else {
					$record->changes = change_state::unchanged;
				}
				$record->declared_path = $candidate->declared_path;
				$record->resolved_path = $candidate->resolved_path;
				$record->position = $candidate->position;
				$record->disk_source = $candidate->disk_source;
				$record->revision = $revision;
				if ($record->changes !== change_state::unchanged) {
					$changed = true;
				}
			}
			else {
				$candidate->changes = change_state::added;
				$candidate->revision = $revision;
				$changed = true;
			}
		}
		foreach ($modules as $record)
		{
			if ((int)$record->revision !== $revision) {
				if ($record->changes !== change_state::deleted) {
					$record->changes = change_state::deleted;
					$changed = true;
				}
			}
		}
		if (!$changed) {
			return false;
		}

		// Publish input order, followed by tombstones; no second order store is retained.
		$ordered /** Keyed_Storage<module> */ = new Keyed_Storage();
		foreach ($incoming as $candidate) {
			$record = $candidate;
			if (isset($modules[$candidate->name])) {
				$record = $modules[$candidate->name];
			}
			$ordered->add($record->name, $record);
		}
		foreach ($modules as $record) {
			if ($record->changes === change_state::deleted) {
				$ordered->add($record->name, $record);
			}
		}
		Model::$modules = $ordered;
		$this->rebuild_modules();
		return true;
	}

	/** Failed discovery leaves no partial source graph and identical input can retry it. */
	private function rebuild_modules(): void
	{
		Model::$modules_ready = false;
		Model::$full_sync_pending = true;
		Compiler_Lifecycle::reset_compilation();
		try
		{
			foreach (Model::$modules as $module) {
				if ($module->changes !== change_state::deleted) {
					Module_Loader::discover($module);
				}
			}
			Model::$modules_ready = true;
		}
		catch (\Throwable $error) {
			Compiler_Lifecycle::reset_compilation();
			throw $error;
		}
	}

	/** Parked legacy LLVM entry remains available only for existing regression callers. */
	public function exec_llvm(): void
	{
		$this->sync_live();
		$this->llvm();
	}

	/** Run the initial v0.2 C++ path without invoking LLVM preparation or emission. */
	public function exec_cpp(): void
	{
		$this->sync_live();
		$this->prepare();
		$this->cpp();
	}

	/** Apply file notifications, then rerun all existing resolution/preparation and generation. */
	public function update_llvm(array $paths /** vector<string> */): void
	{
		$this->sync($paths);
		$this->llvm();
	}

	public function update_cpp(array $paths /** vector<string> */): void
	{
		$this->sync($paths);
		$this->prepare();
		$this->cpp();
	}

	/** Initial compilation and later updates share private work and the same publication path. */
	public function sync(array $paths /** vector<string> */): void
	{
		if ($this->jobs < 1) {
			throw new \LogicException('Compiler job limit must be positive');
		}
		self::require_modules();
		Compiler_Lifecycle::reset_llvm();
		Compiler_Lifecycle::reset_preparation();
		foreach (Model::$modules as $module) {
			if (($module->changes !== change_state::deleted) && $module->disk_source) {
				Module_Loader::discover($module);
			}
		}
		foreach (Model::sources() as $record)
		{
			if (($record->changes === change_state::deleted) && ($record->file->changes === \scpp\compiler\SYNC_DELETED)) {
				continue;
			}
			if (Model::$full_sync_pending || ($record->changes !== change_state::unchanged)) {
				$paths[] = Source_Registry::full_path($record->owning_module(), $record->path);
			}
		}
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
		Model::$full_sync_pending = false;
	}

	/** Standalone parallel scanning; compilation uses the combined pipeline without this barrier. */
	public function tokenize(): void
	{
		$this->frontend(frontend_operation::scan);
	}

	/** Explicitly reparse existing snapshots without rereading their source files. */
	public function parse(): void
	{
		$this->frontend(frontend_operation::parse);
	}

	/** Prepare synchronized source independently of backend emission. */
	public function prepare(): void
	{
		Compiler_Lifecycle::reset_preparation();
		$sources /** Storage<collected_file> */ = new Storage();
		foreach (Model::collected_files() as $source) {
			if ($source->source_file()->changes !== \scpp\compiler\SYNC_DELETED) {
				$sources->append($source);
			}
		}
		if (q_count($sources) !== 1) {
			throw new \RuntimeException('The current preparation slice requires exactly one source file');
		}
		try {
			$prepared = (new File_Preparation($sources[0], Model::$language_scope))->prepare();
			Model::$prepared_files[] = $prepared;
		}
		catch (\Throwable $error) {
			Compiler_Lifecycle::reset_preparation();
			throw $error;
		}
	}

	/** Emit from completed shared preparation; failure discards only C++ artifacts. */
	public function cpp(): void
	{
		Compiler_Lifecycle::reset_cpp();
		$prepared /** Storage<prepared_file> */ = Model::$prepared_files;
		if (q_count($prepared) !== 1) {
			throw new \RuntimeException('C++ emission requires one prepared source file; run prepare first');
		}
		$output = (new CPP_Generator())->generate($prepared[0]);
		Model::$cpp_files[] = $output;
	}

	/** Run parked LLVM preparation and emit modules for existing regression callers. */
	public function llvm(): void
	{
		Compiler_Lifecycle::reset_llvm();
		$policy = new llvm_policy();
		$sources /** Storage<collected_file> */ = new Storage();
		foreach (Model::collected_files() as $source) {
			if ($source->source_file()->changes !== \scpp\compiler\SYNC_DELETED) {
				$sources->append($source);
			}
		}
		$prepared_files = (new LLVM_Preparation())->prepare_program($sources, $policy);
		Model::$llvm_files = (new LLVM_Generator())->generate($prepared_files, $policy);
	}

	/** Scanning selects filesystem changes; explicit notifications can additionally force reads. */
	private function sync_live(): void
	{
		$paths /** vector<string> */ = [];
		foreach (Model::sources() as $record) {
			if (!$record->file->disk_source) {
				$paths[] = Source_Registry::full_path($record->owning_module(), $record->path);
			}
		}
		$this->sync($paths);
	}

	private static function require_modules(): void
	{
		if (!Model::$modules_ready) {
			throw new \LogicException('Module discovery failed: initialize modules successfully before compilation');
		}
	}

	/** One bounded worker reads, tokenizes and immediately parses its file before publication. */
	private function frontend(frontend_operation $operation): void
	{
		self::require_modules();
		if ($operation === frontend_operation::scan) {
			Compiler_Lifecycle::reset_tokens();
		}
		else {
			Compiler_Lifecycle::reset_syntax();
		}
		if ($this->jobs < 1) {
			throw new \LogicException('Compiler job limit must be positive');
		}
		$queue = new Source_Work_Queue();
		if ($operation === frontend_operation::scan) {
			foreach (Model::sources() as $record) {
				$source = $record->file;
				$queue->enqueue($record, $source);
			}
		}
		else
		{
			foreach (Model::sources() as $record) {
				if ($record->tokens !== null) {
					$tokens /** token_list */ = $record->tokens;
					$queue->enqueue($record, $tokens->file, $tokens);
				}
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
	}
}
