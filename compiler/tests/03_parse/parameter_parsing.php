<?php
declare(strict_types=1);
require_once __DIR__ . '/../support/parsing_support.php';
require_once __DIR__ . '/../support/body_support.php';

use parse\Syntax_Access as Syntax;
use parse\syntax_kind;
use parse\Syntax_Comparer;
use parse\File_Parser;
use compile\Phases;
use compile\Update_Context;
use collect_symbols\Declaration_Collector;
use collect_symbols\Symbol_Comparer;
use collect_symbols\change_status;

class Parameter_Parsing_Test extends Body_Test_Stages
{
    /** Compare parsed declarations through the same symbol comparison used for incremental selection. */
    public static function compare_definition(\parse\File_Frontend $old, \parse\File_Frontend $new): \collect_symbols\symbol_change
    {
        $left = new \collect_symbols\symbol_record(1, $old);
        $left->kind = \collect_symbols\symbol_kind::function_symbol;
        $left->name = 'choose';
        $left->declaration_node_id = $old->defined_entities[0];
        $left->body_node_id = Syntax::function_parts($old->syntax, $left->declaration_node_id)->body_id;
        $right = new \collect_symbols\symbol_record(1, $new);
        $right->kind = $left->kind;
        $right->name = $left->name;
        $right->declaration_node_id = $new->defined_entities[0];
        $right->body_node_id = Syntax::function_parts($new->syntax, $right->declaration_node_id)->body_id;
        return \collect_symbols\Comparison_Worker::compare(new \collect_symbols\symbol_change($left, $right, change_status::uncompared));
    }
}

$text = 'function choose($first Named, $second Other): Named { return nested($first, inner(10, $second)); }';
$file = Parsing_Test::parse($text);
$parts = Syntax::function_parts($file->syntax, $file->defined_entities[0]);
$params = Parsing_Test::children($file, $parts->parameters_id);
Parameter_Parsing_Test::check((count($params) === 2) && (Syntax::first_parameter($file->syntax, $parts->parameters_id) === array_key_first($params)),
    'Typed parameters are ordered children of the existing list');
foreach (array_keys($params) as $i => $id) {
    $parameter = Syntax::parameter_parts($file->syntax, $id);
    Parameter_Parsing_Test::check(($params[$id]->kind === syntax_kind::parameter_declaration)
        && (Parsing_Test::text($file, $parameter->variable_id) === ['$first', '$second'][$i])
        && (Parsing_Test::text($file, $parameter->type_syntax_id) === ['Named', 'Other'][$i])
        && (Parsing_Test::text($file, $id) === ['$first Named', '$second Other'][$i]), 'Parameter roles refer to exact source spans');
}
$return_id = $file->syntax->nodes[$parts->body_id - 1]->first_child_id;
$call_id = Syntax::statement_expression($file->syntax, $return_id);
$first_arg = Syntax::first_argument($file->syntax, $call_id);
$second_arg = $file->syntax->nodes[$first_arg - 1]->next_sibling_id;
Parameter_Parsing_Test::check((Parsing_Test::text($file, Syntax::call_target($file->syntax, $call_id)) === 'nested')
    && (Parsing_Test::text($file, $first_arg) === '$first') && (Parsing_Test::text($file, $second_arg) === 'inner(10, $second)')
    && ($file->syntax->nodes[$second_arg - 1]->next_sibling_id === 0),
    'Call children preserve target, first argument and nested-call argument in source order');
Parameter_Parsing_Test::check(Parsing_Test::text($file, Syntax::first_argument($file->syntax, $second_arg)) === '10', 'Nested arguments use the same expression path');
$empty = Parsing_Test::parse('function empty(): void { empty(); }');
$ep = Syntax::function_parts($empty->syntax, $empty->defined_entities[0]);
$ec = Syntax::statement_expression($empty->syntax, $empty->syntax->nodes[$ep->body_id - 1]->first_child_id);
Parameter_Parsing_Test::check((Syntax::first_parameter($empty->syntax, $ep->parameters_id) === 0)
    && (Syntax::first_argument($empty->syntax, $ec) === 0), 'Empty lists retain the original node shape');
$export = json_decode($file->to_json(), true, 512, JSON_THROW_ON_ERROR);
Parameter_Parsing_Test::check((count(array_filter($export['nodes'], static fn($n) => $n['kind'] === 'parameter_declaration')) === 2)
    && (count($file->defined_entities) === 1), 'Debug exports contain real parameter nodes, without extra project definitions');
