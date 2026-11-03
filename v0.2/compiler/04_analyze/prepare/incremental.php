<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Rebuild dirty body caches after frontend publication; does not create an emission handle or lower code. */
final class Incremental_Preparation
{
	public static function prepare(compilation_session $session, entry_target $target): array /** array{prepared:uint32,reused:uint32} */
	{
		$symbols = $session->scope ?? throw new \LogicException('Frontend publication is required');
		if (!$session->is_current) { throw new \LogicException('Only the current frontend session can be prepared'); }
		$files = $session->files();
		$errors = Declaration_Diagnostics::program($files, $symbols);
		if ($errors !== []) { throw new diagnostic_group($errors); }
		foreach ($files as $path => $file) { Program_Preparation::admit($file, $path === $session->entry_key()); }
		Function_Preparation::declarations($symbols);
		$report = ['prepared' => 0, 'reused' => 0];
		$errors = [];
		foreach ($files as $path => $file) {
			$functions = $file->functions();
			if ($file->main() !== null) {
				$main = $file->main();
				if (!$main->return_type_ready()) { $main->settle_return_type(type_definition::int64); }
				$functions[] = $main;
			} elseif ($path === $session->entry_key()) {
				$errors[] = new diagnostic($file->source(), strlen($file->source()->content), 'Entry slice requires an explicit value return');
			}
			foreach ($functions as $function) {
				$body = $function->body();
				$exit = $function->is_implicit_entry ? $target : null;
				if ($body->is_prepared($exit)) { $report['reused']++; continue; }
				try {
					(new Preparation())->function_body($body, $symbols, $exit, $session->function_dependencies);
					$report['prepared']++;
				} catch (diagnostic $error) { $errors[] = $error; }
			}
		}
		if ($errors !== []) { throw new diagnostic_group(Declaration_Diagnostics::ordered($errors)); }
		return $report;
	}
}
