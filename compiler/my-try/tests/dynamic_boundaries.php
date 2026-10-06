<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function dynamic_boundaries_check(bool $ok, string $message): void
{
	if (!$ok) { throw new \LogicException($message); }
}

function dynamic_boundaries_source(string $source): Compiler
{
	Compiler_Lifecycle::reset();
	$input_module = new module('/dynamic-boundaries', '/dynamic-boundaries', '/dynamic-boundaries');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($input_module, $file);
	Model::$modules->add($input_module->name, $input_module);
	$compiler = new Compiler();
	$compiler->exec_cpp();
	return $compiler;
}

dynamic_boundaries_source('$a dynamic = 7; $b dynamic = $a; $c int = $b;');
$nodes = Model::syntax_files()[0]->root->body->statements;
foreach ([conversion_operation::boxed_wrap, conversion_operation::identity,
	conversion_operation::boxed_extract] as $position => $expected) {
	dynamic_boundaries_check($nodes[$position]->require_preparation()->conversion->operation === $expected,
		'Dynamic boundary bypassed shared boxed conversion');
}
dynamic_boundaries_check(str_contains(CPP_Output::single_unit(Model::$cpp_files)->text, 'scpp::required_cast<scpp::int_t<>>'),
	'Required boundary lost checked extraction');
$catalog = Model::$type_catalog;
$dynamic_type = $catalog->registry()->use($catalog->canonical('dynamic')->type_id());
$integer_type = Language_Types::integer(Model::$language_scope);
$signature = Model::$indexing_contracts->lookup($catalog->definition('dynamic'));
$iteration = Model::$iteration_contracts->lookup($catalog->definition('dynamic'));
$arguments = [];
dynamic_boundaries_check(Contract_Type_Substitution::resolve($signature->value(), $dynamic_type, $arguments)->matches($dynamic_type),
	'Indexed reads changed dynamic family');
dynamic_boundaries_check(Contract_Type_Substitution::resolve($iteration->value(), $dynamic_type, $arguments)->matches($dynamic_type),
	'Iteration changed dynamic family');

// Semantic roles, not built-in spellings, select the common conversion path.
$custom = $catalog->registry()->define_dynamic('TaggedValue', type_definition_origin::runtime,
	[generic_contract::value_storable, generic_contract::copyable_value]);
$custom_type = $catalog->registry()->use($catalog->registry()->canonical($custom)->type_id());
dynamic_boundaries_check(Boxed_Conversions::decide($integer_type, $custom_type, conversion_context::assignment)->operation
	=== conversion_operation::boxed_wrap, 'Boxing depends on source name');
dynamic_boundaries_check(Boxed_Conversions::decide($custom_type, $integer_type, conversion_context::return_value)->operation
	=== conversion_operation::boxed_extract, 'Extraction depends on source name');

foreach ([
	'$m mixed = []; $d dynamic = $m;',
	'$d dynamic = {}; $m mixed = $d;',
	'$d dynamic = {child: mixed::{n: 1}};',
	'$m mixed = {child: dynamic::{n: 1}};',
	'$m mixed = 7; $d dynamic = {}; $d["n"] = $m;',
	'$d dynamic = [1]; $value mixed = null; foreach ($d as $value) {}',
	'$d dynamic = {}; $key dynamic = "n"; $v = $d[$key];',
	'$n uint8 = 1; $d dynamic = $n;',
	'$d dynamic = 7; $n uint8 = $d;',
	'function call(int &$v): void {} $d dynamic = 7; call($d);',
	'$d dynamic = 7; $m mixed = 8; $v = true ? $d : $m;',
	'$d dynamic = 7; $m mixed = 8; $v = $m ?: $d;',
	'$d dynamic = 7; $m mixed = 8; $v = $d ?? $m;',
	'$d dynamic = 7; $m mixed = 8; $v = $d && $m;',
	'$d dynamic = 7; $m mixed = 8; $v = $d + $m;',
	'$d dynamic = 7; $m mixed = 8; $v = $m < $d;',
	'$d dynamic = 7; $m mixed = 8; $v = $d === $m;',
	'$d dynamic = 7; $m mixed = 8; $v = $d . $m;',
	'$d dynamic = 7; $m mixed = 8; $d += $m;',
	'function store(dynamic &$d, mixed $v): void { $d["child"]["new"] = $v; }',
	'function store(mixed &$m, dynamic $v): void { $m["child"]["new"] = $v; }',
	'$d dynamic = 7; $v = $d || "yes";',
	'$d dynamic = 7; $v = $d ?: null;',
	'$d dynamic = 7; $v = null ?? $d;',
	'$d dynamic = 7; $v = $d ?? null;',
	'$d dynamic = 7; $n uint8 = 1; $v = true ? $d : $n;',
	'$d dynamic = 7; $r result<int> = 1; $v = $d ?? $r;',
	'$d dynamic = 7; $r result_or_bool<int> = 1; $v = $r ?? $d;',
] as $source) {
	$rejected = false;
	try { dynamic_boundaries_source($source); }
	catch (\RuntimeException $error) { $rejected = true; }
	dynamic_boundaries_check($rejected && Model::$cpp_files->is_empty(), 'Invalid boundary emitted output: ' . $source);
}

