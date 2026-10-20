<?php
namespace scpp\compiler;
require_once __DIR__ . '/operators.php';

// Check retained result and conversion domains independently of C++ overloads.
foreach (['2 + 0.5', '0.5 + 2', '2.0 - 1', '2 * 0.5', '3.0 / 2', '+1.5', '-1.5', 'true == false', 'true != false'] as $expression) {
	$syntax = operator_test_source('$v = ' . $expression . ';');
	$statement = $syntax->root->single_body()->body->statements[0];
	$value = $statement->expression->value;
	$decision = $value instanceof binary_expression_node
		? $value->require_binary_preparation()->decision : $value->require_unary_preparation()->decision;
	$expected = str_contains($expression, 'true') ? Language_Types::boolean(Model::$language_scope)
		: Language_Types::floating(Model::$language_scope);
	if (!$decision->result_type->matches($expected)) { throw new \LogicException('Wrong scalar result: ' . $expression); }
	foreach ($decision->operands as $operand) {
		if (!$operand->target_type->matches($expected)) { throw new \LogicException('Wrong operand domain'); }
		if ($operand->source_type->matches(Language_Types::integer(Model::$language_scope))
			&& $operand->operation !== conversion_operation::integer_to_floating) {
			throw new \LogicException('Numeric promotion was not retained');
		}
	}
}
foreach ([
	'$x float = 1;',
	'function f(float $x): float { return $x; } f(1);',
	'function f(): float { return 1; }',
	'$x = 1; $x += 2.0;',
	'$x uint8 = 1; $y = $x + 2.0;',
	'$x = 1.0 % 2.0;',
	'$x = 1.0 + true;',
	'$x = true == 1;',
] as $source) {
	$rejected = false;
	try { operator_test_source($source); } catch (\RuntimeException $error) { $rejected = true; }
	if (!$rejected) { throw new \LogicException('Deferred surface admitted: ' . $source); }
}
$source = <<<'PHS'
function eq(bool $a, bool $b): bool { return $a == $b; }
function neq(bool $a, bool $b): bool { $r bool = false; $r = $a != $b; return $r; }
function add(float $a, float $b): float { $r float = $a + $b; return $r; }
function sub(float $a, int $b): float { return $a - $b; }
function mul(int $a, float $b): float { return $a * $b; }
function divff(float $a, float $b): float { return $a / $b; }
function divif(int $a, float $b): float { return $a / $b; }
function divfi(float $a, int $b): float { return $a / $b; }
function pos(float $a): float { return +$a; }
function neg(float $a): float { return -$a; }
function left(int &$trace): int { $trace = $trace * 10 + 1; return 2; }
function right(int &$trace): float { $trace = $trace * 10 + 2; return 0.5; }
function ordered(int &$trace): float { return left($trace) + right($trace); }
function snapshot(int &$x): float { return $x++ + 0.5; }
function bool_left(int &$trace): bool { $trace = $trace * 10 + 1; return true; }
function bool_right(int &$trace): bool { $trace = $trace * 10 + 2; return false; }
function bool_ordered(int &$trace): bool { return bool_left($trace) != bool_right($trace); }
function integer_div(int $a, int $b): int { return $a / $b; }
return 0;
PHS;
operator_test_source($source);
$cpp = CPP_Probe::text(Model::$cpp_files);
(new Compiler(new cpp_lowering_options(false)))->cpp();
if ($cpp !== CPP_Probe::text(Model::$cpp_files)) { throw new \LogicException('Scalar no-op publication changed'); }
$cpp = str_replace('int main(int argc, char** argv)', 'int unused_entry(int argc, char** argv)', $cpp);
$cpp .= <<<'CPP'

#include <cmath>
#include <type_traits>
int main() {
    using F = scpp::float_t; using I = scpp::int_t<>; using B = scpp::bool_t;
    static_assert(std::is_same_v<decltype(function_add(F{}, F{})), F>);
    static_assert(std::is_same_v<decltype(function_mul(I{}, F{})), F>);
    static_assert(std::is_same_v<decltype(function_eq(B{}, B{})), B>);
    for (bool a : {false, true}) for (bool b : {false, true}) {
        if (function_eq(B{a}, B{b}).native_value() != (a == b)) return 1;
        if (function_neq(B{a}, B{b}).native_value() != (a != b)) return 2;
    }
    if (function_add(F{2.25}, F{0.5}).native_value() != 2.75) return 3;
    if (function_sub(F{2.5}, I{3}).native_value() != -0.5) return 4;
    if (function_mul(I{-3}, F{0.5}).native_value() != -1.5) return 5;
    if (function_divff(F{7.0}, F{2.0}).native_value() != 3.5) return 6;
    if (function_divif(I{7}, F{2.0}).native_value() != 3.5) return 7;
    if (function_divfi(F{7.0}, I{2}).native_value() != 3.5) return 8;
    if (!std::isinf(function_divff(F{1.0}, F{0.0}).native_value())) return 9;
    if (!std::isnan(function_divff(F{0.0}, F{0.0}).native_value())) return 10;
    if (!std::signbit(function_divff(F{1.0}, F{-0.0}).native_value())) return 11;
    if (!std::isinf(function_divif(I{1}, F{0.0}).native_value())) return 12;
    if (!std::isinf(function_divfi(F{1.0}, I{0}).native_value())) return 13;
    if (!std::signbit(function_pos(F{-0.0}).native_value())) return 14;
    if (!std::signbit(function_neg(F{0.0}).native_value())) return 15;
    I trace{0};
    if (function_ordered(trace).native_value() != 2.5 || trace.native_value() != 12) return 16;
    I x{3};
    if (function_snapshot(x).native_value() != 3.5 || x.native_value() != 4) return 17;
    trace = I{0};
    if (!function_boolU_ordered(trace).native_value() || trace.native_value() != 12) return 18;
    if (function_integerU_div(I{-7}, I{2}).native_value() != -3) return 19;
    try { (void)function_integerU_div(I{1}, I{0}); return 20; } catch (const std::exception&) {}
    return 0;
}
CPP;
// Keep the proposed IEEE contract probe available separately from existing-runtime checks.
if (!in_array('--ieee-zero', $argv, true)) {
	$begin = strpos($cpp, '    if (!std::isinf(function_divff');
	$end = strpos($cpp, '    if (!std::signbit(function_pos');
	$cpp = substr($cpp, 0, $begin) . <<<'CPP'
    try { (void)function_divff(F{1.0}, F{0.0}); return 9; } catch (const std::exception&) {}
    try { (void)function_divff(F{0.0}, F{0.0}); return 10; } catch (const std::exception&) {}
    try { (void)function_divff(F{1.0}, F{-0.0}); return 11; } catch (const std::exception&) {}
    try { (void)function_divif(I{1}, F{0.0}); return 12; } catch (const std::exception&) {}
    try { (void)function_divfi(F{1.0}, I{0}); return 13; } catch (const std::exception&) {}
CPP
	. "\n" . substr($cpp, $end);
}
file_put_contents($argv[1] . '/scalar_admission.cpp', $cpp);
echo "scalar admission OK\n";
