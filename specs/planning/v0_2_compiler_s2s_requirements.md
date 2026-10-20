# v0.2: compiler capabilities for a semantically informed S2S
Doc Status: planning

Date: 2026-09-25

Confirmed implementation home:
`/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try`.
Continue its successful small-step development approach. The broader compiler and
external prototype are reference material, not alternative implementation targets.
This inventory describes capabilities to grow into that model, not a requirement
to copy the larger compiler's organization or machinery.

This is a proposed feature inventory and sequencing discussion, not a language
specification, implementation authorization, or claim of completed support.

## Review basis

- Reviewed local branch `codex/s2s-edit-latency-assessment`, tip
  `044f6a65010fc336fab3dbd23aa24d49ce3a75e8`. It is already an ancestor of the
  reviewed checkout, `1f2804fc`; its technical handoff has no diff against HEAD.
- [Technical handoff](s2s_next_generator_technical_handoff.md),
  [coverage analysis](s2s_compile_latency_minimal_coverage.md), and
  [archived summary](../../tools/compile_latency/results/2026-09-20/minimal-coverage/summary.json).
- External `scpp_compiler_3/prototype/my-try` decisions, preparation code and gap
  assessment; the broader prototype supplies reference designs, not capabilities
  automatically available in the small experiment.
- Local [my-try intake](../../compiler/my-try/docs/README.md),
  [retained model](../../compiler/my-try/docs/architecture/MODEL.md),
  [pipeline](../../compiler/my-try/compiler/calls.md), and LLVM generation code.
  This adapted experiment is the intake's current focus; `compiler/src` is parked.
  Existing uncommitted work there is outside this review.
- Current [dynamic semantics](../dynamic_types.md) and
  [generator contract](../../generators/php/specs/rules.md) remain authoritative.

This was a source/document/evidence review. Benchmarks and compiler suites were
not rerun. Historical proof counts are not new validation of the working tree.

## Assessment of the latency study

Carry forward its output architecture: individual type headers, narrow callable
declarations, stable implementation groups, explicit dependency requirements,
separate volatile diagnostic data, and publication only when final bytes change.

The archived twelve runnable cases have 36 native trials and 1.593–4.815 second
native medians. The coherent 28-source change took 32.680 seconds native and is
explicitly accepted. It is a single screen, not a median. Sixteen context exclusions
and one incompatible before-state limit coverage. The additional 1.5 seconds for
analysis/generation is an assumption requiring measurement with the real frontend.

The experiments establish bounded feasibility, not a production semantic frontend.
Do not promote lexical dependency scans or class-specific tables into the compiler.
Simpler C++ can help, but correct artifact boundaries and invalidation are essential
to reproducing these results. Narrower runtime headers are a subsequent experiment,
not an already measured end-to-end improvement.

## Main architectural requirement

Publish a resolved and checked program before backend generation:

```text
source -> syntax/collection -> resolution -> checked bodies and lifetime facts
                                             |                    |
                                             v                    v
                                      C++ lowering plan      LLVM lowering
                                             |
                                      artifacts -> native build
```

One semantic owner must decide source meaning. Both backends consume those facts.
The compiler need not become a C++ compiler or optimizer: Clang still checks the
emitted C++ and owns native optimization/code generation. Partial coverage must be
explicit; an unresolved fact must never masquerade as a deliberate dynamic value.

The most immediate gap in `my-try` is that semantic checking is partly embedded in
LLVM preparation/emission. Its retained roots expose syntax, collections and LLVM
output, but no complete backend-independent checked-program boundary. Extract that
owner for a bounded existing subset before duplicating decisions in a C++ emitter.

## Features grouped by role

The rows describe required capabilities. They are not a claim that every syntax
feature must be implemented before the first S2S slice.

