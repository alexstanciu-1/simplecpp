# Strict library registry inventory
Doc Status: planning

This snapshot inventories **all 241 entries** in the strict source-symbol registry,
including internal entries. Registry membership means registered lowering, not
blanket validation of every overload, target platform or module configuration.
No API below is newly implemented by this document. Only the three scalar `take`
portability helpers exist today. See [language families](language.md) and
[conversion decisions](conversion.md) for types, syntax and additional surfaces.

Source: [strict registry](../../../generators/php/specs/php_runtime_symbols_strict.json).
SHA-256: `35076d867808c0731d21b0fb83372779b9fbe552af80d633f9db635a48b2df8b`.
The table covers each registry name exactly once. Module requirements come from
owning specs/project configuration, not the presence of a symbol in this list.

The separate [contract metadata](../../../generators/php/specs/php_runtime_symbol_contracts_strict.json)
is a normalization foundation for compiler consumers. Its `blocked` status does
not mean the existing S2S cannot call that function. Missing signatures there are
metadata gaps, not permission to invent signatures. Per-function overload/default,
mutation and error details must be read from owners before implementing adapters.

PHP spellings below are **proposals**, except `take_*`. Ordinary PHP functions are
used where adequate. Proposed overrides use `scpp\compat` function imports;
Simple C++-specific libraries use `scpp` imports or qualified names. The table
shows bare calls after appropriate imports, not global builtin redeclarations.
All namespaces are lowercase. Exact adapter selection remains per-contract;
a matching name alone is not a reason to wrap a PHP function. Managed imports are implemented for the initial script subset; these additional
libraries are not implemented yet. `args` is a placeholder for
the accepted arguments, not unrestricted variadics. Native targets are provenance;
the converter emits PHP++, never C++.

## Core helpers and predicates (8)

[Contract/reference](../../../runtime/specs/spec.md). Explicit PHP/framework operation; preserve wrapper/probe contracts.

| Registry source name | Proposed executable PHP form | Emitted PHP++ | Registered native target | Portability status |
| --- | --- | --- | --- | --- |
| `count` | `count(args)` | `count(args)` | `php::count` | proposed; adapter/map not implemented |
| `empty` | `empty(args)` | `empty(args)` | `php::empty` | proposed; adapter/map not implemented |
| `is_bool` | `is_bool(args)` (ordinary PHP) | `is_bool(args)` | `php::is_bool` | no PHP shim needed; is_bool accepted by central policy |
| `is_float` | `is_float(args)` (ordinary PHP) | `is_float(args)` | `php::is_float` | no PHP shim needed; converter call acceptance pending |
| `is_int` | `is_int(args)` (ordinary PHP) | `is_int(args)` | `php::is_int` | no PHP shim needed; converter call acceptance pending |
| `isset` | `isset(args)` | `isset(args)` | `php::isset` | proposed; adapter/map not implemented |
| `take` | `take_nullable / take_false / take_bool` | `take(...)` | `php::take` | implemented scalar slice; result/error and tagged bool payloads pending |
| `to_dynamic` | `to_dynamic(args)` | `to_dynamic(args)` | `to_dynamic` | proposed; adapter/map not implemented |
## Debugging and diagnostics (6)

[Contract/reference](../../../specs/runtime/error_handling.md). PHP logger approximation; exact native diagnostics tested after porting.

| Registry source name | Proposed executable PHP form | Emitted PHP++ | Registered native target | Portability status |
| --- | --- | --- | --- | --- |
| `dbg` | `dbg(args)` | `dbg(args)` | `php::dbg` | proposed; adapter/map not implemented |
| `dbg_enabled` | `dbg_enabled(args)` | `dbg_enabled(args)` | `php::dbg_enabled` | proposed; adapter/map not implemented |
| `dbg_if` | `dbg_if(args)` | `dbg_if(args)` | `php::dbg_if` | proposed; adapter/map not implemented |
| `dbg_set` | `dbg_set(args)` | `dbg_set(args)` | `php::dbg_set` | proposed; adapter/map not implemented |
| `dbg_unset` | `dbg_unset(args)` | `dbg_unset(args)` | `php::dbg_unset` | proposed; adapter/map not implemented |
| `var_dump` | `var_dump(args)` | `var_dump(args)` | `php::var_dump` | proposed; adapter/map not implemented |
## CLI, environment and process (6)

[Contract/reference](../../../specs/builtins/process/README.md). Adapt PHP globals/getenv/process calls; preserve failure states; no timing equivalence.

| Registry source name | Proposed executable PHP form | Emitted PHP++ | Registered native target | Portability status |
| --- | --- | --- | --- | --- |
| `cli_argc` | `cli_argc(args)` | `cli_argc(args)` | `php::cli_argc` | proposed; adapter/map not implemented |
| `cli_args` | `cli_args(args)` | `cli_args(args)` | `php::cli_args` | proposed; adapter/map not implemented |
| `cli_argv` | `cli_argv(args)` | `cli_argv(args)` | `php::cli_argv` | proposed; adapter/map not implemented |
| `getenv` | `getenv(args)` | `getenv(args)` | `php::getenv` | proposed; adapter/map not implemented |
| `microtime` | `microtime(args)` | `microtime(args)` | `php::microtime` | proposed; adapter/map not implemented |
| `shell_exec` | `shell_exec(args)` | `shell_exec(args)` | `php::shell_exec` | proposed; adapter/map not implemented |
## Layout and memory observations (6)

[Contract/reference](../../../specs/compact_layout_types.md). Layout is native-only; PHP memory observations are not native byte counts.

