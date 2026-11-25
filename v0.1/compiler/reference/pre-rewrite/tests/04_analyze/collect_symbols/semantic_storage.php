<?php
declare(strict_types=1);

require_once __DIR__ . '/../../support/bootstrap.php';

use collect_symbols\change_status;
use collect_symbols\symbol_change;
use collect_symbols\symbol_kind;
use collect_symbols\symbol_record;
use collect_symbols\Symbol_Refresh;
use parse\File_Frontend;
use parse\Frontend_Set;
use parse\File_Parser;
use read_sources\Source_Buffer;
use tokenize\Tokenizer;

class Semantic_Storage_Test
{
    public static function check(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    public static function parse(string $text): File_Frontend
    {
        return (new File_Parser(\tokenize\File_Tokenizer::tokenize(new Source_Buffer(1, 'symbols.phs', 0, $text))))->parse();
    }

    // Storage fixture over real parsed syntax, not declaration collection or resolution.
    public static function function_record(File_Frontend $file): symbol_record
    {
        $symbol = new symbol_record(10, $file);
        $symbol->kind = symbol_kind::function_symbol;
        $symbol->name = 'answer';
        $symbol->declaration_node_id = $file->defined_entities[0];
        $id = $file->syntax->nodes[$symbol->declaration_node_id - 1]->first_child_id;
        while ($id !== 0) {
            $node = $file->syntax->nodes[$id - 1];
            if ($node->kind === \parse\syntax_kind::block) {
                $symbol->body_node_id = $id;
            }
            $id = $node->next_sibling_id;
        }
        return $symbol;
    }
}

$previous = Semantic_Storage_Test::parse('function answer(): int { return 42; }');
$current = Semantic_Storage_Test::parse('function answer(): int { return 43; }');
$before = serialize($previous);
$old_symbol = Semantic_Storage_Test::function_record($previous);
$new_symbol = Semantic_Storage_Test::function_record($current);
$refresh = new Symbol_Refresh();
$refresh->changes[] = new symbol_change($old_symbol, $new_symbol, change_status::unchanged, children_changed: true);

Semantic_Storage_Test::check(($old_symbol->body_node_id === $new_symbol->body_node_id)
    && ($old_symbol->frontend !== $new_symbol->frontend)
    && ($old_symbol->frontend->syntax === $previous->syntax)
    && ($new_symbol->frontend->syntax === $current->syntax),
    'Equal local node IDs must retain different exact snapshots without copying syntax');
Semantic_Storage_Test::check(($refresh->changes[0]->own_status === change_status::unchanged)
    && ($refresh->changes[0]->children_changed === true),
    'A body edit is a child change; own definition status remains separate');

// Even identical syntax reparsed into a new snapshot may need new bindings.
$same = Semantic_Storage_Test::function_record(Semantic_Storage_Test::parse('function answer(): int { return 42; }'));
$unchanged = new symbol_change($old_symbol, $same, change_status::unchanged, children_changed: false);
Semantic_Storage_Test::check(($unchanged->children_changed === false)
    && ($old_symbol->frontend !== $same->frontend),
    'No logical change must not imply valid old AST bindings');

$entry = new symbol_record(11, $current);
$entry->kind = symbol_kind::file_entry;
$entry->body_node_id = $current->entry_body_id;
Semantic_Storage_Test::check(($entry->name === '') && ($entry->declaration_node_id === 0)
    && ($entry->frontend->syntax->nodes[$entry->body_node_id - 1]
        === $current->syntax->nodes[$current->entry_body_id - 1]),
    'Implicit entry points directly to its ordinary body with no invented declaration/name');

$added = new symbol_change(null, $entry, change_status::added);
$removed = new symbol_change($old_symbol, null, change_status::removed);
$refresh->changes[] = $added;
$refresh->changes[] = $removed;
Semantic_Storage_Test::check(($removed->previous === $old_symbol) && ($removed->current === null)
    && ($removed->previous->frontend === $previous) && ($added->previous === null),
    'Removals retain old origins; additions have only a current origin');

foreach ([
        [null, null, change_status::unchanged],
        [$old_symbol, $entry, change_status::changed],
        [null, $new_symbol, change_status::changed],
        [$old_symbol, null, change_status::unchanged],
        [$old_symbol, $new_symbol, change_status::removed],
    ] as $invalid)
{
    $rejected = false;
    try {
        new symbol_change(...$invalid);
    }
    catch (InvalidArgumentException $error) {
        $rejected = true;
    }
    Semantic_Storage_Test::check($rejected, 'Reject mismatched identities or impossible status/presence combinations');
}

$update = new \compile\Update_Context();
$catalog_before = serialize($refresh->changes);
$update->full_rebuild = true;
Semantic_Storage_Test::check((serialize($refresh->changes) === $catalog_before) && (serialize($previous) === $before),
    'Full selection and candidate records must not rewrite the change catalog or accepted syntax');

// Root/index consistency is checked when complete file results join.
new Frontend_Set([$current]);
$invalid = clone $current;
$invalid->defined_entities = [];
$rejected = false;
try {
    new Frontend_Set([$invalid]);
}
catch (Exception $error) {
    $rejected = true;
}
Semantic_Storage_Test::check($rejected, 'A definition index cannot silently omit root children');
$invalid = clone $current;
$invalid->syntax = clone $current->syntax;
$invalid->syntax->root_node_id = $current->entry_body_id;
$rejected = false;
try {
    new Frontend_Set([$invalid]);
}
catch (Exception $error) {
    $rejected = true;
}
Semantic_Storage_Test::check($rejected, 'Entry block is not a valid file root');

echo "semantic storage ok: exact syntax references, implicit entry, change/work separation, removal origins, invalid records and root/index consistency; storage contracts verified\n";
