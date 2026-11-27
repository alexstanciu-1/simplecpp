<?php
declare(strict_types=1);

require_once __DIR__ . '/../boot.php';
use scpp\compiler\{Cpp_Emitter, Source_Frontend, entry_target, flavor, source_file};

$source = <<<'SCPP'
function replace_slot(?int &$slot): int { echo "R"; $slot = 0; return 0; }
function change_plain(int &$slot): result<int> { echo "P"; $slot = 4; return 4; }
function fail_left(): ?int { throw new Exception("left"); }
function replace_result(result_or_false<int> &$slot): bool { echo "S"; $slot=5; return false; }
function right_plain(): int { echo "unexpected"; return 0; }
$empty ?int = null;
$value ?int = 0;
$ok result<int> = 0;
$miss result_or_false<int> = false_sentinel;
$hit result_or_false<int> = 0;
$absent result_or_bool<int> = false_sentinel;
$truth result_or_bool<int> = true_sentinel;
$present result_or_bool<int> = 0;
$plain int = 0;
$negative int = -1;
$bool_miss result_or_false<bool> = false_sentinel;
$bool_value result_or_false<bool> = false;
$flag bool = false;
SCPP;
$expected = '';
// Each pair names two states and the expected positive equality, in both operand orders.
foreach ([
	['value', 'ok', true], ['empty', 'ok', false], ['empty', 'miss', false],
	['value', 'hit', true], ['empty', 'truth', false], ['value', 'present', true],
	['ok', 'hit', true], ['ok', 'truth', false], ['miss', 'absent', true], ['miss', 'truth', false],
	['hit', 'present', true], ['miss', 'present', false], ['hit', 'absent', false],
	['plain', 'value', true], ['plain', 'empty', false], ['plain', 'ok', true],
	['plain', 'hit', true], ['plain', 'miss', false], ['plain', 'present', true],
	['plain', 'truth', false], ['negative', 'present', false],
	['flag', 'bool_value', false], ['flag', 'bool_miss', true], ['flag', 'truth', false], ['flag', 'absent', true],
] as [$left, $right, $equal]) {
	foreach ([[$left, $right], [$right, $left]] as [$a, $b]) {
		foreach (['==', '!=', '===', '!=='] as $op) {
			$source .= 'echo (int)($' . $a . " $op " . '$' . $b . ');';
			$expected .= (string)(int)($equal !== str_starts_with($op, '!'));
		}
	}
}
$source .= <<<'SCPP'
echo (int)($bool_miss === false), (int)(false === $bool_value), (int)($bool_miss == false), (int)(false == $bool_value);
echo (int)(null === $empty), (int)($value === null);
echo (int)($empty == replace_slot($empty)), (int)(0 === $empty);
echo (int)($plain === change_plain($plain)), (int)($plain === 4);
echo (int)(strpos("abc", "a") === 0), (int)(0 === strpos("abc", "x")), (int)(strpos("abc", "x") === false);
$text ?string = "a\0b";
$other result_or_bool<string> = "a\0c";
echo (int)($text !== $other), (int)("a\0b" === $text);
try { echo (int)(fail_left() === right_plain()); } catch (Exception $e) { echo $e->get_message(); }
$failed result_or_false<int> = false_sentinel;
echo (int)($failed === replace_result($failed)), (int)($failed !== false);
return 8;
SCPP;
$program = (new Source_Frontend())->prepare(new source_file('peers', $source, flavor::php), new entry_target(31));
$cpp = (new Cpp_Emitter())->emit($program);
foreach (['(var_hit_4).has_value() && ((var_hit_4).value() == var_plain_8)',
	'((var_miss_3).is_false() && (var_absent_5).is_false())',
	'const auto binary_left = var_empty_0; const auto binary_right = replace_slot_(var_empty_0);',
	'const auto binary_left = var_plain_8; const auto binary_right = change_plain_(var_plain_8);'] as $fragment) {
	if (!str_contains($cpp, $fragment)) throw new RuntimeException('Missing lowered comparison: ' . $fragment);
}
file_put_contents($argv[1], json_encode(['php' => ['cpp' => $cpp,
	'stdout_hex' => bin2hex($expected . '101010R01P0110111leftS11')]], JSON_THROW_ON_ERROR));
