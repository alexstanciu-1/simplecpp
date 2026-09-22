<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/parsing_support.php';
require_once __DIR__ . '/../support/body_support.php';

use Body_Test_Stages as Check;
use parse\Syntax_Access as Syntax;
use parse\Syntax_Comparer;
use parse\syntax_kind;
use tokenize\token_kind;

final class Metaprogramming_Parsing_Test
{
    /** Follow the existing sibling chain and expose source spellings for order-sensitive assertions. */
    public static function spellings(\parse\File_Frontend $file, int $first): array
    {
        $texts = [];
        for ($id = $first; $id !== 0; $id = $file->syntax->nodes[$id - 1]->next_sibling_id) {
            $texts[] = Parsing_Test::text($file, $id);
        }
        return $texts;
    }

    /** Require an application with the exact target and ordered argument spellings, without resolving names. */
    public static function application(\parse\File_Frontend $file, int $id, string $name, array $arguments): void
    {
        $parts = Syntax::template_application_parts($file->syntax, $id);
        Check::check((Parsing_Test::text($file, $parts->name_id) === $name)
            && (self::spellings($file, $parts->first_argument_id) === $arguments), 'Application preserves target, argument count and order');
    }
}

// Token spelling and ordering are independent of provider/type knowledge.
$lexical = 'template<typename T1, typename T2> const constexpr consteval if else true false hash<T1,list<T2>>';
$tokens = \tokenize\File_Tokenizer::tokenize(new \read_sources\Source_Buffer(4, 'meta.phs', 0, $lexical));
$kinds = array_map(static fn($token) => $token->kind, $tokens->rows);
Check::check(($kinds[0] === token_kind::template_keyword) && ($kinds[1] === token_kind::left_angle)
    && ($kinds[2] === token_kind::typename_keyword) && (array_slice($kinds, -3) === [token_kind::right_angle, token_kind::right_angle, token_kind::end_of_file])
    && (count(array_filter($kinds, static fn($kind) => $kind === token_kind::boolean_literal)) === 2),
    'Keywords, booleans and adjacent closing angles have explicit tokens');

// One application shape handles single/multiple/mixed/nested arguments in every annotation location.
$file = Parsing_Test::parse(<<<'PHS'
struct holder { public hash<T1, list<T2>> $items; }
function select($items hash<T1, T2>): list<hash<T1, T2>>
{
    $copy hash<T1, T2> = new hash<T1, T2>();
    return choose<T1, T2, N + 1>($items, constant_value());
}
PHS);
$tree = $file->syntax;
$record = Syntax::struct_parts($tree, $file->defined_entities[0]);
$field = Syntax::field_declaration_parts($tree, $record->first_member_id);
Metaprogramming_Parsing_Test::application($file, $field->type_syntax_id, 'hash', ['T1', 'list<T2>']);
$function = Syntax::function_parts($tree, $file->defined_entities[1]);
$parameter = Syntax::parameter_parts($tree, Syntax::first_parameter($tree, $function->parameters_id));
Metaprogramming_Parsing_Test::application($file, $parameter->type_syntax_id, 'hash', ['T1', 'T2']);
Metaprogramming_Parsing_Test::application($file, $function->return_type_id, 'list', ['hash<T1, T2>']);
$first = $tree->nodes[$function->body_id - 1]->first_child_id;
$local = Syntax::local_declaration_parts($tree, $first);
Metaprogramming_Parsing_Test::application($file, $local->type_syntax_id, 'hash', ['T1', 'T2']);
$construction = $tree->nodes[$local->initializer_id - 1];
Metaprogramming_Parsing_Test::application($file, $construction->first_child_id, 'hash', ['T1', 'T2']);
$return = $tree->nodes[$first - 1]->next_sibling_id;
$call = Syntax::statement_expression($tree, $return);
Metaprogramming_Parsing_Test::application($file, Syntax::call_target($tree, $call), 'choose', ['T1', 'T2', 'N + 1']);
Check::check(Metaprogramming_Parsing_Test::spellings($file, Syntax::first_argument($tree, $call)) === ['$items', 'constant_value()'],
    'Template arguments and ordinary call arguments have separate ordered ranges');
