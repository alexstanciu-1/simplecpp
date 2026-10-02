<?php

namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function capability_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

function capability_program(string $path, string $source): Compiler
{
	file_put_contents($path, $source);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([dirname($path)]);
	$compiler->sync([]);
	return $compiler;
}

function capability_record(string $name): prepared_record
{
	return Model::$global_scope->source_types_named($name)[0]->syntax()->require_preparation();
}

$directory = sys_get_temp_dir() . '/scpp_capabilities_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
try
{
	$compiler = capability_program($path, 'struct Empty {} struct Point { int $x; } '
		. 'struct Owner { unique<int> $item; } struct Outer { Owner $owner; } '
		. 'struct Shared { shared<int> $item; } $items vector<Outer>; return 0;');
	$definition = object_cast(Scope_Lookup::types(Model::$global_scope, 'Point')[0], nominal_type_definition::class);
	// Source registration must not make a positive or negative semantic capability claim.
	$registry = Model::$type_catalog->registry();
	$unknown = false;
	try {
		$registry->has_capability($registry->use($registry->canonical($definition)->type_id()),
			generic_contract::copyable_value);
	}
	catch (\LogicException $error) {
		$unknown = str_contains($error->getMessage(), 'require completed declaration');
	}
	capability_check($unknown, 'Registry answered an unresolved source capability');
	$compiler->prepare();
	foreach (['Empty', 'Point', 'Owner', 'Outer', 'Shared'] as $name) {
		$facts = capability_record($name);
		capability_check($facts->has_capability(generic_contract::value_storable), 'Valid record is not storable');
		capability_check(!$facts->has_capability(generic_contract::hashable)
			&& !$facts->has_capability(generic_contract::comparable), 'Field operations leaked onto record');
		capability_check($facts->has_capability(generic_contract::copyable_value)
			=== in_array($name, ['Empty', 'Point', 'Shared'], true), 'Wrong memberwise copy capability: ' . $name);
	}
	Compiler_Lifecycle::reset_preparation();
	$compiler->prepare();
	capability_check(!capability_record('Outer')->has_capability(generic_contract::copyable_value),
		'Cleanup/reprepare lost nested capability');

	$rejections = [
		'$a unique<int>; $b = $a;',
		'$a unique<int>; $b unique<int> = $a;',
		'$a unique<int>; $b unique<int>; $b = $a;',
		'function f(unique<int> $a): void {} $a unique<int>; f($a);',
		'function f(unique<int> &$a): unique<int> { return $a; }',
		'struct Box { unique<int> $p; } $a Box; $b = $a;',
		'struct Box { unique<int> $p; } $a Box; $b Box; $a->p = $b->p;',
		'$a unique<int>; $b unique<int>; $c = ($b = $a);',
		'struct Box { unique<int> $p; } function f(Box &$a): Box { return $a; }',
	];
	foreach ($rejections as $source) {
		$compiler = capability_program($path, $source . ' return 0;');
		$rejected = false;
		try {
			$compiler->prepare();
		}
		catch (\RuntimeException $error) {
			$rejected = str_contains($error->getMessage(), 'value copy requires copyable_value');
		}
		capability_check($rejected, 'Copy did not reject in preparation: ' . $source);
	}

	// Template contracts use the same query, including cached application revalidation.
	$template_source = 'struct Inner { int $item; } struct Outer { Inner $inner; } $box copy_box<Outer>; return 0;';
	$compiler = capability_program($path, $template_source);
	$registry = Model::$type_catalog->registry();
	$copy_template = $registry->define_template('copy_box', type_definition_origin::runtime,
		nominal_type_kind::class_type, [new template_type_parameter('T')], [generic_contract::value_storable]);
	Model::$language_scope->register_type($copy_template);
	$compiler->prepare();
	$count = $registry->application_count();
	file_put_contents($path, str_replace('int $item', 'unique<int> $item', $template_source));
	$compiler->sync([$path]);
	$rejected = false;
	try {
		$compiler->prepare();
	}
	catch (\RuntimeException $error) {
		$rejected = str_contains($error->getMessage(), 'required capability copyable_value');
	}
	capability_check($rejected, 'Cached specialization bypassed changed source capability');
	file_put_contents($path, $template_source);
	$compiler->sync([$path]);
	$compiler->prepare();
	capability_check($registry->application_count() === ($count + 1),
		'Recovery duplicated specialization identity (only unique<int> should be new)');

	// An outer type and its consumer stay textually unchanged across inner-field edits.
	$valid = 'struct Inner { int $item; } struct Outer { Inner $inner; } '
		. 'function copy(Outer &$a): Outer { return $a; } $a Outer; $b = copy($a); return 0;';
	$compiler = capability_program($path, $valid);
	$compiler->prepare();
	$compiler->cpp();
	$original = Model::$cpp_files[0]->text;
	$outer = Model::$global_scope->source_types_named('Outer')[0];
	$version = $outer->preparation->version;
	file_put_contents($path, str_replace('int $item', 'unique<int> $item', $valid));
	$rejected = false;
	try {
		$compiler->update_cpp([$path]);
	}
	catch (\RuntimeException $error) {
		$rejected = str_contains($error->getMessage(), 'value copy requires copyable_value');
	}
	capability_check($rejected && ($outer->preparation->version > $version)
		&& !capability_record('Outer')->has_capability(generic_contract::copyable_value),
		'Nested edit did not invalidate unchanged copy consumer');
	capability_check(q_count(Model::$cpp_files) === 0, 'Failed copy published completed output');
	file_put_contents($path, $valid);
	$compiler->update_cpp([$path]);
	capability_check(Model::$cpp_files[0]->text === $original, 'Copy recovery differs from clean output');

	$positive = 'struct Inner { int $item; } struct Outer { Inner $inner; } '
		. 'function copy(Outer &$a): Outer { return $a; } '
		. 'function consume(Outer $v): int { return $v->inner->item; } '
		. '$inner Inner; $inner->item = 7; $a Outer; $a->inner = $inner; '
		. '$b = copy($a); $c Outer; $d = ($c = $b); $e = $f = $d; '
		. '$inner->item = 9; return consume($e);';
	$compiler = capability_program($path, $positive);
	$compiler->prepare();
	$compiler->cpp();
	if (isset($argv[2])) {
		file_put_contents($argv[2], Model::$cpp_files[0]->text);
	}

	// Reference-only use must remain valid for non-copyable records and handles.
	$compiler = capability_program($path, 'struct Owner { unique<int> $item; } '
		. 'function borrow(Owner &$a, unique<int> &$b): void {} '
		. '$a Owner; $b unique<int>; borrow($a, $b); return 0;');
	$compiler->prepare();
	$compiler->cpp();
	if (isset($argv[1])) {
		file_put_contents($argv[1], Model::$cpp_files[0]->text);
	}
	echo "Capabilities: memberwise facts, storage constraints, copy boundaries, references and incremental recovery passed\n";
}
finally {
	unlink($path);
	rmdir($directory);
}
