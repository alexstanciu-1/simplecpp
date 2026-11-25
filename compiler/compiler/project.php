<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Owns project loading, change checks, compilation and publication; never writes host output. */
final class Project_Compiler
{
	/** PROPERTIES */
	private readonly ?string $runtime;

	/** CONSTRUCTOR */
	public function __construct(?string $runtime = null)
	{
		$this->runtime = $runtime;
	}

	/** METHODS */
	/** Incremental analyzer entry point; never invokes preparation, lowering or native emission. */
	public function frontend(string $path, ?compilation_session $previous = null, ?string $output_directory = null): compilation_session
	{
		$candidate = (new Module_Loader($this->runtime))->load($path, $output_directory);
		$selection_changed = $previous !== null && !$candidate->type_availability->same_as($previous->type_availability);
		$result = (new Incremental_Frontend())->collect($candidate, $selection_changed ? null : $previous);
		if ($selection_changed) $previous->is_current = false;
		return $result;
	}

	/**
	 * Compile the selected application; dependencies contribute metadata and prebuilt link inputs
	 * only.
	 *
	 * Return: compilation report.
	 *
	 * @param string[] $optimize_functions
	 * @return mixed[]
	 */
	public function run(string $path, string $facts, string $directory, bool $force = false, array $optimize_functions = []): array /** array<string,mixed> */
	{
		$session = (new Module_Loader($this->runtime))->load($path, $directory);
		return $this->run_loaded($session, $facts, $directory, $force, $optimize_functions);
	}

	/**
	 * Compile the session whose dependency graph was loaded by the caller.
	 * @param string[] $optimize_functions
	 * @return mixed[]
	 */
	public function run_loaded(compilation_session $session, string $facts, string $directory, bool $force, array $optimize_functions /** list<string> */): array /** array<string,mixed> */
	{
		$stamps = Project_Changes::capture($session, $facts, $directory, $optimize_functions);
		[$previous, $cached, $state, $settled] = Cpp_Bundle::with_reader($directory,
			static function (bool $present) use ($directory, $force, $stamps): array {
				$snapshot = $present ? Cpp_Bundle::snapshot($directory) : new cpp_bundle_snapshot([], null);
				$baseline = Cpp_Bundle::baseline($snapshot->manifest);
				$state = $stamps;
				$settled = [];
				Project_Changes::confirm($state, $baseline, $settled);
				$cached = $force || $baseline === null ? null : Cpp_Bundle::reuse($directory, $state, $snapshot->manifest);
				return [$snapshot, $cached, $state, $settled];
			});
		$removed = Project_Changes::mark($session, $state, Cpp_Bundle::baseline($previous->manifest));
		$changes = [];
		foreach (array_merge(array_values($session->units()), $removed) as $unit) {
			if ($unit->status !== change_status::none) {
				$changes[$unit->module->root . '/' . $unit->path] = $unit->status->name;
			}
		}
		if ($cached !== null) {
			if ($settled !== []) {
				foreach ($settled as $name => $paths) {
					foreach ($paths as $path) unset($state['modules'][$name]['sources'][$path]['sha256']);
				}
				Cpp_Bundle::confirm_inputs($directory, $previous, $state);
			}
			return ['manifest' => $directory . '/manifest.json',
				'sources' => count($cached['sources']), 'translation_units' => count($cached['translation_units']),
				'rebuilt' => false];
		}
		return $this->compile($session, $facts, $directory, $stamps, $changes, $previous, $optimize_functions);
	}

	/**
	 * Both input adapters publish through the same preparation and bundle path.
	 *
	 * $state: captured input state.
	 * $changes: source path -> change status.
	 * $previous: pinned previous bundle snapshot or validated inventory.
	 * Return: compilation report; counts and source changes.
	 *
	 * @param mixed[]|null $state
	 * @param string[] $changes
	 * @param string[] $optimize_functions
	 * @param mixed[]|cpp_bundle_snapshot|null $previous
	 * @return mixed[]
	 */
	public function compile(compilation_session $session, string $facts, string $directory, ?array $state /** array<string,mixed> */ = null,
		array $changes /** array<string,string> */ = [],
		array|cpp_bundle_snapshot|null $previous /** array<string,mixed> */ = null, array $optimize_functions = []): array /** array<string,mixed> */
	{
		$target = Native_Target::load($facts);
		$read_started = time();
		$program = (new Program_Frontend())->prepare($session, $target);
		$output = Cpp_Program::emit($program, destination_plans: Destination_Plan::select($program, $optimize_functions));
		$inputs = [$facts];
		foreach ($session->modules as $module) {
			if ($module->manifest !== null) {
				$inputs[] = $module->manifest;
			}
		}
		foreach ($program->files as $file) {
			$inputs[] = $file->tokens->source->path;
		}
		if (($state !== null) && (Project_Changes::capture($session, $facts, $directory, $optimize_functions) !== $state)) {
			throw new \RuntimeException('Project inputs changed during compilation; retry the build');
		}
		if ($state !== null) {
			foreach ($session->entry->sources as $unit) {
				$source = &$state['modules'][$session->entry->name]['sources'][$unit->path];
				if (!Project_Changes::needs_confirmation($source['mtime'], $read_started)) continue;
				$snapshot = $program->files[$session->source_key($unit)]->tokens->source;
				$digest = hash('sha256', $snapshot->content);
				$verified = time();
				if (Project_Changes::digest($session->entry->root . '/' . $unit->path) !== $digest) {
					throw new \RuntimeException('Project inputs changed during compilation; retry the build');
				}
				if (Project_Changes::needs_confirmation($source['mtime'], $verified)) $source['sha256'] = $digest;
			}
			unset($source);
		}
		Cpp_Bundle::write($directory, $output, $inputs, $state, $previous);
		return ['manifest' => $directory . '/manifest.json',
			'sources' => count($program->files), 'translation_units' => count($output->translation_units()), 'rebuilt' => true,
			'changes' => $changes];
	}
}
