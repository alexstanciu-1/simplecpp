# Rust compiler and API tooling migration plan

Doc Status: planning
Date: 2026-10-29
Decision: approved direction; slice 1 implemented on 2026-10-29.
API-generator/physical pre-lowering migration to Rust approved on 2026-10-29.
G1 and G2 preparation for all current metadata families are implemented.
G3 scalar C++ emission is implemented; other family emitters and production cutover remain planned.

## Decision and scope

Implement both the compiler and metadata-driven runtime API generator, including
physical pre-lowering, in Rust. Keep the runtime, generated application/API code
and a small native layout probe in C++. The three responsibilities remain the
project backbone, with compiler and API tooling sharing a typed Rust library boundary.

This extends and supersedes the earlier decision to retain the generator in C++.
The Rust compiler foundation is implemented; the production API generator is still
C++. The first G2 batch establishes the workspace, shared library and native probe;
production cutover remains deferred. Preserve numeric, carrier and ownership semantics during the
migration; a tooling language change is not a runtime-policy redesign.

This supersedes the proposal to evolve the C++ compiler into the production v0.2
compiler, including adapting its source snapshots and token sequences to generated
runtime ownership handles. The C++ compiler foundation has now been retired after the separately authorized
Rust sequence replacement. Its donor code remains available in Git history. This
does not retire the production C++ API generator or authorize a bulk legacy import.

The primary reasons are maintainability for the project owner, less declaration
and lifecycle boilerplate, stronger memory-safety feedback, and removing runtime
publication work from the compiler's implementation prerequisites. Faster builds
and a two-to-threefold code reduction are not assumed benefits or acceptance claims.
Measure comparable functionality before making performance or size claims.

All active Rust sources, contracts, fixtures and bootstrap inputs must live inside
v0.2. External toolchains and explicitly selected packages are allowed; the old
repository remains material for bounded, explicitly agreed reuse, not a dependency.

## Ownership and dependency boundaries

| Owner | Responsibility |
| --- | --- |
| Rust compiler | Source loading, scanning, parsing, symbols, types, diagnostics, semantic preparation, evaluation order and C++ emission |
| Rust runtime API tooling library and CLI (partial G2) | Metadata validation; concrete runtime publication; physical type/signature/dependency/adaptation plans |
| Small C++ layout probe | Native C++ target facts required by preparation and generated layout checks |
| C++ runtime | Representation, storage, lifecycle, actual operations and checks requiring runtime values |
| Runtime metadata | Supported identities, operation/conversion policy and binding facts consumed through the generator's prepared model |
| Adopted language contracts | Source admission and semantics, including language-flavor decisions; implementation language does not redefine them |

The Rust compiler uses Rust types and collections internally. Its implementation
is not subject to the runtime's template-free public C++ boundary. It does not link
the Simple C++ runtime, include generated C++ headers, or require generated ownership
specializations for its own records. Runtime users retain the template-free public
API; private runtime templates remain allowed.

The compiler still knows its target. Source semantic preparation determines types
and contextual operations, then uses the shared runtime policy to request physical
bindings. It must not maintain competing handwritten promotion/cast/operator tables.
Compile-time evaluation must implement the adopted target semantics explicitly,
including overflow, widths, rounding, extraction and short-circuit behavior; Rust's
native behavior is not the specification of Simple C++.

## Shared Rust library boundary

Use one Cargo workspace with two packages: the compiler library/CLI and a runtime
API tooling library with its own thin standalone generator CLI. Retain compiler/
and tools/api_generator/ as responsibility locations where practical; stage any
file moves explicitly. The first G2 batch establishes this workspace and consolidates
the toolchain pin, lockfile, Cargo build configuration and output ignores at v0.2/.
Commands and the isolated-export proof now use the two-package workspace.

The compiler depends on API tooling; API tooling must not depend on compiler AST,
symbol tables, source diagnostics or compiler-internal IDs. The library accepts
runtime identities, typed publication demands and target facts, and returns typed
validated policies and physical plans. The compiler maps its symbols/types to those
identities. Keep metadata loading/validation, preparation and C++ emission as clear
modules within the tooling package, not separate frameworks or competing engines.

