<?php
declare(strict_types=1);

require_once __DIR__ . '/../boot.php';
use scpp\compiler\{Cpp_Emitter, Source_Frontend, entry_target, flavor, source_file, diagnostic, diagnostic_group};

$compile = static function (string $source): string {
	$program = (new Source_Frontend())->prepare(new source_file('operators', $source, flavor::php), new entry_target(31));
	return (new Cpp_Emitter())->emit($program);
};
$source = <<<'SCPP'
function skipped(): bool { echo "unexpected"; return true; }
function observed(): bool { echo "R"; return false; }
$i ?int = 7;
$j result<int> = 2;
$s result_or_false<string> = "a\0b";
$b result_or_bool<bool> = true_sentinel;
$f ?float = 3.0;
$byte byte = 7;
$n ?string = null;
$empty ?bool = null;
$miss result_or_false<int> = false_sentinel;
$false_text result_or_bool<string> = false_sentinel;
$true_int result_or_bool<int> = true_sentinel;
SCPP;
$expected = [];
foreach ([
	'$byte == $i' => 1, '$i == $byte' => 1, '$byte === $i' => 0, '$byte == $miss' => 0,
	'$i+$j' => 9, '$i-$j' => 5, '$i*$f' => 21, '$i/$j' => 3, '$i%$j' => 1, '$i**$j' => 49,
	'+$i' => 7, '-$j' => -2, '~$i' => -8, '$i & $j' => 2, '$i | $j' => 7, '$i ^ $j' => 5,
	'$i << $j' => 28, '$i >> $j' => 1, '$i < $f' => 0, '$i <= $j' => 0, '$i > $f' => 1,
	'$i >= $j' => 1, '$j <=> $f' => -1, '$s <=> "a\0c"' => -1,
	'$i === $s' => 0, '$s === $b' => 0, '$b === $true_int' => 1, '$true_int !== $b' => 0,
	'$miss === $false_text' => 1, '$false_text === $miss' => 1, '$b === true' => 0,
	'$empty === $n' => 1, '$n !== $empty' => 0, '$miss === null' => 0, 'null !== $miss' => 1,
	'!$b' => 0, '$miss && skipped()' => 0, '$b || skipped()' => 1, '($b xor $empty)' => 1,
	'$empty ? 1 : 2' => 2, 'isset($b)' => 1, '$i ?? 9' => 7,
] as $expression => $answer) {
	$source .= 'echo (int)(' . $expression . '), "|";';
	$expected[] = $answer;
}
$source .= <<<'SCPP'
echo $s . "!", (int)($b xor observed());
$n ??= "fallback";
echo $n ?? "unexpected";
return 8;
SCPP;
$cpp = $compile($source);
$fixtures = ['php' => ['cpp' => $cpp, 'stdout_hex' => bin2hex(implode('|', $expected) . "|a\0b!R1fallback")]];
// Value-only operators must reach checked extraction on missing input.
foreach (['arithmetic' => '$m + 2', 'bitwise' => '~$m', 'ordering' => '$m < 1', 'spaceship' => '$m <=> 2.0'] as $case => $expr) {
	$fixtures[$case] = ['cpp' => $compile('$m result_or_bool<int> = true_sentinel; return (int)(' . $expr . ');'),
		'stdout_hex' => '', 'catch_runtime_error' => 'carrier_no_value'];
}
$fixtures['concat'] = ['cpp' => $compile('$s ?string = null; echo $s . "!"; return 8;'),
	'stdout_hex' => '', 'catch_runtime_error' => 'carrier_no_value'];
foreach (['$b == $i', '$s != $f', '$i % $f', '$s & $i'] as $expr) {
	try {
		$compile('$b ?bool = null; $i ?int = null; $s ?string = null; $f ?float = null; return (int)(' . $expr . ');');
	} catch (diagnostic|diagnostic_group $e) {
		if (!str_contains($e->getMessage(), 'Unsupported')) throw $e;
		continue;
	}
	throw new RuntimeException('Unexpected admission of ' . $expr);
}
file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR));
