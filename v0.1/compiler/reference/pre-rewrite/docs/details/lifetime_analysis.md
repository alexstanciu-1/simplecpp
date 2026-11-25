# Scalar, local and parameter lifetime analysis
Doc Status: supporting

Scope: this document describes the original scalar foundation. For later managed
lifetimes and borrowing, see [runtime cleanup](runtime_cleanup.md),
[copy construction](runtime_copy_construction.md) and
[source record borrowing](source_record_borrowing.md). The
[lifecycle investigation](lifecycle_contracts.md) records pending extensions.

Status: implemented in the PHP prototype for scalar temporaries and explicitly
initialized locals, including assignments, nested blocks, addition, branches, loops
and the manifest entry. [Operation/flow contracts](operations_and_control_flow.md)
define the current scalar subset. `compile()` stops
before `build_native` for inspection; [native builds](native_executable.md)
continue through executable publication when an output is requested.
Scalar locals and nested blocks now use the common [storage lowering](lowering.md)
and native path.

Incoming parameters and positional arguments now use this analyzer for the same
copyable, no-cleanup scalar contracts. The [lowering/native path](lowering.md) consumes these facts and prepared scalar
callable contracts; lifetime analysis itself does not choose an ABI.

The [handler organization and contracts](analysis_handlers.md#lifetime-analysis)
describe checked-statement, value and local-lifetime extension points. Block
traversal and state remain with the worker; `Local_Flow` remains an independent
analysis process within this stage.

## Facts and ownership

The shared type definition supplies `copy: value` and `cleanup: none` for
supported scalars. Void supplies no value-lifetime contract. The analyzer reads
these facts through the checked body's immutable type dependencies; it never
infers lifetime behavior from type names, widths or storage representations.

[`Lifetime_Analyzer`](../../src/04_analyze/analyze_lifetimes/main_analyze_lifetimes.php) selects and
joins work; each [Lifetime_Worker](../../src/04_analyze/analyze_lifetimes/body.php)
consumes one fixed `Checked_Body`. Its separate `Analyzed_Body` refers to that
exact body and contains a flat `lifetimes` list of `value_lifetime` records:
value ID, statement ID, end kind and optional consuming call ID. Value/statement/
call IDs are one-based indexes in the checked body. Records follow consumption
order, with nondecreasing statement IDs; this can differ from production order.
The type contract and source/typed records are shared, not copied into these rows.

Current temporary lifetimes fit within one statement:

- A discarded scalar result ends at the statement boundary, with no cleanup
  under its verified contract.
- A returned scalar is copied out under its value-copy contract, then the local
  temporary ends. This is a language operation, not a native ABI passing mode.
- An initializer or assignment copies the RHS into its local destination, then
  ends that temporary with `local_copy`. The checked statement already identifies
  the destination; the analyzer does not duplicate the write instruction.
- An argument temporary ends with `argument_copy` when its consuming call takes
  the value copy. `consumer_id` identifies that call. The temporary stays live
  while later arguments are evaluated.
- A conversion input ends with `conversion_input`; `consumer_id` identifies the
  conversion's result value. That new value has its own lifetime until used.
  Statement-boundary ends keep consumer ID zero.
- Reading a local creates a copy under its type contract, including when the
  copy is discarded. The source binding stays live.
- Void calls remain in the checked body's execution segments and produce no
  lifetime row. Bare returns likewise create no value.

The analyzer selects reachable checked blocks and uses the shared
`Expression_Order` traversal for each expression. Operands evaluate left to
right and remain live until their call, conversion or arithmetic consumer.
Arithmetic inputs end with `operation_input`; condition results end with
`condition`. Each static temporary has one consumer. Loop execution repeats the
same static operations; it does not allocate per-iteration analysis rows.

`reachable_statement_count` counts statements in `reachable_blocks`; it is not
a source prefix. Unreachable checked source remains available for diagnostics.
A void body may fall through. Calls are treated as returning normally;
interprocedural flow is not inferred by inspecting callee bodies.

Lowering consumes these block selections and exact consumption facts. For this
scalar subset, values require copy:value and cleanup:none; no managed cleanup
obligations are needed. Managed objects use the later cleanup extension linked above.

## Local bindings and scope exits

The worker keeps a private live-local index and an initialization-order stack.
Incoming parameter IDs 1..count become live in the root scope before any
statement, after their value-copy/no-cleanup contracts are checked, even when
unused. Body-local initialization evaluates/copies its RHS before making the
binding live. Both use the same live index and exit stack.
Assignment requires a live initialized destination; it reads the RHS before
replacing the stored scalar value. Self-assignment is therefore valid. Under the
verified `cleanup: none` contract, replacement needs no destruction action and
does not create another binding lifetime. Reads must refer to live locals with
the checked type. Ownership transfer is deferred; borrowed references use the
later source-record and provider-borrowing contracts linked above.

`Checked_Body` supplies executable statement scope IDs and block ranges; its
shared name-resolution owner supplies local/scope identity. The analyzer uses
those contracts without walking the AST or repeating name lookup. At a normal
block boundary, locals end in reverse initialization order. A return first
establishes its result copy and then ends all remaining live locals, inner/recent
first. Void fallthrough closes the root scope in the same way as any other normal
scope exit. Unreachable declarations and uses receive no runtime lifetime facts.

The private `Local_Flow` worklist computes definite initialization across edges,
intersecting incoming states at joins and removing out-of-scope bindings. The
worker then verifies operations using the converged inputs. These sparse entry
maps are discarded after analysis.

`Analyzed_Body.local_lifetimes` contains static exit facts: local ID,
initialization statement ID, block ID, `end_after_statement` and exit kind.
The end field is a source statement boundary within that block. A local can
have several possible exits; loop-backedge exits execute on each iteration.
Initialization zero means parameter entry. `local_for(id)` returns the first
exit, sharing the initialization information common to every exit of that local;
all exits remain in `local_lifetimes`. It returns null for unreached declarations
and rejects invalid IDs. This is distinct from physical slot lifetime.

No cleanup instructions are synthesized for the supported no-cleanup scalars.
Owned values will need explicit replacement/exit actions and transfer rules;
these lifetime bounds alone do not advertise that support.

## Selection, joins and acceptance

Select one task per new/replaced checked body, or every current body when
`full_rebuild` is set. Body identity is the complete dependency boundary: body
checking already invalidates results when source, bindings, signatures or shared
type definitions change. An unchanged checked body reuses the exact prior
analysis. Workers read fixed inputs, allocate private outputs and never wait for
callee analysis. Serial execution uses these same units.

The join validates selected membership, completeness and exact input identity,
orders results by current checked bodies, and excludes removed owners regardless
of the full flag. The session accepts observed stage snapshots together
after success in `Compiler_Session::observed`, a complete `Compiler_Snapshot`
including lifetimes, the separate [backend context](lowering_inputs.md) and
[lowered bodies](lowering.md). Failed work cannot replace prior
observations. Native publication separately adopts a complete candidate.

`--debug=json` exports `lifetimes` by callable symbol: reachable statement count,
fallthrough, `lifetimes` for temporary end facts and `local_lifetimes` for binding
bounds/exit reasons. These are analysis results, separate from lowered slots
and emitted LLVM instructions.

## Limits and proof

Borrowing, moves, managed cleanup and exceptions remain outside this lifetime slice.
[Addition/control-flow proofs](operations_and_control_flow.md#proof) cover the
new expression and graph behavior. Do not extend
the temporary-within-one-statement assumption to local storage or those future
features. Physical storage placement and ABI adaptation belong downstream.

[`lifetime_analysis.php`](../../tests/04_analyze/analyze_lifetimes/lifetime_analysis.php) proves
source-to-analysis behavior, entry/void flow, copied returns, discarded results,
unreachable exclusion, independent workers, deterministic/invalid joins, zero-work
reuse, selective edits, full provider refresh, removal, failure/repair, source
anchors, unfamiliar type names/widths, large bodies and fresh-build equivalence.
The CLI increment simulation verifies exported facts through both refreshes.

[`local_lifetimes.php`](../../tests/04_analyze/analyze_lifetimes/local_lifetimes.php) proves copying
into/from locals, self-assignment, shadowed identities, normal exits, early-return
unwind, void fallthrough, unreachable exclusion, invalid live-local/contracts,
reversed workers, joins, reuse, full/fresh equivalence, edits, removal and repair.
It also compiles and executes a nested scalar-return program through the common
native path. [Local lowering proofs](../../tests/features/local_lowering.php)
add native execution of local reads/writes, scopes and early returns.

[Parameter lifetime proofs](../../tests/04_analyze/analyze_lifetimes/parameter_lifetimes.php) cover
incoming parameter reads/assignments, nested argument consumption and retention,
shadowing, early return, empty/bare-return/normal void exits, unreachable values,
independent reversed workers, unchanged reuse, full/fresh equality, body edits,
withdrawn owners, malformed consumption graphs, missing value contracts, exports,
deep/wide calls, the lowering handoff and failure followed by native repair.
