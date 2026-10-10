<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

/** Compile isolated expressions and prove retained output agrees with a no-op rerun. */
function eager_binary_compile(string $source): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/eager-binary', '/eager-binary', '/eager-binary');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	$compiler = new Compiler();
	$compiler->exec_cpp();
	$result = CPP_Probe::text(Model::$cpp_files);
	$compiler->exec_cpp();
	if ($result !== CPP_Probe::text(Model::$cpp_files)) {
		throw new \LogicException('Eager binary no-op changed output');
	}
	return $result;
}

$cases = [
'values' => <<<'PHS'
class Ledger {
    public int $debits = 10;
    public int $credits = 3;
    public function balance(): int { return $this->debits - $this->credits; }
}
function value(): int { return 2; }
function change(Ledger $v): int { $v->debits = 50; return 1; }
function step(int &$n, int $v): int { $n = $n * 10 + $v; return $v; }
function &select(int &$v): int { return $v; }
function reset(int &$v): int { $v = 20; return 1; }
$ledger = new Ledger();
$a = $ledger->balance();
$b = value() === 2;
$c = $ledger->debits + change($ledger);
$n = 0;
$d = step($n, 1) + step($n, 2) * step($n, 3);
$x = 4;
$e = select($x) + reset($x);
$s = 1;
$f = $s++ + $s;
$g = (true ? ($s = 7) : 0) + $s;
if ($a !== 7 || !$b || $c !== 11 || $d !== 7 || $n !== 123 || $e !== 5 || $f !== 3 || $g !== 14) { return 1; }
return 0;
PHS,
'families' => <<<'PHS'
function step(int &$n, int $v): int { $n = $n * 10 + $v; return $v; }
function text(int &$n, int $v): string { $n = $n * 10 + $v; return (string)$v; }
function flag(int &$n, int $v): bool { $n = $n * 10 + $v; return $v === 1; }
$n = 0; $a = step($n, 1) ** step($n, 2);
if ($n !== 12 || $a !== 1) { return 1; }
$n = 0; $b = step($n, 1) <=> step($n, 2);
if ($n !== 12 || $b !== -1) { return 2; }
$n = 0; $c = text($n, 1) . text($n, 2);
if ($n !== 12) { return 3; }
echo $c;
$n = 0; $d = step($n, 1) << step($n, 2);
if ($n !== 12 || $d !== 4) { return 4; }
$n = 0; $e = (flag($n, 1) xor flag($n, 2));
if ($n !== 12 || !$e) { return 5; }
$m mixed = 2;
$f = $m++ + $m;
$r int = $f;
if ($r !== 5) { return 6; }
return 0;
PHS,
'lazy' => <<<'PHS'
function touch(int &$n): int { $n++; return 9; }
function probe(int &$n): bool { $n++; return true; }
$n = 0;
$a = !(false && probe($n));
$b = true || probe($n);
$c = (true ? 2 : touch($n)) + 1;
$d = (3 ?: touch($n)) + 1;
$present ?int = 4;
$e = ($present ?? touch($n)) + 1;
if (!$a || !$b || $c !== 3 || $d !== 4 || $e !== 5 || $n !== 0) { return 1; }
return 0;
PHS,
];
foreach ($cases as $name => $source) {
	file_put_contents($argv[1] . '/' . $name . '.cpp', eager_binary_compile($source));
}

$failure_source = <<<'PHS'
function rhs(int &$n): int { $n++; return 1; }
function table(): mixed { return {}; }
function numeric(?int $v, int &$n): int { return (int)$v + rhs($n); }
function checked(mixed $v, int &$n): int { return (int)$v + rhs($n); }
function divide(int &$n): int { return rhs($n) / (rhs($n) - 1); }
return 0;
PHS;
$cpp = str_replace('int main(int argc, char** argv)', 'int unused_entry(int argc, char** argv)', eager_binary_compile($failure_source));
$cpp .= <<<'CPP'

int main() {
    scpp::int_t<> n{0}; scpp::nullable<scpp::int_t<>> missing;
    try { (void)function_numeric(missing, n); return 1; } catch (const std::exception&) {}
    if (n.native_value() != 0) return 2;
    try { (void)function_checked(function_table(), n); return 3; } catch (const std::exception&) {}
    if (n.native_value() != 0) return 4;
    try { (void)function_divide(n); return 5; } catch (const std::exception&) {}
    return n.native_value() == 2 ? 0 : 6;
}
CPP;
file_put_contents($argv[1] . '/failures.cpp', $cpp);
// Former effect-shape rejections now use ordinary expression/type admission.
foreach ([
	'$x = 1; return $x++ << 2;',
	'function value(): int { return 1; } return 2 + -value();',
	'function value(): int { return 1; } $result = value() + 2;',
	'function value(): int { return 1; } $result = 2 - value();',
	'function value(): int { return 1; } $result = 2 + (3 - value());',
	'function value(): int { return 2; } $result = 1 + 3 * value();',
	'function value(): int { return 1; } return value() < 2;',
	'function value(): string { return "a"; } $a = "x" . value();',
	'$x = 2; return $x++ ** 3;',
	'$x = 2; return ($x = 3) ** 2;',
	'$x = 1; return $x++ + $x;',
	'$x = 1; return 1 + (int)$x++;',
	'$x = 1; return 2 + -$x++;',
	'$x = 1; return ($x += 1) + $x;',
	'$x mixed = 7; $y = $x++ . "x";',
	'$x mixed = 7; $y = $x++ + $x;',
	'$a = 0; return ($a = 1) + 2;',
	'$a = false; return (int)(true and ($a = true)) + 1;',
	'$a = 0; return (bool)($a++) and true;',
	'$a = 0; return (bool)($a += 1) and true;',
	'function f(): bool { return true; } return true and f();',
	'$n = 0; return match (1) { 1 => ($n = 1) } + $n;',
	'$n = 0; return match ($n++) { 0 => 1 } + $n;',
	'$n = 0; return (true ? ($n = 1) : 2) + $n;',
	'function value(): int { return 1; } $a = value() + 1;',
	'function value(): int { return 1; } $a = (value()) + 1;',
] as $accepted) {
	eager_binary_compile($accepted);
}
foreach ([
	'function source(): mixed { return 1; } $y = !source() === false;',
	'function probe(): bool { return true; } $y = false == probe();',
	'$x = 1 + ($new = 2);',
] as $invalid) {
	$failed = false;
	try {
		eager_binary_compile($invalid);
	}
	catch (\RuntimeException $error) {
		$failed = true;
	}
	if (!$failed || !Model::$cpp_files->is_empty()) {
		throw new \LogicException('Invalid binary admitted: ' . $invalid);
	}
}
echo "Eager binary preparation passed\n";
