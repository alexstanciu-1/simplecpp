<?php

/*
 * Role: coordinate the currently imported compiler stages.
 * Call map: init -> exec_cpp/update_cpp or exec_llvm/update_llvm -> sync (scan, notify, tokenize, retained parse/collect, cleanup) -> prepare + cpp / llvm.
 * Output: retained sources, syntax, collection and separately selected C++/LLVM artifacts.
 */
namespace scpp\compiler;

/** Shared default concurrency budget for the per-file frontend pipeline. */
const DEFAULT_COMPILER_JOBS = 12;

final class Compiler
{
	/** Constructor installs the configured default before use. */
	public int $jobs = 0;
	public cpp_lowering_options $cpp_lowering;

	public function __construct(?cpp_lowering_options $cpp_lowering = null)
	{
		$this->jobs = \scpp\compiler\DEFAULT_COMPILER_JOBS;
		$this->cpp_lowering = $cpp_lowering ?? new cpp_lowering_options();
	}

	public function select_entry(string $module_name, string $source_path): void
	{
		Model::$entry_selection = new program_entry_selection($module_name, $source_path);
		Model::$prepared_entry = null;
		Model::$prepared_files = new Storage /** Storage<prepared_file> */();
		Model::$diagnostics = new Storage /** Storage<compiler_diagnostic> */();
		Compiler_Lifecycle::reset_cpp();
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

	/** Ordered source selections compare values, independent of host container operators. */
	private static function same_source_paths(array $before /** vector<string> */, array $after /** vector<string> */): bool
	{
		if (q_count($before) !== q_count($after)) { return false; }
		foreach ($before as $index => $path) {
			if ($path !== $after[$index]) { return false; }
		}
		return true;
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
				elseif (($record->declared_path !== $candidate->declared_path) || ($record->resolved_path !== $candidate->resolved_path) || ($record->position !== $candidate->position) || ($record->disk_source !== $candidate->disk_source) || ($record->logical_name !== $candidate->logical_name) || ($record->selected_sources !== $candidate->selected_sources) || !self::same_source_paths($record->source_paths, $candidate->source_paths)) {
					$record->changes = change_state::changed;
				}
				else {
					$record->changes = change_state::unchanged;
				}
				$record->declared_path = $candidate->declared_path;
				$record->logical_name = $candidate->logical_name;
				$record->resolved_path = $candidate->resolved_path;
				$record->position = $candidate->position;
				$record->disk_source = $candidate->disk_source;
				$record->selected_sources = $candidate->selected_sources;
				$record->source_paths = $candidate->source_paths;
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

	/** Portable structured reporting; reading never changes preparation or delivery state. */
	public function diagnostics(): Storage /** Storage<compiler_diagnostic> */
	{
		$result /** Storage<compiler_diagnostic> */ = new Storage();
		foreach (Model::$diagnostics as $diagnostic) {
			$result->append(new compiler_diagnostic($diagnostic->code, $diagnostic->severity,
				$diagnostic->path, $diagnostic->line, $diagnostic->column, $diagnostic->message));
		}
		return $result;
	}

	/** Parked legacy LLVM entry remains available only for existing regression callers. */
	public function exec_llvm(): void
	{
		$this->sync_live();
		$this->llvm();
	}

	/** Run the initial v0.2 C++ path without invoking LLVM preparation or emission. */
	public function exec_cpp(?Output_Publication $publication = null, ?cpp_lowering_policy $policy = null): void
	{
		$this->sync_live();
		$this->prepare(true);
		$this->cpp($publication, $policy);
	}

	/** Apply file notifications, then rerun all existing resolution/preparation and generation. */
	public function update_llvm(array $paths /** vector<string> */): void
	{
		$this->sync($paths);
		$this->llvm();
	}

	public function update_cpp(array $paths /** vector<string> */, ?Output_Publication $publication = null, ?cpp_lowering_policy $policy = null): void
	{
		$this->sync($paths);
		$this->prepare(true);
		$this->cpp($publication, $policy);
	}

	/** Combined entrypoint uses the same retained tokenization and parsing phases as direct callers. */
	public function sync(array $paths /** vector<string> */): void
	{
		Model::$diagnostics = new Storage /** Storage<compiler_diagnostic> */();
		if ($this->jobs < 1) {
			throw new \LogicException('Compiler job limit must be positive');
		}
		self::require_modules();
		if (Model::$rebuild_required) {
			Compiler_Lifecycle::reset_compilation(true);
			Model::$full_sync_pending = true;
		}
		Compiler_Lifecycle::reset_cpp();
		Compiler_Lifecycle::reset_llvm();
		Model::$prepared_entry = null;
		Model::$prepared_files = new Storage /** Storage<prepared_file> */();
		Model::$diagnostics = new Storage /** Storage<compiler_diagnostic> */();
		foreach (Model::$modules as $module) {
			if (($module->changes !== change_state::deleted) && $module->disk_source) {
				Module_Loader::discover($module);
			}
		}
		Source_Synchronization::notify($paths);
		$this->tokenize();
		$this->parse();

		// Both backends must see the same live declaration inventory after the successful join.
		$sources /** Storage<collected_file> */ = new Storage();
		foreach (Model::sources() as $record)
		{
			if ($record->parsed !== null)
			{
				$source = $record->parsed->collection;
				$source->deleted = $record->changes === change_state::deleted;
				if ($source->deleted) {
					$record->file->changes = \scpp\compiler\SYNC_DELETED;
				}
				$sources->append($source);
			}
		}
		Compiler_Lifecycle::retire_deleted_sources($sources, Model::$language_scope);
		Model::$full_sync_pending = false;
	}

	/** Host may call after delivering output; the next scan also finishes pending compaction. */
	public function cleanup_tokens(): void
	{
		try
		{
			foreach (Model::sources() as $record)
			{
				if ($record->parsed === null) {
					continue;
				}
				$parsed /** parsed_file */ = $record->parsed;
				if (($record->tokens === $parsed->tokens) && ($record->changes !== change_state::deleted)) {
					Token_Cleanup::file($parsed);
				}
			}
		}
		catch (\Throwable $error) {
			Model::$rebuild_required = true;
			throw $error;
		}
	}

	/** Tokenize added/changed files into appended storage after any pending compaction. */
	public function tokenize(): void
	{
		$this->cleanup_tokens();
		$this->frontend(frontend_operation::scan);
	}

	/** Parse changed token generations and join collection; resolution is a subsequent phase. */
	public function parse(): void
	{
		$this->frontend(frontend_operation::parse);
	}

	/** Earlier failures block this phase; initial and subsequent preparation use identical work lists. */
	public function prepare(bool $require_entry = false): void
	{
		if (Model::$rebuild_required) {
			$this->sync([]);
		}
		$sources /** Storage<collected_file> */ = new Storage();
		foreach (Model::sources() as $record)
		{
			if ($record->changes !== change_state::deleted)
			{
				if (($record->changes !== change_state::unchanged) || ($record->parsed === null)) {
					throw new \RuntimeException('Finish tokenization and parsing before preparation');
				}
				if (!$record->parsed->complete) {
					throw new \RuntimeException('Preparation is blocked by a failed parse');
				}
			}
			if ($record->parsed !== null)
			{
				$source = $record->parsed->collection;
				$source->deleted = $record->changes === change_state::deleted;
				if ($source->deleted) {
					$record->file->changes = \scpp\compiler\SYNC_DELETED;
				}
				$sources->append($source);
			}
		}
		Compiler_Lifecycle::reset_cpp();
		Model::$prepared_files = new Storage /** Storage<prepared_file> */();
		Model::$diagnostics = new Storage /** Storage<compiler_diagnostic> */();
		Model::$prepared_entry = null;
		try {
			Compiler_Lifecycle::retire_deleted_sources($sources, Model::$language_scope);
			Trait_Composition::synchronize($sources, Model::$language_scope);
			$prepared = Preparation_Run::prepare($sources, Model::$language_scope);
			$entry = Program_Preparation::entry($prepared, Model::$entry_selection, $require_entry);
			Model::$prepared_files = $prepared;
			Model::$prepared_entry = $entry;
			Model::$diagnostics = Preparation_Advisories::collect($sources);
		}
		catch (\RuntimeException $error) {
			// Expected diagnostics retain the existing incremental retry state.
			throw $error;
		}
		catch (\Throwable $error) {
			Model::$rebuild_required = true;
			Compiler_Lifecycle::reset_llvm();
			throw $error;
		}
	}

	/** Emit from completed shared preparation; failure discards only C++ artifacts. */
	public function cpp(?Output_Publication $publication = null, ?cpp_lowering_policy $policy = null): void
	{
		Compiler_Lifecycle::reset_cpp();
		$prepared /** Storage<prepared_file> */ = Model::$prepared_files;
		if ($prepared->is_empty() || (Model::$prepared_entry === null)) {
			throw new \RuntimeException('C++ emission requires a prepared program entry; run prepare first');
		}
		$output /** Storage<cpp_artifact> */;
		try {
			$entry /** prepared_file */ = Model::$prepared_entry;
			$output = (new CPP_Generator(Model::$cpp_output_program, $this->cpp_lowering, $policy))->generate_program($prepared, $entry);
			if ($publication !== null) {
				$publication->publish($output, CPP_Runtime_Build::requirements(Model::$runtime_metadata),
					Model::$cpp_output_program->prelower_templates, $policy);
			}
		}
		catch (\RuntimeException $error) {
			throw $error;
		}
		catch (\Throwable $error) {
			Model::$rebuild_required = true;
			throw $error;
		}
		Model::$cpp_files = $output;
		// The single active consumer acknowledges only after successful output publication.
		$consumed /** Storage<collected_file> */ = new Storage();
		foreach ($prepared as $file) {
			$consumed->append($file->source);
		}
		foreach (Callable_Inventory::source_collections($consumed) as $source) {
			$source->preparation_changes = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
		}
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

	/** Dispatch one frontend phase; parsing mutates retained declarations and reports errors after joining. */
	private function frontend(frontend_operation $operation): void
	{
		if (Model::$rebuild_required) {
			$this->sync([]);
			return;
		}
		self::require_modules();
		if ($operation === frontend_operation::parse) {
			Compiler_Lifecycle::reset_cpp();
			Compiler_Lifecycle::reset_llvm();
		}
		if ($this->jobs < 1) {
			throw new \LogicException('Compiler job limit must be positive');
		}
		$queue = new Source_Work_Queue();
		if ($operation === frontend_operation::scan)
		{
			foreach (Model::sources() as $record)
			{
				if (($record->changes !== change_state::added) && ($record->changes !== change_state::changed)) {
					continue;
				}
				// Reads and lexical failures must not mutate the currently published snapshot.
				$source = new file();
				$source->path = $record->path;
				$source->disk_source = $record->file->disk_source;
				$source->content = $record->file->content;
				$source->mtime = $record->file->mtime;
				$source->size = $record->file->size;
				$source->changes = $record->file->changes;
				$queue->enqueue($record, $source);
			}
		}
		else
		{
			foreach (Model::sources() as $record)
			{
				if (($record->changes !== change_state::added) && ($record->changes !== change_state::changed)) {
					continue;
				}
				if ($record->tokens === null) {
					throw new \LogicException('Tokenize changed files before parsing');
				}
				$tokens /** token_list */ = $record->tokens;
				$queue->enqueue($record, $tokens->file, $tokens);
			}
		}
		$items /** vector<source_work> */ = $queue->items();
		$published = task_run_publish_unordered($items, $this->jobs,
		function (source_work $work) use ($queue, $operation): source_work {
			return Source_Frontend::run($work, $queue, $operation);
		},
		function (source_work $work) use ($queue, $operation): bool {
			Source_Publication::publish_stage($work, $operation);
			if ($work->state !== work_state::failed) {
				$queue->complete($work);
			}
			return true;
		});
		if ($operation === frontend_operation::parse) {
			$this->finish_collection($items);
		}
		if (($published !== q_count($items)) || (!$queue->finished())) {
			throw new \LogicException('Frontend barrier reached before work completed');
		}
	}

	/** Global deletion checks run after the join; a failed file cannot prove an unseen symbol absent. */
	private function finish_collection(array $items /** vector<source_work> */): void
	{
		foreach (Model::sources() as $record)
		{
			if ($record->parsed === null) {
				continue;
			}
			$parsed /** parsed_file */ = $record->parsed;
			if ($record->changes === change_state::deleted) {
				$entries /** Storage<collected_name> */ = $parsed->collection->entries;
				foreach ($entries as $entry) {
					$entry->change_status = change_state::deleted;
				}
			}
			elseif ($parsed->complete) {
				Symbol_Collector::sweep($parsed->collection, true);
			}
		}
		$errors = '';
		foreach ($items as $work) {
			if ($work->state === work_state::failed) {
				$errors .= $work->error . "\n";
			}
		}
		if ($errors !== '') {
			throw new \RuntimeException($errors);
		}
	}
}
