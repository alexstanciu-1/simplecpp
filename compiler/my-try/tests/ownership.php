<?php

/* Ownership admission, transfers, observations, lifetime and incremental recovery. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function ownership_source_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

/** Always exercise the real preparation publication boundary before emission. */
function ownership_source(string $source): string
{
	Compiler_Lifecycle::reset();
	$input_module = new module('/ownership', '/ownership', '/ownership');
	$input = new file();
	$input->path = 'main.phs';
	$input->content = $source;
	Source_Registry::add($input_module, $input);
	Model::$modules->add($input_module->name, $input_module);
	(new Compiler())->exec_cpp();
	return CPP_Probe::text(Model::$cpp_files);
}

/** Inspect the retained construction contract independently of C++ execution. */
final class Ownership_Construction_Probe implements node_maintenance_worker_i
{
	public int $count = 0;
	public int $moves = 0;
	public int $observations = 0;

	public function enter(ast_node $node): void
	{
		if ($node instanceof ownership_observation_node)
		{
			$observation = object_cast($node, ownership_observation_node::class);
			$facts = $observation->require_observation_preparation();
			ownership_source_check(!$facts->addressable() && !$facts->is_fresh_value(), 'Observation is not fresh allocation or a place');
			ownership_source_check($facts->operation === $observation->operation, 'Observation lost its operation');
			$input_type = $observation->operand->require_preparation()->type;
			$input_role = Ownership_Conversions::role($input_type);
			$output_role = Ownership_Conversions::role($facts->type);
			$input_payload = Ownership_Conversions::payload($input_type);
			$output_payload = Ownership_Conversions::payload($facts->type);
			ownership_source_check($input_payload->matches($output_payload), 'Observation changed payload identity');
			$expected_family = $facts->operation === ownership_observation_kind::derive_weak ? ownership_kind::weak : ownership_kind::shared;
			ownership_source_check($output_role->ownership_family() === $expected_family, 'Observation selected wrong handle family');
			$children = iterator_to_array($observation->children());
			ownership_source_check(count($children) === 1 && $children[0] === $observation->operand, 'Observation lost its inspection edge');
			$this->observations++;
			return;
		}
		if ($node instanceof call_node)
		{
			$call = object_cast($node, call_node::class);
			$call_facts = $call->require_call_preparation();
			$role = Ownership_Conversions::role($call_facts->type);
			if ($role !== null) {
				if ($role->ownership_family() === ownership_kind::unique) {
					ownership_source_check($call_facts->is_owned_temporary() === ($call_facts->signature->reference_return === null), 'Unique call result lost value/reference distinction');
				}
			}
			foreach ($call_facts->arguments as $index => $argument_fact) {
				if ($argument_fact->boundary === value_boundary_operation::consume_value) {
					ownership_source_check($call->arguments[$index]->require_preparation()->is_owned_temporary(), 'Consume decision selected an ordinary value');
				}
			}
		}
		if ($node instanceof move_expression_node) {
			$move = object_cast($node, move_expression_node::class);
			$facts = $move->require_move_preparation();
			ownership_source_check($facts->is_owned_temporary() && !$facts->is_fresh_value() && !$facts->addressable(), 'Move must be an owned transfer, not fresh construction or a place');
			ownership_source_check($facts->source_place === $move->operand->require_preparation()->place, 'Move lost source-place identity');
			$children = iterator_to_array($move->children());
			ownership_source_check(count($children) === 1 && $children[0] === $move->operand, 'Move lost inspection edge');
			$this->moves++;
			return;
		}
		if (!($node instanceof object_construction_node)) {
			return;
		}
		$construction = object_cast($node, object_construction_node::class);
		$facts = $construction->require_construction_preparation();
		ownership_source_check($facts->is_fresh_value() && !$facts->addressable(), 'Construction must be a fresh non-addressable value');
		$role = Ownership_Conversions::role($facts->type);
		ownership_source_check(Type_Queries::record_storage($facts->type) === $facts->storage, 'Construction ownership disagrees with result');
		ownership_source_check(Ownership_Conversions::payload($facts->type)->matches($facts->constructed_type), 'Construction target disagrees with result');
		$children = [];
		foreach ($construction->children() as $child) {
			$children[] = $child;
		}
		ownership_source_check(count($children) === 1 && $children[0] === $construction->target_type, 'Construction inspection lost its type edge');
		$this->count++;
	}

