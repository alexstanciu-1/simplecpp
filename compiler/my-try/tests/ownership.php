<?php

/* Ownership admission, default allocation and recovery; explicit moves/locking remain deferred. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

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
	return Model::$cpp_files[0]->text;
}

/** Inspect the retained construction contract independently of C++ execution. */
final class Ownership_Construction_Probe implements node_maintenance_worker_i
{
	public int $count = 0;
	public int $moves = 0;

	public function enter(ast_node $node): void
	{
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
		ownership_source_check($role->ownership_family() === $facts->ownership, 'Construction ownership disagrees with result');
		$application = object_cast(Type_Queries::canonical($facts->type), applied_template_type::class);
		ownership_source_check($role->target_type($application)->matches($facts->constructed_type), 'Construction target disagrees with result');
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
	'move_local' => 'struct P { int $x; } $a unique<P> = new P(); $alias =& $a; $b = move($a); if (isset($a)) { return 1; } if (!empty($alias)) { return 2; } if (!$b) { return 3; } $a = move($b); if (!$alias) { return 4; } if ($b !== null) { return 5; } $c unique<P> = move($alias); if ($a !== null) { return 6; } if (!$c) { return 7; } return 0;',
	'move_replace' => 'struct P { int $x; } $a unique<P> = new P(); $b unique<P> = new P(); $a = move($b); if (!$a) { return 1; } if (isset($b)) { return 2; } $a = move($a); if (!$a) { return 3; } $alias =& $a; $alias = move($a); if (!$a) { return 4; } $a = move($alias); if (!$alias) { return 5; } $a = move($b); if (!empty($alias)) { return 6; } $c = move($b); if (!empty($c)) { return 7; } return 0;',
	'move_parameters' => 'struct P { int $x; } function transfer(unique<P> &$source, unique<P> &$destination): void { $destination = move($source); } $a unique<P> = new P(); $b unique<P>; transfer($a, $b); if (isset($a)) { return 1; } if (!$b) { return 2; } transfer($b, $b); if (!$b) { return 3; } return 0;',
	'allocated_shared' => 'struct Point { int $x; } $p = new Point(); $copy = $p; if (!$p) { return 1; } if (empty($copy)) { return 2; } unset($p); if ($p !== null) { return 3; } if (!isset($copy)) { return 4; } $q shared<Point> = new Point(); if (null === $q) { return 5; } return 0;',
	'allocated_unique' => 'struct Point { int $x; } function reset(unique<Point> &$p): void { $p = null; } $p unique<Point> = new Point(); $a =& $p; if (!$a) { return 1; } reset($p); if (isset($a)) { return 2; } $q unique<Point> = new Point(); unset($q); if (!empty($q)) { return 3; } return 0;',
	'allocated_noncopyable' => 'struct Item { unique<int> $child; } struct Box { Item $item; vector<int> $values; ?string $note; dynamic $data; } $s = new Box(); $copy = $s; $u unique<Box> = new Box(); if (!$u) { return 1; } if (!$copy) { return 2; } return 0;',
	'allocated_expression' => 'struct Point { int $x; } function make(): shared<Point> { return new Point(); } function present(shared<Point> $p): bool { return isset($p); } if (!present(new Point())) { return 1; } if (empty(make())) { return 2; } return 0;',
	'empty_reset' => 'function reset_unique(unique<int> &$u): void { $u = null; } $s shared<int> = null; $u unique<int> = null; $w weak<int> = null; $a =& $u; $a = null; reset_unique($u); $s = null; $w = null; unset($s); unset($u); unset($w); if (isset($u)) { return 1; } if (!empty($s)) { return 2; } return 0;',
	'presence' => '$s shared<int>; $u unique<int>; if ($s) { return 1; } elseif ($u) { return 2; } while ($u) { return 3; } for (; $s;) { return 4; } do { if (!$u) { break; } return 5; } while ($s); $n = $u ? 1 : 0; if ($s !== null) { return 6; } if (null !== $u) { return 7; } if (!($s === null)) { return 8; } if (!(null === $u)) { return 9; } return $n;',
	'observe_once' => 'function observe(shared<int> $s, int &$calls): shared<int> { $calls += 1; return $s; } $s shared<int>; $calls = 0; if (observe($s, $calls)) { return 1; } $p = observe($s, $calls) === null; $q = isset(observe($s, $calls)); $r = empty(observe($s, $calls)); if ($calls !== 4) { return 2; } if (!$p) { return 3; } if ($q) { return 4; } if (!$r) { return 5; } return 0;',
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
	$executions[] = ['path' => $path, 'exit_code' => 0];
}

// Native instrumentation consumes unmodified generated transfer code; weak markers expose destruction.
$lifetime_source = ownership_source('struct P { shared<int> $marker; } function transfer(unique<P> &$source, unique<P> &$destination): void { $destination = move($source); } return 0;');
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
    return 0;
}
CPP;
$lifetime_path = $output . '/move_lifetime.cpp';
file_put_contents($lifetime_path, "#define main generated_program_main\n" . $lifetime_source . "\n" . $lifetime_harness);
$executions[] = ['path' => $lifetime_path, 'exit_code' => 0];
$rejections = [];
foreach (['move($a);', 'if (move($a)) {}', '$b = empty(move($a));', '$b =& move($a);', '$b = move(move($a));', 'function consume(unique<P> $p): void {} consume(move($a));'] as $invalid) {
	$rejections[] = ['struct P { int $x; } $a unique<P> = new P(); ' . $invalid, 'move requires'];
}
$rejections[] = ['struct P { int $x; } function bad(unique<P> &$a): unique<P> { return move($a); }', 'move requires'];
$rejections[] = ['$a shared<int>; $b = move($a);', 'unique ownership'];
$rejections[] = ['$a weak<int>; $b = move($a);', 'unique ownership'];
$rejections[] = ['$a = 1; $b = move($a);', 'unique ownership'];
$rejections[] = ['struct P { int $x; } $b = move(new P());', 'whole local'];
$rejections[] = ['$a unique<int>; $b unique<string> = move($a);', 'ownership boundary'];
$rejections[] = ['$a unique<int>; $b shared<int> = move($a);', 'ownership boundary'];
$rejections[] = ['$a unique<int>; $b unique<int>; $c = ($b = move($a));', 'copyable'];
$rejections[] = ['$a unique<int>; $b unique<int>; $c = $b = move($a);', 'copyable'];
$rejections[] = ['struct H { unique<int> $p; } $h H; $alias =& $h->p; $b = move($alias);', 'field aliases'];
$rejections[] = ['struct H { unique<int> $p; } $h H; $alias =& $h->p; $b unique<int>; $alias = move($b);', 'field aliases'];
$rejections[] = ['struct H { unique<int> $p; } $h H; $b = move($h->p);', 'whole local'];
$rejections[] = ['struct H { unique<int> $p; } $h H; $b unique<int>; $h->p = move($b);', 'move requires'];
$rejections[] = ['$a unique<int>; $b = move($a); $copy = $b;', 'copyable'];
$rejections[] = ['struct P { int $x; } $p = new P(1);', 'constructor arguments'];
$rejections[] = ['struct P { int $x; } $p weak<P> = new P();', 'cannot allocate'];
$rejections[] = ['struct P { int $x; } struct Q { int $x; } $p unique<P> = new Q();', 'exactly match'];
$rejections[] = ['struct P { result<int> $x; } $p = new P();', 'default-constructible'];
$rejections[] = ['$p = new int();', 'source struct'];
$rejections[] = ['struct P { int $x; } $p unique<P> = new P(); $copy = $p;', 'copyable'];
$rejections[] = ['struct P { int $x; } $p unique<P>; $p = new P();', 'ownership boundary'];
$rejections[] = ['struct P { int $x; } function make(): unique<P> { return new P(); }', 'ownership boundary'];
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
foreach (['!$w', 'isset($w)', 'empty($w)', '$w === null', 'null !== $w'] as $expression) {
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
	$original = Model::$cpp_files[0]->text;
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
		ownership_source_check(Model::$cpp_files[0]->text === $original, 'Ownership repair changed output');
	}
	Compiler_Lifecycle::reset_preparation();
	$compiler->prepare();
	$compiler->cpp();
	ownership_source_check(Model::$cpp_files[0]->text === $original, 'Ownership cleanup/reprepare changed output');

	$construction_source = 'struct Field { int $n; } struct Box { Field $field; } function build(): shared<Box> { return new Box(); } $u unique<Box> = new Box(); return 0;';
	file_put_contents($path, $construction_source);
	$compiler->update_cpp([$path]);
	$construction_output = Model::$cpp_files[0]->text;
	file_put_contents($path, str_replace('int $n', 'result<int> $n', $construction_source));
	foreach ([[$path], []] as $changes) {
		$failed = false;
		try { $compiler->update_cpp($changes); }
		catch (\RuntimeException $error) {
			$failed = str_contains($error->getMessage(), 'default-constructible');
		}
		ownership_source_check($failed && Model::$cpp_files->is_empty(), 'Nested default-construction edit was not rejected');
	}
	file_put_contents($path, $construction_source);
	$compiler->update_cpp([$path]);
	ownership_source_check(Model::$cpp_files[0]->text === $construction_output, 'Construction repair changed output');
	Compiler_Lifecycle::reset_preparation();
	$compiler->prepare();
	$compiler->cpp();
	ownership_source_check(Model::$cpp_files[0]->text === $construction_output, 'Construction cleanup changed output');

	$move_source = 'function transfer(unique<int> &$a, unique<int> &$b): void { $b = move($a); } $a unique<int>; $b unique<int>; transfer($a, $b); return 0;';
	file_put_contents($path, $move_source);
	$compiler->update_cpp([$path]);
	$move_output = Model::$cpp_files[0]->text;
	file_put_contents($path, str_replace('unique<int>', 'shared<int>', $move_source));
	foreach ([[$path], []] as $changes) {
		$failed = false;
		try { $compiler->update_cpp($changes); }
		catch (\RuntimeException $error) { $failed = str_contains($error->getMessage(), 'unique ownership'); }
		ownership_source_check($failed && Model::$cpp_files->is_empty(), 'Signature edit reused invalid move facts');
	}
	file_put_contents($path, $move_source);
	$compiler->update_cpp([$path]);
	ownership_source_check(Model::$cpp_files[0]->text === $move_output, 'Move repair changed output');
	Compiler_Lifecycle::reset_preparation();
	$compiler->prepare();
	$compiler->cpp();
	ownership_source_check(Model::$cpp_files[0]->text === $move_output, 'Move cleanup changed output');
}
finally {
	unlink($path);
	rmdir($directory);
}
file_put_contents($output . '/executions.json', json_encode($executions, JSON_PRETTY_PRINT));
echo 'Ownership source: ' . count($executions) . ' programs, ' . count($rejections) . " rejections and recovery passed\n";
