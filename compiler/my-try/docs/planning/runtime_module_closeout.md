# Runtime module integration audit — 2026-10-10
Doc Status: planning

This audit closes the bounded [integration plan](runtime_module_integration.md),
not all runtime API development. Specs remain authoritative. The executable
catalogs declare supported contracts only; the [coverage inventory](runtime_catalog_coverage.md)
owns detailed string/regex/source conflicts. Broader testing is
CH15-RUNTIME-INTEGRATION-TESTS-01. Remaining provider/API batches are tracked by
CH15-RUNTIME-REMAINDER-01 and the specific semantic/lifetime debts it references.

## Inventory boundary and disposition

Compared all current `runtime/include/modules/` owners, the strict runtime symbol
map (`generators/php/specs/php_runtime_symbols_strict.json`) and executable catalogs.
The map is historical exposure evidence, not permission to import obsolete semantics.
At this checkpoint catalogs expose 134 distinct function names; the older strict
map contains 275. Overload counts and language intrinsics are separate quantities.

| Owner | Disposition / remaining surface |
| --- | --- |
| core | Registered byte strlen, count recipes, CLI, environment/shell and memory helpers. Type predicates (`is_bool/int/float`) await mixed/dynamic family consistency. Stable string hashing awaits persisted-key contract repair. Debug/dump controls and microtime need source/profile contract review. Vector reserve/capacity/resize/filled/clear/clear_keep_capacity/compact await a separate mutable-container API batch; not implicitly enabled by indexing. |
| strings | Ready batch registered. strpos/strrpos, substr_replace and boxed joins/formatting remain held for the documented contract conflicts. Builder byte-span append awaits safe views. |
| datetime | Eleven strict helpers registered and provider linkage declared. Legacy aliases and calendar classes are not enabled. Fixed-width arithmetic and platform edges remain debt. |
| filesystem | Twenty-two helpers and shared lock token registered. Eleven `scpp_output_*`/`scpp_input_digest` bindings are compiler-host publication internals, not public source APIs. |
| json | Encode/decode and twelve document/navigation functions registered, three shared asset types and diagnostic fields. Pull reader remains an internal C++/portable compiler schema service, not a source-exposed object API in this batch. |
| process | All five strict managed-process functions, two handles and six output fields registered; optional module. |
| source | Thirteen owning buffer/index/location functions and three types registered. source_buffer_span, byte_span_len/at/to_string and builder span append are held for dangling-view/invalidation repair. hash_bytes/stable_hash_bytes_u64 also require hashing review. source_text_vector_move_append is an internal portability transfer helper. |
| regex | Ten helpers/eighteen signatures registered. Filter conflicts remain unresolved. Scalar-limit/count overload ambiguity requires an unambiguous provider adapter. Callback recipes are now supported by metadata, but callback/callback-array production bindings remain a deferred provider/retention/contract validation batch, not a missing schema feature. |
| compiler | Storage, Keyed_Storage, Key_Storage_List and cursors remain compiler-framework APIs. Public generic member/protocol metadata does not automatically provide their mutation, retained-reference or cursor contracts. No source registration is claimed. |
| binary | Low-level cache codec writer/reader are C++ compiler support. Native byte views, size_t/std::string and borrowed-reader lifetime need a source facade before public registration. |
| tokenizer | jss/phs tokenize and tokenize_buffer, token_buffer count/kind/length/start_offset/line/column/flags/to_mixed remain deferred. Compact columns, ownership and diagnostic records need a source-facing model; do not expose std::vector or raw diagnostic pointers through false metadata types. |
| tasks | All 21 strict task scheduling/publication/status/configuration/metric names remain deferred under CH15-LIB-DYN-01. Owning callback metadata does not establish cross-thread transfer, dynamic-family storage or synchronization safety. |
| curl | Nine strict helper names and option/info constants remain a separate provider batch. Shared handles/fields/overloads can now be represented; optional native dependency availability, boxed getinfo contract and network/disabled-provider proofs remain required. No new compiler model is implied. |
| ui/webview | Thirteen UI and eleven WebView names remain deferred with platform backend packaging, event-owner lifetime and typed-record review. The bounded Linux linker model does not promise Objective-C/framework/resource composition. |
| mysql | Existing module implementation/runtime checkpoint is not source registration. Remains the explicit library model/mixed-dynamic review debt, not an inferred public API from C++ internals. |
| io (legacy scpp header owner) | Ten io_open/seek/tell/read_line/read/write/rewind/flush/eof/close helpers remain deferred for resource-handle ownership and source contract review. No mixed surrogate. |