	public function edge(ast_node $parent, ast_node $child): void
	{
		$child->maintain($this);
	}

	public function token_index(int $index): int
	{
		return $index;
	}
}

$cases = [
	'weak_lifetime' => 'class P { unique<int> $child; } $owner = new P(); $observer = weak($owner); $copy = $observer; $held = lock($copy); if (!$owner) { return 1; } if (!$held) { return 2; } $owner = null; if (!isset(lock($observer))) { return 3; } $held = null; if (lock($observer)) { return 4; } if (lock($copy) !== null) { return 5; } return 0;',
	'weak_empty' => '$owner shared<int>; $observer weak<int> = weak($owner); $held shared<int> = lock($observer); if ($held) { return 1; } $copy = $observer; $observer = null; if (isset(lock($copy))) { return 2; } $nested shared<vector<int>>; $nested_observer = weak($nested); if (lock($nested_observer)) { return 3; } return 0;',
	'weak_temporary' => 'class P { int $x; } function make(): P { return new P(); } $observer = weak(make()); if (lock($observer)) { return 1; } $held = lock(weak(make())); if (!$held) { return 2; } $direct = lock(weak(new P())); if (!$direct) { return 3; } return 0;',
	'weak_once' => 'class P { int $x; } function source(P $p, int &$calls): P { $calls += 1; return $p; } function observe(weak<P> $w, int &$calls): weak<P> { $calls += 1; return $w; } $owner = new P(); $calls = 0; $observer = weak(source($owner, $calls)); if ($calls !== 1) { return 1; } $held = lock(observe($observer, $calls)); if ($calls !== 2) { return 2; } $owner = null; if (!$held) { return 3; } $held = null; if (lock(observe($observer, $calls))) { return 4; } if ($calls !== 3) { return 5; } return 0;',
	'weak_boundaries' => 'class P { int $x; } function derive(P &$p): weak<P> { return weak($p); } function acquire(weak<P> $w): P { return lock($w); } function present(P $p): bool { return isset($p); } $owner = new P(); $alias =& $owner; $observer = derive($alias); $weak_alias =& $observer; if (!present(lock($weak_alias))) { return 1; } $held = acquire(weak($owner)); $owner = null; if (!$held) { return 2; } unset($held); if (present(acquire($observer))) { return 3; } return 0;',
	'weak_condition_release' => 'class P { int $x; } $owner = new P(); $observer = weak($owner); if (lock($observer)) { $owner = null; if (lock($observer)) { return 1; } } else { return 2; } return 0;',
	'consume_return' => 'class P { int $x; } function produce(): unique<P> { $p unique<P> = new P(); return move($p); } function forward(): unique<P> { return produce(); } function consume(unique<P> $p): bool { return isset($p); } $a = forward(); if (!$a) { return 1; } if (!consume(move($a))) { return 2; } if (isset($a)) { return 3; } if (!consume(produce())) { return 4; } $a = produce(); if (!$a) { return 5; } return 0;',
	'consume_forward_parameter' => 'class P { int $x; } function relay(unique<P> $p): unique<P> { return move($p); } function extract(unique<P> &$p): unique<P> { return move($p); } $a unique<P> = new P(); $b = relay(move($a)); if (isset($a)) { return 1; } $c = extract($b); if (isset($b)) { return 2; } if (!$c) { return 3; } return 0;',
	'consume_order' => 'class P { int $x; } function inspect(unique<P> $first, bool $source_present, unique<P> $second): int { if (!isset($first)) { return 1; } if ($source_present) { return 2; } if (isset($second)) { return 3; } return 0; } $a unique<P> = new P(); $result = inspect(move($a), isset($a), move($a)); return $result;',
	'consume_alias' => 'class P { int $x; } function identity(unique<P> $p): unique<P> { return move($p); } $a unique<P> = new P(); $alias =& $a; $alias = identity(move($a)); if (!$a) { return 1; } $b = identity(move($alias)); if (isset($a)) { return 2; } if (!$b) { return 3; } return 0;',
	'move_local' => 'class P { int $x; } $a unique<P> = new P(); $alias =& $a; $b = move($a); if (isset($a)) { return 1; } if (isset($alias)) { return 2; } if (!$b) { return 3; } $a = move($b); if (!$alias) { return 4; } if ($b !== null) { return 5; } $c unique<P> = move($alias); if ($a !== null) { return 6; } if (!$c) { return 7; } return 0;',
	'move_replace' => 'class P { int $x; } $a unique<P> = new P(); $b unique<P> = new P(); $a = move($b); if (!$a) { return 1; } if (isset($b)) { return 2; } $a = move($a); if (!$a) { return 3; } $alias =& $a; $alias = move($a); if (!$a) { return 4; } $a = move($alias); if (!$alias) { return 5; } $a = move($b); if (isset($alias)) { return 6; } $c = move($b); if (isset($c)) { return 7; } return 0;',
	'move_parameters' => 'class P { int $x; } function transfer(unique<P> &$source, unique<P> &$destination): void { $destination = move($source); } $a unique<P> = new P(); $b unique<P>; transfer($a, $b); if (isset($a)) { return 1; } if (!$b) { return 2; } transfer($b, $b); if (!$b) { return 3; } return 0;',
	'allocated_shared' => 'class Point { int $x; } $p = new Point(); $copy = $p; if (!$p) { return 1; } if (!isset($copy)) { return 2; } unset($p); if ($p !== null) { return 3; } if (!isset($copy)) { return 4; } $q Point = new Point(); if (null === $q) { return 5; } return 0;',
	'allocated_unique' => 'class Point { int $x; } function reset(unique<Point> &$p): void { $p = null; } $p unique<Point> = new Point(); $a =& $p; if (!$a) { return 1; } reset($p); if (isset($a)) { return 2; } $q unique<Point> = new Point(); unset($q); if (isset($q)) { return 3; } return 0;',
	'allocated_noncopyable' => 'struct Item { unique<int> $child; } class Box { Item $item; vector<int> $values; ?string $note; dynamic $data; } $s = new Box(); $copy = $s; $u unique<Box> = new Box(); if (!$u) { return 1; } if (!$copy) { return 2; } return 0;',
	'allocated_expression' => 'class Point { int $x; } function make(): Point { return new Point(); } function present(Point $p): bool { return isset($p); } if (!present(new Point())) { return 1; } if (!isset(make())) { return 2; } return 0;',
	'empty_reset' => 'function reset_unique(unique<int> &$u): void { $u = null; } $s shared<int> = null; $u unique<int> = null; $w weak<int> = null; $a =& $u; $a = null; reset_unique($u); $s = null; $w = null; unset($s); unset($u); unset($w); if (isset($u)) { return 1; } if (isset($s)) { return 2; } return 0;',
	'presence' => '$s shared<int>; $u unique<int>; if ($s) { return 1; } elseif ($u) { return 2; } while ($u) { return 3; } for (; $s;) { return 4; } do { if (!$u) { break; } return 5; } while ($s); $n = $u ? 1 : 0; if ($s !== null) { return 6; } if (null !== $u) { return 7; } if (!($s === null)) { return 8; } if (!(null === $u)) { return 9; } return $n;',
	'observe_once' => 'function observe(shared<int> $s, int &$calls): shared<int> { $calls += 1; return $s; } $s shared<int>; $calls = 0; if (observe($s, $calls)) { return 1; } $p = observe($s, $calls) === null; $q = isset(observe($s, $calls)); $r = !isset(observe($s, $calls)); if ($calls !== 4) { return 2; } if (!$p) { return 3; } if ($q) { return 4; } if (!$r) { return 5; } return 0;',
	'handles' => '$s shared<int>; $copy = $s; $w weak<int>; $observer = $w; $u unique<int>; $alias =& $u; return 0;',
	'holder' => 'struct Holder { unique<int> $item; } $h shared<Holder>; $copy = $h; $w weak<Holder>; $observer = $w; $owned unique<Holder>; return 0;',
	'container' => '$v vector<shared<int>>; $h hash<weak<int>>; $s shared<vector<shared<int>>>; return 0;',
	'borrow' => 'function &identity(unique<int> &$u): unique<int> { return $u; } $u unique<int>; $a =& identity($u); return 0;',
];
$output = $argv[1] ?? sys_get_temp_dir() . '/scpp_ownership_' . bin2hex(random_bytes(6));
if (!is_dir($output)) {
	mkdir($output, 0777, true);
}
$executions = [];
foreach ($cases as $name => $source) {
	$path = $output . '/' . $name . '.cpp';
	file_put_contents($path, ownership_source($source));
	$probe = new Ownership_Construction_Probe();
	Model::syntax_files()[0]->root->maintain($probe);
	ownership_source_check($probe->count === substr_count($source, 'new '), 'Construction syntax count differs');
	ownership_source_check($probe->moves === substr_count($source, 'move('), 'Move syntax count differs');
	ownership_source_check($probe->observations === substr_count($source, 'weak(') + substr_count($source, 'lock('), 'Observation syntax count differs');
	$executions[] = ['path' => $path, 'exit_code' => 0];
}

