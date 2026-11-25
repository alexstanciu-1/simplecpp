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

Next, test callable-signature interning across equivalent live types, distinct
parameter modes, expiry, and reintroduction. That cache has its own ownership
contract and cannot be qualified by the observer test alone.