| Registry source name | Proposed executable PHP form | Emitted PHP++ | Registered native target | Portability status |
| --- | --- | --- | --- | --- |
| `layout_alignof` | `layout_alignof(args)` | `layout_alignof(args)` | `layout_alignof` | proposed; adapter/map not implemented |
| `layout_field_sizeof` | `layout_field_sizeof(args)` | `layout_field_sizeof(args)` | `layout_field_sizeof` | proposed; adapter/map not implemented |
| `layout_offsetof` | `layout_offsetof(args)` | `layout_offsetof(args)` | `layout_offsetof` | proposed; adapter/map not implemented |
| `layout_sizeof` | `layout_sizeof(args)` | `layout_sizeof(args)` | `layout_sizeof` | proposed; adapter/map not implemented |
| `memory_get_peak_usage` | `memory_get_peak_usage(args)` | `memory_get_peak_usage(args)` | `memory_get_peak_usage` | proposed; adapter/map not implemented |
| `memory_get_usage` | `memory_get_usage(args)` | `memory_get_usage(args)` | `memory_get_usage` | proposed; adapter/map not implemented |
## PHP-like string operations (23)

[Contract/reference](../../../specs/simple_cpp_php_strict_quick_learn.md). PHP builtin adapter for documented subset; explicit failure wrappers when needed.

| Registry source name | Proposed executable PHP form | Emitted PHP++ | Registered native target | Portability status |
| --- | --- | --- | --- | --- |
| `bin2hex` | `bin2hex(args)` | `bin2hex(args)` | `str::hex_encode` | proposed; adapter/map not implemented |
| `explode` | `explode(args)` | `explode(args)` | `php::explode` | proposed; adapter/map not implemented |
| `hex2bin` | `hex2bin(args)` | `hex2bin(args)` | `php::hex2bin` | proposed; adapter/map not implemented |
| `implode` | `implode(args)` | `implode(args)` | `php::implode` | proposed; adapter/map not implemented |
| `lcfirst` | `lcfirst(args)` | `lcfirst(args)` | `str::lcfirst` | proposed; adapter/map not implemented |
| `ltrim` | `ltrim(args)` | `ltrim(args)` | `str::ltrim` | proposed; adapter/map not implemented |
| `number_format` | `number_format(args)` | `number_format(args)` | `str::number_format` | proposed; adapter/map not implemented |
| `rtrim` | `rtrim(args)` | `rtrim(args)` | `str::rtrim` | proposed; adapter/map not implemented |
| `str_contains` | `str_contains(args)` | `str_contains(args)` | `php::str_contains` | proposed; adapter/map not implemented |
| `str_ends_with` | `str_ends_with(args)` | `str_ends_with(args)` | `str::ends_with` | proposed; adapter/map not implemented |
| `str_pad` | `str_pad(args)` | `str_pad(args)` | `str::pad` | proposed; adapter/map not implemented |
| `str_replace` | `str_replace(args)` | `str_replace(args)` | `str::replace` | proposed; adapter/map not implemented |
| `str_starts_with` | `str_starts_with(args)` | `str_starts_with(args)` | `str::starts_with` | proposed; adapter/map not implemented |
| `strlen` | `strlen(args)` | `strlen(args)` | `str::length` | proposed; adapter/map not implemented |
| `strpos` | `strpos(args)` | `strpos(args)` | `php::strpos` | proposed; adapter/map not implemented |
| `strrpos` | `strrpos(args)` | `strrpos(args)` | `php::strrpos` | proposed; adapter/map not implemented |
| `strtolower` | `strtolower(args)` | `strtolower(args)` | `str::lower` | proposed; adapter/map not implemented |
| `strtoupper` | `strtoupper(args)` | `strtoupper(args)` | `str::upper` | proposed; adapter/map not implemented |
| `substr` | `substr(args)` | `substr(args)` | `str::substr` | proposed; adapter/map not implemented |
| `substr_compare` | `substr_compare(args)` | `substr_compare(args)` | `str::substr_compare` | proposed; adapter/map not implemented |
| `substr_replace` | `substr_replace(args)` | `substr_replace(args)` | `str::substr_replace` | proposed; adapter/map not implemented |
| `trim` | `trim(args)` | `trim(args)` | `str::trim` | proposed; adapter/map not implemented |
| `ucfirst` | `ucfirst(args)` | `ucfirst(args)` | `str::ucfirst` | proposed; adapter/map not implemented |
## Byte, Unicode and text builders (31)

[Contract/reference](../../../specs/compiler_support_runtime_contract.md). Byte/Unicode units explicit; PHP builder objects may allocate/copy freely.

