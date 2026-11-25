<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function compact_compile(string $source): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/compact', '/compact', '/compact');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	(new Compiler())->exec_cpp();
	return CPP_Probe::text(Model::$cpp_files);
}

$source = <<<'PHS'
final struct Pair {
	public int32 $first = 0;
	public int32 $second = 0;
}
struct Nested { Pair $pair; }
enum Kind : uint16 { case One = 1; case Two = 2; }
union Payload {
	public int32 $word;
	bool $flag;
	Pair $pair;
	Kind $kind;
	Nested $nested;
}
struct Row { Payload $payload; }
$u = new Payload();
$u->word = (int32)7;
echo $u->word, "\n";
$p = new Pair();
$p->first = (int32)11;
$p->second = (int32)13;
$u->pair = $p;
$copy = $u;
$u->pair->first = (int32)17;
echo $copy->pair->first, ":", $u->pair->first, "\n";
$u->flag = true;
echo $u->flag, "\n";
$u->kind = Kind::Two;
echo enum_name($u->kind), "\n";
$row = new Row();
$row->payload = $copy;
echo $row->payload->pair->second, "\n";
$nested = new Nested();
$nested->pair = $p;
$u->nested = $nested;
echo $u->nested->pair->first, "\n";
PHS;
file_put_contents($argv[1] . '/union.cpp', compact_compile($source));
$queries = <<<'PHS'
struct Pair { public int32 $first = 0; public uint16 $second = 0; }
union Payload { int32 $word; Pair $pair; }
enum Kind : uint16 { case One = 1; }
function pair_size(): int { return layout_sizeof(Pair); }
function pair_alignment(): int { return layout_alignof(Pair::class); }
function pair_offset(): int { return layout_offsetof(Pair, second); }
function pair_field_size(): int { return layout_field_sizeof(Pair::class, second); }
function payload_size(): int { return layout_sizeof(Payload::class); }
function payload_alignment(): int { return layout_alignof(Payload); }
function payload_offset(): int { return layout_offsetof(Payload::class, pair); }
function payload_field_size(): int { return layout_field_sizeof(Payload, pair); }
function enum_size(): int { return layout_sizeof(Kind); }
function scalar_size(): int { return layout_sizeof(int32); }
PHS;
$observer = <<<'CPP'
#include <cassert>
#include <cstddef>
#include <type_traits>
static_assert(std::is_union_v<record_Payload>);
static_assert(std::is_trivially_copyable_v<record_Payload>);
static_assert(std::is_trivially_destructible_v<record_Payload>);
static_assert(std::is_trivially_copyable_v<record_Pair>);
int main() {
	assert(function_pairU_size().native_value() == sizeof(record_Pair));
	assert(function_pairU_alignment().native_value() == alignof(record_Pair));
	assert(function_pairU_offset().native_value() == offsetof(record_Pair, field_second));
	assert(function_pairU_fieldU_size().native_value() == sizeof(scpp::int_t<std::uint16_t>));
	assert(function_payloadU_size().native_value() == sizeof(record_Payload));
	assert(function_payloadU_alignment().native_value() == alignof(record_Payload));
	assert(function_payloadU_offset().native_value() == 0);
	assert(function_payloadU_fieldU_size().native_value() == sizeof(record_Pair));
	assert(function_enumU_size().native_value() == sizeof(std::uint16_t));
	assert(function_scalarU_size().native_value() == sizeof(std::int32_t));
}
CPP;
$cpp = str_replace('int main(int argc, char** argv)', 'int unused_entry(int argc, char** argv)', compact_compile($queries));
file_put_contents($argv[1] . '/layout.cpp', $cpp . "\n" . $observer);
$operations = <<<'PHS'
union Payload { int32 $word; bool $flag; }
function failed(): int32 { return (int32)(1 / 0); }
function replace(Payload &$u): void { $u->word = $u->word + (int32)1; }
function failwrite(Payload &$u): void { $u->word = failed(); }
function sharedwrite(): int32 {
	$u shared<Payload> = new Payload();
	$items vector<shared<Payload>> = [$u];
	$items[0]->word = (int32)19;
	$items[0]->flag = true;
	$items[0]->word = (int32)23;
	return $u->word;
}
PHS;
$observer = <<<'CPP'
#include <cassert>
#include <memory>
int main() {
	record_Payload value;
	std::construct_at(std::addressof(value.field_word), scpp::int_t<std::int32_t>{7});
	function_replace(value);
	assert(value.field_word.native_value() == 8);
	bool caught = false;
	try { function_failwrite(value); } catch (const scpp::runtime_error& error) {
		caught = error.code() == "division_by_zero";
	}
	assert(caught && value.field_word.native_value() == 8);
	assert(function_sharedwrite().native_value() == 23);
}
CPP;
$cpp = str_replace('int main(int argc, char** argv)', 'int unused_entry(int argc, char** argv)', compact_compile($operations));
file_put_contents($argv[1] . '/writes.cpp', $cpp . "\n" . $observer);
$negative = [
	'union U { public function f(): int { return 1; } }',
	'union U { const X = 1; }',
	'union U { private int32 $x; }',
	'union U { protected int32 $x; }',
	'union U { public static int32 $x; }',
	'union U { int32 $x = 0; }',
	'union U { int32 $x; int32 $x; }',
	'final union U { int32 $x; }',
	'abstract union U { int32 $x; }',
	'data union U { int32 $x; }',
	'union B { int32 $x; } union U extends B { int32 $y; }',
	'interface I {} union U implements I { int32 $x; }',
	'trait T { public int32 $x; } union U { use T; }',
	'union V { int32 $x; } union U { V $v; }',
	'class C {} union U { C $v; }',
	'enum E { case One; } union U { E $v; }',
	'enum E : int { case One = 1; } union U { E $v; }',
	'struct S { string $x; } union U { S $v; }',
	'struct S { vector<int32> $x; } struct T { S $s; } union U { T $v; }',
	'struct S { public static int32 $x; } union U { S $v; }',
	'struct S { private int32 $x; } union U { S $v; }',
	'struct S { public function f(): int { return 1; } } union U { S $v; }',
	'union U { S $s; } struct S { U $u; }',
	'union U { int32 $x; } $u = new U(1);',
	'layout_sizeof();',
	'layout_sizeof(int32, int32);',
	'layout_alignof("int32");',
	'layout_sizeof($missing);',
	'layout_sizeof(Missing);',
	'layout_sizeof(void);',
	'layout_offsetof(int32, field);',
	'struct S { int32 $x; } layout_offsetof(S);',
	'struct S { int32 $x; } layout_offsetof(S, missing);',
	'struct S { int32 $x; } layout_field_sizeof(S, "x");',
	'struct S { int32 $x; } layout_field_sizeof(S, x, x);',
	'struct S { private int32 $x; } layout_field_sizeof(S, x);',
	'struct S { public static int32 $x; } layout_offsetof(S, x);',
];
foreach (['int', 'float', 'string', 'mixed', 'dynamic', '?int32', 'vector<int32>', 'hash<int32>', 'fixed_array<int32, 2>', 'shared<S>', 'unique<S>', 'weak<S>'] as $type) {
	$negative[] = 'struct S { int32 $x; } union U { ' . $type . ' $v; }';
}
$rejections = [];
foreach ($negative as $source) {
	$error = '';
	try { compact_compile($source); }
	catch (\RuntimeException $failure) { $error = $failure->getMessage(); }
	if (($error === '') || !Model::$cpp_files->is_empty()) {
		throw new \LogicException('Invalid compact layout admitted: ' . $source);
	}
	$rejections[] = ['source' => $source, 'diagnostic' => $error];
}
// Every declared width and both bool payload states, with nominal enum storage.
$widths = 'union Widths { bool $flag;';
foreach (['int8', 'int16', 'int32', 'int64', 'uint8', 'byte', 'uint16', 'uint32', 'uint64'] as $type) {
	$widths .= $type . ' $v' . $type . ';';
}
$widths .= '} $u = new Widths();';
foreach (['int8', 'int16', 'int32', 'int64', 'uint8', 'byte', 'uint16', 'uint32', 'uint64'] as $type) {
	$widths .= '$u->v' . $type . '=(' . $type . ')7; echo $u->v' . $type . '," ";';
}
$widths .= '$u->flag=false;echo $u->flag," ";$u->flag=true;echo $u->flag,"\\n";';
file_put_contents($argv[1] . '/widths.cpp', compact_compile($widths));
file_put_contents($argv[1] . '/shadow.cpp', compact_compile('function layout_sizeof(int $x):int{return $x+2;}echo layout_sizeof(9),"\\n";'));

