<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function import_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

function import_project(array $sources): Compiler
{
	Compiler_Lifecycle::reset();
	$module = new module('/imports', '/imports', '/imports');
	foreach ($sources as $path => $text) {
		$file = new file(); $file->path = $path; $file->content = $text;
		Source_Registry::add($module, $file);
	}
	Model::$modules->add($module->name, $module);
	$compiler = new Compiler(new cpp_lowering_options(false)); $compiler->select_entry('main', 'main.phs');
	return $compiler;
}

function import_edit(Compiler $compiler, source_record $source, string $text): void
{
	$source->file->content = $text; $source->file->changes = SYNC_CHANGED; $source->changes = change_state::changed;
	$compiler->tokenize(); $compiler->parse();
}

$library = 'namespace Lib; struct Item { int $value; } function Item(): int { return 7; } function a(): int { return 3; } function b(): int { return 4; }';
$cases = [
	'plain' => 'namespace App; use Lib\\Item as X; $x X; $x->value = X(); return $x->value;',
	'function' => 'namespace App; use function Lib\\Item as value; return value();',
	'group' => 'namespace App; use function Lib\\{a as first, b,}; $a = first(); $b = b(); return $a + $b;',
	'plain_group' => 'namespace App; use Lib\\{Item as X, b}; $x X; return X();',
	'reopened' => 'namespace App { use function Lib\\a as pick; function left(): int { return pick(); } } namespace App { use function Lib\\b as pick; $a = left(); $b = pick(); return $a + $b; }',
	'after_use' => 'namespace App; $x X; return X(); use Lib\\Item as X;',
	'repeated' => 'namespace App; use function Lib\\Item as value; use function \\Lib\\Item as value; return value();',
	'constant' => 'namespace App; use const Lib\\LIMIT as limit; return limit;',
];
$outputs = [];
foreach ($cases as $name => $text) {
	$compiler = import_project(['main.phs' => $text, 'lib.phs' => $library]);
	if ($name === 'constant') {
		$value = new integer_constant_value();
		$value->type = Language_Types::integer(Model::$language_scope); $value->decimal = '7';
		$constant = new registered_constant('LIMIT', $value);
		Model::$namespaces->register('Lib')->published_scope()->register_constant($constant);
	}
	$compiler->exec_cpp();
	$outputs['import_' . $name] = CPP_Output::encode(Model::$cpp_files);
	import_check(Model::$namespaces->find('App')->published_scope()->callables_named('value') === [], 'Import re-exported an alias');
}

foreach ([
	'namespace App; use function Missing\\value; return 7;' => 'Missing import',
	'namespace App; use const Lib\\a; return 7;' => 'wrong symbol kind',
	'namespace App; use function Lib\\a as pick; use function Lib\\b as pick; return 7;' => 'Conflicting import',
	'namespace App; use function Lib\\a as pick; function pick(): int { return 0; } return 7;' => 'conflicts with namespace',
	'namespace App { use function Lib\\a as pick; } namespace App { return pick(); }' => 'resolved function',
	'namespace App; use function Lib\\a as pick; return \\App\\pick();' => 'resolved function',
	'namespace App; use Lib\\Item as X; return X\\a();' => 'resolved function',
] as $text => $message) {
	$compiler = import_project(['main.phs' => $text, 'lib.phs' => $library]);
	$failed = false;
	try { $compiler->exec_cpp(); }
	catch (\RuntimeException $error) { $failed = str_contains($error->getMessage(), $message); }
	import_check($failed && Model::$cpp_files->is_empty(), 'Missing import rejection: ' . $text);
}

