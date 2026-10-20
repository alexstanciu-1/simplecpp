<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function source_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

/** Compile through the ordinary pipeline and check retained no-op output. */
function source_compile(string $source): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/source', '/source', '/source');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	$compiler = new Compiler(new cpp_lowering_options(false));
	$compiler->exec_cpp();
	$text = CPP_Probe::text(Model::$cpp_files);
	$compiler->exec_cpp();
	source_check($text === CPP_Probe::text(Model::$cpp_files), 'Source no-op output changed');
	return $text;
}

$root = dirname(__DIR__, 3) . '/runtime/metadata/modules';
if (($argv[2] ?? '') === '--unselected')
{
	$failed = false;
	try {
		source_compile('source_buffer_empty();');
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), 'source_buffer_empty');
	}
	source_check($failed && Model::$cpp_files->is_empty(), 'Unselected source call emitted');
	echo "Unselected source rejected\n";
	exit(0);
}
$core = Type_Definition_Registration::install(new Type_Registry());
Runtime_Type_Registration::install(Runtime_Catalog_Loader::load($root, ['core']), $core);
source_check($core->source()->type_or_null('source_buffer') === null, 'Unselected source type leaked');
Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load($root, ['source', 'strings']));
$cases = [
	'shadow' => <<<'PHS'
function source_buffer_empty(): int { return 42; }
echo source_buffer_empty();
return 0;
PHS,
	'buffer' => <<<'PHS'
$text = "abc";
$buffer source_buffer = source_buffer_take($text);
$copy = $buffer;
$slice = source_buffer_slice($buffer, 1, 2);
echo strlen($text), "|", source_buffer_byte_len($buffer), "|", source_buffer_byte_at($buffer, 0), "|";
$alias =& $buffer;
echo source_buffer_release($alias), "|", source_buffer_byte_len($buffer), "|", source_buffer_release($copy), "|", $slice, "|";
echo source_buffer_release($buffer), source_buffer_byte_len(source_buffer_empty());
return 0;
PHS,
	'index' => <<<'PHS'
function make_index(): source_line_index {
    $text = "a\r\nbc\rd\né";
    $buffer = source_buffer_take($text);
    $index = source_line_index_build($buffer);
    source_buffer_release($buffer);
    return $index;
}
$index = make_index();
$copy = $index;
$location = source_line_index_offset_to_location($copy, 10);
echo source_line_index_line_count($index), "|", source_location_offset($location), "|", source_location_line($location), "|", source_location_column($location), "|";
echo source_line_index_line_column_to_offset($index, 4, 3), "|";
$empty = source_line_index_build(source_buffer_empty());
$origin = source_line_index_offset_to_location($empty, 0);
echo source_line_index_line_count($empty), "|", source_location_line($origin), "|", source_location_column($origin);
return 0;
PHS,
	'bytes' => <<<'PHS'
$text string = hex2bin("00ff");
$buffer = source_buffer_take($text);
$byte uint8 = source_buffer_byte_at($buffer, 1);
$length uint32 = source_buffer_byte_len($buffer);
echo $length, "|", $byte, "|", bin2hex(source_buffer_slice($buffer, 0, 2));
return 0;
PHS,
];
$directory = $argv[1];
foreach ($cases as $name => $source) {
	file_put_contents($directory . '/' . $name . '.cpp', source_compile($source));
}
$failures = [
	'negative' => 'source_buffer_byte_at($buffer, -1);',
	'wide' => 'source_buffer_slice($buffer, 0, 4294967296);',
	'past_end' => 'source_buffer_byte_at($buffer, 2);',
	'overflow' => 'source_line_index_line_column_to_offset($index, 2, 4294967295);',
	'zero_line' => 'source_line_index_line_column_to_offset($index, 0, 1);',
];
foreach ($failures as $name => $operation)
{
	$source = '$text = "a\n"; $buffer = source_buffer_take($text); $index = source_line_index_build($buffer); ' . $operation . ' return 91;';
	$text = str_replace('int main(int argc, char** argv)', "// SCPP-PROBE-OBSERVE-ENTRY\nint observed_entry(int argc = 0, char** argv = nullptr)", source_compile($source));
	$text .= "\nint main() { try { observed_entry(); } catch (const scpp::runtime_error&) { return 0; } return 92; }\n";
	file_put_contents($directory . '/' . $name . '.cpp', $text);
}
foreach ([
	'source_buffer_take("temporary");',
	'source_buffer_release(source_buffer_empty());',
	'function bad(const source_buffer &$b): void { source_buffer_release($b); }',
	'$b = source_buffer_empty(); source_buffer_byte_at($b, "0");',
	'$b = source_buffer_empty(); source_buffer_span($b, 0, 0);',
	'$b = source_buffer_empty(); count($b);',
] as $source)
{
	$failed = false;
	try {
		source_compile($source);
	}
	catch (\RuntimeException $error) {
		$failed = true;
	}
	source_check($failed && Model::$cpp_files->is_empty(), 'Invalid source call emitted: ' . $source);
}
source_check(source_compile($cases['index']) !== '', 'Source recovery failed');
$old = Model::$type_catalog->definition('source_buffer');
$metadata = Model::$runtime_metadata;
Compiler_Lifecycle::reset_syntax();
source_check((Model::$type_catalog->definition('source_buffer') !== $old) && (Model::$runtime_metadata === $metadata),
	'Source reset lost retained metadata or reused type identity');
echo "Runtime source passed\n";
