<?php
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
echo string_byte_len(string_byte_slice("é", 1, 1)) === 1 ? "byte=1\n" : "byte=0\n";

echo string_byte_len("éé"), ":", string_byte_len("é"), ":", string_byte_len("\xC3\xA9"), "\n";

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
$function_parts = new \parse\function_parts(1, 2, 3, 4);
echo $function_parts->name_id, ":", $function_parts->parameters_id, ":", $function_parts->return_type_id, ":", $function_parts->body_id, "\n";
$local_declaration_parts = new \parse\local_declaration_parts(5, 6, 7);
echo $local_declaration_parts->variable_id, ":", $local_declaration_parts->type_syntax_id, ":", $local_declaration_parts->initializer_id, "\n";
$assignment_parts = new \parse\assignment_parts(8, 9);
echo $assignment_parts->target_id, ":", $assignment_parts->value_id, "\n";
$control_parts = new \parse\control_parts(1, 2, 0);
echo $control_parts->condition, ":", $control_parts->body, ":", $control_parts->alternative, "\n";
$struct_parts = new \parse\struct_parts(2, 3);
echo $struct_parts->name_id, ":", $struct_parts->first_member_id, "\n";
$field_declaration_parts = new \parse\field_declaration_parts(4, 5);
echo $field_declaration_parts->type_syntax_id, ":", $field_declaration_parts->variable_id, ":", $field_declaration_parts->extent_id, "\n";
$template_parts = new \parse\template_parts(6, 7);
echo $template_parts->parameters_id, ":", $template_parts->declaration_id, "\n";
$template_parameter_parts = new \parse\template_parameter_parts(8, 0);
echo $template_parameter_parts->name_id, ":", $template_parameter_parts->type_syntax_id, "\n";
$template_application_parts = new \parse\template_application_parts(9, 10);
echo $template_application_parts->name_id, ":", $template_application_parts->first_argument_id, "\n";
$constant_parts = new \parse\constant_parts(11, 12, 13);
echo $constant_parts->name_id, ":", $constant_parts->type_syntax_id, ":", $constant_parts->initializer_id, "\n";
$parameter_absent = new \parse\parameter_parts(1, 2);
echo $parameter_absent->reference === null ? "reference-absent=1\n" : "reference-absent=0\n";
$parameter_borrow = new \parse\parameter_parts(1, 2, \parse\syntax_kind::reference_annotation);
echo $parameter_borrow->reference !== null ? "reference-present=1\n" : "reference-present=0\n";
$extended_field = new \parse\field_declaration_parts(4, 5, 6);
echo $extended_field->extent_id, "\n";
$reference_kind = \parse\syntax_kind::invalid;
echo take_nullable($reference_kind, $parameter_borrow->reference) ? "reference-taken=1\n" : "reference-taken=0\n";
echo $reference_kind === \parse\syntax_kind::reference_annotation ? "reference-kind=1\n" : "reference-kind=0\n";

$cursor = new \parse\expression_cursor();
echo $cursor->context === \parse\expression_context::value ? "cursor-default=1\n" : "cursor-default=0\n";
echo $cursor->node_id, ":", $cursor->last_child_id, ":", count($cursor->operands), ":", count($cursor->operators), "\n";
$cursor->operands[] = 10;
$cursor->operands[] = 20;
$cursor->operators[] = \parse\syntax_kind::addition_expression;
$cursor_alias = $cursor;
$cursor->last_child_id = 21;
echo $cursor_alias->last_child_id, ":", $cursor_alias->operands[1], ":", count($cursor_alias->operators), "\n";
echo $cursor->operators[0] === \parse\syntax_kind::addition_expression ? "operator=1\n" : "operator=0\n";
$operands_copy = $cursor->operands;
$operands_copy[0] = 90;
echo $cursor->operands[0], ":", $operands_copy[0], "\n";
$other_cursor = new \parse\expression_cursor(\parse\expression_context::call_arguments, 30, 31);
echo $other_cursor->node_id, ":", $other_cursor->last_child_id, ":", count($other_cursor->operands), "\n";
$empty_operands /** vector<int> */ = [];
$cursor->operands = $empty_operands;
echo count($cursor->operands), ":", count($operands_copy), "\n";

$source_snapshot = new \read_sources\Source_Buffer(1, "/source.phs", 100, "aé\n");
echo $source_snapshot->source_file_id, ":", $source_snapshot->path, ":", $source_snapshot->mtime, ":", string_byte_len($source_snapshot->content), "\n";
$source_alias = $source_snapshot;
$revised_snapshot = new \read_sources\Source_Buffer(1, "/source.phs", 100, "changed");
echo $source_alias === $source_snapshot ? "snapshot-shared=1\n" : "snapshot-shared=0\n";
echo $revised_snapshot !== $source_snapshot ? "snapshot-version=1\n" : "snapshot-version=0\n";
echo string_byte_slice($source_snapshot->content, 1, 2) === "é" ? "snapshot-bytes=1\n" : "snapshot-bytes=0\n";
$binary_content = string_byte_slice("é", 1, 1);
$binary_snapshot = new \read_sources\Source_Buffer(2, "/binary.phs", 100, $binary_content);
echo string_byte_len($binary_snapshot->content), "\n";
$tree = new \parse\Syntax_Tree();
echo $tree->source_file_id, ":", $tree->root_node_id, ":", count($tree->nodes), "\n";
$tree->source_file_id = $source_snapshot->source_file_id;
$tree->root_node_id = 1;
$node = new \parse\syntax_node();
$node->kind = \parse\syntax_kind::file_root;
$tree->nodes[] = $node;
$rows_copy = $tree->nodes;
$rows_copy[0]->length = 4;
echo $tree->nodes[0]->length, ":", $node->length, "\n";
$replacement = new \parse\syntax_node();
$replacement->length = 9;
$rows_copy[0] = $replacement;
echo $tree->nodes[0]->length, ":", $rows_copy[0]->length, "\n";
$rows_copy[] = $replacement;
echo count($tree->nodes), ":", count($rows_copy), "\n";
$same_content_snapshot = new \read_sources\Source_Buffer(1, "/source.phs", 100, "aé\n");
echo $same_content_snapshot !== $source_snapshot ? "snapshot-distinct=1\n" : "snapshot-distinct=0\n";
echo \check_bodies\Decimal_Range::fits_positive("1", 0) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("1", 1) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("2", 1) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("9", 3) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("10", 4) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("127", 7) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("128", 7) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("129", 7) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("255", 8) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("256", 8) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("257", 8) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("65535", 16) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("65536", 16) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("65537", 16) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("2147483647", 31) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("2147483648", 31) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("2147483649", 31) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("4294967295", 32) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("4294967296", 32) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("4294967297", 32) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("9223372036854775807", 63) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("9223372036854775808", 63) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("9223372036854775809", 63) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("18446744073709551615", 64) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("18446744073709551616", 64) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("18446744073709551617", 64) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("170141183460469231731687303715884105727", 127) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("170141183460469231731687303715884105728", 127) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("170141183460469231731687303715884105729", 127) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("340282366920938463463374607431768211455", 128) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("340282366920938463463374607431768211456", 128) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("340282366920938463463374607431768211457", 128) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("115792089237316195423570985008687907853269984665640564039457584007913129639935", 256) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("115792089237316195423570985008687907853269984665640564039457584007913129639936", 256) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("115792089237316195423570985008687907853269984665640564039457584007913129639937", 256) ? "1\n" : "0\n";
echo \check_bodies\Decimal_Range::fits_positive("999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999", 64) ? "1\n" : "0\n";
echo string_byte_at("é", 0), ":", string_byte_at("é", 1), ":", string_byte_at("", 0), ":", string_byte_at("a", -1), "\n";

try {
    $invalid_config = new \prepare_backend\backend_configuration("", "triple", "layout", "", "", "abi", "runtime");
    echo "guard-failed\n";
} catch (\InvalidArgumentException $error) {
    echo $error->getMessage(), "\n";
}
$config = new \prepare_backend\backend_configuration("backend", "triple", "layout", "", "", "abi", "runtime");
echo $config->backend_key, ":", $config->runtime_key, "\n";
$cause = new \InvalidArgumentException("cause", 7);
try {
    throw new \RuntimeException("outer", 9, $cause);
} catch (\LogicException $wrong_family) {
    echo "wrong-family\n";
} catch (\Exception $error) {
    echo $error->getMessage(), ":", $error->getCode(), "\n";
    $previous = $error->getPrevious();
    echo same_exception($previous, $cause) ? "cause-shared=1\n" : "cause-shared=0\n";
}
try {
    try {
        throw $cause;
    } catch (\LogicException $inner) {
        echo $inner->getMessage(), ":", $inner->getCode(), "\n";
        throw $inner;
    }
} catch (\Throwable $outer) {
    echo same_exception($outer, $cause) ? "rethrow-shared=1\n" : "rethrow-shared=0\n";
}

try {
    try {
        throw new \InvalidArgumentException("first");
    } catch (\InvalidArgumentException $selected) {
        throw new \RuntimeException("handler escape");
    } catch (\RuntimeException $wrong_sibling) {
        echo "wrong-sibling\n";
    }
} catch (\RuntimeException $outside) {
    echo $outside->getMessage(), "\n";
}
try {
    try {
        throw new \RangeException("range");
    } catch (\InvalidArgumentException $wrong_category) {
        echo "wrong-category\n";
    }
} catch (\RuntimeException $range) {
    echo $range->getMessage(), "\n";
}
$plain_error = new \Exception();
echo $plain_error->getCode(), ":", $plain_error->getPrevious() === null ? "no-cause\n" : "cause\n";


