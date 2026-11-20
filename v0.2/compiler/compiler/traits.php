<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Resolve reachable uses after source collection joins; replacements stay private until symbol publication succeeds. */
final class Trait_Composition
{
	/** @param ast_file_node[] $files @return ast_file_node[] Only consumer roots whose bindings changed. */
	public static function replacements(array $files /** array<string,ast_file_node> */): array /** array<string,ast_file_node> */
	{
		$origins = [];
		$consumers = [];
		foreach ($files as $key => $file) {
			$context = $file->trait_context;
			if ($context === null) continue;
			foreach ($context->origins as $symbol => $trait) {
				if (isset($origins[$symbol])) throw new diagnostic($trait->source(), $trait->byte_start(), 'Duplicate trait: ' . $trait->name());
				$origins[$symbol] = $trait;
			}
			if ($context->uses !== []) $consumers[$key] = $file;
		}
		if ($origins !== []) {
			foreach ($files as $file) {
				foreach (array_intersect_key($file->declarations, $origins) as $type) {
					throw new diagnostic($type->source(), $type->byte_start(), 'Type name also declares a trait: ' . $type->name());
				}
			}
		}
		$result = [];
		$expansions = [];
		foreach ($consumers as $file_key => $file) {
			$bindings = [];
			foreach ($file->trait_context->uses as $use) {
				$key = $file->type_key($use->name);
				if (!isset($expansions[$key])) {
					$order = [];
					$active = [];
					self::visit($key, $use, $origins, $order, $active);
					$expansions[$key] = $order;
				}
				$bindings[$use->name] = $expansions[$key];
			}
			if ($bindings !== $file->trait_context->bindings) $result[$file_key] = Parser::composed($file, $bindings);
		}
		return $result;
	}

	/**
	 * Visit owners before their children. An active ancestor is a cycle; a completed origin is a diamond.
	 * @param ast_trait_declaration[] $origins @param ast_trait_declaration[] $order @param array<string,bool> $active
	 */
	private static function visit(string $key, ast_type_reference $use, array $origins /** array<string,ast_trait_declaration> */,
		array &$order /** array<string,ast_trait_declaration> */, array &$active /** array<string,bool> */): void
	{
		if (isset($active[$key])) throw new diagnostic($use->source(), $use->byte_start(), 'Cyclic trait use: ' . $use->name);
		if (isset($order[$key])) return;
		$origin = $origins[$key] ?? throw new diagnostic($use->source(), $use->byte_start(), 'Unknown trait: ' . $use->name);
		$order[$key] = $origin;
		$active[$key] = true;
		$direct = [];
		foreach ($origin->uses as $child) {
			$target = $origin->root()->type_key($child->name);
			if (isset($direct[$target])) throw new diagnostic($child->source(), $child->byte_start(), 'Duplicate trait use: ' . $child->name);
			$direct[$target] = true;
			self::visit($target, $child, $origins, $order, $active);
		}
		unset($active[$key]);
	}
}