// Native instrumentation consumes unmodified generated transfer code; weak markers expose destruction.
$lifetime_input = 'class P { shared<int> $marker; } function transfer(unique<P> &$source, unique<P> &$destination): void { $destination = move($source); } function relay(unique<P> $p): unique<P> { return move($p); } function call_relay(unique<P> &$p): unique<P> { return relay(move($p)); } function fail(): int { $n ?int = null; return $n; } function sink(unique<P> $p, int $n, int &$entered): void { $entered += 1; } function fail_after(unique<P> &$p, int &$entered): void { sink(move($p), fail(), $entered); } return 0;';
$lifetime_source = ownership_source($lifetime_input);
$lifetime_harness = <<<'CPP'
#undef main
#include "scpp/memory.hpp"
#include <stdexcept>
int main() {
    auto source = scpp::unique<record_P>();
    auto destination = scpp::unique<record_P>();
    source->field_marker = scpp::shared<scpp::int_t<>>();
    destination->field_marker = scpp::shared<scpp::int_t<>>();
    auto source_lifetime = scpp::weak(source->field_marker);
    auto previous_lifetime = scpp::weak(destination->field_marker);
    auto* identity = source.get();
    function_transfer(source, destination);
    if (source.get() != nullptr || destination.get() != identity) return 1;
    if (!static_cast<bool>(previous_lifetime.expired())) return 2;
    if (static_cast<bool>(source_lifetime.expired())) return 3;
    function_transfer(destination, destination);
    if (destination.get() != identity) return 4;
    try {
        scpp::unique_p<record_P> temporary;
        function_transfer(destination, temporary);
        throw std::runtime_error("after transfer");
    } catch (const std::runtime_error&) {}
    if (destination.get() != nullptr) return 5;
    if (!static_cast<bool>(source_lifetime.expired())) return 6;
    source = scpp::unique<record_P>();
    source->field_marker = scpp::shared<scpp::int_t<>>();
    auto call_lifetime = scpp::weak(source->field_marker);
    identity = source.get();
    auto returned = function_callU_relay(source);
    if (source.get() != nullptr || returned.get() != identity) return 7;
    if (static_cast<bool>(call_lifetime.expired())) return 8;
    scpp::int_t<> entered{0};
    bool failed = false;
    try {
        function_failU_after(returned, entered);
    } catch (const std::exception&) { failed = true; }
    if (!failed || entered.native_value() != 0) return 9;
    if (returned.get() != nullptr) return 10;
    if (!static_cast<bool>(call_lifetime.expired())) return 11;
    return 0;
}
CPP;
$lifetime_path = $output . '/move_lifetime.cpp';
file_put_contents($lifetime_path, "#define main generated_program_main\n" . $lifetime_source . "\n" . $lifetime_harness);
$executions[] = ['path' => $lifetime_path, 'exit_code' => 0];