| Registry source name | Proposed executable PHP form | Emitted PHP++ | Registered native target | Portability status |
| --- | --- | --- | --- | --- |
| `string_byte_at` | `string_byte_at(args)` | `string_byte_at(args)` | `php::string_byte_at` | proposed; adapter/map not implemented |
| `string_byte_find` | `string_byte_find(args)` | `string_byte_find(args)` | `php::string_byte_find` | proposed; adapter/map not implemented |
| `string_byte_len` | `string_byte_len(args)` | `string_byte_len(args)` | `str::byte_length` | proposed; adapter/map not implemented |
| `string_byte_slice` | `string_byte_slice(args)` | `string_byte_slice(args)` | `str::byte_slice` | proposed; adapter/map not implemented |
| `string_byte_slice_equals` | `string_byte_slice_equals(args)` | `string_byte_slice_equals(args)` | `php::string_byte_slice_equals` | proposed; adapter/map not implemented |
| `string_grapheme_count` | `string_grapheme_count(args)` | `string_grapheme_count(args)` | `str::grapheme_count` | proposed; adapter/map not implemented |
| `string_grapheme_slice` | `string_grapheme_slice(args)` | `string_grapheme_slice(args)` | `str::grapheme_slice` | proposed; adapter/map not implemented |
| `string_parts_builder_append_bool` | `string_parts_builder_append_bool(args)` | `string_parts_builder_append_bool(args)` | `str::string_parts_builder_append_bool` | proposed; adapter/map not implemented |
| `string_parts_builder_append_int` | `string_parts_builder_append_int(args)` | `string_parts_builder_append_int(args)` | `str::string_parts_builder_append_int` | proposed; adapter/map not implemented |
| `string_parts_builder_append_string` | `string_parts_builder_append_string(args)` | `string_parts_builder_append_string(args)` | `str::string_parts_builder_append_string` | proposed; adapter/map not implemented |
| `string_parts_builder_byte_len` | `string_parts_builder_byte_len(args)` | `string_parts_builder_byte_len(args)` | `str::string_parts_builder_byte_len` | proposed; adapter/map not implemented |
| `string_parts_builder_capacity` | `string_parts_builder_capacity(args)` | `string_parts_builder_capacity(args)` | `str::string_parts_builder_capacity` | proposed; adapter/map not implemented |
| `string_parts_builder_clear` | `string_parts_builder_clear(args)` | `string_parts_builder_clear(args)` | `str::string_parts_builder_clear` | proposed; adapter/map not implemented |
| `string_parts_builder_count` | `string_parts_builder_count(args)` | `string_parts_builder_count(args)` | `str::string_parts_builder_count` | proposed; adapter/map not implemented |
| `string_parts_builder_create` | `string_parts_builder_create(args)` | `string_parts_builder_create(args)` | `str::string_parts_builder_create` | proposed; adapter/map not implemented |
| `string_parts_builder_reserve` | `string_parts_builder_reserve(args)` | `string_parts_builder_reserve(args)` | `str::string_parts_builder_reserve` | proposed; adapter/map not implemented |
| `string_parts_builder_to_string` | `string_parts_builder_to_string(args)` | `string_parts_builder_to_string(args)` | `str::string_parts_builder_to_string` | proposed; adapter/map not implemented |
| `string_utf8_codepoint_at` | `string_utf8_codepoint_at(args)` | `string_utf8_codepoint_at(args)` | `str::utf8_codepoint_at` | proposed; adapter/map not implemented |
| `string_utf8_codepoint_count` | `string_utf8_codepoint_count(args)` | `string_utf8_codepoint_count(args)` | `str::utf8_codepoint_count` | proposed; adapter/map not implemented |
| `string_utf8_slice_codepoints` | `string_utf8_slice_codepoints(args)` | `string_utf8_slice_codepoints(args)` | `str::utf8_slice_codepoints` | proposed; adapter/map not implemented |
| `text_builder_append_bool` | `text_builder_append_bool(args)` | `text_builder_append_bool(args)` | `str::text_builder_append_bool` | proposed; adapter/map not implemented |
| `text_builder_append_byte_span` | `text_builder_append_byte_span(args)` | `text_builder_append_byte_span(args)` | `str::text_builder_append_byte_span` | proposed; adapter/map not implemented |
| `text_builder_append_int` | `text_builder_append_int(args)` | `text_builder_append_int(args)` | `str::text_builder_append_int` | proposed; adapter/map not implemented |
| `text_builder_append_string` | `text_builder_append_string(args)` | `text_builder_append_string(args)` | `str::text_builder_append_string` | proposed; adapter/map not implemented |
| `text_builder_byte_len` | `text_builder_byte_len(args)` | `text_builder_byte_len(args)` | `str::text_builder_byte_len` | proposed; adapter/map not implemented |
| `text_builder_capacity_bytes` | `text_builder_capacity_bytes(args)` | `text_builder_capacity_bytes(args)` | `str::text_builder_capacity_bytes` | proposed; adapter/map not implemented |
| `text_builder_clear` | `text_builder_clear(args)` | `text_builder_clear(args)` | `str::text_builder_clear` | proposed; adapter/map not implemented |
| `text_builder_create` | `text_builder_create(args)` | `text_builder_create(args)` | `str::text_builder_create` | proposed; adapter/map not implemented |
| `text_builder_reserve_bytes` | `text_builder_reserve_bytes(args)` | `text_builder_reserve_bytes(args)` | `str::text_builder_reserve_bytes` | proposed; adapter/map not implemented |
| `text_builder_take_string` | `text_builder_take_string(args)` | `text_builder_take_string(args)` | `str::text_builder_take_string` | proposed; adapter/map not implemented |
| `text_builder_to_string` | `text_builder_to_string(args)` | `text_builder_to_string(args)` | `str::text_builder_to_string` | proposed; adapter/map not implemented |
## Source buffers, spans, line indexes and hashes (22)

[Contract/reference](../../../specs/compiler_support_runtime_contract.md). PHP handle/offset objects and byte algorithms; validity and hash identity require fixtures.