// Fixed tool-service requests retain their exact configuration and string inputs.
$tool_ir = "define i32 @entry() { ret i32 0 }\n";
$tool_output = "/build/é object.o";
$tool_request = new \prepare_backend\object_compilation($tool_ir, $tool_output, $config);
$tool_alias = $tool_request;
$tool_equal = new \prepare_backend\object_compilation($tool_ir, $tool_output, $config);
$config_equal = new \prepare_backend\backend_configuration("backend", "triple", "layout", "", "", "abi", "runtime");
$tool_other = new \prepare_backend\object_compilation("", "", $config_equal);
echo $tool_request->ir === $tool_ir ? "tool-ir=1\n" : "tool-ir=0\n";
echo $tool_request->output === $tool_output ? "tool-path=1\n" : "tool-path=0\n";
echo $tool_alias === $tool_request ? "tool-alias=1\n" : "tool-alias=0\n";
echo $tool_equal !== $tool_request ? "tool-distinct=1\n" : "tool-distinct=0\n";
echo $tool_request->configuration === $config ? "tool-config=1\n" : "tool-config=0\n";
echo $tool_other->configuration !== $config ? "tool-config-version=1\n" : "tool-config-version=0\n";
echo string_byte_len($tool_other->ir), ":", string_byte_len($tool_other->output), "\n";
$link = new \prepare_backend\link_configuration("/bin/link tool", "link-v1");
$link_other = new \prepare_backend\link_configuration("/bin/link tool", "link-v2");
echo $link->executable, ":", $link->key, ":", $link_other->key, "\n";


// Worker requests preserve observed values; no filesystem read occurs here.
$observation = new \read_sources\scanned_source_file("sub/é file.phs", 100, 7);
$read_task = new \read_sources\source_read_task(42, "/root/sub/é file.phs", 100, 7);
$read_same = new \read_sources\source_read_task(42, "/root/sub/é file.phs", 100, 7);
$read_new = new \read_sources\source_read_task(42, "/root/sub/é file.phs", 101, 9);
$read_alias = $read_task;
echo $observation->relative_path, ":", $observation->mtime, ":", $observation->size, "\n";
echo $read_task->source_file_id, ":", $read_task->path, ":", $read_task->mtime, ":", $read_task->size, "\n";
echo $read_alias === $read_task ? "read-alias=1\n" : "read-alias=0\n";
echo $read_same !== $read_task ? "read-distinct=1\n" : "read-distinct=0\n";
echo $read_new->source_file_id, ":", $read_new->mtime, ":", $read_new->size, ":", $read_task->mtime, ":", $read_task->size, "\n";
$empty_observation = new \read_sources\scanned_source_file("", 0, 0);
echo string_byte_len($empty_observation->relative_path), ":", $empty_observation->mtime, ":", $empty_observation->size, "\n";


$folder = new \read_sources\source_folder();
echo $folder->file_names === null ? "folder-recursive=1\n" : "folder-recursive=0\n";
$names /** vector<string> */ = [];
$scan_empty = new \read_sources\source_scan_task(1, 2, "/root", "sub", $names);
$scan_recursive = new \read_sources\source_scan_task(1, 2, "/root", "sub");
$names[] = "é.phs";
$scan_selected = new \read_sources\source_scan_task(3, 4, "/root", "sub", $names);
$names[] = "later.phs";
$selected /** vector<string> */ = [];
echo take_nullable($selected, $scan_empty->file_names) ? "empty-present=1\n" : "empty-present=0\n";
echo count($selected), ":", $scan_recursive->file_names === null ? "recursive" : "bad", "\n";
echo take_nullable($selected, $scan_selected->file_names) ? "selected-present=1\n" : "selected-present=0\n";
echo $scan_selected->index, ":", $scan_selected->top_folder_index, ":", $scan_selected->root, ":", $scan_selected->relative_directory, ":", count($selected), ":", $selected[0], ":", count($names), "\n";
$folder->file_names = $selected;
$selected[] = "local.phs";
$folder_names /** vector<string> */ = [];
echo take_nullable($folder_names, $folder->file_names) ? "folder-selected=1\n" : "folder-selected=0\n";
echo count($folder_names), ":", count($selected), "\n";
$folder->file_names = null;
echo $folder->file_names === null ? "folder-reset=1\n" : "folder-reset=0\n";
$file_row = new \read_sources\source_file();
echo $file_row->buffer === null ? "buffer-absent=1\n" : "buffer-absent=0\n";
echo $file_row->change_state === \read_sources\file_change::unchanged ? "state-unchanged=1\n" : "state-unchanged=0\n";
echo $file_row->needs_recompile ? "pending=1\n" : "pending=0\n";
$file_row->buffer = $source_snapshot;
$held_buffer = new \read_sources\Source_Buffer(0, "", 0, "");
echo take_nullable($held_buffer, $file_row->buffer) ? "buffer-present=1\n" : "buffer-present=0\n";
echo $held_buffer === $source_snapshot ? "buffer-identity=1\n" : "buffer-identity=0\n";
$file_row->buffer = null;
$file_row->change_state = \read_sources\file_change::deleted;
$file_row->top_folder_index = -1;
echo $file_row->change_state === \read_sources\file_change::deleted ? "state-deleted=1\n" : "state-deleted=0\n";
echo $file_row->top_folder_index, ":", $file_row->needs_recompile ? "pending" : "bad", "\n";