Preparation owns runtime-policy resolution once. Neither the compiler nor C++
emission may reconstruct promotion/cast/publication tables independently. The
compiler owns contextual language admission, sequencing and proof-based lowering;
the library does not resolve source symbols or become a second semantic compiler.
Metadata remains authoritative for runtime policy; prepared models are derived.

Compiler integration uses the typed library directly. The standalone CLI wraps the
same library for plain C++ users, CMake and debugging. Keep JSON prepared/inventory
artifacts where useful for inspection and external consumers, but do not require
serialization/process round trips between Rust components. No C++/Rust FFI is needed.
The current C++ CLI remains the baseline until the Rust CLI passes the migration gate.

Document the library contracts and external artifact/CLI schema compatibility,
required fields, target identity, failure categories and deterministic ordering.
Review JSON parsing behavior before selecting a serialization dependency, including
unknown fields, duplicate keys and numeric representation. A focused package is
permitted; changing accepted inputs accidentally is not. Keep diagnostic categories
and useful context; require exact message parity only where it is a documented contract.

## C++ layout probe and bootstrap

The current generator measures native smart-pointer layout while running as C++.
A Rust generator must not infer C++ sizes/alignment from Rust types or constants.
Introduce a small C++ toolchain probe that emits versioned target facts consumed
explicitly by preparation. Inventory all native assumptions first: smart-pointer
sizes/alignment, scalar representation facts and any additional facts currently
obtained from the generator's compilation environment.

The probe may use the selected standard library, but must not include the generated
runtime or require runtime linkage. It contains measurements, not metadata policy
or a second preparation implementation. This is the deliberate small C++ tooling
exception alongside the runtime and generated code.

- Compile and run the probe for the initially supported native target with the same
  C++ compiler, language mode, standard library, ABI switches and relevant flags as
  the runtime consumers. Record schema and toolchain/target/configuration identity.
- Supply its output as an explicit preparation input. Reject absent required facts,
  unsupported schemas, malformed values and mismatched target configuration rather
  than silently falling back to Rust host measurements or guessed defaults.
- Invalidate cached facts when probe source, schema, compiler/toolchain selection,
  target, standard library or ABI-affecting configuration changes. Do not depend
  solely on an existing file's timestamp or treat a compiler version as complete
  ABI proof. Record the relevant identity inputs and regeneration dependencies.
- Preserve generated C++ static assertions for each concrete representation. Probe
  facts and size/alignment assertions do not guarantee arbitrary ABI compatibility;
  runtime and consumers still require a consistent target/toolchain build.
- Cross-compilation is a later design: a target executable cannot automatically be
  run on the host. Unsupported cross-target probing must fail explicitly. Do not
  substitute host facts. A future approved target-facts provider can extend this.
- Generated probe results, plans and C++ output remain ignored build artifacts.
  Small intentional target-fact fixtures may be tracked as tests, clearly identified
  as fixtures rather than deployable machine configuration.

The intended build dependency graph is:

```text
Cargo -> Rust tooling library + standalone generator
C++ toolchain -> small native layout probe -> target facts
metadata + publication + target facts + Rust generator -> generated C++ API/plans
C++ toolchain -> generated API + handwritten runtime + consumer
```

CMake/Ninja owns C++ build orchestration and generation dependencies, invoking Cargo
for the generator with an explicit manifest/package/profile/output location. Cargo
must not invoke CMake back through build.rs. Rust packages can build and run their
pure unit tests without the C++ probe or runtime; real target generation and C++
consumer integration tests require valid probe facts and the native toolchain.

The future compiler flow is policy preparation, source resolution and demand
collection, physical preparation, then C++ emission using the resolved plan. Review
whether the current prepared model can expose policy before a publication is chosen;
add the smallest explicit library API needed. Do not generate a full overload matrix
for policy discovery. This replaces the earlier process-first integration proposal.

## API tooling migration slices and gates

Prioritize this migration before substantial compiler-to-generator integration.
Scanner work is independent; compiler source-semantic work must not grow substitute
runtime-policy tables while the shared library is being established. Each tooling
slice receives focused validation and a commit. Temporary dual generation is a
comparison mechanism with a retirement gate, not two independently evolving products.

