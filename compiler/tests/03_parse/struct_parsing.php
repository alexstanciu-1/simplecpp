<?php
declare(strict_types=1);
require_once __DIR__ . '/../support/parsing_support.php';
require_once __DIR__ . '/../support/body_support.php';

use Body_Test_Stages as Check;
use parse\Syntax_Access as Syntax;
use parse\Syntax_Comparer;
use parse\syntax_kind;

// Syntax views preserve node identities and validate roles without performing type resolution.
$text = 'struct pair { public uint8 $tag; public int32 $count; }';
$file = Parsing_Test::parse($text);
$tree = $file->syntax;
$id = $file->defined_entities[0];
$before = serialize($file);
$parts = Syntax::struct_parts($tree, $id);
Check::check(Parsing_Test::text($file, $parts->name_id) === 'pair', 'Struct name retains its source span');
$field_ids = [];
for ($field = $parts->first_member_id; $field !== 0; $field = $tree->nodes[$field - 1]->next_sibling_id) {
    $field_ids[] = $field;
}
Check::check(count($field_ids) === 2, 'Ordered fields remain in the existing sibling chain');
foreach ($field_ids as $index => $field) {
    $view = Syntax::field_declaration_parts($tree, $field);
    Check::check((Parsing_Test::text($file, $view->type_syntax_id) === ['uint8', 'int32'][$index])
        && (Parsing_Test::text($file, $view->variable_id) === ['$tag', '$count'][$index]), 'Field views expose exact type/name roles');
}
$empty = Parsing_Test::parse('struct empty {}');
Check::check(Syntax::struct_parts($empty->syntax, $empty->defined_entities[0])->first_member_id === 0,
    'Empty syntax is representable; rejecting empty values belongs to semantic resolution');
$spaced = Parsing_Test::parse(str_replace('public', '/* field */ public', $text));
Check::check(Syntax_Comparer::equal($file, $id, $spaced, $spaced->defined_entities[0]), 'Field comments preserve logical declaration equality');
foreach ([str_replace('uint8', 'uint32', $text), str_replace('$tag', '$other', $text),
        'struct pair { public int32 $count; public uint8 $tag; }'] as $changed) {
    $other = Parsing_Test::parse($changed);
    Check::check(!Syntax_Comparer::equal($file, $id, $other, $other->defined_entities[0]),
        'Field type, name and order contribute to definition changes');
}

// Malformed trees must fail at the shared syntax boundary, without mutating retained input.
$bad = clone $tree;
$bad->nodes[$parts->name_id - 1] = clone $bad->nodes[$parts->name_id - 1];
$bad->nodes[$parts->name_id - 1]->kind = syntax_kind::integer_literal;
Check::rejects(static fn() => Syntax::struct_parts($bad, $id), 'name leaf');
$bad = clone $tree;
$bad->nodes[$parts->first_member_id - 1] = clone $bad->nodes[$parts->first_member_id - 1];
$bad->nodes[$parts->first_member_id - 1]->kind = syntax_kind::parameter_declaration;
Check::rejects(static fn() => Syntax::struct_parts($bad, $id), 'struct field');
Check::rejects(static fn() => Syntax::field_declaration_parts($tree, $id), 'field declaration');
$field = $field_ids[0];
$view = Syntax::field_declaration_parts($tree, $field);
$bad = clone $tree;
$bad->nodes[$view->variable_id - 1] = clone $bad->nodes[$view->variable_id - 1];
$bad->nodes[$view->variable_id - 1]->next_sibling_id = $parts->name_id;
Check::rejects(static fn() => Syntax::field_declaration_parts($bad, $field), 'Unexpected field declaration child');
Check::check(serialize($file) === $before, 'Queries, comparisons and rejected syntax preserve the retained tree');
echo "struct parsing ok: shared field roles, ordered declarations, logical changes, malformed shapes and snapshot purity\n";
