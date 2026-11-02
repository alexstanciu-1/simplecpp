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

	/** Compile the selected application; dependencies contribute metadata and prebuilt link inputs only. */
	public function run(string $path, string $facts, string $directory, bool $force = false): array /** compilation report */
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

	/** Both input adapters publish through the same preparation and bundle path. */
	public function compile(compilation_session $session, string $facts, string $directory, ?array $state /** captured input state */ = null,
		array $changes /** array<string,string>; source path -> change status */ = [],
		?array $previous /** validated previous bundle manifest */ = null): array /** compilation report; counts and source changes */
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
			'sources' => count($program->files), 'translation_units' => count($output->files), 'rebuilt' => true, 'changes' => $changes];
	}
}
