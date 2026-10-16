# 14. C++ artifacts, runtime integration and diagnostics
Doc Status: planning

> **Important: LLVM is deferred by default. Work on the direct LLVM backend only when the user explicitly requests it. The v0.2 default scope is Frontend + C++ S2S.**

[Catalog and workflow](README.md) · [Source merge ledger](source_merge.json)

Cross-cutting: use a minimal output skeleton from the first runnable example; grow file splitting and incremental publication as dependencies appear.

Order is a discussion sequence, not a claim that every row is a prerequisite. Split combined examples before implementation.

## Bounded closeout and native gate — 2026-10-16

**Chapter 14 is closed within its implemented frontend + C++ S2S bounds.** Its 32
imported rows are reconciled to current owners, superseded policies, supporting
guidance or explicit limits. CH14-EMIT-01 and NOTE-024 retain legal source shadowing
with `-Wshadow`; no warning-free or complete legacy-PHP compatibility claim is made.

Native execution exposed an arrow-capture portability defect: PHP's absent-key
`??` fallback became a throwing native hash read. `Capture_Preparation` now defaults
the mode to value capture and reads an explicit mode only after a membership check.
The legal-shadowing artifact parity/execution proof covers this path. Publication
journal generation needed the same explicit membership checks for absent before/after
digests; native cold publication and failure/repair cover that adaptation. Four focused
host closure/capture suites also pass (`/tmp/scpp-ch14-capture-host-20261016/commands.json`).
The test driver
also avoids a top-level early return unsupported by the legacy entrypoint lowering.

The compiler/proof sources (215 PHP files) converted to PHS and compiled/linked
natively. The converted driver exercises the compiler's real artifact, publication
and native-build workers; it does not substitute host PHP for those native operations.
The gate passes:

- 14 multi-file PHP/native artifact-byte comparisons and 28 generated executions
  across PCH/no-PCH, including runtime-name collisions, qualification, source headers,
  reference/ownership ABI and legal block/parameter/foreach-key/catch shadowing.
- 27 standalone native-generated header checks without PCH and four host contract suites.
- Native/PHP publication bundle and manifest parity; no-op byte/inode/mtime stability,
  entry-body rebuild isolation, failed-publication readiness refusal and repaired reuse.
- 5 incremental-program and 7 optional-provider native build/execution scenarios,
  including PCH/no-PCH, successful warning delivery, provider-source edits,
  definition/PCH invalidation and library-only relinking. Removing the source catalog
  before building proves consumption of the published runtime requirements.
- A final no-change native compiler rebuild.

Evidence: `/tmp/scpp-ch14-native-20261016/summary.json`, final attempt `logs-5/`,
its native publication/build `observations.json` files, `source_hashes.json`,
`harness_hashes.json`, `runtime_catalog_hashes.json`, `candidate.json`, and the executable
SHA-256 in the summary. Candidate base is `29efc8ad` plus the retained validation
harness/portability overlay captured by those hashes. The driver replaces the normal
entrypoint; this does not certify a new native production CLI. Legacy STAN and LLVM
were explicitly skipped. The shared runner used PHP CLI. Warnings remain visible.

The earlier CH14-EMIT-01 proof below retains its broader ten-case host-driven native
cache matrix and actual old-cache flag-transition check. This focused native gate
supplements it; it does not repeat every historical locking/interruption scenario,
every optional module, or the cumulative CH01–14 suite. CH12's separate native coverage
limits remain unchanged. The retained [native gate command](../testing.md) reproduces
this scope; a missing successful summary cannot establish closeout.