Parsing_Test::parse('$v another_family<int, 2, true, "bytes", compute<Other>(N + 1)>;');

// Definitions retain parameter kinds, ordinary wrapped declarations and both forms of compile-time selection.
$source = <<<'PHS'
template<typename T1, typename T2, int N>
struct dictionary { public hash<T1, T2> $items; }
template<typename T, int N>
constexpr function choose($value T): T
{
    const LOCAL: int = N + 1;
    if constexpr (N) {
        return $value;
    }
    else if constexpr (false) {
        return unavailable<T>();
    }
    else {
        return $value;
    }
}
const COUNT: int = 1 + 2;
const ENABLED = true;
consteval function compute(): int { return COUNT; }
constexpr function context($x int): int
{
    if consteval {
        return compute();
    }
    else {
        return runtime_only($x);
    }
}
PHS;
$meta = Parsing_Test::parse($source);
$meta->validate();
$tree = $meta->syntax;
$before = serialize($meta);
$template = Syntax::template_parts($tree, $meta->defined_entities[0]);
$ids = array_keys(Parsing_Test::children($meta, $template->parameters_id));
Check::check(count($ids) === 3, 'Template declarations have arbitrary ordered arity');
foreach ($ids as $index => $id) {
    $parts = Syntax::template_parameter_parts($tree, $id);
    Check::check((Parsing_Test::text($meta, $parts->name_id) === ['T1', 'T2', 'N'][$index])
        && (($parts->type_syntax_id === 0) === ($index < 2)), 'Parameter kind and name remain structural facts');
}
$template = Syntax::template_parts($tree, $meta->defined_entities[1]);
$function = Syntax::function_parts($tree, Syntax::evaluated_function($tree, $template->declaration_id));
$first = $tree->nodes[$function->body_id - 1]->first_child_id;
$local_constant = Syntax::constant_parts($tree, $first);
Check::check(Parsing_Test::text($meta, $local_constant->initializer_id) === 'N + 1', 'Local constants retain unevaluated ordinary expressions');
$conditional = $tree->nodes[$first - 1]->next_sibling_id;
$branches = Syntax::control_parts($tree, $conditional);
Check::check(($tree->nodes[$conditional - 1]->kind === syntax_kind::constexpr_if_statement)
    && ($tree->nodes[$branches->alternative - 1]->kind === syntax_kind::constexpr_if_statement)
    && str_contains(Parsing_Test::text($meta, $branches->alternative), 'unavailable<T>()'), 'Else-if and unselected dependent code remain in the source AST');
$typed_constant = Syntax::constant_parts($tree, $meta->defined_entities[2]);
$inferred_constant = Syntax::constant_parts($tree, $meta->defined_entities[3]);
Check::check(($typed_constant->type_syntax_id !== 0) && ($inferred_constant->type_syntax_id === 0)
    && ($tree->nodes[$inferred_constant->initializer_id - 1]->kind === syntax_kind::boolean_literal), 'Constants distinguish optional annotation from initializer');
Syntax::function_parts($tree, Syntax::evaluated_function($tree, $meta->defined_entities[4]));
$function = Syntax::function_parts($tree, Syntax::evaluated_function($tree, $meta->defined_entities[5]));
$conditional = $tree->nodes[$function->body_id - 1]->first_child_id;
$branches = Syntax::control_parts($tree, $conditional);
Check::check(($tree->nodes[$conditional - 1]->kind === syntax_kind::consteval_if_statement) && ($branches->condition === 0)
    && str_contains(Parsing_Test::text($meta, $branches->alternative), 'runtime_only'), 'Consteval context selection has two bodies and no invented condition expression');
Check::check(str_contains($meta->to_json(), '"text":"true"') && (serialize($meta) === $before), 'Exports expose literal spelling without changing syntax');

