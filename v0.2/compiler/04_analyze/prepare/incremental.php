<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Rebuild dirty body caches after frontend publication; settles file completion for the existing emitter without lowering code. */
final class Incremental_Preparation
{
	/**
	 * Validate the published generation, then rebuild dirty body caches and report reuse.
	 *
	 * @return mixed[]
	 */
	public static function prepare(compilation_session $session, entry_target $target): array /** array{prepared:uint32,reused:uint32} */
	{
		$symbols = $session->scope ?? throw new \LogicException('Frontend publication is required');
		if (!$session->is_current) {
			throw new \LogicException('Only the current frontend session can be prepared');
		}
		$files = $session->files();
		$errors = Declaration_Diagnostics::program($files, $symbols);
		if ($errors !== []) {
			throw new diagnostic_group($errors);
		}
		foreach ($files as $path => $file) {
			Program_Preparation::admit($file, $path === $session->entry_key());
		}
		Program_Preparation::layouts($files, $symbols, $session->dependency_watches);
		Function_Preparation::declarations($symbols, $session->dependency_watches);
		Literal_Declaration_Preparation::declarations($symbols);
		Import_Preparation::files($files, $symbols);
		$work = $session->preparation_work;
		$entry = $files[$session->entry_key()];
		$main = $entry->main();
		$report = ['prepared' => 0, 'reused' => 0];
		$errors = [];
		if ($main === null) {
			$errors[] = new diagnostic($entry->source(), strlen($entry->source()->content),
				'Entry slice requires an explicit value return');
		}
		else {
			if (!$main->return_type_ready()) {
				$main->settle_return_type(type_registry::integer());
			}
			// The entry target is an external cache input, not a watched source symbol.
			if (!$main->body()->is_prepared($target)) {
				$work->enqueue($session->entry_key(), $main->body());
			}
		}
		foreach ($work->pending() as $bodies)
		{
			foreach ($bodies as $body)
			{
				/** @var ast_function_body $body */
				/** @var ast_function_declaration $function */
				$function = $body->parent;
				$exit = $function->is_implicit_entry ? $target : null;
				try {
					(new Preparation())->function_body($body, $symbols, $exit, $session->dependency_watches);
					$report['prepared']++;
				}
				catch (diagnostic $error) {
					$errors[] = $error;
				}
			}
		}
		$report['reused'] = $work->body_count() - $report['prepared'];
		if ($errors !== []) {
			throw new diagnostic_group(Declaration_Diagnostics::ordered($errors));
		}
		foreach ($work->emission_sources() as $key => $_) {
			if (isset($files[$key])) {
				$files[$key]->settle_preparation();
			}
		}
		return $report;
	}
}
