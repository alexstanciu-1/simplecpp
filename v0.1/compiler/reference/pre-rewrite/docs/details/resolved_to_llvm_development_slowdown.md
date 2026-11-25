# Why development slowed between resolved syntax and LLVM
Doc Status: supporting

Date: 2026-09-07. Scope: development effort in the previous compiler's active
v2 path, with selected August 10–12 commit history. Reference repository:
`../../../simple_cpp_compiler`, HEAD
`9776900f6c305729ae6ef8474052363508913315`, including its existing working-tree
changes. No old compiler code was changed and no builds or benchmarks were run.
This is historical evidence. Current scope and composition gates are in the
[first-slice plan](first_slice.md), rather than the old implementation inventory.

## Conclusion

The strongest explanation is a missing, consistently consumed executable-body
contract. The compiler had syntax, symbols, body summaries, capability checks,
requests, lowering plans, and emission rows, but these did not uniformly answer
all execution questions before LLVM generation. Selected backend routes still
resolved source forms and reconstructed execution order.

As a result, simple feature goals produced source-shape-specific routes. New
combinations needed additional collection logic, authorization plumbing,
operand sidecars, and emitter state. Later refactoring corrected some of those
routes, adding another cycle of implementation and validation. Build/tool-flow
delays amplified the cost.

This is a causal assessment supported by code and history, not a reconstruction
of total developer hours or a measured ranking of runtime bottlenecks. It does
not establish PHP++ or LLVM itself as the primary cause.

## 1. The backend input was not uniformly a resolved executable body

In [the entry backend][entry], `backend_emission_for_entry()` tries direct-call,
scalar-local-body, and literal-return paths. Each receives the frontend model
and source text. The direct-call route invokes
`project_reference_resolution::append_direct_call_from_frontend()`, which
extracts the callee name and arguments from syntax before resolving the call.
See [reference resolution][references].

[Module composition][composition] calls
`parameter_function_text_from_symbol_rows()` in [the LLVM writer][llvm]. That
method collects a frontend body summary, requires one return statement with a
binary-expression value, resolves operand types/operator metadata, then prints
the function's LLVM instructions. It is performing semantic work at emission.

Inference: calling the input a “resolved tree” overstates what these routes can
consume without further interpretation. A new expression or call form needs
work at multiple points because the semantic handoff is incomplete.

## 2. Operand and body shapes limited composition

[Scalar assignment collection][collection] recognizes a literal RHS or a
binary RHS whose left operand is a known local and whose right operand is a
literal or local. It does not recursively produce a general value for each
operand in this route. Therefore support for a binary operation here does not
automatically support nested binary expressions or call results as operands.
This is source inspection of the selected route, not a newly executed whole-
compiler rejection test.

[ScalarBodyBackendCollection][body] carries parallel vectors for assignment
sources, target locals, literal values, binary left locals, and binary right
values. Parallel vectors can be a valid storage choice; the difficulty here is
that the logical representation is specialized to particular operand shapes.

`scalar_local_body_emission()` also keeps individual if/while/for/return bundles.
Its assignment helpers pass many related vectors and use source-row intervals
to place effects. [Loop text emission][loops] closes loops and emits transfers
when a particular source-row id is encountered. Those are signs that the sink
is still reconstructing execution structure.

A reusable executable representation should explicitly carry values, places,
ordered effects, blocks/structured control, and transfer destinations. Source
ids should preserve provenance; downstream stages should not need to infer
control flow from source-row positions.

## 3. History shows the cost of adding combinations

These are actual commits, inspectable with `git show <commit>` in the old repo:

| Commit | Change | Relevant observation |
|---|---|---|
| `cbeafb45` | Accept one-level while break | Changed five compiler implementation files plus docs/expectations. |
| `a9a4143a` | Accept one-level continue targets | Again changed entry, requests, local lowering, loop text, and transfer analysis. |
| `ca3392dc` | Accept scalar for break transfer | Added another control-form combination. |
| `75cf2f5f` | Sequence scalar loop transfers after assignments | Added `control_flow_flag_body_assignments_then_break_id` and the corresponding continue flag, plus delayed-transfer emitter handling. |
| `db62ce4c` | Replace loop shape flags with terminator rows | Began replacing combination names with an execution concept. |
| `844998ba` | Pass loop body sequences to backend requests | Replaced duplicated entry-side bundles with the sequence object. |
| `661b587f` | Add CF loop sequencing proof | Added source and expected artifacts for the resulting behavior. |

The [drift-correction note][correction] explicitly records removal of
`assignments_then` and old control flags. The [refactor campaign][campaign]
describes body facts near backend entry, acceptance through local flags, and
shape-specific coverage helpers as problems.

These changes included useful repairs and real tests. The lesson is not that
small goals failed. The goals lacked a sufficiently strong requirement that
accepted features combine through the same executable representation.

## 4. More protocol layers did not finish the missing decisions

For example, [lowering_plan::from_entry_and_backend_requests()][lowering]
primarily accepts ready requests into work references and records blockers.
That is useful authorization/order infrastructure. It does not itself replace
the upstream source-form recognizers with a general expression/body lowerer.

