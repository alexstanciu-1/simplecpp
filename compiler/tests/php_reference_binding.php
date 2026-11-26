<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
use scpp\compiler\{Source_Frontend, Cpp_Emitter, entry_target, flavor, source_file, diagnostic, diagnostic_group};

$compile = static function (string $body): string {
	$entry = (new Source_Frontend())->prepare(new source_file('refs', $body, flavor::php), new entry_target(31));
	return (new Cpp_Emitter())->emit($entry);
};
$functions = <<<'SCPP'
function clear_position(?int &$position): void { $position = null; }
function bump(int &$v): void { $v += 1; }
function &select(string $tag, ?int &$slot): ?int { echo $tag; $alias =& $slot; return $alias; }
function &relay(?int &$slot): ?int { return select(slot: $slot, tag: "R"); }
function observe(const vector<int> &$view, vector<int> &$writer): int {
    $writer[] = 3;
    return count($view);
}
SCPP;
$cpp = $compile($functions . <<<'SCPP'
$p ?int = 7;
$alias =& relay($p);
clear_position($alias);
clear_position($p);
echo (int)($p === null);
$alias = 0;
echo (int)($p === 0);
$xs vector<int> = [1];
echo observe($xs, $xs);
$count int = 9;
$text = str_replace("x", "yy", "xx", $count);
echo $count, $text;
$text = str_replace("x", "z", "abc", $count);
echo $count;
foreach ($xs as &$item) { bump($item); }
echo $xs[0], $xs[1];
return 8;
SCPP);
// A named call may stage its owning string, but the result and the slot must both stay references.
foreach (['::scpp::nullable_int64& relay_(', '() -> scpp::nullable_int64&', 'return var_slot_1;',
	'clear_position_(var_p_0);', 'const ::scpp::vector_int64 &var_view_0', 'observe_(var_xs_2, var_xs_2)',
	'bump_(var_xs_2.iteration_reference(', 'scpp::string{"xx", 2}, var_count_3)'] as $fragment) {
	if (!str_contains($cpp, $fragment)) throw new RuntimeException('Missing reference lowering: ' . $fragment);
}
$invalid = [
	['$p ?int = 7; bump($p);', 'exact storage type int64, received nullable<int64>'],
	['$p int = 7; clear_position($p);', 'exact storage type nullable<int64>, received int64'],
	['$p ?int = 7; bump((int)$p);', 'stable variable storage'],
	['$p ?int = null; $s = str_replace("a","b","a",$p);', 'exact storage type int64, received nullable<int64>'],
	['$xs vector<int> = [1]; bump($xs[0]);', 'stable variable storage'],
	['function writer(const vector<int> &$v): void { observe($v, $v); }', 'Cannot write through read-only reference'],
	['function &bad(?int &$slot): ?int { $local ?int = 7; return $local; }', 'must originate in its writable parameter'],
];
foreach (['makebox()::$slot', 'static::$slot', 'parent::$slot'] as $qualified) {
	$model = 'class Box { public static int $slot = 42; } function makebox(): Box { return new Box(); } class Child extends Box { public final function &bad(int &$slot): int { ';
	$invalid[] = [$model . "return $qualified; } }", 'must originate in its writable parameter'];
	$invalid[] = [$model . "\$other =& $qualified; return \$other; } }", 'Local alias requires'];
}
foreach ($invalid as [$snippet, $reason]) {
	try {
		$compile($functions . $snippet . 'return 8;');
	} catch (diagnostic|diagnostic_group $e) {
		if (!str_contains($e->getMessage(), $reason)) throw $e;
		continue;
	}
	throw new RuntimeException('Unexpected reference admission: ' . $snippet);
}
file_put_contents($argv[1], json_encode(['php' => ['cpp' => $cpp, 'stdout_hex' => bin2hex('R1122yyyy024')]], JSON_THROW_ON_ERROR));
