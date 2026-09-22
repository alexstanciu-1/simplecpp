# Addition and scalar control flow
Doc Status: supporting

Status: implemented in the PHP prototype. The six semantic owners and ordinary
phase calls remain separate. This implements the addition and control-flow rows
of the [foundations tracker](../planning/compiler_foundations.md), under the existing
[type model](../type_model.md). Addition now shares the
[binary-operation path](binary_operations.md) with integer `<` and boolean results.

## Agreed language contracts

Addition accepts two values with the same canonical type ID when that type's
provider definition explicitly supplies `addition: "wrapping"`. The result has
that same type. Integer addition wraps modulo 2^width for signed and unsigned
integers. Representation equality alone grants neither operation support nor
mixed-type compatibility. Literals retain their configured default type; existing
boundary conversions still apply to assignment, arguments and returns after an
operation is checked. Mixed-type addition remains unsupported. Same-type integer `<` is now supported
through an explicit comparison capability; other operators remain deferred.

Operands evaluate left to right. `+` associates left; parentheses group an
expression. Calls, locals, literals, conversions and additions compose through
the same expression path. A void call cannot supply an operand.

`if (condition) { ... } else { ... }` and `while (condition) { ... }` require
blocks; `else` is optional. Conditions accept integer values: zero is false,
nonzero is true. Conditions are evaluated on entry to an if and on every loop
test. Branch and loop bodies establish lexical scopes; locals do not escape
them. Loop-local initialization runs on each iteration.

All source is checked, including unreachable source. Every reachable exit from
a non-void callable must return a value. Both condition outcomes are considered
possible; there is no constant-condition folding or proof of infinite loops.
Consequently, a non-void body consisting only of `while (1) { ... }` still needs
a return after the loop. Boolean results and comparisons now use this same flow.
Short-circuit operations, else-if shorthand and break/continue remain unsupported;
[managed cleanup](runtime_cleanup.md) was added through the lifetime owner.

## Operation ownership and evaluation

The catalog owns integer addition semantics. The checking-owned
[Operation_Resolver](../../src/04_analyze/check_bodies/utilities/operations.php)
selects an exact operand/result contract and the implemented `compiler.integer`
`add_wrap` primitive. It does not infer support from type names. Contracts are
shared for repeated operator/operand-type combinations within one checked callable.

An `operation_value` holds two earlier value IDs and the selected contract.
There is no retained operand array per addition. Calls keep their existing flat
argument ranges, and conversions keep their unary input. Type dependencies
retain the definitions that authorized the selected operation; a catalog change
uses the existing type-context invalidation and full-work gate.

[Expression_Order](../../src/04_analyze/check_bodies/utilities/evaluation.php)
traverses these completed checked facts with explicit depth-bounded cursors. It
visits operands before their consumer and preserves the checked call-effect
ranges. It produces transient evaluation items, not a retained event dataset.
Lifetime analysis owns production/consumption rules; lowering owns instruction
construction. Both use this traversal, replacing their separate call/conversion
traversals. This is checked-expression traversal, not a shared AST walker.

An addition operand ends with `operation_input`, whose consumer ID is the result
value. The result has its own lifetime. Lowering translates the selected primitive
to a typed binary instruction carrying `add_wrap`; LLVM emission emits integer `add` without signed or
unsigned no-overflow flags. The native proof checks full-width overflow results.

## Typed control-flow boundary

Checking builds blocks during its existing syntax traversal through
[Flow_Builder](../../src/04_analyze/check_bodies/flow.php). A block owns a
contiguous range of checked statements and an explicit jump, conditional branch,
return or fallthrough exit. Block, statement, value and local IDs remain separate
callable-local domains. A branch's last statement evaluates its condition.
Lexical scope ranges remain source information; graph edges describe execution.

Returns close a block. Subsequent source is still checked in unreachable blocks.
If arms and loop backedges are explicit; return-path checking uses graph
reachability rather than whether a source return was encountered. No second flow
tree or duplicate AST is retained. Straight-line bodies use the same graph path.

[Local_Flow](../../src/04_analyze/analyze_lifetimes/flow.php) computes
initialized bindings at block entry using a private worklist. Joins intersect
incoming initialized sets; scope transitions remove bindings that leave scope.
Loop edges participate in the same algorithm. After convergence, the lifetime
worker checks reads/writes and emits facts in deterministic source-block order.
The entry maps are temporary and are not retained by analysis results.