### G1. Capture the existing generator contract

Inventory handwritten preparation, emission, JSON support, native assumptions,
CLI/file behavior and all supported publication families. Use the current default,
compiler and ownership-proof publications plus existing reduced/negative publication
tests. Record which artifacts require byte parity versus semantic comparison.
Baseline measured on 2026-10-29: nine C++ source/header files, 2,146 physical lines,
133,862 bytes including private JSON support; tests/metadata/output excluded. These
counts are scope evidence, not an estimate of verification effort.

Acceptance: a reproducible parity corpus and explicit contract checklist covering
admitted/rejected requests, artifact paths, freshness/check behavior, deterministic
ordering, generated header boundaries and target facts. Retain intentional fixtures
and compact summaries; keep bulky regenerated outputs ignored.

### G2. Establish the shared model and native facts

Introduce the two-package workspace, typed metadata/preparation model, thin Rust CLI
skeleton and bounded JSON loading. Implement the C++ layout probe and target-fact
validation independently of the runtime. Port preparation in coherent family batches,
starting with scalars and then sequences/carriers/ownership. The C++ generator stays
the production path during these incomplete slices.

Acceptance: pure Rust tests run without C++ tooling; malformed metadata, unsupported
requests, missing/incompatible target facts and invalid layout inputs are rejected.
Compare complete prepared facts with the C++ baseline on the same native toolchain,
normalizing only explicitly reviewed nonsemantic differences. Shared model types
must express resolved operations rather than a mirror of JSON field access.

### G3. Port emission and prove equivalent generated consumers

Port C++ emission by the same coherent families using the prepared Rust model.
Preserve generated artifact structure and CLI compatibility where useful; record
intentional differences rather than silently adjusting expectations. Both generators
run on the same metadata/publication and matching toolchain facts into separate
output directories; do not overwrite the production output with incomplete results.

Acceptance: compare prepared facts and generated output across the parity corpus;
any differences are reviewed, not blanket-normalized. Compile and run existing C++
consumer, template-free boundary, compile-failure, layout-mismatch, lifetime and
allocation proofs against Rust-generated artifacts. Run the applicable Release and
UBSan suites. Verify deterministic regeneration, freshness checks and a source-only
build. Measure generator build/run costs separately from generated C++ compilation.

### G4. Switch generation and retire the superseded toolchain path

After G3 passes, switch CMake generation to the Rust CLI plus explicit probe facts.
Prove rebuild behavior after metadata, publication, generator and target-configuration
changes, and that an unchanged build does not regenerate/recompile unnecessarily.
Update operator commands and fresh-checkout setup before cutover.

Acceptance: one production generator/preparation implementation, no Cargo/CMake
cycle, no dependency on archived/prebuilt C++ generator artifacts, and independent
runtime and tooling validation paths. Remove or archive the C++ generator and its
JSON support in a reviewed cleanup after checking remaining consumers. Retain useful
fixtures and history. This does not retire the separate C++ compiler foundation;
its own migration gate below still applies. Do not port the runtime to Rust.

## Rust implementation posture

- Prefer ordinary structs, enums, functions and direct control flow. The project
  owner must be able to review the implementation without a framework of traits.
- Use compiler-owned tables and typed SourceId, SymbolId, TypeId and NodeId values
  where graph relationships need stable identity. Define validity and mutation
  boundaries; do not propagate long-lived borrows throughout semantic graphs.
- Choose owned buffers and bounded local borrows deliberately. Shared immutable
  storage is available when retention requires it; shared mutable nodes are not
  the default architecture. Keep unsafe code out of the initial compiler slice.
- Use generics when they remove actual repetition. Avoid speculative abstractions,
  procedural macros, async infrastructure and premature parallelism.
- The initial compiler slice uses one package. At G2, introduce the two-package
  workspace described above, with one-way compiler-to-tooling dependencies and
  coherent modules rather than additional speculative packages.
- Select and pin a stable Rust toolchain at implementation time, commit Cargo.lock
  for reproducible application builds, and review dependencies individually. A
  focused serialization dependency is acceptable; no large framework is required.
