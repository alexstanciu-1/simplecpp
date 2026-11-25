# v0.2 milestone: v0.1 source compatibility
Doc Status: planning

Reviewed 2026-10-17 against my-try `d675aca8251374555c9191067793175b43d8a79c`
and the working-tree catalog, including pre-existing Chapter 15 edits.

## Explicit goal

The next v0.2 milestone is to compile supported v0.1 PHS/PHP++ programs and
preserve their specified observable behavior, except for explicitly documented
v0.2 incompatibilities and approved semantic changes. This includes valid existing
source spellings, callable/type contracts and required runtime APIs, not just
equivalent examples rewritten into the preferred v0.2 style.

**Identical lowering is not a goal.** v0.2 uses shared semantic preparation,
prepared operations, different C++ representations where appropriate, and
partitioned output. Acceptance compares source behavior, failures, value/reference
semantics and lifecycle effects; it does not compare v0.1/v0.2 generated C++ bytes.
Existing PHP-host/native-v0.2 artifact comparisons remain useful implementation
parity checks, but do not establish compatibility with v0.1.

This report records the requested milestone, not new semantic exceptions or
authorization to implement cross-owner redesigns. A temporary deferral is not an
approved incompatibility. An alternative API is not source compatibility unless
its migration is explicitly accepted.

Non-goals: full PHP compatibility, reproducing v0.1 bugs/unsafe lowering, LLVM,
reactivating old STAN, new language extensions absent from v0.1, and identical ABI,
generated filenames or build performance. JSS is a separately scoped frontend
follow-up: v0.1 has it, but this milestone follows the catalog's PHS-first scope.

## Baseline verified locally and on GitHub

`git ls-remote --heads --tags origin` was executed successfully on 2026-10-17.
No checkout switch or fetch was needed; the relevant commit objects exist locally.

| Reference | Commit | Meaning |
| --- | --- | --- |
| GitHub/local `v0.1.77`, GitHub/local `main` | `3940141f8be319a8e3feeb748a0bef5628f5a390` | Latest published **tagged** v0.1 baseline; the annotated tag object itself is `f3bf694e…`. |
| GitHub/local `release/0.1.78` | `d2f63e37efe949355c11b137cc4c3520fbfc6368` | Untagged release candidate, 2026-09-22, explicitly pending downstream portability proof. |
| GitHub `develop` | `75de94c71e37060c564b6ade19850b7686bd457a` | Newest verified v0.1 integration head, 2026-09-24; includes compiler Storage #242. Its VERSION.txt still says 0.1.77. |
| Local `develop` | `8239c9ee80e27f12cb63f4d2de22f892e5360d04` | Stale, 2026-07-01; do not use it as the newest baseline. |

Use **`75de94c7` as the development comparison**, retaining `3940141f` as the
released acceptance baseline. The release candidate and develop are not a simple
ancestor chain: release packaging differs. Their generator trees contain the same
qualified-construction fix; the development head additionally contains Storage work.
Do not call 0.1.78 a published release or infer version recency from VERSION.txt alone.

