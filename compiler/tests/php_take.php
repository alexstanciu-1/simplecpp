<?php
declare(strict_types=1);

require_once __DIR__ . '/../boot.php';
use scpp\compiler\{Cpp_Emitter, Source_Frontend, entry_target, flavor, source_file, diagnostic, diagnostic_group};

$compile = static function (string $source): string {
	$program = (new Source_Frontend())->prepare(new source_file('take', $source, flavor::php), new entry_target(31));
	return (new Cpp_Emitter())->emit($program);
};
$source = <<<'SCPP'
function hit(int &$calls): result_or_false<int> { $calls++; return 0; }
function guarded(result_or_false<int> $r): int {
    ($value, $ok) = $r;
    if (!$ok) { return -1; }
    $ok = false;
    return $value;
}
function structured(result<int> $r): int {
    ($value, $error, $ok) = $r;
    if ($error) {
        if (!$error) { return -98; }
        return $error->get_line();
    }
    if (!$error) {
        if (!$ok) { return -99; }
        return $value;
    }
    return -97;
}
function sentinel(result_or_bool<int> $r): int {
    $value int = 17;
    take($value, $ok) = $r;
    return $value;
}
function falsy(result_or_false<bool> $r): int {
    take($value, $ok) = $r;
    if (!$ok) { return 2; }
    if ($value) { return 1; }
    return 0;
}
$falsePayload result_or_false<bool> = false;
echo falsy($falsePayload);
$calls int = 0;
$ok = take($first) = hit($calls);
if (!$ok) { return 90; }
echo $first, $calls;
$miss result_or_false<int> = false_sentinel;
echo guarded($miss), guarded(hit($calls)), $calls;
$previous int = 81;
take($previous) = $miss;
echo $previous;
($fallback, $unused) = $miss;
$fallback = 6;
echo $fallback;
$t result_or_bool<int> = true_sentinel;
$f result_or_bool<int> = false_sentinel;
$payload result_or_bool<int> = 0; echo sentinel($t), sentinel($f), sentinel($payload);
if (!(take($next) = hit($calls))) { return 90; }
echo $next;
return 8;
SCPP;
$cpp = $compile($source);
foreach (['.emplace(source.value())', 'source.has_value()', 'source.error()', 'var_previous_', ' = source.value();'] as $fragment) {
	if (!str_contains($cpp, $fragment)) throw new RuntimeException('Missing take lowering: ' . $fragment);
}
$cpp = "int verify_take();\n" . str_replace('::App::scpp_entry_body,', '::verify_take,', $cpp) . <<<'CPP'

int verify_take() {
    if (App::structured_(scpp::result_int64{scpp::int64{0}}) != 0) return 91;
    if (App::structured_(scpp::result_int64{scpp::error_t{scpp::string{"failure"}, scpp::int64{37}}}) != 37) return 92;
    return App::scpp_entry_body();
}
CPP;
$prefix = 'function overwrite(bool &$ok): void { $ok = true; } $r result_or_false<int> = false_sentinel; ';
foreach ([
	'($v,$ok)=$r; echo $v;',
	'($v,$ok)=$r; $ok=true; if (!$ok) { return 8; } echo $v;',
	'($v,$ok)=$r; $alias =& $ok; $alias=true; if (!$ok) { return 8; } echo $v;',
	'function unsafe(bool &$a, bool &$b, result_or_false<int> $r): void { $a=take($v)=$r; $b=true; if ($a) { echo $v; } }',
	'($v,$ok)=$r; overwrite($ok); if (!$ok) { return 8; } echo $v;',
	'($v,$ok)=$r; if ($ok) { echo $v; } echo $v;',
	'$v float=2.0; take($v)=$r;',
	'take($v,$v)=$r;',
	'($v,$ok)=$r; if ($ok) { $ok=false; } else { $ok=true; } if ($ok) { echo $v; }',
	'($v,$ok)=$r; ($w,$ok)=$r; if ($ok) { echo $v; }',
	'take($v,$e,$ok)=$r;',
] as $invalid) {
	try {
		$compile($prefix . $invalid . 'return 8;');
	}
	catch (diagnostic|diagnostic_group $e) {
		if (!str_contains($e->getMessage(), 'take')) throw $e;
		continue;
	}
	throw new RuntimeException('Unexpected take admission: ' . $invalid);
}
file_put_contents($argv[1], json_encode(['php' => ['cpp' => $cpp, 'stdout_hex' => bin2hex('001-102816171700')]], JSON_THROW_ON_ERROR));