Deferred work remains explicit in [remaining work](#remaining-work) and
[Chapter 15](15_blocking_debt.md#chapter-14-closeout-deferrals): structured diagnostic
origins, broader template incomplete-type contracts, optional-module/toolchain
acceptance, a checked current-build/run reader, automatic runtime refresh, cache cost,
parallel native actions, garbage collection and LLVM. Formatting and broad warning
cleanup are not silently added to the completed slice. Next: prioritize Chapter 15
by user value and dependencies before agreeing its first bounded slice.

## Reconciliation — 2026-10-16

At reconciliation, the 32 imported rows were not 32 missing features. This review
maps them to current owners and separates legacy policies from implementation gaps.
`superseded` means the imported policy is replaced, not that its old spelling was
implemented. `reconciled-bounded` inherits the linked owner's limits. Imported source
text below remains historical evidence; it does not reopen completed chapter work.

### Artifacts

[Chapter 08](08_project_symbols.md) and its
[artifact roadmap](../planning/incremental_cpp_artifacts.md) already own:

- Individual type/callable headers, initial per-source implementation groups and a
  selected `entry.cpp`, using one path for single- and multi-source programs.
- Stable logical identities, declarations separate from bodies/default arguments,
  and backend-owned declaration-versus-complete-definition requirements. Bare-record
  declaration-only consumers can avoid layout includes; template/handle payloads
  remain conservatively included. This is not missing shared-handle forward-declaration
  support that must be implemented merely to match EMIT-FILE-003's historical example.
- [Content-aware publication](../architecture/output_publication.md): no-op byte/mtime
  preservation, owned-file retirement, failed-attempt readiness and cooperative locks.
- [Native caching](../architecture/native_cpp_build.md): exact unit membership,
  runtime-only PCH, content/dependency keys and failed-action recovery.

Retained proof owners: `tests/project_cpp.php` / `.py`, `cpp_artifacts.php` / `.py`,
`output_publication.py`, `native_cache.py` and `native_invalidation.py`. The project
runner already compiles separate units and standalone headers without PCH and checks
actual Clang dependencies. Rebuilding those facilities is not the next slice.

### Runtime

[Runtime module metadata](../../../../specs/runtime_module_metadata.md) separates
semantic contracts from C++ bindings. Selected module closure supplies provider sources,
ordered libraries and definitions through the publication's `runtime_build` snapshot;
the native builder consumes it without reopening catalogs. This is already beyond the
historical core-only cache. `runtime_catalog.php`, `runtime_build_contract.php` and
`runtime_module_build.py` own admission, publication and real build/cache proofs.

[Reference safety](../../../../specs/native_reference_safety.md), `tests/references.php`
and `nullable_handles.php` own handle references and nullable normalization. `T` and
`?T` are not a new promise of future non-null handle enforcement. Ordinary inline
nullable types retain their distinct wrapper identity. Runtime probes, reset and strict
identity remain with Chapters 05–07; static/member APIs remain with Chapters 09–11.
[Ordered echo](../../../../specs/output_v0_2.md) and `tests/echo.php` already cover NOTE-046.
The [Chapter 13 gate](13_errors.md#bounded-closeout-and-native-gate--2026-10-16) supplies
current native evidence for its error/exception/cleanup bounds only.

### Semantics

The imported type-blind-generator boundary is not the v0.2 compiler model.
Shared preparation resolves types, calls and admissible operations; backend workers
consume settled facts. [Expression evaluation](../../../../specs/expression_evaluation.md)
and Chapter 02 own operand sequencing, integer division and bounded comparison domains.
Chapter 03 owns switch/loop behavior; Chapter 06 owns container defaults and iteration;
Chapter 08 owns namespaces, imports and entry selection. Their explicit deferrals stay
with those owners rather than becoming new Chapter 14 implementation requirements.

### Qualification

`CPP_Generator::source_name` owns reversible role-prefixed spellings; resolved source
identities and registered runtime C++ bindings own call targets. The current output
uses explicit `scpp::` / `::scpp::` references. Reintroducing the imported rooted-name
ban or blanket using-directives would conflict with the existing output architecture.
`tests/namespace_identity.php`, `namespaces.php` and runtime-catalog shadowing checks
provide relevant evidence. Warning-enabled generated-artifact coverage is the remaining
bounded acceptance gap below, not a mandate to rename every existing symbol.

### Governance

NOTE-001/003/004/007/030/064 are provenance/workflow guidance, not language features.
The [spec map](../../../../specs/spec_map.md) and this catalog's current workflow govern.
NOTE-048 remains supporting formatting guidance; exact pretty-printing and a global
warning-free policy are not established by these cards.

### Evidence checked in this review

At base `10dfee14`, four focused host suites (`cpp_artifacts`, `project_cpp`,
`runtime_catalog`, `runtime_build_contract`) pass in
`/tmp/scpp-ch14-review-20261016/summary.json`. These prove transport, retained artifact
behavior and metadata contracts; the exported project programs were not compiled by
that host-only run. The optional-provider check also passes six build/execution scenarios in
`/tmp/scpp-ch14-runtime-build-20261016/observations.json` and `logs/`: cold/warm,
body edit, provider edit, definition/PCH invalidation and library-only relinking.
The builder successfully consumes published requirements with the source catalog
removed. These are generated executable proofs, not a native compiler rebuild.
Historical CH08 checkpoints are recorded in the roadmap; their old temporary evidence
is not present in this workspace and was not rerun as a full acceptance sweep.
That reconciliation did not claim a native compiler rebuild; the closeout gate above
now supplies focused native evidence.

### Remaining work

1. NOTE-024 is implemented by [CH14-EMIT-01](#first-slice) below. The requested native
   closeout gate above now passes; its coverage remains explicitly bounded.
2. Source-origin/category diagnostics remain **CH13-DIAG-01**, already recorded in
   [Chapter 15](15_blocking_debt.md#chapter-13-deferred-work). Do not hide that gap by
   treating an arbitrary C++ diagnostic as a complete source diagnostic.
3. Broader runtime/template incomplete-type contracts, all optional-module/toolchain
   combinations, and a current-build/run reader remain extensions of their existing
   owners. The native guide explicitly distinguishes a historical executable path
   from a checked freshness API. None is required merely to reproduce legacy file pairs.
4. Automatic runtime refresh, native validation cost, broader toolchain completeness,
   parallel native actions and cache garbage collection retain their existing CH15 or
   native-guide deferrals. LLVM remains parked. Formatting is supporting work, not a
   reason to silently widen this slice into a generator rewrite.

### First slice

**CH14-EMIT-01 implemented within its agreed bounds — 2026-10-16.**

The shared `CppFixtureCompiler` and snippet runtime compilation now enable `-Wshadow`.
`Native_CPP_Build` includes it in its common compilation/PCH flags. Existing ordered
argument keys invalidate prior actions naturally; no cache schema change or deletion
is needed. Successful compile/link stderr is returned in invocation-local
`native_cpp_result::diagnostics` and delivered by the build CLI on stderr, leaving its
JSON stdout intact. Cache hits do not replay historical warnings. Failed actions retain
the existing exception path. This is raw compiler diagnostic delivery, not CH13-DIAG-01
source-location enrichment.

Legal source shadowing remains accepted. The added project cases execute qualified
cross-file calls to a source function named `strlen`, source type/callable headers,
arrow parameter shadowing, closure capture, explicit block-local shadowing, foreach
key shadowing and catch shadowing. Foreach values retain the existing assignment
semantics; this work does not change them into fresh loop-local bindings. Reviewed
Clang shadow warnings identify the intended block, key and catch declarations. A
pre-existing runtime explicit-instantiation warning also remains. No naming or lookup
fault required a backend rename, and no warning is promoted to an error.

Evidence at base `f0d6dd60` plus this slice:

- `/tmp/scpp-ch14-emit-artifacts-04/summary.json`: five host suites, 16 unmodified
  artifact bundles compiled/linked/run both with and without PCH (32 executions),
  34 standalone header checks without PCH, three Clang transitive-dependency checks,
  relocation and missing-entry rejection. The editable probe roundtrip assertion now
  compares its actual identity/path/source-byte contract, excluding publication-only
  include metadata; production bundles are compiled intact.
- `/tmp/scpp-ch14-emit-incremental-02/summary.json` and `observations.json`: ten
  native build/execution scenarios covering cold/warm, body/default/signature/layout,
  insertion/deletion, relocation and compiler identity. Warm publication preserves
  bytes/inode/mtime. Body edits rebuild only their implementation unit and retain PCH.
  The compiler proxy verifies `-Wshadow` on scan/PCH/object paths; successful-build
  stderr must contain the intentionally legal foreach-key shadow diagnostic.
- `/tmp/scpp-ch14-emit-flag-transition-01/summary.json`: the prior review's real
  no-`-Wshadow` optional-provider cache rebuilds three objects, PCH and executable
  after the flag is added, then reuses everything on a second build. Both executions
  retain exit 12. This local transition check supplements the retained runners.

The shared runner used PHP CLI and its bounded task pool. These proofs compile
**generated programs**, not the compiler itself. No compiler PHS/native rebuild,
full chapter closeout or warning-free certification is claimed.

Non-goals remain new language/runtime APIs, global symbol renaming, broad warning
cleanup, formatting, structured source diagnostics, template dependency optimization,
new run orchestration, legacy generator changes, STAN and LLVM. The native closeout
above completes the chapter within these bounds; extensions need their own agreed slice.

## Progress

### Early output slice — 2026-10-09

`echo` now has one ordered-operand statement node. Shared body preparation admits
the [bounded runtime output domain](../../../../specs/output_v0_2.md); C++ emits
sequential `php::echo_one` calls with its explicit header dependency. No implicit
newline or eager multi-argument call is introduced. Unsupported operands reject
before generation. This closes the test-enablement output gap, not all CH14 cards.
`tests/echo.php` checks output bytes, side-effect ordering, methods/closures,
wrapper states, rejection and incremental repair/fresh agreement. Run it with
`python3 compiler/my-try/tests/records.py --suite echo --no-pch --results FRESH`.
The shared generated-program runners check optional expected stdout as well as exit
status. Native compiler rebuilding remains a separate requested checkpoint.

Edit these rows as work proceeds. Imported source support is recorded below, independently of this progress. `owner-linked` means existing implementation/tests
are identified above, not that every imported example was recompiled in this review.
Governance rows do not require frontend/backend implementation.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [CLASS-SPLIT-001](#class-split-001) | superseded-bounded | `class A { function f(): int { return 1; } }` | owner-linked | owner-linked | deferred | Declaration/body split exists; artifact identity is per type/callable plus source implementation group, not one pair per class. |
| [GEN-QUAL-001](#gen-qual-001) | superseded | `$x vector<string> = ["a"]; echo $x[0];` | owner-linked | owner-linked | deferred | The legacy rooted-name ban does not govern v0.2; backend-owned names and explicit runtime qualification are already emitted. |
| [RUNTIME-REF-001](#runtime-ref-001) | implemented-bounded | `function inspect(const shared<Box> &$x): void {}` | owner-linked | owner-linked | deferred | References alias existing handle storage; no extra ownership wrapper. Reference/lifetime restrictions remain CH07. |
| [EMIT-FILE-001](#emit-file-001) | superseded-bounded | `input.phs` | owner-linked | owner-linked | deferred | One-input/one-pair is replaced by the common partitioned artifact program, including single-source input. |
| [EMIT-FILE-002](#emit-file-002) | superseded-bounded | generated header/source for one PHP++ input file | owner-linked | owner-linked | deferred | Explicit artifact includes and a separate runtime-only PCH replace reliance on a project umbrella header. |
| [EMIT-FILE-003](#emit-file-003) | reconciled-conservative | `shared_p<B> b;` in a header declaration | owner-linked | owner-linked | deferred | Bare-record name-only requirements use forward declarations; template/handle payloads remain conservatively complete. |
| [EXPR-EMIT-001](#expr-emit-001) | superseded | `$x = ($a + 1) * f($b);` | owner-linked | owner-linked | deferred | Prepared typed operations and ordered expression emission replace type-blind structural lowering. |
| [META-INTENT-001](#meta-intent-001) | reconciled-bounded | `function f(A $arg) {}` and `function f(?A $arg) {}` | owner-linked | owner-linked | deferred | Nullable handle spelling normalizes through type metadata; this is not a deferred non-null enforcement promise. |
| [NOTE-001](#note-001) | reconciled | — (policy/provenance) | owner-linked | owner-linked | deferred | Historical catalog preamble; v0.2 container defaults and authority are owned by CH06 and the spec map. |
| [NOTE-002](#note-002) | superseded-bounded | — (policy/provenance) | owner-linked | owner-linked | deferred | Type-aware preparation, separated boxed families and prepared places replace the imported composite generator summary. |
| [NOTE-003](#note-003) | reconciled | — (policy/provenance) | owner-linked | owner-linked | deferred | Historical catalog preamble and v1 bridge policy; v0.2 separated-family amendment takes precedence. |
| [NOTE-004](#note-004) | reconciled | — (policy/provenance) | owner-linked | owner-linked | deferred | Coverage/normalization guidance, not a missing compiler feature. |
| [NOTE-007](#note-007) | reconciled | — (policy/provenance) | owner-linked | owner-linked | deferred | Imported authority labels do not outrank top-level semantics or the v0.2 amendments. |
| [NOTE-008](#note-008) | superseded | — (policy/provenance) | owner-linked | owner-linked | deferred | Type-blind responsibility boundary belongs to the legacy generator, not my-try shared preparation. |
| [NOTE-009](#note-009) | reconciled-bounded | — (policy/provenance) | owner-linked | owner-linked | deferred | Registered runtime types, ownership and bindings exist; no claim of every historical helper or runtime API. |
| [NOTE-014](#note-014) | reconciled-bounded | — (policy/provenance) | owner-linked | owner-linked | deferred | PHP-semantic adapters and registered runtime bindings already select runtime behavior; constants are settled metadata. |
| [NOTE-020](#note-020) | superseded-bounded | — (policy/provenance) | owner-linked | owner-linked | deferred | Historical rejection list is not the current feature matrix; CH02/03/06/07 own the implemented operations and deferrals. |
| [NOTE-023](#note-023) | reconciled-bounded | — (policy/provenance) | owner-linked | owner-linked | deferred | Bounded numeric division/comparisons/spaceship and switch contracts already supersede the old incompatibility list. |
| [NOTE-024](#note-024) | implemented-bounded | — (policy/provenance) | owner-linked | proved | deferred | `-Wshadow` enabled in fixture/native compilation; artifact, lookup, diagnostics and incremental proofs pass; legal shadowing preserved. |
| [NOTE-026](#note-026) | superseded-bounded | — (policy/provenance) | owner-linked | owner-linked | deferred | Partitioned declarations, precise dependencies and source implementation groups replace the imported file-pair policy. |
| [NOTE-028](#note-028) | reconciled-bounded | — (policy/provenance) | owner-linked | owner-linked | deferred | CH08 owns canonical namespace bodies and selected entry; CH05/09 own handle normalization, not deferred intent metadata. |
| [NOTE-030](#note-030) | reconciled | — (policy/provenance) | owner-linked | owner-linked | deferred | Use specs/spec_map.md authority order; imported catalog status cannot define semantics. |
| [NOTE-032](#note-032) | superseded-bounded | — (policy/provenance) | owner-linked | owner-linked | deferred | Runtime behavior is delegated, but type/operation admission is checked in preparation; arbitrary stream/operator availability is not assumed. |
| [NOTE-041](#note-041) | reconciled-bounded | — (policy/provenance) | owner-linked | owner-linked | deferred | Prepared probes/reset/identity and settled constants already use their bounded owners; no PHP get_defined_constants discovery. |
| [NOTE-042](#note-042) | superseded | — (policy/provenance) | owner-linked | owner-linked | deferred | Explicit runtime bindings and collision-safe generated names replace the relative-qualification-only convention. |
| [NOTE-046](#note-046) | implemented-bounded | — (policy/provenance) | owner-linked | owner-linked | deferred | Ordered echo is implemented; one ordered-operand node replaces the legacy exporter sibling representation. |
| [NOTE-048](#note-048) | guidance | — (policy/provenance) | owner-linked | owner-linked | deferred | Readable compact output remains a style goal; generated whitespace is not a language feature or warning-clean guarantee. |
| [NOTE-049](#note-049) | reconciled-bounded | — (policy/provenance) | owner-linked | owner-linked | deferred | Integer division and admitted comparison domains are defined and tested; full PHP coercive arithmetic is not promised. |
| [NOTE-057](#note-057) | implemented-bounded | — (policy/provenance) | owner-linked | owner-linked | deferred | Native build selects SCPP_LANGUAGE_TARGET_PHP=1; runtime owns key normalization. |
| [NOTE-061](#note-061) | superseded-bounded | — (policy/provenance) | owner-linked | owner-linked | deferred | Runtime-owned module catalogs replace legacy profile symbol lists as the my-try registration authority. |
| [NOTE-062](#note-062) | superseded-bounded | — (policy/provenance) | owner-linked | owner-linked | deferred | Resolved source/runtime identities and separate C++ bindings replace tail-name rewriting; selected-module exposure is checked. |
| [NOTE-064](#note-064) | reconciled | — (policy/provenance) | owner-linked | owner-linked | deferred | Current catalog workflow groups bounded contracts and proof owners; no implementation needed for historical workflow prose. |
## CLASS-SPLIT-001

**v0.2 reconciliation:** Declaration/body split exists; artifact identity is per type/callable plus source implementation group, not one pair per class. See [current ownership](#artifacts). Imported content below is historical.

Both imported versions are preserved for provenance. The current partitioned artifact model above supersedes their per-class file-pair wording.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:23](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
class A { function f(): int { return 1; } }
```

**Existing C++ lowering / result**

`A.hpp` contains declarations; `A.cpp` contains out-of-line bodies

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** class is user-defined PHP class

**Normalized pattern:** `class <name> { <members> }`

**General rule:** Each PHP class lowers to two generated files: header for declarations and source for method / constructor / destructor bodies.

**Diagnostics:** Error if bodies are emitted inline contrary to the split policy.

**Notes:** This is the approved class generation model.

### Imported version 2

**Source:** [generators/php/specs/rules_catalog.md:186](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
class A { function f(): int { return 1; } }
```

**Existing C++ lowering / result**

`A.hpp` contains declarations; `A.cpp` contains out-of-line bodies

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** class is a user-defined PHP class

**Normalized pattern:** `class <name> { <members> }`

**General rule:** Each user-defined PHP class lowers to two generated files: header for declarations and source for constructor, destructor, and method bodies.

**Diagnostics:** Error if bodies are emitted inline contrary to the split policy.

**Notes:** Preserves the approved header/source class model.


## GEN-QUAL-001

**v0.2 reconciliation:** The legacy rooted-name ban does not govern v0.2; backend-owned names and explicit runtime qualification are already emitted. See [current ownership](#qualification). Imported content below is historical.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:25](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$x = ["a"]; echo $x[0];
```

**Existing C++ lowering / result**

```cpp
mixed_t x = table_(table_item_(string_t("a"))); echo x.get(static_cast<int_t>(0));
```

**Category:** Generator

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** emitted code is inside a generated `.cpp` namespace block

**Normalized pattern:** `runtime/helper emission inside generated source`

**General rule:** Generator MUST NOT emit fully-qualified names like `::scpp` or `::scpp::php` inside generated expression/type code because the generated source block already injects `using namespace ::scpp;``.

**Diagnostics:** Error if generated expression/type code contains rooted `::scpp` / `::scpp::php` helper references.

**Notes:** Allowed exceptions are the generated using-directives themselves and explicit import-lowering forms such as `using ::scpp::A::B::f;`.


## RUNTIME-REF-001

**v0.2 reconciliation:** References alias existing handle storage; no extra ownership wrapper. Reference/lifetime restrictions remain CH07. See [current ownership](#runtime). Imported content below is historical.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:361](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

future Prism++ `&x` where `x` is `shared_p<T>`, `unique_p<T>`, or `weak_p<T>`

**Existing C++ lowering / result**

native handle reference lowering such as `shared_p<T>&`

**Category:** Runtime

**Rule kind:** general rule

**Source support status:** supported

**Preconditions:** operand is already handle-like

**Normalized pattern:** `reference to handle-like wrapper`

**General rule:** An explicit reference applied to a handle-like wrapper must lower to a native reference over that existing handle category instead of creating an extra wrapper layer.

**Diagnostics:** Error if generation introduces wrapper-of-wrapper pointer/reference layering or emits `&&` / `*` in a type definition.

**Notes:** This is a runtime/generation contract for the native-reference lowering path.


## EMIT-FILE-001

**v0.2 reconciliation:** One-input/one-pair is replaced by the common partitioned artifact program, including single-source input. See [current ownership](#artifacts). Imported content below is historical.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:362](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
input.phs
```

**Existing C++ lowering / result**

`input.hpp` + `input.cpp`

**Category:** Emission

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** one PHP++ input file is being transpiled

**Normalized pattern:** one-input-file emission pair

**General rule:** One PHP++ input file generates one header file and one source file. Emission is organized per input file, not per class.

**Diagnostics:** Error only if the configured output pair cannot be materialized.

**Notes:** Keeps file emission simple and predictable.


## EMIT-FILE-002

**v0.2 reconciliation:** Explicit artifact includes and a separate runtime-only PCH replace reliance on a project umbrella header. See [current ownership](#artifacts). Imported content below is historical.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:363](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

generated header/source for one PHP++ input file

**Existing C++ lowering / result**

broad runtime/project header included

**Category:** Emission

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** normal file emission

**Normalized pattern:** broad runtime include

**General rule:** Generated files may always include a broad runtime/project header; include minimization is not required.

**Diagnostics:** No generator-side optimization pass is required.

**Notes:** Clean architecture is preferred over include micro-optimization.


## EMIT-FILE-003

**v0.2 reconciliation:** Bare-record name-only requirements use forward declarations; template/handle payloads remain conservatively complete. See [current ownership](#artifacts). Imported content below is historical.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:364](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

`shared_p<B> b;` in a header declaration

**Existing C++ lowering / result**

`class B;` may be emitted instead of a heavier include

**Category:** Emission

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** the referenced type is used only in a trivial obvious declaration context through `shared_p<T>`

**Normalized pattern:** trivial forward declaration case

**General rule:** Forward declarations may be used only in trivial obvious declaration contexts; the generator must not build a dependency solver for include optimization.

**Diagnostics:** Fall back to the simpler include-based path when the case is not trivially safe.

**Notes:** This keeps dependency handling intentionally simple.


## EXPR-EMIT-001

**v0.2 reconciliation:** Prepared typed operations and ordered expression emission replace type-blind structural lowering. See [current ownership](#semantics). Imported content below is historical.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:365](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$x = ($a + 1) * f($b);
```

**Existing C++ lowering / result**

structural C++ emission preserving grouping

**Category:** Expression

**Rule kind:** general rule

**Source support status:** supported

**Preconditions:** source form is otherwise supported

**Normalized pattern:** structural expression emission

**General rule:** Expression lowering remains structural. The generator must not try to behave like a semantic expression compiler.

**Diagnostics:** Reject only when a generation rule explicitly marks the source form unsupported.

**Notes:** Runtime/operator behavior is delegated.


## META-INTENT-001

**v0.2 reconciliation:** Nullable handle spelling normalizes through type metadata; this is not a deferred non-null enforcement promise. See [current ownership](#runtime). Imported content below is historical.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:366](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

`function f(A $arg) {}` and `function f(?A $arg) {}`

**Existing C++ lowering / result**

same emitted handle type, different recorded intent metadata

**Category:** Metadata

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** object type nullability intent appears in source

**Normalized pattern:** deferred intent metadata

**General rule:** Source-level intent that is not yet enforced at generation time may be recorded as metadata without changing the current emitted C++ form.

**Diagnostics:** No emitted type difference is required while enforcement remains deferred.

**Notes:** Example: object nullability intent may be recorded for future runtime check injection.


## NOTE-001

**Source:** [generators/php/specs/catalog.md:1](../../../../generators/php/specs/catalog.md)

**v0.2 reconciliation:** Historical catalog preamble; v0.2 container defaults and authority are owned by CH06 and the spec map. See [current ownership](#governance). Imported content below is historical.

> # Prism++ â€“ Rule Catalog
> Doc Status: normative
> This catalog reflects the rules approved in the current session and is aligned with the current project direction.
>
> Columns:
> - **ID**: stable catalog identifier
> - **Category**: major area
> - **Rule kind**: generation / rejection / generation-with-precondition
> - **PHP input example**: representative source form
> - **Expected Prism++ / Result**: expected lowering or rejection
> - **Status**: supported / rejected / supported-with-precondition
> - **Preconditions**: explicit conditions required for the rule
> - **Normalized pattern**: abstract source pattern
> - **General rule**: canonical rule statement
> - **Diagnostics**: generator behavior on failure
> - **Notes**: clarifications
>
> - Untyped PHP array literals are a deliberate declaration-time exception: first assignment must declare them as `mixed_t`, not `auto`, so the resulting local uses the fat-value runtime surface immediately.

## NOTE-002

**Source:** [generators/php/specs/catalog.md:107](../../../../generators/php/specs/catalog.md)

**v0.2 reconciliation:** Type-aware preparation, separated boxed families and prepared places replace the imported composite generator summary. See [current ownership](#semantics). Imported content below is historical.

> ## Notes
>
> - This catalog intentionally reflects only the rules explicitly approved in the current session.
> - The generator remains local and syntactic: no cross-file semantic checking, no hierarchy validation, no inference of overrides or virtual dispatch beyond explicit source cues.
> - Class generation assumes a handle-like object model for user PHP classes: instance access uses `->`, and object creation uses `create<T>(...)`.
>
>
> ## Array rules added in the current stage
>
> The current catalog now keeps generalized array rules instead of one row per trivial permutation.
>
> Core rule families:
> - untyped literals lower through `table_`, `table_item_`, and `table_kv_`, producing `mixed_t`-based PHP-array values
> - typed `vector<T>` literals lower to `vector_t<T>{...}`
> - typed `hash<T>` literals lower to typed `hash_t<T>` construction/update code
> - PHP array reads lower directly to `operator[]` on `hash_t` / `mixed_t`
> - keyed writes lower to direct `operator[]` assignment
> - append writes lower to `append(...)`; simple right-hand sides inline directly, while non-trivial right-hand sides may spill into a temporary
> - `unset($a[k])` lowers to `remove(k)` and remains a no-op on missing keys
> - `isset($a[k])` lowers through the runtime `isset(...)` helper with null-sensitive behavior
> - `empty($a[k])` lowers through the runtime `empty(...)` helper for the resulting value under the reduced Prism++ emptiness rule
> - the normative cross-runtime contract is defined in `specs/count_empty_isset_contract.md`
>
>
> ## Nested table dim support
>
> - Nested table dim reads chain through `get(...)` / `_find_val(...)` so `$x["inner"][0]` stays non-mutating on the read path.
> - Nested table dim writes keep the full lvalue chain on mutating `operator[]` access, so `$x[0]["name"] = "first";` does not route intermediate segments through `get(...)`.
> - Nested append on a table-valued slot is supported through chained mutating access plus `append(...)` on `mixed_t` / `hash_t<mixed_t>`; the prefix of a nested append target stays on the mutating path (for example `$x["users"][] = $v;` â†’ `x[string_t("users")].append(...)`), while read-only nested access lowers through `get(...)` / `_find_val(...)`.
> - Table-valued assignments into table slots now use direct `mixed_t` assignment through the returned `operator[]` reference.
>
> ## Assignment-expression lambda fallback
>
> - Default rule: do not emit a helper lambda for ordinary assignment statements or simple assignment expressions.
> - Fallback rule: emit a helper lambda only in complex expression contexts where the generator must preserve PHP assignment-value semantics while also guaranteeing single evaluation, especially append expressions or larger composed expressions.
>
> - `FUNC-ARG-001`: direct DIM call arguments use the direct slot path `[]`; computed call arguments keep the normal expression/read path. By-value array args rely on runtime detach-on-write rather than generator-side `table_copy(...)`.
> - `REF-BIND-001`: `=&` binding from a direct DIM slot uses the mutable slot path `[]`, not the read path `.get(...)`, so the binding targets the real slot storage.
>
> ## Historical note â€” typed scalar by-reference proxy lowering
>
> Legacy helper/proxy infrastructure may still exist in the runtime, but it is not part of the supported safe subset. The current design direction is the native-reference safety rule documented in `specs/native_reference_safety.md`.

## NOTE-003

**Source:** [generators/php/specs/rules_catalog.md:1](../../../../generators/php/specs/rules_catalog.md)

**v0.2 reconciliation:** Historical catalog preamble and v1 bridge policy; v0.2 separated-family amendment takes precedence. See [current ownership](#governance). Imported content below is historical.

> Doc Status: normative
>
>
> See `../../specs/spec_map.md` for document hierarchy, authority, and v1 conflict-resolution rules.
>
> Priority note for dynamic typed-destination bridging:
> - The authoritative v1 rule is `../../specs/dynamic_types.md` sections 1.2 and 1.3.
> - If a row here reaches a typed destination from `mixed_t`, current v1 behavior must preserve that bridge until generator parity exists for explicit cast insertion.
>
> # Prism++ â€“ Rule Catalog (Rebuild)
>
> This catalog is pre-seeded with common PHP constructs.
> The rows below were updated with the decisions made so far.
>
> Columns that remain intentionally incomplete for future collaborative work:
> - `Expected Prism++ / Result` for untouched rows
> - `Status` for untouched rows
> - `Preconditions` for untouched rows
> - `General rule` for untouched rows
> - `Diagnostics` for untouched rows
> - `Notes` for untouched rows

## NOTE-004

**Source:** [generators/php/specs/rules_catalog.md:346](../../../../generators/php/specs/rules_catalog.md)

**v0.2 reconciliation:** Coverage/normalization guidance, not a missing compiler feature. See [current ownership](#governance). Imported content below is historical.

> ## Notes
>
> - This file collapses old catalog permutations into normalized patterns.
> - The rows above are updated only where we made explicit decisions in the session.
> - Untouched rows remain as seed placeholders for future completion.

## NOTE-007

**Source:** [generators/php/specs/rules.md:1](../../../../generators/php/specs/rules.md)

**v0.2 reconciliation:** Imported authority labels do not outrank top-level semantics or the v0.2 amendments. See [current ownership](#governance). Imported content below is historical.

> Doc Status: normative
>
>
> See `../../specs/spec_map.md` for document hierarchy, authority, and v1 conflict-resolution rules.
>
> # Prism++ â€“ General Rules (Authoritative, Normalized)
>
> > Transitional implementation note: see `../../specs/mixed_boundary_transitional.md`.
>
> This document is the single source of truth for the supported subset.
>
> ---

## NOTE-008

**Source:** [generators/php/specs/rules.md:14](../../../../generators/php/specs/rules.md)

**v0.2 reconciliation:** Type-blind responsibility boundary belongs to the legacy generator, not my-try shared preparation. See [current ownership](#semantics). Imported content below is historical.

> ## 0. Generator Responsibility Boundary
>
> The S2S generator is a deterministic structured code generator, not a semantic compiler.
>
> It performs only the checks required to emit configured C++ output reliably. Symbol resolution, type validation, inheritance validation, override validation, and other semantic compile-time checks are delegated to the C++ compiler unless a generation rule explicitly requires a local structural check.
>
> The generator must prefer deterministic syntactic lowering over semantic interpretation. If a supported source form can be lowered locally, it should be emitted. If the resulting C++ is semantically invalid, that failure belongs to the C++ compiler unless the generation rules state otherwise.
>
> ---

## NOTE-009

**Source:** [generators/php/specs/rules.md:24](../../../../generators/php/specs/rules.md)

**v0.2 reconciliation:** Registered runtime types, ownership and bindings exist; no claim of every historical helper or runtime API. See [current ownership](#runtime). Imported content below is historical.

> ## 1. Runtime Contract
>
> All generated code targets the `scpp` runtime.
>
> Object construction and ownership helpers are runtime concepts. Current generation rules use `create<T>(...)` for user PHP class construction, while explicit runtime forms such as `shared(new MyClass)`, `weak($object)`, and `unique(new MyClass)` remain runtime-level constructs when they are later brought into the supported subset.
>
> ### Core Types
> - `int_t`
> - `float_t`
> - `bool_t`
> - `string_t`
> - `nullable<T>`
> - `shared_p<T>`
> - `unique_p<T>`
> - `weak_p<T>`
> - `value_p<T>`
> - `vector_t`
> - runtime `null` / `nullopt` support via the runtime helpers
>
> ### Rules
> - `string_t` uses constructor form, not `static_cast`
> - `nullable<T>` is the null carrier for nullable value types
> - object/class/interface handle types use `shared_p<T>` and are inherently nullable
> - explicit runtime handle annotations `shared<T>`, `unique<T>`, `weak<T>`, and `weakref<T>` lower directly to `shared_p<T>`, `unique_p<T>`, and `weak_p<T>`
> - `value_p<T>` is opt-in inline storage and is never the default lowering for PHP object types
> - runtime `null` is the canonical null literal for generated code where null is supported
> - null comparisons/checks must use the configured runtime helpers such as `php::is_null(...)` and `php::not_null(...)`
> - generated/frontend-facing semantic calls should follow the active PHP profile surface
> - legacy-profile generated calls target `scpp::php::*` entrypoints
> - strict-profile generated calls may lower directly to shared `scpp::*` runtime families only through symbols declared by the active strict profile registry
> - `scpp::php::*` may forward to shared `scpp::*` authorities when PHP semantics match the shared Prism++ semantics
>
> ---

## NOTE-014

**Source:** [generators/php/specs/rules.md:205](../../../../generators/php/specs/rules.md)

**v0.2 reconciliation:** PHP-semantic adapters and registered runtime bindings already select runtime behavior; constants are settled metadata. See [current ownership](#runtime). Imported content below is historical.

> ## 6. Runtime Delegation
>
> PHP-specific behavior must go through runtime helpers when required.
>
> Examples:
> - `php::isset`
> - `php::empty`
> - predefined/runtime constants through `::scpp::php` (classified from `get_defined_constants()`)
> - `php::identical`
> - `php::not_identical`
> - `scpp::pow`
> - `scpp::cmp`
>
> Current architectural rule:
> - legacy PHP-facing lowering targets `scpp::php::*`
> - strict PHP-facing lowering may target shared `scpp::*` runtime families when the active strict profile registry declares those symbols
> - language entrypoints remain the stable generator-facing surface for legacy and PHP-owned semantics
>
> ---

## NOTE-020

**Source:** [generators/php/specs/rules.md:285](../../../../generators/php/specs/rules.md)

**v0.2 reconciliation:** Historical rejection list is not the current feature matrix; CH02/03/06/07 own the implemented operations and deferrals. See [current ownership](#semantics). Imported content below is historical.

> ## 11. Rejected Features
>
> - reduced PHP array subset (see catalog rows `ARR-*`)
> - `stdClass` / object iteration
> - `foreach` by value is supported for typed `vector<T>` / `hash<T>` surfaces, for the current packed `hash_t<mixed_t>` dynamic-array surface, and for approved wrappers that delegate an iterable success payload through the runtime iterable surface
> - foreach key/value variables are always emitted as fresh loop-local variables in the generated C++; they shadow outer locals of the same PHP name inside the loop body
> - by-reference foreach is currently lowered through source-slot rewriting rather than a standalone alias local
> - value-only form synthesizes a hidden key local such as `_<value>_key_`
> - explicit-key form preserves the PHP key variable and rewrites the foreach value variable through the source slot keyed by that variable
> - this lowering is provisional and subject to future improvement
> - for boxed-array foreach over `mixed_t`, indexed loop lowering uses the generator-facing `mixed_t::size()` / `mixed_t::at(...)` surface instead of reaching through to raw table internals
> - for wrapper-carried iterable payloads, the generator remains type-blind and simply lowers against the runtime iterable surface exposed by the wrapper
> - explicit function/method reference returns require an explicit declared PHP return type and must still satisfy the native-reference safety rule; dynamic interior slot/property returns are not allowed
> - `include`, `include_once`, and `require`
> - `and` / `or` / `xor`
> - untyped parameters
> - function or method overloading
> - untyped raw `null` assignment
>
> ---

## NOTE-023

**Source:** [generators/php/specs/rules.md:364](../../../../generators/php/specs/rules.md)

**v0.2 reconciliation:** Bounded numeric division/comparisons/spaceship and switch contracts already supersede the old incompatibility list. See [current ownership](#semantics). Imported content below is historical.

> ## 12. Incompatibilities
>
> See `incompatibilities.md`.
>
> Known items include:
> - division semantics
> - `switch` behavior differences
> - spaceship operator
>
> ---

## NOTE-024

**Source:** [generators/php/specs/rules.md:375](../../../../generators/php/specs/rules.md)

**v0.2 reconciliation:** `-Wshadow` enabled in fixture/native compilation; artifact, lookup, diagnostics and incremental proofs pass; legal shadowing preserved. See [current ownership](#first-slice). Imported content below is historical.

> ## 13. Compilation Constraints
>
> All generated C++ code must compile with `-Wshadow` enabled.
>
> ### Implications
> - generated symbol access must remain explicit and unambiguous under C++ shadowing semantics
> - generation must not rely on unstable lookup behavior
> - use-before-declare remains an error
>
> ---

## NOTE-026

**Source:** [generators/php/specs/rules.md:446](../../../../generators/php/specs/rules.md)

**v0.2 reconciliation:** Partitioned declarations, precise dependencies and source implementation groups replace the imported file-pair policy. See [current ownership](#artifacts). Imported content below is historical.

> ## 15. File Emission Model
>
> - one PHP++ input file generates one `.hpp` file and one `.cpp` file
> - generation is organized per input file, not per class
> - the generated header contains declarations and the generated source contains out-of-line definitions
> - generated files may always include a broad runtime/project header
> - include minimization is not required for the generator
>
> ### Forward Declarations
> - forward declarations may be used only in trivial obvious cases where a class type is referenced through `shared_p<T>` in declarations
> - the generator must not build a dependency solver for include optimization
> - if a case is not trivially safe for forward declaration, the generator may use the simpler include-based path instead

## NOTE-028

**Source:** [generators/php/specs/rules.md:468](../../../../generators/php/specs/rules.md)

**v0.2 reconciliation:** CH08 owns canonical namespace bodies and selected entry; CH05/09 own handle normalization, not deferred intent metadata. See [current ownership](#semantics). Imported content below is historical.

> ## 17. Deferred Intent Metadata
>
> - source-level intent that is not yet enforced at generation time may be recorded as metadata
> - this includes, for example, non-null object intent where `T` and `?T` currently emit the same object-handle type
> - recording intent metadata must not change the current emitted C++ form unless a generation rule explicitly requires it
> - namespace-scope assignments that participate in executable bootstrap code are allowed and are lowered inside the synthetic namespace execution function
> - namespace-scope static variables remain rejected
>
> ### 14.7 Namespace-Scope Executable Code
> Executable statements must not be emitted directly at namespace scope.
>
> Executable statements inside the same namespace body are consolidated into a single synthetic namespace `main()`, even when declarations appear between them.
>
> Declarations remain at namespace scope and do not split execution into separate synthetic functions.
>
> Source order of executable statements must be preserved when consolidating them into the synthetic namespace `main()`.
>
> This consolidation is valid only when all executable statements belong to the same namespace body and can be merged into a single generated code block for that namespace.
>
> If execution reaches the end of the synthetic namespace `main()` without an explicit return, the generator must append `return 0;`.
>
> The generated global `int main()` must return the result of the selected synthetic namespace `main()` call.
>
> ### 14.8 Cross-Namespace Execution Restriction
> Executable statement consolidation applies only within a single namespace body.
>
> Executable code in a parent namespace and executable code in a nested namespace create different execution flows and are not allowed together.
>
> A nested namespace may appear inside a parent namespace execution region only when the nested namespace contributes declarations only.
>
> ### 14.9 Multiple Namespace Blocks
> Multiple braced namespace blocks in one file are supported when they lower into ordinary declarations and, at most, one selected synthetic execution entry point.
>
> Supported forms include:
> - `namespace A { ... } namespace B { ... }`
> - `namespace A\B { ... } namespace { ... }`
> - multiple braced namespace blocks containing declarations only
> - multiple braced namespace blocks followed by a braced global namespace block `namespace { ... }`
>
> Lowering rules:
> - each PHP namespace block lowers independently under the `scpp::...` root
> - a braced global namespace block `namespace { ... }` lowers to `namespace scpp { ... }`
> - rooted calls from the global block must lower without an empty namespace segment, for example `::scpp::__scpp_main()`
> - declarations remain in their own generated namespace blocks and are not merged by name just because they appear in the same source file
>
> Restriction:
> - executable-statement consolidation still applies only within one namespace body at a time
> - cross-namespace execution merging remains forbidden
> - this section currently covers braced namespace blocks; semicolon-form multi-block behavior remains governed by the existing execution restrictions and file-structure rules
>
> ---

## NOTE-030

**Source:** [generators/php/specs/rules.md:562](../../../../generators/php/specs/rules.md)

**v0.2 reconciliation:** Use specs/spec_map.md authority order; imported catalog status cannot define semantics. See [current ownership](#governance). Imported content below is historical.

> ## 1. Scope and precedence
>
> - General rules in this document have precedence over per-example decisions.
> - Concrete examples may be corrected to comply with these rules.
> - The catalog is for coverage and traceability; this file defines the normative behavior.

## NOTE-032

**Source:** [generators/php/specs/rules.md:578](../../../../generators/php/specs/rules.md)

**v0.2 reconciliation:** Runtime behavior is delegated, but type/operation admission is checked in preparation; arbitrary stream/operator availability is not assumed. See [current ownership](#runtime). Imported content below is historical.

> ## 3. Runtime assumptions
>
> ### 3.1 Provided runtime types
> Primitive-like types:
> - `int_t` -> signed 8-byte integer
> - `bool_t` -> C++ `bool`
> - `float_t` -> signed 8-byte floating point
>
> Wrapper / heavy types:
> - `string_t` -> wrapper around `std::string`
> - `vector_t` -> wrapper around `std::vector`
>
> Null support:
> - `null_t` -> custom type
> - `null` -> `inline constexpr null_t null {};`
>
> Nullable support:
> - `nullable<T>`
> - `shared_p<T>`
> - `unique_p<T>`
> - `weak_p<T>`
> - `value_p<T>`
> - `vector_t`
> - runtime `null` / `nullopt` support via the runtime helpers
>
> ### 3.2 Provided runtime helpers
> - `create<T>()`
> - `shared<T>()`
> - `weak<T>()`
> - `unique<T>()`
>
> ### 3.3 Runtime boundary
> The generator does **not** validate whether operator overloads or conversions exist in the runtime.
>
> If generated C++ later fails because of:
> - operator overload gaps
> - unsupported runtime conversions
> - stream operator gaps
> - missing runtime helpers
>
> that is outside the current generator scope and may fail at C++ compile time.
>
> ### 3.4 Allowed assumed runtime/operator surface
> The generator is allowed to emit code that assumes support for:
> - arithmetic operators
> - comparison operators
> - logical operators
> - `std::cout <<`
> - string concatenation through `+`
> - comparisons against `null`

## NOTE-041

**Source:** [generators/php/specs/rules.md:842](../../../../generators/php/specs/rules.md)

**v0.2 reconciliation:** Prepared probes/reset/identity and settled constants already use their bounded owners; no PHP get_defined_constants discovery. See [current ownership](#runtime). Imported content below is historical.

> ## 12. PHP runtime boundary rules
>
> These PHP semantics must go through the `php::` layer:
>
> - `unset($a)` -> `php::unset(a);` only when the lowered target type is nullable / pointer-like / handle-like and supports an empty state
> - for non-nullable value/container-like targets, use `clean($a)` -> `php::clean(a);` as the current project direction instead of lowering to `php::unset(a);`
> - `isset($b)` -> `php::isset(b)`
> - when the exporter normalizes multi-operand forms, generation must follow the exported tree instead of reconstructing surface syntax
> - `empty($b)` -> `php::empty(b)`
> - strict equality `===` -> `php::identical(...)`
> - strict inequality `!==` -> `php::not_identical(...)`
> - both helpers return `bool_t`, not native `bool`, because they are PHP-semantic runtime operations
> - predefined/runtime constants discovered from `get_defined_constants()` -> unqualified `...` inside generated source namespace blocks
> - user-defined non-class constants -> generated user namespace path (no `::scpp::php` remapping)

## NOTE-042

**Source:** [generators/php/specs/rules.md:857](../../../../generators/php/specs/rules.md)

**v0.2 reconciliation:** Explicit runtime bindings and collision-safe generated names replace the relative-qualification-only convention. See [current ownership](#qualification). Imported content below is historical.

> ## 13. Prism++ runtime/helper boundary rules
>
> Helpers that are not plain PHP semantic primitives may go through the `scpp::` layer.
>
> Current accepted case:
> - exponentiation `**` -> `scpp::pow(...)`
>
> ### 13.1 Rooted runtime qualification ban
> Generator MUST NOT emit fully-qualified names like `::scpp` or `::scpp::php` in generated expression/type code because generated source namespace blocks already inject:
> - `using namespace ::scpp;`
>
> Examples:
> ```cpp
> table_(table_item_(string_t("x")))
> expect_array_argument(x, false, "x")
> create<MyClass>()
> class_t<decltype(obj)>::make()
> A::B::LIMIT
> ```
>
> Never emit these rooted runtime/helper forms inside generated expression/type code:
> ```cpp
> ::scpp::table_(...)
> php::expect_array_argument(...)
> ::scpp::create<MyClass>()
> ::scpp::class_t<decltype(obj)>::make()
> ::scpp::A::B::LIMIT
> ```
>
> Allowed exception:
> - generated using-directives/import-lowering lines may still use rooted forms, for example `using namespace ::scpp;` or `using ::scpp::A::B::f;`
>
> Example:
> ```cpp
> auto a = scpp::pow(static_cast<int_t>(2), static_cast<int_t>(3));
> ```

## NOTE-046

**Source:** [generators/php/specs/rules.md:1055](../../../../generators/php/specs/rules.md)

**v0.2 reconciliation:** Ordered echo is implemented; one ordered-operand node replaces the legacy exporter sibling representation. See [current ownership](#runtime). Imported content below is historical.

> ## 17. Output rules
>
> - generated code currently routes output through direct `echo_one(...)` calls
> - lowering must preserve the exporter shape while preserving left-to-right echo operand evaluation
> - for the current exporter:
> 	- each `AST_ECHO` node carries one operand
> 	- `echo a, b, c;` is exported as multiple sibling `AST_ECHO` nodes
> 	- adjacent echo nodes from the same lowered statement stream are emitted as sequential `echo_one(...)` calls
> - each emitted operand is evaluated and printed in statement order
>
> Examples:
> ```cpp
> echo_one(a);
> echo_one(b);
> echo_one(c);
> ```

## NOTE-048

**Source:** [generators/php/specs/rules.md:1079](../../../../generators/php/specs/rules.md)

**v0.2 reconciliation:** Readable compact output remains a style goal; generated whitespace is not a language feature or warning-clean guarantee. See [current ownership](#governance). Imported content below is historical.

> ## 19. Formatting
>
> Current target:
> - compact
> - readable
> - tabs for indentation

## NOTE-049

**Source:** [generators/php/specs/rules.md:1086](../../../../generators/php/specs/rules.md)

**v0.2 reconciliation:** Integer division and admitted comparison domains are defined and tested; full PHP coercive arithmetic is not promised. See [current ownership](#semantics). Imported content below is historical.

> ## 20. Notes on known open incompatibilities
>
> These are known and not yet fully resolved in rules:
>
> ### 20.1 Division semantics
> PHP `/` produces a floating-point result; C++ `/` depends on operand types.
> A later normalization/promotion rule is required.
>
> ### 20.2 Loose comparison semantics
> PHP `==` and `!=` are not fully equivalent to native C++ `==` and `!=`.
> A later decision must either:
> - route them through runtime helpers, or
> - formally restrict supported operand/type combinations.

## NOTE-057

**Source:** [generators/php/specs/rules.md:1257](../../../../generators/php/specs/rules.md)

**v0.2 reconciliation:** Native build selects SCPP_LANGUAGE_TARGET_PHP=1; runtime owns key normalization. See [current ownership](#runtime). Imported content below is historical.

> ## Runtime language target
>
> PHP-target array-key normalization is a runtime concern. The generator must not duplicate numeric-string key normalization logic. Builds used by the project test harness are expected to compile the runtime and generated samples with `-DSCPP_LANGUAGE_TARGET_PHP=1`.

## NOTE-061

**Source:** [generators/php/specs/rules.md:1282](../../../../generators/php/specs/rules.md)

**v0.2 reconciliation:** Runtime-owned module catalogs replace legacy profile symbol lists as the my-try registration authority. See [current ownership](#runtime). Imported content below is historical.

> ## PHP runtime relative symbol registry
>
> Generator-emitted calls that are known Prism++ runtime intrinsics may be emitted through a runtime-symbol registry inside `namespace scpp { ... }`. The registry is profile-specific and is stored in `generators/php/specs/php_runtime_symbols_legacy.json` or `generators/php/specs/php_runtime_symbols_strict.json`. Entries are recorded as relative symbol paths under `scpp`, or as visible-to-target mappings relative to `scpp` for strict profile flat names such as `fs_is_file -> fs::is_file`. User-defined functions must not be rewritten through this registry when the generator has already resolved them as user declarations.
>
> Architecture note:
>
> - strict-profile direct emission to shared runtime families is allowed only for symbols declared by the active profile registry
> - the registry is the approved bridge between visible/source strict names and shared runtime-family targets

## NOTE-062

**Source:** [generators/php/specs/rules.md:1292](../../../../generators/php/specs/rules.md)

**v0.2 reconciliation:** Resolved source/runtime identities and separate C++ bindings replace tail-name rewriting; selected-module exposure is checked. See [current ownership](#runtime). Imported content below is historical.

> ## Runtime Symbol Registry (relative to scpp)
>
> Any runtime function intended to be callable from transpiled PHP code through the registry **must be registered** in the active profile file:
>
> `generators/php/specs/php_runtime_symbols_legacy.json`
>
> `generators/php/specs/php_runtime_symbols_strict.json`
>
> The S2S generator uses this registry to emit the registered relative path directly. For example:
>
>     php::function_name(...)
>
> Or, for strict flat visible names:
>
>     fs_is_file(...)  ->  fs::is_file(...)
>
> ### Precedence
> User-defined PHP functions take precedence over runtime symbols with the same name.\
> The registry is only applied when no user-defined function is resolved. Bare source calls may resolve through the registry by unique tail-name match.
>
> ### Important
> If a symbol is not present in the registry, the generator will **not** rewrite it through the runtime-symbol registry, even if it exists in the runtime.

## NOTE-064

**Source:** [generators/php/specs/catalog_rebuild_workflow.md:1](../../../../generators/php/specs/catalog_rebuild_workflow.md)

**v0.2 reconciliation:** Current catalog workflow groups bounded contracts and proof owners; no implementation needed for historical workflow prose. See [current ownership](#governance). Imported content below is historical.

> # Prism++  Catalog Rebuild Workflow
> Doc Status: planning
> 1. Assistant provides a PHP example from the catalog.
> 2. User provides the expected Prism++ target code, or marks the case as `ERROR`.
> 3. Assistant corrects the proposed target to comply with the general rules.
> 4. Assistant derives the generalized rule, not just the concrete instance.
> 5. The catalog stores one row per generalized rule, not one row per trivial permutation.
>
> ## Constraints
>
> - General rules have precedence.
> - Concrete examples may be corrected to fit the general rules.
> - The goal is to avoid combinatorial explosion and preserve only reusable rule knowledge.
