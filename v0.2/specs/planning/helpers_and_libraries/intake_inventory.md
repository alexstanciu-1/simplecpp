# Stage 1 helper and library inventory

Doc Status: planning

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

**Donor coverage.** The two release maps at `d2f63e37e` contain 187 legacy and 257 strict entries. The my-try input at `624ab8a2d` has 752 function signatures across 15 catalogs. Its complete parameter modes, defaults, member owners, native bindings and qualified aliases are available by callable ID in the [catalog evidence](../../../../specs/planning/runtime_surface_audit/README.md). Compared the function records in those tables with all 15 pinned catalogs. The [companion entity table](donor_entities.tsv) includes 33 my-try types, 43 constants, 25 properties, and 74 release shallow class/member declarations across the two profiles. Shallow signatures locate surfaces and sometimes erase contract details.

**Native-only donor leads.** v0.1 also ships a binary reader/writer used for compiler artifacts and a MySQL provider with PHP mysqli bindings. Neither has a my-try module catalog. Keep binary's little-endian primitives under compiler infrastructure review. For mysqli the shallow stubs cover only part of the specified API; review the connection, result and statement methods/properties with the MariaDB dependency before scheduling that library. Native support, class methods and backend internals are not enumerated as global helper spellings. [mysqli member contract](../../../../specs/builtins/db/mysqli.md) · [Release provider directory inventory](README.md#inventory-and-scope)

## Family decisions for batch review

| Family | Evidence on v0.2 and proposed treatment |
| --- | --- |
| Strings | Of 54 distinct my-try string names, 22 have matching registry spellings and two are pilot-only. v0.2 also has additions such as PHP `str_repeat` and `stripos`. Compare byte, UTF-8 and grapheme operations separately. Builder types and their methods form a separate lifetime/API decision. |
| Filesystem | Five general free bindings exist: `fs_get`, `fs_put`, `fs_rename`, `fs_remove`, `fs_stage`. The donor filesystem catalog has 35 distinct names. Four match the registry; `fs_stage` comes from the v0.2 side. Review the get/put pair and the PHP adaptations first. Check failed read versus empty bytes and failed write versus zero bytes written. |
| Regex | The optional PCRE2 module publishes neutral `regex_test`, `regex_find_span`, and PHP-only `preg_match`. The historical match/capture/replace APIs include different outputs and callback contracts. Compare each signature independently. |
| Datetime | All 11 donor `dt_*` names have native symbol matches, across time, formatting, parsing, and sleep. Source binding, admitted clocks/types and failure policy are the intake questions. [Module contract](../../datetime_module.md) |
| JSON | Three donor spellings map to native symbols covering all 12 selected operations, including concrete encode overloads. Document nodes and pull readers are separate families with their own types and lifetime questions. [Module contract](../../json_module.md) |
| Core, vector, hash and boxed | Separate language constructs such as `isset` and cardinality from library calls. Generated lookup keys and type-owned methods both matter. Preserve distinct receiver types; review natural string/vector/hash spellings in individual flavor passes. |
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
| Historical translated framework sample | The PHS filesystem shim also calls `fs_get`. Classify its owning toolchain before editing any manifest. [Shim](../../../samples/my-try/phpp/scpp_framework/filesystem.phs) |

If regex general names subsequently receive the same gate, review the [regex fixture](../../../compiler/tests/regex_native.py) independently. Host PHP calls such as the bootstrap compiler's own `file_get_contents` run in PHP itself and do not establish a Simple C++ source dependency.

## Verification and reproduction

Generated fresh runtime and compiler outputs into scratch directories for both core and the optional-library profile. The stock standalone numeric configuration failed during provider enumeration. The base publication already requests `int32` and the numeric overlay appends it again. The generator independently rejects the composed request with `Duplicate published type`. Numeric-only expansion therefore remains unverified in this snapshot. No requests were edited to manufacture a successful configuration.

From `v0.2/`, with the normal Clang native layout facts already available:

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

The historical signature inventory and actual publication answer which names and dependencies exist. Stage 1 still requires a source caller and a distinguishing behavioral check before marking an operation operational. Start batch review with the three pilot operations and the bounded two-argument PHP file write. [Integration process](integration_process.md) · [Write contract](../../../../specs/builtins/filesystem/file_put_contents.md)
