# Parallel optimizer and prepared operation effects

Doc Status: planning

**Compiler debt — deferred.** Resume borrowing optimizations after a separate analysis over prepared operations can identify which storage an expression may read, modify, borrow or let escape at each program point.

## Optimizer ownership and migration

Build a separate compiler optimizer that consumes prepared facts. Move **every existing Simple C++ compiler optimization** into it, including optimizations currently selected during preparation, type-specific lowering and C++ emission. Start with a repository-wide inventory. For each optimization, record its current decision site, required facts, fallback, regression coverage and migration status. The inventory must include the existing collection reader and snapshot proofs. During migration, preparation supplies semantic facts, the optimizer owns the choice of transformation, and lowering emits the selected implementation. Close this debt when every inventoried decision has moved and its former decision site has been retired.

Evaluate other optimization families as part of that inventory. For example, effect and lifetime facts could justify removing redundant temporaries or reusing repeated computations. For each candidate, identify observable effects and failures, estimate the benefit, and measure compiler cost and generated-program performance before adoption.

## Parallel execution

The optimizer must run independent work in parallel. Schedule bodies whose prepared inputs are ready, give workers a stable revision of those inputs, and order dependent passes by their data dependencies. Publish results only for the revision analyzed. An edit during analysis must invalidate dependent results before lowering consumes them.

Compare one-worker and multi-worker runs on the same fresh build, edit and recovery. Require identical decisions, diagnostics and generated artifacts, including after a worker finishes late. Measure wall time, peak memory and scheduling overhead on dev002 for small incremental edits and larger builds.

## Analysis

Use resolved bindings and type capabilities. For each operation, distinguish the effects of evaluating its operands, performing the operation, converting the result and running cleanup. Preserve their order. For example, in `$snapshot[changeKey($writer)]`, the key expression may mutate an alias before the read-only lookup begins. [Indexed lookup contract](../native_reference_safety.md#private-collection-copies-at-calls)

Distinguish replacing an element from changing the collection’s structure. Follow possible aliases and live borrows, and join possible effects across branches and loops. Account for calls, callback re-entry, suspension and exceptional exits where reachable. Unknown effects keep the ordinary copy. Track dependencies so edits to a callee or type invalidate the affected summaries.

## First use and acceptance

Use the existing bounded borrowing proofs as controls. The first proposed extension is a local snapshot whose two reads finish before a scalar-only return. An aliased write between the reads must preserve the saved values, with native copy counts of 0 → 1 → 0 across the edit and recovery. [Snapshot contract](../native_reference_safety.md#private-local-collection-snapshots)

Check throwing reads, escaping borrows and authored destructors. Compare incremental results with fresh preparation after edits. Measure preparation cost on dev002 for both affected and unrelated bodies before enabling an optimization based on the analysis. [Existing evidence](non_async_language_closure.md#compiler-debt-for-inferred-read-only-borrows)
