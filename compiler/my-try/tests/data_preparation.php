<?php

/* Contextual preparation, recovery and settled-fact emission proofs. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function data_preparation_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

final class Data_Preparation_Probe implements node_maintenance_worker_i
{
	public array $constructions = [];
	public function enter(ast_node $node): void
	{
		if ($node instanceof data_construction_node) { $this->constructions[] = $node; }
	}
	public function edge(ast_node $parent, ast_node $child): void { $child->maintain($this); }
	public function token_index(int $index): int { return $index; }
}

function data_preparation_program(string $path, string $source): Compiler
{
	file_put_contents($path, $source);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([dirname($path)]);
	$compiler->sync([]);
	$compiler->prepare();
	return $compiler;
}

function data_preparation_nodes(): array
{
	$probe = new Data_Preparation_Probe();
	Model::syntax_files()[0]->root->maintain($probe);
	return $probe->constructions;
}

function data_preparation_rejects(string $path, string $source, string $message): void
{
	$failed = false;
	try { data_preparation_program($path, $source); }
	catch (\Exception $error) {
		$failed = str_contains($error->getMessage(), $message);
		data_preparation_check($failed, 'Wrong diagnostic for ' . $source . ': ' . $error->getMessage());
	}
	data_preparation_check($failed && Model::$prepared_files->is_empty() && Model::$cpp_files->is_empty(), 'Invalid construction published facts: ' . $source);
}

$directory = sys_get_temp_dir() . '/scpp_data_preparation_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
try {
	$cases = [
		'$v vector<int> = [];',
		'$v vector<int> = [1, 2, 3,];',
		'$v vector<int>; $v = ([1, 2]);',
		'$v = vector<int>::[1, 2];',
		'$v hash<int> = [];',
		'$v hash<int> = {};',
		'$v hash<int> = ["a" => 1, "a" => 2];',
		'$v hash<int, int> = [1, 2];',
		'$v hash<int, int> = [10 => 1, 20 => 2];',
		'$v mixed = [];',
		'$v mixed = {a: 1, b: "text", c: null};',
		'$v dynamic = [1, 2];',
		'$v dynamic = {};',
		'$v vector<vector<int>> = [[1], [2, 3]];',
		'$v hash<vector<int>> = {a: [1], b: []};',
		'$v vector<nullable<int>> = [1, null];',
		'function build(): vector<int> { return [1, 2]; } $v = build();',
		'function accept(vector<int> $v): void {} accept([1, 2]);',
		'function accept(vector<int> ...$v): void {} accept([1], [2]);',
		'struct Holder { vector<int> $items; } $h Holder; $h->items = [1, 2];',
		'$n = 0; function number(): int { return 4; } $v vector<int> = [$n = 1, $n++, number()];',
		'$k = 0; $v hash<int, int> = [($k = 1) => $k++, $k => 3];',
		'$unknown mixed = 1; $v vector<int> = [$unknown];',
		'$key mixed = "x"; $v dynamic = [$key => 1];',
		'function key(): string { return "x"; } function value(): int { return 1; } $v hash<int> = [key() => value()];',
		'function size(vector<string> $v): int { return 1; } $v vector<int> = [size(["x"])];',
		'$v vector<int> = [1]; if (true) { $v hash<int> = {a: 1}; } $v = [2];',
	];
	foreach ($cases as $source) {
		data_preparation_program($path, $source . ' return 0;');
		$nodes = data_preparation_nodes();
		data_preparation_check(count($nodes) > 0, 'Fixture has no construction');
		foreach ($nodes as $node) {
			$facts = $node->require_data_preparation();
			data_preparation_check(!$facts->addressable && $facts->type->matches($facts->binding->target()), 'Wrong construction result identity');
			foreach ($node->entries as $entry) {
				$entry_facts = $entry->require_preparation();
				data_preparation_check(($entry_facts->key_conversion !== null) === ($entry->key !== null), 'Key absence changed');
				data_preparation_check($entry_facts->value_conversion->context === conversion_context::initializer_value, 'Lost value boundary context');
				if ($entry->key !== null) {
					data_preparation_check($entry_facts->key_conversion->context === conversion_context::initializer_key, 'Lost key boundary context');
				}
			}
		}
	}

	// Nesting defaults are independent of mixed-only entry storage. Explicit annotations win.
	data_preparation_program($path, '$d dynamic = {settings: {enabled: true}, other: mixed::{n: 1}}; return 0;');
	$nodes = data_preparation_nodes();
	data_preparation_check(Type_Queries::canonical($nodes[1]->require_data_preparation()->type)->definition() instanceof dynamic_semantics, 'Dynamic nested default lost');
	data_preparation_check(Type_Queries::canonical($nodes[2]->require_data_preparation()->type)->definition() instanceof mixed_semantics, 'Explicit nested target ignored');
	data_preparation_check($nodes[0]->entries[0]->require_preparation()->value_conversion->operation === conversion_operation::mixed_dynamic_box, 'Nested dynamic was not boxed by identity');
	data_preparation_check($nodes[0]->entries[1]->require_preparation()->value_conversion->operation === conversion_operation::identity, 'Explicit mixed nested value changed');
	data_preparation_program($path, '$d dynamic = {}; $m mixed = {nested: {}, existing: $d}; return 0;');
	$nodes = data_preparation_nodes();
	data_preparation_check(Type_Queries::canonical($nodes[2]->require_data_preparation()->type)->definition() instanceof mixed_semantics, 'Mixed nested default lost');
	data_preparation_check($nodes[1]->entries[1]->require_preparation()->value_conversion->operation === conversion_operation::mixed_dynamic_box, 'Existing dynamic value was not admitted');

	// Duplicate entries remain source-ordered; no hash-shaped prepared snapshot is created.
	data_preparation_program($path, '$v hash<int> = {same: 1, same: 2}; return 0;');
	$node = data_preparation_nodes()[0];
	data_preparation_check(count($node->entries) === 2 && $node->entries[0]->value->require_preparation()->decimal === '1'
		&& $node->entries[1]->value->require_preparation()->decimal === '2', 'Preparation removed/reordered duplicates');
	Preparation_Cleanup::tree($node);
	data_preparation_check($node->preparation() === null && $node->entries[0]->preparation() === null
		&& $node->entries[0]->value->preparation() === null, 'Entry facts survived structural cleanup');

	$rejections = [
		['$v value<dynamic> = {};', 'effective storage modifiers'],
		['$v = [];', 'known concrete target'],
		['$v = {name: 1};', 'known concrete target'],
		['$v vector<int> = {};', 'does not support initialization shape'],
		['$v vector<int> = ["x" => 1];', 'does not support initialization shape'],
		['$v hash<int> = [1];', 'does not support initialization shape'],
		['$v mixed = [1, "x" => 2];', 'mixed positional/keyed'],
		['$v dynamic = [true => 1];', 'mixed initializer keys require'],
		['$v mixed = [null => 1];', 'mixed initializer keys require'],
		['$v dynamic = [1.5 => 1];', 'mixed initializer keys require'],
		['$v vector<int> = ["wrong"];', 'requires matching types'],
		['$v vector<int> = [true ? [1] : [2]];', 'known concrete target'],
		['$v vector<int> = hash<int>::{};', 'requires matching types'],
		['$v vector<int> = [$v];', 'itself in its initializer'],
		['$v vector<int> = [$new = 1];', 'established local'],
		['function accept(vector<int> &$v): void {} accept([1]);', 'stable storage'],
		['$d dynamic = {}; $m mixed = $d;', 'mixed boxing currently supports'],
		['struct Plain { int $x; } $v Plain = {x: 1};', 'source initialization interfaces are deferred'],
		['$v dynamic = {x: true ? {} : {}};', 'known concrete target'],
	];
	foreach ($rejections as [$source, $message]) { data_preparation_rejects($path, $source . ' return 0;', $message); }

	// Return-signature changes reprepare a retained body with a fresh contextual target.
	$source = 'function build(): vector<int> { return []; } return 0;';
	$compiler = data_preparation_program($path, $source);
	$function = Model::$global_scope->functions_named('build')[0]->syntax();
	$body = $function->body;
	$construction = $body->statements[0]->expression;
	$old_facts = $construction->require_data_preparation();
	file_put_contents($path, str_replace('vector<int>', 'hash<int>', $source));
	$compiler->sync([$path]);
	$compiler->prepare();
	$new_facts = $construction->require_data_preparation();
	data_preparation_check($function->body === $body && $new_facts !== $old_facts && $new_facts->binding->is_keyed(), 'Retained return literal kept its old expected type');
	file_put_contents($path, str_replace('vector<int>', 'int', $source));
	$compiler->sync([$path]);
	$failed = false;
	try { $compiler->prepare(); }
	catch (\RuntimeException $error) { $failed = str_contains($error->getMessage(), 'does not support initialization shape'); }
	data_preparation_check($failed && $construction->preparation() === null && Model::$prepared_files->is_empty(), 'Failed reprepare retained stale construction facts');
	file_put_contents($path, $source);
	$compiler->sync([$path]);
	$compiler->prepare();
	data_preparation_check(!$construction->require_data_preparation()->binding->is_keyed(), 'Contextual construction did not recover');
	$compiler->cpp();
	data_preparation_check(str_contains(Model::$cpp_files[0]->text, 'scpp::data_initializer<'), 'Construction did not use the registered adapter');
	$first_cpp = Model::$cpp_files[0]->text;
	data_preparation_check(!str_contains($first_cpp, 'initializer_buffer'), 'Empty construction allocated input backing');
	Compiler_Lifecycle::reset_preparation();
	$compiler->prepare();
	$compiler->cpp();
	data_preparation_check(Model::$cpp_files[0]->text === $first_cpp, 'Preparation reset changed construction emission');
}
finally {
	unlink($path);
	rmdir($directory);
}
echo "Data preparation: contextual targets, ordered facts, conversions, nesting and recovery passed\n";
