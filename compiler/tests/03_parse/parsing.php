<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/parsing_support.php';

use parse\File_Frontend;
use parse\syntax_kind;
use parse\Syntax_Access;
use tokenize\Tokenizer;
use read_sources\Source_Buffer;

$text = "/* prefix */\nreturn first();\nfunction first(): Custom { ping(); return 42; return; }\nsecond();\nfunction second(): Other {}\nreturn 7; // tail\n";
$file = Parsing_Test::parse($text);
Parsing_Test::check(count($file->defined_entities) === 2, 'Collect multiple definitions separately');
$statements = Parsing_Test::children($file, $file->entry_body_id);
Parsing_Test::check(array_map(static fn($node) => $node->kind, array_values($statements)) ===
    [syntax_kind::return_statement, syntax_kind::expression_statement, syntax_kind::return_statement],
    'Interleaved definitions do not enter or interrupt entry statement order');
foreach ($file->defined_entities as $index => $id)
{
    $parts = Parsing_Test::children($file, $id);
    Parsing_Test::check(array_map(static fn($node) => $node->kind, array_values($parts)) ===
        [syntax_kind::name, syntax_kind::parameter_list, syntax_kind::name, syntax_kind::block], 'Function has ordered name/parameters/type/body children');
    $ids = array_keys($parts);
    $view = Syntax_Access::function_parts($file->syntax, $id);
    Parsing_Test::check([$view->name_id, $view->parameters_id, $view->return_type_id, $view->body_id] === $ids,
        'Function roles reference the existing ordered children');
    Parsing_Test::check((Parsing_Test::text($file, $ids[0]) === ['first', 'second'][$index])
        && (Parsing_Test::text($file, $ids[2]) === ['Custom', 'Other'][$index])
        && (Parsing_Test::text($file, $ids[1]) === '()'), 'Names and parameter-list spans come from source');
    $body = Parsing_Test::children($file, $ids[3]);
    Parsing_Test::check(count($body) === [3, 0][$index], 'Function and entry bodies use ordinary ordered statements');
    if ($index === 0) {
        $body_ids = array_keys($body);
        Parsing_Test::check((Parsing_Test::text($file, $body_ids[1]) === 'return 42;')
            && ($body[$body_ids[2]]->first_child_id === 0), 'Return spans include semicolon; bare return has no expression');
        Parsing_Test::check(Syntax_Access::statement_expression($file->syntax, $body_ids[2]) === 0,
            'Bare return has no expression through structural access');
    }
}
$entry_ids = array_keys($statements);
$value_id = array_key_first(Parsing_Test::children($file, $entry_ids[0]));
Parsing_Test::check(($file->syntax->nodes[$value_id - 1]->kind === syntax_kind::call_expression)
    && (Parsing_Test::text($file, $value_id) === 'first()'), 'Return contains a real call expression');
$name_id = array_key_first(Parsing_Test::children($file, $value_id));
Parsing_Test::check(Parsing_Test::text($file, $name_id) === 'first', 'Call references its callee name');
Parsing_Test::check((Syntax_Access::call_target($file->syntax, $value_id) === $name_id)
    && (Syntax_Access::statement_expression($file->syntax, $entry_ids[0]) === $value_id)
    && ($file->syntax->nodes[Syntax_Access::statement_expression($file->syntax, $entry_ids[1]) - 1]->kind === syntax_kind::call_expression),
    'Call targets and statement expressions retain exact source node identities');
$before = serialize($file);
Parsing_Test::reject_access(static fn() => Syntax_Access::function_parts($file->syntax, $file->entry_body_id));
Parsing_Test::reject_access(static fn() => Syntax_Access::call_target($file->syntax, $name_id));
Parsing_Test::reject_access(static fn() => Syntax_Access::statement_expression($file->syntax, $value_id));

// Reject malformed child roles/counts without modifying the real parser result.
$bad_tree = clone $file->syntax;
$bad_tree->nodes[$name_id - 1] = clone $bad_tree->nodes[$name_id - 1];
$bad_tree->nodes[$name_id - 1]->first_child_id = $value_id;
Parsing_Test::reject_access(static fn() => Syntax_Access::call_target($bad_tree, $value_id));
$bad_tree = clone $file->syntax;
$bad_tree->nodes[$value_id - 1] = clone $bad_tree->nodes[$value_id - 1];
$bad_tree->nodes[$value_id - 1]->next_sibling_id = $name_id;
Parsing_Test::reject_access(static fn() => Syntax_Access::statement_expression($bad_tree, $entry_ids[0]));
$bad_tree = clone $file->syntax;
$definition_id = $file->defined_entities[0];
$bad_tree->nodes[$definition_id - 1] = clone $bad_tree->nodes[$definition_id - 1];
$bad_tree->nodes[$definition_id - 1]->first_child_id = $file->entry_body_id;
Parsing_Test::reject_access(static fn() => Syntax_Access::function_parts($bad_tree, $definition_id));
$export = json_decode($file->to_json(), true, 512, JSON_THROW_ON_ERROR);
Parsing_Test::check(($export['defined_entities'] === $file->defined_entities)
    && ($export['root_node_id'] === $file->syntax->root_node_id)
    && ($export['nodes'][$name_id - 1]['text'] === 'first') && (serialize($file) === $before),
    'Debug AST exports actual roots, links, spans and leaf text without mutation');

