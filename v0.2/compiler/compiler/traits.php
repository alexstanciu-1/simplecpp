<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Resolve direct uses after source collection joins; replacements stay private until symbol publication succeeds. */
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
		foreach ($consumers as $key => $file) {
			$bindings = [];
			foreach ($file->trait_context->uses as $use) {
				$bindings[$use->name] = $origins[$file->type_key($use->name)]
					?? throw new diagnostic($use->source(), $use->byte_start(), 'Unknown trait: ' . $use->name);
			}
			if ($bindings !== $files[$key]->trait_context->bindings) $result[$key] = Parser::composed($file, $bindings);
		}
		return $result;
	}
}