| Registry source name | Proposed executable PHP form | Emitted PHP++ | Registered native target | Portability status |
| --- | --- | --- | --- | --- |
| `byte_span_at` | `byte_span_at(args)` | `byte_span_at(args)` | `source::byte_span_at` | proposed; adapter/map not implemented |
| `byte_span_len` | `byte_span_len(args)` | `byte_span_len(args)` | `source::byte_span_len` | proposed; adapter/map not implemented |
| `byte_span_to_string` | `byte_span_to_string(args)` | `byte_span_to_string(args)` | `source::byte_span_to_string` | proposed; adapter/map not implemented |
| `hash_bytes` | `hash_bytes(args)` | `hash_bytes(args)` | `source::hash_bytes` | proposed; adapter/map not implemented |
| `hash_string` | `hash_string(args)` | `hash_string(args)` | `php::hash_string` | proposed; adapter/map not implemented |
| `source_buffer_byte_at` | `source_buffer_byte_at(args)` | `source_buffer_byte_at(args)` | `source::source_buffer_byte_at` | proposed; adapter/map not implemented |
| `source_buffer_byte_len` | `source_buffer_byte_len(args)` | `source_buffer_byte_len(args)` | `source::source_buffer_byte_len` | proposed; adapter/map not implemented |
| `source_buffer_empty` | `source_buffer_empty(args)` | `source_buffer_empty(args)` | `source::source_buffer_empty` | proposed; adapter/map not implemented |
| `source_buffer_release` | `source_buffer_release(args)` | `source_buffer_release(args)` | `source::source_buffer_release` | proposed; adapter/map not implemented |
| `source_buffer_slice` | `source_buffer_slice(args)` | `source_buffer_slice(args)` | `source::source_buffer_slice` | proposed; adapter/map not implemented |
| `source_buffer_span` | `source_buffer_span(args)` | `source_buffer_span(args)` | `source::source_buffer_span` | proposed; adapter/map not implemented |
| `source_buffer_take` | `source_buffer_take(args)` | `source_buffer_take(args)` | `source::source_buffer_take` | proposed; adapter/map not implemented |
| `source_line_index_build` | `source_line_index_build(args)` | `source_line_index_build(args)` | `source::source_line_index_build` | proposed; adapter/map not implemented |
| `source_line_index_line_column_to_offset` | `source_line_index_line_column_to_offset(args)` | `source_line_index_line_column_to_offset(args)` | `source::source_line_index_line_column_to_offset` | proposed; adapter/map not implemented |
| `source_line_index_line_count` | `source_line_index_line_count(args)` | `source_line_index_line_count(args)` | `source::source_line_index_line_count` | proposed; adapter/map not implemented |
| `source_line_index_offset_to_location` | `source_line_index_offset_to_location(args)` | `source_line_index_offset_to_location(args)` | `source::source_line_index_offset_to_location` | proposed; adapter/map not implemented |
| `source_location_column` | `source_location_column(args)` | `source_location_column(args)` | `source::source_location_column` | proposed; adapter/map not implemented |
| `source_location_line` | `source_location_line(args)` | `source_location_line(args)` | `source::source_location_line` | proposed; adapter/map not implemented |
| `source_location_offset` | `source_location_offset(args)` | `source_location_offset(args)` | `source::source_location_offset` | proposed; adapter/map not implemented |
| `source_text_vector_move_append` | `source_text_vector_move_append(args)` | `source_text_vector_move_append(args)` | `source::source_text_vector_move_append` | proposed; adapter/map not implemented |
| `stable_hash_bytes_u64` | `stable_hash_bytes_u64(args)` | `stable_hash_bytes_u64(args)` | `source::stable_hash_bytes_u64` | proposed; adapter/map not implemented |
| `stable_hash_string_u64` | `stable_hash_string_u64(args)` | `stable_hash_string_u64(args)` | `php::stable_hash_string_u64` | proposed; adapter/map not implemented |
## Token buffers and language tokenizers (12)

[Contract/reference](../../../specs/builtins/tokenizer/token_buffer.md). PHP token-buffer adapter; token_get_all alone is not a PHS/JSS tokenizer.

| Registry source name | Proposed executable PHP form | Emitted PHP++ | Registered native target | Portability status |
| --- | --- | --- | --- | --- |
| `jss_tokenize` | `jss_tokenize(args)` | `jss_tokenize(args)` | `tokenizer::jss_tokenize` | proposed; adapter/map not implemented |
| `jss_tokenize_buffer` | `jss_tokenize_buffer(args)` | `jss_tokenize_buffer(args)` | `tokenizer::jss_tokenize_buffer` | proposed; adapter/map not implemented |
| `phs_tokenize` | `phs_tokenize(args)` | `phs_tokenize(args)` | `tokenizer::phs_tokenize` | proposed; adapter/map not implemented |
| `phs_tokenize_buffer` | `phs_tokenize_buffer(args)` | `phs_tokenize_buffer(args)` | `tokenizer::phs_tokenize_buffer` | proposed; adapter/map not implemented |
| `token_buffer_column` | `token_buffer_column(args)` | `token_buffer_column(args)` | `tokenizer::token_buffer_column` | proposed; adapter/map not implemented |
| `token_buffer_count` | `token_buffer_count(args)` | `token_buffer_count(args)` | `tokenizer::token_buffer_count` | proposed; adapter/map not implemented |
| `token_buffer_flags` | `token_buffer_flags(args)` | `token_buffer_flags(args)` | `tokenizer::token_buffer_flags` | proposed; adapter/map not implemented |
| `token_buffer_kind_id` | `token_buffer_kind_id(args)` | `token_buffer_kind_id(args)` | `tokenizer::token_buffer_kind_id` | proposed; adapter/map not implemented |
| `token_buffer_length` | `token_buffer_length(args)` | `token_buffer_length(args)` | `tokenizer::token_buffer_length` | proposed; adapter/map not implemented |
| `token_buffer_line` | `token_buffer_line(args)` | `token_buffer_line(args)` | `tokenizer::token_buffer_line` | proposed; adapter/map not implemented |
| `token_buffer_start_offset` | `token_buffer_start_offset(args)` | `token_buffer_start_offset(args)` | `tokenizer::token_buffer_start_offset` | proposed; adapter/map not implemented |
| `token_buffer_to_mixed` | `token_buffer_to_mixed(args)` | `token_buffer_to_mixed(args)` | `tokenizer::token_buffer_to_mixed` | proposed; adapter/map not implemented |
## Vector operations (7)

[Contract/reference](../../../specs/compiler_support_runtime_contract.md). PHP array/adapter operations; reserve can be approximate, observable capacity needs a defined model.

| Registry source name | Proposed executable PHP form | Emitted PHP++ | Registered native target | Portability status |
| --- | --- | --- | --- | --- |
| `vector_capacity` | `vector_capacity(args)` | `vector_capacity(args)` | `php::vector_capacity` | proposed; adapter/map not implemented |
| `vector_clear` | `vector_clear(args)` | `vector_clear(args)` | `php::vector_clear` | proposed; adapter/map not implemented |
| `vector_clear_keep_capacity` | `vector_clear_keep_capacity(args)` | `vector_clear_keep_capacity(args)` | `php::vector_clear_keep_capacity` | proposed; adapter/map not implemented |
| `vector_compact` | `vector_compact(args)` | `vector_compact(args)` | `php::vector_compact` | proposed; adapter/map not implemented |
| `vector_filled` | `vector_filled(args)` | `vector_filled(args)` | `php::vector_filled` | proposed; adapter/map not implemented |
| `vector_reserve` | `vector_reserve(args)` | `vector_reserve(args)` | `php::vector_reserve` | proposed; adapter/map not implemented |
| `vector_resize` | `vector_resize(args)` | `vector_resize(args)` | `php::vector_resize` | proposed; adapter/map not implemented |
## Filesystem (18)

[Contract/reference](../../../specs/builtins/filesystem/first_pass.md). PHP filesystem adapter translating result/error/absence; preserve empty and zero successes.

