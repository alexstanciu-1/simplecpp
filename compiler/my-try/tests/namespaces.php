<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function namespace_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

/** In-memory project still runs the coordinator's synchronized parser publication path. */
function namespace_project(array $sources): Compiler
{
	Compiler_Lifecycle::reset();
	$module = new module('/namespaces', '/namespaces', '/namespaces');
	foreach ($sources as $path => $text) {
		$file = new file();
		$file->path = $path;
		$file->content = $text;
		Source_Registry::add($module, $file);
	}
	Model::$modules->add($module->name, $module);
	$compiler = new Compiler();
	$compiler->select_entry('main', 'main.phs');
	return $compiler;
}

function namespace_edit(Compiler $compiler, source_record $record, string $text): void
{
	$record->file->content = $text;
	$record->file->changes = SYNC_CHANGED;
	$record->changes = change_state::changed;
	$compiler->tokenize();
	$compiler->parse();
}

$cases = [
	'semicolon' => ['main.phs' => 'namespace A; function value(): int { return 7; } return value();'],
	'braced' => ['main.phs' => 'namespace A { function value(): int { return 7; } return value(); }'],
	'cross_file' => ['main.phs' => 'namespace A; $box Box; $box->value = value(); return $box->value;',
		'lib.phs' => 'namespace A; struct Box { int $value; } function value(): int { return 7; }',
		'other.phs' => 'namespace B { struct Box { string $value; } function value(): int { return 11; } }'],
	'reopened' => ['main.phs' => 'namespace A { function value(): int { return 7; } } namespace A { return value(); }'],
	'nested_path' => ['main.phs' => 'namespace A\B { function value(): int { return 7; } return value(); }'],
	'global_block' => ['main.phs' => 'namespace A {} namespace { return 7; }'],
	'semicolon_reopen' => ['main.phs' => 'namespace A; function value(): int { return 7; } namespace A; return value();'],
	'qualified_root' => ['main.phs' => 'namespace App; return \\Lib\\value();',
		'lib.phs' => 'namespace Lib; function value(): int { return 7; }'],
	'qualified_relative' => ['main.phs' => 'namespace App; $box Sub\\Box; $box->value = Sub\\value(); return $box->value;',
		'lib.phs' => 'namespace App\\Sub; struct Box { int $value; } function value(): int { return 7; }',
		'other.phs' => 'namespace Sub; function value(): int { return 11; }'],
	'qualified_global' => ['main.phs' => 'namespace App; function value(): int { return 11; } return \\value();',
		'lib.phs' => 'function value(): int { return 7; }'],
	'qualified_types' => ['main.phs' => 'namespace App; $box \\Lib\\Box; $box->value = 7; return \\Lib\\read(\\Lib\\copy($box));',
		'lib.phs' => 'namespace Lib; struct Box { int $value; } function copy(\\Lib\\Box $box): \\Lib\\Box { return $box; } function read(\\Lib\\Box $box): int { return $box->value; }'],
	'qualified_fields' => ['main.phs' => 'namespace App; struct Outer { \\Lib\\Box $box; } $outer Outer; $outer->box->value = 7; return $outer->box->value;',
		'lib.phs' => 'namespace Lib; struct Box { int $value; }'],
	'qualified_new' => ['main.phs' => 'namespace App; $box = new \\Lib\\Box(); return 7;',
		'lib.phs' => 'namespace Lib; struct Box { int $value; }'],
	'qualified_constant' => ['main.phs' => 'namespace App; return \\Lib\\LIMIT;'],
];
$outputs = [];
foreach ($cases as $name => $sources) {
	$compiler = namespace_project($sources);
	if ($name === 'qualified_constant') {
		// Registry-supplied constant; source constant declarations are a separate feature.
		$constant = new integer_constant_definition();
		$constant->name = 'LIMIT';
		$constant->type = Language_Types::integer(Model::$language_scope);
		$constant->decimal = '7';
		Model::$namespaces->register('Lib')->published_scope()->register_constant($constant);
	}
	$compiler->exec_cpp();
	$outputs[$name] = CPP_Output::encode(Model::$cpp_files);
}
namespace_check($outputs['semicolon'] === $outputs['braced'], 'Namespace spelling changed generated meaning');

foreach ([
	'namespace A { return 1; } namespace B { return 2; }' => 'only one namespace body',
	'function value(): int { return 1; } namespace A { return value(); }' => 'value',
	'namespace A { function value(): int { return 1; } } namespace A\B { return value(); }' => 'value',
	'namespace A { function value(): int { return 1; } } namespace A { function value(): int { return 2; } return value(); }' => 'duplicate function name',
	'namespace A { function value(): int { return 7; } return Missing\\value(); }' => 'Missing\\value',
	'namespace A { return \\Missing\\value(); }' => 'Missing\\value',
	'namespace A { return \\Missing\\LIMIT; }' => 'Missing\\LIMIT',
	'namespace A { $box \\Missing\\Box; return 0; }' => 'Missing\\Box',
	'return \\Lib\\();' => 'source name',
] as $text => $message) {
	$compiler = namespace_project(['main.phs' => $text]);
	$failed = false;
	try { $compiler->exec_cpp(); }
	catch (\RuntimeException $expected) { $failed = str_contains($expected->getMessage(), $message); }
	namespace_check($failed && Model::$cpp_files->is_empty(), 'Missing namespace rejection: ' . $text);
}

