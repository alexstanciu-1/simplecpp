<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

/** Compile isolated receiver scenarios and check stable no-op publication. */
function indexed_property_compile(string $source): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/indexed-properties', '/indexed-properties', '/indexed-properties');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	$compiler = new Compiler();
	$compiler->exec_cpp();
	$text = CPP_Probe::text(Model::$cpp_files);
	$compiler->exec_cpp();
	if ($text !== CPP_Probe::text(Model::$cpp_files)) {
		throw new \LogicException('Indexed property no-op changed output');
	}
	return $text;
}

$cases = [
'families' => <<<'PHS'
class Line { public int $credit = 0; }
class Entry { public $lines vector<Line> = []; public $map hash<Line> = {}; }
$entry = new Entry(); $line = new Line();
$entry->lines[] = $line; $entry->map["a"] = $line;
$entry->lines[0]->credit = 100;
if ($line->credit !== 100) { return 1; }
$entry->map["a"]->credit = 200;
if ($entry->lines[0]->credit !== 200) { return 2; }
$result = $entry->lines[0]->credit = 300;
if ($result !== 300 || $line->credit !== 300) { return 3; }
return 0;
PHS,
'lifetime' => <<<'PHS'
class Line {
    public static int $last = -1;
    public int $credit = 0;
    public function __destruct() { Line::$last = $this->credit; }
}
class Entry { public $lines vector<Line> = []; }
function make(): Entry { $e = new Entry(); $e->lines[] = new Line(); return $e; }
function key(Entry &$e, int &$n): int { $e = make(); $n = $n * 10 + 1; return 0; }
function rhs(Entry &$e, int &$n): int { $e->lines = []; $n = $n * 10 + 2; return 17; }
$e = make(); $n = 0;
$e->lines[key($e, $n)]->credit = rhs($e, $n);
if ($n !== 12 || Line::$last !== 17) { return 1; }
return 0;
PHS,
];
foreach ($cases as $name => $source) {
	file_put_contents($argv[1] . '/' . $name . '.cpp', indexed_property_compile($source));
}
$source = <<<'PHS'
class Line { public int $credit = 0; }
class Entry { public $lines vector<Line> = []; }
function make(): Entry { return new Entry(); }
function rhs(int &$n): int { $n++; return 9; }
function write(Entry $e, int &$n): void { $e->lines[0]->credit = rhs($n); }
function absent(Entry $e): void { $nil Line; $e->lines[] = $nil; }
return 0;
PHS;
$cpp = str_replace('int main(int argc, char** argv)', 'int unused_entry(int argc, char** argv)', indexed_property_compile($source));
$cpp .= <<<'CPP'

int main() {
    auto e = function_make(); scpp::int_t<> n{0};
    try { function_write(e, n); return 1; } catch (const std::exception&) {}
    if (n.native_value() != 0) return 2;
    function_absent(e);
    try { function_write(e, n); return 3; } catch (const std::exception&) {}
    return n.native_value() == 0 ? 0 : 4;
}
CPP;
file_put_contents($argv[1] . '/failures.cpp', $cpp);
foreach ([
	'struct Line { public int $credit; } $v vector<Line> = []; $v[0]->credit = 1;',
	'class Line { private int $credit; } $v vector<Line> = []; $v[0]->credit = 1;',
	'class Line { public int $credit; } $v vector<Line> = []; $v[0]->credit += 1;',
	'class Line { public int $credit; } $v vector<Line> = []; $a =& $v[0]->credit;',
	'class Line { public int $credit; } $v vector<Line> = []; $v[0]->credit = "bad";',
	'class Line { public int $credit; } $v vector<Line> = []; $v[0]->credit = $new = 1;',
] as $invalid) {
	$failed = false;
	try { indexed_property_compile($invalid); }
	catch (\RuntimeException $error) { $failed = true; }
	if (!$failed || !Model::$cpp_files->is_empty()) {
		throw new \LogicException('Invalid indexed property admitted: ' . $invalid);
	}
}
echo "Indexed property assignments passed\n";
