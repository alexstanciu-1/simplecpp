<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
require_once __DIR__ . '/native_record_export.php';

function static_redeclaration_source(string $text): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/static-redeclarations', '/static-redeclarations', '/static-redeclarations');
	$file = new file(); $file->path = 'main.phs'; $file->content = $text;
	Source_Registry::add($module, $file); Model::$modules->add($module->name, $module);
	(new Compiler(new cpp_lowering_options(false)))->exec_cpp();
	return native_record_export();
}

$results = [];
foreach (['class', 'struct'] as $kind) {
	foreach ([
		'fixed' => ['public static function f(int $n = 2): int { return $n; }', 'public static function f(int $renamed = 7): int { return $renamed; }', '$a = B::f(); $b = C::f(); $c C; $d = $c::f(); return $a + $b + $d;', 16],
		'lexical' => ['protected static function f(): int { return 2; } public static function base(): int { return self::f(); }', 'public static function f(): int { return 7; } public static function child(): int { $a = self::f(); $b = parent::f(); return $a + $b; }', '$a = C::base(); $b = C::child(); return $a + $b;', 11],
		'reference' => ['public static function &pick(int &$n): int { return $n; }', 'public static function &pick(int &$value): int { return $value; }', '$n = 1; $alias =& C::pick($n); $alias = 9; return $n;', 9],
		'variadic' => ['public static function count(int ...$values): int { return 1; }', 'public static function count(int ...$values): int { $n = 0; foreach ($values as $value) { $n += $value; } return $n; }', 'return C::count(2, 3, 4);', 9],
		'private' => ['private static function f(): int { return 2; } public static function base(): int { return self::f(); }', 'private static function f(): int { return 7; } public static function child(): int { return self::f(); }', '$a = C::base(); $b = C::child(); return $a + $b;', 9],
	] as $name => [$base, $child, $program, $expected]) {
		$cpp = static_redeclaration_source($kind . ' B { ' . $base . ' } ' . $kind . ' C extends B { ' . $child . ' } ' . $program);
		if (str_contains($cpp, 'virtual ') || str_contains($cpp, ' override')) { throw new \LogicException('Static redeclaration acquired virtual lowering'); }
		if (isset($argv[1])) {
			$path = $argv[1] . '/' . $kind . '_' . $name . '.cpp'; file_put_contents($path, $cpp);
			$results[] = ['path' => $path, 'exit_code' => $expected];
		}
	}
}
foreach ([
	['public static function f(int $n): int { return $n; }', 'public static function f(string $n): int { return 1; }', 'exact compatible signature'],
	['public static function f(int $n = 2): int { return $n; }', 'public static function f(int $n): int { return $n; }', 'exact compatible signature'],
	['public static function f(int &$n): int { return $n; }', 'public static function f(int $n): int { return $n; }', 'exact compatible signature'],
	['public static function &f(int &$n): int { return $n; }', 'public static function f(int &$n): int { return $n; }', 'exact compatible signature'],
	['public static function f(int ...$n): int { return 1; }', 'public static function f(vector<int> $n): int { return 1; }', 'exact compatible signature'],
	['public static function f(): int { return 1; }', 'protected static function f(): int { return 1; }', 'visibility'],
	['protected static function f(): int { return 1; }', 'private static function f(): int { return 1; }', 'visibility'],
	['public final static function f(): int { return 1; }', 'public static function f(): int { return 1; }', 'final method'],
	['public static function f(): int { return 1; }', '#[\\Override] public static function f(): int { return 1; }', 'inherited virtual method'],
	['public static function f(): int { return 1; }', 'public function f(): int { return 1; }', 'Nonvirtual inherited'],
	['public function f(): int { return 1; }', 'public static function f(): int { return 1; }', 'Nonvirtual inherited'],
] as [$base, $child, $diagnostic]) {
	try { static_redeclaration_source('class B { ' . $base . ' } class C extends B { ' . $child . ' }'); throw new \LogicException('Invalid redeclaration accepted'); }
	catch (\RuntimeException $error) {
		if (!str_contains($error->getMessage(), $diagnostic)) { throw $error; }
	}
}
if (isset($argv[1])) { file_put_contents($argv[1] . '/executions.json', json_encode($results, JSON_PRETTY_PRINT) . "\n"); }
echo "Static redeclarations: fixed dispatch, exact contracts, visibility and finality passed\n";