Remote references: [v0.1.77](https://github.com/alexstanciu-1/simplecpp/tree/v0.1.77),
[release candidate](https://github.com/alexstanciu-1/simplecpp/tree/d2f63e37efe949355c11b137cc4c3520fbfc6368),
[development baseline](https://github.com/alexstanciu-1/simplecpp/tree/75de94c71e37060c564b6ade19850b7686bd457a).

## Overall finding and evidence boundary

Current follow-up scope is **strict-profile source tests only**. See the
[strict cause groups](v0_1_compatibility_strict_causes.md) for the active implementation
queue; legacy-profile and native-runtime findings below remain background evidence.

The [expanded execution report](v0_1_compatibility_expanded.md) now separates
baseline-proved source failures into missing implementation, explicit v0.2 changes,
and unresolved contract decisions. It also records compile-only tests, negative
oracles, runtime C++ tests, generation probes and blocked harnesses. The
[runner guide](v0_1_compatibility_testing.md) owns reproducible discovery and
execution. The [50-case first pass](v0_1_compatibility_first_pass.md) is retained as
historical evidence; it is no longer the current coverage boundary.

**The bounded catalog pass is substantially implemented; v0.1 compatibility is
not complete or demonstrated.** The largest remaining areas are original source
syntax, runtime/library coverage, several language families omitted from the
imported cards, and end-to-end project acceptance.

The tables below began as a documentation/source/metadata audit; use the linked
expanded evidence for fresh differential results.
"Missing" below means a documented gate or inspected absent implementation path;
"review" means a compatibility obligation needing a concrete baseline fixture.
Historical tests linked by chapters are evidence for their stated slices, not a
fresh run at this checkout. No native compiler rebuild was run. The expanded source/runtime suite uses
PHP-hosted compilers and freshly compiled generated programs.

The imported [source ledger](../catalog/source_merge.json) merges four generator
documents and 402 source rows. It is not a complete release API inventory. Both
accepted and rejected v0.1 examples occur in it, and many progress examples were
adapted to strict v0.2 spelling. Therefore neither card counts nor Chapter 15's
52 grouped debts measure remaining v0.1 compatibility.

## Confirmed gaps and bounded support

| ID | Area / v0.1 evidence | Current v0.2 boundary and required work | Owner / priority |
| --- | --- | --- | --- |
| COMP-01 | Comment-based local/property/parameter/return types, including closure signatures; generator rules and imported cards | Tokenization skips comments; strict equivalents do not preserve annotation intent. Catalog explicitly defers legacy annotations/conflict rules. Inventory every supported placement and alias; normalize into the existing type syntax, or approve a precise migration exclusion. Inferred scalar examples can hide lost type intent. | Frontend/type syntax; early compatibility slice. [CH01](../catalog/01_literals_locals.md), [CH10](../catalog/10_objects.md), [tokenizer](../../02_tokenize/tokens.php). |
| COMP-02 | `array` / `?array` parameters and by-reference forms with table-only entry guards | Deferred even though explicit mixed/vector/hash calls work. The old guard is not equivalent to accepting any boxed scalar. Decide profile admission and reuse parameter-boundary preparation. | Type/parameter boundaries; [CH06](../catalog/06_containers.md), [CH07](../catalog/07_references_lifetime.md). |
| COMP-03 | Scalar primary-type union parameters and `@arg.x.from(T)` normalization | v0.1 has explicit normalization helpers; my-try type grammar has no equivalent union/normalization route. This is distinct from payload unions and ordinary nullable syntax. | Signature binding/conversions; [baseline contract](../../../../generators/php/specs/primary_type_normalized_parameters.md). |
| COMP-04 | PHS `async function`, task await and timer suspension; v0.1 alpha contract and language tests | No async syntax/preparation/emission route found in my-try. Runtime coroutine support alone is insufficient. Add a named task/suspension semantic owner before lowering. This is separate from thread-backed Tasks migration. | Callable/control-flow/lifetime owners; [async contract](../../../../specs/async_await.md). Cross-owner design needed. |
| COMP-05 | Restricted `union` payload declarations and `layout_sizeof/alignof/offsetof/field_sizeof` | Existing class/struct/enum support does not include restricted union declaration handling or registered layout probes. Preserve the baseline's narrow admissible payload contract; do not infer unrestricted union support. | Type declarations/layout + compiler-provided operations; [compact layout](../../../../specs/compact_layout_types.md), parser/type registry. |
| COMP-06 | `fixed_array<T,N>` and v0.1 collection transforms | No current module exposure of fixed arrays; current type arguments are type syntax, not a non-type size model. `collection_map/filter`, `sequence_map/filter`, `keyed_map/filter` are also absent from runtime module functions. Implement shared carrier/callback contracts rather than six name-specific compiler branches. | Runtime generic contracts + type arguments; [baseline collections contract](https://github.com/alexstanciu-1/simplecpp/blob/75de94c71e37060c564b6ade19850b7686bd457a/specs/builtins/collections.md), [runtime inventory](runtime_catalog_coverage.md). |
| COMP-07 | Reference foreach and mutation during iteration | Value/key-value foreach is supported in bounded non-mutating cases. Reference foreach is disabled. A safe mutation/replacement/guard contract is required before compatibility; unsafe v0.1 slot rewriting is not the target. | Existing final-catalog gates CH06-ITER-01 / CH06-REF-01 in [CH15](../catalog/15_blocking_debt.md). |
| COMP-08 | Existing container values boxed into mixed/dynamic; vector removal and indexed updates | Contextual nested literals are implemented but do not establish conversion of existing typed containers. Vector-element unset and indexed increment/decrement remain gated; existing-element compound assignments and field-root writes have later support and must not be re-queued wholesale. | Conversion/place/container operations; ARR-VALUE-001 and [CH06 remaining scope](../catalog/06_containers.md#remaining-scope-and-reopening-gates). Reconcile each old valid carrier separately. |
| COMP-09 | Interpolation of indexed/property/method expressions | Simple scalar variable interpolation is proved; path/call interpolation remains deferred. v0.1 rules explicitly admit these simple subexpressions. Reuse ordinary expression preparation with ordered string conversion. | Expression/interpolation owner; NOTE-052 in [CH05](../catalog/05_dynamic_boundaries.md), baseline rules “String interpolation limitations”. |
| COMP-10 | Exceptions: `new Exception(...)`, named throwable subclasses and typed catch arms | Current single runtime Exception factory/throw/catch works; constructor metadata, source subclasses and multiple catch dispatch remain deferred. A factory rewrite is a migration, not unchanged-source acceptance. Review non-scalar/reference finally returns against baseline accepted return families. | Existing CH13 exception/transfer debts; [CH13](../catalog/13_errors.md). Union catches, throw expressions and transfers escaping finally were not broadly supported in v0.1. |
| COMP-11 | Original inheritance/member spellings and contexts | Many old CH10 “await CH11” rows are stale: interfaces, protected access, overrides and static-context late binding now exist. Remaining compatibility candidates include instance-context late static binding, trait lifecycle members, broader receiver lifetimes and reference-return origins. Prove baseline accepted cases before treating all CH15 extensions as parity work. | [CH11](../catalog/11_inheritance.md), CH15 method/inheritance/trait/reference debts. |
| COMP-12 | Runtime functions and library profiles | Current metadata covers a useful subset, but stream IO, collection transforms, debug/predicates, selected strings and optional libraries are incomplete. Strict v0.1 code is affected as well as legacy-profile code. See API inventory below. | Runtime-owned module metadata and provider owners. |
| COMP-13 | Project composition and ordinary build/run use | my-try publishes multi-source artifacts and has a native cache consumer. Its source-directory entry does not establish `prism.json` dependency/export/library/profile/module behavior or routing through `scpp build/run`. The existing CLI still references the legacy generator. Inventory and prove a v0.2 project adapter. | CLI/project workflow + publication/native consumer; [project contract](../../../../specs/project_build_v1.md), [main.php](../../main.php), [native guide](../architecture/native_cpp_build.md). |

The normal scalar/control-flow/function/container/record/callable foundations
should be reused. Chapters 01–14 provide substantial typed-operation and generated
program evidence; the CH13/14 native gates cover their stated bounds. This milestone
does not require rebuilding those concepts from scratch.

## Runtime API comparison

A name-only comparison was performed between the two baseline
`generators/php/specs/php_runtime_symbols_{strict,legacy}.json` maps and all current
`runtime/metadata/modules/*.json` free functions (excluding member entries):

| Baseline map | Distinct names | Also present as module free functions | Absent from that metadata set |
| --- | ---: | ---: | ---: |
| strict | 257 | 121 | 136 |
| legacy | 187 | 80 | 107 |

The current metadata set has 134 distinct free-function names. These are **discovery
counts, not missing-feature counts**. Compiler intrinsics (`take`, `isset`, `empty`),
old lowering-only helpers and internal/debug hooks occur in the old maps. Matching
names do not prove signatures, default arguments, return wrappers, constants,
type availability, profile behavior or optional-provider linkage. All modules were
included in the comparison, even those not selected by default.

| Group | Concrete remaining exposure / compatibility work |
| --- | --- |
| Core/collections | Six map/filter names above; `is_bool/is_int/is_float`; `dbg`, `dbg_if`, gate helpers, `var_dump`, `microtime`; `vector_capacity/reserve/resize/filled/clear/clear_keep_capacity/compact`; layout probes. Distinguish public functions from internal instrumentation and lowering helpers. |
| Strings | `strpos`, `strrpos`, `substr_replace` are held for semantic conflicts, not missing JSON rows alone. Boxed `implode` / `number_format` overloads are incomplete despite the names being registered. Byte/code-point behavior requires explicit disposition. |
| Filesystem/IO | Most strict filesystem functions and locks are registered. **`fs_is_windows` is absent**, despite being added in v0.1.77. The `io_*` stream family still needs resource/lifetime exposure. |
| Regex | Ten helpers are registered. Filter, callbacks/callback arrays and limit/count output overloads remain held; `regex_replace` name presence does not establish all v0.1 overloads. |
| Tasks / DB / UI / curl | Tasks family separation and transfer/synchronization remain open. MySQL registration/consumers require a separate library audit; it is not measured by the two flat maps. Curl, UI and WebView are not registered whole-provider equivalents. |
| Source/tokenizer/Storage | Owning source buffers/indexes are exposed. Borrowed spans, span append/hash and tokenizer facade are not. Compiler Storage exists internally and in the newest baseline, but internal native use is not proof of a public PHS API. |
| Stable hashes | Hash helpers are intentionally held for algorithm/stability and persisted-key review. Preserve the agreed contract or publish an explicit migration; do not silently change persisted results. |
| Legacy profile | Names such as `file_get_contents`, `fopen`, `date`, `strtotime`, `preg_*` are not made available by strict `fs_*`, `io_*`, `dt_*`, `regex_*` APIs. They may have different wrapper contracts. Profile selection/adapters need an explicit compatibility decision, not blind aliases. |

Existing owners: [runtime coverage](runtime_catalog_coverage.md),
[runtime closeout](runtime_module_closeout.md), [CH15 review](../catalog/15_review.md).
JSON documents, locks and managed-process metadata are already implemented; their
provider integration should be tested in real programs, not reported absent.

## Approved differences and items needing disposition

| Difference | Treatment for this milestone |
| --- | --- |
| Separated mixed/dynamic families, explicit bridges, JSON result-family changes | Approved break under [dynamic types](../../../../specs/dynamic_types.md). Default `json_decode` yields dynamic; mixed decoding is explicit. Do not restore cross-family payload boxing to pass old tests. |
| Known-target data construction; no implicit mixed target for untyped literals | Approved [data initialization](../../../../specs/data_initialization.md) change. Record the minimal required source migration; do not silently rewrite the legacy corpus and count it as unchanged compatibility. |
| Namespace identity, body-local imports, relative/absolute lookup and selected executable entry | Approved [namespace rules](../../../../specs/namespaces_v0_2.md). Old accidental C++ lookup/import leakage is not a requirement. Nested namespace bodies still need an explicit supported/excluded disposition. |
| Earlier rejection of statically invalid programs | Approved [diagnostic policy](../../../../specs/v0_2_preparation_diagnostics.md). Preserve value-dependent runtime guards. A changed failure stage is not itself a compatibility defect. |
| Ordered expression evaluation, typed operators, storage normalization and partitioned artifacts | Follow current owning specs; keep behavioral examples for changed contracts. Old C++ text/helper calls do not define acceptance. |
| `take` primary assignment spelling | The helper spelling remains supported under [error/take](../../../../specs/error_values_and_take.md); do not require unnecessary rewrites. Intrinsic shadowing behavior is an explicit current restriction and must be listed for affected old programs. |
| Legacy comment annotations and legacy library profile | Deferred outside the current strict-first queue, including S02 fixtures whose library profile is strict but declaration syntax is legacy. Keep historical failures and separately labeled strict-declaration probes; this is not a claim of unchanged-source compatibility or permanent removal. |
| Static prologue `require_once`, root `$argc/$argv`, magic/source constants and runtime aliases | Compatibility review required. v0.1 documents a narrow textual include form and root CLI globals; v0.2 CLI helpers do not alone prove these spellings. Project-composition guidance discourages source includes, so reconcile that contract rather than reviving textual generated-header inclusion automatically. |
| `(object)[...]`, `new stdClass()` and dynamic property access | Legacy construction/representation intersects the approved family change. Dynamic `->` remains deferred. Specify which spellings migrate and which operations must remain available; neither unrestricted PHP objects nor a blanket exemption is implied. |

String indexing is reserved/deferred by the existing runtime contract; a catalog
syntax card is not proof it worked in v0.1. `(array)` casts, enum reflection/string
backing/methods/interfaces, arbitrary named arguments, constructor promotion, union
catches, general borrow types and LLVM are **not automatically compatibility gaps**.
Some were explicitly unsupported in the baseline; others need positive baseline
evidence. Keep these separate from confirmed parity work and from v0.2 enhancements.

## Recommended milestone sequence and completion criteria

1. Continue the pinned executable ledger now established by the expanded run.
   Promote generation-only probes into behavioral or rejection oracles; add the
   remaining project/provider/tool adapters and representative real applications.
   Preserve separate source, runtime and diagnostic evidence lanes.
2. Reconcile stale cross-chapter rows against later proofs. In particular CH01's
   control-flow handoffs, CH03's foreach handoff, CH08's object handoffs and CH10's
   CH11 handoffs are not reliable current absence lists. Reconcile CH09 constructor
   carryovers separately from genuinely deferred enum features.
3. Close original syntax and high-use API gaps through their existing owners:
   annotation/type admission, normalized parameters, strings, predicates/debug,
   filesystem target query, IO and collection transforms. Keep contract conflicts
   visible instead of registering unsafe approximations.
4. Agree bounded designs for reference iteration, async, union/layout support and
   exception/transfer compatibility. These can span ownership areas; discuss scope,
   risks and proof cost before implementing. Existing CH15 iteration gates remain.
5. Complete required optional-library/profile and project-build integration, then
   run representative multi-file legacy applications through the actual v0.2 entry.

Each fixture/signature needs a baseline reference, original source, profile/modules,
expected behavior, v0.2 result, owner and one explicit disposition:
`compatible-proved`, `approved-change` (spec plus migration), `missing`,
`baseline-unsupported`, or `unverified`. A compiler-specific rejection is not a
successful result for a valid unchanged baseline program.

Completion requires every in-scope supported baseline case to be either proved
compatible or linked to an approved change. Compare output/exit/failure states,
null/false/error distinctions, mutation/copy/identity, evaluation order and cleanup.
Use the [shared runner](../testing.md), with focused generated-program proofs and
explicit native-compiler evidence for the final milestone. Include incremental
edit/reject/repair checks where new dependencies are introduced. Golden C++ from
v0.1 must not be used as the compatibility oracle.

No honest milestone completion percentage is available yet. The expanded ledger
covers the metadata corpus and further probes, but project/provider adapters,
negative-test semantic review and unannotated behavioral oracles remain open.
Use its classified first blockers to choose implementation slices; do not turn all
Chapter 15 debt or all differential failures into a missing-support count.
