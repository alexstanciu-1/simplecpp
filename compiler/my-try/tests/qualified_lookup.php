<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function qualified_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

Compiler_Lifecycle::reset();
$module = new module('/qualified-proof', '/qualified-proof', '/qualified-proof');
foreach (['main.phs' => 'namespace Client; return Sub\\target();', 'lib.phs' => ''] as $path => $text) {
	$file = new file();
	$file->path = $path;
	$file->content = $text;
	Source_Registry::add($module, $file);
}
Model::$modules->add($module->name, $module);
$compiler = new Compiler();
$compiler->select_entry('main', 'main.phs');
$compiler->tokenize();
$compiler->parse();
$main = $module->sources['main.phs'];
$library = $module->sources['lib.phs'];
$body = $main->parsed->root->single_body()->body;

// Authored qualified spelling reaches the same retained lookup and dependency path.
$call = $body->statements[0]->expression;
qualified_check($call->name === 'Sub\\target' && $call->occurrence()->name === $call->name, 'Parser lost qualified spelling');
$local = $main->parsed->root_scope();
qualified_check(Scope_Lookup::functions($local, $call->name) === [], 'Absent plain lookup returned a candidate');
qualified_check(Model::$namespaces->find('Client\\Sub') === null, 'Plain lookup mutated namespace registry');
$failed = false;
try { $compiler->prepare(true); }
catch (\RuntimeException $error) { $failed = str_contains($error->getMessage(), 'Sub\\target'); }
qualified_check($failed, 'Missing qualified function did not reject during preparation');
$pool = Model::$namespaces->find('Client\\Sub')->published_scope();
$work = $body->work();
$observed = $pool->preparation_lookups_named('target');
qualified_check(count($observed) === 1 && isset($observed[0]->dependents[$work]), 'Missing namespace has no member subscription');

function qualified_edit(Compiler $compiler, source_record $source, string $text): void
{
	$source->file->content = $text;
	$source->file->changes = SYNC_CHANGED;
	$source->changes = change_state::changed;
	$compiler->tokenize();
	$compiler->parse();
}

$definitions = 'namespace Client\\Sub { struct Box { int $value; } function target(): int { return 7; } }';
qualified_edit($compiler, $library, $definitions);
$compiler->prepare(true);
$compiler->cpp();
qualified_check(Model::$namespaces->find('Client\\Sub')->published_scope() === $pool, 'Publication replaced observed empty namespace');
qualified_check($body->work() === $work && $work->state === preparation_state::ready, 'Namespace addition did not recover unchanged caller');
$selected = $call->require_preparation()->target;
$version = $work->version;
$compiler->prepare(true);
qualified_check($work->version === $version, 'No-op qualified lookup rebuilt caller');

// Rooted and relative queries select the same canonical member, never a tail-name search.
qualified_check(Scope_Lookup::functions($local, 'Sub\\target') === [$selected], 'Relative path selected wrong function');
qualified_check(Scope_Lookup::functions($local, '\\Client\\Sub\\target') === [$selected], 'Rooted path selected wrong function');
qualified_check(Scope_Lookup::functions($local, '\\Sub\\target') === [], 'Rooted path was interpreted relative to current namespace');
qualified_check(Scope_Lookup::functions($local, 'target') === [], 'Nested member leaked into unqualified lookup');
$types = Scope_Lookup::types($local, 'Sub\\Box');
qualified_check(count($types) === 1 && $types[0]->name() === 'Client\\Sub\\Box', 'Relative type lost canonical identity');
qualified_check(Scope_Lookup::types($local, '\\Client\\Sub\\Box') === $types, 'Rooted type lookup differs');

