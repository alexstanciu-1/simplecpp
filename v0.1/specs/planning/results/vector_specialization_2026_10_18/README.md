# Concrete vector specialization and native edit experiment
Doc Status: planning

2026-10-18. Native representation proof, not a production compiler implementation.
The fixture emitter specializes a single bounded vector recipe for two nominal
shared-object handle types. The production type/capability registry is not called.

## Representations

1. **exposed**: application sees `scpp::vector_t<Row>` and the runtime umbrella/PCH.
   `Row` is a concrete shared-object handle backed by `scpp::shared_p<RowPayload>`.
   The private payload declaration is also exposed, modeling the current complete-type
   dependency. This is a controlled test fixture, not actual generated my-try output.
2. **adapter**: the same runtime vector is hidden in an out-of-line implementation
   behind a concrete vector API. Object payloads and ownership are unchanged.
3. **lowered**: vector specialization is emitted as explicit typed storage, copy/move,
   append, read, replacement and destruction operations. No vector template is used.
   The object provider still uses `scpp::shared_p<RowPayload>` and the runtime umbrella.
4. **concrete**: both the vector and per-class provider have no C++ template exposure.
   A single precompiled `owner.cpp` uses `std::shared_ptr<void>` with an explicit
   destruction callback. Class specializations emit concrete allocation/destruction
   and field access. This reuses standard shared reference counting rather than
   implementing a new counter. This representation is not ABI-compatible with existing
   scpp handles; interoperability is not established by this test.

All variants retain ordinary C++ classes, constructors, destructors, move operations,
exception unwinding and a small sequencing lambda. The lowered vector uses `new Row[]`
with explicit geometric growth; it does not claim std::vector's allocation pattern
or runtime performance. The bound here is a copyable shared-handle element with
noexcept moves, not arbitrary value structs or noncopyable elements.

## Semantics exercised

Each executable checks construction, growth, vector copy independence, shared pointee
identity and mutation, replacement without changing the copied vector's slot,
append independence, copy assignment, move construction, self-assignment, checked
out-of-range access, an aliased append, returned-handle survival, cleanup on early
return, cleanup on exception unwinding, and exact payload construction/destruction
balance. The added specialization repeats the same suite for a distinct class.

Bounds errors use an experiment-local BoundsError consistently across variants;
production diagnostic payloads are not reproduced. Null dereference, inheritance,
weak ownership, shared-self, allocation-failure injection, multithreading, arbitrary
nested specializations and runtime throughput remain outside this proof.

## Edit protocol and measurement

Clang 18, C++23, PIC, debug information, O0/O2, three serial repetitions of each edit
sequence. Variant order is shuffled per repetition. Warm filesystem; no compiler
object cache. Each sequence starts with fresh objects, then applies:

- **base**: compile required providers, vector support and application, then link.
- **body**: change a value in the application body; only app.cpp must rebuild.
- **layout**: add a private payload field, update initialization and field access.
  Exposed mode rebuilds Row.cpp and app.cpp. Concrete interfaces rebuild only Row.cpp.
- **specialization**: add a second nominal class and vector specialization, and use
  both from the application. Existing Row and RowVector objects must remain intact.
- **unchanged**: rerun the final source state; no compilation or linking is allowed.

The small experimental executor uses actual Clang depfiles and content hashes to
select stale objects, and checks hashes of retained objects. Assertions verify each
expected rebuild set; every resulting executable runs its behavioral checks.
Compilation/link times exclude emission, dependency hashing, process execution of
behavioral checks and PCH setup. Zero unchanged native work is not zero end-to-end
build time. Native actions are serial, so totals measure work on this execution
schedule rather than optimal parallel wall time.

The first three variants share one runtime PCH per optimization level. Concrete
application/vector units omit it; concrete mode uses no PCH at all. The existing
accounting runtime library is retained and linked in all modes for consistency,
although the fully concrete mode does not require its APIs. The fourth variant is a
separate sequential follow-up run, not interleaved with the first three. Environment
noise and three repetitions limit interpretation of small differences.

