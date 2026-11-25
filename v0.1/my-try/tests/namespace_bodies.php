<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

/** Inventory/lifecycle proof; explicit namespace grammar is deliberately not claimed. */
function namespace_body_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

Compiler_Lifecycle::reset();
$source = new file();
$source->path = 'namespace_bodies.phs';
$source->content = 'return 7; return 9;';
$parsed = (new Parser((new Tokenizer($source))->tokenize()))->parse();
$first = $parsed->root->single_body();
$first->body_scope()->set_parent(Model::$global_scope);
$second_scope = new scope();
$second_scope->set_parent(Model::$global_scope);
$parsed->scopes->append($second_scope);
$second = new namespace_body_node($second_scope);
$second->body->statements->append($first->body->statements[1]);
$first->body->statements->remove(1);
$bodies = $parsed->root->namespace_bodies;
$second_index = $bodies->append($second);
namespace_body_check(iterator_to_array($parsed->root->children()) === [$first, $second], 'File traversal flattened namespace bodies');
namespace_body_check($first->body_scope() !== $second->body_scope(), 'Source bodies merged local environments');
$failed = false;
try { $parsed->root->single_body(); }
catch (\LogicException $expected) { $failed = true; }
namespace_body_check($failed, 'Single-body adapter silently selected one of several bodies');

$failed = false;
try { (new File_Preparation($parsed->collection, Model::$language_scope))->prepare(); }
catch (\RuntimeException $expected) {
	$failed = str_contains($expected->getMessage(), 'only one namespace body');
}
namespace_body_check($failed, 'Preparation merged executable namespace bodies');
$second->body->statements = new Storage();
$second->body->syntax_changed = true;
$prepared = (new File_Preparation($parsed->collection, Model::$language_scope))->prepare();
namespace_body_check($prepared->executable_body() === $first->body, 'Wrong executable body selected');
$first_work = $first->body->work();
$second_work = $second->body->work();
namespace_body_check($first_work !== $second_work && Preparation_Status::complete($second_work), 'Bodies did not prepare independently');
$program = new cpp_program();
$generator = new CPP_Generator($program, new cpp_lowering_options(false));
$initial = CPP_Output::encode($generator->generate($prepared));

// Move execution to a later body: the emitter must consume analysis's selection.
$second->body->statements->append($first->body->statements[0]);
$first->body->statements = new Storage();
$first->body->syntax_changed = true;
$second->body->syntax_changed = true;
$prepared = (new File_Preparation($parsed->collection, Model::$language_scope))->prepare();
namespace_body_check($prepared->executable_body() === $second->body, 'Entry remained hardwired to the first body');
$moved = CPP_Output::encode($generator->generate($prepared));
namespace_body_check($moved === $initial, 'Moving the sole executable body changed generated output');

// Failure must invalidate a previously published entry selection even after bodies settle.
$extra_scope = new scope();
$extra_scope->set_parent(Model::$global_scope);
$parsed->scopes->append($extra_scope);
$extra = new namespace_body_node($extra_scope);
$literal = new integer_literal_node();
$literal->set_span(4, 5); // token 9 in the original source
$return_statement = new return_node();
$return_statement->set_span(3, 6);
$return_statement->expression = $literal;
$extra->body->statements->append($return_statement);
$extra_index = $bodies->append($extra);
$failed = false;
try { (new File_Preparation($parsed->collection, Model::$language_scope))->prepare(); }
catch (\RuntimeException $expected) { $failed = true; }
namespace_body_check($failed, 'Second executable body was accepted on update');
$failed = false;
try { $generator->generate($prepared); }
catch (\RuntimeException $expected) { $failed = true; }
namespace_body_check($failed, 'Saved preparation emitted after entry admission failed');

// Removed body syntax stays owned until analysis retires work, then frontend disposes it.
$retired = $parsed->root->retired_namespace_bodies;
$retired->append($second);
$retired->append($extra);
$bodies->remove($second_index);
$bodies->remove($extra_index);
$sources = new Storage();
$sources->append($parsed->collection);
$parsed->collection->parse_complete = false;
$failed = false;
try { Compiler_Lifecycle::retire_deleted_sources($sources, Model::$language_scope); }
catch (\RuntimeException $expected) { $failed = true; }
namespace_body_check($failed && $second->body->work() === $second_work && count($retired) === 2, 'Failed join disposed removed bodies');
$parsed->collection->parse_complete = true;
Compiler_Lifecycle::retire_deleted_sources($sources, Model::$language_scope);
namespace_body_check($second_work->change_status === change_state::deleted && $second->body->work() === null, 'Removed body work was not retired');
namespace_body_check($parsed->root->retired_namespace_bodies->is_empty(), 'Frontend retained retired namespace bodies');
$prepared = (new File_Preparation($parsed->collection, Model::$language_scope))->prepare();
$empty = CPP_Output::encode($generator->generate($prepared));
namespace_body_check(!isset($program->fragments[$second_work]), 'Removed entry fragment survived generation');
namespace_body_check($prepared->executable_body() === $first->body, 'Declaration-only entry lost its empty body');

if (isset($argv[1])) {
	file_put_contents($argv[1] . '/entry.json', $moved);
	file_put_contents($argv[1] . '/empty.json', $empty);
}
echo "Namespace bodies: inventory, entry selection, stale-output rejection and retirement passed\n";
