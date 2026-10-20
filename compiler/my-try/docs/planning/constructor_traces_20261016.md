# Refreshed expensive-unit traces and constructor experiment
Doc Status: planning

Date: 2026-10-16. Diagnostic experiment against the shared-handle refinement at
`2f453540`; no compiler, runtime, application or unit-grouping changes. This is
not a new full edit-matrix run or a measured constructor optimization.

## Protocol and results

On dev002, replay the recorded full-build Clang recipes from
`/home/alex-ai/accounting-shared-handles-20261016/bench/edit-results/trial-1/full/clang-calls.json`.
Use unchanged `published-1` output, runtime include paths, Clang 18.1.3, C++23,
application O0 and production environment. Build one fresh private runtime PCH
with the recorded recipe; reuse it for all six serial diagnostic compiles. Remove
dependency-output flags, redirect object outputs and add `-ftime-trace`; do not
overwrite baseline objects/PCH or launch a link. Use the shared task runner with
one slot to isolate per-unit cost. Normal OS caching is allowed. `/usr/bin/time`
records process peak RSS and CPU time. Input hashes and all 292 publication
artifact hashes are recorded/verified. Remote results remain under
`/home/alex-ai/accounting-shared-handles-20261016/results`.

| Unit | Driver wall seconds | ExecuteCompiler | Frontend | LLVM backend | Maximum peak RSS MiB |
| --- | ---: | ---: | ---: | ---: | ---: |
| workflow_checks | 3.053 | 3.024 | 2.218 | 0.788 | 296.8 |
| closing_checks | 1.788 | 1.762 | 1.310 | 0.440 | 228.2 |

Times use each unit's median-wall trial (trial 2 for both); RSS is the maximum of
its three trials. Frontend accounts for about 73% and 74% of ExecuteCompiler.
Trace instrumentation adds overhead; isolated serial times are not a prediction
of the eight-job Ninja critical path. The earlier pre-refinement isolated trace
was 3.076/1.782 seconds ExecuteCompiler respectively: broadly similar, with no
controlled speedup claim. Frontend/backend totals do not cover every driver step.
Nested instantiation events overlap and must not be summed as independent costs.

## Selected pattern

Start with **implicit zero-argument construction of a non-inherited shared class
whose instance fields have empty typed-container defaults**, exemplified by
`WarehouseDesk`. It has no authored constructor and four private typed-hash fields:
locations, bins, transfers and counts. Each header initializer currently calls
`data_initializer<hash_t<shared_p<Payload>, string_t>>::from_key_value` with an empty
initializer list. The declaration emitter owns this spelling in
`05_backend/cpp/declarations.php`; implementation-group assembly already owns the
destination source unit.

In the median workflow trace, `scpp::shared<record_WarehouseDesk>` takes 0.226 s
inclusive. Inside it, the four `from_key_value` instantiations take 0.054–0.056 s
each and expose `populate_keyed`/hash insertion templates despite the empty input.
This is concrete attribution for the first experiment, not a claim that 0.226 s
can all be removed. `ProcurementDesk` has the same four-empty-hash shape and a
0.223 s shared-construction event, making it a useful second witness after the
first controlled case. `BankReconciliation` (0.185 s) and `InventoryBook`
(0.118 s) mix scalar/vector/hash defaults and are follow-ups for closing_checks.

Controlled change proposal:

1. For eligible O0/O1 lowering, emit a constructor declaration in the type header.
   Move the existing default expressions into its out-of-line member-initializer
   list in the already existing owning source unit. Preserve member order; do not
   turn initialization into assignment or change the container operation yet.
2. Keep caller allocation and `shared`/`make_shared` unchanged to isolate the
   effect. Leave destructor/copy/move policy and unit membership unchanged. Retain
   current dependencies initially; measure header cleanup separately.
3. Initially exclude inherited/data/value-record construction, authored constructor
   combinations, virtual lifecycle cases and other shapes whose construction or
   C++ aggregate/triviality rules need separate reconciliation. Verify the selected
   shared-class path does not rely on those properties or changed exception traits.
4. Compare caller and owner traces, a full build and affected edits, unchanged
   rebuild behavior and application execution. Prove independent empty containers,
   shared identity, initialization/destruction and failure behavior. Run a repeated
   allocation/use/destruction workload to measure any added call cost at O0/O1 and
   preserve the O2+ execution baseline. No end-to-end improvement is established yet.

A later, separate experiment could simplify provably equivalent empty-container
initialization. Do not combine that with outlining: otherwise attribution and
semantic review become ambiguous. Moving allocation behind a factory is also a
separate experiment; constructor outlining does not eliminate all `make_shared`
instantiation in callers.

## Optimization policy direction

The user requests compile-speed priority at **application O0/O1** and execution-speed
priority at **O2+**. Introduce one explicit C++ lowering policy before enabling the
first conditional pattern, rather than hardwiring an O0-only change and migrating
it later. This is a proposed next implementation slice, not completed work.

Current `Compiler::cpp()`/`CPP_Generator` receives no optimization policy; native
recipes validate only levels 0 and 2. The policy must be coordinator-selected,
backend-owned, and part of generated-fragment validity/publication identity.
It must also be available to standalone emission and compatible with the later
native invocation. Changing only the Clang command cannot refresh previously
rendered fragments. O1 and higher-level admission need explicit native recipe
support. These are necessary configuration/invalidation hooks, not a redesign of
compiler incremental/MT internals.

For the first experiment, use compile-latency policy to select eligible outlining
at O0/O1 and retain current spelling at O2+. This preserves an execution baseline;
it does not establish that current spelling is optimal at O2+. Future policy
choices require runtime evidence, not an assumption that outlining is always slow
or inline code always fast. Unit splitting stays deferred.

Tracked future work: [CH15-CPP-OPT-POLICY-01](../catalog/15_blocking_debt.md#ch15-cpp-opt-policy-01--optimization-aware-c-lowering).

## Evidence

[Evidence directory](constructor_traces_20261016/) contains the runnable remote
script, commands/timings/top-template summary, input hashes, generated-header
snapshots and compressed raw traces. No source behavior was changed and no new
application regression run is claimed; the six object compilations and private
PCH construction passed. Earlier full application validation remains in the
[shared-handle checkpoint](shared_handle_completeness_20261016.md).
