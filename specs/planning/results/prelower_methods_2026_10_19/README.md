# Source instance method transport
Doc Status: supporting

Date: 2026-10-19. Branch: `v0.2/prelower_templates`. Base: `58c0333a`.

The runtime ABI backend now transports source instance calls through existing
prepared call/receiver facts and operation requests. The ordinary call emitter
still sequences arguments and defaults. A dedicated method worker selects native
dispatch and argument value categories; the specialization engine gains no family
or type cases. There is no new AST, semantic registry or layout cache.

Shared receivers are retained and checked before evaluating arguments. Inline
receivers are borrowed. The concrete bridge borrows existing native storage;
owned results are constructed directly in final facade storage. The callback's
mutable pointers derive only from mutable bridge inputs.

## Validation

```bash
python3 compiler/my-try/tests/prelower_expressions.py --source-types --results FRESH_DIRECTORY
```

The expanded existing source fixture checks:

- left-to-right argument evaluation and writable/readonly reference aliasing;
- replacement of the sole shared owner during argument evaluation, with the call
  still observing the retained original instance;
- clearing the caller's handle from inside the selected method;
- borrowed inline class-value and struct receivers, mutation, defaults, void and
  owned results;
- throwing payload copies during argument snapshotting and native parameter
  construction, with complete cleanup;
- existing ownership identities, nested containers, demand removal, failed-emission
  recovery, no-op/reused fragments, configuration switching and fresh equivalence;
- native object/layout reuse, including body-only and layout changes.

The compiler emits the calls, methods and construction/destruction. Identical
test-only tracked fields measure payload lifetimes; no runtime behavior is replaced.
Counters are allocations, bytes, frees, constructions, copies, moves, destructions:

| Scenario | Allocations | Bytes | Frees | Constructions | Copies | Moves | Destructions |
|---|---:|---:|---:|---:|---:|---:|---:|
| Source/method workload | 26 | 1536 | 26 | 7 | 11 | 4 | 22 |
| Snapshot-copy failure | 0 | 0 | 0 | 1 | 0 | 0 | 1 |
| Native parameter-copy failure | 0 | 0 | 0 | 1 | 1 | 0 | 2 |
| Ownership inspection | 2 | 320 | 2 | 3 | 1 | 0 | 4 |

All O0/O2 and ASan/UBSan runs passed, with equal direct/ABI counters within
each configuration. Warm builds compiled and linked nothing. The method-body edit
reused the layout and compiled one support unit; the field edit rebuilt the layout.
See [counters](summary.json) and [cache results](source-cache-summary.json).
Sanitizer builds disable
optional copy elision: ownership inspection then has one additional move and
destruction in both modes. LeakSanitizer is disabled; live-payload assertions remain.
These cases measure zero additional boundary allocations or payload copies.

Raw final run: `/tmp/prelower-methods-final`. Focused PHP methods, method parameters,
method returns, ownership, destructors and initialization passed through the shared
runner in `/tmp/prelower-methods-focused`. Scoped portability conversion of four
backend files passed in `/tmp/prelower-methods-portability-02`; the initial check
caught a C++ keyword used as a PHP local, which was renamed. This is generated-program
native validation, not a native compiler rebuild. FPM sockets were unavailable,
so the runner used its CLI fallback.

An initial native run (`/tmp/prelower-methods-01`) matched workload counters but its
final comparison failed because the parameter-failure case was added after direct
emission. The final run emits both modes from the same completed fixture.

## Scope

Static/provider methods, reference results, variadic/observation parameter transport,
property crossings, selected constructors, callbacks and weak operations remain
follow-ups. Full inheritance dispatch, consume-parameter and absent-receiver failure
matrices are not established by this fixture. This is not whole-accounting support
or a new compile-time speed measurement.

[Owning guide](../../../../compiler/my-try/docs/architecture/cpp_template_prelowering.md)
