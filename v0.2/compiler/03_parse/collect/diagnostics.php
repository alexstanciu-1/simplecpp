<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Reports every additional declaration without discarding syntax or selecting an ambiguous name.
 * Example: void ping() {} void ping() {} yields one error referring to the first ping.
 * File chains remain source-local; global candidates use their existing separate index.
 */
final class Declaration_Diagnostics
{
	/** Completed bodies are available now; diagnostics never prevent parsing later declarations. */
	public static function file(array $heads /** array<string,ast_function_declaration> */): array /** list<diagnostic> */
	{
		$errors = [];
		foreach ($heads as $head) {
			for ($node = $head; $node !== null; $node = $node->duplicate_element()) {
				if ($node !== $head) { $errors[] = self::duplicate($node, $head, 'function'); }
				array_push($errors, ...self::locals($node->body()));
			}
		}
		return self::ordered($errors);
	}

	/** Reconcile all sources against one deterministic original, without relinking their local chains. */
	public static function functions(global_symbols $symbols): array /** list<diagnostic> */
	{
		$errors = [];
		foreach ($symbols->declarations as $candidates) {
			if (count($candidates) < 2) { continue; }
			usort($candidates, static function (ast_function_declaration $a, ast_function_declaration $b): int /** int32 */ {
				return strcmp($a->source()->path, $b->source()->path) ?: ($a->start <=> $b->start);
			});
			foreach ($candidates as $index => $candidate) {
				if ($index !== 0) { $errors[] = self::duplicate($candidate, $candidates[0], 'function'); }
			}
		}
		return $errors;
	}

	/** Locals are scoped to one body; repeated spellings in other bodies are independent. */
	public static function locals(ast_function_body $body): array /** list<diagnostic> */
	{
		$first = [];
		$errors = [];
		foreach ($body->locals as $local) {
			$key = $local->declaration_key();
			if (isset($first[$key])) { $errors[] = self::duplicate($local, $first[$key], 'local'); }
			else { $first[$key] = $local; }
		}
		return $errors;
	}

	/** Gather once per declaration, including locals in every duplicate function body. */
	public static function program(array $files /** array<string,ast_file_node> */, global_symbols $symbols): array /** list<diagnostic> */
	{
		$errors = self::functions($symbols);
		foreach ($files as $file) {
			foreach ($file->functions() as $function) { array_push($errors, ...self::locals($function->body())); }
			$main = $file->main();
			if ($main !== null) { array_push($errors, ...self::locals($main->body())); }
		}
		return self::ordered($errors);
	}

	private static function duplicate(ast_local_declaration|ast_function_declaration $node,
		ast_local_declaration|ast_function_declaration $first, string $kind): diagnostic
	{
		$source = $first->source();
		$location = $source->location($first->root()->byte_at($first->name_position()));
		return new diagnostic($node->source(), $node->root()->byte_at($node->name_position()),
			'Duplicate ' . $kind . ': ' . $node->name . '; first declaration at '
			. $source->path . ':' . $location['line'] . ':' . $location['column']);
	}

	public static function ordered(array $errors /** list<diagnostic> */): array /** list<diagnostic> */
	{
		usort($errors, static fn(diagnostic $a, diagnostic $b): int /** int32 */ =>
			strcmp($a->source->path, $b->source->path) ?: ($a->offset <=> $b->offset));
		return $errors;
	}
}
