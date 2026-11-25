<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Bind a joined batch without inspecting worker bodies. */
final class Mt_Provisioning
{
	/** Resolve worker targets and range operands against settled contracts. */
	public static function batch(ast_call_expression $batch, scope $symbols, Dependency_Watches $dependencies, ast_function_body $caller, \Closure $prepare, \Closure $with_index): void
	{
		if ($batch->arguments === []) self::reject($batch, 'Expected at least one segment worker');
		if ($batch instanceof ast_parallel_expression && $batch->receiver !== null) {
			$prepare($batch->receiver);
			if ($batch->receiver->type_def() !== type_int64::instance()) self::reject($batch->receiver, 'Group workers requires int');
		}
		foreach ($batch->arguments as $launch) {
			if ($launch instanceof ast_inline_job) {
				$inputs = [];
				if (!in_array('write', $launch->binding_kinds, true) && !in_array('read', $launch->binding_kinds, true))
					self::reject($launch, 'Inline job requires at least one segment binding');
				$bind = function () use ($launch, $prepare, &$inputs): void {
					foreach ($launch->arguments as $slot => $input) {
						if ($launch->binding_kinds[$slot] === 'each') continue;
						if ($launch->binding_kinds[$slot] === 'value') {
							$prepare($input);
							if ($launch->suspending_operands) self::reject($input, 'Await a scalar before binding it to an inline job');
							$type = $input->type_def();
							if (!Mt_Rules::scalar($type))
								self::reject($input, 'Inline job value binding requires a numeric or boolean scalar');
							$inputs[] = $type;
						} else $inputs[] = self::grant($input, $prepare, read_only: $launch->binding_kinds[$slot] === 'read');
					}
				};
				if ($launch->iteration !== null) {
					$settings = $launch->arguments[0];
					$bound = $settings instanceof ast_job_repetition ? $settings->arguments[0] : $settings;
					$prepare($bound);
					if ($bound->type_def() !== type_int64::instance()) self::reject($bound, 'each bound requires int');
					if ($settings instanceof ast_job_repetition) {
						$workers = $settings->arguments[1];
						$prepare($workers);
						if ($workers->type_def() !== type_int64::instance()) self::reject($workers, 'Job workers requires int');
						$settings->settle_mt('each', type_int64::instance());
					}
					$inputs[] = type_int64::instance();
					$launch->iteration->settle_type(type_int64::instance());
					$with_index($launch->iteration, $bind);
				} else $bind();
				$prepare($launch->receiver, new callback_type_definition(type_int64::instance(), $inputs));
				$dependencies->mt->workers[] = $launch->receiver;
				$launch->settle_mt('inline_job', type_int64::instance());
				continue;
			}
			if ($launch::class !== ast_call_expression::class || $launch->qualifier === call_qualifier::local || $launch->member_separator !== null || $launch->receiver !== null)
				self::reject($launch, 'Expected a direct segment worker');
			$symbol = $symbols->entry($launch->symbol_key());
			$worker = $symbol->declaration();
			if (!($worker instanceof ast_function_declaration) || $worker->is_async || $worker->returns_reference
				|| $worker->return_type_def() !== type_int64::instance() || $launch->arguments === [] || count($worker->parameters) !== count($launch->arguments))
				self::reject($launch, 'Segment worker requires one or more matching segment arguments and an int result');
			$conversions = [];
			foreach ($launch->arguments as $i => $range) {
				self::grant($range, $prepare, $worker->parameters[$i]->type_def());
				$conversions[] = value_conversion::identity;
			}
			$dependencies->watch_call($symbol, $caller);
			$dependencies->mt->workers[] = $worker;
			$launch->settle_function($symbol, $conversions);
		}
		if ($batch->suspending_operands) self::reject($batch, 'Await batch inputs before the parallel block');
		$batch->settle_mt('mt_join', type_registry::specialize('vector', type_int64::instance()));
	}

	/** Infer inline parameters from the admitted element or match a declared worker parameter. */
	private static function grant(ast_expression $range, \Closure $prepare, ?type_definition $expected = null, bool $read_only = false): segment_type
	{
		if ($range::class !== ast_call_expression::class || $range->name !== 'segment'
			|| $range->qualifier === call_qualifier::local || $range->member_separator !== null || $range->receiver !== null || !in_array(count($range->arguments), [2, 3], true))
			self::reject($range, 'Expected segment(collection, offset, length) or segment(collection, range)');
		$owner = $range->arguments[0];
		$prepare($owner, writable_segment: !($expected instanceof segment_type ? $expected->read_only : $read_only));
		$container = $owner->type_def();
		if ($owner::class !== ast_name_expression::class || $owner->local_binding() === null || !($container instanceof vector_type_definition || $container instanceof hash_type_definition))
			self::reject($owner, 'Segment requires a matching local vector or hash');
		if ($owner->local_binding() instanceof ast_writable_element) self::reject($range, 'Writable foreach element cannot supply segment storage');
		$key = $container instanceof hash_type_definition ? $container->key_type : null;
		if ($key !== null && $key !== type_int64::instance() && $key !== type_string::instance())
			self::reject($range, 'Hash segment keys must be int or string');
		$element = $container instanceof hash_type_definition ? $container->value_type : $container->element_type;
		if ($expected === null) {
			if (!Mt_Rules::isolated($element)) self::reject($range, 'Segment requires isolated inline elements');
			$expected = new segment_type($element, $read_only, $key);
		} elseif (!($expected instanceof segment_type) || $expected->element !== $element || $expected->key !== $key)
			self::reject($range, 'Segment requires a matching local vector or hash');
		if (!$expected->read_only) Readonly_Access::write($owner);
		if (count($range->arguments) === 2) {
			$coordinates = $range->arguments[1];
			$prepare($coordinates);
			if (!($coordinates->type_def() instanceof type_segment_range)) self::reject($coordinates, 'Expected a segment_range value');
			if ($range->suspending_operands) self::reject($coordinates, 'Await the range before binding it to a segment');
		} else foreach (array_slice($range->arguments, 1) as $bound) {
			$prepare($bound);
			if ($bound->type_def() !== type_int64::instance() || !($bound instanceof ast_integer_expression
				|| ($bound::class === ast_name_expression::class && $bound->local_binding() !== null)))
				self::reject($bound, 'Segment bounds require int locals or literals');
		}
		$range->settle_mt('segment', $expected);
		return $expected;
	}

	private static function reject(ast_node $site, string $message): never
	{
		throw new diagnostic($site->source(), $site->byte_start(), $message);
	}
}

/** Fresh preparation owns this list until the safety coordinator consumes it. */
final class mt_worker_roots
{
	/** @var (ast_function_declaration|ast_lambda_expression)[] Original declarations reached from launch sites. */
	public array $workers /** list<ast_function_declaration|ast_lambda_expression> */ = [];
}