// Cross-file imports and nested payload dependencies survive error/retry/repair.
$directory = $argv[1] . '/retained';
mkdir($directory);
$producer = $directory . '/producer.phs';
$consumer = $directory . '/main.phs';
$good = 'namespace Layout; struct Pair { public int32 $x; } union Payload { Pair $pair; bool $flag; }';
$caller = 'namespace Client; use Layout\\Pair as P; use Layout\\Payload as U;'
	. '$u = new U(); echo layout_offsetof(U, pair),":",'
	. 'layout_field_sizeof(P::class,x)===layout_sizeof(int32),":",'
	. 'layout_sizeof(U)>=layout_sizeof(P),":",layout_alignof(U)>=layout_alignof(P),"\\n";';
file_put_contents($producer, $good);
file_put_contents($consumer, $caller);
Compiler_Lifecycle::reset();
$compiler = new Compiler();
$compiler->init([$directory]);
$compiler->select_entry('main', 'main.phs');
$compiler->exec_cpp();
$baseline = CPP_Probe::text(Model::$cpp_files);
file_put_contents($argv[1] . '/imported.cpp', $baseline);
file_put_contents($producer, str_replace('int32', 'string', $good));
$compiler->sync([$producer]);
$error = '';
try { $compiler->prepare(); } catch (\RuntimeException $failure) { $error = $failure->getMessage(); }
if (($error === '') || !Model::$cpp_files->is_empty()) { throw new \LogicException('Invalid nested payload survived producer edit'); }
$compiler->sync([]);
$retry = '';
try { $compiler->prepare(); } catch (\RuntimeException $failure) { $retry = $failure->getMessage(); }
if ($error !== $retry) { throw new \LogicException('No-edit retry changed the failure'); }
file_put_contents($producer, $good);
$compiler->sync([$producer]); $compiler->prepare(); $compiler->cpp();
if ($baseline !== CPP_Probe::text(Model::$cpp_files)) { throw new \LogicException('Repair differs from baseline'); }
$compiler->sync([]); $compiler->prepare(); $compiler->cpp();
if ($baseline !== CPP_Probe::text(Model::$cpp_files)) { throw new \LogicException('No-op changed output'); }
file_put_contents($producer, str_replace('int32', 'int64', $good));
$compiler->sync([$producer]); $compiler->prepare(); $compiler->cpp();
$changed = CPP_Probe::text(Model::$cpp_files);
file_put_contents($argv[1] . '/changed.cpp', $changed);
Compiler_Lifecycle::reset();
$compiler = new Compiler(); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
if ($changed !== CPP_Probe::text(Model::$cpp_files)) { throw new \LogicException('Retained layout edit differs from fresh'); }
file_put_contents($argv[1] . '/checks.json', json_encode(['rejections' => $rejections, 'recovery_cycles' => 1], JSON_PRETTY_PRINT) . "\n");
echo "Restricted union preparation passed\n";