| Registry source name | Proposed executable PHP form | Emitted PHP++ | Registered native target | Portability status |
| --- | --- | --- | --- | --- |
| `fs_basename` | `fs_basename(args)` | `fs_basename(args)` | `fs::basename` | proposed; adapter/map not implemented |
| `fs_copy` | `fs_copy(args)` | `fs_copy(args)` | `fs::copy` | proposed; adapter/map not implemented |
| `fs_dirname` | `fs_dirname(args)` | `fs_dirname(args)` | `fs::dirname` | proposed; adapter/map not implemented |
| `fs_exists` | `fs_exists(args)` | `fs_exists(args)` | `fs::exists` | proposed; adapter/map not implemented |
| `fs_get` | `fs_get(args)` | `fs_get(args)` | `fs::get` | proposed; adapter/map not implemented |
| `fs_is_dir` | `fs_is_dir(args)` | `fs_is_dir(args)` | `fs::is_dir` | implemented; [scanner facade contract](../compiler_source_scanner_slice.md) |
| `fs_is_file` | `fs_is_file(args)` | `fs_is_file(args)` | `fs::is_file` | implemented; [scanner facade contract](../compiler_source_scanner_slice.md) |
| `fs_is_link` | `fs_is_link(args)` | `fs_is_link(args)` | `fs::is_link` | implemented; [scanner facade contract](../compiler_source_scanner_slice.md) |
| `fs_mkdir` | `fs_mkdir(args)` | `fs_mkdir(args)` | `fs::mkdir` | proposed; adapter/map not implemented |
| `fs_mtime` | `fs_mtime(args)` | `fs_mtime(args)` | `fs::mtime` | implemented; [scanner facade contract](../compiler_source_scanner_slice.md) |
| `fs_put` | `fs_put(args)` | `fs_put(args)` | `fs::put` | proposed; adapter/map not implemented |
| `fs_realpath` | `fs_realpath(args)` | `fs_realpath(args)` | `fs::realpath` | proposed; adapter/map not implemented |
| `fs_remove` | `fs_remove(args)` | `fs_remove(args)` | `fs::remove` | proposed; adapter/map not implemented |
| `fs_rename` | `fs_rename(args)` | `fs_rename(args)` | `fs::rename` | proposed; adapter/map not implemented |
| `fs_rmdir` | `fs_rmdir(args)` | `fs_rmdir(args)` | `fs::rmdir` | proposed; adapter/map not implemented |
| `fs_scan` | `fs_scan(args)` | `fs_scan(args)` | `fs::scan` | implemented; [scanner facade contract](../compiler_source_scanner_slice.md) |
| `fs_size` | `fs_size(args)` | `fs_size(args)` | `fs::size` | implemented; [scanner facade contract](../compiler_source_scanner_slice.md) |
| `fs_touch` | `fs_touch(args)` | `fs_touch(args)` | `fs::touch` | proposed; adapter/map not implemented |
## Resource IO (10)

[Contract/reference](../../../specs/builtins/filesystem/first_pass.md). PHP resource adapter; explicit seek/read/write/close result contracts.

| Registry source name | Proposed executable PHP form | Emitted PHP++ | Registered native target | Portability status |
| --- | --- | --- | --- | --- |
| `io_close` | `io_close(args)` | `io_close(args)` | `io::close` | proposed; adapter/map not implemented |
| `io_eof` | `io_eof(args)` | `io_eof(args)` | `io::eof` | proposed; adapter/map not implemented |
| `io_flush` | `io_flush(args)` | `io_flush(args)` | `io::flush` | proposed; adapter/map not implemented |
| `io_open` | `io_open(args)` | `io_open(args)` | `io::open` | proposed; adapter/map not implemented |
| `io_read` | `io_read(args)` | `io_read(args)` | `io::read` | proposed; adapter/map not implemented |
| `io_read_line` | `io_read_line(args)` | `io_read_line(args)` | `io::read_line` | proposed; adapter/map not implemented |
| `io_rewind` | `io_rewind(args)` | `io_rewind(args)` | `io::rewind` | proposed; adapter/map not implemented |
| `io_seek` | `io_seek(args)` | `io_seek(args)` | `io::seek` | proposed; adapter/map not implemented |
| `io_tell` | `io_tell(args)` | `io_tell(args)` | `io::tell` | proposed; adapter/map not implemented |
| `io_write` | `io_write(args)` | `io_write(args)` | `io::write` | proposed; adapter/map not implemented |
## JSON (2)

[Contract/reference](../../../specs/builtins/json/first_pass.md). Use PHP JSON internally, wrap explicit Result/Error; null and false are successful values.

| Registry source name | Proposed executable PHP form | Emitted PHP++ | Registered native target | Portability status |
| --- | --- | --- | --- | --- |
| `json_decode` | `json_decode(args)` | `json_decode(args)` | `json::decode` | proposed; adapter/map not implemented |
| `json_encode` | `json_encode(args)` | `json_encode(args)` | `json::encode` | proposed; adapter/map not implemented |
## Datetime (11)

[Contract/reference](../../../specs/builtins/datetime/first_pass.md). PHP clock/date parser adapter restricted to documented formats and units.

