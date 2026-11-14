<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Owns project loading, change checks, compilation and publication; never writes host output. */
final class Project_Compiler
{
	private readonly ?string $runtime;

	public function __construct(?string $runtime = null)
	{
		$this->runtime = $runtime;
	}

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
	 * @return mixed[]
	 */
	public function run(string $path, string $facts, string $directory, bool $force = false): array /** array<string,mixed> */
	{
		$session = (new Module_Loader($this->runtime))->load($path, $directory);
		$state = Project_Changes::capture($session, $facts, $directory);
		$previous = Cpp_Bundle::read($directory);
		$removed = Project_Changes::mark($session, $state, Cpp_Bundle::baseline($previous));
		$changes = [];
		foreach (array_merge(array_values($session->units()), $removed) as $unit) {
			if ($unit->status !== change_status::none) {
				$changes[$unit->module->root . '/' . $unit->path] = $unit->status->name;
			}
		}
		$cached = $force ? null : Cpp_Bundle::reuse($directory, $state, $previous);
		if ($cached !== null) {
			return ['manifest' => $directory . '/manifest.json',
				'sources' => count($cached['sources']), 'translation_units' => count($cached['translation_units']),
				'rebuilt' => false];
		}
		return $this->compile($session, $facts, $directory, $state, $changes, $previous);
	}

	/**
	 * Both input adapters publish through the same preparation and bundle path.
	 *
	 * $state: captured input state.
	 * $changes: source path -> change status.
	 * $previous: validated previous bundle manifest.
	 * Return: compilation report; counts and source changes.
	 *
	 * @param mixed[]|null $state
	 * @param string[] $changes
	 * @param mixed[]|null $previous
	 * @return mixed[]
	 */
	public function compile(compilation_session $session, string $facts, string $directory, ?array $state /** array<string,mixed> */ = null,
		array $changes /** array<string,string> */ = [],
		?array $previous /** array<string,mixed> */ = null): array /** array<string,mixed> */
	{
		$target = Native_Target::load($facts);
		$program = (new Program_Frontend())->prepare($session, $target);
		$output = Cpp_Program::emit($program);
		$inputs = [$facts];
		foreach ($session->modules as $module) {
			if ($module->manifest !== null) {
				$inputs[] = $module->manifest;
			}
		}
		foreach ($program->files as $file) {
			$inputs[] = $file->tokens->source->path;
		}
		if (($state !== null) && (Project_Changes::capture($session, $facts, $directory) !== $state)) {
			throw new \RuntimeException('Project inputs changed during compilation; retry the build');
		}
		Cpp_Bundle::write($directory, $output, $inputs, $state, $previous);
		return ['manifest' => $directory . '/manifest.json',
			'sources' => count($program->files), 'translation_units' => count($output->translation_units()), 'rebuilt' => true,
			'changes' => $changes];
	}
}
