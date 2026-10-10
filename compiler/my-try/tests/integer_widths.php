<?php
namespace scpp\compiler;

require_once __DIR__ . '/operators.php';

// Reuse the operator harness; inspect facts, not only destination-converted values.
foreach ([
	['int8', 'int16', 'int16'], ['int32', 'int64', 'int64'],
	['int64', 'int32', 'int64'], ['int8', 'int8', 'int8'],
	['int', 'int64', 'int'], ['int64', 'int', 'int'],
	['uint8', 'uint16', 'uint16'], ['uint32', 'uint16', 'uint32'],
	['uint32', 'uint64', 'uint64'], ['byte', 'uint8', 'uint8'],
] as [$left, $right, $expected]) {
	$syntax = operator_test_source('$a ' . $left . ' = 8; $b ' . $right . ' = 2; $c = $a + $b; return 0;');
	$statement = $syntax->root->single_body()->body->statements[2];
	$binary = $statement->expression->value;
	$decision = $binary->require_binary_preparation()->decision;
	if (Type_Queries::canonical($decision->result_type)->definition()->name() !== $expected) {
		throw new \LogicException('Wrong integer result domain: ' . $left . ' + ' . $right);
	}
	foreach ($decision->operands as $conversion) {
		if (!$conversion->target_type->matches($decision->result_type)) {
			throw new \LogicException('Operand did not convert to the selected integer domain');
		}
	}
}

foreach (['==', '!=', '<', '<=', '>', '>=', '<=>'] as $symbol) {
	$syntax = operator_test_source('$a uint64 = 8; $b uint32 = 2; $c = $a ' . $symbol . ' $b; return 0;');
	$decision = $syntax->root->single_body()->body->statements[2]->expression->value->require_binary_preparation()->decision;
	$expected = $symbol === '<=>' ? 'int' : 'bool';
	if (Type_Queries::canonical($decision->result_type)->definition()->name() !== $expected) {
		throw new \LogicException('Comparison result inherited the operand width');
	}
}

foreach ([
	'$a uint64 = 8; $b int64 = 2; $c = $a + $b;',
	'$a int64 = 8; $b uint64 = 2; $c = $a < $b;',
	'$a uint32 = 8; $c = $a + 1;',
	'$a int32 = 8; $c = $a === $a;',
	'$a int32 = 8; $c = $a ** 2;',
	'$a int32 = 8; $c = $a << 2;',
	'$a int32 = 8; $a += 2;',
] as $invalid) {
	$failed = false;
	try {
		operator_test_source($invalid);
	}
	catch (\RuntimeException $error) {
		$failed = true;
	}
	if (!$failed || !Model::$cpp_files->is_empty()) {
		throw new \LogicException('Unsupported integer combination reached output');
	}
}

$source = <<<'PHS'
class Calendar {
    public int64 $year = 2026;
    public int32 $month = 10;
}
function step(int &$effects, int32 $value): int32 { $effects = $effects * 10 + (int)$value; return $value; }
$date = new Calendar();
if ($date->year + 1 != 2027 || $date->month < 1 || $date->year - $date->month != 2016) { return 1; }
$small int8 = 100;
$wide int16 = 200;
if ($small + $wide != 300 || $wide - $small != 100 || $wide / $small != 2 || $wide % $small != 0 || $small * $wide != 20000) { return 2; }
$unsigned uint16 = 60000;
$factor uint32 = 2;
if ($unsigned * $factor != (uint32)120000) { return 3; }
$half uint64 = 9223372036854775807;
$two uint64 = 2;
$maximum = $half * $two + (uint64)1;
$one uint32 = 1;
if ($maximum <= $half || $maximum - $one != $half * $two || $maximum / $two != $half || $maximum % $two != (uint64)1) { return 4; }
if (($one <=> $maximum) !== -1 || ($maximum <=> $maximum) !== 0 || ($maximum <=> $one) !== 1) { return 5; }
$n = 0;
$sum = step($n, 1) + step($n, 2);
if ($n !== 12 || $sum != 3) { return 6; }
return 0;
PHS;
operator_test_source($source);
$cpp = CPP_Probe::text(Model::$cpp_files);
$compiler = new Compiler();
$compiler->prepare();
$compiler->cpp();
if ($cpp !== CPP_Probe::text(Model::$cpp_files)) {
	throw new \LogicException('No-op preparation changed integer lowering');
}
file_put_contents($argv[1] . '/integer_values.cpp', $cpp);

operator_test_source('function divide(int16 $a, int32 $b): int32 { return $a / $b; } function remainder(uint16 $a, uint32 $b): uint32 { return $a % $b; } return 0;');
$cpp = str_replace('int main(int argc, char** argv)', 'int unused_entry(int argc, char** argv)', CPP_Probe::text(Model::$cpp_files));
$cpp .= <<<'CPP'

int main() {
    try { function_divide(scpp::int_t<std::int16_t>(8), scpp::int_t<std::int32_t>(0)); return 1; }
    catch (const std::exception&) {}
    try { function_remainder(scpp::int_t<std::uint16_t>(8), scpp::int_t<std::uint32_t>(0)); return 2; }
    catch (const std::exception&) {}
    return 0;
}
CPP;
file_put_contents($argv[1] . '/integer_failures.cpp', $cpp);
echo "Integer width preparation passed\n";