`Analyzed_Body.reachable_blocks` identifies analyzed blocks.
`reachable_statement_count` is now a count, **not a prefix boundary**. Consumers
iterate the selected block ranges. Each local-exit row identifies its block and
static statement boundary. One binding may have several possible exits; a loop
backedge exit executes on every iteration. `local_for(id)` supplies the first
exit's shared initialization information; consumers needing all exits use
`local_lifetimes`. No per-iteration compiler records are created.

Lowering allocates one static slot per reached binding, emits initialization at
its actual statement, and preserves graph edges. Allocas remain in the entry
block. Integer conditions become `icmp ne` against zero followed by a branch.
Local values crossing joins use their existing mutable storage, so this slice
needs no SSA construction or phi nodes. The original scalar proof uses copy:value
and cleanup:none. Later [lifecycle composition](lifecycle_contracts.md) and
[owning fields](owning_storage_fields.md) extend the same flow with managed obligations.

## Work, reuse and memory

Parsing remains per file; names, checking, lifetimes and lowering remain per
callable. Workers read fixed inputs, use private scratch state and produce
separate outputs. Selection precedes execution; joins and session publication
retain their existing boundaries. No threads or additional semantic work rounds
are introduced.

Body edits, including changed conditions and loop bodies under unchanged
contracts, use the existing incremental category. New declarations and catalog
changes retain full fallback. Each new output retains its exact producer input;
old source anchors are never rebound to a new AST. Finer same-file callable reuse
remains deferred.

Retained additions are real semantic data: operation inputs/contracts, typed
blocks, reachable block IDs and local exits. Blocks scale with control transfers,
not with every expression or statement. No extra prepared syntax, dense per-node
metadata or retained dataflow state is added. The private initialization maps
can still grow with the number of blocks and simultaneously live locals; sparse
PHP arrays do not eliminate that worst-case cost. PHP object/array measurements
must not be presented as native container sizes.

## Focused PHP cost check

On 2026-09-12, PHP 8.5.7 ran the existing 1 MiB scalability input in three fresh
processes per version, alternating order. The baseline was Git `e935696`; the
candidate includes this work and the preceding fixed checking-task change.
Each process performed an untimed inspection compile, collected cycles, then
timed full selection/execution/joins for checking, lifetimes and lowering against
fixed inputs. Native compilation, linking and emission were outside the timer.
Repeated phase exports matched that version's ordinary pipeline.

| Measurement | Baseline | Candidate |
|---|---:|---:|
| Checking median | 266 ms | 311 ms |
| Lifetime median | 172 ms | 250 ms |
| Lowering median | 268 ms | 320 ms |
| Three-phase total median | 706 ms | 888 ms |
| Three-phase total range | 702–734 ms | 881–890 ms |
| PHP memory after compile/GC | 146.11 MiB | 148.96 MiB |
| Additional peak during the measured phases | 59.59 MiB | 62.33 MiB |

This probe records about 26% more time for those three phases and 2.85 MiB more
post-compile PHP memory. Interpreter allocations and normal PHP cycle-collector
behavior are included; this is not an allocation-only or end-to-end benchmark.
The input has no new control-flow syntax, so it exposes the overhead of the
stronger common contracts on the existing scalar workload. It does not measure
branch-heavy dataflow scaling, native container costs or a parallel speedup.
Further optimization remains a separate decision, not a claim made by these
capability proofs.

## Proof

[addition.php](../../tests/features/addition.php) proves composed operands,
full-width signed and narrow unsigned wrapping, exact-type rejection, explicit
provider permission and catalog repair, deep expressions, fixed workers,
reversed joins, native body edits and fresh-build agreement.

[control_flow.php](../../tests/features/control_flow.php) proves both branch
outcomes, assignments through joins, repeated and nested loops, loop-local
initialization, shadowing, empty bodies, early returns, missing-return and
condition diagnostics, unreachable-source checking, reversed lifetime/lowering
workers, native condition/callee body edits, retained-input purity and repair.
The broader prototype suite continues to exercise the earlier scalar path,
exports, invalid inputs, incremental admission and native publication.