$fixed = serialize($file);
$space = Parsing_Test::parse(str_replace(',', ', /* gap */ ', $text));
Parameter_Parsing_Test::check(Syntax_Comparer::equal($file, $file->defined_entities[0], $space, $space->defined_entities[0]), 'Whitespace/comments do not change argument or parameter meaning');
foreach ([str_replace('$first Named, $second Other', '$second Other, $first Named', $text),
        str_replace('$first Named', '$renamed Named', $text), str_replace('Other', 'Changed', $text),
        str_replace(', $second Other', '', $text)] as $source) {
    $changed = Parsing_Test::parse($source);
    $comparison = Parameter_Parsing_Test::compare_definition($file, $changed);
    Parameter_Parsing_Test::check(($comparison->own_status === change_status::changed) && ($comparison->children_changed === false),
        'Parameter order/name/type/add-remove changes belong to the callable definition');
}
$changed = Parsing_Test::parse(str_replace('inner(10, $second)', 'inner($second, 10)', $text));
$comparison = Parameter_Parsing_Test::compare_definition($file, $changed);
Parameter_Parsing_Test::check(($comparison->own_status === change_status::unchanged) && ($comparison->children_changed === true),
    'Argument expression/order changes belong to the executable body');
$bad = clone $file->syntax;
$parameter_id = array_key_first($params);
$parameter = Syntax::parameter_parts($bad, $parameter_id);
$bad->nodes[$parameter->type_syntax_id - 1] = clone $bad->nodes[$parameter->type_syntax_id - 1];
$bad->nodes[$parameter->type_syntax_id - 1]->next_sibling_id = $parameter->variable_id;
Parsing_Test::reject_access(static fn() => Syntax::parameter_parts($bad, $parameter_id));
Parsing_Test::reject_access(static fn() => Syntax::first_parameter($file->syntax, $parts->body_id));
Parsing_Test::reject_access(static fn() => Syntax::first_argument($file->syntax, $parameter_id));
Parameter_Parsing_Test::check(serialize($file) === $fixed, 'Comparison/accessors and malformed probes cannot mutate retained syntax');

foreach ([['f(1 2);', 4, "')' after call"], ['f(,1);', 2, 'Expected literal'], ['f(1,);', 4, 'Expected literal'],
        ['f(inner(1),);', 11, 'Expected literal'], ['f(1;', 3, "')' after call"],
        ['function f($x): int {}', 13, 'parameter type'], ['function f($x int,): int {}', 18, 'variable name'],
        ['function f($x int = 1): int {}', 18, 'defaults'], ['function f($x int $y int): int {}', 18, "')' after parameters"],
        ['function f(int $x): int {}', 15, "'&' in a reference"]] as [$source, $offset, $reason]) {
    Parsing_Test::reject($source, $offset, $reason);
}

// The parser records syntax, leaving duplicate bindings and unknown names/types for later stages.
Parsing_Test::parse('function f($x Missing, $x Other): Missing { return missing($unknown); }');
$wide = Parsing_Test::parse('function many(' . implode(',', array_map(static fn($i) => '$p' . $i . ' Named', range(1, 1000))) . '): Named { return many('
    . implode(',', array_map(static fn($i) => '$p' . $i, range(1, 1000))) . '); }');
Parameter_Parsing_Test::check(count($wide->syntax->nodes) === 4010, 'Large parameter/argument lists produce linear rows without copied lists');
$deep = Parsing_Test::parse('return ' . str_repeat('f(', 256) . '42' . str_repeat(')', 256) . ';');
Parameter_Parsing_Test::check(count($deep->syntax->nodes) === 516, 'Nested argument expressions retain flat nodes with unique containment');

