# Threading Transfer Semantics Follow-Up
Doc Status: planning

Date: 2026-06-15

## Purpose

Specify the next design slice after the first-pass `tasks` implementation: how input values and output values may cross worker-thread boundaries.

This note is planning only.
It does not change current language, runtime, generator, STAN, or build semantics by itself.

Related documents:

- [specs/builtins/tasks/first_pass.md](/home/alexv/__AI/simple_cpp/simple_cpp_02/specs/builtins/tasks/first_pass.md)
- [specs/planning/threading_batch_api_plan_2026_06_08.md](/home/alexv/__AI/simple_cpp/simple_cpp_02/specs/planning/threading_batch_api_plan_2026_06_08.md)
- [specs/planning/threading_preimplementation_audit_2026_06_12.md](/home/alexv/__AI/simple_cpp/simple_cpp_02/specs/planning/threading_preimplementation_audit_2026_06_12.md)

## Current Baseline

The first-pass runtime supports typed `vector<T>` and `hash<T, K>` batch inputs.

The current safe public model is:

- workers receive an item value and optional `task_context`
- result placement is coordinator-owned
- workers do not mutate shared result state
- background `task_join` returns a boxed result table
- mixed input is deferred
- public mutable/exclusive item mode is deferred

## Discussion Decisions - 2026-06-15

- Timeout should be modeled as post-item timeout detection plus cooperative stop request.
- Timeout should become catchable through the normal `task_error` path later, without claiming the runtime can force-kill arbitrary running user code.
- Mixed/dynamic input may be accepted at runtime only when the value resolves to a supported concrete collection shape.
- Mutable/exclusive item mode is a larger follow-up project, not a small extension of the first-pass API.
- The mutable/exclusive project should evaluate `unique_p`-style ownership and by-value structures before choosing source-visible API.
- S2S lowering, with STAN help, may eventually generate safer thread-boundary code, but that requires explicit ownership and memory-allocation rules.

## Design Goal

Define transfer rules that let tasks remain useful for performance work without exposing raw locks or asking users to reason about arbitrary shared memory.

The design should answer:

- which values are copy-safe across threads
- which values must be rejected, frozen, cloned, or moved
- whether mutable item access is internal-only or source-visible
- what partial guarantees STAN can enforce
- what the runtime must still guard at execution time

## Transfer Categories

Proposed categories for values crossing into workers:

1. **Copy-safe values**
   Scalars, strings, nulls, and typed containers whose element types are also copy-safe.

2. **Boxed dynamic values**
   `mixed` / `dynamic` carriers and decoded JSON-like data need explicit rules before public mixed input is enabled.

3. **Runtime resources**
   File handles, curl handles, database handles, task handles, and other runtime-owned resources should be rejected unless a specific resource type later defines thread-safe transfer semantics.

4. **Shared mutable values**
   Objects/tables with aliasing or reference-like behavior should not cross worker boundaries by default.

5. **Exclusive item ownership**
   A future mode may transfer one collection item to one worker so it can be mutated without locks while the main thread and other workers cannot observe that item.

## First Recommended Rule

Public task input should remain copy/freeze oriented until STAN and runtime checks can agree on a stronger ownership story.

Initial rule:

```text
task input item must be copy-safe or runtime-isolated
worker result must be copy-safe or runtime-isolated
result placement remains coordinator-owned
worker callback must not mutate the source collection or shared result target
```

## Mixed Input Gate

Mixed/dynamic input should be checked at runtime.

The acceptable first rule is:

```text
mixed/dynamic input is accepted only if it resolves to a supported concrete collection shape
```

Supported first concrete shapes:

- vector-like/list input
- hash-like/map input

Do not enable public `mixed` input until these cases have explicit behavior:

- scalar boxed values
- boxed strings
- boxed vectors/hashes
- boxed dynamic objects/tables
- nested mixed values
- missing/null values
- runtime resources hidden inside mixed carriers
- copy failure diagnostics

The first acceptable mixed-input implementation may reject non-copy-safe mixed contents with a clear runtime contract error.

## Mutable / Exclusive Item Mode

The user preference is that mutable work inside a thread should be invisible to the ordinary API.

The current working concept is that mutable/exclusive item mode should work with object identity and possibly allocator-owned memory, not just ordinary copying.
This likely requires coordination between:

- runtime ownership primitives
- S2S lowering
- STAN analysis/advisories
- generated C++ thread-boundary code
- allocator and lifetime rules

Two candidate transfer shapes should be evaluated:

- `unique_p`-style exclusive ownership for objects/resources that must not be aliased
- by-value structures for data that can be cheaply or safely moved/copied into the worker

That suggests two possible paths:

1. **Internal optimization only**
   The public API stays unchanged. The runtime may move or borrow internally only when it can prove exclusive ownership.

2. **Explicit future mode**
   A source-visible mode is added only if internal optimization cannot cover real performance cases.

The internal-only path is preferable for now.

Implementation gate for any mutable path:

- the item slot is owned by exactly one worker while executing
- the source collection cannot be read or mutated concurrently
- the item cannot escape into shared globals, another live task batch, or a shared result target
- failures leave the source collection in a documented state
- cancellation and timeout have documented ownership cleanup behavior

## STAN Advisory Scope

STAN should eventually diagnose or warn about:

- worker callback captures of mutable locals
- worker callback captures of the source collection
- worker callback writes to global/shared state
- result target mutation inside worker callbacks
- passing task handles or runtime resources into worker input
- using mutable/exclusive mode when aliases to the same data remain reachable
- returning values that contain non-transferable runtime resources

STAN should not be the only safety mechanism.
The runtime must still reject non-transferable values at execution time where source analysis cannot prove safety.

## Runtime Diagnostics

Transfer failures should be contract errors with task-specific operation names.

Suggested diagnostic kinds:

- `invalid_task_input_transfer`
- `invalid_task_result_transfer`
- `invalid_task_resource_transfer`
- `invalid_task_mutable_alias`

The saved diagnostic should include:

- task operation (`task_run` or `task_join`)
- input key/index when available
- value kind when available
- whether failure happened before worker execution, during worker result collection, or during result placement

## Deferred Questions

- Should source-visible mutable mode ever exist?
- If yes, should it be a new function name or a named-argument mode?
- Can STAN prove enough exclusive ownership to make mutable mode safe without noisy false positives?
- Should dynamic objects be frozen, deep-copied, or rejected?
- What is the exact interaction between timeout/cancellation and an item currently owned by a worker?
- Should background `task_join` preserve typed result containers instead of boxing for selected cases?

## Suggested Next Implementation Slice

Before implementing mixed input or mutable item transfer:

1. Add a runtime helper that classifies transfer-safe runtime value kinds.
2. Add tests proving non-transferable runtime resources are rejected clearly.
3. Add STAN advisory placeholders for task callback captures and source collection access.
4. Add one narrow mixed-input prototype for scalar-only mixed values, if the runtime classification is ready.
