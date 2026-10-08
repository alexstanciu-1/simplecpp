<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function demand_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}
function demand_record(string $name): collected_record
{
	foreach (Model::collected_files() as $source) {
		foreach ($source->defined_elements as $position) {
			$entry = $source->entries[$position];
			if ($entry instanceof collected_record && $entry->name === $name) { return $entry; }
		}
	}
	throw new \LogicException('Missing ' . $name);
}

$directory = sys_get_temp_dir() . '/callable-demands-' . bin2hex(random_bytes(6));
mkdir($directory);
$base_path = $directory . '/base.phs';
$child_path = $directory . '/child.phs';
try {
	file_put_contents($base_path, 'class Root { public static function ancestor(): int { return 2; } } class Base extends Root { public static function start(int $n): int { $first = self::step($n); $second = parent::ancestor(); $third = Base::step(0); return $first + $second + $third; } public static function step(int $n): int { if ($n === 0) { return 1; } return self::step($n - 1); } }');
	file_put_contents($child_path, 'class Child extends Base {} class Other extends Base {}');
	Compiler_Lifecycle::reset();
	$compiler = new Compiler(); $compiler->init([$directory]); $compiler->select_entry('main', 'child.phs'); $compiler->exec_cpp();
	$base = demand_record('Base'); $child = demand_record('Child'); $other = demand_record('Other');
	$start = object_cast($base->syntax()->methods[0]->occurrence(), collected_method::class);
	$step = object_cast($base->syntax()->methods[1]->occurrence(), collected_method::class);
	$ancestor = object_cast(demand_record('Root')->syntax()->methods[0]->occurrence(), collected_method::class);
	$first = Callable_Instances::obtain($start, $child);
	$second = Callable_Instances::obtain($start, $other);
	$worker = new Preparation_Worker(Model::$language_scope);
	$yielded = false;
	try { $worker->prepare(Model::collected_files()); }
	catch (\RuntimeException $signal) {
		$yielded = $worker->is_materialization_yield($signal);
		if (!$yielded) { throw $signal; }
		demand_check(!$worker->is_materialization_yield(new \RuntimeException($signal->getMessage())), 'Diagnostic text mistaken for control signal');
	}
	demand_check($yielded && count($worker->materialization_requests()) === 1, 'Missing concrete materialization request');
	$request = $worker->materialization_requests()[0];
	demand_check($request->origin === $step && $request->called_type === $child, 'Wrong request identity');
	demand_check($step->called_instances->is_empty(), 'Analysis materialized syntax');
	$waiting = $first->working->callable_syntax()->require_body()->work();
	demand_check(!$waiting->failed && $waiting->state === preparation_state::pending && $waiting->version === 0, 'Suspension became failure/completion');
	$compiler->prepare();
	$compiler->cpp();
	$before = CPP_Output::encode(Model::$cpp_files);
	demand_check($waiting->state === preparation_state::ready && !$waiting->failed, 'Coordinator did not resume suspended body');
	$child_step = Callable_Inventory::find($step, $child);
	$other_step = Callable_Inventory::find($step, $other);
	$base_step = Callable_Inventory::find($step, $base);
	demand_check($child_step !== null && $other_step !== null && $base_step !== null && count($step->called_instances) === 3, 'Forwarded/explicit contexts not interned independently');
	demand_check(Callable_Inventory::find($ancestor, $child) !== null && Callable_Inventory::find($ancestor, $other) !== null, 'Parent call lost forwarded called type');
	$recursive = object_cast($child_step->working->callable_syntax()->require_body()->statements[1], return_node::class);
	$call = object_cast($recursive->expression, call_node::class);
	demand_check(weakref_get($call->require_call_preparation()->target) === $child_step->working, 'Recursive call did not reuse its own implementation identity');
	$versions = [];
	foreach ([$first, $second, $child_step, $other_step, $base_step] as $instance) {
		$work = $instance->working->callable_syntax()->require_body()->work();
		demand_check($work->state === preparation_state::ready, 'Private implementation not settled');
		$versions[] = $work->version;
	}
	$compiler->update_cpp([]);
	foreach ([$first, $second, $child_step, $other_step, $base_step] as $index => $instance) {
		demand_check($instance->working->callable_syntax()->require_body()->work()->version === $versions[$index], 'No-op demand rebuilt a body');
	}
	demand_check(CPP_Output::encode(Model::$cpp_files) === $before, 'No-op changed private implementation output');
	Compiler_Lifecycle::reset_preparation();
	(new File_Preparation($base->collection, Model::$language_scope))->prepare();
	demand_check($waiting->state === preparation_state::ready && $child_step->working->callable_syntax()->require_body()->work()->state === preparation_state::ready, 'Standalone adapter did not share preparation');
}
finally {
	Compiler_Lifecycle::reset();
	foreach ([$base_path, $child_path] as $path) { if (is_file($path)) { unlink($path); } }
	rmdir($directory);
}
echo "Callable demands: stage handoff, identity signal, forwarding, recursion and no-op passed\n";
