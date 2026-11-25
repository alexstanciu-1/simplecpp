# Stage 1 helper and library inventory

Doc Status: planning

## Current family status

Reconciled against `codex/helpers-and-libs` through `cfd9f27c9` on 2026-11-25. All implementation groups selected through mysqli, UI and Webview have source-to-native evidence for their agreed v0.2 contracts. The wider donor inventory still contains separate intake decisions. Compile-time, LLVM and execution tuning belong to Stage 2.

| Operational family | Proven v0.2 scope |
| --- | --- |
| Filesystem | 25 general operations and 18 PHP exposures, including byte-preserving reads/writes, staging, checked Linux snapshots and all four file-lock operations. [Contract](../../filesystem_module.md) |
| CLI arguments and environment | General typed argument count/list and environment lookup, with PHP count, packed mixed list aliases and getenv. [Contract](../../runtime_api_summary.md#executable-arguments-and-environment) |
| Datetime | All 11 general clock, sleep, UTC and local-time operations, including exact unsigned clock transport. [Audit and proof](datetime_audit.md#stage-1-outcome) |
| Byte strings, Unicode strings, string builders | Byte indexing, slicing, comparison and typed-hash join. Existing strlen, substr and strpos keep byte units. UTF-8 validation, three explicit code-point helpers and two owning ten-operation builders. [Family proof](string_families_audit.md#stage-1-outcome) |
| Stable string hashing | Both general helpers publish the same FNV-1a 64-bit fingerprint, as lowercase hex or an unsigned numeric value. [Contract](../../runtime_api_summary.md#stable-string-fingerprints) · [Donor vectors](stable_hash_audit.md) |
| Vector construction and capacity | Fill, capacity, reserve, resize, compact and both clear spellings. [Family audit](vector_capacity_audit.md) |
| Typed vector/hash operations and map/filter | Construction, indexing, mutation, membership, removal, clear and traversal. The six general transform names cover generic, sequence and keyed cases for their admitted receivers. [Operations](typed_container_operations_audit.md) · [Transforms](collection_map_filter_audit.md) |
| Exact-mixed type predicates | PHP and general is_bool, is_int and is_float observe the mixed tag. [Contract](../../boxed_slice.md#exact-mixed-type-predicates) |
| Boxed collections | Table-valued mixed and dynamic support indexing, probes, mutation, traversal and same-family generic map/filter. Keys and order survive transforms, with mixed copy-on-write and dynamic shared identity. [Contract](../../collection_traversal.md) |
| JSON codec, document/nodes, pull reader | Checked encode/decode for the published types, immutable documents with retained nodes and diagnostics, and a checked pull reader with a shared cursor. [Contract](../../json_module.md) |
| Regex | General PCRE2 operations and ten PHP preg names, including captures, output parameters, split, filter and callback replacement. [Contract](../../regex_module.md) |
| Stream I/O, Process, curl | Byte streams with shared cursor/close state, managed Linux children with captured output, checked curl requests and PHP adapters. Each module and a combined caller reached a native executable. [Group verification](stream_process_curl_audit.md#stage-1-verification) |
| FastCGI host | Optional synchronous Unix-socket backend tested behind Apache2. The application owns its handler and response. The PHP bridge and process supervision remain deferred. [Contract](../../fastcgi_module.md) |
| mysqli | General database calls and PHP connection, statement and result receivers. Parameter binding reads the live slot at execute time and requires compiler lifetime proof. [Contract](../../database_desktop_modules.md) |
| UI, Webview | Standalone GTK window/events and optional WebKitGTK views. Native commands are available to app-owned pages. Remote HTTP(S) pages display in a separate context. Both generated callers ran on dev002. [Group verification](async_mysqli_ui_webview_audit.md#implementation-evidence) |

### Deferred or owned elsewhere

| Area | Disposition |
| --- | --- |
| Fixed arrays | Keep direct native arrays for stack locals and inline fields. Broader element/extent coverage, traversal and sequence map/filter remain explicit debt. [Array audit](typed_container_operations_audit.md) |
| Grapheme operations | Code-point helpers shipped. Grapheme count and slice await a segmentation contract and implementation. [Unicode audit](string_families_audit.md) |
| Source buffers and spans | Public buffer/span ownership, line indexing, span hashing and builder span append await a source caller. The current compiler owns its source snapshots and diagnostic offset mapping. [Donor surface index](donor_surfaces.tsv) |
| Tokenizer | Excluded from native helper intake by decision. Its implementation and benchmarks belong to the Simple C++ tokenizer. [Benchmark](../benchmarks/tokenizer/current_native.md) |
| Iterators and storage | Built-in traversal, custom iterators and yield producers exist. Sparse stable-ID storage and keyed duplicate membership await concrete consumers. [Iteration](../../../compiler/docs/foreach.md) |
| Compiler support | Bitsets, work queues, binary codecs and memory accounting await compiler or measurement consumers. The eleven donor filesystem publication operations belong with the publisher. [Filesystem audit](filesystem_audit.md) |
| Shell execution and PHP root argument variables | shell_exec remains deferred. The PHP-flavor review owns implicit $argc and $argv. [Family audit](cli_environment_shell_audit.md) |
| Async/task helpers | Cooperative suspension and joined parallel batches exist. Background ownership, cancellation, progress and intermediate publication await a concurrency use case. [Task audit](async_mysqli_ui_webview_audit.md#asynctask-helpers) |
| Flavor and platform expansion | Separate flavor reviews own PHP date/time compatibility, indexed unset, donor container aliases and additional natural JS/Python APIs. Additional database engines, widget toolkits and non-Linux desktop providers are outside the selected contracts. |

### Stage 1 closeout

The selected predicate family is operational. The [core closeout audit](core_closeout_audit.md) records the remaining flavor and tooling deferral recommendations. Broader echo coverage remains compiler-owned.

**Visibility.** C++ uses general names by default. PHP, JS and Python can add them with `"general_api": true` on the root project. Selecting a module remains a separate requirement. [Module and lookup contract](../../../compiler/docs/modules.md)

## Historical intake evidence

The tables below retain the pre-implementation snapshot at `e8b03d67f` and pilot evidence from `7936b9bec`. Their spelling counts are useful for locating donor contracts. Current family coverage is recorded above.

## Reading the inventory

[Operation crosswalk](intake.tsv) has 422 distinct spellings. Search a name to see both v0.1 target symbols and shallow signatures, every matching my-try callable ID, exact v0.2 compiler lookup keys, and any matching JSON/datetime native operation IDs. Receiver and flavor are part of the key. For example, a match on `remove` for a hash says nothing about the compiler Storage method of the same name.

| Evidence level | Spellings | Interpretation for intake |
| --- | ---: | --- |
| Registry spelling | 78 | Found at least one exact spelling in the generated v0.2 callable registry. Inspect the listed flavor, receiver, arity, return, and module before crediting a donor contract. |
| Compiler path | 16 | Located an intrinsic or a type-owned method outside that registry. For example, `make` here covers the Exception factory; the donor also uses that name on several unrelated types. |
| Native target | 14 | Donor symbols match generated datetime or JSON operations. No free-call key for these donor spellings in the inspected registry. |
| Pilot only | 3 | `string_byte_at`, `string_byte_slice_equals`, `file_get_contents`. Source execution verified on the separate pilot. |
| Review | 311 | Needs a contract or mapping decision. A differently named equivalent, operator, historical internal helper, or unsupported feature can all land here. |

The 364 names in the original [donor scan](symbols.tsv) are all represented. The release shallow stubs add one name outside both release target maps and the my-try callable list, `to_hash`. Another 57 spellings come from v0.2 lookup keys and compiler paths. The counts above are spelling counts; they do not measure implementation completion.

**Donor coverage.** The two release maps at `d2f63e37e` contain 187 legacy and 257 strict entries. The my-try input at `624ab8a2d` has 752 function signatures across 15 catalogs. Its complete parameter modes, defaults, member owners, native bindings and qualified aliases are available by callable ID in the [catalog evidence](../../../v0.1/specs/planning/runtime_surface_audit/README.md). Compared the function records in those tables with all 15 pinned catalogs. The [companion entity table](donor_entities.tsv) includes 33 my-try types, 43 constants, 25 properties, and 74 release shallow class/member declarations across the two profiles. Shallow signatures locate surfaces and sometimes erase contract details.

**Native-only donor leads.** v0.1 also ships a binary reader/writer used for compiler artifacts and a MySQL provider with PHP mysqli bindings. Neither has a my-try module catalog. Keep binary's little-endian primitives under compiler infrastructure review. The mysqli shallow stubs cover only part of the specified API. The later family audit also inspected connection, result and statement methods and properties. Native support, class methods and backend internals are not enumerated as global helper spellings. [mysqli member contract](../../../v0.1/specs/builtins/db/mysqli.md) · [Release provider directory inventory](README.md#inventory-and-scope)

## Source surfaces outside the free-function lists

The [family and receiver index](donor_surfaces.tsv) records 449 pieces of evidence. Each row identifies the donor revision, registration or contract, receiver, operation and original file. It includes the catalog's 55 static/instance method signatures and 25 properties for comparison with compiler-handled and release-only forms. These counts overlap the earlier tables. For example, `async_sleep_ms` is one source name with three pieces of evidence, and was absent from the 422-spelling table.

| Family | What the pinned donors expose |
| --- | --- |
| Arrays, vectors, fixed arrays and hashes | In addition to map/filter and vector helpers below, v0.1 defines indexed reads/writes, nested writes, append, probes, keyed removal and iteration. Its strict guide demonstrates `unset($items[1])` on a typed vector. My-try publishes `vector->append` and `hash::make` as source methods. Its vector descriptor enables read/write/append/probes/iteration and omits removal. Its hash descriptor enables removal and restricts append to integer keys. The parser accepts a fixed extent `T[N]`; neither the vector descriptor nor a shared helper spelling establishes fixed-array overload coverage. |
| Mixed and dynamic | My-try installs indexing and iteration for each boxed type separately from module registration, including writes, append, probes and keyed erase. Whole-storage `clean($x)` and `unset($x)` have their own admission rules. v0.1 has special lowering for mixed property reads, null-safe property access and `new stdClass()`. Release mixed-array reads, writes, null bootstrap and missing-key behavior are specified by operation in `specs/array_semantics.md`. |
| Iterators and storage | My-try recognizes five public zero-argument methods supplied by a source Iterator implementation: `current`, `key`, `valid`, `next`, `rewind`. The registered Storage_Cursor methods have their own receiver. Storage and Keyed_Storage also declare indexing and iteration; Key_Storage_List exposes named lookup and item enumeration. |
| Results, errors and exceptions | Both donors handle `take` specially. My-try parses call and assignment forms. v0.1 explicitly lowers zero-argument `result<T>->error()` and declares `error->get_message/get_line/get_file`. Its runtime documents native members for all four carriers. My-try registers factories and getters separately for Exception and error, and installs `error::$no_error` as a constant. |
| Handles, conversions and lifecycle | My-try parses `weak`, `lock`, `move`, `object_cast`, `instanceof`, type casts, typed literals and `new`. Constructors and destructors participate in object lifecycle. v0.1 documents `weakref`, `weakref_get` and `debug_use_count`. Receiver lifetime, nullable results and accepted input types belong to the family audit. |
| Enum and layout queries | Both compilers recognize `enum_name`, `enum_value`, `enum_from_value`, `layout_sizeof`, `layout_alignof`, `layout_offsetof` and `layout_field_sizeof`. My-try admits type/field operands during semantic preparation. These operations need compiler type information. |
| Async and tasks | Both recognize `async_sleep_ms` and `async_wait`. My-try parses `await`; v0.1 checks sleep in an async context. Task batch helpers, seven progress methods and task error fields are separately inventoried. |
| Filesystem, streams, JSON and datetime | v0.1 JSS resolves 35 dotted names: 18 filesystem, ten IO, two JSON and five datetime. The index gives the target and declared result of each. For example `fs.get` returns `result<string>`. My-try's JSON pull-reader methods and diagnostic fields are indexed by receiver. |
| CLI, output, debug and constants | v0.1 has executable-root `$argc` and `$argv`, JSS `print` and `console_log`, and dedicated lowering for `dbg`, `dbg_if` and generated debug hooks. My-try installs `PHP_INT_MAX`, `error::$no_error`, `false_sentinel`, `true_sentinel` and `nullopt`. Release PHP provider aliases and JSS curl constants have separate evidence rows. |
| Database, curl, regex and other module records | The v0.1 mysqli contract supplies a connection constructor, 13 receiver methods and 11 properties across connection, result and statement. The strict curl response exposes nine fields, and regex documents two supported split flags. The [entity table](donor_entities.tsv) also covers release process output, task progress/error and UI window/event declarations. The [module catalog evidence](../../../v0.1/specs/planning/runtime_surface_audit/README.md) carries the remaining ordinary library calls by family. |
| Strings, builders, hashing and source utilities | The release analyzer supplies signatures independently of name remapping. The index records its 54 named entries across these and other families. For instance, `source_text_vector_move_append` appears there; the my-try source-module review identifies it as internal. Analyzer knowledge alone does not publish an application helper. |

**Native boundary.** The [native family index](donor_native_methods.tsv) enumerates all 176 named method records and 25 constructor records in my-try's 22 public API family templates, including vector/hash cursors. It also indexes 41 free-function records, 11 function families and 46 function sets before specialization. For example, `vector::capacity` exists in this C++ publication, while the historical source call is `vector_capacity($v)`. The concrete string header additionally declares byte access, byte capacity, UTF-8 validation, code-point length/substrings, comparison, append and clear. Scalar conversions and operators have their own headers and operator definitions. For v0.1, the compiler-support contract also identifies bitsets, work queues, binary reader/writer and memory accounting as native infrastructure.

**Coverage boundary.** In my-try, the language installer is finite (nine named functions), and static/instance runtime method lookup queries the module's registered methods. v0.1 has an open fallback: after its special cases the generator emits an ordinary C++ function or receiver method call. Project declarations and linked C++ headers can therefore extend what compiles. The inventory covers the shipped contracts and identifiable compiler paths; there is no closed list of every call that a v0.1 application with external headers could make. An occurrence in a fixture can also resolve to the application's own method. Full-family behavioral proofs belong to implementation.

To reproduce both supplemental indexes from the pinned Git objects, run `python3 specs/planning/helpers_and_libraries/donor_surfaces.py --output /tmp/scpp-donor-index` from the repository root. Inspect a historical source with `git show <revision>:<donor-path>`. The my-try method dispatch is in `compiler/my-try/04_analyze/prepare/semantics/methods.php`; the concrete native headers are in `runtime/public/scpp/`. The release PHP call and method fallbacks are in `generators/php/src/Generator/Generator.php`.

## Historical verification and reproduction

Generated fresh runtime and compiler outputs into scratch directories for both core and the optional-library profile. The stock standalone numeric configuration failed during provider enumeration. The base publication already requests `int32` and the numeric overlay appends it again. The generator independently rejects the composed request with `Duplicate published type`. Numeric-only expansion therefore remains unverified in this snapshot. No requests were edited to manufacture a successful configuration.

Run from the repository root after building the Clang native layout facts.

```sh
audit=$(mktemp -d)
cmake --preset default -B "$audit/build" -DSCPP_ENABLE_DATETIME=ON -DSCPP_ENABLE_JSON=ON -DSCPP_ENABLE_FILESYSTEM=ON -DSCPP_ENABLE_REGEX=ON
for kind in runtime compiler; do
  php tools/api_generator/generate.php "emit-$kind" runtime/metadata/runtime.json \
    "$audit/build/runtime-publication.json" build/clang/native-layout/facts.json \
    build/clang/native-layout/context.json "$audit/$kind"
done
python3 specs/planning/helpers_and_libraries/inventory.py \
  "$audit/compiler/types/registry.php" "$audit/runtime/prepared.json" --output "$audit/tables"
```

The historical signature inventory and actual publication answer which names and dependencies exist. Stage 1 still requires a source caller and a distinguishing behavioral check before marking an operation operational. Use the existing pilot and the two-argument PHP file write as evidence in their respective full-family audits. [Integration process](integration_process.md) · [Write contract](../../../v0.1/specs/builtins/filesystem/file_put_contents.md)