[Semantic body capability consumers][consumers] improve reuse of readiness
checks, but receive `ScalarBodyBackendCollection` and append local, assignment,
binary, return, and condition capability consumers. This is a capability plan,
not the complete program body with arbitrary value dependencies and blocks.

The distinction matters: metadata answers which operations are allowed and how
they map to runtime/target contracts. An executable body answers which values
are computed, in which order, in which blocks, with which effects and exits.
Both are needed, but one cannot substitute for the other.

Inference: every new case had to keep several projections aligned while still
finishing execution decisions near the backend. Splitting files or adding
another descriptive row family could leave that central problem unchanged.

## 5. Validation and build overhead amplified the structural cost

The [TDD runner][runner] invokes the vendor build unless
`SCPP_SKIP_COMPILER_BUILD=1` is set. The [campaign guard][guard] invokes separate
valid and invalid BASE runs. The underlying build can reuse outputs, so these
calls do not imply a full native rebuild every time; shared-build controls also
existed and were used in recorded runs.

The [development-cycle notes][cycle] document that cold checks can take minutes
and allow 600 seconds or more for some implementation builds. Those are
historical guidance/allowances, not fresh measurements or typical-run averages.

More concretely, [the August 11 correction note][correction] reports broad STAN
unresolved-type noise and a saved successful build of 965 ms whose wrapper was
interrupted after about 60 seconds without output. This is reported historical
evidence of tooling friction, distinct from LLVM emission time. The saved raw
build logs were not available in the inspected `compiler/build` tree.

Structural guards and detailed snapshots remain useful. However, checks for
expected helper names, source phrases, or “boundary complete” documentation
cannot establish compositional behavior. [The lowering boundary audit][audit]
contains such structural checks. A passing small source example likewise
proves that example, not arbitrary combinations of accepted constructs.

## Implication for the new compiler

Keep small verifiable goals, with a composition check when introducing a new
operation or context. Examples include nested expressions, a call result used
in arithmetic, and a conditional transfer after an effect inside a loop. Select
one relevant combination per slice; do not create an exhaustive matrix by
default.

The shared body should make these questions explicit:

- What typed value does this operation produce, and which values does it use?
- Which stable place does a load/store address?
- What is the evaluation/effect order?
- Which block executes next, and what terminates the current block?
- Which callable/signature/ABI contract does a call use?
- Which cleanup actions are owed on an exit?

Illustrative lowering of `(a + b) * c`, after name/type checking:

```text
v1 = load place_a
v2 = load place_b
v3 = add v1, v2
v4 = load place_c
v5 = multiply v3, v4
```

This illustrates a concept, not a required file format or a proposal to build a
large optimizer first. Mutable locals can initially use explicit loads/stores;
we do not need our own optimizing SSA construction to establish compositional
lowering. A resolved expression tree with a correct recursive lowerer is also
valid—the failure was an incomplete execution contract, not simply using trees.

Do not reproduce the old request/coverage/emission layers wholesale. Retain
each boundary only when it owns a real decision, representation, or reusable
output. An LLVM API sink would still need this contract; changing the text
writer alone would not remove the source-shape problem.

Validation for this note: inspected cited source and historical diffs, checked
local links, and preserved a distinction between observed code, reported
historical timings, and causal inference. No compiler behavior changed.

[entry]: ../../../simple_cpp_compiler/compiler/src/compile/backend/compiler_entry_backend.phs
[references]: ../../../simple_cpp_compiler/compiler/src/compile/model/project_reference_resolution.phs
[composition]: ../../../simple_cpp_compiler/compiler/src/compile/backend/backend_module_composition.phs
[llvm]: ../../../simple_cpp_compiler/compiler/src/compile/backend/llvm_text_from_plan.phs
[collection]: ../../../simple_cpp_compiler/compiler/src/compile/backend/scalar_body_statement_collection.phs
[body]: ../../../simple_cpp_compiler/compiler/src/compile/backend/scalar_body_backend_collection.phs
[loops]: ../../../simple_cpp_compiler/compiler/src/compile/backend/backend_loop_text_emission.phs
[lowering]: ../../../simple_cpp_compiler/compiler/src/compile/backend/lowering_plan.phs
[consumers]: ../../../simple_cpp_compiler/compiler/src/compile/semantic/semantic_body_capability_consumers.phs
[correction]: ../../../simple_cpp_compiler/compiler/docs/future/90_percent_functionality/definition_driven_drift_correction_plan_2026_08_11.md
[campaign]: ../../../simple_cpp_compiler/compiler/docs/future/90_percent_functionality/architecture_refactor_campaign_plan_2026_08_11.md
[runner]: ../../../simple_cpp_compiler/compiler/tools/run_90_pure_tdd.php
[guard]: ../../../simple_cpp_compiler/compiler/tools/run_90_campaign_start_guard.php
[cycle]: ../../../simple_cpp_compiler/compiler/docs/development_cycle_rules.md
[audit]: ../../../simple_cpp_compiler/compiler/tools/lowering_plans_boundary_audit.php
