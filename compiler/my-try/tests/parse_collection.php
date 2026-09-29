<?php

namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function parse_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

/** Force a notification so these tests do not depend on filesystem timestamp granularity. */
function parse_update(Compiler $compiler, source_record $source, string $content): void
{
	// This parser-only fixture models successful downstream consumption before its next edit.
	// Persistent unresolved state is covered separately by preparation_recovery.php.
	foreach ($source->parsed->collection->entries as $entry) {
		if ($entry->change_status !== change_state::deleted) {
			$entry->change_status = change_state::unchanged;
		}
	}
	file_put_contents(Source_Registry::full_path($source->owning_module(), $source->path), $content);
	$source->changes = change_state::changed;
	$compiler->tokenize();
	$compiler->parse();
}

/** Produce a fresh/incremental comparison through owning syntax children. */
function syntax_shape(ast_node $node): array
{
	$result = [$node->kind()->name, $node->start_token(), $node->end_token()];
	foreach ($node->children() as $child) {
		$result[] = syntax_shape($child);
	}
	return $result;
}

$directory = sys_get_temp_dir() . '/scpp_parse_collection_' . bin2hex(random_bytes(6));
mkdir($directory);
file_put_contents($directory . '/a.phs', 'struct Box { int $value; bool $ready; } function get(int $x): int { return $x; } $local int = 1; get($local);');
file_put_contents($directory . '/b.phs', 'function other(): int { return 2; }');
try
{
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$module = Model::$modules[$directory];
	$a = $module->sources['a.phs'];
	$b = $module->sources['b.phs'];
	$compiler->tokenize();
	$compiler->parse();
	$parsed = $a->parsed;
	parse_check($parsed->complete && $b->parsed->complete, 'Initial collection did not complete');
	$get = Model::$global_scope->functions_named('get')[0];
	$function_node = $get->node;
	$function = object_cast($function_node, function_node::class);
	$body = $function->body;
	$parameter = $function->parameters[0];
	$parameter_entry = $parameter->occurrence();
	$type = Model::$global_scope->types_named('Box')[0];
	$box = $type->declaration->node;
	$field = object_cast($box, struct_node::class)->fields[0];
	$removed = object_cast($box, struct_node::class)->fields[1]->occurrence();
	$old_revision = $get->revision;
	parse_check(q_count(Model::$global_scope->variables_named('local')) === 0, 'File-local variable leaked into the global scope');
	parse_check(q_count($parsed->collection->function_references) === 1, 'Call occurrence was not retained for resolution');
	parse_check($function->preparation() === null, 'Parsing performed preparation');

	// File executable statements exclude declarations; declaration movement alone does not change the body.
	parse_update($compiler, $a, 'function get(int $x): int { return $x; } struct Box { int $value; bool $ready; } $local int = 1; get($local);');
	parse_check($a->parsed === $parsed, 'Parsed file identity was replaced');
	parse_check(Model::$global_scope->functions_named('get')[0] === $get, 'Function symbol identity was replaced');
	parse_check($get->node === $function_node, 'Function node identity was replaced');
	parse_check(($function->parameters[0] === $parameter) && ($parameter->occurrence() === $parameter_entry), 'Parameter identity was replaced');
	parse_check($function->body === $body, 'Unchanged function body was not retained');
	parse_check(!$function->body->syntax_changed && !$parsed->root->body->syntax_changed, 'Declaration order or token offsets changed an executable-body flag');
	parse_check($get->change_status === change_state::unchanged, 'Unchanged signature marked changed');
	parse_check($get->revision !== $old_revision, 'Presence revision was not advanced');
	parse_check(Model::$global_scope->types_named('Box')[0] === $type, 'Canonical type identity was replaced');
	parse_check(object_cast($box, struct_node::class)->fields[0] === $field, 'Field node identity was replaced');
	parse_check(q_count(Model::$global_scope->functions_named('get')) === 1, 'Repeated registration duplicated a global symbol');
	parse_check($parsed->root->declarations[0] === $function_node, 'AST source order was not rebuilt');

	parse_update($compiler, $a, 'function get(bool $x): int { return 7; } struct Box { float $value; } get(1); $local int = 1;');
	parse_check($get->change_status === change_state::changed, 'Signature change was missed');
	parse_check($parameter_entry->change_status === change_state::changed, 'Parameter change was missed');
	parse_check($function->body->syntax_changed && $parsed->root->body->syntax_changed, 'Executable changes were missed');
	parse_check($field->occurrence()->change_status === change_state::changed, 'Field change was missed');
	parse_check($removed->change_status === change_state::deleted, 'Removed field was not marked deleted');

	// The failing file stops, but a later independent file completes and retains its progress.
	$other = Model::$global_scope->functions_named('other')[0];
	file_put_contents($directory . '/a.phs', 'function added(): int { return 1; } function broken(');
	file_put_contents($directory . '/b.phs', 'function other(): int { return 99; }');
	$a->changes = change_state::changed;
	$b->changes = change_state::changed;
	$compiler->tokenize();
	$failed = false;
	try {
		$compiler->parse();
	}
	catch (\RuntimeException $expected) {
		$failed = true;
	}
	parse_check($failed && !$a->parsed->complete && $b->parsed->complete, 'Failure did not isolate the file');
	parse_check(object_cast($other->node, function_node::class)->body->syntax_changed, 'Independent file was not updated after another file failed');
	parse_check($get->change_status !== change_state::deleted, 'Failure incorrectly deleted an unvisited function');
	$added = Model::$global_scope->functions_named('added')[0];
	$broken = Model::$global_scope->functions_named('broken')[0];
	parse_update($compiler, $a, 'function added(): int { return 1; } function broken(): int { return 3; }');
	parse_check($a->parsed->complete, 'Failed parse could not retry');
	parse_check(Model::$global_scope->functions_named('added')[0] === $added, 'Retry replaced a completed declaration identity');
	parse_check(Model::$global_scope->functions_named('broken')[0] === $broken, 'Retry replaced an incomplete declaration identity');
	parse_check($get->change_status === change_state::deleted, 'Successful retry did not delete a missing global');

	// Reintroduction reuses the tombstone; independent files with the same name remain distinct.
	parse_update($compiler, $a, 'function get(int $x): int { return $x; } function other(): int { return 4; }');
	parse_check(Model::$global_scope->functions_named('get')[0] === $get, 'Reintroduced declaration lost its identity');
	parse_check($get->change_status === change_state::added, 'Reintroduced declaration was not marked added');
	parse_check(q_count(Model::$global_scope->functions_named('other')) === 2, 'Same names from different files were merged');
	$fresh = (new Parser($a->tokens))->parse();
	parse_check(syntax_shape($a->parsed->root) === syntax_shape($fresh->root), 'Incremental tree differs from a fresh parse');

	// Tombstone spans may belong to a much older, longer file than the current token generation.
	parse_update($compiler, $a, '');
	parse_update($compiler, $a, 'function get(int $x): int { return $x; }');
	parse_check($get->change_status === change_state::added, 'Reappearance after an empty file was not added');
	parse_check($get->node === $function_node, 'Reappearance after an empty file replaced the node');
	$parameter_index = $parameter_entry->local_index;
	$a->parsed->collection->revision = 4294967295;
	parse_update($compiler, $a, 'function get(int $x): int { return $x; }');
	parse_check(($get->revision === 1) && ($parameter_entry->local_index === $parameter_index), 'Revision rollover lost declaration identity/index');
	parse_check($get->change_status === change_state::unchanged, 'Revision rollover changed an unchanged signature');
	$tokens = $a->tokens;
	$body = object_cast($get->node, function_node::class)->body;
	$compiler->parse();
	parse_check(($a->tokens === $tokens) && (object_cast($get->node, function_node::class)->body === $body), 'Unchanged source was reparsed');
	$a->changes = change_state::deleted;
	$compiler->parse();
	parse_check($get->change_status === change_state::deleted, 'Deleted file retained a live global declaration');
}
finally {
	foreach (glob($directory . '/*') as $path) {
		unlink($path);
	}
	rmdir($directory);
}
echo "Parse/collection: identity, member/signature/body changes, ordering, globals, failure isolation and retry passed\n";
