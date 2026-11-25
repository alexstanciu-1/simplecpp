# Synchronous collection transformations
Doc Status: normative

## v0.2 strict contract

`collection_map` and `collection_filter` accept typed vectors, typed hashes, and
owning mixed/dynamic tables. `sequence_map` / `sequence_filter` accept vectors;
`keyed_map` / `keyed_filter` accept typed hashes. Carrier admission is independent
of contents, including empty inputs.

Maps receive one value and return one value. Typed maps may change element type;
hashes retain their exact key type and encounter order. Filters require an exact
bool result and retain the original values. Vector results are dense. Every result
has fresh outer membership; ordinary element copy/handle identity semantics apply.

Boxed maps receive and return their own family: mixed → mixed or dynamic → dynamic.
Boxed filters receive that family and return bool. Scalar boxed inputs reject at
runtime. No mixed/dynamic family conversion or weak-table lifetime extension is
introduced. Wider boxing of callback results is separate.

Input and callback expressions are evaluated once, left to right, with ordinary
value snapshots. Callbacks run synchronously in encounter order, once per element;
empty inputs invoke none. Exceptions propagate, and a partial result is not
published. Callbacks must not mutate the source, its aliases or shared elements;
this is an authoring obligation, not a newly implemented mutation detector.

The compiler's default prelowering supports owned, one-value-parameter callbacks
with non-void value results. Owned captures retain ordinary copy and handle identity
rules. Borrowed captures, reference parameters/returns, asynchronous retention,
fixed-array adapters and general callable transport remain separate.

The shared runtime algorithm owner is `runtime/include/scpp/collections.hpp`.
The [portable PHP adapters](portability/collection_helpers.md) retain their narrower
portable authoring profile; these PHS declarations do not widen that converter.