// Real file reads and private workers use the resident frontend replacement path.
$manifest = '../fixtures/three_files/project.json';
$session = new \compile\Compiler_Session();
$baseline = $session->compile($manifest);
$before = serialize($baseline);
$main = $baseline->inputs->manifest->directory . '/src/main.phs';
$value = $baseline->inputs->manifest->directory . '/src/nested/value.phs';
$original_main = file_get_contents($main);
$original_value = file_get_contents($value);
$main_id = $baseline->inputs->sources->find_file_id($main);
Parameter_Parsing_Test::edit($main, 'return value(1, answer());');
Parameter_Parsing_Test::edit($value, 'function value($x int, $y int): int { return $x; }');
$update = new Update_Context();
$sources = \Step_Test::run(new \read_sources\Source_Discovery($baseline->inputs->manifest, $baseline->inputs->sources));
$lexical = Phases::run_tokenization($sources, $baseline->inputs->tokens, $update);
$value_id = $baseline->inputs->sources->find_file_id($value);
$tasks = [$lexical->tokens->for_file($main_id), $lexical->tokens->for_file($value_id)];
$inputs_before = serialize([$lexical, $tasks]);
$results = array_map(static fn($task) => (new File_Parser($task))->parse(), array_reverse($tasks));
$join = new \parse\Frontend_Join($baseline->inputs->frontends, $lexical->sources, $lexical->tokens, $tasks);
$join->merge($results, 0, 1);
Parameter_Parsing_Test::rejects(static fn() => $join->finish(), 'Incomplete');
$join->merge($results, 1, 1);
$parsed = $join->finish();
$selected = Phases::run_parsing($lexical->sources, $lexical->tokens, $baseline->inputs->frontends, $update);
Parameter_Parsing_Test::check($selected->to_json() === $parsed->to_json(), 'Phase selection equals the explicit changed-file batch');
foreach ($lexical->sources->files as $file) {
    Parameter_Parsing_Test::check(($selected->for_file($file->id) !== $baseline->inputs->frontends->for_file($file->id))
        === in_array($file->id, [$main_id, $value_id], true), 'Only edited parameter/argument files replace their AST');
}
Parameter_Parsing_Test::check((serialize([$lexical, $tasks]) === $inputs_before) && (serialize($baseline) === $before), 'Segmented joins and reversed workers preserve fixed inputs');
$warm = Phases::run_parsing($lexical->sources, $lexical->tokens, $parsed, $update);
foreach ($lexical->sources->files as $file) {
    Parameter_Parsing_Test::check($warm->for_file($file->id) === $parsed->for_file($file->id), 'Unchanged parameter/argument syntax shares frontend results');
}
$full = new Update_Context();
$full->full_rebuild = true;
Parameter_Parsing_Test::check(Phases::run_parsing($lexical->sources, $lexical->tokens, $parsed, $full)->to_json() === $parsed->to_json(), 'Full selection uses the same parsing path');
$catalog = \Step_Test::run(new Symbol_Comparer(\Step_Test::run(new Declaration_Collector($baseline->symbols->current, $lexical->sources, $parsed, false)), false));
Parameter_Parsing_Test::check(count($catalog->current->records()) === count($baseline->symbols->current->records()), 'Parameters are callable children, not project symbols');
foreach ($catalog->changes as $change) {
    Parameter_Parsing_Test::check($change->own_status === ($change->current->name === 'value' ? change_status::changed : change_status::unchanged),
        'Project collection/comparison preserves definition versus body boundaries');
}
Parameter_Parsing_Test::rejects(static fn() => $session->compile($manifest), 'Call argument count does not match the resolved signature');
Parameter_Parsing_Test::edit($value, $original_value);
$error = Parameter_Parsing_Test::rejects(static fn() => $session->compile($manifest), 'Call argument count does not match the resolved signature');
Parameter_Parsing_Test::check(($error instanceof \diagnostics\Source_Error) && ($error->path === $main) && ($error->start === 13),
    'The body-checking boundary rejects the first actual argument with a source diagnostic');
Parameter_Parsing_Test::edit($main, $original_main);

// Even an unused parameter on an otherwise valid function must not disappear.
Parameter_Parsing_Test::edit($value, 'function value($x int): int { return 42; }');
$error = Parameter_Parsing_Test::rejects(static fn() => $session->compile($manifest), 'Call argument count does not match the resolved signature');
Parameter_Parsing_Test::check(($error instanceof \diagnostics\Source_Error) && str_ends_with($error->path, '/src/answer.phs')
    && (serialize($baseline) === $before) && ($session->observed?->inputs === $baseline->inputs), 'Missing argument rejection preserves the accepted session');
Parameter_Parsing_Test::edit($value, 'function value($x int,): int { return 42; }');
Parameter_Parsing_Test::rejects(static fn() => $session->compile($manifest), 'variable name');
Parameter_Parsing_Test::edit($value, $original_value);
$repair = $session->compile($manifest);
Parameter_Parsing_Test::check(($repair->llvm->ir_by_file() === $baseline->llvm->ir_by_file()) && (serialize($baseline) === $before), 'Syntax and semantic failures repair through the original compiler pipeline');
echo "parameter parsing ok: ordered typed parameters/arguments, nesting, flat storage, roles, spans, comparison, fixed workers, reuse, full selection and honest semantic boundaries\n";