| Registry source name | Proposed executable PHP form | Emitted PHP++ | Registered native target | Portability status |
| --- | --- | --- | --- | --- |
| `dt_format` | `dt_format(args)` | `dt_format(args)` | `dt::format_local` | proposed; adapter/map not implemented |
| `dt_format_iso_utc` | `dt_format_iso_utc(args)` | `dt_format_iso_utc(args)` | `dt::format_iso_utc` | proposed; adapter/map not implemented |
| `dt_format_now` | `dt_format_now(args)` | `dt_format_now(args)` | `dt::format_local_now` | proposed; adapter/map not implemented |
| `dt_monotonic_ms` | `dt_monotonic_ms(args)` | `dt_monotonic_ms(args)` | `dt::monotonic_millis` | proposed; adapter/map not implemented |
| `dt_monotonic_ns` | `dt_monotonic_ns(args)` | `dt_monotonic_ns(args)` | `dt::monotonic_nanos` | proposed; adapter/map not implemented |
| `dt_monotonic_us` | `dt_monotonic_us(args)` | `dt_monotonic_us(args)` | `dt::monotonic_micros` | proposed; adapter/map not implemented |
| `dt_now` | `dt_now(args)` | `dt_now(args)` | `dt::now_unix_seconds` | proposed; adapter/map not implemented |
| `dt_now_ms` | `dt_now_ms(args)` | `dt_now_ms(args)` | `dt::now_unix_millis` | proposed; adapter/map not implemented |
| `dt_parse` | `dt_parse(args)` | `dt_parse(args)` | `dt::parse_common_local` | proposed; adapter/map not implemented |
| `dt_parse_iso_utc` | `dt_parse_iso_utc(args)` | `dt_parse_iso_utc(args)` | `dt::parse_iso_utc` | proposed; adapter/map not implemented |
| `dt_sleep_ms` | `dt_sleep_ms(args)` | `dt_sleep_ms(args)` | `dt::sleep_millis` | proposed; adapter/map not implemented |
## Regex (13)

[Contract/reference](../../../specs/builtins/regex/first_pass.md). PHP preg adapter with named/ordered result-shape conversion; optional extension requirements.

| Registry source name | Proposed executable PHP form | Emitted PHP++ | Registered native target | Portability status |
| --- | --- | --- | --- | --- |
| `regex_filter` | `regex_filter(args)` | `regex_filter(args)` | `regex::filter` | proposed; adapter/map not implemented |
| `regex_grep` | `regex_grep(args)` | `regex_grep(args)` | `regex::grep` | proposed; adapter/map not implemented |
| `regex_jit_available` | `regex_jit_available(args)` | `regex_jit_available(args)` | `regex::jit_available` | proposed; adapter/map not implemented |
| `regex_match` | `regex_match(args)` | `regex_match(args)` | `regex::match` | proposed; adapter/map not implemented |
| `regex_match_all` | `regex_match_all(args)` | `regex_match_all(args)` | `regex::match_all` | proposed; adapter/map not implemented |
| `regex_match_all_named` | `regex_match_all_named(args)` | `regex_match_all_named(args)` | `regex::match_all_named` | proposed; adapter/map not implemented |
| `regex_match_all_pattern_order` | `regex_match_all_pattern_order(args)` | `regex_match_all_pattern_order(args)` | `regex::match_all_pattern_order` | proposed; adapter/map not implemented |
| `regex_match_named` | `regex_match_named(args)` | `regex_match_named(args)` | `regex::match_named` | proposed; adapter/map not implemented |
| `regex_quote` | `regex_quote(args)` | `regex_quote(args)` | `regex::quote` | proposed; adapter/map not implemented |
| `regex_replace` | `regex_replace(args)` | `regex_replace(args)` | `regex::replace` | proposed; adapter/map not implemented |
| `regex_replace_callback` | `regex_replace_callback(args)` | `regex_replace_callback(args)` | `regex::replace_callback` | proposed; adapter/map not implemented |
| `regex_replace_callback_array` | `regex_replace_callback_array(args)` | `regex_replace_callback_array(args)` | `regex::replace_callback_array` | proposed; adapter/map not implemented |
| `regex_split` | `regex_split(args)` | `regex_split(args)` | `regex::split` | proposed; adapter/map not implemented |
## HTTP/cURL (9)

[Contract/reference](../../../specs/builtins/curl/first_pass.md). PHP cURL extension adapter, strict response/error objects; not legacy curl result assumptions.

| Registry source name | Proposed executable PHP form | Emitted PHP++ | Registered native target | Portability status |
| --- | --- | --- | --- | --- |
| `curl_close` | `curl_close(args)` | `curl_close(args)` | `curl::close` | proposed; adapter/map not implemented |
| `curl_errno` | `curl_errno(args)` | `curl_errno(args)` | `curl::errno_code` | proposed; adapter/map not implemented |
| `curl_error` | `curl_error(args)` | `curl_error(args)` | `curl::error_string` | proposed; adapter/map not implemented |
| `curl_exec` | `curl_exec(args)` | `curl_exec(args)` | `curl::exec` | proposed; adapter/map not implemented |
| `curl_getinfo` | `curl_getinfo(args)` | `curl_getinfo(args)` | `curl::getinfo` | proposed; adapter/map not implemented |
| `curl_init` | `curl_init(args)` | `curl_init(args)` | `curl::init` | proposed; adapter/map not implemented |
| `curl_reset` | `curl_reset(args)` | `curl_reset(args)` | `curl::reset` | proposed; adapter/map not implemented |
| `curl_setopt` | `curl_setopt(args)` | `curl_setopt(args)` | `curl::setopt` | proposed; adapter/map not implemented |
| `curl_strerror` | `curl_strerror(args)` | `curl_strerror(args)` | `curl::strerror` | proposed; adapter/map not implemented |
## Thread-backed tasks (19)

[Contract/reference](../../../specs/builtins/tasks/first_pass.md). Sequential worker/batch model for PHP; actual threads, cancellation and lock metrics native-only.