// Change the family of a producer while retaining a typed consumer, then compare a clean run.
$valid = 'function source(): mixed { return 7; } function read(): int { return source(); }';
$compiler = dynamic_boundaries_source($valid);
$changed = str_replace('source(): mixed', 'source(): dynamic', $valid);
$record = Model::sources()[0];
$record->file->content = $changed;
$compiler->update_cpp(['/dynamic-boundaries/main.phs']);
$incremental = CPP_Output::single_unit(Model::$cpp_files)->text;
dynamic_boundaries_source($changed);
dynamic_boundaries_check(CPP_Output::single_unit(Model::$cpp_files)->text === $incremental, 'Dynamic boundary clean/incremental output differs');

// Conditions and selections consume the changed signature without stale mixed facts.
foreach (['return source() ? 1 : 0;', 'return !source() ? 1 : 0;',
	'return (source() && true) ? 1 : 0;', 'return source() ?: 1;',
	'return source() ?? 1;', 'return true ? 1 : source();',
	'$v = source(); return $v + 1;', 'return -source();',
	'$v = source(); return $v === 7 ? 1 : 0;', '$v = source(); return $v < 8 ? 1 : 0;',
	'$v = source(); return ++$v;', '$v = source(); return $v += 1;',
	'$v = source(); return (int)($v . "0");', '$v = source(); $v .= "0"; return (int)$v;'] as $consumer) {
	$valid = 'function source(): mixed { return 7; } function read(): int { ' . $consumer . ' }';
	$compiler = dynamic_boundaries_source($valid);
	$changed = str_replace('source(): mixed', 'source(): dynamic', $valid);
	$record = Model::sources()[0];
	$record->file->content = $changed;
	$compiler->update_cpp(['/dynamic-boundaries/main.phs']);
	$incremental = CPP_Output::single_unit(Model::$cpp_files)->text;
	dynamic_boundaries_source($changed);
	dynamic_boundaries_check(CPP_Output::single_unit(Model::$cpp_files)->text === $incremental,
		'Dynamic selection clean/incremental output differs: ' . $consumer);
}

dynamic_boundaries_source('$d dynamic = 0; if ($d) {} $a dynamic = $d ?: 1; $b dynamic = $d ?? 1;');
$root = Model::syntax_files()[0]->root;
dynamic_boundaries_check($root->body->statements[1]->require_preparation()->truthiness->operation
	=== truthiness_operation::boxed_condition, 'Dynamic condition bypassed common truthiness');
$elvis = $root->body->statements[2]->initializer;
$coalesce = $root->body->statements[3]->initializer;
dynamic_boundaries_check(Boxed_Conversions::is_dynamic($elvis->require_preparation()->type)
	&& !$elvis->require_preparation()->addressable(), 'Dynamic join lost its owned family');
dynamic_boundaries_check($coalesce->require_coalesce_preparation()->presence === coalesce_presence::boxed_nonnull,
	'Dynamic coalescing used truthiness instead of null presence');
Preparation_Cleanup::tree($root);
dynamic_boundaries_check($elvis->preparation() === null && $coalesce->preparation() === null,
	'Dynamic joins retained facts after cleanup');

foreach (['mixed', 'dynamic'] as $family) {
	foreach (['$out = $v + true;', '$out = $v % 0.5;', '$out = ~$v;',
		'$out = $v < "1";', '$v += false;', '$out = $v ** 2;',
		'$n = 1; $n += $v;', '$out = $v++ + $v;', '$v[0] += 1;'] as $operation) {
		$rejected = false;
		try { dynamic_boundaries_source('$v ' . $family . ' = 1; ' . $operation); }
		catch (\RuntimeException $error) { $rejected = true; }
		dynamic_boundaries_check($rejected && Model::$cpp_files->is_empty(),
			'Invalid boxed operator emitted output: ' . $family . ' ' . $operation);
	}
	dynamic_boundaries_source('$v ' . $family . ' = 1; $out ' . $family . ' = $v + 1; $v += 1;');
	$root = Model::syntax_files()[0]->root;
	dynamic_boundaries_check($root->body->statements[1]->initializer->require_binary_preparation()->decision->operation
		=== operator_operation::boxed_addition, 'Family bypassed shared operator identity');
	Preparation_Cleanup::tree($root);
	dynamic_boundaries_check($root->body->statements[1]->initializer->preparation() === null,
		'Boxed arithmetic retained stale facts');
}
echo "Dynamic boundaries: shared conversions, family signatures, rejection and recovery passed\n";
