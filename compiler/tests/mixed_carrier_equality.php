<?php
declare(strict_types=1);

require_once __DIR__ . '/../boot.php';
use scpp\compiler\{Cpp_Emitter, Source_Frontend, entry_target, flavor, source_file};

$source = <<<'SCPP'
function ordinary(?int &$i, ?float &$f): bool { return $i == $f; }
function strict(?float &$f, ?int &$i): bool { return $f === $i; }
function replace(?int &$i): ?float { echo "R"; $i = 7; return null; }
function fail(): ?int { throw new Exception("left"); }
function observed_float(): float { echo "F"; return 7.0; }
function right(): float { echo "unexpected"; return 1.0; }
$i ?int = null;
$f ?float = null;
$big result<int> = 9007199254740993;
$rounded ?float = 9007199254740992.0;
$plain_float float = 9007199254740992.0;
$plain_int int = 9007199254740993;
$next ?float = 9007199254740994.0;
$zero result_or_bool<int> = 0;
$negative_zero ?float = -0.0;
$miss result_or_false<int> = false_sentinel;
$truth result_or_bool<int> = true_sentinel;
$hit result_or_false<int> = 0;
SCPP;
$expected = '';
foreach ([
	['i', 'f', true, true], ['i', 'rounded', false, false], ['plain_int', 'f', false, false],
	['big', 'rounded', true, false], ['big', 'plain_float', true, false], ['plain_int', 'rounded', true, false],
	['big', 'next', false, false], ['zero', 'negative_zero', true, false], ['hit', 'negative_zero', true, false],
	['miss', 'f', false, false], ['truth', 'f', false, false], ['truth', 'plain_float', false, false],
] as [$integer, $floating, $equal, $identical]) {
	foreach ([[$integer, $floating], [$floating, $integer]] as [$a, $b]) {
		foreach (['==' => $equal, '!=' => !$equal, '===' => $identical, '!==' => !$identical] as $op => $answer) {
			$source .= 'echo (int)($' . $a . " $op " . '$' . $b . ');';
			$expected .= (int)$answer;
		}
	}
}
$source .= <<<'SCPP'
echo (int)($i === replace($i)), (int)($i == 7.0);
$i = null;
echo (int)($i == replace($i));
echo (int)($i === observed_float());
try { echo (int)(fail() === right()); } catch (Exception $e) { echo $e->get_message(); }
return 8;
SCPP;
$program = (new Source_Frontend())->prepare(new source_file('mixed_peers', $source, flavor::php), new entry_target(31));
$cpp = (new Cpp_Emitter())->emit($program);
foreach (['(var_i_0).has_value() ? ((var_f_1).has_value() && (static_cast<scpp::float64>((var_i_0).value()) == (var_f_1).value()))',
	'(!(var_f_0).has_value() && !(var_i_1).has_value())',
	'const auto binary_left = var_i_0; const auto binary_right = replace_(var_i_0);'] as $fragment) {
	if (!str_contains($cpp, $fragment)) throw new RuntimeException('Missing lowered comparison: ' . $fragment);
}
file_put_contents($argv[1], json_encode(['php' => ['cpp' => $cpp,
	'stdout_hex' => bin2hex($expected . 'R11R1F0left')]], JSON_THROW_ON_ERROR));