$value = new integer_constant_value();
$value->type = Language_Types::integer(Model::$language_scope);
$value->decimal = '7';
$constant = new registered_constant('LIMIT', $value);
$pool->register_constant($constant);
$context = new preparation_context();
$context->owner = $work;
$context->worker = new Preparation_Worker(Model::$language_scope);
qualified_check(Observed_Lookup::types($local, 'Sub\\Box', $context) === $types, 'Observed qualified type differs');
qualified_check(Observed_Lookup::constants($local, 'Sub\\LIMIT', $context) === [$constant], 'Observed qualified constant differs');
qualified_check(Scope_Lookup::constants($local, '\\Client\\Sub\\LIMIT') === [$constant], 'Plain rooted constant differs');
qualified_check(Observed_Lookup::functions($local, 'Sub\\LIMIT', $context) === [], 'Symbol kinds collapsed');
qualified_check(Observed_Lookup::types($local, 'Sub\\int', $context) === [], 'Explicit namespace fell back to language types');
qualified_check(Observed_Lookup::types($local, 'int', $context) !== [], 'Bare builtin lookup regressed');

// Losing the exact namespace member must invalidate the unchanged caller.
qualified_edit($compiler, $library, 'namespace Elsewhere { function target(): int { return 7; } }');
$failed = false;
try { $compiler->prepare(true); }
catch (\RuntimeException $error) { $failed = true; }
qualified_check($failed && $work->state !== preparation_state::ready, 'Moved target left stale ready caller');
qualified_edit($compiler, $library, $definitions);
$compiler->prepare(true);
qualified_check($work->state === preparation_state::ready, 'Qualified caller could not recover after move/repair');

// The explicitly rooted source-global pool is separate from the current namespace.
qualified_edit($compiler, $library, $definitions . ' namespace { function target(): int { return 9; } }');
$compiler->prepare(true);
$global = Scope_Lookup::functions($local, '\\target');
qualified_check(count($global) === 1 && $global[0]->qualified_name() === 'target', 'Root member did not select source-global identity');
qualified_check(Scope_Lookup::functions($local, 'target') === [], 'Explicit global support introduced implicit global fallback');

// A duplicate added to the exact target pool must invalidate an already ready use.
qualified_edit($compiler, $library, $definitions . ' namespace Client\\Sub { function target(): int { return 8; } }');
$failed = false;
try { $compiler->prepare(true); }
catch (\RuntimeException $error) { $failed = true; }
qualified_check($failed && $work->state !== preparation_state::ready, 'New qualified ambiguity left stale ready caller');
qualified_edit($compiler, $library, $definitions);
$compiler->prepare(true);

// Replacing the consumer drops both observation directions through ordinary cleanup.
qualified_edit($compiler, $main, 'namespace Client; return 0;');
$compiler->prepare(true);
foreach ($pool->preparation_lookups_named('target') as $remaining) {
	qualified_check(!isset($remaining->dependents[$work]), 'Removed qualified use retained its reverse subscription');
}
foreach ($work->lookups as $remaining) {
	qualified_check($remaining->lookup_scope !== $pool, 'Removed qualified use retained its forward subscription');
}

// Syntax-only coverage of shared type scanning; this does not admit new wrapper semantics.
$syntax_file = new file();
$syntax_file->path = 'qualified-syntax.phs';
$syntax_file->content = 'namespace Client; function probe(?\\Lib\\Box $x): ?\\Lib\\Box { $copy ?\\Lib\\Box = null; $list vector<\\Lib\\Box> = []; for ($i \\Lib\\Box; false;) {} return $x; }';
$syntax = (new Parser((new Tokenizer($syntax_file))->tokenize()))->parse();
$function = $syntax->root->single_body()->declarations[0];
qualified_check($function->return_type->payload->name === '\\Lib\\Box', 'Nullable return lost qualified payload');
qualified_check($function->body->statements[0]->type_syntax->payload->name === '\\Lib\\Box', 'Nullable local lookahead lost qualified payload');
qualified_check($function->body->statements[1]->type_syntax->arguments[0]->name === '\\Lib\\Box', 'Template payload lost qualified name');

echo "Qualified lookup: authored paths, exact pools, kinds, pure lookup, misses, addition, move and repair passed\n";
