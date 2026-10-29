# Rust compiler decision and migration plan

Doc Status: planning
Date: 2026-10-29
Decision: approved direction; implementation has not started.

## Decision and scope

Implement the v0.2 compiler in Rust, independently of the C++ runtime. Keep the
runtime and the metadata-driven API generator, including physical pre-lowering,
in C++ for now. The three components remain the project backbone; only the
compiler implementation language and its bootstrap dependency boundary change.

This supersedes the proposal to evolve the C++ compiler into the production v0.2
compiler, including adapting its source snapshots and token sequences to generated
runtime ownership handles. The existing C++ compiler foundation remains working
reference/proof material during migration. This plan does not remove it, change
builds, introduce Rust code, activate a goal or authorize a bulk legacy import.

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
| C++ API generator | Metadata validation; concrete runtime publication; physical type/signature/dependency/adaptation plans |
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

## Generator integration and bootstrap

Use the existing generator executable and file artifacts as the initial process
boundary. Do not add C++/Rust FFI, a second generator, or a Rust port of the generator
as a prerequisite. Plain C++ consumers keep using the same publication mechanism.

The intended flow is:

1. The compiler obtains validated target-policy facts from generator preparation.
2. It parses/resolves source and accumulates concrete publication demands.
3. The generator resolves those demands into a checked physical plan and C++ API.
4. The compiler binds its prepared operations to that plan and emits C++.
5. Clang compiles emitted C++ together with the requested runtime publication.

Before implementing this integration, inspect the existing prepared.json model and
CLI. Preserve usable preparation/emission logic. If current output only describes
an already selected publication, add the smallest explicit policy-query/preparation
interface needed to break discovery dependencies. Do not presume an unimplemented
query protocol exists or generate the entire overload matrix to discover policy.

Document schema/version compatibility, target/configuration identity, required
fields, failure diagnostics and deterministic output at the integration slice.
Reject incompatible or stale facts. Generated plans remain derived artifacts;
protocol compatibility does not turn them into independently authored truth.
Regenerate native layout facts using the target C++ toolchain, never infer them
from Rust host layouts. Initial validation targets the existing native platform;
cross-compilation is separate work.

A standalone Cargo build/test of the compiler core must not require Clang, a
runtime build, or generator execution. End-to-end compilation intentionally needs
the generator and C++ toolchain. Start with one-way process orchestration and an
explicit integration test, avoiding a Cargo/CMake build cycle.

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
- Start with one Cargo package containing a library and thin CLI, grouped into
  coherent modules. Split packages only when a real dependency/build boundary helps.
- Select and pin a stable Rust toolchain at implementation time, commit Cargo.lock
  for reproducible application builds, and review dependencies individually. A
  focused serialization dependency is acceptable; no large framework is required.
- Use Cargo with eight build jobs. Keep Ninja/eight jobs/Clang/libc++/mold for the
  existing C++ components. Configure mold for Rust on the supported Linux target
  when validated; Rust compilation itself uses rustc, not Clang or Ninja.
- Ignore Cargo target directories, generated C++, plans, caches and local benchmark
  output. Keep intentional source fixtures and compact validation summaries.

## Migration slices and acceptance

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

Resolve the generator interface described above, request only needed concrete
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
legacy compiler facility. No runtime/API-generator language rewrite is included.
