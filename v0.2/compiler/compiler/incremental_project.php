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
	 * @return mixed[]
	 */
	public function run(): array /** array<string,mixed> */
	{
		$this->session = $this->compiler->frontend($this->application, $this->session, $this->directory);
		$session = $this->session;
		$report = Incremental_Preparation::prepare($session, $this->target);
		$files = $session->files();
		$entry = $session->entry_key();
		$program = new prepared_program($entry, $files, new prepared_entry($files[$entry], $this->target), $session);
		$changed = $session->preparation_work->emission_sources();
		if (($this->output !== null) && ($this->output->entry_path !== $entry)) {
			$changed[$entry] = true;
			$changed[$this->output->entry_path] = true;
		}
		$output = Cpp_Program::emit($program, $this->output, $changed);
		$inputs = array_map(static fn(ast_file_node $file): string => $file->source()->path, $files);
		Cpp_Bundle::write($this->directory, $output, array_values($inputs));
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
