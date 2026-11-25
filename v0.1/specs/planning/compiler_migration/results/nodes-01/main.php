<?php
// <scpp-imports>
use function scpp\take_nullable as take_nullable;
use function scpp\take_false as take_false;
use function scpp\take_bool as take_bool;
use function scpp\string_byte_slice as string_byte_slice;
// </scpp-imports>

$coordinator = new \compile\Update_Context();
$worker = $coordinator;
echo $worker->full_rebuild ? "initial=1\n" : "initial=0\n";

// Both PHP class assignment and native shared handles retain the same decision.
$coordinator->full_rebuild = true;
echo $worker->full_rebuild ? "shared=1\n" : "shared=0\n";

// A new update owns a fresh decision, independent of the previous update.
$next = new \compile\Update_Context();
echo $next->full_rebuild ? "next=1\n" : "next=0\n";
echo $worker->full_rebuild ? "previous=1\n" : "previous=0\n";

// Rebinding one handle does not replace the object held by other consumers.
$coordinator = $next;
echo $coordinator->full_rebuild ? "rebound=1\n" : "rebound=0\n";
echo $worker->full_rebuild ? "retained=1\n" : "retained=0\n";

// Real tokenizer rows retain the adopted reference semantics in this slice.
$row = new \tokenize\token();
echo $row->kind === \tokenize\token_kind::invalid ? "default=1\n" : "default=0\n";
echo $row->start, ":", $row->length, "\n";
$alias = $row;
$row->kind = \tokenize\token_kind::identifier;
$row->start = 7;
$row->length = 3;
echo $alias->kind === \tokenize\token_kind::identifier ? "token-shared=1\n" : "token-shared=0\n";
echo $alias->start, ":", $alias->length, "\n";
$fresh = new \tokenize\token();
echo $fresh->kind !== $row->kind ? "independent=1\n" : "independent=0\n";

$state = \compile\step_status::created;
echo $state === \compile\step_status::created ? "created=1\n" : "created=0\n";
$state = \compile\step_status::finished;
echo $state !== \compile\step_status::failed ? "finished=1\n" : "finished=0\n";
echo \read_sources\Source_Path_Syntax::join("a", "b"), "\n";
echo \read_sources\Source_Path_Syntax::join("a/", "b"), "\n";
echo \read_sources\Source_Path_Syntax::join("", "x"), "\n";
echo \read_sources\Source_Path_Syntax::join("/", "x"), "\n";
echo \read_sources\Source_Path_Syntax::join("a", ""), "\n";
echo \read_sources\Source_Path_Syntax::is_absolute("", false) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("", true) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("/", false) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("/", true) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("x", false) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("x", true) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("C:", false) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("C:", true) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("C:/x", false) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("C:/x", true) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("C:\\x", false) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("C:\\x", true) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("\\server\\x", false) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("\\server\\x", true) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("/a", false) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("/a", true) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("a/b", false) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("a/b", true) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("C:x", false) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("C:x", true) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_source("a.phs") ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_source(".phs") ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_source("a.php") ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_source("a.PHS") ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_source("a.phs/x") ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_source("") ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("é:/x", true) ? "1\n" : "0\n";
echo \read_sources\Source_Path_Syntax::is_absolute("C:/é", true) ? "1\n" : "0\n";
echo string_byte_slice("abcdef", 2, 99), ":", string_byte_slice("abc", 9, 1), ":", string_byte_slice("abc", 0, 0), "\n";
echo strlen(string_byte_slice("é", 1, 1)) === 1 ? "byte=1\n" : "byte=0\n";

echo strlen("éé"), ":", strlen("é"), ":", strlen("\xC3\xA9"), "\n";

$root_node = new \parse\syntax_node();
echo $root_node->kind === \parse\syntax_kind::invalid ? "node-default=1\n" : "node-default=0\n";
echo $root_node->start, ":", $root_node->length, ":", $root_node->first_child_id, ":", $root_node->next_sibling_id, "\n";
$root_node->kind = \parse\syntax_kind::file_root;
$root_node->first_child_id = 2;
$child_node = new \parse\syntax_node();
$child_node->kind = \parse\syntax_kind::function_declaration;
$child_node->start = 12;
$child_node->length = 9;
$child_node->next_sibling_id = 3;
$reader_node = $child_node;
echo $reader_node->start, ":", $reader_node->length, ":", $reader_node->next_sibling_id, "\n";
$child_node->length = 10;
echo $reader_node->length, ":", $root_node->first_child_id, "\n";
echo $root_node->kind !== $child_node->kind ? "node-independent=1\n" : "node-independent=0\n";
