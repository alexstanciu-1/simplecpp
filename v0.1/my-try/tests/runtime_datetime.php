<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function datetime_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

function datetime_compile(string $source): void
{
	Compiler_Lifecycle::reset();
	$module = new module('/datetime', '/datetime', '/datetime');
	$file = new file(); $file->path = 'main.phs'; $file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	(new Compiler(new cpp_lowering_options(false)))->exec_cpp();
}

$cases = [
	'utc' => <<<'PHS'
echo dt_format_iso_utc(0), "|";
$seconds int = 99; $problem error;
if (take($seconds, $problem) = dt_parse_iso_utc("1970-01-01T00:00:00Z")) { echo $seconds; } else { return 1; }
if ($problem) { return 2; }
if (take($seconds, $problem) = dt_parse_iso_utc("2024-02-29T12:34:56Z")) { echo "|", dt_format_iso_utc($seconds); } else { return 3; }
return 0;
PHS,
	'local' => <<<'PHS'
$seconds int = 0; $problem error;
foreach (vector<string>::["2024-02-29", "2024-02-29 12:34:56", "2024-02-29T12:34:56", "2024-02-29T12:34:56Z"] as $text) {
    if (take($seconds, $problem) = dt_parse($text)) { echo dt_format("Y-m-d H:i:s", $seconds), "|"; } else { return 1; }
}
echo dt_format("Y y m n d j H G i s U", 0), "|", dt_format_now("!");
return 0;
PHS,
	'errors' => <<<'PHS'
$seconds int = 7; $problem error;
foreach (vector<string>::["", "2023-02-29T00:00:00Z", "2024-13-01T00:00:00Z", "2024-01-00T00:00:00Z", "2024-01-01T24:00:00Z", "2024-01-01T00:60:00Z", "2024-01-01T00:00:60Z", "2024-01-01 00:00:00Z", "2024-01-01T00:00:00Zx"] as $text) {
    if (take($seconds, $problem) = dt_parse_iso_utc($text)) { return 1; }
    if (!$problem) { return 2; }
}
foreach (vector<string>::["next Tuesday", "2024-02-30", "2024-02-29 25:00:00", ""] as $text) {
    if (take($seconds, $problem) = dt_parse($text)) { return 3; }
    if (!$problem) { return 4; }
}
return 0;
PHS,
	'clocks' => <<<'PHS'
$wall = dt_now(); $wall_ms = dt_now_ms();
if ($wall <= 0 || $wall_ms <= 0) { return 1; }
$before = dt_monotonic_ms(); $before_us uint64 = dt_monotonic_us(); $before_ns uint64 = dt_monotonic_ns();
dt_sleep_ms(-1); dt_sleep_ms(0); dt_sleep_ms(1);
$after = dt_monotonic_ms(); $after_us uint64 = dt_monotonic_us(); $after_ns uint64 = dt_monotonic_ns();
if ($after < $before) { return 2; }
echo $before_us, "|", $after_us, "|", $before_ns, "|", $after_ns;
return 0;
PHS,
	'order' => <<<'PHS'
function timestamp(string &$format, int &$calls): int { $calls++; $format = "!"; return 0; }
$format = "Y"; $calls = 0;
$text = dt_format($format, timestamp($format, $calls));
echo $text, "|", $format, "|", $calls;
return 0;
PHS,
	'shadow' => 'function dt_now(int $value): int { return $value; } return dt_now(0);',
];
foreach ($cases as $name => $source) {
	datetime_compile($source);
	file_put_contents($argv[1] . '/' . $name . '.cpp', CPP_Probe::text(Model::$cpp_files));
}

foreach (['dt_now(1);', 'dt_now_ms(1);', 'dt_monotonic_ms(1);', 'dt_monotonic_us(1);', 'dt_monotonic_ns(1);',
	'dt_sleep_ms();', 'dt_format_iso_utc("0");', 'dt_parse_iso_utc(vector<int>::[]);',
	'dt_format("Y");', 'dt_format_now();', 'dt_parse("x", 0);',
	'time();', 'date("Y");', 'strtotime("2024-01-01");'] as $source) {
	$failed = false;
	try { datetime_compile($source); }
	catch (\RuntimeException $error) { $failed = true; }
	datetime_check($failed && Model::$cpp_files->is_empty(), 'Invalid/legacy datetime call emitted: ' . $source);
}

$root = dirname(__DIR__, 3) . '/runtime/metadata/modules';
$selected = Runtime_Catalog_Loader::load($root, ['datetime', 'core', 'datetime']);
datetime_check(count($selected->modules) === 2, 'Datetime dependency deduplication failed');
$types = Type_Definition_Registration::install(new Type_Registry());
$core = Runtime_Catalog_Loader::load($root, ['core']);
Runtime_Type_Registration::install($core, $types);
$catalog = Runtime_Catalog_Registration::resolve($core, $types);
$failed = false;
try { $catalog->function_definition('datetime.dt_now'); }
catch (\Exception $error) { $failed = true; }
datetime_check($failed, 'Unselected datetime module leaked');

Compiler_Lifecycle::reset();
$original = Model::$runtime_callables->function_definition('datetime.dt_monotonic_ns');
$metadata = Model::require_runtime_metadata();
Compiler_Lifecycle::reset_syntax();
datetime_check($original !== Model::$runtime_callables->function_definition('datetime.dt_monotonic_ns'), 'Stale datetime identity');
datetime_check($metadata === Model::require_runtime_metadata(), 'Metadata was not retained across reset');
echo "Runtime datetime passed\n";
