<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
use scpp\compiler\{ast_index_assignment, ast_append_statement, ast_index_compound, ast_index_update,
	Cpp_Emitter, entry_target, flavor, Function_Preparation, Preparation, source_file, Source_Frontend};

$source = new source_file('prepared_index_roots', '
	$rows vector<vector<int>> = [[2]];
	$rows[0][0] = 3;
	$rows[0][] = 4;
	$rows[0][0] += 5;
	++$rows[0][0];
	$rows[0][0]--;
	$rows[0][0] ??= 9;
	return 8;', flavor::php);
$target = new entry_target(31);
$entry = (new Source_Frontend())->prepare($source, $target);
$forms = [ast_index_assignment::class, ast_append_statement::class, ast_index_compound::class,
	ast_index_update::class, ast_index_update::class, ast_index_compound::class];
$writes = array_slice($entry->body()->statements, 1, count($forms));
foreach ($forms as $i => $class) {
	$write = $writes[$i];
	if ($write::class !== $class) throw new RuntimeException('Unexpected write form');
	$row = $write instanceof ast_index_compound || $write instanceof ast_index_update ? $write->target->receiver : $write->receiver;
	if ($write->prepared_root !== $row->receiver) throw new RuntimeException("Missing prepared root on $class");
}
$before = (new Cpp_Emitter())->emit($entry);
Function_Preparation::reset_body($entry->body());
foreach ($writes as $write) {
	if ($write->prepared_root !== null) throw new RuntimeException('Reset retained an indexed write root');
}
$again = (new Preparation())->prepare($entry->file, $target);
if ((new Cpp_Emitter())->emit($again) !== $before) throw new RuntimeException('Reprepared lowering changed');
