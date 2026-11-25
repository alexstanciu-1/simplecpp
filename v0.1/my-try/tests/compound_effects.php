<?php
namespace scpp\compiler;
require_once __DIR__ . '/compound_assignments.php';

$cases = [
'values' => <<<'PHS'
class Line { public int $amount = 7; }
function change(int &$v): int { $v = 100; return 3; }
function boxed(mixed &$v): int { $v = "changed"; return 3; }
function dynamic_change(dynamic &$v): int { $v = "changed"; return 3; }
$x = 10; $result = ($x += change($x));
if ($x !== 13 || $result !== 13) { return 1; }
$line = new Line(); $x += $line->amount;
if ($x !== 20) { return 2; }
$x = 1; $x += $x++;
if ($x !== 2) { return 3; }
$x = 1; $x += 1 + $x++;
if ($x !== 3) { return 4; }
$x = 1; $x += ($x += 1);
if ($x !== 3) { return 5; }
$a = false; $b = 0; $b += (int)(true and ($a = true));
if (!$a || $b !== 1) { return 6; }
$m mixed = 7; $m .= $m++;
$text string = $m;
if ($text !== "77") { return 7; }
$m = 10; $m += boxed($m); $number int = $m;
if ($number !== 13) { return 8; }
$d dynamic = 10; $d += dynamic_change($d); $number = $d;
if ($number !== 13) { return 9; }
return 0;
PHS,
'lifetime' => <<<'PHS'
class Box {
    public static int $last = 0;
    public int $value = 10;
    public function __destruct() { Box::$last = $this->value; }
}
function replace(Box &$box): int { $box = new Box(); return 3; }
$box = new Box();
$box->value += replace($box);
if (Box::$last !== 13 || $box->value !== 10) { return 1; }
return 0;
PHS,
];
foreach ($cases as $name => $source) {
	compound_test_source($source);
	$cpp = CPP_Probe::text(Model::$cpp_files);
	$compiler = new Compiler(new cpp_lowering_options(false)); $compiler->prepare(); $compiler->cpp();
	if ($cpp !== CPP_Probe::text(Model::$cpp_files)) { throw new \LogicException('Compound no-op changed output'); }
	file_put_contents($argv[1] . '/' . $name . '.cpp', $cpp);
}
compound_test_source(<<<'PHS'
function zero(int &$x): int { $x = 100; return 0; }
function fail(int &$x): void { $x /= zero($x); }
function change(mixed &$x): float { $x = 100; return 0.5; }
function kindfail(mixed &$x): void { $x += change($x); }
class Box { public int $value = 10; }
function effect(int &$n): int { $n++; return 1; }
function absent(Box $box, int &$n): void { $box->value += effect($n); }
return 0;
PHS);
$cpp = str_replace('int main(int argc, char** argv)', 'int unused_entry(int argc, char** argv)', CPP_Probe::text(Model::$cpp_files));
$cpp .= <<<'CPP'

int main() {
    scpp::int_t<> x{10};
    try { function_fail(x); return 1; } catch (const std::exception&) {}
    if (x.native_value() != 100) return 2;
    scpp::mixed_t m{scpp::int_t<>(10)};
    try { function_kindfail(m); return 3; } catch (const std::exception&) {}
    if (scpp::cast<scpp::int_t<>>(m).native_value() != 100) return 4;
    scpp::int_t<> n{0};
    try { function_absent({}, n); return 5; } catch (const std::exception&) {}
    return n.native_value() == 0 ? 0 : 6;
}
CPP;
file_put_contents($argv[1] . '/failures.cpp', $cpp);
echo "Compound effects passed\n";
