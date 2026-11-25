# Closure dependencies in the native compiler port

Doc Status: planning

The first identity-keyed dependency proof starts from `015aadcef`. Run
`ctest --preset default -j1 -R '^compiler_translated_closure_dependencies$'`
on dev002. The host comparison uses an actual PHP-flavor reference-returning
closure, its enclosing function, and a second source file declaring its callee.

Changing the callee's signature produces an argument diagnostic at the closure
call. Changing its body to call the enclosing function instead produces the
recursive reference-return diagnostic. Failed edits preserve the last successful
bundle. Repair and deletion of the enclosing function agree with a fresh build.

The native fixture represents a closure edge separately from a symbol watch.
The enclosing body calls a concrete closure; the closure calls a stable symbol;
the enclosing body watches changes to that symbol. Two registrations through
the same caller reach the same bucket. Traversal locks each target in insertion
order and resolves the symbol's current body at visit time. Repeated registrations
and overlapping notifications in one batch result in one delivery per body.
Retirement removes subscriptions and queues the final invalidation. The pending
batch temporarily owns the watcher, and dispatch releases it. Weak keys and
targets also expire if their external owner disappears before explicit cleanup.

Direct publication of `vector<weak<Graph_Node>>` was unavailable. A vector of
inline records, each containing one weak handle, compiled and ran. The fixture
uses linear identity lookup and an ordered visitor. It establishes lifetime and
delivery behavior for small graphs. It does not establish production-scale
lookup costs, general weak-map semantics, or full strongly connected component
validation. The cycle check follows reachable calls for one reference-returning
root and accepts a reachable cycle that excludes that root.

## Callable-signature interning

The production frontend can allocate separate owned callable types for parameter
declarations. Collection element resolution and dependency grouping use
`callable_signature_index::element()` when they need a canonical live identity.
The first live owner keeps the signature and its component types alive. The
index observes the component keys and the canonical owner through weak references.

The default test `compiler_translated_callable_signatures` feeds 19 declarations
from two independent frontend preparations into the production index and compares
the resulting 15 identity groups with a native Simple C++ implementation. It
varies result type, arity, parameter order, writable parameters, reference return
origin, and a trailing variadic parameter versus an ordinary vector. Builtin
components agree across preparations. Separate classes named Box stay distinct.

The native test also releases the last owner while a component is still alive,
looks up the same signature again, and reuses an expired child edge below a live
prefix. Both implementations release a retired nominal component. The native
fixture uses linked children because recursive concrete collection fields are
currently unsupported. Lookup scans siblings and can recycle an expired edge
on insertion. Production uses PHP WeakMap, which automatically removes an entry
when its key expires. These tests qualify identity and ownership for the tested
signatures. The following on-demand measurement covers one common-result prefix.

### Native lookup and retirement under repeated replacement

On dev002, `compiler_callable_signature_churn` builds the translated fixture
with Clang at `-O2`. Enable `SCPP_ON_DEMAND_TESTS` and `SCPP_ENABLE_DATETIME`,
then run `ctest --preset default -V -R '^compiler_callable_signature_churn$'`.
Each row measures 131,072 round-robin successful lookups and identity checks
across distinct one-parameter signatures with the same result type. The
monotonic timer excludes construction, retirement, node counting, and startup.

| Live signatures | Median of 5 runs (range) | Nodes after release | After 32 more generations | After one narrow generation |
| ---: | ---: | ---: | ---: | ---: |
| 8 | 6 ms (5–6) | 10 | 10 | 10 |
| 64 | 15 ms (12–15) | 66 | 66 | 66 |
| 256 | 55 ms (54–56) | 258 | 258 | 258 |

There is one root and one result node in addition to the input nodes.
Expired input slots are reused across generations. They remain allocated when
the width falls to one. These counts measure index metadata, not process memory.
The increase in time is consistent with scanning more siblings on each hit.
Keep the native fixture as an identity and lifetime reference. Before adopting
its lookup structure in the compiler port, measure actual sibling counts and
lookup frequency on representative compiler inputs.

### Observed production lookup widths

On dev002 at `2edf8458c`, a disposable checkout recorded each production
`callable_signature_index::element()` visit, whether its terminal owner was
still alive, and the child count in each PHP WeakMap immediately after selecting
the component. The probe held no extra component or owner references. Timing an
instrumented build would include the probe's file writes, so this run reports
counts only.

| Input or default workflow | Lookups | Live-owner hits | Widest visited map |
| --- | ---: | ---: | ---: |
| Queue report, access log, stock ledger, route table (four cold project builds) | 0 | 0 | — |
| Translated compiler path and callable reference inputs | 0 | 0 | — |
| Callable collections | 36 | 32 | 1 |
| Translated closure dependencies | 4 | 0 | 1 |
| Ten other callable, callback, closure and reference-return regressions combined | 46 | 29 | 2 |

All four project builds emitted manifests and all 14 measured regressions
passed. The dedicated interning proof was excluded. Of 86 lookups across these
workloads, three traversed a map of width two, all at the result component.
The PHP index splits reference and variadic modes into separate branches, so its
widths need not equal the native fixture's sibling counts.

Defer a specialized native lookup structure for the freeze. The high-width
benchmark establishes a scaling limit, but these applications supplied no
traffic at that boundary. Revisit the choice with a larger callable-heavy
application or the native compiler's own workload.