// Native identity and destruction checks call the generated observation functions unchanged.
$observation_input = 'class P { shared<int> $marker; unique<int> $noncopyable; } function observe(P &$p): weak<P> { return weak($p); } function acquire(weak<P> &$w): P { return lock($w); } return 0;';
$observation_native_source = ownership_source($observation_input);
$observation_harness = <<<'CPP'
#undef main
#include "scpp/memory.hpp"
int main() {
    auto owner = scpp::shared<record_P>();
    owner->field_marker = scpp::shared<scpp::int_t<>>();
    auto marker = scpp::weak(owner->field_marker);
    auto* identity = owner.get();
    auto observer = function_observe(owner);
    if (owner.get() != identity || observer.use_count() != 1) return 1;
    auto observer_copy = observer;
    if (observer.use_count() != 1) return 2;
    auto held = function_acquire(observer_copy);
    if (held.get() != identity || observer.use_count() != 2) return 3;
    owner.reset();
    if (held.get() != identity || observer.use_count() != 1) return 4;
    if (static_cast<bool>(marker.expired())) return 5;
    held.reset();
    if (!static_cast<bool>(marker.expired())) return 6;
    if (function_acquire(observer).get() != nullptr) return 7;
    if (function_acquire(observer_copy).get() != nullptr) return 8;
    return 0;
}
CPP;
$observation_path = $output . '/weak_identity.cpp';
file_put_contents($observation_path, "#define main generated_program_main\n" . $observation_native_source . "\n" . $observation_harness);
$executions[] = ['path' => $observation_path, 'exit_code' => 0];
$rejections = [];
foreach (['unique<int>', 'weak<int>', 'int', 'dynamic', '?int'] as $invalid_type) {
	$rejections[] = ['$v ' . $invalid_type . '; $w = weak($v);', 'requires shared ownership'];
}
foreach (['shared<int>', 'unique<int>', 'int', 'mixed', '?int'] as $invalid_type) {
	$rejections[] = ['$v ' . $invalid_type . '; $held = lock($v);', 'requires weak ownership'];
}
$rejections[] = ['$w = weak(null);', 'requires shared ownership'];
$rejections[] = ['$held = lock(null);', 'requires weak ownership'];
$rejections[] = ['$s shared<int>; $w weak<int> = $s;', 'ownership boundary'];
$rejections[] = ['$s shared<int>; $w weak<string> = weak($s);', 'ownership boundary'];
$rejections[] = ['$w weak<int>; $s shared<string> = lock($w);', 'ownership boundary'];
$rejections[] = ['$s shared<int>; $alias =& weak($s);', 'stable'];
$rejections[] = ['$w weak<int>; $alias =& lock($w);', 'stable'];
$rejections[] = ['function borrow(shared<int> &$s): void {} $w weak<int>; borrow(lock($w));', 'stable'];
foreach (['move($a);', 'if (move($a)) {}', '$b = empty(move($a));', '$b =& move($a);', '$b = move(move($a));'] as $invalid) {
	$rejections[] = ['class P { int $x; } $a unique<P> = new P(); ' . $invalid, 'move requires'];
}
$rejections[] = ['function consume(unique<int> $a): void {} $a unique<int>; consume($a);', 'copyable'];
$rejections[] = ['function bad(unique<int> $a): unique<int> { return $a; }', 'copyable'];
$rejections[] = ['function borrow(unique<int> &$a): void {} $a unique<int>; borrow(move($a));', 'move requires'];
$rejections[] = ['function bad(unique<int> ...$a): void {}', 'copyable'];
$rejections[] = ['function &borrow(unique<int> &$a): unique<int> { return $a; } $a unique<int>; $b = borrow($a);', 'copyable'];
$rejections[] = ['function produce(): unique<int> { $p unique<int>; return move($p); } $a =& produce();', 'stable'];
$rejections[] = ['function produce(): unique<int> { $p unique<int>; return move($p); } struct H { unique<int> $p; } $h H; $h->p = produce();', 'fields'];
$rejections[] = ['function produce(): unique<int> { $p unique<int>; return move($p); } struct H { unique<int> $p; } $h H; $alias =& $h->p; $alias = produce();', 'field aliases'];
$rejections[] = ['function bad(unique<int> $a): unique<string> { return move($a); }', 'ownership boundary'];
$rejections[] = ['function consume(unique<string> $a): void {} $a unique<int>; consume(move($a));', 'ownership boundary'];
$rejections[] = ['$a shared<int>; $b = move($a);', 'unique ownership'];
$rejections[] = ['$a weak<int>; $b = move($a);', 'unique ownership'];
$rejections[] = ['$a = 1; $b = move($a);', 'unique ownership'];
$rejections[] = ['class P { int $x; } $b = move(new P());', 'whole local'];
$rejections[] = ['$a unique<int>; $b unique<string> = move($a);', 'ownership boundary'];
$rejections[] = ['$a unique<int>; $b shared<int> = move($a);', 'ownership boundary'];
$rejections[] = ['$a unique<int>; $b unique<int>; $c = ($b = move($a));', 'copyable'];
$rejections[] = ['$a unique<int>; $b unique<int>; $c = $b = move($a);', 'copyable'];
$rejections[] = ['struct H { unique<int> $p; } $h H; $alias =& $h->p; $b = move($alias);', 'field aliases'];
$rejections[] = ['struct H { unique<int> $p; } $h H; $alias =& $h->p; $b unique<int>; $alias = move($b);', 'field aliases'];
$rejections[] = ['struct H { unique<int> $p; } $h H; $b = move($h->p);', 'whole local'];
$rejections[] = ['struct H { unique<int> $p; } $h H; $b unique<int>; $h->p = move($b);', 'move requires'];
$rejections[] = ['$a unique<int>; $b = move($a); $copy = $b;', 'copyable'];
$rejections[] = ['class P { int $x; } $p = new P(1);', 'constructor arguments'];
$rejections[] = ['class P { int $x; } $p weak<P> = new P();', 'cannot allocate'];
$rejections[] = ['class P { int $x; } struct Q { int $x; } $p unique<P> = new Q();', 'exactly match'];
$rejections[] = ['class P { result<int> $x; } $p = new P();', 'default-constructible'];
$rejections[] = ['$p = new int();', 'source struct'];
$rejections[] = ['class P { int $x; } $p unique<P> = new P(); $copy = $p;', 'copyable'];
$rejections[] = ['class P { int $x; } $p unique<P>; $p = new P();', 'ownership boundary'];
$rejections[] = ['class P { int $x; } function make(): unique<P> { return new P(); }', 'ownership boundary'];
foreach (['shared', 'unique', 'weak'] as $outer) {
	foreach (['shared', 'unique', 'weak'] as $inner) {
		$rejections[] = ['$v ' . $outer . '<' . $inner . '<int>>;', 'Direct ownership wrapper nesting'];
	}
	$rejections[] = ['$v value<' . $outer . '<int>>;', 'Value modifier'];
}
$rejections[] = ['struct Bad { shared<weak<int>> $field; }', 'Direct ownership wrapper nesting'];
$rejections[] = ['function bad(shared<unique<int>> $v): void {}', 'Direct ownership wrapper nesting'];
$rejections[] = ['$v vector<shared<shared<int>>>;', 'Direct ownership wrapper nesting'];
$rejections[] = ['$v unique<int>; $copy = $v;', 'copyable'];
$rejections[] = ['$w weak<int>; if ($w) {}', 'truthiness'];
$rejections[] = ['$w weak<int>; $test = empty($w);', 'empty requires'];
foreach (['!$w', 'isset($w)', '$w === null', 'null !== $w'] as $expression) {
	$rejections[] = ['$w weak<int>; $test = ' . $expression . ';', 'explicit lock'];
}
foreach (['shared', 'unique', 'weak'] as $family) {
	$rejections[] = ['$v ' . $family . '<int>; clean($v);', 'clean'];
	$rejections[] = ['$v ' . $family . '<int>; $alias =& $v; unset($alias);', 'alias'];
	$rejections[] = ['$v ' . $family . '<int> = 1;', 'ownership boundary'];
}
$rejections[] = ['$u unique<int>; $copy = ($u = null);', 'copyable'];
$rejections[] = ['function consume(unique<int> $u): void {} consume(null);', 'ownership boundary'];
$rejections[] = ['function make(): unique<int> { return null; }', 'ownership boundary'];
foreach ($rejections as [$source, $message])
{
	$failed = false;
	try {
		ownership_source($source);
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), $message);
		ownership_source_check($failed, 'Unexpected ownership diagnostic: ' . $error->getMessage());
	}
	ownership_source_check($failed && Model::$cpp_files->is_empty(), 'Invalid ownership source was published');
}

