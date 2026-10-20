<?php
namespace scpp\compiler;
require_once __DIR__ . '/operators.php';
require_once __DIR__ . '/mutations.php';

function float_operator_check(bool $ok, string $message): void
{
	if (!$ok) { throw new \LogicException($message); }
}
$comparisons = ['eq' => '==', 'ne' => '!=', 'lt' => '<', 'le' => '<=', 'gt' => '>', 'ge' => '>='];
$mutations = ['preinc' => '++$x', 'postinc' => '$x++', 'predec' => '--$x', 'postdec' => '$x--'];
$program = '';
foreach ($comparisons as $name => $operator) {
	$syntax = operator_test_source('$x = 1.5 ' . $operator . ' 2.5;');
	$value = $syntax->root->single_body()->body->statements[0]->expression->value;
	$decision = $value->require_binary_preparation()->decision;
	float_operator_check($decision->result_type->matches(Language_Types::boolean(Model::$language_scope)), 'Comparison result must be bool');
	foreach ($decision->operands as $operand) {
		float_operator_check($operand->target_type->matches(Language_Types::floating(Model::$language_scope))
			&& $operand->operation === conversion_operation::identity, 'Comparison must not promote operands');
	}
	Preparation_Cleanup::tree($syntax->root);
	float_operator_check($value->preparation() === null, 'Comparison cleanup retained facts');
	$program .= 'function ' . $name . '(float $a, float $b): bool { return $a ' . $operator . ' $b; } ';
	$program .= 'function ordered' . $name . '(int &$trace): bool { return left($trace) ' . $operator . ' right($trace); } ';
}
foreach ($mutations as $name => $expression) {
	$syntax = mutation_test_source('$x = 1.5; $r = ' . $expression . ';');
	$value = $syntax->root->single_body()->body->statements[1]->expression->value;
	$facts = $value->require_mutation_preparation();
	float_operator_check($facts->type->matches(Language_Types::floating(Model::$language_scope))
		&& !$facts->addressable() && $value->target->require_preparation()->addressable(), 'Mutation must return a float snapshot');
	Preparation_Cleanup::tree($syntax->root);
	float_operator_check($value->preparation() === null && $value->target->preparation() === null, 'Mutation cleanup retained facts');
	$program .= 'function ' . $name . '(float &$x): float { return ' . $expression . '; } ';
	$program .= 'function local' . $name . '(float $input, float &$final): float { $x = $input; $r = ' . $expression . '; $final = $x; return $r; } ';
}
foreach ([
	'$x = 1.5 === 1.5;', '$x = 1.5 !== 1.5;', '$x = 1.5 <=> true;',
	'$x = true == 1.5;',
	'function f(const float &$x): void { $x++; }',
	'function f(float &$x): void {} $x = 1.5; f(++$x);',
	'function f(float &$x): void {} $x = 1.5; f($x--);',
	'$x = 1.5; (++$x) = 2.5;', '$x = 1.5; ($x++)++;',
	'class C { private float $x; } $c = new C(); $c->x++;',
	'class C { float $x; } function f(const C &$c): void { ++$c->x; }',
	'$v vector<float> = [1.5]; $v[0]++;',
] as $source) {
	$failed = false;
	try { operator_test_source($source); } catch (\RuntimeException $error) { $failed = true; }
	float_operator_check($failed && Model::$cpp_files->is_empty(), 'Excluded or non-writable source accepted: ' . $source);
}
// Failure/retry/repair must not retain stale operator facts or published artifacts.
foreach ([
	['function compare(float $a, float $b): bool { return $a < $b; }',
	 'function compare(float $a, bool $b): bool { return $a < $b; }',
	 'function compare(float $a, float $b): bool { return $a >= $b; }'],
	['function shift(float &$x): float { return ++$x; }',
	 'function shift(const float &$x): float { return ++$x; }',
	 'function shift(float &$x): float { return $x--; }'],
] as [$valid, $invalid, $repaired]) {
	$directory = sys_get_temp_dir() . '/scpp_float_' . bin2hex(random_bytes(6));
	mkdir($directory);
	$path = $directory . '/main.phs';
	try {
		file_put_contents($path, $valid);
		Compiler_Lifecycle::reset();
		$compiler = new Compiler();
		$compiler->init([$directory]);
		$compiler->exec_cpp();
		file_put_contents($path, $invalid);
		foreach ([[$path], []] as $changes) {
			$failed = false;
			try { $compiler->update_cpp($changes); } catch (\RuntimeException $error) { $failed = true; }
			float_operator_check($failed && Model::$cpp_files->is_empty()
				&& Model::$prepared_files->is_empty(), 'Failed/repeated preparation retained artifacts');
		}
		file_put_contents($path, $repaired);
		$compiler->update_cpp([$path]);
		$incremental = CPP_Probe::text(Model::$cpp_files);
		$compiler->update_cpp([]);
		float_operator_check($incremental === CPP_Probe::text(Model::$cpp_files), 'No-op repair output changed');
		Compiler_Lifecycle::reset();
		$compiler->init([$directory]);
		$compiler->exec_cpp();
		float_operator_check($incremental === CPP_Probe::text(Model::$cpp_files), 'Repaired float output differs from fresh');
	}
	finally { unlink($path); rmdir($directory); }
}
$program .= <<<'PHS'
function left(int &$trace): float { $trace = $trace * 10 + 1; return 1.5; }
function right(int &$trace): float { $trace = $trace * 10 + 2; return 2.5; }
function snapshot(float &$x): bool { return $x++ < $x++; }
class C { float $x = 1.5; static float $total = 1.5; }
struct S { float $x = 1.5; }
function classvalues(float &$a, float &$b, float &$c, float &$d): float {
 $v = new C(); $a = ++$v->x; $b = $v->x++; $c = --$v->x; $d = $v->x--; return $v->x;
}
function structvalues(float &$a, float &$b, float &$c, float &$d): float {
 $v = new S(); $a = ++$v->x; $b = $v->x++; $c = --$v->x; $d = $v->x--; return $v->x;
}
function produce(int &$calls): C { $calls++; return new C(); }
function staticvalues(int &$calls, float &$a, float &$b, float &$c, float &$d): float {
 C::$total = 1.5;
 $a = ++produce($calls)::$total; $b = produce($calls)::$total++;
 $c = --produce($calls)::$total; $d = produce($calls)::$total--; return C::$total;
}
PHS;
operator_test_source($program);
$cpp = str_replace('int main(int argc, char** argv)', 'int unused_entry(int argc, char** argv)', CPP_Probe::text(Model::$cpp_files));
$cpp .= "\n#include <cmath>\n#include <limits>\n#include <type_traits>\nint main() {\nusing F = scpp::float_t; using I = scpp::int_t<>; using B = scpp::bool_t;\n";
foreach ($comparisons as $name => $operator) {
	$cpp .= 'static_assert(std::is_same_v<decltype(function_' . $name . '(F{}, F{})), B>);' . "\n";
	$cpp .= 'for (double a : {0.0, -0.0, 1.5, -2.5, std::numeric_limits<double>::infinity(), -std::numeric_limits<double>::infinity(), std::numeric_limits<double>::quiet_NaN()}) for (double b : {0.0, -0.0, 1.5, -2.5, std::numeric_limits<double>::infinity(), -std::numeric_limits<double>::infinity(), std::numeric_limits<double>::quiet_NaN()}) { if (function_' . $name . '(F{a}, F{b}).native_value() != (a ' . $operator . ' b)) return 1; }' . "\n";
	$cpp .= '{ I trace{0}; if (function_ordered' . $name . '(trace).native_value() != (1.5 ' . $operator . ' 2.5) || trace.native_value() != 12) return 2; }' . "\n";
}
foreach ($mutations as $name => $expression) {
	$increment = str_contains($name, 'inc'); $prefix = str_starts_with($name, 'pre');
	$delta = $increment ? '1.0' : '-1.0';
	$cpp .= 'static_assert(std::is_same_v<decltype(function_' . $name . '(std::declval<F&>())), F>);' . "\n";
	foreach (['function_' . $name . '(v)', 'function_local' . $name . '(F{input}, v)'] as $call) {
		$cpp .= 'for (double input : {0.0, -0.0, 1.5, -2.5, std::numeric_limits<double>::infinity(), -std::numeric_limits<double>::infinity(), std::numeric_limits<double>::quiet_NaN()}) { F v{input}; auto r = ' . $call . '; double stored = input + ' . $delta . '; double result = ' . ($prefix ? 'stored' : 'input') . '; if (std::isnan(input)) { if (!std::isnan(v.native_value()) || !std::isnan(r.native_value())) return 3; } else { if (v.native_value() != stored || r.native_value() != result || std::signbit(r.native_value()) != std::signbit(result)) return 4; } }' . "\n";
	}
}
$cpp .= <<<'CPP'
{ F x{1.5}; if (!function_snapshot(x).native_value() || x.native_value() != 3.5) return 5; }
for (int kind : {0, 1, 2}) {
 F a{}, b{}, c{}, d{}; I calls{0};
 F final = kind == 0 ? function_classvalues(a,b,c,d) : kind == 1 ? function_structvalues(a,b,c,d) : function_staticvalues(calls,a,b,c,d);
 if (a.native_value()!=2.5 || b.native_value()!=2.5 || c.native_value()!=2.5 || d.native_value()!=2.5 || final.native_value()!=1.5) return 6;
 if (kind == 2 && calls.native_value()!=4) return 7;
}
return 0;
}
CPP;
if (isset($argv[1])) { file_put_contents($argv[1] . '/float_operators.cpp', $cpp); }
echo "Float operators: ten forms, exact domains, snapshot facts and exclusions passed\n";
