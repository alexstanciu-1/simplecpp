# Structural queries and streaming member traversal
Doc Status: supporting

Syntax_Access, its same-namespace Metaprogramming_Syntax trait, and
Struct_Member_Cursor form one structural-query component. Queries return existing
node IDs or shared nodes; they do not resolve names/types or mutate input trees.
The ten semantic cursor consumers were adapted in the preceding PHP checkpoint.

## Source adaptation

Optional `nodes[id - 1] ?? null` and nullsafe kind tests now use two private owned
operations: require_node returns a node or throws the caller's original diagnostic;
is_kind performs a bounded optional role test. These rely on Syntax_Tree's existing
dense vector<syntax_node> contract. Sparse PHP maps/null elements are not valid
native syntax storage. No whole-tree validation or general PHP lookup semantics
are introduced. Existing unchecked links remain unchecked where that was the
query's contract; this slice does not invent a complete malformed-graph verifier.

Fixed enum membership arrays become explicit comparisons. Control-branch eligibility
is a boolean, rather than a dynamically extended PHP array. Optional reference
modifiers produce the appropriate result directly; absent local initializers avoid
a nullable intermediate object. Queries retain their original validation order,
error messages and exact returned role fields. Literal Syntax_Access calls name the
trait's actual owner because the converter requires a locally spelled call target;
no receiver or trait-consumer inference was added. Record constructors explicitly
use \parse\ qualification: unqualified names such as function_parts were hidden
by same-named query methods in emitted C++. This source disambiguation retains
the existing PHP meaning; the initial failed native build is retained.

The cursor preserves lazy validation and constant-size traversal state. Current
requires a positioned cursor; exhaustion or failure closes traversal. The cumulative PHP/native
fixture includes template unwrapping, field filtering over interleaved methods,
repeated current/advance, invalid declarations and shared node identity; these expectations pass on both PHP and native. Optional local initializers also
cover absent/present values and unexpected trailing children.

## Evidence and limits

A frozen pre-adaptation query owner and trait are host-only oracles. The comparison
harness invokes public two-argument queries across parsed trees, missing IDs, and
single-field kind/child/sibling mutations: 27,560 value/error comparisons, with input
purity checked after each variant. It is integrated into the fast portability loop.
These cases are not exhaustive arbitrary graph coverage; cyclic graphs remain
outside the input contract. Existing focused cursor tests separately verify early
termination before a poisoned tail.

The cumulative proof now includes all three query files alongside the previous
36 production files. Whole-file conversion, trait expansion, incremental reuse and
strict native execution pass on exact candidate
`a1a1babd07082d9abf7ac885b2328c99368ad4cf`, now the configured unreleased target.
The cumulative harness includes 22 retained PHP fixtures, including the focused
cursor regression. Its independent expected output covers the existing ready set
and the query/cursor behavior. Native coverage does not promote the ten semantic
consumer files or establish whole-compiler portability.

The earlier constructor and eager-evaluation failures remain in
`results/structural-queries-01/` and `results/release-a1a1babd-01/`. #235 clears
qualified construction; explicit source guards preserve optional traversal without
changing target operator semantics. Focused adaptation evidence is in
`results/explicit-guards-01/`; cumulative evidence is in
`results/structural-queries-cumulative-01/` under the migration planning directory.

## Optimization follow-up

Typed checked access exposes where bounds checks recur. Profile complete compiler
workloads before introducing validation caching, batching checks or indexed member
lists. Changing check placement can change deferred failures and early-termination
work. The shared query owner is the place to optimize; do not scatter unchecked
special cases through consumers. No measured runtime speedup is claimed here.

See [the target handoff](../planning/compiler_migration/structural_query_target_handoff.md).
The ready count is now 39. The selected candidate remains unpublished; target
adoption here does not merge, tag or publish the v0.1 release.