- Use Cargo with eight build jobs. Keep Ninja/eight jobs/Clang/libc++/mold for the
  existing C++ components. Configure mold for Rust on the supported Linux target
  when validated; Rust compilation itself uses rustc, not Clang or Ninja.
- Ignore Cargo target directories, generated C++, plans, caches and local benchmark
  output. Keep intentional source fixtures and compact validation summaries.

## Compiler migration slices and acceptance

Each slice needs an agreed scope, a working proof, updated documentation and a
commit. Keep the C++ baseline until its replacement satisfies the stated checks.
Do not maintain two production compiler implementations in parallel.

### 1. Standalone Rust foundation and diagnostic CLI

Create the package and CLI, source byte ownership, source IDs, spans, compact token
model and diagnostics. Inspect the current v0.2 token/buffer/sequence implementations
and tests as bounded donors. Preserve useful behavior rather than translating
headers, state pointers or C++ special-member mechanics literally. Record intentional
API differences, including how Rust represents absence and failures.

Acceptance: Cargo build/test works without runtime generation; byte offsets, embedded
NUL/high bytes, empty input, invalid spans and source retention have focused proofs.
Preserve the compact token representation if adopted, with size/range tests. Diagnostics
must report source locations and fail cleanly. Record clean build, incremental edit,
cargo check and focused test timings with toolchain and hardware context.

### 2. In-memory scanning and token publication

Port/reimplement scanning in bounded token-family batches after donor review. Retain
source ownership and deterministic ordered token spans. Add lexical-error recovery
where specified; do not pull in legacy filesystem/task/module infrastructure.

Acceptance: golden token/diagnostic fixtures for the agreed source flavor, boundary
bytes, comments, literals and malformed input. Compare legacy behavior only where
its contract has been adopted. Existing C++ tests are evidence, not language authority.

### 3. Small parsed and symbol-aware semantic slice

Choose and document one source flavor and a minimal executable subset before coding.
A proposed first subset is integer literals, typed local declarations, local name
references and arithmetic. Implement explicit syntax nodes, symbol/type IDs and
resolved operations. Diagnostics should distinguish unknown names, invalid types
and unsupported syntax. Avoid broad language coverage or an LLVM backend.

Acceptance: valid and invalid fixtures prove name resolution, type decisions and
source locations. Syntax-to-semantic-to-emission ownership must be clear. Numeric
policy must come from validated metadata facts before target arithmetic is admitted;
use unresolved demands until the next slice if that boundary is not yet ready.

### 4. Metadata boundary and first executable end-to-end proof

After the Rust tooling parity/cutover gates G3/G4, use the shared typed library
interface described above, request only needed concrete
runtime operations, bind the semantic representation to the physical plan, emit
C++, compile and run a small program. This slice completes slice 3's target binding;
its interface design can be prepared alongside slice 3 to avoid rework.

Acceptance: one source program exercises a resolved local and arithmetic, with
expected output or exit status. Negative fixtures cover incompatible metadata,
rejected conversions and invalid source operands. Where relevant, compare compiler
constant evaluation with runtime execution at numeric boundaries. Verify regeneration
when metadata/publication changes, deterministic output, and no runtime linkage in
the Rust compiler executable. A fresh source-only checkout reproduces the result.

### 5. Cut over and retire superseded compiler scaffolding

Once equivalent foundation tests and the end-to-end slice pass, make the Rust path
the documented compiler entrypoint. Prefer the final compiler/ layout with Cargo
sources in compiler/src/; temporary C++ coexistence must have an explicit retirement
point. Archive or remove the C++ foundation and compiler-only publication/build
wiring in a separately reviewed cleanup slice, retaining useful fixtures and history.

Acceptance: a single active compiler path; no obsolete required compiler runtime
publication, state-wrapper adaptation work or stale README instructions. Runtime
and generator tests remain independently buildable. Do not delete ownership/value
support: it continues serving generated programs and plain C++ applications.

### 6. Grow compiler functionality through vertical slices