$directory = sys_get_temp_dir() . '/scpp_ownership_recovery_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
try
{
	$valid = 'function borrow(shared<int> &$v): void {} function absent(shared<int> $v): bool { return !$v; } $s shared<int>; borrow($s); if (!absent($s)) { return 1; } return 0;';
	file_put_contents($path, $valid);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$original = CPP_Probe::text(Model::$cpp_files);
	foreach (['shared<shared<int>>', 'value<shared<int>>', 'weak<int>'] as $invalid_type)
	{
		file_put_contents($path, str_replace('shared<int>', $invalid_type, $valid));
		foreach ([[$path], []] as $changes)
		{
			$failed = false;
			try {
				$compiler->update_cpp($changes);
			}
			catch (\RuntimeException $error) {
				$failed = true;
			}
			ownership_source_check($failed && Model::$cpp_files->is_empty(), 'Rejected type edit published output');
		}
		file_put_contents($path, $valid);
		$compiler->update_cpp([$path]);
		ownership_source_check(CPP_Probe::text(Model::$cpp_files) === $original, 'Ownership repair changed output');
	}
	Compiler_Lifecycle::reset_preparation();
	$compiler->prepare();
	$compiler->cpp();
	ownership_source_check(CPP_Probe::text(Model::$cpp_files) === $original, 'Ownership cleanup/reprepare changed output');

	$construction_source = 'struct Field { int $n; } class Box { Field $field; } function build(): Box { return new Box(); } $u unique<Box> = new Box(); return 0;';
	file_put_contents($path, $construction_source);
	$compiler->update_cpp([$path]);
	$construction_output = CPP_Probe::text(Model::$cpp_files);
	file_put_contents($path, str_replace('int $n', 'result<int> $n', $construction_source));
	foreach ([[$path], []] as $changes) {
		$failed = false;
		try { $compiler->update_cpp($changes); }
		catch (\RuntimeException $error) {
			$failed = str_contains($error->getMessage(), 'default-constructible')
				|| str_contains($error->getMessage(), 'Inline record property requires an explicit initializer');
		}
		ownership_source_check($failed && Model::$cpp_files->is_empty(), 'Nested default-construction edit was not rejected');
	}
	file_put_contents($path, $construction_source);
	$compiler->update_cpp([$path]);
	ownership_source_check(CPP_Probe::text(Model::$cpp_files) === $construction_output, 'Construction repair changed output');
	Compiler_Lifecycle::reset_preparation();
	$compiler->prepare();
	$compiler->cpp();
	ownership_source_check(CPP_Probe::text(Model::$cpp_files) === $construction_output, 'Construction cleanup changed output');

	$move_source = 'function transfer(unique<int> &$a, unique<int> &$b): void { $b = move($a); } $a unique<int>; $b unique<int>; transfer($a, $b); return 0;';
	file_put_contents($path, $move_source);
	$compiler->update_cpp([$path]);
	$move_output = CPP_Probe::text(Model::$cpp_files);
	file_put_contents($path, str_replace('unique<int>', 'shared<int>', $move_source));
	foreach ([[$path], []] as $changes) {
		$failed = false;
		try { $compiler->update_cpp($changes); }
		catch (\RuntimeException $error) { $failed = str_contains($error->getMessage(), 'unique ownership'); }
		ownership_source_check($failed && Model::$cpp_files->is_empty(), 'Signature edit reused invalid move facts');
	}
	file_put_contents($path, $move_source);
	$compiler->update_cpp([$path]);
	ownership_source_check(CPP_Probe::text(Model::$cpp_files) === $move_output, 'Move repair changed output');
	Compiler_Lifecycle::reset_preparation();
	$compiler->prepare();
	$compiler->cpp();
	ownership_source_check(CPP_Probe::text(Model::$cpp_files) === $move_output, 'Move cleanup changed output');

	$consume_source = 'function sink(unique<int> $p): void {} $a unique<int>; sink(move($a)); return 0;';
	file_put_contents($path, $consume_source);
	$compiler->update_cpp([$path]);
	$consume_output = CPP_Probe::text(Model::$cpp_files);
	file_put_contents($path, str_replace('unique<int> $p', 'unique<int> &$p', $consume_source));
	foreach ([[$path], []] as $changes) {
		$failed = false;
		try { $compiler->update_cpp($changes); }
		catch (\RuntimeException $error) { $failed = str_contains($error->getMessage(), 'move requires'); }
		ownership_source_check($failed && Model::$cpp_files->is_empty(), 'Passing-mode edit reused consuming argument facts');
	}
	file_put_contents($path, $consume_source);
	$compiler->update_cpp([$path]);
	ownership_source_check(CPP_Probe::text(Model::$cpp_files) === $consume_output, 'Consume repair changed output');
	Compiler_Lifecycle::reset_preparation();
	$compiler->prepare();
	$compiler->cpp();
	ownership_source_check(CPP_Probe::text(Model::$cpp_files) === $consume_output, 'Consume cleanup changed output');

	$observation_source = 'function source(): shared<int> { $s shared<int>; return $s; } function read(): bool { $w = weak(source()); return isset(lock($w)); } return 0;';
	file_put_contents($path, $observation_source);
	$compiler->update_cpp([$path]);
	$observation_output = CPP_Probe::text(Model::$cpp_files);
	file_put_contents($path, str_replace('shared<int>', 'weak<int>', $observation_source));
	foreach ([[$path], []] as $changes) {
		$failed = false;
		try { $compiler->update_cpp($changes); }
		catch (\RuntimeException $error) { $failed = str_contains($error->getMessage(), 'requires shared ownership'); }
		ownership_source_check($failed && Model::$cpp_files->is_empty(), 'Signature edit reused invalid observation facts');
	}
	file_put_contents($path, $observation_source);
	$compiler->update_cpp([$path]);
	ownership_source_check(CPP_Probe::text(Model::$cpp_files) === $observation_output, 'Observation repair changed output');
	Compiler_Lifecycle::reset_preparation();
	$compiler->prepare();
	$compiler->cpp();
	ownership_source_check(CPP_Probe::text(Model::$cpp_files) === $observation_output, 'Observation cleanup changed output');
	$changed_payload = str_replace('shared<int>', 'shared<string>', $observation_source);
	file_put_contents($path, $changed_payload);
	$compiler->update_cpp([$path]);
	$incremental_output = CPP_Probe::text(Model::$cpp_files);
	ownership_source_check($incremental_output !== $observation_output, 'Payload edit did not change emitted types');
	ownership_source_check($incremental_output === ownership_source($changed_payload), 'Observation payload edit differs from fresh preparation');
}
finally {
	unlink($path);
	rmdir($directory);
}
file_put_contents($output . '/executions.json', json_encode($executions, JSON_PRETTY_PRINT));
$lifetime_base = $output . '/move_lifetime_base.cpp';
$observation_base = $output . '/weak_identity_base.cpp';
file_put_contents($lifetime_base, $lifetime_source);
file_put_contents($observation_base, $observation_native_source);
$programs = ['valid' => [], 'rejected' => [], 'probes' => [
	['name' => 'move-lifetime', 'source' => $lifetime_input, 'base_path' => $lifetime_base, 'path' => $lifetime_path, 'exit_code' => 0],
	['name' => 'weak-identity', 'source' => $observation_input, 'base_path' => $observation_base, 'path' => $observation_path, 'exit_code' => 0],
]];
foreach ($cases as $name => $source) {
	$programs['valid'][$name] = [$source, 0];
}
foreach ($rejections as [$source, $message]) {
	$programs['rejected'][] = $source;
}
file_put_contents($output . '/programs.json', json_encode($programs, JSON_PRETTY_PRINT));
echo 'Ownership source: ' . count($executions) . ' programs, ' . count($rejections) . " rejections and recovery passed\n";
