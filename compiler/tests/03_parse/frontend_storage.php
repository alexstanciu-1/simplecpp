<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/bootstrap.php';

class Frontend_Storage_Test
{
    public static function check_storage(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    // A complete named-function fixture using the ordinary block/return nodes.
    public static function append_function_fixture(\parse\Syntax_Tree $tree): int
    {
        $base = count($tree->nodes);
        $node = new \parse\syntax_node();
        $node->kind = \parse\syntax_kind::function_declaration;
        $node->first_child_id = $base + 2;
        $tree->nodes[] = $node;
        $node = new \parse\syntax_node();
        $node->kind = \parse\syntax_kind::name;
        $node->next_sibling_id = $base + 3;
        $tree->nodes[] = $node;
        $node = new \parse\syntax_node();
        $node->kind = \parse\syntax_kind::parameter_list;
        $node->next_sibling_id = $base + 4;
        $tree->nodes[] = $node;
        $node = new \parse\syntax_node();
        $node->kind = \parse\syntax_kind::name;
        $node->next_sibling_id = $base + 5;
        $tree->nodes[] = $node;
        $node = new \parse\syntax_node();
        $node->kind = \parse\syntax_kind::block;
        $node->first_child_id = $base + 6;
        $tree->nodes[] = $node;
        $node = new \parse\syntax_node();
        $node->kind = \parse\syntax_kind::return_statement;
        $node->first_child_id = $base + 7;
        $tree->nodes[] = $node;
        $node = new \parse\syntax_node();
        $node->kind = \parse\syntax_kind::integer_literal;
        $tree->nodes[] = $node;
        $function_id = $base + 1;
        return $function_id;
    }
}

// Storage proof only: construct a tree directly, without pretending to parse.

$tokens = new \tokenize\Token_Buffer(new \read_sources\Source_Buffer(1, "storage.phs", 0, ""));
$item = new \tokenize\token();
$item->kind = \tokenize\token_kind::integer_literal;
$item->start = 7;
$item->length = 70000;
$tokens->rows[] = clone $item;
$item->length = 2;
$tokens->rows[] = clone $item;
Frontend_Storage_Test::check_storage((int)$tokens->rows[0]->length === 70000, "cloned token rows must be independent");
Frontend_Storage_Test::check_storage((int)$tokens->rows[1]->length === 2, "token append must preserve the new value");

$tree = new \parse\Syntax_Tree();
$tree->source_file_id = 1;
$file = new \parse\File_Frontend($tokens, $tree);
$file->source_file_id = 1;
$file->entry_body_id = 1;
$node = new \parse\syntax_node();
$node->kind = \parse\syntax_kind::block;
$node->first_child_id = 2;
$tree->nodes[] = $node;
$node = new \parse\syntax_node();
$node->kind = \parse\syntax_kind::return_statement;
$node->first_child_id = 3;
$node->next_sibling_id = 4;
$tree->nodes[] = $node;
$node = new \parse\syntax_node();
$node->kind = \parse\syntax_kind::integer_literal;
$node->start = 7;
$node->length = 2;
$tree->nodes[] = $node;
$node = new \parse\syntax_node();
$node->kind = \parse\syntax_kind::return_statement;
$node->first_child_id = 5;
$tree->nodes[] = $node;
$node = new \parse\syntax_node();
$node->kind = \parse\syntax_kind::call_expression;
$node->first_child_id = 6;
$tree->nodes[] = $node;
$node = new \parse\syntax_node();
$node->kind = \parse\syntax_kind::name;
$tree->nodes[] = $node;

$first_entity_id = Frontend_Storage_Test::append_function_fixture($tree);
$second_entity_id = Frontend_Storage_Test::append_function_fixture($tree);
$file->defined_entities[] = $first_entity_id;
$file->defined_entities[] = $second_entity_id;

// Force vector growth after linking; IDs must still identify the original rows.
$i = 0;
while ($i < 4096) {
    $tree->nodes[] = clone $node;
    $i = $i + 1;
}
$root = $tree->nodes[(int)$file->entry_body_id - 1];
Frontend_Storage_Test::check_storage($root->kind === \parse\syntax_kind::block, "file entry must use an ordinary body block");
$statement_id = $root->first_child_id;
$statements = 0;
while ((int)$statement_id !== 0)
{
    $statement = $tree->nodes[(int)$statement_id - 1];
    Frontend_Storage_Test::check_storage($statement->kind === \parse\syntax_kind::return_statement, "statement order must follow sibling IDs");
    $value = $tree->nodes[(int)$statement->first_child_id - 1];
    if ($statements === 0) {
        Frontend_Storage_Test::check_storage($value->kind === \parse\syntax_kind::integer_literal, "first return must reference its literal");
    }
    else {
        Frontend_Storage_Test::check_storage($value->kind === \parse\syntax_kind::call_expression, "second return must reference its call");
        $callee = $tree->nodes[(int)$value->first_child_id - 1];
        Frontend_Storage_Test::check_storage($callee->kind === \parse\syntax_kind::name, "nested expression must reference its callee");
    }
    $statement_id = $statement->next_sibling_id;
    $statements = $statements + 1;
}
Frontend_Storage_Test::check_storage($statements === 2, "body traversal must handle multiple statements");

Frontend_Storage_Test::check_storage(count($file->defined_entities) === 2, "file must retain multiple top-level definitions");
Frontend_Storage_Test::check_storage(($file->defined_entities[0] === $first_entity_id) && ($file->defined_entities[1] === $second_entity_id), "entity order must survive syntax-vector growth");
foreach ($file->defined_entities as $entity_id)
{
    Frontend_Storage_Test::check_storage($entity_id !== $file->entry_body_id, "implicit entry must be separate from named entities");
    $entity = $tree->nodes[(int)$entity_id - 1];
    Frontend_Storage_Test::check_storage($entity->kind === \parse\syntax_kind::function_declaration, "entity IDs must identify declarations");
    $name = $tree->nodes[(int)$entity->first_child_id - 1];
    $parameters = $tree->nodes[(int)$name->next_sibling_id - 1];
    $return_type = $tree->nodes[(int)$parameters->next_sibling_id - 1];
    $body = $tree->nodes[(int)$return_type->next_sibling_id - 1];
    Frontend_Storage_Test::check_storage($body->kind === $root->kind, "named functions and file entry must share the block representation");
    $statement = $tree->nodes[(int)$body->first_child_id - 1];
    Frontend_Storage_Test::check_storage($statement->kind === \parse\syntax_kind::return_statement, "function body must retain its statements");
}

$other = new \parse\Syntax_Tree();
$other->source_file_id = 2;
Frontend_Storage_Test::check_storage(count($other->nodes) === 0, "file tables must have separate storage");
$declarations_only = new \parse\File_Frontend(new \tokenize\Token_Buffer(new \read_sources\Source_Buffer(2, "other.phs", 0, "")), $other);
$declarations_only->source_file_id = 2;
$declarations_only->entry_body_id = 1;
$node = new \parse\syntax_node();
$node->kind = \parse\syntax_kind::block;
$other->nodes[] = $node;
$empty_entry = $other->nodes[(int)$declarations_only->entry_body_id - 1];
Frontend_Storage_Test::check_storage((count($declarations_only->defined_entities) === 0) && ((int)$empty_entry->first_child_id === 0), "empty file must retain an empty entry block");
$declarations_only->defined_entities[] = Frontend_Storage_Test::append_function_fixture($other);
Frontend_Storage_Test::check_storage((int)$other->nodes[(int)$declarations_only->entry_body_id - 1]->first_child_id === 0, "adding a declaration must leave entry statements empty");
Frontend_Storage_Test::check_storage((count($file->defined_entities) === 2) && (count($declarations_only->defined_entities) === 1), "file entity lists must have separate storage");

// Scalar byte spans copy with the node; no nested allocation/deep clone is needed.
$copy = clone $tree->nodes[2];
$copy->start = 999;
Frontend_Storage_Test::check_storage($tree->nodes[2]->start === 7, "copied spans must not mutate the baseline");

// Resolution storage proof: node IDs are interpreted through one exact owner AST.
// Each result owns lookup while sharing its immutable binding records.
$binding = new \resolve_symbols\symbol_binding(1, 200);
$resolution = new \resolve_symbols\Symbol_Resolution(100, $tree, [$binding]);
$other_resolution = new \resolve_symbols\Symbol_Resolution(101, $other,
    [new \resolve_symbols\symbol_binding(1, 201)]);
Frontend_Storage_Test::check_storage(($resolution->target_for(1) === 200)
    && ($other_resolution->target_for(1) === 201) && ($resolution->bindings[0] === $binding),
    'Owned lookup retains exact records and keeps AST-local node IDs separate');
Frontend_Storage_Test::check_storage(($resolution->syntax->source_file_id === 1)
    && ($other_resolution->syntax->source_file_id === 2)
    && ($resolution->syntax->nodes[$resolution->bindings[0]->use_node_id - 1] === $root),
    "Same local node ID must be interpreted within its result's AST");
$replacement_tree = clone $tree;
$replacement_tree->nodes = [];
Frontend_Storage_Test::check_storage(($resolution->syntax === $tree) && ($resolution->syntax !== $replacement_tree)
    && ($resolution->bindings[0]->target_symbol_id === 200),
    "Bindings retain exact AST identity and independent target rows across replacement");
echo "frontend storage ok: record clones, long tokens, independent file tables and linked traversal\n";

// Failed manifest preparation must not publish a fabricated compiler result.
$session = new \compile\Compiler_Session();
$blocked = false;
try {
    $session->compile("__missing_project_manifest__.json");
}
catch (Exception $exception) {
    $blocked = true;
}
Frontend_Storage_Test::check_storage($blocked, "failed preparation must not report success");
Frontend_Storage_Test::check_storage((int)$session->generation === 0, "failed preparation must not publish a generation");
Frontend_Storage_Test::check_storage($session->published === null, "failed preparation must leave published sources intact");
echo "failed manifest preparation preserves session state\n";