// Comparison sees argument order, constant values and conditional/specifier kinds; whitespace is immaterial.
$spaced = Parsing_Test::parse(str_replace('typename', '/* parameter */ typename', $source));
Check::check(Syntax_Comparer::equal($meta, $tree->root_node_id, $spaced, $spaced->syntax->root_node_id), 'Comments preserve logical equality');
foreach ([['hash<T1, T2>', 'hash<T2, T1>'], ['N + 1', 'N + 2'], ['const ENABLED = true', 'const ENABLED = false'],
    ['if constexpr (N)', 'if (N)'], ['constexpr function choose', 'consteval function choose']] as [$from, $to]) {
    $changed = Parsing_Test::parse(str_replace($from, $to, $source));
    Check::check(!Syntax_Comparer::equal($meta, $tree->root_node_id, $changed, $changed->syntax->root_node_id), 'Every meaningful metaprogramming change participates in syntax comparison');
}

// Both deep nesting and wide argument lists use linear flat storage and iterative expression continuations.
$depth = 2000;
$deep = Parsing_Test::parse('$v ' . str_repeat('list<', $depth) . 'int' . str_repeat('>', $depth) . ';');
Check::check(count($deep->syntax->nodes) === (2 * $depth + 5), 'Nested applications allocate one target and one application per level');
$wide = Parsing_Test::parse('$v family<' . implode(',', array_fill(0, 1000, 'int')) . '>;');
Check::check(count($wide->syntax->nodes) === 1006, 'Argument lists do not require an arity-specific representation');

// Source errors identify malformed delimiters and declarations before producing a frontend.
foreach (['$v hash<int,>;' => 'Expected literal', '$v list<>;' => 'at least one', '$v hash<int string>;' => "'>'",
    '$v list<list<int>;' => "'>'", 'template<> struct x {}' => 'type name',
    'template<typename> struct x {}' => 'parameter name', 'template<typename T,> struct x {}' => 'type name',
    'template<typename T = int> struct x {}' => 'defaults', 'template<typename T> return 0;' => 'struct or function',
    'const N;' => "'='", 'const N: = 1;' => 'type name', 'consteval struct x {}' => "'function'",
    'if consteval (true) {}' => "'{'", 'if constexpr {}' => "'('", 'if consteval {} else if (1) {}' => "'{'",
] as $invalid => $reason) {
    $error = Check::rejects(static fn() => Parsing_Test::parse($invalid), $reason);
    Check::check(($error instanceof \diagnostics\Source_Error) && ($error->path === 'parser-test.phs'), 'Malformed metaprogramming reports source syntax, not an internal error');
}
$invalid = '$v hash<int,>;';
Parsing_Test::reject($invalid, strpos($invalid, '>'), 'Expected literal');
$bad = clone $tree;
$wrapper = $meta->defined_entities[0];
$parts = Syntax::template_parts($tree, $wrapper);
$bad->nodes[$parts->parameters_id - 1] = clone $bad->nodes[$parts->parameters_id - 1];
$bad->nodes[$parts->parameters_id - 1]->first_child_id = 0;
Parsing_Test::reject_access(static fn() => Syntax::template_parts($bad, $wrapper));

// Structural views reject malformed roles without mutating their source snapshots.
$record = Syntax::struct_parts($tree, $parts->declaration_id);
$field = Syntax::field_declaration_parts($tree, $record->first_member_id);
$application = Syntax::template_application_parts($tree, $field->type_syntax_id);
$typed = $meta->defined_entities[2];
$typed_parts = Syntax::constant_parts($tree, $typed);
$evaluated = $meta->defined_entities[4];
foreach ([
    [$application->name_id, 'kind', syntax_kind::integer_literal, 'template_application_parts', $field->type_syntax_id],
    [$application->name_id, 'next_sibling_id', 0, 'template_application_parts', $field->type_syntax_id],
    [$typed_parts->type_syntax_id, 'next_sibling_id', $typed_parts->initializer_id, 'constant_parts', $typed],
    [$typed_parts->name_id, 'next_sibling_id', 0, 'constant_parts', $typed],
    [Syntax::evaluated_function($tree, $evaluated), 'next_sibling_id', $typed, 'evaluated_function', $evaluated],
] as [$node, $property, $value, $method, $root]) {
    $bad = clone $tree;
    $bad->nodes[$node - 1] = clone $bad->nodes[$node - 1];
    $bad->nodes[$node - 1]->$property = $value;
    Parsing_Test::reject_access(static fn() => Syntax::$method($bad, $root));
}
Check::check(serialize($meta) === $before, 'Rejected structural views preserve the retained AST');

