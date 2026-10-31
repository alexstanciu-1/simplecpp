<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Invocation-local coordinator: discoveries are pending until every frontend result succeeds. */
final class Symbol_Publication implements declaration_sink
{
	private array $pending /** array<string,list<ast_function_declaration>> */ = [];
	private bool $published = false;

	/** Read only the initialized header; discovery must never inspect the still-unfinished body. */
	public function discovered(ast_function_declaration $function): void
	{
		if ($this->published) {
			throw new \LogicException('Cannot notify a completed symbol publication');
		}
		if (!$function->is_private) {
			$this->pending[$function->declaration_key()][] = $function;
		}
	}

	/** The coordinator supplies the complete successful inventory; failed parses cannot publish candidates. */
	public function publish(array $files /** array<string,ast_file_node> */): global_symbols
	{
		if ($this->published) {
			throw new \LogicException('Symbols already published');
		}
		$complete = [];
		foreach ($files as $file) {
			foreach ($file->functions() as $function) {
				$function->body();
				$complete[spl_object_id($function)] = true;
			}
		}
		foreach ($this->pending as $candidates)
		{
			foreach ($candidates as $candidate)
			{
				$id = spl_object_id($candidate);
				if (!isset($complete[$id])) {
					throw new \LogicException('Discovery is not part of a completed source result');
				}
				unset($complete[$id]);
			}
		}
		if ($complete !== []) {
			throw new \LogicException('Completed source has unreported declarations');
		}
		$this->published = true;
		ksort($this->pending, SORT_STRING);
		return new global_symbols($this->pending);
	}

	/** In-memory analysis clients publish their already complete collections through the same owner. */
	public static function completed(array $files /** array<string,ast_file_node> */): global_symbols
	{
		$publication = new self();
		foreach ($files as $file) {
			foreach ($file->functions() as $function) {
				$publication->discovered($function);
			}
		}
		return $publication->publish($files);
	}
}
