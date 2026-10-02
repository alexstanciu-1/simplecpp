# Preparation / C++ emission audit
Doc Status: supporting

Audited 2026-10-02 at `091bf094483ce840c29e5e7543ff6197a330a1ec`.
This report completes the bounded inspection requested by the
[catalog prerequisite gate](../catalog/README.md#prerequisite-gates).
**Inspection is complete. PE-01 is subsequently resolved for the agreed bounded
copy policy, PE-02 by registry normalization, and PE-04 for supported straight-line
bodies. PE-03 (duplicate declarations) is explicitly deferred debt.** No compiler structures,
semantics or generation behavior were changed by this audit.

## Result and boundary

The implemented chapter 02 scalar path selects operand/result types, conversions,
operator meanings and target eligibility during preparation. C++ emission generally
consumes those decisions. No need was found to move C++ temporary allocation,
parentheses, runtime helper spelling, escaping, include selection or declaration
ordering into semantic facts.

Four concrete preparation gaps were reproduced. Programs pass preparation and
emission, then fail generated-C++ checking: copy eligibility, entry-return carrier
eligibility, duplicate declarations, and non-void fallthrough. These are missing
preparation guarantees, not evidence that the emitter should acquire more semantic
validation. The last two overlap deferred general validation; they are kept distinct
from chapter 02 operator selection. Existing bounded fixtures do not prove these
additional input shapes correct.

The audit covers all six files under `05_backend/cpp/`, attached preparation facts,
their declaration/body/expression/type owners, operator and conversion selection,
record dependency scheduling, and the entrypoint's one-file generation boundary.
LLVM, legacy STAN, general lifetime analysis, additional operator families, runtime
policy changes, modular C++ output and repairs are outside this audit.

## Covered paths

| Path | Preparation owns | C++ owns / audit conclusion |
| --- | --- | --- |
| Integer, float, bool and string literals | Canonical type; integer magnitude; exact float spelling; decoded bytes | Target literal spelling, escaping and length-aware strings. Representation guards are backend invariants, not source type inference. |
| Locals, constants and fields | Resolved declaration/definition identity; field selection and addressability | Stable generated names and member spelling. No source-name lookup occurs here. |
| Explicit casts and interpolation | Conversion operation, source/target/result type; scalar insertion permission | Render chosen conversion; append parts in order with temporaries. |
| Unary, binary, comparisons, logic and power | Normalized operator, exact operand policy/conversions, semantic operation and result type | Render selected operation. Bool short circuiting, XOR/three-way snapshots and power helper calls implement already agreed operation contracts. |
| Assignment and chains | Declaration vs reassignment, resolved storage, write conversion and snapshot result type; declarations confined to root chains | Lift chain declarations, store once, return value snapshots. Target AST dispatch does not re-decide storage eligibility. Copy admission needs PE-01. |
| Increment/decrement and compound updates | Writable local/parameter eligibility, old/new operation, computation and write-back conversions | Emit the chosen mutation and a single target binding. Current identity target conversion preserves the place. |
| Function calls | Resolved callable/signature, arity, reference mode and exact storage compatibility, value conversions | Left-to-right argument temporaries and reference bindings. Copying needs PE-01; no new argument-order fact is required while this is the uniform language rule. |
| Function and entry returns | Explicit return's value requirement and typed conversion; numeric/bool entry-family check | Function conversion or native main exit adaptation. PE-02 and PE-04 expose missing guarantees. |
| Typed default initialization | Declared value type and absent initializer | Use the type's existing default construction. The scalar control compiles. Constructor-overload selection is not currently supported or silently performed. |
| Struct layouts and signatures | Field/parameter types, field value-storage capability, recursive declaration completion | Type spelling, record ordering, prototypes. PE-01 and PE-03 expose missing declaration guarantees. |
| Retained fragments/publication | Ready work, dependencies, effective versions and changes | Render selected fragments; assemble live includes/output; consume handoff on success. No semantic reselection identified. This is inspection, not a new incremental stress proof. |
| Brackets and unsupported expression contexts | Shared deferred-overload rejection for both bracket arities; eager-effect and nested-target restrictions | No bracket lowering is published. Negative probes stop before emission. |
| Blocks / multi-file output | Standalone block probe rejects in parsing; child environments remain deferred | Block emission exists, but it is not evidence of a supported source-level scope feature. One prepared source file remains an explicit output capability limit. |

Read-only calls to `Type_Preparation::canonical()` / `source_record()` from the
backend query settled identities. Their class location alone does not mean emission
is performing type resolution. Similarly, `CPP_Types` maps an existing type identity
to a representation; it does not choose operand compatibility.

## PE-01 — copying is admitted without copy permission

**Subsequent bounded resolution (2026-10-02):** source records now derive storage
and copy capabilities after field preparation. A common semantic query requires
completion and records dependencies; runtime/language types use registry contracts.
Owned initialization/assignment, value arguments, returns and assignment snapshots
require copy permission. Reference-only use remains valid, and no implicit move is
introduced. Template constraints consult the same query, including cached applications.
The historical reproductions below now reject during preparation.

[Capability proofs](../../tests/capabilities.php) cover direct/nested/empty records,
no inferred hash/comparison operation, typed/inferred copies, field writes, arguments,
returns, snapshots, reference controls, cleanup, nested-field edits, cached template
constraint revalidation and recovery. Focused PHP suites and two generated C++
programs validate this slice; the native compiler itself was not rebuilt.

Runtime-template conditional copyability and effective class-like modifier contracts
remain explicit [type-system debt](types.md), outside this bounded repair. These
limits prevent treating this result as unrestricted ownership/copy support.
The original audit evidence below remains unchanged for provenance.

**Priority: high; before expanding value-producing operators/overloads.**

Reproductions include:

```php
$a unique<int>; $b = $a;
$a unique<int>; $b unique<int>; $b = $a;
function f(unique<int> $a): void {} $a unique<int>; f($a);
function f(unique<int> &$a): unique<int> { return $a; } return 0;
struct Box { unique<int> $p; } $a Box; $b = $a;
```

All five prepare and emit; Clang rejects deleted copy construction/assignment.
A reference-only `unique<int>` call compiles, so the type itself is not the problem.

[Conversion_Preparation::decide](../../04_analyze/conversions/preparation.php)
returns identity for matching types without establishing copy permission. Inferred
bindings can bypass conversion selection entirely. Call argument temporaries and
assignment snapshots then assume a legal value copy.
The [runtime type registration](../../03_parse/types/register.php) correctly omits
`copyable_value` for `unique`. However,
[define_source_structure](../../03_parse/types/catalog.php) grants every source
struct that capability before its fields establish whether copying is possible.
The [runtime unique wrapper](../../../../runtime/include/scpp/unique_p.hpp) deletes
copy operations. This is observable inconsistency, not a proposed ownership policy.

**Owning repair:** distinguish exact type identity from permitted value transfer at
binding, argument, return and snapshot boundaries. Establish truthful source-record
capabilities before consumers rely on them. Do not add emitter-side type-name checks,
implicit moves, or reject valid reference use as a shortcut.

**Discussion boundary:** truthful record capabilities touch type registration,
record preparation/dependency invalidation and conversion/value-boundary ownership.
This is broader than adding one check in the C++ emitter. Discuss whether the first
slice conservatively rejects non-copyable value use or introduces explicit transfer
facts; do not silently introduce move semantics. Required proofs include inferred
and typed copies, reassignment, arguments, returns, snapshots, reference controls,
and incremental changes to a record member's copy capability.

## PE-02 — entry-return adaptation lacks an exact carrier contract

**Subsequent resolution (2026-10-02):** the agreed
[storage-modifier rule](../../../../specs/type_use_modifiers.md) makes `value<int>`
semantically equal to `int`. Registry normalization removes the redundant effective
modifier before preparation consumers see it. The existing scalar entry adaptation
then applies correctly; no special emitter unwrapping or new return fact is needed.
The new `value_entry` generated-program fixture returns 7. The original audit
reproduction and evidence below remain unchanged for provenance.

**Priority: high; small bounded repair candidate.**

```php
$a value<int>; return $a;
```

Preparation and emission succeed; generated C++ calls `.native_value()` on
`scpp::value_p<scpp::int_t<>>`, which has no such member. Ordinary `return 3.5;`
and integer entry returns compile.

[Type_Preparation::entry_return_type](../../04_analyze/prepare/semantics/types.php)
checks the canonical family but ignores the by-value modifier.
[Body_Preparation::prepare_return](../../04_analyze/prepare/semantics/bodies.php)
retains no entry conversion on `prepared_return`.
[CPP_Generator::generate_return](../../05_backend/cpp/generate.php) infers entry
handling from generation context and always emits the native-value extraction/cast.

**Owning repair:** prepare an exact supported entry-exit boundary, including modifier
eligibility; reject unsupported carriers before emission. If richer entry returns
are desired, retain their chosen adaptation explicitly. Native `int` spelling and
main's ABI remain backend concerns. Do not assume `value<T>` should automatically
unwrap. Discuss any new return-kind/adaptation fact before changing structures.
Proofs should distinguish bare/typed/entry returns, scalar controls, modifiers,
unsupported results and recovery after a failed edit.

## PE-03 — duplicate declarations can reach C++

**Status: deferred debt (explicitly retained, 2026-10-02).**
**Priority: medium; declaration validation, not operator dispatch.**

Owner: declaration preparation/publication. Duplicate fields, parameter names and
currently non-overloaded function declarations can still pass preparation when
unused and fail in C++. This slice does not repair or suppress them. Close this debt
with preparation diagnostics and incremental introduction/removal/recovery proofs;
retain the original candidates rather than deduplicating or renaming declarations.

```php
struct A { int $x; int $x; } return 0;
function f(int $x, int $x): void {} return 0;
function f(): int { return 1; } function f(): int { return 2; } return 0;
```

All prepare and emit. Clang rejects duplicate fields, duplicate parameter names and
function redefinition. Lookup correctly rejects ambiguous uses elsewhere, but an
unused invalid declaration can bypass that lookup.

[Declaration_Preparation](../../04_analyze/prepare/semantics/declarations.php)
retains ordered declarations and duplicate field candidates; output renders them.
Do not fix this by silently deduplicating syntax or renaming source declarations.
The declaration/publication preparation owner should establish validity for the
currently supported non-overloaded declarations while preserving candidates for
inspection and diagnostics. Overload support is a separate language slice.

Required proofs: unused and used duplicates, separate valid scopes, duplicate
introduction/removal, recovery and withheld completed output. General validation was
already deferred; this finding makes the concrete generation consequence explicit.

## PE-04 — non-void fallthrough is not established

**Subsequent resolution (2026-10-02):** body preparation now rejects a non-void
function whose supported straight-line statement sequence can reach its end.
Empty bodies, local-only bodies and discarded value-returning calls all reject,
including unused functions. A validated unconditional return ends the path; later
statements still undergo normal semantic checking. Existing internal block nodes
compose completion through the same traversal. Void functions and program entry
may fall through; standalone block syntax remains deferred.

The result is checked before body work settles, so successful preparation certifies
completion without a new retained flag or emitter-generated fallback return.
[Focused proofs](../../tests/fallthrough.php) cover return-value requirements,
non-void scalar/record cases, void/entry controls, return removal, no-edit retries,
signature-only changes, independent-body preservation and recovery. Eight focused
PHP suites pass through FPM; six generated C++ controls compile with
`-Werror=return-type` and run with expected exits. No native compiler rebuild was run.

Branch, loop, exception and non-returning-call completion rules remain future
control-flow work. The historical reproduction and audit evidence below are unchanged.

**Priority: medium; required before broader control-flow generation.**

```php
function f(): int {} return 0;
```

Individual returns are checked, but the body can complete without one.
[Body_Preparation::prepare_body](../../04_analyze/prepare/semantics/bodies.php)
only traverses statements; no completion guarantee is retained. C++ emits the empty
non-void body. Clang reports `-Wreturn-type`; the audit deliberately promotes this
warning with `-Werror=return-type`. Without that flag this is not necessarily a C++
compilation failure. The probe does not call the function or execute undefined behavior.

**Owning repair:** establish body completion in preparation. Start with the supported
straight-line model; branch/loop rules belong to the later control-flow slice.
Do not invent a default return value in emission. An explicit void return control
passes. Closure requires straight-line success/fallthrough, void/entry distinctions,
and later branch/loop completion proofs when those forms become supported.

## Checks that did not become findings

- Record cycles already fail `Preparation_Worker::declaration` through completion
  dependencies. Both backend cycle guards are defensive checks; moving the same
  diagnostic again would not repair a missing semantic owner.
- Bracket overloads fail preparation deliberately; neither arity implies append.
- Calls/updates in eager operators are rejected by the current operand whitelist.
  There is no general effect record, but existing restrictions make its absence an
  explicit extension gate, not proof that current scalar lowering selects semantics.
- A standalone `{ $x = 1; } return $x;` fails parsing. Block nodes share preparation
  context while C++ emits braces, but this was not reproduced as an accepted source
  bug. Child environments must be addressed with
  [NOTE-033.b](../catalog/01_literals_locals.md#note-033b--nested-statement-blocks)
  before enabling control-flow/block syntax.
- Current runtime overflow and unchecked-shift behavior remains the agreed contract;
  this audit does not authorize changing it.

## Evidence and reproduction

[Machine-readable evidence](preparation_emission_audit_evidence.json) retains the
21 exact source inputs, phase outcomes, C++ diagnostics, host probe source, audited
revision, source hashes and toolchain versions. Temporary complete artifacts and
command journals are under `/tmp/my-try-emission-audit-final-20261002/`.

The host probe invokes `Compiler::init`, `sync([])`, `prepare()` and `cpp()` separately,
recording the stage on failure. It runs via the default FPM executor with 12 task
slots. For emitted output, each task runs:

```bash
clang++-18 -std=c++20 -Werror=return-type -I runtime/include \
  -fsyntax-only PATH_TO_FIXTURE/main.cpp
```

To reproduce an individual case, save its `source` from the evidence JSON as
`main.phs` in a fresh directory, save `probe_php` as a PHP file, and pass that directory
as its first argument through the shared runner. The saved probe's boot path points
to the audited checkout; change that path when reproducing elsewhere. Then run the
Clang command above on emitted output. The same host probe can be run directly by
CLI when specifically comparing executors.

Results: **6 emitted programs passed C++ checking; 10 exposed the four findings;
4 rejected during preparation; 1 rejected during parsing.** None of these 21 inputs
first failed in C++ text emission. The missing-return case is the warning-policy
exception described above; the other nine C++ failures are hard errors.

These are diagnostic probes, not 10 newly failing previously passing regression
fixtures. No full regression sweep, native compiler rebuild, generated executable
run or lifetime/performance proof was performed for this documentation audit.
The earlier chapter checkpoint remains separate evidence.

## Suggested repair order

1. PE-02: resolved by the agreed registry-based modifier normalization above.
2. PE-01: bounded copy admission and source-record capabilities are resolved above;
   explicit transfer and conditional runtime-template contracts remain separate gates.
3. PE-03: close duplicate-declaration admission within declaration preparation.
4. PE-04 is resolved for supported straight-line bodies. Before expanding chapter
   03, add branch/loop completion rules and child-block environments.

Keep current hard rejections until the corresponding semantic facts exist. New
operator providers or side-effectful operands must not bypass those gates. Completing
this audit does not mark its repairs, deferred catalog features or STAN as complete.
