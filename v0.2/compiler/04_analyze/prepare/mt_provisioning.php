<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Bind a joined batch without inspecting worker bodies. Each worker receives two exclusive vectors' ranges. */
final class Mt_Provisioning
{
	/** Resolve worker targets and range operands against settled contracts. */
	public static function batch(ast_call_expression $batch, scope $symbols, Dependency_Watches $dependencies, ast_function_body $caller, \Closure $prepare): void
	{
		if ($batch->arguments === []) self::reject($batch, 'Expected at least one segment worker');
		foreach ($batch->arguments as $launch) {
			if ($launch::class !== ast_call_expression::class || $launch->qualifier === call_qualifier::local || $launch->member_separator !== null || $launch->receiver !== null)
				self::reject($launch, 'Expected a direct segment worker');
			$symbol = $symbols->entry($launch->symbol_key());
			$worker = $symbol->declaration();
			if (!($worker instanceof ast_function_declaration) || $worker->is_async || $worker->returns_reference
				|| $worker->return_type_def() !== type_int64::instance() || count($launch->arguments) !== 2 || count($worker->parameters) !== 2)
				self::reject($launch, 'Segment worker requires two exclusive segments and an int result');
			foreach ($launch->arguments as $i => $range) {
				$type = $worker->parameters[$i]->type_def();
				if (!($type instanceof segment_type) || $range::class !== ast_call_expression::class || $range->name !== 'segment'
					|| $range->qualifier === call_qualifier::local || $range->member_separator !== null || $range->receiver !== null || count($range->arguments) !== 3)
					self::reject($range, 'Expected segment(vector, offset, length)');
				[$owner, $offset, $length] = $range->arguments;
				$prepare($owner);
				if ($owner::class !== ast_name_expression::class || $owner->local_binding() === null
					|| !($owner->type_def() instanceof vector_type_definition) || $owner->type_def()->element_type !== $type->element)
					self::reject($owner, 'Exclusive segment requires a matching writable local vector');
				Readonly_Access::write($owner);
				foreach ([$offset, $length] as $bound) {
					$prepare($bound);
					if ($bound->type_def() !== type_int64::instance() || !($bound instanceof ast_integer_expression
						|| ($bound::class === ast_name_expression::class && $bound->local_binding() !== null)))
						self::reject($bound, 'Segment bounds require int locals or literals');
				}
				$range->settle_mt('segment', $type);
			}
			$dependencies->watch_call($symbol, $caller);
			$dependencies->mt->workers[] = $worker;
			$launch->settle_function($symbol, [value_conversion::identity, value_conversion::identity]);
		}
		$batch->settle_mt('mt_join', type_registry::specialize('vector', type_int64::instance()));
	}

	private static function reject(ast_node $site, string $message): never
	{
		throw new diagnostic($site->source(), $site->byte_start(), $message);
	}
}

/** Fresh preparation owns this list until the safety coordinator consumes it. */
final class mt_worker_roots
{
	/** @var ast_function_declaration[] Original declarations reached from launch sites. */
	public array $workers /** list<ast_function_declaration> */ = [];
}