| Role / owner | Required features | Output consumed by S2S |
| --- | --- | --- |
| Source and project model | Module/file membership, configured profile, entry and initialization order, imports/dependencies, source spans, stable declaration identity across edits | Source-to-symbol map, project context, diagnostic locations |
| Syntax and collection | Parse the selected PHS subset; preserve declaration, expression and statement structure; distinguish initialization from assignment; retain all declarations and references | Syntax with exact source provenance and indexed semantic work |
| Symbol resolution | Lexical scopes and visibility, namespaces/imports where supported, duplicate/ambiguous/missing-name diagnostics, cross-file calls, member lookup, inheritance lookup when enabled | Exact declaration/member/callable targets; explicit unsupported states |
| Type model and signatures | Canonical scalar/object/value/container/wrapper identities; qualifiers, nullability, ordered fields and bases; parameter/result contracts; exact generic instance identity | Types and source representation semantics independent of LLVM type strings |
| Body and operation checking | Types of every expression; permitted operator/call selection; explicit conversions by purpose; receiver/argument evaluation order; assignable storage paths; temporaries; result categories | Checked operations, selected calls, conversion records and read/write places |
| Control flow and lifetime | Initialization across paths, returns and joins, reference mutability, aliasing, shared-object versus value-copy behavior, temporary lifetime, required copy/destruction and supported escape rules | Facts sufficient to preserve source behavior through C++ signatures and RAII |
| Dynamic boundaries | Distinguish mixed from unresolved analysis; typed normalization at approved destinations; guarded narrowing; static versus dynamic member operations | Explicit checked extraction/dispatch operations with failure behavior |
| Runtime/library contracts | Builtin and wrapper signatures, profile/module availability, conversions/checks, construction/iteration, callback/task/exception requirements for enabled features | Resolved runtime operations and semantic dependencies |
| Templates and compile-time use | Definition permissions, argument binding, concrete instance reuse, demanded operations, dependency closure; defaults/constants/inline requirements where supported | Required specializations and definition-use facts, with unsupported cases diagnosed |
| Semantic publication and changes | Completed snapshot, validity and coverage state, stable identities, body/contract/type/constant/location changes, additions/deletions, retained dependency facts | A trustworthy current program and previous-to-current mapping |
| C++ lowering and artifacts (S2S) | Representation and ABI policy, direct versus checked runtime operations, declaration versus complete-definition needs, stable grouping/names, artifact hashes and dependency reasons | Per-type/callable artifacts, implementation units and build manifest |
| Native build driver | Context fingerprints, depfiles, correct complete link inputs, obsolete-unit removal, consistent publication, runtime-only PCH baseline, explainable rebuilds and timings | Correct incremental Clang/Ninja build |

Runtime contracts should reuse authoritative metadata where it exists. Missing
source-facing contracts need an explicit owner; parsing runtime C++ or inferring
builtin semantics in each backend would recreate the same problem.

The frontend reports semantic uses, such as field access, construction and inferred
return types. The S2S determines which generated operations require a forward
declaration, complete definition or inline/template definition. Header paths and
partition policy do not belong in the frontend.

## Minimum checked-program contract

Start with a small in-process API, not a serialization framework. For each supported
body the emitter needs:

- Its owning symbol, source location and complete callable contract.
- Canonical types and resolved declaration/member/call targets.
- Ordered operations and operands, including conversions and runtime checks.
- Storage places, value/reference categories, mutability and temporary lifetimes.
- Referenced semantic contracts, types, specializations and runtime operations.
- Explicit completion/failure state: no incomplete body is published as checked.

Program-level data supplies declarations, type definitions, source mapping and
configuration. Snapshot comparison can initially supply changes; a separate change
feed is optional. Content fingerprints detect change but do not define entity
identity. Current collection positions/object handles survive only their retained
objects; cross-reparse and cross-process stability needs an explicit mapping policy.

For the C++ path, source lifetime meaning is required, but LLVM physical ABI plans,
LLVM instructions and complete direct-LLVM native preparation are not prerequisites.
Use C++ RAII where it faithfully implements the checked source contract. Do not
silently change source-owned lifecycle behavior through a native adaptation.

## What type awareness can simplify

