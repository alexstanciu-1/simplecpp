# Stage 1 helper and library inventory

Doc Status: planning

The filesystem family has completed Stage 1 with 21 general operations and 18 PHP exposures. The counts below preserve the pre-implementation inventory. [Filesystem contract](../../filesystem_module.md) · [Family audit](filesystem_audit.md)

Snapshot of `codex/helpers-and-libs` at `e8b03d67f`, 2026-11-25. The separate pilot at `7936b9bec` supplies experimental evidence for three operations. This inventory makes no language or publication changes.

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

**Native-only donor leads.** v0.1 also ships a binary reader/writer used for compiler artifacts and a MySQL provider with PHP mysqli bindings. Neither has a my-try module catalog. Keep binary's little-endian primitives under compiler infrastructure review. For mysqli the shallow stubs cover only part of the specified API; review the connection, result and statement methods/properties with the MariaDB dependency before scheduling that library. Native support, class methods and backend internals are not enumerated as global helper spellings. [mysqli member contract](../../../v0.1/specs/builtins/db/mysqli.md) · [Release provider directory inventory](README.md#inventory-and-scope)

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

## Family audit and implementation

Use a whole family as the implementation unit. First reconcile its donor specs, catalogs, compiler/type-owned forms, native providers and tests. A narrow probe can settle a contract question. Record the v0.2 disposition of every operation, overload and receiver, including deferred flavor surfaces. Then set up an implementation goal covering the full agreed family and its source-level proofs. Individual performance tuning follows in Stage 2.

| Family | Evidence on v0.2 and proposed treatment |
| --- | --- |
| Strings | Of 54 distinct my-try string names, 22 have matching registry spellings and two are pilot-only. v0.2 also has additions such as PHP `str_repeat` and `stripos`. Compare byte, UTF-8 and grapheme operations separately. Builder types and their methods form a separate lifetime/API decision. |
| Filesystem | Five general free bindings exist: `fs_get`, `fs_put`, `fs_rename`, `fs_remove`, `fs_stage`. The donor filesystem catalog has 35 distinct names. Four match the registry; `fs_stage` comes from the v0.2 side. Audit the full filesystem family. Use the get/put pair to probe flavor adaptation. Check failed read versus empty bytes and failed write versus zero bytes written. |
| Regex | The optional PCRE2 module publishes neutral `regex_test`, `regex_find_span`, and PHP-only `preg_match`. The historical match/capture/replace APIs include different outputs and callback contracts. Compare each signature independently. |
| Datetime | All 11 donor `dt_*` names have native symbol matches, across time, formatting, parsing, and sleep. Source binding, admitted clocks/types and failure policy are the intake questions. [Module contract](../../datetime_module.md) |
| JSON | Three donor spellings map to native symbols covering all 12 selected operations, including concrete encode overloads. Document nodes and pull readers are separate families with their own types and lifetime questions. [Module contract](../../json_module.md) |
| Typed collection map/filter | Both donors expose `collection_map`, `collection_filter`, `sequence_map`, `sequence_filter`, `keyed_map`, and `keyed_filter`. These are six strict release bindings and twelve my-try callable IDs in the core catalog. Audit receiver coverage per donor. For example, the release contract explicitly admits fixed arrays for the sequence adapters. Check dense sequence output, preservation of hash keys and iteration order, and the callback contract. [Release collection contract](https://github.com/alexstanciu-1/simplecpp/blob/d2f63e37efe949355c11b137cc4c3520fbfc6368/specs/builtins/collections.md) |
| Vector construction and capacity | Stage 1 implemented. [Family audit and resize probe](vector_capacity_audit.md) · [Current contract](../../vector_slice.md#vector-construction-and-capacity). Seven names across both release profiles and my-try: `vector_filled`, `vector_capacity`, `vector_reserve`, `vector_resize`, `vector_compact`, `vector_clear`, and `vector_clear_keep_capacity`. The eight my-try callable IDs include two arities for compact. Probe mutation, retained capacity, and a resize fill value that aliases an existing element. |
| Container operations and boxed values | Separate language constructs such as `isset` and cardinality from library calls. Generated lookup keys and type-owned methods both matter. Preserve distinct receiver types; review natural string/vector/hash spellings in individual flavor passes. |
| Compiler | Storage methods can share names with current container methods. Keep record identity, removal and alias behavior in the comparison. Classify compiler-host utilities independently from source-callable facilities. |
| Source and tokenizer | Source buffers, spans, line indexes and token buffers bring owner types. Evaluate groups against an actual compiler consumer and the existing tokenizer decisions. |
| IO and process | Review stream and process handles, cleanup, EOF and operational errors. Filesystem path IO covers a different contract. |
| curl | Review the handle, response, option constants and external dependency together before admitting wrappers. |
| Tasks | 28 distinct callable names expand to 406 signatures in the pinned catalog. Compare the modern async/task and multithreading contracts before choosing any donor overload family. |
| UI and webview | Each has 12 distinct callable names. Review platform dependencies, event/handle ownership and a runnable consumer at library intake. |
| Release-only PHP and internal forms | Profile aliases, debug hooks, generated-code support, and operators can appear in the same maps as application APIs. Record an explicit disposition when their family is reviewed. |

## Current visibility and migration

The successfully generated core and optional-library registries share the same lookup rules. With filesystem, regex, JSON and datetime selected, the callable registry has 158 keys, including ten neutral free-call keys. These are the five filesystem functions above, `regex_test`, `regex_find_span`, `balanced_range`, `string_byte_from_int`, and `strlen`. PHP also has three generated padding constants. This is a registration inventory. Compiler intrinsics, type-owned operations and module checks have separate paths. In particular, keep PHP's natural `strlen` exposure when reviewing the neutral key. A blanket guard on every neutral key would change that behavior.

**Agreed migration.** When the visibility guard lands, existing PHP and JS source callers of general filesystem functions get the project-level general-API opt-in in their manifests. Keep their structured result contracts. C++ uses the general surface by default. Module selection still controls dependencies. The inventory does not choose a manifest key. Apply visibility consistently to ordinary lookup, rooted calls and imports. Include a mixed-flavor project and an incremental manifest toggle in the guard's proof.

| Current filesystem consumer | Where to apply or verify the opt-in |
| --- | --- |
| Access-log, stock-ledger, route-table | Three base single-file pilot manifests and six checked-in 50/100 KB variants. The scaling generator copies each base manifest; benchmark staging must preserve it. [Base projects](../../../samples/single-file-pilots) · [Generator](../../../samples/single-file-pilots/scaled/generate.py) |
| Translated compiler path and publisher cleanup | PHP proof sources contain general reads/writes. Their Python harnesses construct the application manifests. Update the manifest writers. [Translation](../../../compiler/tests/translated_compiler_path.py) · [Publisher](../../../compiler/tests/translated_publisher_cleanup.py) |
| Filesystem native fixture | The harness generates PHP, JS and C++ apps, toggles module selection and imports `fs_get` under an alias in PHP. Exercise module and opt-in independently so each diagnostic proves the intended rule. [Fixture](../../../compiler/tests/filesystem_native.py) |
| Historical translated framework sample | The PHS filesystem shim also calls `fs_get`. Classify its owning toolchain before editing any manifest. [Shim](../../../v0.1/reference-samples/my-try/phpp/scpp_framework/filesystem.phs) |

If regex general names subsequently receive the same gate, review the [regex fixture](../../../compiler/tests/regex_native.py) independently. Host PHP calls such as the bootstrap compiler's own `file_get_contents` run in PHP itself and do not establish a Simple C++ source dependency.

## Verification and reproduction

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