// Full parse, independent workers and one real source edit use the ordinary frontend phases and joins.
$root = getcwd() . '/metaprogramming-parse';
mkdir($root);
file_put_contents($root . '/project.json', json_encode(['source_folders' => ['.'], 'entry' => 'main.phs'], JSON_THROW_ON_ERROR));
file_put_contents($root . '/main.phs', '$items hash<int, list<int>>;');
file_put_contents($root . '/definitions.phs', $source);
$manifest = Step_Test::run(new \read_manifest\Manifest_Reader($root . '/project.json'));
$sources = Step_Test::run(new \read_sources\Source_Discovery($manifest, new \read_sources\Source_Set()));
$update = new \compile\Update_Context();
$update->full_rebuild = true;
$lexical = \compile\Phases::run_tokenization($sources, new \tokenize\Token_Set(), $update);
$parsed = \compile\Phases::run_parsing($lexical->sources, $lexical->tokens, new \parse\Frontend_Set(), $update);
$retained = serialize([$lexical, $parsed]);
$tasks = array_map(static fn($file) => $lexical->tokens->for_file($file->id), $lexical->sources->files);
$results = array_map(static fn($task) => (new \parse\File_Parser($task))->parse(), array_reverse($tasks));
$joined = (new \parse\Frontend_Join(new \parse\Frontend_Set(), $lexical->sources, $lexical->tokens, $tasks))->join($results);
Check::check($joined->to_json() === $parsed->to_json(), 'Reversed fixed parser tasks produce deterministic joined exports');
Check::edit($root . '/main.phs', '$items hash<int, list<string>>;');
$update = new \compile\Update_Context();
$sources = Step_Test::run(new \read_sources\Source_Discovery($manifest, $lexical->sources));
$next_lexical = \compile\Phases::run_tokenization($sources, $lexical->tokens, $update);
$next = \compile\Phases::run_parsing($next_lexical->sources, $next_lexical->tokens, $parsed, $update);
$main = $sources->entry_file()->id;
foreach ($sources->files as $file) {
    Check::check(($next->for_file($file->id) === $parsed->for_file($file->id)) === ($file->id !== $main),
        'One source edit replaces its frontend and retains unselected definitions');
}
Check::check((serialize([$lexical, $parsed]) === $retained) && (!$update->full_rebuild), 'Incremental parsing keeps old snapshots unchanged');

// Parsed syntax is not a claim of semantic support; public compilation must reject each unsupported boundary.
foreach (['constexpr function f(): int { return 1; }',
    'consteval function f(): int { return 1; }', 'const N = 1;', '$v hash<int, int>;',
    'struct holder { public hash<int, int> $value; }', 'function f($x hash<int, int>): int { return 1; }',
    'function f(): hash<int, int> { return; }', 'new hash<int, int>();', 'f<int>(1);', 'return N;',
    'if constexpr (true) { return 1; } else { return 0; }', 'if consteval { return 1; } else { return 0; }',
    'function f(): int { const N = 1; return N; }', 'if (1) {} else if (1) {}'] as $unsupported)
{
    file_put_contents($root . '/unsupported.phs', $unsupported);
    $session = new \compile\Compiler_Session();
    $error = Check::rejects(static fn() => $session->compile($root . '/unsupported.phs'), '');
    Check::check(($error instanceof \diagnostics\Source_Error) && ($session->published === null),
        'Unsupported metaprogramming cannot silently compile or become an internal protocol failure: ' . $unsupported);
}
echo "metaprogramming parsing ok: general multi-argument applications, declarations/constants/context syntax, deep/wide inputs, exports, fixed workers, one replacement and semantic rejection\n";