Add source features from explicit consumer needs, extending metadata requests and
runtime capabilities only where required. Keep diagnostics and semantic negatives
alongside successful execution tests. Hash/mixed/dynamic runtime work retains its
own review and Swiss-table reuse constraints; it is not a prerequisite for Rust
symbol tables or other compiler-internal collections.

## Validation and completion criteria

Track compiler correctness, reproducibility and developer feedback time separately.
Use cargo fmt/check/test and an agreed Clippy baseline; C++ boundary/runtime checks
continue under their existing presets. Time clean and incremental builds separately,
state dependency-cache conditions, and measure release builds independently from
cargo check. Optimize measured bottlenecks rather than designing for speculative costs.

The migration is complete when the agreed source subset passes semantic and execution
proofs, the compiler builds independently in Rust, generator/runtime integration uses
one documented metadata boundary, and the superseded C++ compiler path is retired.
This is not a claim of complete language coverage or a requirement to port every
legacy compiler facility. The API tooling migration additionally requires G1–G4
parity and cutover gates. The runtime remains C++; no runtime language rewrite or
source semantic redesign is included.

## Slice 1 checkpoint — 2026-10-29

Implemented the standalone Cargo library/CLI in compiler/ alongside the unchanged
C++ source list. It uses stable Rust 1.99.1, eight jobs and mold on the tested Linux
target, with no third-party packages, runtime linkage or generator dependency.
Sources, IDs, half-open byte spans, compact tokens and retained location diagnostics
are implemented. The CLI loads a file and reports a byte location; it does not scan
or compile source yet. Token-sequence publication and scanner work remain slice 2.

Eight Rust tests pass in debug/release, formatting and strict Clippy pass, and all
34 C++ checks pass. A Rust-only temporary export builds/tests offline. The
[compiler README](../../compiler/README.md) records adopted donor contracts,
intentional Rust API differences, installation commands and reproducible timing
measurements. No C++ implementation, build wiring or generated publication was
removed. This checkpoint implements the separately authorized first slice only.

## G1 checkpoint — 2026-10-29

The [generator contract inventory](api_generator_contract_inventory.md) now records
input/JSON rules, supported families, prepared facts, artifact and CLI behavior,
native assumptions and comparison gates. A frozen fingerprint fixture covers 17
accepted publications and 37 rejected inputs, plus CLI lifecycle checks. Comparator
mutation tests verify that changes are detected. The candidate hook is exercised
with the unchanged C++ executable; Rust/C++ parity is not yet claimed.

The current C++ generator remains the production implementation. G1 adds only
contract fixtures, a Python test harness, test-only CMake wiring and documentation.
Probe implementation, the Rust shared library and build cutover remain G2–G4 work.

## G2 first batch checkpoint — 2026-10-29

The [tooling README](../../tools/api_generator/README.md) records the implemented
shared Rust library, thin inspection CLI, bounded JSON adapter and native C++ probe.
The compiler has a one-way tooling-library dependency; neither Rust package links
the C++ runtime. Cargo and CMake remain independent builds.

Rust JSON loading uses serde/serde_json with raw-token preservation and decoded
duplicate-key/depth checks. The existing custom C++ reader remains in production.
No custom JSON grammar was ported to Rust. Package versions are locked.

The first typed preparation projection covers integer identities, native inputs
and checked construction. All eight integer types and 104 conversions match the
C++ prepared model exactly. This is not full metadata validation: policies outside
that projection are not semantically validated, and unsupported publication
demands fail. Scalar operators/casts beyond construction and the remaining families
still need coherent G2 batches; G2 is not complete.

The explicit CMake probe target measures native integers, binary64 and smart-pointer
layouts without runtime dependencies. Context matching, cache stability/invalidation
(including expanded headers), malformed facts and cross-target rejection are tested.
The consumer must obtain its expected context from the current trusted probe driver;
a matching stale facts/context pair alone cannot establish freshness. Native x86_64
Linux is the supported initial target. No cross-compilation or ABI certification is
claimed. Existing generated C++ layout assertions remain in place.

Validation: 15 Rust tests pass in debug/release, formatting and strict Clippy pass,
and a Rust-only workspace export builds/tests offline with cached dependencies.
All 36 existing C++ tests pass in release and UBSan. Integration compares native
facts, ownership layouts and the complete integer-construction projection against
the unchanged C++ generator. C++ production generation and G1 fixtures are unchanged.