foreach (['', "// empty\n", 'function only(): int {}'] as $text) {
    $file = Parsing_Test::parse($text);
    Parsing_Test::check(($file->syntax->nodes[$file->entry_body_id - 1]->kind === syntax_kind::block)
        && (Parsing_Test::children($file, $file->entry_body_id) === []), 'Empty/declaration-only files still have an empty entry body');
}
$file = Parsing_Test::parse(str_repeat("return 123;\n", 5000));
Parsing_Test::check((count(Parsing_Test::children($file, $file->entry_body_id)) === 5000)
    && (count($file->syntax->nodes) === 10002), 'Flat links and loops survive large statement lists');
$file = Parsing_Test::parse('return ' . str_repeat('9', 70000) . ';');
Parsing_Test::check($file->syntax->nodes[2]->length === 70000, 'Parsing preserves integer spelling without host integer conversion');

$text = '$count int = 42; { $copy Other = $count; $count = fetch(); { return $copy; } } $count; 7;'
    . ' function worker(): Result { $answer Named = get(); $answer = 9; return $answer; }';
$file = Parsing_Test::parse($text);
$ids = array_keys(Parsing_Test::children($file, $file->entry_body_id));
Parsing_Test::check(array_map(static fn($node) => $node->kind, array_values(Parsing_Test::children($file, $file->entry_body_id)))
    === [syntax_kind::local_declaration, syntax_kind::block, syntax_kind::expression_statement, syntax_kind::expression_statement],
    'Local declarations, blocks and expressions stay in the ordered entry body');
Parsing_Test::check(count($file->defined_entities) === 1, 'Local declarations never enter the top-level definition index');
$local = Syntax_Access::local_declaration_parts($file->syntax, $ids[0]);
Parsing_Test::check(([$local->variable_id, $local->type_syntax_id, $local->initializer_id]
        === array_keys(Parsing_Test::children($file, $ids[0])))
    && (Parsing_Test::text($file, $local->variable_id) === '$count')
    && (Parsing_Test::text($file, $local->type_syntax_id) === 'int')
    && (Parsing_Test::text($file, $local->initializer_id) === '42')
    && (Parsing_Test::text($file, $ids[0]) === '$count int = 42;'), 'Declaration roles are source-backed node IDs; spans include the semicolon');
$nested = array_keys(Parsing_Test::children($file, $ids[1]));
$copy = Syntax_Access::local_declaration_parts($file->syntax, $nested[0]);
$assignment = Syntax_Access::assignment_parts($file->syntax, $nested[1]);
Parsing_Test::check((Parsing_Test::text($file, $copy->initializer_id) === '$count')
    && ($file->syntax->nodes[$copy->initializer_id - 1]->kind === syntax_kind::variable_name)
    && (Parsing_Test::text($file, $assignment->target_id) === '$count')
    && (Parsing_Test::text($file, $assignment->value_id) === 'fetch()')
    && ([$assignment->target_id, $assignment->value_id] === array_keys(Parsing_Test::children($file, $nested[1]))),
    'Variable reads and assignment target/value roles use the same flat tree');
$return = array_key_first(Parsing_Test::children($file, $nested[2]));
Parsing_Test::check((Parsing_Test::text($file, Syntax_Access::statement_expression($file->syntax, $return)) === '$copy')
    && (Parsing_Test::text($file, Syntax_Access::statement_expression($file->syntax, $ids[2])) === '$count'),
    'Nested returns and expression statements reference variable leaves');
$function = Syntax_Access::function_parts($file->syntax, $file->defined_entities[0]);
$function_statements = array_keys(Parsing_Test::children($file, $function->body_id));
Parsing_Test::check((Parsing_Test::text($file, Syntax_Access::local_declaration_parts($file->syntax, $function_statements[0])->initializer_id) === 'get()')
    && (Parsing_Test::text($file, Syntax_Access::assignment_parts($file->syntax, $function_statements[1])->value_id) === '9'),
    'Named functions and the entry use the same local grammar');