// Stable syntax, work and lookup identities survive no-op imports; retargeting affects consumers.
$text = 'namespace App; use function Lib\\a as pick; function selected(): int { return pick(); } return selected();';
$compiler = import_project(['main.phs' => $text, 'lib.phs' => $library]);
$compiler->exec_cpp();
$source = Model::sources()[0]; $lib = Model::sources()[1];
$body = $source->parsed->root->single_body();
$binding = $body->imports[0];
$function = $body->declarations[0]; $work = $function->body->work(); $version = $work->version;
import_edit($compiler, $source, $text); $compiler->prepare(true); $compiler->cpp();
import_check($body->imports[0] === $binding && $work->version === $version, 'No-op import rebuilt consumer');
import_edit($compiler, $source, str_replace('Lib\\a', 'Lib\\b', $text));
$failed = false;
try { $compiler->cpp(); } catch (\RuntimeException $error) { $failed = str_contains($error->getMessage(), 'import changes'); }
import_check($failed, 'Import-only edit allowed stale C++ emission');
$compiler->prepare(true); $compiler->cpp();
import_check($function->body->work() === $work && $work->version > $version, 'Retargeting import failed to invalidate unchanged function');
$call = $function->body->statements[0]->expression;
import_check($call->require_preparation()->target->qualified_name() === 'Lib\\b', 'Retargeting kept old selected target');

import_edit($compiler, $source, str_replace('Lib\\a', 'Later\\value', $text));
$failed = false;
try { $compiler->prepare(true); } catch (\RuntimeException $error) { $failed = true; }
import_check($failed, 'Missing retargeted import passed');
import_edit($compiler, $lib, $library . ' namespace Later; function value(): int { return 7; }');
$compiler->prepare(true); $compiler->cpp();
import_check($call->require_preparation()->target->qualified_name() === 'Later\\value', 'Target addition did not recover import');
$recovered = CPP_Output::encode(Model::$cpp_files);
$clean = import_project(['main.phs' => str_replace('Lib\\a', 'Later\\value', $text), 'lib.phs' => $library . ' namespace Later; function value(): int { return 7; }']);
$clean->exec_cpp();
import_check(CPP_Output::encode(Model::$cpp_files) === $recovered, 'Incremental imports differ from clean build');
$source = Model::sources()[0];
$with_import = str_replace('Lib\\a', 'Later\\value', $text);
import_edit($clean, $source, str_replace('use function Later\\value as pick; ', '', $with_import));
$failed = false;
try { $clean->prepare(true); } catch (\RuntimeException $error) { $failed = str_contains($error->getMessage(), 'pick'); }
import_check($failed, 'Removed import left its alias visible');
import_edit($clean, $source, $with_import); $clean->prepare(true); $clean->cpp();
import_check(CPP_Output::encode(Model::$cpp_files) === $recovered, 'Removed-import repair changed output');

$type_source = 'namespace App; use Lib\\Item as X; function copy(X $x): X { return $x; } $x X; return 7;';
$compiler = import_project(['main.phs' => $type_source, 'lib.phs' => $library . ' struct Other { int $value; }']);
$compiler->exec_cpp();
$source = Model::sources()[0];
$function = $source->parsed->root->single_body()->declarations[0];
$before = $function->require_preparation()->signature->return_type;
import_edit($compiler, $source, str_replace('Lib\\Item', 'Lib\\Other', $type_source));
$compiler->prepare(true); $compiler->cpp();
import_check(!$function->require_preparation()->signature->return_type->matches($before), 'Type alias retargeting retained old signature');

$compiler = import_project(['main.phs' => 'namespace App; use function Lib\\a as pick; return other();',
	'lib.phs' => $library, 'other.phs' => 'namespace App; function other(): int { return pick(); }']);
$failed = false;
try { $compiler->exec_cpp(); } catch (\RuntimeException $error) { $failed = str_contains($error->getMessage(), 'pick'); }
import_check($failed, 'Import leaked into another file in the same namespace');

if (isset($argv[1])) {
	foreach ($outputs as $name => $output) { file_put_contents($argv[1] . '/' . $name . '.json', $output); }
}
echo "Imports: body-local kinds, aliases, groups, validation, no-op, retargeting and recovery passed\n";
