# Runtime template source discovery index
Doc Status: planning

Date: 2026-10-19. Snapshot: `2f216d42`.
Companion to the [family-by-capability matrix](runtime_template_capability_matrix_2026_10_19.md).
This is a dated source-search index, not executable metadata, a registration list or
a second registry. No compiler reads it. Refresh it when reviewing a changed runtime.

Discovery scans tracked C++ `.h`, `.hpp`, `.cpp`, `.cc`, `.cxx` and `.inc` files under
`runtime/include` for the lexical pattern `\btemplate\s*<` (including line breaks).
Counts include forward declarations, explicit specializations and possible comment
matches; they are not a parsed declaration inventory or measured instantiation counts.
Macros, transitively included standard-library templates and generated compiler
adapter code need separate exposure review. Absence from this list does not prove a
header is template-free after preprocessing. Semantic contracts come from the existing
registry, runtime metadata and owning specs, never from this lexical search.

Scanned **195** tracked source files; **86** contain matches. All matching
files are listed below, including private implementations and optional/legacy providers.
Family grouping, registration status and proposed disposition are in the matrix.

| Runtime source | Lexical occurrences | First matching line |
|---|---:|---:|
| [core/resource.hpp](../../runtime/include/core/resource.hpp) | 1 | 83 |
| [lang/php/operators/empty/empty.hpp](../../runtime/include/lang/php/operators/empty/empty.hpp) | 1 | 9 |
| [lang/php/operators/isset/isset.hpp](../../runtime/include/lang/php/operators/isset/isset.hpp) | 2 | 10 |
| [lang/php/operators/type_predicates/type_predicates.hpp](../../runtime/include/lang/php/operators/type_predicates/type_predicates.hpp) | 4 | 8 |
| [lang/php/php_compiler.hpp](../../runtime/include/lang/php/php_compiler.hpp) | 13 | 7 |
| [lang/php/php_exceptions.hpp](../../runtime/include/lang/php/php_exceptions.hpp) | 2 | 60 |
| [lang/php/support/php_common.hpp](../../runtime/include/lang/php/support/php_common.hpp) | 2 | 80 |
| [lang/php/support/php_mysqli.hpp](../../runtime/include/lang/php/support/php_mysqli.hpp) | 4 | 86 |
| [lang/php/support/php_string.hpp](../../runtime/include/lang/php/support/php_string.hpp) | 13 | 215 |
| [lang/php/support/php_take.hpp](../../runtime/include/lang/php/support/php_take.hpp) | 4 | 14 |
| [lang/php/support/php_value.hpp](../../runtime/include/lang/php/support/php_value.hpp) | 8 | 44 |
| [modules/compiler/detail/storage_common.hpp](../../runtime/include/modules/compiler/detail/storage_common.hpp) | 5 | 15 |
| [modules/compiler/key_storage_list.hpp](../../runtime/include/modules/compiler/key_storage_list.hpp) | 1 | 15 |
| [modules/compiler/keyed_storage.hpp](../../runtime/include/modules/compiler/keyed_storage.hpp) | 3 | 11 |
| [modules/compiler/storage.hpp](../../runtime/include/modules/compiler/storage.hpp) | 3 | 10 |
| [modules/compiler/storage_cursor.hpp](../../runtime/include/modules/compiler/storage_cursor.hpp) | 3 | 10 |
| [modules/curl/curl.cpp](../../runtime/include/modules/curl/curl.cpp) | 1 | 57 |
| [modules/filesystem/detail/snapshot_reader.hpp](../../runtime/include/modules/filesystem/detail/snapshot_reader.hpp) | 1 | 19 |
| [modules/json/json.cpp](../../runtime/include/modules/json/json.cpp) | 6 | 25 |
| [modules/json/json.hpp](../../runtime/include/modules/json/json.hpp) | 2 | 44 |
| [modules/json/pull_reader.hpp](../../runtime/include/modules/json/pull_reader.hpp) | 2 | 34 |
| [modules/strings/strings.hpp](../../runtime/include/modules/strings/strings.hpp) | 1 | 683 |
| [modules/tasks/publication_context.hpp](../../runtime/include/modules/tasks/publication_context.hpp) | 1 | 23 |
| [modules/tasks/tasks.hpp](../../runtime/include/modules/tasks/tasks.hpp) | 135 | 59 |
| [modules/tokenizer/tokenizer.hpp](../../runtime/include/modules/tokenizer/tokenizer.hpp) | 2 | 51 |
| [modules/webview/webview.cpp](../../runtime/include/modules/webview/webview.cpp) | 2 | 582 |
| [operators/arithmetic/boxed_numeric.hpp](../../runtime/include/operators/arithmetic/boxed_numeric.hpp) | 3 | 11 |
| [operators/coalesce/coalesce.hpp](../../runtime/include/operators/coalesce/coalesce.hpp) | 27 | 7 |
| [operators/conditional/condition_truthiness.hpp](../../runtime/include/operators/conditional/condition_truthiness.hpp) | 5 | 12 |
| [operators/conditional/conditional_selection.hpp](../../runtime/include/operators/conditional/conditional_selection.hpp) | 26 | 7 |
| [operators/count/count.hpp](../../runtime/include/operators/count/count.hpp) | 3 | 33 |
| [operators/debug_use_count/debug_use_count.hpp](../../runtime/include/operators/debug_use_count/debug_use_count.hpp) | 2 | 9 |
| [operators/empty/empty.hpp](../../runtime/include/operators/empty/empty.hpp) | 12 | 15 |
| [operators/identity/boxed_scalar_equality.hpp](../../runtime/include/operators/identity/boxed_scalar_equality.hpp) | 2 | 12 |
| [operators/identity/strict_identity.hpp](../../runtime/include/operators/identity/strict_identity.hpp) | 54 | 11 |
| [operators/isset/isset.hpp](../../runtime/include/operators/isset/isset.hpp) | 20 | 14 |
| [operators/probe/probe.hpp](../../runtime/include/operators/probe/probe.hpp) | 14 | 29 |
| [operators/unset/unset.hpp](../../runtime/include/operators/unset/unset.hpp) | 11 | 16 |
| [operators/weakref/weakref.hpp](../../runtime/include/operators/weakref/weakref.hpp) | 2 | 9 |
| [scpp/async_core.hpp](../../runtime/include/scpp/async_core.hpp) | 11 | 21 |
| [scpp/cast.hpp](../../runtime/include/scpp/cast.hpp) | 73 | 40 |
| [scpp/data_initializer.hpp](../../runtime/include/scpp/data_initializer.hpp) | 12 | 16 |
| [scpp/detail.hpp](../../runtime/include/scpp/detail.hpp) | 57 | 33 |
| [scpp/dynamic_t.hpp](../../runtime/include/scpp/dynamic_t.hpp) | 1 | 9 |
| [scpp/fixed_array_t.hpp](../../runtime/include/scpp/fixed_array_t.hpp) | 5 | 17 |
| [scpp/foreach.hpp](../../runtime/include/scpp/foreach.hpp) | 27 | 15 |
| [scpp/generated/operator_detail.hpp](../../runtime/include/scpp/generated/operator_detail.hpp) | 61 | 22 |
| [scpp/generated/operators.hpp](../../runtime/include/scpp/generated/operators.hpp) | 152 | 32 |
| [scpp/index_existing.hpp](../../runtime/include/scpp/index_existing.hpp) | 8 | 10 |
| [scpp/index_read.hpp](../../runtime/include/scpp/index_read.hpp) | 2 | 13 |
| [scpp/index_write.hpp](../../runtime/include/scpp/index_write.hpp) | 4 | 12 |
| [scpp/index_write_path.hpp](../../runtime/include/scpp/index_write_path.hpp) | 8 | 9 |
| [scpp/int_t.hpp](../../runtime/include/scpp/int_t.hpp) | 4 | 14 |
| [scpp/json/from_json.hpp](../../runtime/include/scpp/json/from_json.hpp) | 33 | 20 |
| [scpp/json/writer.hpp](../../runtime/include/scpp/json/writer.hpp) | 18 | 13 |
| [scpp/keyed_path.hpp](../../runtime/include/scpp/keyed_path.hpp) | 12 | 16 |
| [scpp/memory.hpp](../../runtime/include/scpp/memory.hpp) | 5 | 17 |
| [scpp/memory_accounting.hpp](../../runtime/include/scpp/memory_accounting.hpp) | 8 | 27 |
| [scpp/nullable.hpp](../../runtime/include/scpp/nullable.hpp) | 8 | 12 |
| [scpp/object_cast.hpp](../../runtime/include/scpp/object_cast.hpp) | 6 | 9 |
| [scpp/result.hpp](../../runtime/include/scpp/result.hpp) | 1 | 7 |
| [scpp/result_core.hpp](../../runtime/include/scpp/result_core.hpp) | 12 | 24 |
| [scpp/result_or_bool.hpp](../../runtime/include/scpp/result_or_bool.hpp) | 6 | 8 |
| [scpp/result_or_false.hpp](../../runtime/include/scpp/result_or_false.hpp) | 1 | 7 |
| [scpp/row_arena_t.hpp](../../runtime/include/scpp/row_arena_t.hpp) | 1 | 24 |
| [scpp/shared_conversion.hpp](../../runtime/include/scpp/shared_conversion.hpp) | 5 | 9 |
| [scpp/shared_p.hpp](../../runtime/include/scpp/shared_p.hpp) | 9 | 12 |
| [scpp/shared_self.hpp](../../runtime/include/scpp/shared_self.hpp) | 1 | 11 |
| [scpp/support/dbg.cpp](../../runtime/include/scpp/support/dbg.cpp) | 1 | 174 |
| [scpp/support/dbg.hpp](../../runtime/include/scpp/support/dbg.hpp) | 34 | 97 |
| [scpp/support/dynamic_value.hpp](../../runtime/include/scpp/support/dynamic_value.hpp) | 1 | 12 |
| [scpp/support/hash_t.hpp](../../runtime/include/scpp/support/hash_t.hpp) | 45 | 34 |
| [scpp/support/mixed_t.cpp](../../runtime/include/scpp/support/mixed_t.cpp) | 4 | 1035 |
| [scpp/support/mixed_t.hpp](../../runtime/include/scpp/support/mixed_t.hpp) | 4 | 20 |
| [scpp/support/var_dump.cpp](../../runtime/include/scpp/support/var_dump.cpp) | 2 | 20 |
| [scpp/support/var_dump.hpp](../../runtime/include/scpp/support/var_dump.hpp) | 5 | 31 |
| [scpp/typed_iterator.hpp](../../runtime/include/scpp/typed_iterator.hpp) | 3 | 15 |
| [scpp/unique_p.hpp](../../runtime/include/scpp/unique_p.hpp) | 4 | 11 |
| [scpp/value_iteration.hpp](../../runtime/include/scpp/value_iteration.hpp) | 2 | 12 |
| [scpp/value_p.hpp](../../runtime/include/scpp/value_p.hpp) | 2 | 14 |
| [scpp/vector_t.hpp](../../runtime/include/scpp/vector_t.hpp) | 5 | 19 |
| [scpp/weak_p.hpp](../../runtime/include/scpp/weak_p.hpp) | 6 | 11 |
| [scpp/work_queue_t.hpp](../../runtime/include/scpp/work_queue_t.hpp) | 1 | 21 |
| [utfcpp/utf8/checked.h](../../runtime/include/utfcpp/utf8/checked.h) | 15 | 73 |
| [utfcpp/utf8/core.h](../../runtime/include/utfcpp/utf8/core.h) | 21 | 88 |
| [utfcpp/utf8/unchecked.h](../../runtime/include/utfcpp/utf8/unchecked.h) | 15 | 37 |
