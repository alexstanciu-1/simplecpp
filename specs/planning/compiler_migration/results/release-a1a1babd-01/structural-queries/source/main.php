<?php
declare(strict_types=1);
// <scpp-imports>
use function scpp\sequence_require_strings as sequence_require_strings;
use function scpp\fs_is_link as fs_is_link;
use function scpp\fs_is_dir as fs_is_dir;
use function scpp\fs_is_file as fs_is_file;
use function scpp\fs_size as fs_size;
use function scpp\fs_mtime as fs_mtime;
use function scpp\fs_scan as fs_scan;
use function scpp\json_quote as json_quote;
use function scpp\string_byte_from_int as string_byte_from_int;
use function scpp\enum_name as enum_name;
use function scpp\lock_empty as lock_empty;
use function scpp\lock_try as lock_try;
use function scpp\lock_release as lock_release;
use function scpp\lock_transfer as lock_transfer;
use function scpp\process_spawn as process_spawn;
use function scpp\process_poll as process_poll;
use function scpp\process_output as process_output;
use function scpp\process_stop as process_stop;
use function scpp\process_close as process_close;
use function scpp\sequence_map as sequence_map;
use function scpp\sequence_filter as sequence_filter;
use function scpp\keyed_map as keyed_map;
use function scpp\keyed_filter as keyed_filter;
use function scpp\string_byte_len as string_byte_len;
use function scpp\string_byte_starts_with as string_byte_starts_with;
use function scpp\string_byte_ends_with as string_byte_ends_with;
use function scpp\string_utf8_is_valid as string_utf8_is_valid;
use function scpp\string_codepoint_at as string_codepoint_at;
use function scpp\compat\substr as substr;
use function scpp\compat\strpos as strpos;
use function scpp\compat\strrpos as strrpos;
use function scpp\same_exception as same_exception;
use function scpp\string_byte_at as string_byte_at;
use function scpp\take_nullable as take_nullable;
use function scpp\take_false as take_false;
use function scpp\take_bool as take_bool;
use function scpp\compat\str_starts_with as str_starts_with;
use function scpp\compat\str_ends_with as str_ends_with;
use function scpp\compat\strlen as strlen;
use function scpp\string_byte_slice as string_byte_slice;
// </scpp-imports>

$query_tree = new \parse\Syntax_Tree();
$query_nodes /** vector<\parse\syntax_node> */ = [];
for ($query_index /** int */ = 0; $query_index < 18; ++$query_index) {
    $query_nodes[] = new \parse\syntax_node();
}
$query_tree->nodes = $query_nodes;
$query_tree->nodes[0]->kind = \parse\syntax_kind::struct_declaration;
$query_tree->nodes[0]->first_child_id = 2;
$query_tree->nodes[1]->kind = \parse\syntax_kind::name;
$query_tree->nodes[1]->next_sibling_id = 3;
$query_tree->nodes[2]->kind = \parse\syntax_kind::field_declaration;
$query_tree->nodes[2]->first_child_id = 4;
$query_tree->nodes[2]->next_sibling_id = 6;
$query_tree->nodes[3]->kind = \parse\syntax_kind::name;
$query_tree->nodes[3]->next_sibling_id = 5;
$query_tree->nodes[4]->kind = \parse\syntax_kind::variable_name;
$query_tree->nodes[5]->kind = \parse\syntax_kind::method_declaration;
$query_tree->nodes[5]->first_child_id = 7;
$query_tree->nodes[5]->next_sibling_id = 12;
$query_tree->nodes[6]->kind = \parse\syntax_kind::function_declaration;
$query_tree->nodes[6]->first_child_id = 8;
$query_tree->nodes[7]->kind = \parse\syntax_kind::name;
$query_tree->nodes[7]->next_sibling_id = 9;
$query_tree->nodes[8]->kind = \parse\syntax_kind::parameter_list;
$query_tree->nodes[8]->next_sibling_id = 10;
$query_tree->nodes[9]->kind = \parse\syntax_kind::name;
$query_tree->nodes[9]->next_sibling_id = 11;
$query_tree->nodes[10]->kind = \parse\syntax_kind::block;
$query_tree->nodes[11]->kind = \parse\syntax_kind::field_declaration;
$query_tree->nodes[11]->first_child_id = 13;
$query_tree->nodes[12]->kind = \parse\syntax_kind::name;
$query_tree->nodes[12]->next_sibling_id = 14;
$query_tree->nodes[13]->kind = \parse\syntax_kind::variable_name;
$query_tree->nodes[14]->kind = \parse\syntax_kind::template_declaration;
$query_tree->nodes[14]->first_child_id = 16;
$query_tree->nodes[15]->kind = \parse\syntax_kind::template_parameter_list;
$query_tree->nodes[15]->first_child_id = 17;
$query_tree->nodes[15]->next_sibling_id = 1;
$query_tree->nodes[16]->kind = \parse\syntax_kind::type_parameter_declaration;
$query_tree->nodes[16]->first_child_id = 18;
$query_tree->nodes[17]->kind = \parse\syntax_kind::name;
$query_cursor = \parse\Syntax_Access::struct_members($query_tree, 15, \parse\syntax_kind::field_declaration);
try { echo $query_cursor->current(); }
catch (\LogicException $query_error) { echo $query_error->getMessage(), "\n"; }
while ($query_cursor->advance()) { echo 'member:', $query_cursor->current(), ':', $query_cursor->current(), "\n"; }
if ($query_cursor->advance()) { throw new \LogicException('Cursor reopened'); }
$query_methods = \parse\Syntax_Access::struct_members($query_tree, 1, \parse\syntax_kind::method_declaration);
while ($query_methods->advance()) { echo 'method:', $query_methods->current(), "\n"; }
$query_view = \parse\Syntax_Access::function_parts($query_tree, 7);
echo 'function:', $query_view->name_id, ':', $query_view->parameters_id, ':', $query_view->return_type_id, ':', $query_view->body_id, "\n";
$query_field = \parse\Syntax_Access::field_declaration_parts($query_tree, 12);
echo 'field:', $query_field->type_syntax_id, ':', $query_field->variable_id, ':', $query_field->extent_id, "\n";
$query_type = \parse\Syntax_Access::type_syntax($query_tree, 13);
if ($query_type !== $query_tree->nodes[12]) { throw new \LogicException('Query copied node'); }
echo 'root:', \parse\Syntax_Access::place_root($query_tree, 14), ':', \parse\Syntax_Access::place_root($query_tree, 0), "\n";
echo 'parameter:', \parse\Syntax_Access::first_parameter($query_tree, 9), "\n";
$query_invalid = \parse\Syntax_Access::struct_members($query_tree, 0, \parse\syntax_kind::field_declaration);
echo "deferred\n";
try { $query_invalid->advance(); }
catch (\LogicException $query_error) { echo $query_error->getMessage(), "\n"; }
if ($query_invalid->advance()) { throw new \LogicException('Failed cursor reopened'); }
try { $query_invalid->current(); }
catch (\LogicException $query_error) { echo $query_error->getMessage(), "\n"; }
try { $query_wrong = \parse\Syntax_Access::function_parts($query_tree, 19); }
catch (\LogicException $query_error) { echo $query_error->getMessage(), "\n"; }
echo "query-tree-retained:", count($query_tree->nodes), ':', $query_tree->nodes[2]->next_sibling_id, "\n";