// Source_Set owns logical-ID indexes and publishes acknowledgment as a new snapshot.
$sources = new \read_sources\Source_Set();
$root_folder = new \read_sources\source_folder();
$root_folder->path = "src";
$root_folder->resolved_path = "/src";
$sources->folders[] = $root_folder;
$extra_folder = new \read_sources\source_folder();
$extra_folder->path = "extra";
$empty_names /** vector<string> */ = [];
$extra_folder->file_names = $empty_names;
$sources->folders[] = $extra_folder;
$live = new \read_sources\source_file();
$live->id = 37;
$live->full_path = "/src/a.phs";
$live->relative_path = "a.phs";
$live->buffer = $source_snapshot;
$clean = new \read_sources\source_file();
$clean->id = 12;
$clean->full_path = "/src/b.phs";
$clean->needs_recompile = false;
$tombstone = new \read_sources\source_file();
$tombstone->id = 99;
$tombstone->top_folder_index = -1;
$tombstone->full_path = "/src/gone.phs";
$tombstone->change_state = \read_sources\file_change::deleted;
$tombstone->needs_recompile = false;
$sources->files[] = $live;
$sources->files[] = $clean;
$sources->files[] = $tombstone;
$sources->removed_file_ids[] = 99;
$sources->next_file_id = 100;
$sources->refresh_indexes();
$sources->set_entry_file(37);
$folder_ids /** vector<int> */ = $sources->file_ids_in_folder(0);
$no_ids /** vector<int> */ = $sources->file_ids_in_folder(1);
echo $folder_ids[0], ":", $folder_ids[1], ":", count($no_ids), ":", $sources->find_file_id("/src/gone.phs"), ":", $sources->find_file_id("/absent"), "\n";
echo $sources->entry_file() === $live ? "entry-shared\n" : "bad\n";
echo $sources->file_by_id(99) === $tombstone ? "tombstone-retained\n" : "bad\n";
$before_json = $sources->to_json();
$accepted = $sources->acknowledged();
$accepted_live = $accepted->file_by_id(37);
echo $accepted !== $sources ? "owner-new" : "bad", ":", $accepted_live !== $live ? "changed-new" : "bad", ":", $accepted->file_by_id(12) === $clean ? "clean-shared" : "bad", "\n";
$accepted_buffer = new \read_sources\Source_Buffer(0, "", 0, "");
echo take_nullable($accepted_buffer, $accepted_live->buffer) ? "buffer-present" : "bad", ":", $accepted_buffer === $source_snapshot ? "buffer-shared" : "bad", "\n";
echo $sources->has_pending_changes() ? "old-pending" : "bad", ":", $accepted->has_pending_changes() ? "bad" : "accepted-clear", ":", count($accepted->removed_file_ids), ":", $accepted->next_file_id, "\n";
echo $sources->to_json() === $before_json ? "old-unchanged\n" : "bad\n";
$accepted_again = $accepted->acknowledged();
echo $accepted_again->file_by_id(37) === $accepted_live ? "repeat-shared\n" : "bad\n";
try { $sources->file_by_id(404); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
try { $sources->folder_by_index(-1); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
try { $sources->set_entry_file(99); } catch (\LogicException $error) { echo $error->getMessage(), "\n"; }
$invalid = new \read_sources\source_file();
$invalid->id = 80;
$invalid->full_path = "/src/a.phs";
$sources->files[] = $invalid;
try { $sources->refresh_indexes(); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
echo $sources->find_file_id("/src/a.phs"), "\n";
$invalid->id = 37;
try { $sources->refresh_indexes(); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
echo \read_sources\Source_Json::quote("é😀/\"\\\n"), "\n";
echo $accepted->to_json(), "\n";
$empty_sources = new \read_sources\Source_Set();
echo $empty_sources->to_json(), "\n";
$invalid_utf8 = string_byte_slice("é", 0, 1);
$extra_folder->path = $invalid_utf8;
try { $sources->to_json(); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }

$scan_task = new \read_sources\source_scan_task(8, 2, "/src", "sub", null);
$observation = new \read_sources\scanned_source_file("sub/é.phs", 101, 4);
$observations /** vector<\read_sources\scanned_source_file> */ = [];
$observations[] = $observation;
$children /** vector<string> */ = ["sub/child"];
$scan_result = new \read_sources\Source_Scan_Result($scan_task, $observations, $children);
$observations[] = new \read_sources\scanned_source_file("later.phs", 102, 7);
$children[0] = "changed";
echo $scan_result->task === $scan_task ? "scan-task-shared\n" : "bad\n";
echo count($scan_result->files), ":", $scan_result->directories[0], "\n";
echo $scan_result->files[0] === $observation ? "scan-row-shared\n" : "bad\n";
echo $scan_result->files[0]->relative_path, ":", $scan_result->files[0]->mtime, ":", $scan_result->files[0]->size, "\n";
$copied_files /** vector<\read_sources\scanned_source_file> */ = $scan_result->files;
$copied_files[] = $observation;
echo count($copied_files), ":", count($scan_result->files), "\n";
$no_files /** vector<\read_sources\scanned_source_file> */ = [];
$no_directories /** vector<string> */ = [];
$empty_scan = new \read_sources\Source_Scan_Result($scan_task, $no_files, $no_directories);
echo count($empty_scan->files), ":", count($empty_scan->directories), "\n";

$previous_scan = new \read_sources\Source_Set();
$scan_folder = new \read_sources\source_folder(); $scan_folder->resolved_path = "/root";
$previous_scan->folders[] = $scan_folder;
$old_scan_row = new \read_sources\source_file();
$old_scan_row->id = 5; $old_scan_row->full_path = "/root/old.phs";
$old_scan_row->relative_path = "old.phs"; $old_scan_row->mtime = 1; $old_scan_row->size = 4;
$old_scan_row->needs_recompile = false;
$old_scan_buffer = new \read_sources\Source_Buffer(5, "/root/old.phs", 1, "old!");
$old_scan_row->buffer = $old_scan_buffer;
$previous_scan->files[] = $old_scan_row; $previous_scan->refresh_indexes();
$current_scan = new \read_sources\Source_Set(); $current_scan->folders = $previous_scan->folders;
$current_scan->next_file_id = 6;
$task_a = new \read_sources\source_scan_task(0, 0, "/root", "", null);
$task_b = new \read_sources\source_scan_task(1, 0, "/root", "sub", null);
$join_tasks /** vector<\read_sources\source_scan_task> */ = []; $join_tasks[] = $task_a; $join_tasks[] = $task_b;
$files_a /** vector<\read_sources\scanned_source_file> */ = [];
$files_a[] = new \read_sources\scanned_source_file("old.phs", 2, 4);
$files_a[] = new \read_sources\scanned_source_file("new.phs", 3, 5);
$files_b /** vector<\read_sources\scanned_source_file> */ = []; $files_b[] = new \read_sources\scanned_source_file("sub/child.phs", 4, 6);
$dirs_a /** vector<string> */ = ["next"];
$result_a = new \read_sources\Source_Scan_Result($task_a, $files_a, $dirs_a);
$result_b = new \read_sources\Source_Scan_Result($task_b, $files_b, $no_directories);
$join_results /** vector<\read_sources\Source_Scan_Result> */ = []; $join_results[] = $result_b; $join_results[] = $result_a;
$scan_join = new \read_sources\Source_Scan_Join($current_scan, $previous_scan, $join_tasks);
$next_tasks /** vector<\read_sources\source_scan_task> */ = $scan_join->join($join_results);
foreach ($current_scan->files as $file) { echo $file->id, ":", $file->relative_path, "\n"; }
echo $current_scan->next_file_id, ":", count($next_tasks), ":", $next_tasks[0]->index, ":", $next_tasks[0]->relative_directory, "\n";
echo $current_scan->files[0] !== $old_scan_row ? "scan-copy" : "bad", ":", $current_scan->files[0]->buffer === null ? "cleared" : "bad", ":", $old_scan_row->buffer === $old_scan_buffer ? "old-retained" : "bad", "\n";
echo $current_scan->files[0]->change_state === \read_sources\file_change::changed ? "changed" : "bad", ":", $current_scan->files[1]->change_state === \read_sources\file_change::added ? "added" : "bad", "\n";
$bad_batch /** vector<\read_sources\Source_Scan_Result> */ = []; $bad_batch[] = $result_a; $bad_batch[] = $result_a;
try { $scan_join->join($bad_batch); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
$missing_batch /** vector<\read_sources\Source_Scan_Result> */ = [];
try { $scan_join->join($missing_batch); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
echo count($current_scan->files), "\n";
$foreign_task = new \read_sources\source_scan_task(0, 0, "/root", "", null);
$foreign_result = new \read_sources\Source_Scan_Result($foreign_task, $files_a, $no_directories);
$foreign_batch /** vector<\read_sources\Source_Scan_Result> */ = []; $foreign_batch[] = $foreign_result; $foreign_batch[] = $result_b;
try { $scan_join->join($foreign_batch); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
$single_tasks /** vector<\read_sources\source_scan_task> */ = []; $single_tasks[] = $task_a;
$unchanged_files /** vector<\read_sources\scanned_source_file> */ = []; $unchanged_files[] = new \read_sources\scanned_source_file("old.phs", 1, 4);
$unchanged_results /** vector<\read_sources\Source_Scan_Result> */ = [];
$unchanged_results[] = new \read_sources\Source_Scan_Result($task_a, $unchanged_files, $no_directories);
$unchanged_set = new \read_sources\Source_Set();
$unchanged_join = new \read_sources\Source_Scan_Join($unchanged_set, $previous_scan, $single_tasks);
$unchanged_join->join($unchanged_results);
echo $unchanged_set->files[0] !== $old_scan_row ? "unchanged-row-copy" : "bad", ":", $unchanged_set->files[0]->buffer === $old_scan_buffer ? "buffer-retained" : "bad", ":", $unchanged_set->files[0]->needs_recompile ? "bad" : "clean", "\n";
$exhausted_set = new \read_sources\Source_Set(); $exhausted_set->next_file_id = 4294967296;
$new_files /** vector<\read_sources\scanned_source_file> */ = []; $new_files[] = new \read_sources\scanned_source_file("new.phs", 1, 4);
$new_results /** vector<\read_sources\Source_Scan_Result> */ = [];
$new_results[] = new \read_sources\Source_Scan_Result($task_a, $new_files, $no_directories);
$exhausted_join = new \read_sources\Source_Scan_Join($exhausted_set, $previous_scan, $single_tasks);
try { $exhausted_join->join($new_results); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
echo count($exhausted_set->files), "\n";

// Snapshot acceptance: changed, retained and deleted rows in one indexed set.
$snapshot_set = new \read_sources\Source_Set();
$snapshot_set->folders[] = $scan_folder;
$snapshot_set->next_file_id = 40;
$snapshot_changed = new \read_sources\source_file();
$snapshot_changed->id = 11; $snapshot_changed->top_folder_index = 0;
$snapshot_changed->full_path = '/root/change'; $snapshot_changed->mtime = 2; $snapshot_changed->size = 2;
$snapshot_retained = new \read_sources\source_file();
$snapshot_retained->id = 12; $snapshot_retained->top_folder_index = 0;
$snapshot_retained->full_path = '/root/keep'; $snapshot_retained->mtime = 3; $snapshot_retained->size = 1;
$snapshot_retained->buffer = new \read_sources\Source_Buffer(12, '/root/keep', 3, 'K');
$snapshot_deleted = new \read_sources\source_file();
$snapshot_deleted->id = 13; $snapshot_deleted->change_state = \read_sources\file_change::deleted;
$snapshot_deleted->buffer = new \read_sources\Source_Buffer(13, '/root/gone', 1, 'G');
$snapshot_set->files[] = $snapshot_changed; $snapshot_set->files[] = $snapshot_retained; $snapshot_set->files[] = $snapshot_deleted;
$snapshot_set->removed_file_ids[] = 13;
$snapshot_set->refresh_indexes(); $snapshot_set->set_entry_file(11);
$snapshot_tasks /** vector<\read_sources\source_read_task> */ = [];
$snapshot_tasks[] = new \read_sources\source_read_task(11, '/root/change', 2, 2);
$snapshot_join = new \read_sources\Snapshot_Join($snapshot_set, $snapshot_tasks);
$snapshot_results /** vector<\read_sources\Source_Buffer> */ = [];
$snapshot_buffer = new \read_sources\Source_Buffer(11, '/root/change', 2, 'é');
$snapshot_results[] = $snapshot_buffer;
$snapshot_accepted = $snapshot_join->join($snapshot_results);
echo $snapshot_accepted !== $snapshot_set ? 'snapshot-owner' : 'bad', ':', $snapshot_accepted->file_by_id(11) !== $snapshot_changed ? 'changed-copy' : 'bad', ':', $snapshot_accepted->file_by_id(12) === $snapshot_retained ? 'retained-shared' : 'bad', "\n";
echo $snapshot_accepted->file_by_id(11)->buffer === $snapshot_buffer ? 'buffer-exact' : 'bad', ':', $snapshot_changed->buffer === null ? 'old-clean' : 'bad', ':', $snapshot_accepted->file_by_id(13)->buffer === null ? 'deleted-clear' : 'bad', ':', $snapshot_deleted->buffer !== null ? 'old-retained' : 'bad', "\n";
echo $snapshot_accepted->entry_file()->id, ':', $snapshot_accepted->find_file_id('/root/keep'), ':', $snapshot_accepted->removed_file_ids[0], ':', $snapshot_accepted->next_file_id, "\n";
$snapshot_accepted->removed_file_ids[] = 99;
echo count($snapshot_set->removed_file_ids), "\n";
$snapshot_empty /** vector<\read_sources\Source_Buffer> */ = [];
try { $snapshot_join->join($snapshot_empty); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
$snapshot_results[] = $snapshot_buffer;
try { $snapshot_join->join($snapshot_results); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
$snapshot_bad /** vector<\read_sources\Source_Buffer> */ = [];
$snapshot_bad[] = new \read_sources\Source_Buffer(11, '/root/change', 2, 'X');
try { $snapshot_join->join($snapshot_bad); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
$snapshot_tasks[] = $snapshot_tasks[0];
$snapshot_duplicate = new \read_sources\Snapshot_Join($snapshot_set, $snapshot_tasks);
try { $snapshot_duplicate->join($snapshot_empty); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
$snapshot_stale_tasks /** vector<\read_sources\source_read_task> */ = [];
$snapshot_stale_tasks[] = new \read_sources\source_read_task(11, '/root/change', 1, 2);
$snapshot_stale = new \read_sources\Snapshot_Join($snapshot_set, $snapshot_stale_tasks);
try { $snapshot_stale->join($snapshot_empty); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
$snapshot_no_tasks /** vector<\read_sources\source_read_task> */ = [];
$snapshot_retention = new \read_sources\Snapshot_Join($snapshot_set, $snapshot_no_tasks);
try { $snapshot_retention->join($snapshot_empty); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }

$snapshot_repeat_join = new \read_sources\Snapshot_Join($snapshot_accepted, $snapshot_no_tasks);
$snapshot_repeat = $snapshot_repeat_join->join($snapshot_empty);
echo $snapshot_repeat->file_by_id(11) === $snapshot_accepted->file_by_id(11) ? 'repeat-shared' : 'bad', ':', $snapshot_repeat->file_by_id(13) === $snapshot_accepted->file_by_id(13) ? 'deleted-shared' : 'bad', "\n";
$snapshot_empty_files /** vector<\read_sources\source_file> */ = [];
$snapshot_repeat->files = $snapshot_empty_files;
$snapshot_repeat->refresh_indexes();
echo $snapshot_accepted->find_file_id('/root/change'), ':', count($snapshot_accepted->files), ':', $snapshot_repeat->find_file_id('/root/change'), "\n";

// Pure read selection retains source order, ignores pending semantic work and skips tombstones.
$selected_incremental = \read_sources\Source_Read_Selection::select($snapshot_set, false);
$selected_full = \read_sources\Source_Read_Selection::select($snapshot_set, true);
echo count($selected_incremental), ':', $selected_incremental[0]->source_file_id, ':', $selected_incremental[0]->path, ':', $selected_incremental[0]->mtime, ':', $selected_incremental[0]->size, "\n";
echo count($selected_full), ':', $selected_full[0]->source_file_id, ':', $selected_full[1]->source_file_id, "\n";
$selected_accepted = \read_sources\Source_Read_Selection::select($snapshot_accepted, false);
echo count($selected_accepted), ':', $snapshot_retained->needs_recompile ? 'pending-retained' : 'bad', ':', $snapshot_changed->buffer === null ? 'input-unmodified' : 'bad', "\n";
$snapshot_changed->mtime = 9;
echo $selected_incremental[0]->mtime, ':', $selected_full[0]->mtime, "\n";
$snapshot_changed->mtime = 2;
$selection_empty = new \read_sources\Source_Set();
$empty_selection = \read_sources\Source_Read_Selection::select($selection_empty, true);
echo count($empty_selection), "\n";
$selection_moved = new \read_sources\Source_Set();
$moved_file = new \read_sources\source_file(); $moved_file->change_state = \read_sources\file_change::moved;
$selection_moved->files[] = $moved_file;
try { \read_sources\Source_Read_Selection::select($selection_moved, false); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
try { \read_sources\Source_Read_Selection::select($selection_moved, true); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }

$token_source = new \read_sources\Source_Buffer(71, '/tokens', 1, 'é x');
$token_buffer = new \tokenize\Token_Buffer($token_source);
$token_row = new \tokenize\token(); $token_row->kind = \tokenize\token_kind::variable_name;
$token_row->start = 3; $token_row->length = 1; $token_buffer->rows[] = $token_row;
$token_buffers /** vector<\tokenize\Token_Buffer> */ = []; $token_buffers[] = $token_buffer;
$token_set = new \tokenize\Token_Set($token_buffers);
echo $token_set->for_file(71) === $token_buffer ? 'token-shared' : 'bad', ':', $token_set->for_file(72) === null ? 'token-absent' : 'bad', "\n";
echo $token_set->to_json(), "\n";
$token_baseline = new \tokenize\Token_Set(); echo $token_baseline->to_json(), "\n";
$token_buffers[] = $token_buffer;
try { $bad_tokens = new \tokenize\Token_Set($token_buffers); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }

$invalid_token_source = new \read_sources\Source_Buffer(0, '/invalid', 1, '');
$invalid_token_buffers /** vector<\tokenize\Token_Buffer> */ = [];
$invalid_token_buffers[] = new \tokenize\Token_Buffer($invalid_token_source);
try { $invalid_tokens = new \tokenize\Token_Set($invalid_token_buffers); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
$token_row->start = 0; $token_row->length = 2;
echo $token_buffer->to_json(), "\n";
$token_row->length = 1;
try { echo $token_buffer->to_json(); } catch (\JsonException $error) { echo $error->getMessage(), "\n"; }
$token_row->start = 3; $token_row->length = 1;
echo $token_set->for_file(71)->source === $token_source ? 'source-shared' : 'bad', ':', $token_baseline->for_file(71) === null ? 'baseline-independent' : 'bad', "\n";

// Accept replacements in completion order; preserve source membership order and exact snapshots.
$join_sources = $snapshot_accepted;
$join_source_a = $snapshot_buffer;
$join_source_b = $snapshot_retained->buffer;
$join_token_a = new \tokenize\Token_Buffer($join_source_a);
$join_token_b = new \tokenize\Token_Buffer($join_source_b);
$accept_token_tasks /** vector<\read_sources\Source_Buffer> */ = []; $accept_token_tasks[] = $join_source_a; $accept_token_tasks[] = $join_source_b;
$accept_token_results /** vector<\tokenize\Token_Buffer> */ = []; $accept_token_results[] = $join_token_b; $accept_token_results[] = $join_token_a;
$join_previous = new \tokenize\Token_Set();
$token_join = new \tokenize\Token_Join($join_sources, $join_previous, $accept_token_tasks);
$joined_tokens = $token_join->join($accept_token_results);
echo $joined_tokens->for_file(11) === $join_token_a ? 'join-a' : 'bad', ':', $joined_tokens->for_file(12) === $join_token_b ? 'join-b' : 'bad', ':', $joined_tokens->for_file(13) === null ? 'deleted-absent' : 'bad', "\n";
echo $joined_tokens->to_json(), "\n";
$no_token_tasks /** vector<\read_sources\Source_Buffer> */ = [];
$no_token_results /** vector<\tokenize\Token_Buffer> */ = [];
$retain_token_join = new \tokenize\Token_Join($join_sources, $joined_tokens, $no_token_tasks);
$retained_tokens = $retain_token_join->join($no_token_results);
echo $retained_tokens !== $joined_tokens ? 'new-token-set' : 'bad', ':', $retained_tokens->for_file(11) === $join_token_a ? 'retained-token' : 'bad', ':', $join_previous->for_file(11) === null ? 'previous-unmodified' : 'bad', "\n";
try { $token_join->join($no_token_results); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
$duplicate_tokens /** vector<\tokenize\Token_Buffer> */ = []; $duplicate_tokens[] = $join_token_a; $duplicate_tokens[] = $join_token_a;
try { $token_join->join($duplicate_tokens); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
$foreign_tokens /** vector<\tokenize\Token_Buffer> */ = []; $foreign_tokens[] = $token_buffer;
try { $token_join->join($foreign_tokens); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
$stale_source = new \read_sources\Source_Buffer(11, '/root/change', 2, 'é');
$stale_tokens /** vector<\tokenize\Token_Buffer> */ = []; $stale_tokens[] = new \tokenize\Token_Buffer($stale_source);
try { $token_join->join($stale_tokens); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
$stale_tasks /** vector<\read_sources\Source_Buffer> */ = []; $stale_tasks[] = $stale_source;
$stale_token_join = new \tokenize\Token_Join($join_sources, $joined_tokens, $stale_tasks);
try { $stale_token_join->join($no_token_results); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
$missing_token_join = new \tokenize\Token_Join($join_sources, $join_previous, $no_token_tasks);
try { $missing_token_join->join($no_token_results); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }

// Token selection uses snapshot identity, independently of pending semantic work.
$warm_token_tasks = \tokenize\Token_Selection::select($join_sources, $joined_tokens, false);
$full_token_tasks = \tokenize\Token_Selection::select($join_sources, $joined_tokens, true);
$cold_token_tasks = \tokenize\Token_Selection::select($join_sources, $join_previous, false);
echo count($warm_token_tasks), ':', count($full_token_tasks), ':', count($cold_token_tasks), "\n";
echo $full_token_tasks[0] === $join_source_a ? 'selected-a' : 'bad', ':', $full_token_tasks[1] === $join_source_b ? 'selected-b' : 'bad', "\n";
$changed_token_sources = $join_sources->copy();
$changed_token_row = $join_sources->file_by_id(11)->copy();
$changed_token_row->buffer = $stale_source;
$changed_token_sources->files[0] = $changed_token_row;
$changed_token_tasks = \tokenize\Token_Selection::select($changed_token_sources, $joined_tokens, false);
echo count($changed_token_tasks), ':', $changed_token_tasks[0] === $stale_source ? 'new-identity' : 'bad', ':', $join_sources->file_by_id(11)->buffer === $join_source_a ? 'old-retained' : 'bad', "\n";
try { \tokenize\Token_Selection::select($snapshot_set, $joined_tokens, false); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
try { \tokenize\Token_Selection::select($snapshot_set, $joined_tokens, true); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
$empty_token_selection = \tokenize\Token_Selection::select($selection_empty, $joined_tokens, true);
echo count($empty_token_selection), "\n";

$frontend_source = new \read_sources\Source_Buffer(91, '/parse', 4, 'x é');
$frontend_tokens = new \tokenize\Token_Buffer($frontend_source);
$frontend_tree = new \parse\Syntax_Tree(); $frontend_tree->source_file_id = 91; $frontend_tree->root_node_id = 2;
$frontend_entry = new \parse\syntax_node(); $frontend_entry->kind = \parse\syntax_kind::block; $frontend_entry->next_sibling_id = 3;
$frontend_root = new \parse\syntax_node(); $frontend_root->kind = \parse\syntax_kind::file_root; $frontend_root->first_child_id = 1;
$frontend_entity = new \parse\syntax_node(); $frontend_entity->kind = \parse\syntax_kind::name; $frontend_entity->start = 2; $frontend_entity->length = 2;
$frontend_tree->nodes[] = $frontend_entry; $frontend_tree->nodes[] = $frontend_root; $frontend_tree->nodes[] = $frontend_entity;
$frontend = new \parse\File_Frontend($frontend_tokens, $frontend_tree); $frontend->source_file_id = 91;
$frontend->entry_body_id = 1; $frontend->defined_entities[] = 3;
$frontend->validate();
echo $frontend->tokens === $frontend_tokens ? 'frontend-token-shared' : 'bad', ':', $frontend->syntax === $frontend_tree ? 'frontend-tree-shared' : 'bad', "\n";
echo $frontend->to_json(), "\n";
$frontend->entry_body_id = 0;
try { $frontend->validate(); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
$frontend->entry_body_id = 1; $frontend_tree->root_node_id = 99;
try { $frontend->validate(); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
$frontend_tree->root_node_id = 2; $frontend->source_file_id = 92;
try { $frontend->validate(); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
$frontend->source_file_id = 91; $frontend->defined_entities[0] = 99;
try { $frontend->validate(); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
$frontend->defined_entities[0] = 3; $frontend_entity->next_sibling_id = 1;
try { $frontend->validate(); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
$frontend_entity->next_sibling_id = 0; $frontend->validate(); echo "frontend-recovered\n";

$frontend_source_two = new \read_sources\Source_Buffer(92, '/parse-two', 4, 'x é');
$frontend_tokens_two = new \tokenize\Token_Buffer($frontend_source_two);
$frontend_tree_two = new \parse\Syntax_Tree(); $frontend_tree_two->source_file_id = 92; $frontend_tree_two->root_node_id = 2; $frontend_tree_two->nodes = $frontend_tree->nodes;
$frontend_two = new \parse\File_Frontend($frontend_tokens_two, $frontend_tree_two);
$frontend_two->source_file_id = 92; $frontend_two->entry_body_id = 1; $frontend_two->defined_entities[] = 3;
$frontend_sources = new \read_sources\Source_Set(); $frontend_sources->folders[] = $scan_folder;
$frontend_row_one = new \read_sources\source_file(); $frontend_row_one->id = 91; $frontend_row_one->top_folder_index = 0; $frontend_row_one->full_path = '/parse'; $frontend_row_one->buffer = $frontend_source;
$frontend_row_two = new \read_sources\source_file(); $frontend_row_two->id = 92; $frontend_row_two->top_folder_index = 0; $frontend_row_two->full_path = '/parse-two'; $frontend_row_two->buffer = $frontend_source_two;
$frontend_sources->files[] = $frontend_row_one; $frontend_sources->files[] = $frontend_row_two; $frontend_sources->refresh_indexes();
$frontend_task_list /** vector<\tokenize\Token_Buffer> */ = []; $frontend_task_list[] = $frontend_tokens; $frontend_task_list[] = $frontend_tokens_two;
$frontend_token_set = new \tokenize\Token_Set($frontend_task_list);
$frontend_previous = new \parse\Frontend_Set();
$frontend_join = new \parse\Frontend_Join($frontend_previous, $frontend_sources, $frontend_token_set, $frontend_task_list);
$frontend_results /** vector<\parse\File_Frontend> */ = []; $frontend_results[] = $frontend_two; $frontend_results[] = $frontend;
try { $frontend_join->merge($frontend_results, 0, 3); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
$frontend_join->merge($frontend_results, 0, 1);
try { $frontend_join->finish(); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
$frontend_duplicates /** vector<\parse\File_Frontend> */ = []; $frontend_duplicates[] = $frontend; $frontend_duplicates[] = $frontend;
try { $frontend_join->merge($frontend_duplicates, 0, 2); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
$frontend_join->merge($frontend_results, 1, 1);
$frontend_accepted = $frontend_join->finish();
echo $frontend_accepted->for_file(91) === $frontend ? 'frontend-one' : 'bad', ':', $frontend_accepted->for_file(92) === $frontend_two ? 'frontend-two' : 'bad', ':', $frontend_previous->for_file(91) === null ? 'previous-empty' : 'bad', "\n";
$frontend_repeated = $frontend_join->finish();
echo $frontend_repeated !== $frontend_accepted ? 'repeat-owner' : 'bad', ':', $frontend_repeated->for_file(91) === $frontend ? 'repeat-shared' : 'bad', "\n";
$frontend_no_tasks /** vector<\tokenize\Token_Buffer> */ = [];
$frontend_retention = new \parse\Frontend_Join($frontend_accepted, $frontend_sources, $frontend_token_set, $frontend_no_tasks);
$frontend_retained = $frontend_retention->finish();
echo $frontend_retained->for_file(92) === $frontend_two ? 'retained-frontend' : 'bad', "\n";
try { $frontend_retention->merge($frontend_results, 0, 1); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
try { $frontend_bad_store = new \parse\Frontend_Set($frontend_duplicates); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
echo $frontend_previous->to_json(), "\n";

$angle_tokens = new \tokenize\Token_Buffer($frontend_source);
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::left_angle; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::left_parenthesis; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::left_angle; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::right_angle; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::right_parenthesis; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::right_angle; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::left_angle; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::left_bracket; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::left_angle; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::right_bracket; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::right_angle; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::left_angle; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::semicolon; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::right_angle; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::left_angle; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::left_angle; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::right_angle; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::right_angle; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::left_brace; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::left_angle; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::right_brace; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::right_angle; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::left_bracket; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::left_angle; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::right_angle; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::right_bracket; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::right_bracket; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::left_angle; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::left_parenthesis; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::left_angle; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::right_parenthesis; $angle_tokens->rows[] = $angle_row;
$angle_row = new \tokenize\token(); $angle_row->kind = \tokenize\token_kind::right_angle; $angle_tokens->rows[] = $angle_row;
$angle_ends = \parse\Binary_Syntax::angle_ends($angle_tokens);
foreach ($angle_ends as $opening => $closing) { echo $opening, ':', $closing, "\n"; }
echo \parse\Binary_Syntax::from_token(\tokenize\token_kind::plus) === \parse\syntax_kind::addition_expression ? 'addition-kind' : 'bad', ':', \parse\Binary_Syntax::from_token(\tokenize\token_kind::variable_name) === null ? 'not-operator' : 'bad', "\n";
echo \parse\Binary_Syntax::operation(\parse\syntax_kind::less_than_expression) === 'less_than' ? 'less-than-name' : 'bad', ':', \parse\Binary_Syntax::operation(\parse\syntax_kind::block) === null ? 'no-operation' : 'bad', "\n";
echo \parse\Binary_Syntax::precedence(\parse\syntax_kind::addition_expression), ':', \parse\Binary_Syntax::precedence(\parse\syntax_kind::less_than_expression), "\n";
try { \parse\Binary_Syntax::precedence(\parse\syntax_kind::block); } catch (\LogicException $error) { echo $error->getMessage(), "\n"; }

$parser_warm = \parse\Parser_Selection::select($frontend_sources, $frontend_token_set, $frontend_accepted, false);
$parser_full = \parse\Parser_Selection::select($frontend_sources, $frontend_token_set, $frontend_accepted, true);
$parser_cold = \parse\Parser_Selection::select($frontend_sources, $frontend_token_set, $frontend_previous, false);
echo count($parser_warm), ':', count($parser_full), ':', count($parser_cold), "\n";
echo $parser_full[0] === $frontend_tokens ? 'parse-first' : 'bad', ':', $parser_full[1] === $frontend_tokens_two ? 'parse-second' : 'bad', "\n";
$parser_replacement = new \tokenize\Token_Buffer($frontend_source);
$parser_replacement_rows /** vector<\tokenize\Token_Buffer> */ = []; $parser_replacement_rows[] = $parser_replacement; $parser_replacement_rows[] = $frontend_tokens_two;
$parser_replacement_set = new \tokenize\Token_Set($parser_replacement_rows);
$parser_changed = \parse\Parser_Selection::select($frontend_sources, $parser_replacement_set, $frontend_accepted, false);
echo count($parser_changed), ':', $parser_changed[0] === $parser_replacement ? 'parse-replaced' : 'bad', ':', $frontend_accepted->for_file(91)->tokens === $frontend_tokens ? 'previous-retained' : 'bad', "\n";
$parser_empty_tokens = new \tokenize\Token_Set();
try { \parse\Parser_Selection::select($frontend_sources, $parser_empty_tokens, $frontend_accepted, false); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
$parser_stale_source = new \read_sources\Source_Buffer(91, '/parse', 4, 'x é');
$parser_stale_rows /** vector<\tokenize\Token_Buffer> */ = []; $parser_stale_rows[] = new \tokenize\Token_Buffer($parser_stale_source); $parser_stale_rows[] = $frontend_tokens_two;
$parser_stale_set = new \tokenize\Token_Set($parser_stale_rows);
try { \parse\Parser_Selection::select($frontend_sources, $parser_stale_set, $frontend_accepted, true); } catch (\Exception $error) { echo $error->getMessage(), "\n"; }
$parser_removed_sources = $frontend_sources->copy();
$parser_removed = $frontend_row_two->copy(); $parser_removed->change_state = \read_sources\file_change::deleted;
$parser_removed_sources->files[1] = $parser_removed;
$parser_live = \parse\Parser_Selection::select($parser_removed_sources, $frontend_token_set, $frontend_accepted, true);
echo count($parser_live), ':', $parser_live[0] === $frontend_tokens ? 'live-only' : 'bad', "\n";
$parser_empty = \parse\Parser_Selection::select($selection_empty, $frontend_token_set, $frontend_accepted, true);
echo count($parser_empty), "\n";

$compare_source = new \read_sources\Source_Buffer(103, '/compare', 1, '   é');
$compare_tokens = new \tokenize\Token_Buffer($compare_source);
$compare_tree = new \parse\Syntax_Tree(); $compare_tree->source_file_id = 103; $compare_tree->root_node_id = 1;
$compare_root = new \parse\syntax_node(); $compare_root->kind = \parse\syntax_kind::file_root; $compare_root->first_child_id = 3; $compare_root->next_sibling_id = 99;
$compare_name = new \parse\syntax_node(); $compare_name->kind = \parse\syntax_kind::name; $compare_name->start = 3; $compare_name->length = 2;
$compare_block = new \parse\syntax_node(); $compare_block->kind = \parse\syntax_kind::block; $compare_block->next_sibling_id = 2;
$compare_tree->nodes[] = $compare_root; $compare_tree->nodes[] = $compare_name; $compare_tree->nodes[] = $compare_block;
$compare_frontend = new \parse\File_Frontend($compare_tokens, $compare_tree);
echo \parse\Syntax_Comparer::equal($frontend, 2, $compare_frontend, 1) ? 'logical-tree-equal' : 'bad', ':', \parse\Syntax_Comparer::equal($frontend, 3, $compare_frontend, 2) ? 'spelling-equal' : 'bad', "\n";
echo \parse\Syntax_Comparer::equal($frontend, 0, $compare_frontend, 0) ? 'both-absent' : 'bad', ':', \parse\Syntax_Comparer::equal($frontend, 3, $compare_frontend, 0) ? 'bad' : 'one-absent', "\n";
$compare_name->length = 1;
echo \parse\Syntax_Comparer::equal($frontend, 3, $compare_frontend, 2) ? 'bad' : 'length-differs', "\n";
$compare_name->length = 2; $compare_name->kind = \parse\syntax_kind::integer_literal;
echo \parse\Syntax_Comparer::equal($frontend, 3, $compare_frontend, 2) ? 'bad' : 'kind-differs', "\n";
$compare_name->kind = \parse\syntax_kind::name; $compare_block->next_sibling_id = 0;
echo \parse\Syntax_Comparer::equal($frontend, 2, $compare_frontend, 1) ? 'bad' : 'child-membership-differs', "\n";
$compare_block->next_sibling_id = 2;
try { \parse\Syntax_Comparer::equal($frontend, 99, $compare_frontend, 1); } catch (\LogicException $error) { echo $error->getMessage(), "\n"; }
try { \parse\Syntax_Comparer::equal($frontend, 1, $compare_frontend, 99); } catch (\LogicException $error) { echo $error->getMessage(), "\n"; }
$compare_name->kind = \parse\syntax_kind::invalid;
try { \parse\Syntax_Comparer::equal($compare_frontend, 2, $compare_frontend, 2); } catch (\LogicException $error) { echo $error->getMessage(), "\n"; }
$compare_name->kind = \parse\syntax_kind::name;
echo $frontend_entity->start, ':', $compare_name->start, ':', $compare_root->next_sibling_id, "\n";

// Both pending sibling and child frames exist together; reused slots must not corrupt either.
$frontend_entry->first_child_id = 3; $compare_block->first_child_id = 2;
echo \parse\Syntax_Comparer::equal($frontend, 2, $compare_frontend, 1) ? 'paired-stack-branches' : 'bad', "\n";
$frontend_entry->first_child_id = 0; $compare_block->first_child_id = 0;

// Byte literal decoding: explicit numeric boundaries and binary output.
$octal_inputs /** vector<string> */ = ['"\\000"', '"\\001"', '"\\002"', '"\\003"', '"\\004"', '"\\005"', '"\\006"', '"\\007"', '"\\010"', '"\\011"', '"\\012"', '"\\013"', '"\\014"', '"\\015"', '"\\016"', '"\\017"', '"\\020"', '"\\021"', '"\\022"', '"\\023"', '"\\024"', '"\\025"', '"\\026"', '"\\027"', '"\\030"', '"\\031"', '"\\032"', '"\\033"', '"\\034"', '"\\035"', '"\\036"', '"\\037"', '"\\040"', '"\\041"', '"\\042"', '"\\043"', '"\\044"', '"\\045"', '"\\046"', '"\\047"', '"\\050"', '"\\051"', '"\\052"', '"\\053"', '"\\054"', '"\\055"', '"\\056"', '"\\057"', '"\\060"', '"\\061"', '"\\062"', '"\\063"', '"\\064"', '"\\065"', '"\\066"', '"\\067"', '"\\070"', '"\\071"', '"\\072"', '"\\073"', '"\\074"', '"\\075"', '"\\076"', '"\\077"', '"\\100"', '"\\101"', '"\\102"', '"\\103"', '"\\104"', '"\\105"', '"\\106"', '"\\107"', '"\\110"', '"\\111"', '"\\112"', '"\\113"', '"\\114"', '"\\115"', '"\\116"', '"\\117"', '"\\120"', '"\\121"', '"\\122"', '"\\123"', '"\\124"', '"\\125"', '"\\126"', '"\\127"', '"\\130"', '"\\131"', '"\\132"', '"\\133"', '"\\134"', '"\\135"', '"\\136"', '"\\137"', '"\\140"', '"\\141"', '"\\142"', '"\\143"', '"\\144"', '"\\145"', '"\\146"', '"\\147"', '"\\150"', '"\\151"', '"\\152"', '"\\153"', '"\\154"', '"\\155"', '"\\156"', '"\\157"', '"\\160"', '"\\161"', '"\\162"', '"\\163"', '"\\164"', '"\\165"', '"\\166"', '"\\167"', '"\\170"', '"\\171"', '"\\172"', '"\\173"', '"\\174"', '"\\175"', '"\\176"', '"\\177"', '"\\200"', '"\\201"', '"\\202"', '"\\203"', '"\\204"', '"\\205"', '"\\206"', '"\\207"', '"\\210"', '"\\211"', '"\\212"', '"\\213"', '"\\214"', '"\\215"', '"\\216"', '"\\217"', '"\\220"', '"\\221"', '"\\222"', '"\\223"', '"\\224"', '"\\225"', '"\\226"', '"\\227"', '"\\230"', '"\\231"', '"\\232"', '"\\233"', '"\\234"', '"\\235"', '"\\236"', '"\\237"', '"\\240"', '"\\241"', '"\\242"', '"\\243"', '"\\244"', '"\\245"', '"\\246"', '"\\247"', '"\\250"', '"\\251"', '"\\252"', '"\\253"', '"\\254"', '"\\255"', '"\\256"', '"\\257"', '"\\260"', '"\\261"', '"\\262"', '"\\263"', '"\\264"', '"\\265"', '"\\266"', '"\\267"', '"\\270"', '"\\271"', '"\\272"', '"\\273"', '"\\274"', '"\\275"', '"\\276"', '"\\277"', '"\\300"', '"\\301"', '"\\302"', '"\\303"', '"\\304"', '"\\305"', '"\\306"', '"\\307"', '"\\310"', '"\\311"', '"\\312"', '"\\313"', '"\\314"', '"\\315"', '"\\316"', '"\\317"', '"\\320"', '"\\321"', '"\\322"', '"\\323"', '"\\324"', '"\\325"', '"\\326"', '"\\327"', '"\\330"', '"\\331"', '"\\332"', '"\\333"', '"\\334"', '"\\335"', '"\\336"', '"\\337"', '"\\340"', '"\\341"', '"\\342"', '"\\343"', '"\\344"', '"\\345"', '"\\346"', '"\\347"', '"\\350"', '"\\351"', '"\\352"', '"\\353"', '"\\354"', '"\\355"', '"\\356"', '"\\357"', '"\\360"', '"\\361"', '"\\362"', '"\\363"', '"\\364"', '"\\365"', '"\\366"', '"\\367"', '"\\370"', '"\\371"', '"\\372"', '"\\373"', '"\\374"', '"\\375"', '"\\376"', '"\\377"', '"\\400"', '"\\401"', '"\\402"', '"\\403"', '"\\404"', '"\\405"', '"\\406"', '"\\407"', '"\\410"', '"\\411"', '"\\412"', '"\\413"', '"\\414"', '"\\415"', '"\\416"', '"\\417"', '"\\420"', '"\\421"', '"\\422"', '"\\423"', '"\\424"', '"\\425"', '"\\426"', '"\\427"', '"\\430"', '"\\431"', '"\\432"', '"\\433"', '"\\434"', '"\\435"', '"\\436"', '"\\437"', '"\\440"', '"\\441"', '"\\442"', '"\\443"', '"\\444"', '"\\445"', '"\\446"', '"\\447"', '"\\450"', '"\\451"', '"\\452"', '"\\453"', '"\\454"', '"\\455"', '"\\456"', '"\\457"', '"\\460"', '"\\461"', '"\\462"', '"\\463"', '"\\464"', '"\\465"', '"\\466"', '"\\467"', '"\\470"', '"\\471"', '"\\472"', '"\\473"', '"\\474"', '"\\475"', '"\\476"', '"\\477"', '"\\500"', '"\\501"', '"\\502"', '"\\503"', '"\\504"', '"\\505"', '"\\506"', '"\\507"', '"\\510"', '"\\511"', '"\\512"', '"\\513"', '"\\514"', '"\\515"', '"\\516"', '"\\517"', '"\\520"', '"\\521"', '"\\522"', '"\\523"', '"\\524"', '"\\525"', '"\\526"', '"\\527"', '"\\530"', '"\\531"', '"\\532"', '"\\533"', '"\\534"', '"\\535"', '"\\536"', '"\\537"', '"\\540"', '"\\541"', '"\\542"', '"\\543"', '"\\544"', '"\\545"', '"\\546"', '"\\547"', '"\\550"', '"\\551"', '"\\552"', '"\\553"', '"\\554"', '"\\555"', '"\\556"', '"\\557"', '"\\560"', '"\\561"', '"\\562"', '"\\563"', '"\\564"', '"\\565"', '"\\566"', '"\\567"', '"\\570"', '"\\571"', '"\\572"', '"\\573"', '"\\574"', '"\\575"', '"\\576"', '"\\577"', '"\\600"', '"\\601"', '"\\602"', '"\\603"', '"\\604"', '"\\605"', '"\\606"', '"\\607"', '"\\610"', '"\\611"', '"\\612"', '"\\613"', '"\\614"', '"\\615"', '"\\616"', '"\\617"', '"\\620"', '"\\621"', '"\\622"', '"\\623"', '"\\624"', '"\\625"', '"\\626"', '"\\627"', '"\\630"', '"\\631"', '"\\632"', '"\\633"', '"\\634"', '"\\635"', '"\\636"', '"\\637"', '"\\640"', '"\\641"', '"\\642"', '"\\643"', '"\\644"', '"\\645"', '"\\646"', '"\\647"', '"\\650"', '"\\651"', '"\\652"', '"\\653"', '"\\654"', '"\\655"', '"\\656"', '"\\657"', '"\\660"', '"\\661"', '"\\662"', '"\\663"', '"\\664"', '"\\665"', '"\\666"', '"\\667"', '"\\670"', '"\\671"', '"\\672"', '"\\673"', '"\\674"', '"\\675"', '"\\676"', '"\\677"', '"\\700"', '"\\701"', '"\\702"', '"\\703"', '"\\704"', '"\\705"', '"\\706"', '"\\707"', '"\\710"', '"\\711"', '"\\712"', '"\\713"', '"\\714"', '"\\715"', '"\\716"', '"\\717"', '"\\720"', '"\\721"', '"\\722"', '"\\723"', '"\\724"', '"\\725"', '"\\726"', '"\\727"', '"\\730"', '"\\731"', '"\\732"', '"\\733"', '"\\734"', '"\\735"', '"\\736"', '"\\737"', '"\\740"', '"\\741"', '"\\742"', '"\\743"', '"\\744"', '"\\745"', '"\\746"', '"\\747"', '"\\750"', '"\\751"', '"\\752"', '"\\753"', '"\\754"', '"\\755"', '"\\756"', '"\\757"', '"\\760"', '"\\761"', '"\\762"', '"\\763"', '"\\764"', '"\\765"', '"\\766"', '"\\767"', '"\\770"', '"\\771"', '"\\772"', '"\\773"', '"\\774"', '"\\775"', '"\\776"', '"\\777"'];
$byte_index /** int */ = 0;
foreach ($octal_inputs as $literal) {
    $decoded /** string */ = \check_bodies\Byte_Literals::decode($literal);
    if ((string_byte_len($decoded) !== 1) || (string_byte_at($decoded, 0) !== ($byte_index % 256))) { throw new \LogicException("Octal byte mismatch"); }
    ++$byte_index;
}
echo "octal-bytes=", $byte_index, "\n";
$hex_inputs /** vector<string> */ = ['"\\x00"', '"\\x01"', '"\\x02"', '"\\x03"', '"\\x04"', '"\\x05"', '"\\x06"', '"\\x07"', '"\\x08"', '"\\x09"', '"\\x0A"', '"\\x0B"', '"\\x0C"', '"\\x0D"', '"\\x0E"', '"\\x0F"', '"\\x10"', '"\\x11"', '"\\x12"', '"\\x13"', '"\\x14"', '"\\x15"', '"\\x16"', '"\\x17"', '"\\x18"', '"\\x19"', '"\\x1A"', '"\\x1B"', '"\\x1C"', '"\\x1D"', '"\\x1E"', '"\\x1F"', '"\\x20"', '"\\x21"', '"\\x22"', '"\\x23"', '"\\x24"', '"\\x25"', '"\\x26"', '"\\x27"', '"\\x28"', '"\\x29"', '"\\x2A"', '"\\x2B"', '"\\x2C"', '"\\x2D"', '"\\x2E"', '"\\x2F"', '"\\x30"', '"\\x31"', '"\\x32"', '"\\x33"', '"\\x34"', '"\\x35"', '"\\x36"', '"\\x37"', '"\\x38"', '"\\x39"', '"\\x3A"', '"\\x3B"', '"\\x3C"', '"\\x3D"', '"\\x3E"', '"\\x3F"', '"\\x40"', '"\\x41"', '"\\x42"', '"\\x43"', '"\\x44"', '"\\x45"', '"\\x46"', '"\\x47"', '"\\x48"', '"\\x49"', '"\\x4A"', '"\\x4B"', '"\\x4C"', '"\\x4D"', '"\\x4E"', '"\\x4F"', '"\\x50"', '"\\x51"', '"\\x52"', '"\\x53"', '"\\x54"', '"\\x55"', '"\\x56"', '"\\x57"', '"\\x58"', '"\\x59"', '"\\x5A"', '"\\x5B"', '"\\x5C"', '"\\x5D"', '"\\x5E"', '"\\x5F"', '"\\x60"', '"\\x61"', '"\\x62"', '"\\x63"', '"\\x64"', '"\\x65"', '"\\x66"', '"\\x67"', '"\\x68"', '"\\x69"', '"\\x6A"', '"\\x6B"', '"\\x6C"', '"\\x6D"', '"\\x6E"', '"\\x6F"', '"\\x70"', '"\\x71"', '"\\x72"', '"\\x73"', '"\\x74"', '"\\x75"', '"\\x76"', '"\\x77"', '"\\x78"', '"\\x79"', '"\\x7A"', '"\\x7B"', '"\\x7C"', '"\\x7D"', '"\\x7E"', '"\\x7F"', '"\\x80"', '"\\x81"', '"\\x82"', '"\\x83"', '"\\x84"', '"\\x85"', '"\\x86"', '"\\x87"', '"\\x88"', '"\\x89"', '"\\x8A"', '"\\x8B"', '"\\x8C"', '"\\x8D"', '"\\x8E"', '"\\x8F"', '"\\x90"', '"\\x91"', '"\\x92"', '"\\x93"', '"\\x94"', '"\\x95"', '"\\x96"', '"\\x97"', '"\\x98"', '"\\x99"', '"\\x9A"', '"\\x9B"', '"\\x9C"', '"\\x9D"', '"\\x9E"', '"\\x9F"', '"\\xA0"', '"\\xA1"', '"\\xA2"', '"\\xA3"', '"\\xA4"', '"\\xA5"', '"\\xA6"', '"\\xA7"', '"\\xA8"', '"\\xA9"', '"\\xAA"', '"\\xAB"', '"\\xAC"', '"\\xAD"', '"\\xAE"', '"\\xAF"', '"\\xB0"', '"\\xB1"', '"\\xB2"', '"\\xB3"', '"\\xB4"', '"\\xB5"', '"\\xB6"', '"\\xB7"', '"\\xB8"', '"\\xB9"', '"\\xBA"', '"\\xBB"', '"\\xBC"', '"\\xBD"', '"\\xBE"', '"\\xBF"', '"\\xC0"', '"\\xC1"', '"\\xC2"', '"\\xC3"', '"\\xC4"', '"\\xC5"', '"\\xC6"', '"\\xC7"', '"\\xC8"', '"\\xC9"', '"\\xCA"', '"\\xCB"', '"\\xCC"', '"\\xCD"', '"\\xCE"', '"\\xCF"', '"\\xD0"', '"\\xD1"', '"\\xD2"', '"\\xD3"', '"\\xD4"', '"\\xD5"', '"\\xD6"', '"\\xD7"', '"\\xD8"', '"\\xD9"', '"\\xDA"', '"\\xDB"', '"\\xDC"', '"\\xDD"', '"\\xDE"', '"\\xDF"', '"\\xE0"', '"\\xE1"', '"\\xE2"', '"\\xE3"', '"\\xE4"', '"\\xE5"', '"\\xE6"', '"\\xE7"', '"\\xE8"', '"\\xE9"', '"\\xEA"', '"\\xEB"', '"\\xEC"', '"\\xED"', '"\\xEE"', '"\\xEF"', '"\\xF0"', '"\\xF1"', '"\\xF2"', '"\\xF3"', '"\\xF4"', '"\\xF5"', '"\\xF6"', '"\\xF7"', '"\\xF8"', '"\\xF9"', '"\\xFA"', '"\\xFB"', '"\\xFC"', '"\\xFD"', '"\\xFE"', '"\\xFF"'];
$byte_index = 0;
foreach ($hex_inputs as $literal) {
    $decoded /** string */ = \check_bodies\Byte_Literals::decode($literal);
    if ((string_byte_len($decoded) !== 1) || (string_byte_at($decoded, 0) !== $byte_index) || ($decoded !== string_byte_from_int($byte_index))) { throw new \LogicException("Hex byte mismatch"); }
    ++$byte_index;
}
echo "hex-bytes=", $byte_index, "\n";
$quoted_inputs /** vector<string> */ = ['"\\n\\r\\t\\v\\f\\e\\$"', '\'\\n\\r\\t\\v\\f\\e\\$\'', '"\\xFz\\xGG\\4007\\7778"', '\'é😀\'', '"$1 $-"', '"\\q\\x"', '\'\\\'', '"\\"'];
foreach ($quoted_inputs as $literal) {
    $decoded /** string */ = \check_bodies\Byte_Literals::decode($literal);
    for ($i /** int */ = 0; $i < string_byte_len($decoded); ++$i) { echo string_byte_at($decoded, $i), ":"; }
    echo "\n";
}
$bad_literals /** vector<string> */ = ['', '\'', 'text', '"$a"', '"${x}"', '"$é"', '"\\u{41}"'];
foreach ($bad_literals as $literal) {
    try { \check_bodies\Byte_Literals::decode($literal); throw new \LogicException("Accepted invalid literal"); }
    catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
}
foreach ([-1, 256] as $invalid_byte) {
    try { string_byte_from_int($invalid_byte); throw new \LogicException("Accepted invalid byte"); }
    catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
}

// Unresolved references retain exact identity and ordered family membership.
$named_reference /** \type_model\named_type_reference */ = new \type_model\named_type_reference('é_name', 'example');
$provider_reference /** \type_model\provider_type_reference */ = new \type_model\provider_type_reference('provider-v1', 'native-id');
$parameter_reference /** \type_model\parameter_type_reference */ = new \type_model\parameter_type_reference('owner', 3);
$named_anchor /** \type_model\type_reference */ = $named_reference;
$provider_anchor /** \type_model\type_reference */ = $provider_reference;
$parameter_anchor /** \type_model\type_reference */ = $parameter_reference;
$reference_arguments /** vector<\type_model\type_reference> */ = [$named_anchor, $provider_anchor, $parameter_anchor, $named_anchor];
$family_reference /** \type_model\family_type_reference */ = new \type_model\family_type_reference('family-id', $reference_arguments);
$reference_arguments[0] = $provider_anchor;
if (($family_reference->arguments[0] !== $named_anchor) || ($family_reference->arguments[1] !== $provider_anchor) ||
    ($family_reference->arguments[2] !== $parameter_anchor) || ($family_reference->arguments[3] !== $named_anchor)) {
    throw new \LogicException('Type-reference order or identity changed');
}
$copied_arguments /** vector<\type_model\type_reference> */ = $family_reference->arguments;
$copied_arguments[1] = $named_anchor;
if ($family_reference->arguments[1] !== $provider_anchor) { throw new \LogicException('Family membership alias'); }
$family_anchor /** \type_model\type_reference */ = $family_reference;
$outer_arguments /** vector<\type_model\type_reference> */ = [$family_anchor, $family_anchor];
$outer_reference /** \type_model\family_type_reference */ = new \type_model\family_type_reference('outer', $outer_arguments);
if (($outer_reference->arguments[0] !== $family_anchor) || ($outer_reference->arguments[1] !== $family_anchor)) {
    throw new \LogicException('Nested family identity changed');
}
$empty_arguments /** vector<\type_model\type_reference> */ = [];
$empty_reference /** \type_model\family_type_reference */ = new \type_model\family_type_reference('', $empty_arguments);
echo $named_reference->namespace_name, ':', $named_reference->name, "\n";
echo $provider_reference->provider, ':', $provider_reference->id, "\n";
echo $parameter_reference->owner, ':', $parameter_reference->slot, "\n";
echo $family_reference->family, ':', count($family_reference->arguments), ':', $outer_reference->family, ':', count($outer_reference->arguments), ':', count($empty_reference->arguments), "\n";
echo "type-references:identity-order-independent-membership\n";

$native_scope = new \compile\native_project('project-key', '//a/./é//', '/out/.../');
echo $native_scope->project_key, ':', $native_scope->source_root, ':', $native_scope->output_root, "\n";
$root_scope = new \compile\native_project('key', '/./', '///');
echo $root_scope->source_root, ':', $root_scope->output_root, "\n";
$binary_scope = new \compile\native_project('key', '/' . string_byte_from_int(255), '/out');
echo string_byte_at($binary_scope->source_root, 1), "\n";
$invalid_roots /** vector<string> */ = ['', 'relative', '/..', '/a/../b', '/' . string_byte_from_int(0)];
foreach ($invalid_roots as $root) {
    try { $bad_scope = new \compile\native_project('key', $root, '/out'); }
    catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
}
try { $bad_scope = new \compile\native_project('', '/', '/'); }
catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $bad_scope = new \compile\native_project('a' . string_byte_from_int(0), '/', '/'); }
catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }
try { $bad_scope = new \compile\native_project('key', '/', '/out/..'); }
catch (\InvalidArgumentException $error) { echo $error->getMessage(), "\n"; }

$manifest_snapshot = new \read_manifest\Project_Manifest();
echo $manifest_snapshot->to_json(), "\n";
$manifest_snapshot->path = 'é/project.json';
$manifest_snapshot->content = "{\n\t\"entry\":\"a.phs\"\n}";
$manifest_snapshot->directory = '/src';
$manifest_roots /** vector<string> */ = ['src', 'é😀'];
$manifest_snapshot->source_folder_paths = $manifest_roots;
$manifest_snapshot->entry_path = 'a.phs';
echo $manifest_snapshot->to_json(), "\n";
$manifest_snapshot->content = null;
$manifest_files /** vector<string> */ = ['a.phs'];
$manifest_snapshot->source_file_paths = $manifest_files;
echo $manifest_snapshot->to_json(), "\n";
$manifest_snapshot->path = string_byte_from_int(255);
try { echo $manifest_snapshot->to_json(); }
catch (\Exception $error) {
    echo $error->getMessage(), ':', $error->getCode(), "\n";
    $manifest_cause = $error->getPrevious();
    if ($manifest_cause !== null) { echo $manifest_cause->getMessage(), ':', $manifest_cause->getCode(), "\n"; }
}
$control_bytes /** string */ = '';
for ($i /** int */ = 0; $i < 32; ++$i) { $control_bytes = $control_bytes . string_byte_from_int($i); }
echo json_quote($control_bytes), "\n";

class Nullable_Scalar_Defaults {
    public ?int $number = 0;
    public ?bool $flag = false;
    public ?string $text = '';
}
$nullable_defaults = new Nullable_Scalar_Defaults();
$default_number /** int */ = 9;
$default_flag /** bool */ = true;
$default_text /** string */ = 'wrong';
if (!take_nullable($default_number, $nullable_defaults->number) || !take_nullable($default_flag, $nullable_defaults->flag) || !take_nullable($default_text, $nullable_defaults->text)) {
    throw new \LogicException('Missing scalar default');
}
if (($default_number !== 0) || ($default_flag !== false) || ($default_text !== '')) { throw new \LogicException('Wrong scalar default'); }
echo "nullable-defaults:0:false:empty\n";

$filesystem_root /** string */ = '/tmp/scpp-context-port-llaqrgik/filesystem';
$filesystem_task = new \read_sources\source_scan_task(1, 0, $filesystem_root, 'good');
$filesystem_result = \read_sources\Source_Scanner::scan($filesystem_task);
if ($filesystem_result->task !== $filesystem_task) { throw new \LogicException('Lost scanner task identity'); }
foreach ($filesystem_result->files as $file) { echo $file->relative_path, ':', $file->mtime, ':', $file->size, "\n"; }
foreach ($filesystem_result->directories as $directory) { echo 'directory:', $directory, "\n"; }
$filesystem_names /** vector<string> */ = ['z.phs', 'note.txt', 'a.phs'];
$filesystem_selected_task = new \read_sources\source_scan_task(2, 0, $filesystem_root, 'good', $filesystem_names);
$filesystem_selected = \read_sources\Source_Scanner::scan($filesystem_selected_task);
foreach ($filesystem_selected->files as $file) { echo 'selected:', $file->relative_path, ':', $file->size, "\n"; }
$filesystem_empty /** vector<string> */ = [];
$filesystem_empty_task = new \read_sources\source_scan_task(3, 0, $filesystem_root, 'missing', $filesystem_empty);
$filesystem_empty_result = \read_sources\Source_Scanner::scan($filesystem_empty_task);
echo 'empty-selection:', count($filesystem_empty_result->files), ':', count($filesystem_empty_result->directories), "\n";
$filesystem_bad_tasks /** vector<\read_sources\source_scan_task> */ = [
    new \read_sources\source_scan_task(4, 0, $filesystem_root, 'missing'),
    new \read_sources\source_scan_task(5, 0, $filesystem_root, 'linked'),
    new \read_sources\source_scan_task(6, 0, $filesystem_root, 'bad')
];
foreach ($filesystem_bad_tasks as $filesystem_bad_task) {
    try { $filesystem_bad = \read_sources\Source_Scanner::scan($filesystem_bad_task); throw new \LogicException('Accepted bad directory'); }
    catch (\Exception $filesystem_error) {
        if (string_byte_starts_with($filesystem_error->getMessage(), 'Cannot scan source directory:')) { echo "scan-missing\n"; }
        else if (string_byte_starts_with($filesystem_error->getMessage(), 'Symbolic links inside source roots are unsupported:')) { echo "scan-symlink\n"; }
        else { throw $filesystem_error; }
    }
}
$filesystem_missing_value /** int */ = 77;
if (take_false($filesystem_missing_value, fs_size($filesystem_root . '/missing'))) { throw new \LogicException('Missing file size accepted'); }
echo 'missing-size-retained:', $filesystem_missing_value, "\n";

$symbol_components /** vector<string> */ = ['type', 'simple_cpp', 'size', 'move_constructible'];
echo \runtime_preparation\Symbols::name($symbol_components), "\n";
$symbol_edge_components /** vector<string> */ = ['', '_X_', '_x2E_', 'é'];
echo \runtime_preparation\Symbols::name($symbol_edge_components), "\n";
echo \runtime_preparation\Symbols::append('rp_type_X_simple__cpp_X_size', 'move_constructible'), "\n";
$symbol_all_bytes /** string */ = '';
for ($symbol_byte /** int */ = 0; $symbol_byte < 256; ++$symbol_byte) { $symbol_all_bytes = $symbol_all_bytes . string_byte_from_int($symbol_byte); }
$symbol_binary_components /** vector<string> */ = [$symbol_all_bytes];
echo \runtime_preparation\Symbols::name($symbol_binary_components), "\n";
$symbol_no_components /** vector<string> */ = [];
try { echo \runtime_preparation\Symbols::name($symbol_no_components); }
catch (\InvalidArgumentException $symbol_error) { echo $symbol_error->getMessage(), "\n"; }

// Structural queries and lazy member traversal share the cumulative project.

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

// Optional local initializers use the same explicit evaluation boundary as fields.
$query_tree->nodes[11]->kind = \parse\syntax_kind::local_declaration;
$query_tree->nodes[11]->first_child_id = 14;
$query_tree->nodes[13]->next_sibling_id = 13;
$query_tree->nodes[12]->next_sibling_id = 0;
$query_local = \parse\Syntax_Access::local_declaration_parts($query_tree, 12);
echo 'local:', $query_local->variable_id, ':', $query_local->type_syntax_id, ':', $query_local->initializer_id, "\n";
$query_tree->nodes[12]->next_sibling_id = 18;
$query_local = \parse\Syntax_Access::local_declaration_parts($query_tree, 12);
echo 'initializer:', $query_local->initializer_id, "\n";
$query_tree->nodes[17]->next_sibling_id = 17;
try { $query_local = \parse\Syntax_Access::local_declaration_parts($query_tree, 12); }
catch (\LogicException $query_error) { echo $query_error->getMessage(), "\n"; }
