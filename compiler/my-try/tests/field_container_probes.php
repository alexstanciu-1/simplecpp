<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

/** Compile each isolated program and verify retained no-op output stability. */
function field_probe_compile(string $source): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/field-probes', '/field-probes', '/field-probes');
	$file = new file(); $file->path = 'main.phs'; $file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	$compiler = new Compiler(); $compiler->exec_cpp();
	$result = CPP_Probe::text(Model::$cpp_files);
	$compiler->exec_cpp();
	if ($result !== CPP_Probe::text(Model::$cpp_files)) { throw new \LogicException('Field probe no-op changed output'); }
	return $result;
}

$cases = [
'families' => <<<'PHS'
struct Values {
    public $items vector<string> = ["", "0"];
    public $map hash<?int> = {nil: null, zero: 0};
    public $m mixed = {child: {x: 1}};
    public $d dynamic = {child: {x: 1}};
    public function remove(): void { unset($this->map["zero"]); }
}
class Holder { public $values Values; }
function observe(const Values &$v): bool { return isset($v->map["zero"]); }
$h = new Holder();
$a = empty($h->values->items[0]);
$b = empty($h->values->items[1]);
$c = isset($h->values->map["nil"]);
$v Values;
$e = observe($v);
$v->remove();
$f = isset($v->map["zero"]);
$copy = $h->values;
unset($h->values->m["child"]["x"]);
unset($h->values->d["child"]["x"]);
$m = isset($copy->m["child"]["x"]);
$d = isset($copy->d["child"]["x"]);
$gone = isset($h->values->m["child"]["x"]);
if (!$a || $b || $c || !$e || $f || !$m || $d || $gone) { return 1; }
return 0;
PHS,
'lifetime' => <<<'PHS'
class Bag {
    public static int $last = -1;
    public $map hash<int> = {x: 1};
    public function __destruct() { Bag::$last = count($this->map); }
}
class Holder { public $bag Bag; }
function replace(Holder &$h, int &$n): string {
    $h = new Holder(); $h->bag = new Bag(); $n++; return "x";
}
$h = new Holder(); $h->bag = new Bag(); $n = 0;
$present = isset($h->bag->map[replace($h, $n)]);
$last = Bag::$last;
if (!$present || $last !== 1 || $n !== 1) { return 1; }
unset($h->bag->map[replace($h, $n)]);
$last = Bag::$last;
$fresh = isset($h->bag->map["x"]);
if ($last !== 0 || !$fresh || $n !== 2) { return 2; }
return 0;
PHS,
'lazy' => <<<'PHS'
class Box { public $map mixed = {a: {x: {leaf: 1}}}; }
function clear(Box $b, int &$n): string { $b->map = {}; $n++; return "x"; }
function later(int &$n): string { $n += 10; return "leaf"; }
$b = new Box(); $n = 0;
$present = isset($b->map["a"][clear($b, $n)][later($n)]);
unset($b->map["missing"][later($n)]);
$size = count($b->map);
if ($present || $n !== 1 || $size !== 0) { return 1; }
$b->map = {a: {x: {leaf: 1}}};
unset($b->map["a"][clear($b, $n)][later($n)]);
$size = count($b->map);
if ($n !== 2 || $size !== 0) { return 2; }
return 0;
PHS,
];
foreach ($cases as $name => $source) { file_put_contents($argv[1] . '/' . $name . '.cpp', field_probe_compile($source)); }

$source = <<<'PHS'
class Box { public $map mixed = {nil: null, wrong: 12}; }
function box(): Box { return new Box(); }
function key(int &$n): string { $n++; return "leaf"; }
function probe(Box $b, int &$n): bool { return isset($b->map[key($n)]); }
function blank(Box $b, string $k, int &$n): bool { return empty($b->map[$k][key($n)]); }
function remove(Box $b, string $k, int &$n): void { unset($b->map[$k][key($n)]); }
function size(Box $b): int { return count($b->map); }
return 0;
PHS;
$cpp = str_replace('int main(int argc, char** argv)', 'int unused_entry(int argc, char** argv)', field_probe_compile($source));
$cpp .= <<<'CPP'

int main() {
    auto box = function_box(); scpp::int_t<> n{0}; decltype(box) absent;
    try { (void)function_probe(absent, n); return 1; } catch (const std::exception&) {}
    try { function_remove(absent, scpp::string_t{"missing"}, n); return 2; } catch (const std::exception&) {}
    for (const char* key : {"missing", "nil", "wrong"}) {
        try { (void)function_blank(box, scpp::string_t{key}, n); return 3; } catch (const std::exception&) {}
    }
    function_remove(box, scpp::string_t{"missing"}, n);
    function_remove(box, scpp::string_t{"nil"}, n);
    try { function_remove(box, scpp::string_t{"wrong"}, n); return 4; } catch (const std::exception&) {}
    return n.native_value() == 0 && function_size(box).native_value() == 2 ? 0 : 5;
}
CPP;
file_put_contents($argv[1] . '/failures.cpp', $cpp);
foreach ([
    'class A { private $v hash<int> = {}; } $a = new A(); $b = isset($a->v["x"]);',
    'struct A { public $v hash<int> = {}; } function f(const A &$a): void { unset($a->v["x"]); }',
    'class A { public $v hash<int> = {}; } function make(): A { return new A(); } $b = isset(make()->v["x"]);',
    'class A { public $v vector<int> = []; } $a = new A(); unset($a->v[0]);',
    'class A { public $v hash<int> = {}; } $a = new A(); $b = empty($a->v["x"]);',
    'class A { public $v hash<int> = {}; } $a vector<A> = []; $b = isset($a[0]->v["x"]);',
] as $invalid) {
    $failed = false;
    try { field_probe_compile($invalid); } catch (\RuntimeException $error) { $failed = true; }
    if (!$failed || !Model::$cpp_files->is_empty()) { throw new \LogicException('Invalid field probe admitted: ' . $invalid); }
}
echo "Field container probes/removal passed\n";
