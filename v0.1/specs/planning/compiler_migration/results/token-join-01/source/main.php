<?php
// <scpp-imports>
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
