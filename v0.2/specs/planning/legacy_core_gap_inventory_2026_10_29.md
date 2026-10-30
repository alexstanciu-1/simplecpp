# Legacy core runtime gap inventory

Doc Status: planning

The [PHP compiler](../../compiler/README.md) supersedes the retired compiler
implementations. Adapting their ownership handles is no longer planned.
Runtime ownership/value support remains useful for generated programs and plain C++
consumers. Earlier compiler-adaptation recommendations below retain historical context.
Date: 2026-10-29
Baseline: v0.2 ownership slice c8ed69fba.

This inventory compares capabilities, not header counts. Missing legacy code is not
an automatic port requirement. v0.2 remains the only active implementation; legacy
sources are bounded reuse candidates. No runtime code is changed by this review.

## Scope and evidence

Inspected the legacy runtime umbrella, core implementation bundle, scpp header
families, private support and operator/adaptation headers; compared them with the
v0.2 family contracts, publication metadata, generator and compiler foundation.
Module libraries and providers are excluded: filesystem, IO services, JSON runtime,
strings module helpers, datetime, regex, curl, database, process, tasks services,
UI/webview, tokenizer/source/compiler modules, binary and hosting integrations.
A scpp forwarding header does not make the forwarded module a missing core feature.
The v0.2 tool JSON reader is not a runtime JSON module port.

Rows below identify concrete donor files. They are bounded source-review findings,
not a complete behavioral parity audit or a fresh execution of legacy tests.

## Already present, with deliberate narrower scope

- Fixed-width integer registry, configured aliases, bool_t/null_t, float64,
  checked constructors/casts and requested numeric operations.
- Owned string value, byte storage, append/clear, comparisons and selected text casts.
- Concrete vectors for selected scalar/string elements, value reads, set/append,
  equality and explicit requested elementwise casts.
- Nullable/result/result_or_false/result_or_bool for selected payloads, with distinct
  payload/sentinel/error states and checked one-layer extraction.
- Explicit-present error_t carrying message/line/file.
- First concrete shared/weak/unique record handles, with inline private standard
  ownership and zero per-copy wrapper allocations. This is not the entire ownership API.
- Metadata preparation, generated physical API plans, template-free declarations,
  independent generation and boundary/lifecycle proofs.

## Capability inventory

| Capability and legacy donor | v0.2 gap | Recommended disposition |
| --- | --- | --- |
| Inline object values: value_p.hpp | No generated value-record family or value-normalization integration | Next bounded ownership work. Preserve inline/memberwise behavior and propagate actual copy/move/noexcept capabilities. |
| Ownership completion: shared_p.hpp, unique_p.hpp, weak_p.hpp | Current proof is exact mutable record types; no const-pointee variants, ancestry conversions, null operations or identity comparisons | Add only requested concrete operations. Const-pointee support matters for compiler immutable snapshots. Keep weak locking explicit. |
| Self retention and object tests: source_self.hpp, shared_self.hpp, object_cast.hpp | No managed-self protocol, adjusted-base identity, checked runtime downcast support | Separate compiler-object slice; inspect current source_self versus older shared_self before selecting reuse. Preserve control-block identity and multiple-base adjustment. |
| Typed hash: support/hash_t.hpp and hash_t.hpp | No generated hash specialization | High-priority reusable core. Preserve Swiss-table algorithms; begin with string-key/integer-value lookup. |
| mixed_t: support/mixed_t.hpp/.cpp, mixed_t.hpp | No tagged mixed value, table ownership/detach or mixed-specific operations | Port after the hash and ownership prerequisites; reuse the existing separated-family contract. |
| dynamic_t: support/dynamic_value.hpp/.cpp, dynamic_t.hpp | No independent dynamic payload family and shared dynamic containers | Port after hash/mixed review, while preserving independent dynamic-valued storage. Do not reinterpret it as a shared mixed container. |
| Table keys and interning: util/global_string_pool.*, detail/concrete_keys.hpp, detail/concrete_integer_keys.hpp | No equivalent key adaptation/intern pool | Review as part of hash/packed-table port, not a mandatory global compiler pool. |
| Stable persisted hashing: stable_hash.hpp | No versioned stable byte/string hash API | Small independent candidate when IDs/caches need it. Distinct from Swiss-table bucket hashing; preserve algorithm/version if persisted compatibility is required. |
| Vector completion: vector_t.hpp, vector_helpers.hpp | No general record/owner/nested elements; missing reserve/resize/removal/compaction and legacy access helpers | Extend based on consumer needs. Do not restore unrestricted interior references merely for API parity. |
| Fixed arrays: fixed_array_t.hpp | No generated fixed-count array | Useful later for concrete record layouts. Publish element/count-specific types when needed. |
| Traversal: foreach.hpp, typed_iterator.hpp, bound_iteration.hpp, value_iteration.hpp, vector_iteration.hpp, table_iteration.hpp, iteration_preservation.hpp | No general iteration/cursor protocol, custom iterator binding or mutation-during-traversal preservation | Define semantics before implementation. The compiler owns loop/type binding; the runtime may still need cursor/storage lifetime guarantees. |
| Collection algorithms: collections.hpp | No runtime map/filter-style algorithms and associated callable/carrier adaptation | Defer until iteration, callbacks and payload capability rules are settled. |
| Initialization and indexed paths: data_initializer_*.hpp, index_*.hpp, keyed_path.hpp, mixed/dynamic entry/input/initializer families | No typed/boxed initializer adapters, nested path read/write/probe/erase behavior | Compiler prepares known shapes and operations; runtime retains actual storage checks and dynamic operations. Avoid wholesale overload import. |
| Presence and extraction operations: operators/{probe,isset,empty,count,coalesce,conditional,unset}, lang/php/support/php_take.hpp | Some leaf methods exist, but no full legacy helper/lowering surface | Split source admission/sequencing from runtime state checks. Missing, null, false and error remain distinct. Do not reinstate PHP coercions as universal defaults. |
| Carrier composition: nullable.hpp, result*.hpp | No arbitrary record/owner payloads, move-only consuming extraction, nested payload publication or legacy delegation | Review after ownership/value capabilities. Explicit handles already carry absence; do not add redundant nullable ownership. Result-of-unique needs an explicit consuming contract. |
| Rich failures/exceptions: runtime_error.hpp, failure.hpp, error_value.hpp, exception_info.hpp, core/{failure,error_value,exception_info}.cpp | error_t exists, but standard exceptions are still the runtime failure boundary; no unified codes/details/traces/capture/rethrow facade | Design a small failure contract when diagnostics need it; do not duplicate error_t and error_value without a real distinction. Source exception/cleanup lowering stays compiler-owned. |
| Core string extras: string_t.hpp, support/utf8.hpp | No published UTF-8 validity/code-point length/slicing, byte slicing/capacity helpers or move-out bridge | Request concrete operations as needed. Keep byte indexing distinct from code points. Strings module utilities remain excluded. |
| Callable erasure: callable_t.hpp, task_permissions.hpp | No concrete callback signature wrappers or capture-transfer facts | Separate signature-specific generator/runtime slice. Compiler supplies capture/admission facts; runtime owns callable lifetime/invocation. |
| Async primitive: async_core.hpp/.cpp | No task/coroutine core | Conditional future work. This lives in the old core bundle but depends on larger language/runtime decisions; tasks module services remain excluded. |
| Compiler-oriented collections: row_arena_t.hpp, bitset_t.hpp, work_queue_t.hpp | No reusable stable-ID row arena, visited/dirty bitset or ring work queue | Mine when the compiler has a concrete use; choose compiler-support or runtime ownership deliberately. The current token sequence is not a general row arena. |
| Diagnostics/accounting: memory_accounting.hpp, support/dbg.*, support/var_dump.*, runtime_error call-depth support | No equivalent general dump/storage-accounting/debug-trace surface | Keep scoped diagnostics/profiling tools separate from mandatory semantic APIs. Current allocation tests are evidence, not a runtime memory-accounting API. |