| Registry source name | Proposed executable PHP form | Emitted PHP++ | Registered native target | Portability status |
| --- | --- | --- | --- | --- |
| `task_cancel` | `task_cancel(args)` | `task_cancel(args)` | `tasks::cancel` | proposed; adapter/map not implemented |
| `task_done` | `task_done(args)` | `task_done(args)` | `tasks::done` | proposed; adapter/map not implemented |
| `task_join` | `task_join(args)` | `task_join(args)` | `tasks::join` | proposed; adapter/map not implemented |
| `task_progress` | `task_progress(args)` | `task_progress(args)` | `tasks::progress` | proposed; adapter/map not implemented |
| `task_publish_batch_count` | `task_publish_batch_count(args)` | `task_publish_batch_count(args)` | `tasks::publish_batch_count` | proposed; adapter/map not implemented |
| `task_publish_callback_us` | `task_publish_callback_us(args)` | `task_publish_callback_us(args)` | `tasks::publish_callback_us` | proposed; adapter/map not implemented |
| `task_publish_deferred_flush_count` | `task_publish_deferred_flush_count(args)` | `task_publish_deferred_flush_count(args)` | `tasks::publish_deferred_flush_count` | proposed; adapter/map not implemented |
| `task_publish_failed_try_lock_count` | `task_publish_failed_try_lock_count(args)` | `task_publish_failed_try_lock_count(args)` | `tasks::publish_failed_try_lock_count` | proposed; adapter/map not implemented |
| `task_publish_lock_hold_us` | `task_publish_lock_hold_us(args)` | `task_publish_lock_hold_us(args)` | `tasks::publish_lock_hold_us` | proposed; adapter/map not implemented |
| `task_publish_lock_wait_us` | `task_publish_lock_wait_us(args)` | `task_publish_lock_wait_us(args)` | `tasks::publish_lock_wait_us` | proposed; adapter/map not implemented |
| `task_publish_max_batch_size` | `task_publish_max_batch_size(args)` | `task_publish_max_batch_size(args)` | `tasks::publish_max_batch_size` | proposed; adapter/map not implemented |
| `task_publish_published_count` | `task_publish_published_count(args)` | `task_publish_published_count(args)` | `tasks::publish_published_count` | proposed; adapter/map not implemented |
| `task_run` | `task_run(args)` | `task_run(args)` | `tasks::run` | proposed; adapter/map not implemented |
| `task_run_publish` | `task_run_publish(args)` | `task_run_publish(args)` | `tasks::run_publish` | proposed; adapter/map not implemented |
| `task_set_publish_try_lock` | `task_set_publish_try_lock(args)` | `task_set_publish_try_lock(args)` | `tasks::configure_publish_try_lock` | proposed; adapter/map not implemented |
| `task_set_status` | `task_set_status(args)` | `task_set_status(args)` | `tasks::set_status` | proposed; adapter/map not implemented |
| `task_set_worker_pool_size` | `task_set_worker_pool_size(args)` | `task_set_worker_pool_size(args)` | `tasks::configure_default_worker_pool` | proposed; adapter/map not implemented |
| `task_start` | `task_start(args)` | `task_start(args)` | `tasks::start` | proposed; adapter/map not implemented |
| `task_status` | `task_status(args)` | `task_status(args)` | `tasks::status` | proposed; adapter/map not implemented |
## Native UI preview (13)

[Contract/reference](../../../docs/ui_webview_preview.md). Out of scope for this PHP framework; retained as target inventory only.

| Registry source name | Proposed executable PHP form | Emitted PHP++ | Registered native target | Portability status |
| --- | --- | --- | --- | --- |
| `ui_app_create` | `ui_app_create(args)` | `ui_app_create(args)` | `ui::app_create` | out of scope for PHP framework |
| `ui_app_exit` | `ui_app_exit(args)` | `ui_app_exit(args)` | `ui::app_exit` | out of scope for PHP framework |
| `ui_app_next_event` | `ui_app_next_event(args)` | `ui_app_next_event(args)` | `ui::app_next_event` | out of scope for PHP framework |
| `ui_app_poll` | `ui_app_poll(args)` | `ui_app_poll(args)` | `ui::app_poll` | out of scope for PHP framework |
| `ui_event_message` | `ui_event_message(args)` | `ui_event_message(args)` | `ui::event_message` | out of scope for PHP framework |
| `ui_event_text` | `ui_event_text(args)` | `ui_event_text(args)` | `ui::event_text` | out of scope for PHP framework |
| `ui_event_type` | `ui_event_type(args)` | `ui_event_type(args)` | `ui::event_type` | out of scope for PHP framework |
| `ui_event_url` | `ui_event_url(args)` | `ui_event_url(args)` | `ui::event_url` | out of scope for PHP framework |
| `ui_event_webview` | `ui_event_webview(args)` | `ui_event_webview(args)` | `ui::event_webview` | out of scope for PHP framework |
| `ui_event_window` | `ui_event_window(args)` | `ui_event_window(args)` | `ui::event_window` | out of scope for PHP framework |
| `ui_window_close` | `ui_window_close(args)` | `ui_window_close(args)` | `ui::window_close` | out of scope for PHP framework |
| `ui_window_create` | `ui_window_create(args)` | `ui_window_create(args)` | `ui::window_create` | out of scope for PHP framework |
| `ui_window_show` | `ui_window_show(args)` | `ui_window_show(args)` | `ui::window_show` | out of scope for PHP framework |
## WebView preview (11)

[Contract/reference](../../../docs/ui_webview_preview.md). Out of scope for this PHP framework; retained as target inventory only.

| Registry source name | Proposed executable PHP form | Emitted PHP++ | Registered native target | Portability status |
| --- | --- | --- | --- | --- |
| `webview_close` | `webview_close(args)` | `webview_close(args)` | `webview_runtime::close` | out of scope for PHP framework |
| `webview_create` | `webview_create(args)` | `webview_create(args)` | `webview_runtime::create` | out of scope for PHP framework |
| `webview_eval` | `webview_eval(args)` | `webview_eval(args)` | `webview_runtime::eval` | out of scope for PHP framework |
| `webview_load_app` | `webview_load_app(args)` | `webview_load_app(args)` | `webview_runtime::load_app` | out of scope for PHP framework |
| `webview_load_html` | `webview_load_html(args)` | `webview_load_html(args)` | `webview_runtime::load_html` | out of scope for PHP framework |
| `webview_load_url` | `webview_load_url(args)` | `webview_load_url(args)` | `webview_runtime::load_url` | out of scope for PHP framework |
| `webview_message_command` | `webview_message_command(args)` | `webview_message_command(args)` | `webview_runtime::message_command` | out of scope for PHP framework |
| `webview_message_id` | `webview_message_id(args)` | `webview_message_id(args)` | `webview_runtime::message_id` | out of scope for PHP framework |
| `webview_message_payload_json` | `webview_message_payload_json(args)` | `webview_message_payload_json(args)` | `webview_runtime::message_payload_json` | out of scope for PHP framework |
| `webview_reply_error` | `webview_reply_error(args)` | `webview_reply_error(args)` | `webview_runtime::reply_error` | out of scope for PHP framework |
| `webview_reply_ok` | `webview_reply_ok(args)` | `webview_reply_ok(args)` | `webview_runtime::reply_ok` | out of scope for PHP framework |
## Internal/emission and cross-language entries (14)

