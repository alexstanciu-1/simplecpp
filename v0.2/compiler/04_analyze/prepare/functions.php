<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Settles function return types before body analysis and resets source-owned facts for sequential reanalysis.
 */
final class Function_Preparation
{
	/** Reset source-owned facts before a new sequential run; no foreign bodies are traversed. */
	public static function reset(ast_function_declaration $function): void
	{
		$function->reset_preparation();
		foreach ($function->body()->statements as $statement) {
			if (($statement instanceof ast_local_declaration) || ($statement instanceof ast_call_statement)) {
				$statement->reset_preparation();
			}
		}
	}

	/** Validate all declarations before body work; callable recursion has no signature dependency cycle. */
	public static function declarations(global_symbols $symbols): array /** array<string,ast_function_declaration>; settled public declarations */
	{
		foreach ($symbols->functions as $candidates) {
			foreach ($candidates as $candidate) {
				self::reset($candidate);
			}
		}
		$callables = [];
		foreach ($symbols->functions as $name => $candidates)
		{
			usort($candidates, static function (ast_function_declaration $a, ast_function_declaration $b): int /** int32 */
			{
				return strcmp($a->source()->path, $b->source()->path)
					?: ($a->start <=> $b->start);
			});
			if (count($candidates) !== 1) {
				$span = $candidates[1];
				throw new diagnostic($span->source(), $span->root()->byte_at($span->name_position()), 'Duplicate function: ' . $name);
			}
			$declaration = $candidates[0];
			$type = $declaration->return_type_name;
			if ($type !== 'void') {
				throw new diagnostic($declaration->source(), $declaration->root()->byte_at($declaration->return_type_position()), 'Only explicit void functions are supported');
			}
			$declaration->settle_return_type(type_definition::void);
			$callables[$name] = $declaration;
		}
		return $callables;
	}

	/** Prepare only this source's bodies, reading foreign signatures but never foreign executable syntax. */
	public static function bodies(ast_file_node $file, array $callables /** array<string,ast_function_declaration> */): array /** list<prepared_function> */
	{
		$functions = [];
		foreach ($file->functions() as $declaration) {
			$node = $declaration;
			$node->return_type_def();
			$body = (new Preparation())->function_body($node->body(), $callables);
			$node->body()->settle_preparation($body);
			$functions[] = new prepared_function($node, $body);
		}
		return $functions;
	}
}
