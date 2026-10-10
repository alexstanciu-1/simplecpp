<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function field_write_compile(string $source): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/field-writes', '/field-writes', '/field-writes');
	$file = new file(); $file->path = 'main.phs'; $file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	$compiler = new Compiler(); $compiler->exec_cpp();
	$result = CPP_Probe::text(Model::$cpp_files);
	$compiler->exec_cpp();
	if ($result !== CPP_Probe::text(Model::$cpp_files)) { throw new \LogicException('Field write no-op changed output'); }
	return $result;
}

$cases = [
'class_receiver' => <<<'PHS'
class Bag {
    private $items vector<int> = [1];
    private $map hash<int> = {};
    private $nested vector<vector<int>> = [[2]];
    public int $order = 0;
    public function key(): string { $this->order = $this->order * 10 + 1; return "x"; }
    public function value(): int { $this->order = $this->order * 10 + 2; return 7; }
    public function fill(): int {
        $this->items[] = 3;
        $this->items[0] = 4;
        $this->map[$this->key()] = $this->value();
        $this->nested[0][] = 5;
        return $this->items[0] + $this->items[1] + $this->map["x"] + $this->nested[0][1];
    }
}
$bag = new Bag();
$sum = $bag->fill();
if ($sum !== 19 || $bag->order !== 12) { return 1; }
return 0;
PHS,
'families' => <<<'PHS'
struct Values {
    public $items vector<int> = [];
    public $map hash<int> = {};
    public $nested vector<vector<int>> = [[1]];
    public $m mixed;
    public $d dynamic = {};
    public function add(): void {
        $this->items[] = 3;
        $this->map["a"] = 4;
        $this->nested[0][] = 5;
        $this->m["x"] = 6;
        $this->d["x"] = 7;
    }
}
class Holder { public $values Values; }
$h = new Holder();
$initial Values;
$initial->add();
$h->values = $initial;
$h->values->items[] = 8;
$h->values->map["a"] = 9;
$copy = $h->values;
$copy->items[0] = 11;
$a = $h->values->items[0];
$b = $copy->items[0];
$n = count($h->values->items);
$c = $h->values->map["a"];
$d = $h->values->nested[0][1];
$m int = $h->values->m["x"];
$v int = $h->values->d["x"];
if ($a !== 3 || $b !== 11 || $n !== 2 || $c !== 9 || $d !== 5 || $m !== 6 || $v !== 7) { return 1; }
return 0;
PHS,
'lifetime' => <<<'PHS'
class Bag { public $items vector<int> = [1]; }
class Holder { public $bag Bag; }
function key(Holder &$h, int &$order): int {
    $h = new Holder();
    $h->bag = new Bag();
    $order = $order * 10 + 1;
    return 0;
}
function value(int &$order): int { $order = $order * 10 + 2; return 17; }
$h = new Holder();
$h->bag = new Bag();
$selected = $h->bag;
$order = 0;
$h->bag->items[key($h, $order)] = value($order);
$old = $selected->items[0];
$fresh = $h->bag->items[0];
if ($old !== 17 || $fresh !== 1 || $order !== 12) { return 1; }
return 0;
PHS,
'last_owner' => <<<'PHS'
class Bag {
    public static int $last = 0;
    public $items vector<int> = [1];
    public function __destruct() { $n = $this->items[0]; Bag::$last = $n; }
}
function replace(Bag &$bag): int { $bag = new Bag(); return 19; }
$bag = new Bag();
$bag->items[0] = replace($bag);
$last = Bag::$last;
if ($last !== 19) { return 1; }
return 0;
PHS,
];
foreach ($cases as $name => $source) { file_put_contents($argv[1] . '/' . $name . '.cpp', field_write_compile($source)); }
$failure_source = <<<'PHS'
class Box { public $items vector<int> = [1]; public $map mixed = {}; }
function box(): Box { return new Box(); }
function size(Box $box): int { return count($box->items); }
function map_size(Box $box): int { return count($box->map); }
function rhs(int &$effects): int { $effects++; return 7; }
function bad_key(Box $box, int &$effects): void { $key mixed = true; $box->map[$key] = rhs($effects); }
function bad_rhs(Box $box): void { $missing ?int; $box->items[] = $missing; }
function bad_bounds(Box $box, int &$effects): void { $box->items[9] = rhs($effects); }
return 0;
PHS;
$failure_cpp = str_replace('int main(int argc, char** argv)', 'int unused_entry(int argc, char** argv)', field_write_compile($failure_source));
$failure_cpp .= <<<'CPP'

int main() {
    auto box = function_box(); scpp::int_t<> effects{0};
    try { function_badU_key(box, effects); return 1; } catch (const std::exception&) {}
    if (effects.native_value() != 0 || function_mapU_size(box).native_value() != 0) return 2;
    try { function_badU_rhs(box); return 3; } catch (const std::exception&) {}
    if (function_size(box).native_value() != 1) return 4;
    try { function_badU_bounds(box, effects); return 5; } catch (const std::exception&) {}
    if (effects.native_value() != 1 || function_size(box).native_value() != 1) return 6;
    effects = scpp::int_t<>{0};
    decltype(box) absent;
    try { function_badU_bounds(absent, effects); return 7; } catch (const std::exception&) {}
    return effects.native_value() == 0 ? 0 : 8;
}
CPP;
file_put_contents($argv[1] . '/failures.cpp', $failure_cpp);
foreach ([
    'class A { public $v vector<int> = []; } $a value<A>; $a->v[] = 1;',
    'class A { public readonly $v vector<int> = []; public function f(): void { $this->v[] = 1; } }',
    'class A { private $v vector<int> = []; } $a = new A(); $a->v[] = 1;',
    'struct A { public $v vector<int> = []; } function f(const A &$a): void { $a->v[] = 1; }',
    'class A { public $v vector<int> = []; } function make(): A { return new A(); } make()->v[] = 1;',
    'class A { public $v vector<int> = []; } $a = new A(); $a->v[] = "bad";',
    'class A { public $v vector<int> = []; } $a vector<A> = []; $a[0]->v[] = 1;',
] as $source) {
    $failed = false;
    try { field_write_compile($source); } catch (\RuntimeException $error) { $failed = true; }
    if (!$failed || !Model::$cpp_files->is_empty()) { throw new \LogicException('Invalid field write admitted: ' . $source); }
}
echo "Field container writes passed\n";