$before = serialize($file);
$export = json_decode($file->to_json(), true, 512, JSON_THROW_ON_ERROR);
Parsing_Test::check(($export['nodes'][$local->variable_id - 1]['text'] === '$count')
    && ($export['nodes'][$local->type_syntax_id - 1]['text'] === 'int')
    && (serialize($file) === $before), 'Exports include variable spelling without changing the AST');
Parsing_Test::reject_access(static fn() => Syntax_Access::local_declaration_parts($file->syntax, $ids[1]));
Parsing_Test::reject_access(static fn() => Syntax_Access::assignment_parts($file->syntax, $ids[0]));
foreach ([[$local->variable_id, 'kind', syntax_kind::name],
        [$local->initializer_id, 'next_sibling_id', $local->variable_id], [$local->initializer_id, 'kind', syntax_kind::block]] as [$id, $property, $value]) {
    $bad = clone $file->syntax;
    $bad->nodes[$id - 1] = clone $bad->nodes[$id - 1];
    $bad->nodes[$id - 1]->$property = $value;
    Parsing_Test::reject_access(static fn() => Syntax_Access::local_declaration_parts($bad, $ids[0]));
}
// Assignment views require expression roles; whether the target is writable belongs to binding/checking.
foreach ([[$assignment->target_id, 'kind', syntax_kind::block], [$assignment->target_id, 'first_child_id', $local->variable_id],
        [$assignment->target_id, 'next_sibling_id', 0], [$assignment->value_id, 'next_sibling_id', $local->variable_id]] as [$id, $property, $value]) {
    $bad = clone $file->syntax;
    $bad->nodes[$id - 1] = clone $bad->nodes[$id - 1];
    $bad->nodes[$id - 1]->$property = $value;
    Parsing_Test::reject_access(static fn() => Syntax_Access::assignment_parts($bad, $nested[1]));
}
Parsing_Test::check(serialize($file) === $before, 'Invalid-role probes leave retained syntax untouched');

// These are structurally valid; visibility, declaration identity and types are later decisions.
Parsing_Test::parse('$x Unknown = $x; $x Different = missing(); { $x int = $not_declared; }');
foreach (['{}', '{{}}', 'function empty(): void { {} }'] as $empty_block) {
    Parsing_Test::parse($empty_block);
}
$deep = Parsing_Test::parse(str_repeat('{', 128) . '$x int = 42; return $x;' . str_repeat('}', 128));
Parsing_Test::check(count($deep->syntax->nodes) === 136, 'Nested blocks remain flat rows without per-scope copies');

Parsing_Test::reject('return 42', 9, "';'");
Parsing_Test::reject('return ();', 8, 'Expected literal');
Parsing_Test::parse('return name;');
Parsing_Test::parse('f(1);');
Parsing_Test::reject('function f(x): int {}', 12, "'&' in a reference");
Parsing_Test::reject('function f() {}', 13, "':' before return type");
Parsing_Test::reject('function f(): int {', 19, "'}'");
Parsing_Test::reject('function f(): int { function g(): int {} }', 20, 'Nested function');
Parsing_Test::reject('return 1; }', 10, 'Expected return');
// Default construction is a semantic capability; parsing preserves an absent initializer.
$default = Parsing_Test::parse('$x Unknown;');
$default_id = $default->syntax->nodes[$default->entry_body_id - 1]->first_child_id;
Parsing_Test::check(Syntax_Access::local_declaration_parts($default->syntax, $default_id)->initializer_id === 0,
    'Default construction preserves an absent initializer for later type checking');


Parsing_Test::reject('$x int = ;', 9, 'Expected literal');
Parsing_Test::reject('$x = ;', 5, 'Expected literal');
Parsing_Test::reject('$x = 42', 7, "';'");
Parsing_Test::reject('$x int = 42 }', 12, "';'");
Parsing_Test::reject('$x = $y = 42;', 8, "';'");
Parsing_Test::reject('return $x = 42;', 10, "';'");
Parsing_Test::reject('42 = $x;', 3, "';'");
Parsing_Test::reject('f() = $x;', 4, "';'");
Parsing_Test::reject('{ function f(): int {} }', 2, 'Nested function');
Parsing_Test::reject('{ $x int = 1;', 13, "'}'");
Parsing_Test::reject('$x int float = 1;', 7, "'='");
Parsing_Test::reject('function (): int {}', 9, 'function name');
Parsing_Test::reject('function f(): 42 {}', 14, 'return-type name');
echo "parsing ok: file definitions/entry bodies, ordered statements, call/return trees, spans, large flat trees, exports, purity and anchored syntax errors\n";