[Contract/reference](../../../generators/php/specs/php_runtime_symbols_strict.json). Do not expose as authored portability APIs; preserve entry for registry completeness.

| Registry source name | Proposed executable PHP form | Emitted PHP++ | Registered native target | Portability status |
| --- | --- | --- | --- | --- |
| `__scpp_debug_break` | `not an authored API` | `generator-owned` | `php::__scpp_debug_break_at` | excluded pending any explicit public contract |
| `__scpp_debug_call_entry` | `not an authored API` | `generator-owned` | `php::__scpp_debug_call_entry` | excluded pending any explicit public contract |
| `__scpp_debug_dump` | `not an authored API` | `generator-owned` | `php::__scpp_debug_dump_at` | excluded pending any explicit public contract |
| `__scpp_debug_exit` | `not an authored API` | `generator-owned` | `php::__scpp_debug_exit_at` | excluded pending any explicit public contract |
| `coalesce_eval` | `not an authored API` | `generator-owned` | `php::coalesce_eval` | excluded pending any explicit public contract |
| `condition_truthy` | `not an authored API` | `generator-owned` | `php::condition_truthy` | excluded pending any explicit public contract |
| `echo_eval` | `not an authored API` | `generator-owned` | `php::echo_eval` | excluded pending any explicit public contract |
| `echo_one` | `not an authored API` | `generator-owned` | `php::echo_one` | excluded pending any explicit public contract |
| `expect_array_argument` | `not an authored API` | `generator-owned` | `php::expect_array_argument` | excluded pending any explicit public contract |
| `identical` | `not an authored API` | `generator-owned` | `php::identical` | excluded pending any explicit public contract |
| `isset_eval` | `not an authored API` | `generator-owned` | `php::isset_eval` | excluded pending any explicit public contract |
| `js_plus` | `not an authored API` | `generator-owned` | `php::js_plus` | excluded pending any explicit public contract |
| `not_identical` | `not an authored API` | `generator-owned` | `php::not_identical` | excluded pending any explicit public contract |
| `ternary_eval` | `not an authored API` | `generator-owned` | `php::ternary_eval` | excluded pending any explicit public contract |

## Surfaces outside this function registry

The registry is not a complete list of language operations or runtime member APIs.

| Surface | Inventory / PHP direction | Authority and limitation |
| --- | --- | --- |
| Async helpers | `async_wait`, `async_sleep_ms`; proposed explicit scpp calls and local async annotation | [Async](../../../specs/async_await.md); special lowering, not task threads |
| Core runtime members | nullable: has_value/value/require_value/reset; result: has_value/has_error/value/error/require_value/require_error; false/bool wrappers add is_false/is_true/reset | [Runtime catalog](../../../runtime/specs/catalog.md); PHP wrapper objects needed for methods; raw scalar approximation insufficient |
| Error methods | get_message/get_line/get_file | [Runtime](../../../runtime/specs/spec.md); proposed PHP Error payload object |
| Ownership observers | shared get/reset, weak lock, value/unique storage operations where source-supported | [Runtime](../../../runtime/specs/spec.md); native member existence does not establish PHP++ source support |
| Container members | value reads, writes, append, find/at/remove and iteration according to container contract | [Arrays](../../../specs/array_semantics.md), [hash](../../../runtime/specs/hash_t.md); prefer explicit local helper when PHP representation differs |
| Runtime-backed types | source_buffer, byte_span, source_line_index, source_location, token_buffer, text_builder, string_parts_builder | [Compiler support](../../../specs/compiler_support_runtime_contract.md); methods/ownership are not inferred by converter |
| Module handles | resource_handle, cURL handles/responses, task_batch/context/progress/error, UI/window/event/WebView handles | Respective family contracts above; represent as PHP adapter objects or native-only test boundaries |
| Constants | literal/magic constants; string pad flags; cURL options; debug flags; module-specific constants | [Generator rules](../../../generators/php/specs/rules.md) and family specs; no single exhaustive strict constant registry exists here. Exact allowlists must be frozen per adapter, not all host PHP constants |
| Exceptions | Throwable/Exception and source-defined throwable classes | [Exception contract](../../../generators/php/specs/exceptions.md); PHP runtime objects approximate target hierarchy |
| Database/mysqli | Existing PHP adapter/library docs, not entries in this strict function registry | [mysqli](../../../specs/builtins/db/mysqli.md); strict method/profile availability requires separate verification before adding a portability adapter |
| Native/compiler internals | bitset, work queue, binary codecs and native memory accounting helpers | [Compiler support](../../../specs/compiler_support_runtime_contract.md); not promoted to portable source APIs |

This is exhaustive for the pinned global registry, with explicit inventories of
other surface families. It is not an exhaustive enumeration of C++ overloads,
platform constants or every header member; those are intentionally not equated
with strict source features.

## Framework library ownership

- Ordinary PHP candidates: basic type predicates and reviewed standard operations.
- `scpp\compat` adapters: PHP-like names whose target behavior needs a wrapper,
  especially JSON result contracts and count/probe semantics. String, process,
  cURL and memory families need per-function review, not automatic blanket wrapping.
- `scpp` libraries: wrapper/error carriers, containers/records where needed, source
  buffers/spans/locations, text builders, token buffers, filesystem/IO family APIs,
  task/async abstractions and other target-specific facilities absent from PHP.
- UI/WebView is outside this PHP framework effort. Native layout remains a
  target-only test boundary.

These are framework ownership directions; exact subnamespaces and class/function
APIs are selected with their first implementation slice. The framework is broader
than the converter and its function map.
