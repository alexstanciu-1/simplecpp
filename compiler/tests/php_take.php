<?php
declare(strict_types=1);

require_once __DIR__ . '/../boot.php';
use scpp\compiler\{Cpp_Emitter, Source_Frontend, entry_target, flavor, source_file, diagnostic, diagnostic_group};

$compile = static function (string $source): string {
	$program = (new Source_Frontend())->prepare(new source_file('take', $source, flavor::php), new entry_target(31));
	return (new Cpp_Emitter())->emit($program);
};
$source = <<<'SCPP'
class PortFailure extends Exception { public int $port = 0; public function __construct() { $this->port = $this->get_line(); } }
function validated(PortFailure $failure): result<int> { return $failure; }
function bad_port(): result<int> { return new Exception("port"); }
function rethrow_result(result<int> $result): void {
    take($value, $error, $ok) = $result;
    if (!$ok) { throw $error; }
}
$original = new PortFailure();
try { rethrow_result(validated($original)); }
catch (PortFailure $caught) {
    if ($caught->port !== 8 || $original !== $caught || $caught->get_line() !== 8 || $caught->get_file() !== "take") { return 83; }
    $caught->port = 42;
}
if ($original->port !== 42) { return 84; }
$bad = bad_port();
($unusedPort,$problem,$valid) = $bad;
if ($valid || $problem->get_line() !== 3 || $problem->get_message() !== "port") { return 85; }
$factory = Exception::make("factory");
if ($factory->get_line() !== 18 || $factory->get_file() !== "take") { return 86; }
function length_or_error(result<string> $input): result<int> {
    return (($text, $diagnostic) = $input) ? strlen($text) : $diagnostic;
}
function forward_unchecked(result<string> $input): result<int> {
    take($text, $diagnostic, $ok) = $input;
    return $diagnostic;
}
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
function counted_position(string $s, int &$calls): result_or_false<int> {
    $calls++;
    return strpos($s, "a");
}
function position(string $s, int &$calls, int &$hits): int {
    return (take($p) = counted_position($s, $calls)) ? bumped($p, $hits) : -1;
}
function bumped(int $p, int &$hits): int { $hits++; return $p; }
function kept(result<string> $r): string {
    $text = "fallback";
    return !(($text,$error)=$r) ? $text : $text;
}
function bool_state(result_or_bool<int> $r): int {
    ($n,$ok)=$r;
    return $ok ? $n : -1;
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
foreach (['.emplace(source.value())', 'source.has_value()', 'source.error()', 'var_previous_', 'scpp::result_int64{var_diagnostic_', ' = source.value();'] as $fragment) {
	if (!str_contains($cpp, $fragment)) throw new RuntimeException('Missing take lowering: ' . $fragment);
}
$cpp = "#include <stdexcept>\n#include <string_view>\nint verify_take();\n" . str_replace('::App::scpp_entry_body,', '::verify_take,', $cpp) . <<<'CPP'

int verify_take() {
    if (App::structured_(scpp::result_int64{scpp::int64{0}}) != 0) return 91;
    if (App::structured_(scpp::result_int64{scpp::error_t{scpp::string{"failure"}, scpp::int64{37}}}) != 37) return 92;
    const scpp::result_string empty{scpp::string{}};
    if (App::length_or_error_(empty).value() != 0) return 93;
    scpp::int64 calls = 0, hits = 0;
    if (App::position_(scpp::string{"abc"}, calls, hits) != 0 ||
        App::position_(scpp::string{"zzz"}, calls, hits) != -1 || calls != 2 || hits != 1) return 97;
    if (App::bool_state_(scpp::result_or_bool_int64::from_true()) != -1 ||
        App::bool_state_(scpp::result_or_bool_int64::from_false()) != -1) return 98;
    for (const auto& message : {scpp::string{"failure"}, scpp::string{}}) {
        scpp::error_t error{message, scpp::int64{37}, scpp::string{"input.php"}};
        const scpp::result_int64 expected{error};
        if (App::kept_(scpp::result_string{error}) != scpp::string{"fallback"}) return 99;
        if (!App::length_or_error_(scpp::result_string{error}).same_error_identity(expected)) return 94;
    }
    try { (void)App::forward_unchecked_(empty); return 95; }
    catch (const std::invalid_argument& error) {
        if (std::string_view(error.what()) != "result_requires_real_error") return 96;
    }
    return App::scpp_entry_body();
}
CPP;
foreach (['int', '?int', 'result_or_false<int>', 'result_or_bool<int>'] as $destination) {
	try {
		$compile('function bad(result<string> $r): ' . $destination . ' { take($v,$e,$ok)=$r; return $e; } return 8;');
	} catch (diagnostic|diagnostic_group $e) {
		if (!str_contains($e->getMessage(), 'Exception')) throw $e;
		continue;
	}
	throw new RuntimeException('Unexpected diagnostic return to ' . $destination);
}
try {
	$compile('function ambiguous(): result<Exception> { return new Exception("bad"); } return 8;');
	throw new RuntimeException('Unexpected result<Exception> specialization');
} catch (diagnostic|diagnostic_group $e) {
	if (!str_contains($e->getMessage(), 'Unavailable runtime specialization')) throw $e;
}
$prefix = 'function overwrite(bool &$ok): void { $ok = true; } $r result_or_false<int> = false_sentinel; ';
foreach ([
	'$n=(take($v)=$r) ? 1 : $v;',
	'$n=!(take($v)=$r) ? $v : 1;',
	'$n=(take($v)=$r) ? $v : 0; echo $v;',
	'$ok=true; $skip=false && ((($v,$ok)=$r) ? true : false); if ($ok) {echo $v;}',
	'($v,$ok)=$r; overwrite($ok); $n=$ok ? $v : 0;',
	'$q ?int=null; $n=(take($v)=$q) ? $v : 0;',
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
