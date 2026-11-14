<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Reports every additional declaration without discarding syntax or selecting an ambiguous name.
 * Example: void ping() {} void ping() {} yields one error referring to the first ping.
 * File diagnostics use syntax inventory; compilation diagnostics follow the declaring scope chain.
 */
final class Declaration_Diagnostics
{
	/**
	 * Completed bodies are available now; diagnostics never prevent parsing later declarations.
	 *
	 * @param (ast_function_declaration|ast_global_declaration|ast_constant_declaration|ast_type_declaration)[] $nodes
	 * @return diagnostic[]
	 */
	public static function file(array $nodes /** list<ast_function_declaration|ast_global_declaration|ast_constant_declaration|ast_type_declaration> */): array /** list<diagnostic> */
	{
		$errors = [];
		/** @var (ast_function_declaration|ast_global_declaration|ast_constant_declaration|ast_type_declaration)[] $first */
		$first = [];
		foreach ($nodes as $node)
		{
			$key = $node->symbol_key();
			if (isset($first[$key])) {
				$errors[] = self::duplicate($node, $first[$key], self::kind($node));
			}
			else {
				$first[$key] = $node;
			}
			if ($node instanceof ast_function_declaration) {
				array_push($errors, ...self::locals($node->body()));
			}
		}
		return self::ordered($errors);
	}

	/**
	 * Publication orders public declarations by source path and position for deterministic originals.
	 *
	 * @return diagnostic[]
	 */
	public static function published(scope $symbols): array /** list<diagnostic> */
	{
		$errors = [];
		foreach ($symbols->declarations as $head) {
			/** @var ast_function_declaration|ast_global_declaration|ast_constant_declaration|ast_type_declaration $head */
			for ($node = $head->duplicate_element(); $node !== null; $node = $node->duplicate_element()) {
				/** @var ast_function_declaration|ast_global_declaration|ast_constant_declaration|ast_type_declaration $node */
				$errors[] = self::duplicate($node, $head, self::kind($node));
			}
		}
		return $errors;
	}

	/**
	 * Locals are scoped to one body; repeated spellings in other bodies are independent.
	 *
	 * @return diagnostic[]
	 */
	public static function locals(ast_statement_block $body): array /** list<diagnostic> */
	{
		$errors = [];
		foreach ($body->scope->declarations as $head) {
			/** @var ast_local_declaration $head */
			for ($local = $head->duplicate_element(); $local !== null; $local = $local->duplicate_element()) {
				/** @var ast_local_declaration $local */
				$errors[] = self::duplicate($local, $head, 'local');
			}
		}
		foreach ($body->statements as $statement) {
			if ($statement instanceof ast_loop_statement) {
				array_push($errors, ...self::locals($statement->body));
			}
			elseif ($statement instanceof ast_if_statement) {
				foreach ($statement->arms as $arm) {
					array_push($errors, ...self::locals($arm->body));
				}
			}
		}
		return self::ordered($errors);
	}

	/**
	 * Gather once per declaration, including locals in every duplicate function body.
	 *
	 * @param ast_file_node[] $files
	 * @return diagnostic[]
	 */
	public static function program(array $files /** array<string,ast_file_node> */, scope $symbols): array /** list<diagnostic> */
	{
		$errors = self::published($symbols);
		foreach ($files as $file)
		{
			foreach ($file->functions() as $function) {
				array_push($errors, ...self::locals($function->body()));
			}
			$main = $file->main();
			if ($main !== null) {
				array_push($errors, ...self::locals($main->body()));
			}
		}
		return self::ordered($errors);
	}

	/** Diagnostic label for a published declaration. */
	private static function kind(ast_declaration_interface $node): string
	{
		return match (true) {
			$node instanceof ast_struct_declaration => 'struct',
			$node instanceof ast_enum_declaration => 'enum',
			$node instanceof ast_function_declaration && $node->parent instanceof ast_struct_declaration => 'method',
			$node instanceof ast_global_declaration && $node->parent instanceof ast_struct_declaration => 'static property',
			$node instanceof ast_global_declaration => 'global',
			$node instanceof ast_constant_declaration => 'constant',
			default => 'function',
		};
	}

	private static function duplicate(ast_local_declaration|ast_parameter_declaration|ast_function_declaration|ast_global_declaration|ast_constant_declaration|ast_type_declaration $node,
		ast_local_declaration|ast_parameter_declaration|ast_function_declaration|ast_global_declaration|ast_constant_declaration|ast_type_declaration $first, string $kind): diagnostic
	{
		$source = $first->source();
		$location = $source->location($first->root()->byte_at($first->name_position()));
		return new diagnostic($node->source(), $node->root()->byte_at($node->name_position()),
			'Duplicate ' . $kind . ': ' . $node->name() . '; first declaration at '
			. $source->path . ':' . $location['line'] . ':' . $location['column']);
	}

	/**
	 * @param diagnostic[] $errors
	 * @return diagnostic[]
	 */
	public static function ordered(array $errors /** list<diagnostic> */): array /** list<diagnostic> */
	{
		usort($errors, static fn(diagnostic $a, diagnostic $b): int /** int32 */ =>
			strcmp($a->source->path, $b->source->path) ?: ($a->offset <=> $b->offset));
		return $errors;
	}
}