$compiler = namespace_project(['main.phs' => 'namespace A { function value(): int { return 7; } } namespace A { return value(); }']);
$compiler->exec_cpp();
$record = Model::sources()[0];
$first = $record->parsed->root->namespace_bodies[0];
$second = $record->parsed->root->namespace_bodies[1];
$definition = $first->declarations[0]->occurrence();
$work = $second->body->work();
$original = CPP_Output::encode(Model::$cpp_files);
namespace_check($first->body_scope() !== $second->body_scope(), 'Reopened namespace merged environments');
$version = $work->version;
namespace_edit($compiler, $record, 'namespace A { function value(): int { return 7; } } namespace A { return value(); }');
$compiler->prepare(true);
$compiler->cpp();
namespace_check($second->body->work() === $work && $work->version === $version, 'No-op namespace parse rebuilt executable work');
namespace_check(CPP_Output::encode(Model::$cpp_files) === $original, 'No-op namespace edit changed artifacts');
namespace_edit($compiler, $record, 'namespace A { function value(): int { return 9; } } namespace A { return value(); }');
$compiler->prepare(true);
$compiler->cpp();
namespace_check($record->parsed->root->namespace_bodies[0] === $first && $record->parsed->root->namespace_bodies[1] === $second,
	'Body edit replaced source occurrence identities');
namespace_check($first->declarations[0]->occurrence() === $definition && $second->body->work() === $work, 'Body edit replaced canonical work');

// New partial bodies and old scopes must survive an unsuccessful join, then retire on repair.
$failed = false;
try { namespace_edit($compiler, $record, 'namespace B { function moved(): int { return 3; } } namespace Broken { function nope('); }
catch (\RuntimeException $expected) { $failed = true; }
namespace_check($failed && !$record->parsed->complete && !$record->parsed->collection->retired_scopes->is_empty(), 'Failed parse lost scope owners');
namespace_check($definition->preparation_work_owner() !== null, 'Failed parse retired old semantic work');
namespace_edit($compiler, $record, 'namespace A { function value(): int { return 7; } } namespace A { return value(); }');
$compiler->prepare(true);
$compiler->cpp();
namespace_check(CPP_Output::encode(Model::$cpp_files) === $original, 'Failed-parse repair differs from clean output');
namespace_check($record->parsed->collection->retired_scopes->is_empty(), 'Successful retirement retained old scope inventory');
namespace_check(count(Model::$namespaces->find('B')->published_scope()->functions_named('moved')) === 0, 'Removed partial declaration stayed published');

$old_owner = $definition->preparation_work_owner();
namespace_edit($compiler, $record, 'namespace B { function value(): int { return 7; } return value(); }');
$compiler->prepare(true);
$compiler->cpp();
namespace_check($old_owner->change_status === change_state::deleted && $work->change_status === change_state::deleted, 'Namespace move retained old work');
namespace_check(count(Model::$namespaces->find('A')->published_scope()->functions_named('value')) === 0, 'Namespace move retained old publication');
$moved = Model::$namespaces->find('B')->published_scope()->functions_named('value')[0];
namespace_check($moved !== $definition && $moved->qualified_name() === 'B\value', 'Namespace move reused a differently qualified definition');
$outputs['moved'] = CPP_Output::encode(Model::$cpp_files);
$failed = false;
try { $compiler->llvm(); }
catch (\RuntimeException $expected) { $failed = str_contains($expected->getMessage(), 'parked LLVM'); }
namespace_check($failed, 'Parked LLVM admitted named namespace semantics');

namespace_edit($compiler, $record, 'function value(): int { return 7; } return value();');
$compiler->prepare(true);
$compiler->cpp();
namespace_check(count(Model::$namespaces->find('B')->published_scope()->functions_named('value')) === 0,
	'Returning to implicit global source left a namespace contribution');
namespace_check(Model::$global_scope->functions_named('value')[0]->qualified_name() === 'value', 'Implicit global source inherited its previous namespace');
$outputs['implicit_global'] = CPP_Output::encode(Model::$cpp_files);

// The standalone API's explicit implicit environment is preserved on a retained parse.
$standalone_scope = new scope();
$standalone_scope->set_parent(Model::$namespaces->register('Standalone')->published_scope());
$standalone_source = new file();
$standalone_source->path = 'standalone.phs';
$standalone_source->content = 'function value(): int { return 7; }';
$standalone = (new Parser((new Tokenizer($standalone_source))->tokenize(), $standalone_scope))->parse();
$standalone_definition = $standalone->root->single_body()->declarations[0]->occurrence();
$standalone_source->content = 'function value(): int { return 9; }';
$standalone = (new Parser((new Tokenizer($standalone_source))->tokenize(), null, $standalone))->parse();
namespace_check($standalone->root_scope() === $standalone_scope && $standalone->root->single_body()->declarations[0]->occurrence() === $standalone_definition,
	'Retained standalone parse lost its explicit environment');
$standalone_source->content = '';
$standalone = (new Parser((new Tokenizer($standalone_source))->tokenize(), null, $standalone))->parse();
$standalone = (new Parser((new Tokenizer($standalone_source))->tokenize(), null, $standalone))->parse();
namespace_check($standalone->root_scope() === $standalone_scope, 'Empty standalone reparse lost its namespace environment');

if (isset($argv[1])) {
	foreach ($outputs as $name => $output) { file_put_contents($argv[1] . '/' . $name . '.json', $output); }
}
echo "Namespaces: syntax, shared publication, separate environments, retained edits, moves and recovery passed\n";