| Known fact | Permitted simplification | Required protection |
| --- | --- | --- |
| Exact callable and argument contracts | Direct calls and explicit conversion only where required | Preserve evaluation order, defaults, reference behavior and diagnostics |
| Concrete receiver/member | Direct member access with precise includes | Preserve dispatch, visibility and null checks |
| Concrete operand types and selected operation | Select one appropriate native/runtime operation | Native arithmetic is valid only if its semantics match the source contract |
| Exact value/reference/result category | Reduce unnecessary adapters, boxing or temporaries | Preserve copies, aliasing, lifetime and exceptions |
| Complete operation dependency set | Narrow declarations and type/runtime includes | Include dependencies from inferred results, nested fields and destruction |

Remove v1 runtime bridges only after explicit generator coverage proves parity at
all affected typed boundaries. More information does not authorize changing mixed
reference restrictions or using mixed extraction to invent overload selection.

## `->` on mixed: two distinct capabilities

1. **Statically established receiver:** an approved narrowing or explicit typed
   boundary identifies the object type. Resolve the member normally and emit
   checked extraction where necessary, followed by ordinary member access.
2. **Genuinely dynamic receiver:** its runtime contents determine the property.
   Represent this as a dynamic-member operation and lower it through a defined
   runtime contract. A compiler cannot resolve an unknown runtime member statically.

The current dynamic spec describes a shared property bag and explicitly records
missing dynamic-property lowering. It does not supply a complete new contract for
arbitrary dynamic method dispatch. Before implementing the second capability, settle
receiver kinds, literal/computed property names, read/write behavior, missing versus
null values, wrong-kind failures, nested mutation, probes and aliasing. Decide method
calls separately from property access. This is a language/runtime/S2S feature slice,
not merely another type annotation on the AST.

## Starting point and proposed delivery order

`my-try` already offers source/token/AST ownership, declaration/reference collection,
bounded cross-file target preparation, scalar value/reference calls, small structs
and fixed arrays, and explicit function-template experiments. Those are useful
foundations. They do not prove general resolution, managed lifecycle behavior,
runtime integration, or stable incremental publication.

1. **Shared semantics for the existing scalar-call subset.** Extract checked
   signatures, variable/call bindings, body types, initialization and return checks
   from LLVM-specific processing. Keep both backends on the same decisions.
2. **First C++ vertical slice with incremental output.** Compile two files with a
   typed parameter and returned call result. Publish narrow callable declarations
   and stable implementation units. Change one body, preserve unrelated bytes and
   timestamps, compile/link/run again. Also prove wrong argument/return rejection
   before either emitter. Measure frontend and native time separately.
3. **Representations and real dependencies.** Add independent record copying,
   shared objects, nested field access, containers and managed values in bounded
   slices. Prove inferred-result and destruction dependencies, signature changes,
   layout changes, symbol addition/removal and location-only changes.
4. **Dynamic boundaries and selected new features.** Prove mixed normalization at
   locals, properties, container slots, arguments and returns. Then implement the
   agreed mixed-property contract with aliasing and wrong-kind witnesses.
5. **v0.2 coverage and performance qualification.** Maintain an explicit matrix
   of existing language features: supported, deliberately deferred, or blocked.
   Include wrappers, exceptions/finally, inheritance, templates, closures/tasks
   and enabled library modules as applicable; do not silently drop old features.
   Replay the latency fixtures only once equivalent source/runtime context exists.

For the first incremental proof, one full build plus one update is sufficient.
Unsupported update categories can use the same stages with full selection. Production
readiness later needs repeated edits, deletion and failure behavior verified; do not
claim it from the bounded first proof. Actual compiler-worker threading is optional.

Non-goals for this planning slice: implement a backend, resume the parked compiler
migration, port the compiler to native, implement a full PHP/C++ compiler, build an
optimizer, or complete the direct-LLVM roadmap before using C++ output.

The next concrete design discussion should define the shared checked-body contract
for step 1 and the exact first acceptance fixture. Implementation may cross current
analysis/LLVM ownership: present that bounded refactor for agreement before coding,
rather than treating this inventory as approval for a broad rewrite.
