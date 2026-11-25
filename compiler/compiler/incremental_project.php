<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Retains one app's frontend and emitted bundle across edits; the native host runs Ninja after publication.
 * Example: run(), edit a source, run() again; unchanged source text and native object timestamps survive.
 */
final class Incremental_Project
{
	/** PROPERTIES */
	/** Native owning; one sequential current generation, including recoverable preparation failures. */
	private ?compilation_session $session = null;
	/** Native owning; last successfully published text, not a second AST or preparation model. */
	private ?cpp_program_output $output = null;
	/** @var array<string,array<string,Destination_Plan>> Last successfully published selection. */
	private array $destination_plans = [];
	private readonly Project_Compiler $compiler;
	private readonly string $application;
	private readonly string $directory;
	/** Native shared-owning immutable target facts; changing the target requires a new build context. */
	private readonly entry_target $target;

	/** CONSTRUCTOR */
	public function __construct(string $application, string $directory, entry_target $target, ?string $runtime = null)
	{
		$this->application = $application;
		$this->directory = $directory;
		$this->target = $target;
		$this->compiler = new Project_Compiler($runtime);
	}

	/** METHODS */
	/**
	 * Publish only after all preparation and emission succeeds; failed native builds retry the published bundle.
	 * @param string[] $optimize_functions
	 * @return mixed[]
	 */
	public function run(array $optimize_functions /** list<string> */ = []): array /** array<string,mixed> */
	{
		$this->session = $this->compiler->frontend($this->application, $this->session, $this->directory);
		$session = $this->session;
		try {
			return $this->compile($session, $optimize_functions);
		}
		catch (\Throwable $error) {
			Generation_Cleanup::failed($session);
			throw $error;
		}
	}

	/** Continue a published frontend generation; only successful output acknowledges downstream work.
	 * @param string[] $optimize_functions
	 * @return mixed[]
	 */
	private function compile(compilation_session $session, array $optimize_functions /** list<string> */): array /** array<string,mixed> */
	{
		$report = Incremental_Preparation::prepare($session, $this->target);
		$files = $session->files();
		$entry = $session->entry_key();
		$program = new prepared_program($entry, $files, new prepared_entry($files[$entry], $this->target), $session);
		$changed = $session->preparation_work->emission_sources();
		if (($this->output !== null) && ($this->output->entry_path !== $entry)) {
			$changed[$entry] = true;
			$changed[$this->output->entry_path] = true;
		}
		$plans = Destination_Plan::select($program, $optimize_functions, $this->destination_plans);
		foreach ($plans + $this->destination_plans as $path => $_) {
			if (($plans[$path] ?? []) !== ($this->destination_plans[$path] ?? [])) $changed[$path] = true;
		}
		$output = Cpp_Program::emit($program, $this->output, $changed, $plans);
		$inputs = array_map(static fn(ast_file_node $file): string => $file->source()->path, $files);
		Cpp_Bundle::write($this->directory, $output, array_values($inputs));
		$this->destination_plans = $plans;
		$this->output = $output;
		$session->preparation_work->complete_emission();
		Generation_Cleanup::close($session);
		return ['preparation' => $report, 'emitted_sources' => $output->emitted_sources,
			'manifest' => $this->directory . '/manifest.json'];
	}

	/** Current analysis state remains available after a preparation error; a parse failure retains the prior generation. */
	public function session(): ?compilation_session
	{
		return $this->session;
	}
}