## Rust sequence publication and C++ foundation retirement — 2026-10-29

TokenBuilder now owns source-bound mutable tokens and checks containment and
nonoverlapping order. Consuming finish publishes an immutable shared generation
without copying/reallocating its token buffer. Rust moves replace the old runtime
finished flag; Option expresses absence. Token and SourceFile remain the owners of
representation and source-size validation. Scanner/token classification is not part
of this slice; compiler migration slice 2 remains incomplete until scanning is added.

Removed the 12 C++ foundation/build/test files and the root CMake compiler hook.
Relocated compiler/publication.json unchanged to
tests/generator_parity/compiler_publication.json and updated both parity/probe
consumers. The five compiler-specific CTests are intentionally retired; runtime and
API-generator checks remain. Old checkpoint statements about keeping the C++
foundation describe their historical state. The current compiler README describes
the Rust API and its proofs. Production API generation remains C++.

Replacement validation: 20 Rust tests plus the single-use compile-fail doctest pass
in debug/release; strict Clippy, formatting and isolated Rust export pass. All 31
remaining C++ tests pass in release/UBSan. G1 parity and G2 native-probe integration
pass without changing the publication contents or frozen baseline.
A fresh source-only C++ export also builds and passes all 31 checks, without
retired compiler sources or pre-generated runtime files.

## G2 scalar preparation checkpoint — 2026-10-29

Rust now prepares the complete scalar-only document: integer construction/casts,
all integer operators and promotion, bool/null value capabilities, configured
source defaults and aliases, float64 construction/casts/operators, native bridge
dependencies and physical operand/write-back adaptations. The scalar model uses
typed forms, promotions, conversion implementations and adaptations. The original
integer-construction projection reuses the same registry/range conversion owner.

The new prepare-scalars CLI emits inspection JSON only. All fields match the C++
prepared plan for scalar-only publications, including a 2,692-signature matrix.
Known optional non-scalar metadata is outside this entrypoint's semantic validation;
non-scalar publication demands are rejected. Strings, vectors, carriers, ownership,
C++ emission and build cutover remain future work. The C++ generator remains the
production path and the frozen G1 fixtures have not changed. See the
[tooling README](../../tools/api_generator/README.md) for scope and proof commands.

Validation: 24 Rust unit/integration tests plus the compiler's compile-fail doctest
pass in debug/release; formatting, strict Clippy and the isolated Rust-only workspace
export pass. Exact scalar parity passes 19 accepted publications (including the
2,692-signature matrix) and 537 rejected inputs, comparing every field and JSON
value type. All 31 existing C++ tests pass in release/UBSan, including frozen G1
parity. G2 probe/cache and original 104-conversion projection checks also pass.

## G2 string preparation checkpoint — 2026-10-29

Added RuntimePlan composition and prepare-runtime inspection CLI, delegating scalar
policy to the existing scalar planner. String preparation validates capabilities,
count/bool/native dependencies, publication headers, operators and explicit text
casts, including borrowed input categories and failure facts. Optional string
metadata is validated even without publication. No C++ emission or cutover.

Exact full-document parity passes 13 accepted and 29 rejected string cases. Two
new pure Rust proofs cover resolved casts/methods and rejected dependencies; all
workspace tests and strict Clippy pass. Scalar parity remains unchanged.

## G2 vector preparation checkpoint — 2026-10-29

RuntimePlan now includes concrete vector specializations, pre-resolved method
arguments/adaptations and cross-specialization cast edges. Preparation checks count
and boolean dependencies, element family publication, native adapters, equality
operators and admitted element conversions. All specialization identities are
registered before cast edges are resolved, preserving request-order independence.

Combined string/vector exact-plan parity passes 20 accepted and 64 rejected cases,
including all 12 admitted elements and all admitted elementwise cast combinations.
Two additional pure Rust proofs cover methods, ordering and missing dependencies;
workspace tests, strict Clippy and focused C++ vector checks pass. Production
generation remains C++; carrier/ownership preparation and emission remain pending.

