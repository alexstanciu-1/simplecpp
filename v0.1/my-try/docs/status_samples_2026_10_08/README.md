# Status comparison samples — 2026-10-08
Doc Status: supporting

87 distinct source probes, 174 generator invocations in the final collection. Source snippets are strict PHP++ unless the row deliberately probes a deferred/rejected syntax. No generated program was compiled or executed. The artifact-layout row reuses the multi-file probe; the other three non-probe rows describe compiler/build protocols.

## Results

| Generator | Emitted text (exit 0) | Generation error |
| --- | --- | --- |
| v1 | 81 | 6 |
| v2 | 50 | 37 |

These are **generation outcomes, not test pass counts**. Exit zero does not prove usable C++. In particular, legacy xor and array-cast samples contain unsupported placeholders; trait use/adaptations are not established by the emitted class declarations; closure boxing emission is not a supported-runtime guarantee. Readonly field emission does not enforce readonly writes. Both versions emitting the mutation sample proves no iteration-safety guarantee.

## Method

- The same final source is supplied to both generators. The legacy adapter explicitly selects strict profile and writes only stdout/stderr. v2 uses the ordinary PHP-host S2S CLI, with main.phs explicitly selected as entry. No source compatibility rewrites are hidden between versions.
- Multi-file probe 44 collects legacy header/source pairs separately without scpp project include/link assembly; v2 discovers the project files. It is an artifact example, not a full legacy project-build proof.
- The shared 12-slot CommandRunner records every command, exit status and raw byte stream. CLI mode is explicit because these entrypoints use process exits. Its PASS progress messages mean evidence collection completed, including expected/unexpected generator rejections; they are not compiler success verdicts.
- Preliminary samples were refined to remove unrelated ordering/cast/lookup blockers. Saved sources, results, SHA256 hashes and final logs describe the final collection. PHP implementation fingerprints found no edits during that collection.
- The intended module configuration is recorded with samples; direct S2S bypasses project module discovery/STAN/build. A runtime API appearing in emitted text is not proof of registered native declarations or successful linking.
- v2 raw stdout is an artifact JSON envelope. The readable v2.cpp.txt concatenates its exact artifact text, with added file-separator comments. Raw envelopes remain in logs/*.stdout. No generated C++ was hand-written or repaired.
- Current v2 json_decode returns dynamic; json_decode_mixed is its mixed-family entry. Probe 36 deliberately uses the current dynamic spelling on both sides. A legacy emission is not proof of family-contract parity.
- Probe 73 shows runtime error-to-failed-Result conversion, not a claimed structured-error constructor. Probes 25 and 42 stop at explicit shared type / weak assignment before later key/alias blockers. Probe 84 does not run STAN advisory checks.

## Reproduce

From the repository root (these commands only regenerate this new evidence folder):

```sh
python3 compiler/my-try/docs/status_samples_2026_10_08/collect.py
```

Run render.py in the same folder after collection to refresh readable bundles and inline table code with references. The renderer populates the report from the saved final collection. Raw commands and diagnostics are available in [the command journal](logs/commands.json); [provenance](provenance.json) records implementation hashes and concurrent-change checks; [samples.json](samples.json) records scope per row. The PHP++ source hash for each result is in its result.json.

## Samples

| Row | Feature | Evidence |
| --- | --- | --- |
| 01 | Integer, float, boolean and string literals; inferred and explicitly typed locals | [Read](01/README.md) |
| 02 | Typed predeclarations and nullable scalar locals | [Read](02/README.md) |
| 03 | Fixed-width integer fields and integer boundary conversions | [Read](03/README.md) |
| 04 | Scalar arithmetic, comparisons, boolean logic and unary operators | [Read](04/README.md) |
| 05 | Bitwise/shift operators and compound assignment | [Read](05/README.md) |
| 06 | Prefix/postfix updates and assignment expressions | [Read](06/README.md) |
| 07 | Keyword `and`, `or`, `xor` | [Read](07/README.md) |
| 08 | String concatenation and simple variable interpolation | [Read](08/README.md) |
| 09 | String bracket indexing | [Read](09/README.md) |
| 10 | Braced `if`/`elseif`/`else`, `while`, `do`, `for`; ordinary `break`/`continue` | [Read](10/README.md) |
| 11 | `switch` and `match` | [Read](11/README.md) |
| 12 | Ternary, Elvis and null coalescing | [Read](12/README.md) |
| 13 | Typed signatures, positional calls, returns and terminating recursion | [Read](13/README.md) |
| 14 | Default arguments | [Read](14/README.md) |
| 15 | Typed trailing variadics | [Read](15/README.md) |
| 16 | Ordinary function overloading | [Read](16/README.md) |
| 17 | Scalar casts and stabilization from `mixed` into typed scalar destinations | [Read](17/README.md) |
| 18 | Nullable and Result families: success values, extraction, state tests | [Read](18/README.md) |
| 19 | `take` extraction | [Read](19/README.md) |
| 20 | No-error state, copying and error truthiness | [Read](20/README.md) |
| 21 | Separated mixed/value and dynamic/shared container families, explicit bridges | [Read](21/README.md) |
| 22 | Mixed arithmetic, comparisons, logical operators and text updates | [Read](22/README.md) |
| 23 | Typed vector/hash construction; nested mixed/container literals | [Read](23/README.md) |
| 24 | Typed public Storage collections: `Storage<T>`, `Keyed_Storage<T>`, `Key_Storage_List<T>` | [Read](24/README.md) |
| 25 | Object-identity keys in typed hashes | [Read](25/README.md) |
| 26 | Empty `new vector<T>()` / `new hash<T,K>()` constructors | [Read](26/README.md) |
| 27 | Shared PHP-like and JS-like data construction notation | [Read](27/README.md) |
| 28 | Container reads, direct/nested writes and appends | [Read](28/README.md) |
| 29 | Keyed `isset`, `empty`, `unset` | [Read](29/README.md) |
| 30 | Vector-element `unset` with index compaction | [Read](30/README.md) |
| 31 | Value and key/value `foreach` | [Read](31/README.md) |
| 32 | Typed `Storage_Cursor<T>` construction and read-view widening | [Read](32/README.md) |
| 33 | Global `Iterator` protocol and typed object foreach | [Read](33/README.md) |
| 34 | Whole-container calls, copies and references | [Read](34/README.md) |
| 35 | Legacy PHP `array` / `?array` entry guards and `(array)` casts | [Read](35/README.md) |
| 36 | Boxed JSON source calls (`json_decode` / `json_encode`) | [Read](36/README.md) |
| 37 | Explicit reference parameters and stable local aliases | [Read](37/README.md) |
| 38 | Reference returns and forwarding | [Read](38/README.md) |
| 39 | Explicit read-only reference parameters | [Read](39/README.md) |
| 40 | Shared, unique, weak and inline/value ownership | [Read](40/README.md) |
| 41 | Converter cast spelling `scpp_portability_object_cast(..., Class::class)` | [Read](41/README.md) |
| 42 | Converter `weakref_get` spelling and implicit shared-to-weak assignment | [Read](42/README.md) |
| 43 | Whole-storage reset/clean/unset | [Read](43/README.md) |
| 44 | Multiple sources and selected executable entry | [Read](44/README.md) |
| 45 | Namespaces, qualified names, imports and scalar source constants | [Read](45/README.md) |
| 46 | Unqualified global facade calls inside compiler namespace | [Read](46/README.md) |
| 47 | Nested namespace declarations | [Read](47/README.md) |
| 48 | Static prologue `require_once` textual lowering | [Read](48/README.md) |
| 49 | Retained semantic preparation, dependency invalidation and failure/retry | [Read](49/scope.md) |
| 50 | Struct/inline record storage and explicit inline class values | [Read](50/README.md) |
| 51 | Unit and integral-backed enums | [Read](51/README.md) |
| 52 | Explicit `enum_name`, `enum_value`, `enum_from_value` helpers | [Read](52/README.md) |
| 53 | Classes/structs, typed properties, construction and property defaults | [Read](53/README.md) |
| 54 | Instance methods, visibility, static methods/properties/constants | [Read](54/README.md) |
| 55 | Non-null shared-object strict identity | [Read](55/README.md) |
| 56 | Readonly instance-field declarations and write enforcement | [Read](56/README.md) |
| 57 | Destructors and automatic cleanup | [Read](57/README.md) |
| 58 | Nullable inline records and handle normalization | [Read](58/README.md) |
| 59 | Explicit `data class` / `data struct` keyed construction | [Read](59/README.md) |
| 60 | Concrete inheritance, parent calls/construction | [Read](60/README.md) |
| 61 | Abstract classes, interfaces and explicit virtual/override dispatch | [Read](61/README.md) |
| 62 | Traits, nested trait use and abstract trait requirements | [Read](62/README.md) |
| 63 | Shared base/interface views, checked shared casts and literal `instanceof` | [Read](63/README.md) |
| 64 | Shared parameter contravariance and result covariance | [Read](64/README.md) |
| 65 | Static-context called-type binding and shared factories | [Read](65/README.md) |
| 66 | Anonymous functions, concrete callable locals and invocation | [Read](66/README.md) |
| 67 | Value and reference captures | [Read](67/README.md) |
| 68 | Task runtime callbacks: synchronization and unordered worker/publication | [Read](68/README.md) |
| 69 | Arrow functions | [Read](69/README.md) |
| 70 | Callable values stored in mixed/dynamic entries | [Read](70/README.md) |
| 71 | Source `throw` and typed `try`/`catch` | [Read](71/README.md) |
| 72 | `finally` | [Read](72/README.md) |
| 73 | Full authored structured errors and conversion into failed Results | [Read](73/README.md) |
| 74 | Runtime error inspection (`get_message`) | [Read](74/README.md) |
| 75 | Separate C++ declarations/implementations and program entry | [Read](75/scope.md) |
| 76 | Content-aware artifact publication and incremental native builds | [Read](76/scope.md) |
| 77 | Early semantic diagnostics | [Read](77/README.md) |
| 78 | String byte/text helpers, UTF-8 conversion, search and checked hex decoding | [Read](78/README.md) |
| 79 | Filesystem read/scan/stat/snapshot APIs | [Read](79/README.md) |
| 80 | File-lock handles and acquire/release/transfer | [Read](80/README.md) |
| 81 | Output digests, safe writes, staging/promotion/discard | [Read](81/README.md) |
| 82 | Immutable JSON document/node types, parse and typed queries | [Read](82/README.md) |
| 83 | Process handles, polling/results/cleanup and dt_sleep_ms | [Read](83/README.md) |
| 84 | Nonfatal reference-return/performance advice | [Read](84/README.md) |
| 85 | Use by ordinary `scpp build` / `scpp run` | [Read](85/scope.md) |
| 86 | Reference `foreach`, with or without explicit key | [Read](86/README.md) |
| 87 | Safe structural mutation/source replacement during iteration | [Read](87/README.md) |
| 88 | Trait adaptations and trait constructors/destructors | [Read](88/README.md) |
| 89 | PHP enum reflection (`cases/from/tryFrom`, pseudo-properties), string backing and enum methods/interfaces | [Read](89/README.md) |
| 90 | Dynamic `->` property access | [Read](90/README.md) |
| 91 | Named arguments, constructor property promotion | [Read](91/README.md) |
