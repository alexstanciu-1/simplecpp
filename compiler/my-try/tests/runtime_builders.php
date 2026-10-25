<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function builder_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

/** Public provider types must not leak from unselected modules. */
$core_input = Runtime_Catalog_Loader::load(dirname(__DIR__, 3) . '/runtime/metadata/modules', ['core']);
$core_types = Type_Definition_Registration::install(new Type_Registry());
Runtime_Type_Registration::install($core_input, $core_types);
$core_calls = Runtime_Catalog_Registration::resolve($core_input, $core_types);
foreach (['string_parts_builder', 'text_builder'] as $name)
{
	builder_check($core_types->source()->type_or_null($name) === null, 'Unselected builder type leaked');
	$failed = false;
	try {
		$core_calls->function_definition('strings.' . $name . '_create');
	}
	catch (\OutOfBoundsException $error) {
		$failed = true;
	}
	builder_check($failed, 'Unselected builder callable leaked');
}

/** All fixtures use the same frontend, shared call admission and backend lowering. */
function builder_compile(string $source): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/builders', '/builders', '/builders');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	$compiler = new Compiler(new cpp_lowering_options(false));
	$compiler->exec_cpp();
	$text = CPP_Probe::text(Model::$cpp_files);
	$compiler->exec_cpp();
	builder_check($text === CPP_Probe::text(Model::$cpp_files), 'Builder no-op output changed');
	return $text;
}

$cases = [
	'parts' => <<<'PHS'
$b string_parts_builder = string_parts_builder_create();
string_parts_builder_reserve($b, 8);
$capacity = string_parts_builder_capacity($b);
if ($capacity < 8) { return 1; }
string_parts_builder_append_string($b, "hello");
string_parts_builder_append_string($b, "-");
string_parts_builder_append_int($b, 42);
string_parts_builder_append_string($b, "-");
string_parts_builder_append_bool($b, true);
string_parts_builder_append_bool($b, false);
echo string_parts_builder_count($b), "|", string_parts_builder_byte_len($b), "|", string_parts_builder_to_string($b), "|";
string_parts_builder_clear($b);
echo string_parts_builder_count($b), "|", string_parts_builder_byte_len($b), "|";
string_parts_builder_append_string($b, "again");
echo string_parts_builder_to_string($b);
return 0;
PHS,
	'text' => <<<'PHS'
$b text_builder = text_builder_create();
text_builder_reserve_bytes($b, 32);
$capacity = text_builder_capacity_bytes($b);
if ($capacity < 32) { return 1; }
text_builder_append_string($b, "hello");
text_builder_append_string($b, "-");
text_builder_append_int($b, 42);
text_builder_append_string($b, "-");
text_builder_append_bool($b, true);
text_builder_append_bool($b, false);
echo text_builder_byte_len($b), "|", text_builder_to_string($b), "|";
$snapshot = text_builder_to_string($b);
text_builder_append_string($b, "!");
echo $snapshot, "|", text_builder_take_string($b), "|", text_builder_byte_len($b), "|";
text_builder_append_string($b, "again");
echo text_builder_to_string($b), "|";
text_builder_clear($b);
echo text_builder_byte_len($b);
return 0;
PHS,
];
foreach (['string_parts_builder', 'text_builder'] as $family)
{
	$cases[$family . '_copy'] = str_replace('@B@', $family, <<<'PHS'
function inspect(const @B@ &$v): string { return @B@_to_string($v); }
function copy_add(@B@ $v): @B@ { @B@_append_string($v, "C"); return $v; }
function mutate(@B@ &$v): void { @B@_append_string($v, "R"); }
$b = @B@_create(); @B@_append_string($b, "A");
$copy = $b; @B@_append_string($copy, "B");
echo inspect($b), "|", inspect($copy), "|";
$alias =& $b; @B@_append_string($alias, "X"); mutate($alias);
$other = copy_add($b); echo inspect($b), "|", inspect($other), "|";
@B@_clear($copy); echo inspect($b), "|", @B@_byte_len(@B@_create());
return 0;
PHS);
	$cases[$family . '_bytes'] = str_replace('@B@', $family, <<<'PHS'
$b = @B@_create(); $binary string = hex2bin("00ff");
@B@_append_string($b, $binary); @B@_append_string($b, "é中😀");
const MIN = -9223372036854775808;
@B@_append_int($b, MIN);
echo @B@_byte_len($b), "|", bin2hex(@B@_to_string($b));
return 0;
PHS);
	$cases[$family . '_order'] = str_replace('@B@', $family, <<<'PHS'
function replace(@B@ &$b, int &$calls): string {
    $calls++; $b = @B@_create(); @B@_append_string($b, "after"); return "!";
}
$b = @B@_create(); @B@_append_string($b, "before"); $calls = 0;
@B@_append_string($b, replace($b, $calls)); echo @B@_to_string($b), "|", $calls;
return 0;
PHS);
}
$directory = $argv[1];
foreach ($cases as $name => $source) {
	file_put_contents($directory . '/' . $name . '.cpp', builder_compile($source));
}
foreach (['string_parts_builder' => 'reserve', 'text_builder' => 'reserve_bytes'] as $family => $reserve)
{
	$source = '$b = ' . $family . '_create(); ' . $family . '_' . $reserve . '($b, -1); return 91;';
	$text = str_replace('int main(int argc, char** argv)', 'int observed_entry(int argc = 0, char** argv = nullptr)', builder_compile($source));
	$text .= "\nint main() { try { observed_entry(); } catch (const scpp::ValueError&) { return 0; } return 92; }\n";
	file_put_contents($directory . '/' . $family . '_negative.cpp', $text);
}
foreach ([
	'text_builder_create(1);',
	'$b = text_builder_create(); text_builder_append_string($b, 1);',
	'text_builder_clear(text_builder_create());',
	'function bad(const text_builder &$b): void { text_builder_clear($b); }',
	'$b = string_parts_builder_create(); text_builder_clear($b);',
	'$b = text_builder_create(); $r result<text_builder> = $b;',
	'$b = text_builder_create(); count($b);',
	'$b = text_builder_create(); empty($b);',
	'text_builder_append_byte_span();',
] as $source)
{
	$failed = false;
	try {
		builder_compile($source);
	}
	catch (\RuntimeException $error) {
		$failed = true;
	}
	builder_check($failed && Model::$cpp_files->is_empty(), 'Unsupported builder call emitted: ' . $source);
}
builder_check(builder_compile($cases['text']) !== '', 'Builder recovery failed');
$old = Model::$type_catalog->definition('text_builder');
$metadata = Model::require_runtime_metadata();
Compiler_Lifecycle::reset_syntax();
builder_check(Model::$type_catalog->definition('text_builder') !== $old && Model::require_runtime_metadata() === $metadata,
	'Builder reset did not retain metadata with fresh type identities');
echo "Runtime builders passed\n";