## G2 carrier preparation checkpoint — 2026-10-29

RuntimePlan now composes nullable, result, result_or_false and result_or_bool plans
with typed family and extraction policy. It checks published payloads, nullable's
null dependency, result's string/int64 error dependencies, default states, header
collisions and unsupported nested/null/error payloads. Both checked one-layer value
copy policies are supported. There is no new operator matrix or implicit carrier
conversion inventory. Structured error facts appear only for published result
specializations. Public header validation is shared by all prepared families.

Combined full-document preparation parity passes 43 accepted and 130 rejected
cases, including 92 carrier specializations with both extraction policies and all
G1 cases without ownership publication. String/vector regression cases remain in
the same suite. Existing narrower scalar and integer inspection entrypoints remain
available. Ownership metadata validation/preparation, C++ emission and production
cutover are the remaining generator migration work; the C++ generator stays active.

Final validation for these three family slices: 30 Rust unit/integration tests plus
the compiler compile-fail doctest pass in debug/release; strict Clippy, formatting
and the isolated Rust-only workspace export pass. Scalar parity still passes its
19 accepted / 537 rejected cases and 2,692-signature matrix. All 31 existing C++
tests pass in release and UBSan. Frozen G1 artifacts and production build wiring
are unchanged; generated files and validation outputs remain ignored.

## G2 ownership/value preparation checkpoint — 2026-10-29

Rust now prepares the complete current metadata family set. The ownership module
validates registered records, scalar constructor signatures, mutable/const family
publication, name/header collisions and weak/shared dependencies. Typed qualification
edges preserve copy retention and move transfer semantics; unique conversion is
move-only. Handle layouts come from validated probe facts. Inline value layout and
special-member capabilities remain native-payload-dependent, without guessed facts.

Production ownership headers admit /./ components, unlike the other families.
This existing distinction is preserved and tested for parity, not silently tightened.
The prepared-model comparison now covers the complete frozen G1 corpus and all
ownership-family subsets: 128 accepted publications and 249 rejected inputs.
No G1 baseline or C++ generator source changed. G2 preparation is complete for the
current contract; G3 emission/CLI artifacts and G4 build cutover remain.

Validation: 33 Rust unit/integration tests plus the compiler compile-fail doctest
pass in debug/release, with strict Clippy, formatting and isolated Rust-only export.
All 31 C++ regression checks pass in release and UBSan. The full runtime prepared
comparison passes 128 accepted / 249 rejected cases; the separate scalar matrix
retains 19 accepted / 537 rejected cases and 2,692 signatures. Existing generated
consumer tests still exercise the production C++ emitter, not a Rust emitter.


## G3 scalar emission checkpoint — 2026-10-29

The Rust shared library now emits integer, bool/null and float C++ artifacts from
ScalarPlan. Preparation still owns cast admission, promotions, operand adaptations
and result/write-back categories. Emission only renders resolved bindings. The
experimental emit-scalars command takes explicit probe facts/context and supports
read-only --check; generation rejects obsolete inventory entries before writing.
Publication is per-file, not transactional, matching the existing writer boundary.

All eight artifacts match the production C++ generator byte-for-byte across 19
accepted publications. The 537 rejected cases create no output. The complete
2,692-operation matrix compiles with Clang/libc++; generated headers compile alone
and expose no template declarations after preprocessing. Existing integer, value
and floating consumers compile/link/run against Rust-emitted definitions. Checks
also prove freshness does not rewrite files and obsolete inventories prevent writes.

Non-goals: string/vector/carrier/ownership emission, production CLI compatibility,
CMake cutover and compiler integration. These remain subsequent G3/G4 slices; the
C++ generator and frozen G1 baseline remain in place. Artifacts stay ignored.

Validation also passes 34 Rust unit/integration tests and one compile-fail doctest
in debug/release, formatting and strict Clippy. The isolated Rust-only workspace
export builds and tests without native runtime inputs. Rebuild measurements now
include compiler-model and shared-tooling diagnostic edits as well as CLI edits.

The full runtime prepared-plan regression retains 128 accepted / 249 rejected
cases, and all 31 C++ release regression checks pass, including frozen G1 parity.
