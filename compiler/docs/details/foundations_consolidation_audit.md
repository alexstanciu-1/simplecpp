# Foundations consolidation audit — 2026-09-18
Doc Status: supporting

Reviewed baseline: `e4b1553`, following fixed arrays/source methods and the parallel
test-runner change. The findings below describe that review baseline; the approved
consolidation is recorded separately. This is not a claim of exhaustive correctness. The [accepted remaining sequence](../planning/compiler_foundations.md#accepted-remaining-implementation-sequence)
remains unchanged.

## Assessment

The shared architecture is worth retaining. Source/provider records normalize
through one structural join; method receivers use ordinary borrowed signatures;
canonical types and concrete instances have explicit identity owners; checked
locations feed lifetime analysis and lowering; runtime metadata stays behind its
adapter. The review did not establish a need for a replacement architecture.

The three current findings A1–A3 have been consolidated after discussion and approval.
Lifecycle composition, nested fields and provider families remain next-feature
boundaries, not implemented capabilities.

## Approved consolidation outcome

- **A1 complete:** `place_cursor` carries projection progress on the existing
  expression work stack. Reads and assignment index operands share it without
  source-depth recursion. The native
  [depth proof](../../tests/integration/index_depth.php) executes 256
  nested read/write operands under the normal PHP settings, then one body increment.
  Existing array proofs retain target-before-RHS order, bounds and const checks.
- **A2 complete:** ordinary methods are concrete declaration requests independent
  of calls; dependent template methods remain demand driven. Both use the same
  member worker/join and downstream signatures/bodies, retaining original ASTs.
  [Preparation proof](../../tests/04_analyze/resolve_types/concrete_preparation.php)
  rejects unused ordinary parameter/return/body errors and leaves an undemanded
  dependent method uninstantiated.
- **A3 complete:** `resolve_types/Concrete_Preparation` owns the shared ready/pending
  requests. It admits each context once and indexes exact prerequisites. Ordinary
  nominal records and specialized type occurrences publish distinct readiness facts.
  Application/member reuse happens before computation. `instantiate/Instance_Store`
  owns the private working registry and incremental record index; workers read a
  fixed `Instance_View` per batch and joins alone accept private results. One
  immutable `Instance_Set` is published at completion. The old phase owner has been
  removed. The proof checks a 128-member chain, zero unchanged preparation work,
  one demand removal and retained context/body purity. Existing application/member
  tests cover reversed completion and rejected joins; ordinary-declaration results
  also have a provenance-rejection proof.

Focused post-change timings used the same isolated preparation setup as the audit,
with Xdebug disabled. Generic chains additionally exercise one-at-a-time demand
rather than only the now-eager ordinary declaration roots:

| Methods | Original ordinary chain | Consolidated ordinary chain | Consolidated generic chain |
|---|---|---|---|
| 100 | 3.1 ms | 1.64 ms | 1.56 ms |
| 200 | 9.4 ms | 3.65 ms | 3.08 ms |
| 400 | 33.1 ms | 7.38 ms | 6.38 ms |
| 800 | 118.1 ms | 14.73 ms | 12.31 ms |

These are local diagnostic medians of three preparations, not performance promises.
Work-count assertions carry the regression proof; tests do not assert wall time.

Validation: `python3 tests/run.py` passed **84/84 fixtures in 70.0 seconds**
with 10 parallel jobs, including PHP lint, native runtime/ABI and full/ThinLTO proofs,
cleanup and incremental paths. This was one full-suite run after focused development
checks. Actual threading and large-scale memory profiling remain outside this proof.

## Agreed boundaries for subsequent slices

- **Lifecycle:** keep semantic operations separate from their implementations.
  Provided, source-defined and field-composed implementations must join that contract.
  Storage, copying permission and resource ownership remain distinct. Preserve the
  existing lifetime analysis and explicit cleanup obligations. This consolidation
  does not relax managed-field eligibility or infer ownership from pointers.
- **Records:** ordinary and specialized records now share the readiness owner and
  normalized join. Nested field dependencies and lifecycle composition still need
  their own implementation; moving the queue alone does not support nested fields.
- **Provider preparation:** discover concrete demands, batch missing preparation,
  then accept a fixed package snapshot before dependent work. Do not generate under
  the current shared reader reservation or mutate packages inside semantic workers.
  Retain prepared specializations with the selected runtime version, target and
  configuration. Persistent specialization keys must use exact provider/argument
  identities, never session-local type IDs or hash uniqueness. Source-defined
  types as provider arguments need an explicit supported representation contract;
  this is not assumed by the initial consumption path.

These boundaries guide the accepted sequence; lifecycle composition, dynamic
storage and provider-family generation are not part of this consolidation.

## Original findings (baseline evidence)

### A1 — Medium: index checking reintroduces source-depth recursion

Owner: [Place_Checking::check_place()](../../src/04_analyze/check_bodies/handlers/places.php),
especially its call to `check_expression()` for the index operand, and
[Expression_Checking::check_expression()](../../src/04_analyze/check_bodies/handlers/expressions.php).

Calls and additions use explicit expression cursors, but an indexed leaf enters
`check_place()`, which starts another expression-checking invocation. An index
that itself contains indexing repeats this PHP call chain. The flat AST and
iterative lifetime/lowering traversal do not prevent this checking-stage recursion.

Evidence: a one-element `int32` array, zero-initialized in a source record, with
`$a->data[...]` nested in the index expression:

- 32 nested accesses compile successfully.
- 128 and 256 accesses fail with Xdebug's 512-frame stack-depth error in the
  normal local PHP configuration, rather than a source diagnostic.
- 128 accesses pass with Xdebug disabled; this is not a type or bounds rejection.
- A control containing 128 nested ordinary function calls passes with the same
  normal PHP configuration.

Consolidation: schedule index operands through the existing iterative expression
work, retaining location/projection progress in explicit cursors. Preserve
evaluation order and the target-before-RHS contract. Do not fix this by raising
Xdebug's limit or disabling it in the fixture. Native-stack growth would remain a
porting concern even without the PHP diagnostic.

### A2 — Important semantic decision: unused ordinary methods escape checking

Owner: [Signature_Resolver::participates()/body_participates()](../../src/04_analyze/resolve_types/signatures.php)
and [Concrete_Preparation](../../src/04_analyze/resolve_types/main_prepare_concrete.php).

An owned ordinary `function_symbol` participates only when it has a demanded
instance context. Thus method-call demand controls semantic checking as well as
concrete code production. The same dependency is appropriate for the supported
dependent template-method bodies, but ordinary methods already have concrete
contracts.

Evidence: this program is accepted:

```php
struct row {
    public int32 $value;
    public function bad($argument void): int { return 1; }
}
return 0;
```

Adding a call rejects the parameter: `void has no value`. An unused free function
with the same parameter is rejected. Similarly, an unused ordinary method
declared to return `int` with `return;` passes; calling it exposes the missing
return value diagnostic.

At the reviewed baseline, the [fixed-array implementation note](fixed_array_list_plan.md)
recorded demand checking for ordinary methods. This finding is therefore a semantic
consistency decision, not an undocumented implementation surprise. It should be
reconciled with the existing policy of diagnosing concrete function contracts
independently of use before we extend member/lifecycle behavior. Struct methods
are an agreed prototype extension; this audit does not claim the current upstream
struct implementation supports them.

Recommendation: prepare/check concrete ordinary methods independently of call
demand, as for ordinary free functions; keep dependent template members demand
driven. There is no need to add dead-code elimination merely to fix validation.

### A3 — Medium: readiness waves repeatedly scan completed work

Original owner: `instantiate/Instance_Preparation`, particularly its
`select_applications()`, `prepare_records()` and `prepare_members()` loops, plus
[Instance_Set construction](../../src/04_analyze/instantiate/data/result.php).
The replacement owner is [Concrete_Preparation](../../src/04_analyze/resolve_types/main_prepare_concrete.php).

Each discovery wave rescans the accumulated contexts and their applications or
members, including completed occurrences. A chain in which each method demands
the next creates one new method per wave, causing quadratic scanning despite a
linear number of declarations and demanded implementations. This is scheduling
overhead, not an unavoidable multiplication of template combinations.

Focused measurement: source records with 100/200/400/800 methods, each calling the
next and the last returning an integer. After one normal inspection compile,
instance preparation was run three times with fresh type stores and the same
fixed declarations/bindings; Xdebug was disabled for all timing samples.

| Demanded methods | Median preparation time |
|---|---|
| 100 | 3.1 ms |
| 200 | 9.4 ms |
| 400 | 33.1 ms |
| 800 | 118.1 ms |

These are local diagnostic timings, not a general compiler benchmark. Doubling
the chain approaches quadrupling this stage's time, consistent with the loops.

Two related selection costs are visible in the code:

- `reuse_applications()` restores explicit template applications, but not member
  occurrences. Member resolution runs again on an otherwise unchanged method
  graph when the semantic pipeline processes a body increment. Existing concrete
  contexts and later bodies can still be reused; the redundant preparation work
  is the concern.
- The retained-record branch of `prepare_records()` constructs an `Instance_Set`
  inside its loop; each construction rebuilds the index of all accepted records.
  This additional cost is established by inspection, not by the method-only timing.

Consolidation: process newly admitted contexts once, retain explicit pending
requests/prerequisites, and revisit only requests whose prerequisites became
ready. Reuse member demands under validated dependencies and batch record/index
updates. Keep the existing concrete workers, private results and deterministic
joins; no general-purpose scheduling framework or actual threads are needed.

## Original design-boundary assessment

### B1 — Lifecycle semantics currently identify runtime ABI implementations

[lifetime_contract](../../src/04_analyze/type_model/data/definitions.php)
holds `runtime_lifecycle_operation` for copying/destruction. Backend lifecycle
selection enumerates [Runtime_Package::lifecycle_operations()](../../src/01_prepare_inputs/load_runtime/data/package.php),
and [lowering](../../src/05_generate_code/lower/handlers/locals.php)
looks those operations up by link name. This is truthful for current opaque
runtime values. It does not yet represent source-defined or field-composed
lifecycle operations.

Likewise, [Record_Definitions](../../src/04_analyze/resolve_types/record_definitions.php)
requires the supported field contracts and creates value-copy/no-cleanup records;
body checking currently selects zero construction for structural values. These
restrictions are safe today. Merely permitting managed fields would not derive
their initialization, copy or destruction correctly.

Before accepted steps 1–2, agree a semantic lifecycle-operation contract whose
implementation can be provided, source-defined or composed from fields. Keep
storage representation, copy permission and resource ownership distinct. Retain
the existing per-value lifetime analysis and explicit cleanup obligations; do
not replace them with backend inference or teach lowering special container names.
This requires a coordinated model change, not relaxing a field-eligibility flag.

### B2 — Ordinary record preparation lacks the shared prerequisite path

At the reviewed baseline, `Instance_Preparation::run()` prepared ordinary records
in a batch before its template/member readiness loop. The consolidation now shares
that queue in [Concrete_Preparation](../../src/04_analyze/resolve_types/main_prepare_concrete.php).
[Record_Preparation::resolve()](../../src/04_analyze/resolve_types/records.php)
reads field annotations through the task's base catalog; its task does not carry
the accepted concrete definition view used by later annotation workers.

This matches the present scalar/array field subset. Nested ordinary source
records need readiness for their field types, including forward references.
Plan one record-dependency path for ordinary and specialized records, using the
same normalized results and canonical join. Do not add source-order retries or
a second nested-record materializer. Coordinate this with A3's selected-work
repair when designing the change, without silently implementing nested fields.

### B3 — Provider demand must precede acceptance of a fixed package snapshot

[Compiler_Session](../../src/compile/compile.php) imports and reserves the
runtime package before collection and holds it through linking. The
[adapter](../../src/01_prepare_inputs/load_runtime/package_adapter.php)
uses a nonblocking shared preparation lock; the
[preparation tool](../../src-runtime-preparation/prepare.php) needs an
exclusive lock to publish. Any package change currently selects a full rebuild.

The existing preprepared-package flow is coherent. Generating a missing provider
specialization from within a worker that uses that reserved package would
conflict with its reader reservation and fixed inputs. The current locks would
reject the request rather than permit publication underneath readers.

The already scheduled early discussion must define demand discovery, missing
specialization requests, preparation/publication, acceptance of a new fixed
snapshot, and exact dependencies for retained specializations. Keep generation
outside semantic workers and LLVM emission. Follow Simple C++ contracts and
retain runtime-version/target/configuration reuse; do not regenerate unchanged
bindings per project/file. This is a planned boundary, not a defect in today's
preprepared-package implementation.

## Smaller documentation consolidation (completed)

[The type-model guide](../type_model.md) now names implemented target-layout
preparation. Touched operation comments and process maps describe current imports,
ordinary-method checking and concrete preparation ownership. No general folder
migration was added.

## What to preserve

- Original ASTs stay unchanged; concrete environments and allocated identities
  are separate. Current identities use exact keys/allocated IDs, not hash uniqueness.
- Type-store replacement creates new rows rather than editing retained rows.
  Worker outputs and joins have explicit provenance and acceptance boundaries.
- Source/provider structural declarations converge before canonical materialization.
- Borrowed receivers converge with ordinary parameter passing; no list-specific
  lowering or fixture-capacity dispatch was found in the reviewed paths.
- Lifetime analysis determines ordered obligations. Lowering consumes them rather
  than inventing destruction from a pointer or ending ownership with a borrow.
- Layout and ABI preparation remain separate from source semantics, and runtime
  serialization remains owned by the package adapter.

## Suggested consolidation order and verification

1. Discuss A2's ordinary-method checking policy and the B1/B3 ownership/provider
   boundaries. Keep the accepted feature sequence; do not start dynamic storage.
2. Repair A1 through the existing iterative expression owner.
3. Repair A2's concrete-method participation after agreeing its semantics.
4. Repair A3's selected-work flow; account for B2 when choosing the owner, while
   leaving nested-field implementation to its accepted slice.
5. Refresh touched documentation and run the relevant integration checkpoint.

Use focused depth/order, unused-method diagnostic and preparation-selection
proofs during consolidation. Include native behavior, selected workers/joins,
snapshot purity and one full-build/one-increment case where contracts change.
Run the complete suite once at the resulting integration checkpoint, rather than
after each small edit.

The original audit validation used only isolated probes in `/tmp`: index depth/control cases,
ordinary/free method diagnostic comparisons and the bounded preparation timings
above. No compiler implementation or registered test was changed, and the full
suite was not rerun. Existing green tests support the baseline but do not rule out
these missing compositions. Actual threading, large-scale memory profiling and
new upstream semantic comparisons were not performed.