## Legacy machinery to simplify or leave behind

- Giant runtime.hpp and aggregate generated operator/constructor surfaces: retain
  focused requested publications, not a blanket include/dependency graph.
- Generic public cast<T>, numeric_add helpers, full mixed overload grids and runtime
  class_of deduction to compensate for type-blind lowering: the symbol-aware compiler
  can prepare explicit operations; private runtime algorithms still enforce checks.
- Broad PHP truthiness, null-to-text and boxed coercions: language-flavor choices,
  not missing universal v0.2 behavior. Current bool/null/text defaults are deliberate.
- Public raw standard-container/smart-pointer access, unique release/adoption,
  expiration-based weak bool and duplicated deref/arrow/native_value spellings:
  do not import them merely because legacy tests used them.
- nullopt_t/nullptr_t/true/false sentinel helper types: necessary states already exist
  in the new families. Separate public sentinel identities need a concrete use case.
- Reference/native adaptation machinery and arbitrary wrapper delegation: distinguish
  compiler-known borrows from actual runtime lifetime enforcement. No automatic
  implicit ownership extraction or generalized reference export.
- Resource/stdio facades under core/ that directly own FILE streams: classify with
  the excluded IO/resource integration work, not a reason to import the old core bundle.

## Recommended order

1. Complete the narrowly needed ownership/value foundation: inline value records,
   capability propagation and read-only ownership publication. Keep the first
   compiler adaptation as a separately validated consumer slice.
2. Extend concrete vectors only for actual record/handle needs, then publish typed
   hash string-to-integer lookup while retaining the protected Swiss-table core.
3. Settle iteration and mutation/lifetime rules, then additional container operations.
4. Port mixed and dynamic via hash-owned storage, preserving their separate families.
5. Add structured failures, indexed/initializer adapters and wider carrier composition
   alongside the first compiler/source features that require them.
6. Keep fixed arrays, stable hashing, row arenas/bitsets/queues, callbacks and async
   as explicit demand-driven slices rather than prerequisites for all compiler work.

This order does not authorize a bulk port. The current boxed API is documented
in [boxed_slice.md](../boxed_slice.md); the detailed
ownership baseline is [ownership_contract_review.md](ownership_contract_review.md)
and its implemented subset is [ownership_slice.md](../ownership_slice.md).

## Completion criterion

"Core ready" should mean the agreed concrete consumers work through metadata-driven,
template-free interfaces with verified behavior and ownership, not that every legacy
header has a replacement. Track each row as reuse/adapt/compiler-owned/defer/retire
when its consumer is selected. No percentage-complete claim follows from this inventory.

## Ownership/value implementation follow-up — 2026-10-29

The first recommended foundation step is now implemented for registered concrete
records: inline values propagate payload copy/move/noexcept behavior, and generated
shared/weak/unique const-pointee handles support explicit add-const qualification.
See [ownership_slice.md](../ownership_slice.md) for the exact boundary and proof.
The table above retains its c8ed69fba inventory baseline. Source value normalization,
compiler adaptation and the remaining ownership operations are still pending.