## Architectural implication

Concrete specialization can preserve C++ automatic cleanup while controlling both
native dependencies and template exposure. A private shared-payload layout change
need not invalidate its vector specialization when the handle representation and
copy/move/drop contracts are unchanged. A change to those contracts, public handle
layout, or inline-value representation would require a different invalidation set.

The final variant puts remaining generic implementation inside one reusable ownership
service; it does not require per-class C++ template instantiation. This is a bounded
example of owning specialization, not proof that all runtime metaprogramming can be
lowered by the compiler today. Production work must bind emitted operations to the
existing semantic identities/capabilities and publish artifacts through existing owners.
No compiler or runtime production sources are modified.

## Reproduce

```
python3 specs/planning/results/vector_specialization_2026_10_18/run.py
python3 specs/planning/results/vector_specialization_2026_10_18/run.py --variants concrete --output specs/planning/results/vector_specialization_2026_10_18/concrete_results.json
```

`fixtures.py` and `concrete_fixtures.py` are the complete experimental emitters.
`fixtures/` holds final emitted sources. Results include native command timings,
source hashes, exact rebuilt actions and behavior outcomes. During the first run,
run.py was extended to select the additional concrete variant; the first three
variant compilation paths and fixture emitter remained unchanged.

## Results: median native compile + link milliseconds

### -O0

| Representation | Initial build | Body edit | Private layout edit | Add class + vector | Unchanged |
|---|---:|---:|---:|---:|---:|
| exposed | 3802.0 | 2034.0 | 3809.7 | 3897.9 | 0.0 |
| adapter | 3802.8 | 212.0 | 1918.0 | 3882.9 | 0.0 |
| lowered | 2101.6 | 209.8 | 1972.7 | 2140.9 | 0.0 |
| concrete | 1008.2 | 219.8 | 209.3 | 352.8 | 0.0 |

### -O2

| Representation | Initial build | Body edit | Private layout edit | Add class + vector | Unchanged |
|---|---:|---:|---:|---:|---:|
| exposed | 4184.7 | 2202.8 | 4137.4 | 4403.7 | 0.0 |
| adapter | 4208.1 | 249.5 | 2069.7 | 4211.3 | 0.0 |
| lowered | 2283.6 | 250.7 | 2109.3 | 2325.9 | 0.0 |
| concrete | 1123.4 | 252.5 | 228.7 | 455.3 | 0.0 |

Initial builds include all experimental support objects, including owner.cpp in
concrete mode, but exclude the existing runtime library and PCH construction.
Runtime PCH setup separately took 4235 ms at O0 and 4210 ms at O2; concrete mode
has no PCH setup. Its initial total was 1008/1123 ms, including the one-time shared
ownership service compilation. These are warm-filesystem native builds, not fully
cold end-to-end compiler builds.

At O0, the new vector support compiled in 1847 ms with runtime templates versus
64 ms as concrete storage. The concrete new class provider compiled in 61 ms;
retaining runtime templates in that provider cost approximately 1.8 seconds.
At O2, the corresponding vector support costs were 1949 vs 96 ms, and the concrete
class provider cost 75 ms. This separates the gain from hiding templates during
body edits from the additional gain of eliminating per-specialization templates.

All 120 edit-state executions passed across four variants, two optimization levels
and three repetitions. All 24 unchanged states performed zero compile/link actions.
The explicit rebuilt-source assertions passed for body, private-layout and new-type
changes. LeakSanitizer could not run in this environment: it reported a fatal ptrace-related
limitation (leak_sanitizer_results.json). ASan/UBSan passed with leak detection
disabled (sanitizer_results.json); exact payload destruction counters remain part of every behavioral run. This is a bounded behavioral proof,
not exhaustive equivalence with every runtime class/vector operation.

Reproduce sanitizers with `python3 specs/planning/results/vector_specialization_2026_10_18/sanitize.py --no-leaks`.