Remaining strict-map entries are language/lowering mechanisms, not missing module
rows: empty, isset, take, identical/not_identical, condition_truthy, coalesce_eval,
ternary_eval, isset_eval, echo_one/echo_eval, expect_array_argument and js_plus.
Their current v0.2 syntax/semantics or frontend ownership governs availability.
`to_dynamic` and layout_sizeof/alignof/offsetof/field_sizeof likewise require their
explicit conversion/compiler-intrinsic paths; this audit does not invent runtime
aliases for them. `dbg`, `dbg_if`, dbg_set/unset/enabled, var_dump and the four
`__scpp_debug_*` names remain the core debugging/profile review above.

## Residual hardcoding classification

Inspected runtime catalog loading/registration, language publication, preparation
and C++ backend bindings. Ordinary asset names (JSON documents, process handles,
file locks, provider test types) require no parser/preparation/emitter branches.

- `compiler/types/language.php` publishes registered exposures. Its int/bool/float/
  string/error/void queries select language semantic identities, not provider recipes.
- `compiler/callables/language.php` retains enum_name/value/from_value compiler
  intrinsics. Metadata is not executable semantic compiler logic.
- Variadics explicitly use vector because vector packing is the language ABI.
  Metadata declares element recipes, never vector<int>/vector<string> rows.
- Nullable, Result, ownership wrappers and mixed/dynamic semantic roles remain
  language-owned. Metadata cannot claim these roles merely by a name or C++ binding.
- Four existing vector/hash allowlists remain in object default construction,
  cleanup, value probes and callable-wrapper admission. These are not required by
  the new assets; arbitrary providers do not inherit these operations. They remain
  CH15-RUNTIME-TYPE-METADATA-01 follow-up for explicit capabilities/protocols, not
  evidence of fully metadata-driven operation coverage.
- Backend type/function/property/initializer bindings are joined by definition
  identity; provider source/lib/definition inputs come from selected metadata.
  No provider JSON is read while preparing calls or executing native builds.

## Requirement-to-evidence review

| Planned deliverable | Current proof |
| --- | --- |
| Storage, handle versus payload copying | runtime_handles: deleted-copy provider behind copied shared handles, alias/null/reference/Result transport |
| Members/factories/constants/properties | runtime_types + runtime_handles: nominal/generic owner recipes, stable identities, property storage, no fabricated AST |
| Reusable protocols | runtime_protocols/runtime_initialization: unfamiliar template list/keyed construction, reads/writes/iteration; no application enumeration |
| Defaults, variadics, reference overloads, callbacks | runtime_signatures/runtime_callbacks: exact admission, copied packs, recursive callback inference and borrowed-capture rejection |
| Decode once/reset reuse/configuration failures | runtime_catalog/runtime_types: removed input files, fresh canonical identities, rejected private configuration leaves old input intact |
| All production catalogs together | runtime_inventory: all eight modules configure/reset together with exact deduplicated provider/link closure |
| Native source/link/PCH requirements | runtime_module_build: cold/warm/body/provider/definition/library changes, catalog absent during build |
| Export/import behavior | runtime_exports: rooted aliases, generic/nominal types, source shadow edits, namespace isolation and generated execution |
| Real multi-module integration | runtime_assets: all twenty JSON/lock/process bindings, diagnostic/output fields, lifetimes, OS failures and native provider linkage |
| Operator guidance and deferred work | runtime/metadata/README.md; module inventory above; CH15-RUNTIME-INTEGRATION-TESTS-01 and CH15-RUNTIME-TYPE-METADATA-01 |

Dated runs and exact scope remain in the integration plan. PHP/generated-program
proofs are not a native compiler rebuild. Whole-runtime migration, all optional
backends, new borrowed-view/async contracts, automatic package installation and
replacement of v0.1's maps are not completion claims of this bounded foundation.
