# Runtime-core typed access composition boundary
Doc Status: planning

Checkpoint: 2026-10-28, implementation baseline `a1b7c58fe`.
Owner: runtime-core completion batch E, with the prepared-demand boundary in
[runtime_core_formation_boundary.md](runtime_core_formation_boundary.md).
This is an implementation proposal and gap audit, not a semantic specification or
permission to infer source admission from physical type composition.

## Evidence and the actual missing concept

A metadata-only run of the current C++ generator resolves:

| Demand | Native storage | Public element access |
| --- | --- | --- |
| vector<int64> | vector_t<scpp::int64> | int64 at(uint64) const |
| vector<vector<int64>> | vector_t<scpp::integer_vector> | integer_vector at(uint64) const |
| hash<vector<int64>,string> | hash_t<scpp::integer_vector,scpp::string> | integer_vector at(const string&) const |

The two nested types store concrete public wrappers as their elements. The generated
accessor returns an owned wrapper, not a reference. The existing compiled
`test_nested_vector.cpp` fixture already checks that modifying an extracted inner
vector leaves its outer owner unchanged. Its historical execution evidence remains
in the boundary results; no new compiled run is claimed by this metadata audit.

Consequently, chaining public at()/set() is not nested mutation. Copy-modify-replace
would add intermediate copying, fail for admitted noncopyable storage and obscure
current-root re-resolution and failure ordering. Exposing a mutable element reference
would violate the intended public boundary. The homogeneous boxed helper cannot
simply be reused: its loop keeps one receiver type, whereas typed steps change type
and cross concrete wrapper representations.

## Proposed owner and mechanism

Extend the existing physical API recipe/operation pipeline with composable access
protocol metadata. Each receiver recipe describes its key and element bindings and
its private representation operations. A demanded path operation supplies a prepared
sequence of steps and an operation, rather than asking the physical generator to
resolve source expressions or decide source-language legality.

The metadata must distinguish these operations instead of treating every access as
at()/set():

- Read/probe lookup and missing/invalid policy, with explicit element representation.
- Non-mutating write validation versus mutable descent and leaf commit.
- Ordinary store/append versus existing-only snapshot/writeback.
- Readiness/presence/empty observation versus removal membership/commit.
- Owned output versus a private call-scoped intermediate borrow.

Vector bounds, hash missing-intermediate behavior, mixed bootstrap and dynamic
existing-child requirements remain in their current native owners. Snippets supply
representation access; the generator composes them without vector/hash/type-name
switches. Public/native type bindings come from ordinary resolved specializations.
Do not introduce an independently maintained source capability registry.

Illustrative demand, not a finalized wire schema:

```
operation: existing_store
root: hash<vector<int64>,string>
steps:
  - receiver: hash<vector<int64>,string>
    key: string
    element: vector<int64>
  - receiver: vector<int64>
    key: uint64
    element: int64
value: int64
source-boundary: prepared by the existing semantic owner
```

The resulting public signature has a whole root reference, concrete saved keys and
an owned/scalar value input. Its compiled body resolves and validates both steps,
unwraps the stored inner public vector only inside the call, and commits only after
validation. An owned read returns the final element, never a borrowed wrapper.

Lazy probes/removal demand prefix signatures containing only already-evaluated keys.
Do not accept unevaluated later keys as ordinary eager arguments. Prefix identities
and full-path identities should reuse the same canonical demand/publication owner.
Only requested paths are emitted; there is no pre-generated Cartesian product of
container families, key types, depth or operations. Generated file limits remain in
force. Long-path output and repeated-prefix costs need focused measurement.

## Scope and acceptance sequence

1. Settle the existing prepared-fact ownership decision. Keep source definition,
   formation and operation admission separate from native representation facts.
2. Prototype one physical two-step path using fixture-prepared facts: first
   vector<vector<int64>>, then hash<vector<int64>,string> through the same mechanism.
3. Prove failed bounds/missing intermediates cause no partial changes, successful
   writes affect the original root, RHS replacement is observed, and existing-only
   writeback never recreates deleted entries. Include an owned-read lifetime proof.
4. Verify imported inner specializations, complete dependency identity, independent
   headers and sealed application reuse. Measure allocations and caller compile cost;
   no intermediate-container copies may be hidden behind a passing value test.
5. Extend to lazy probes/removal and explicit mixed/dynamic protocol composition only
   where source contracts admit the path. Keep field-owner retention and ownership
   expansion behind their existing review gate.

Full frontend integration remains excluded. The physical consumer may use trusted
prepared fixture facts, but it must not advertise those as a real frontend producer.
A shared semantic-model extraction would be a separate cross-owner refactor requiring
confirmation under the repository rules. No such extraction or path-specific family
emitter is implemented by this audit.


## Implemented first path (E23, 2026-10-28)

The prepared ownership decision is settled: semantic validation stays compiler-owned;
the generator consumes prepared facts. The first existing-read/store composition is
implemented through family `access` metadata and demanded `paths`, documented in
runtime/specs/public_api_generation.md. Vector/vector and hash/vector use the same
resolver and function emitter, with no type-name branches. Depth-three composition
also has metadata evidence. Lazy probes/removal, heterogeneous boxed paths and
field-owner retention remain follow-up work; this does not complete batch E.

Release and ASan/UBSan/LSan execution prove original-root writes, bounds/missing
failure preservation, RHS replacement and deletion handling, owned reads surviving
parent destruction, and zero allocations for repeated scalar read/store paths.
Publication checks prove independent public headers, zero-unit sealed reuse and a
new operation using imported inner/outer storage with exactly one emitted unit.
Missing prepared facts, conflicting imported definitions, invalid depths/operations
and oversized generated files reject. See E23 in the completion plan for timings.


## Typed readiness/removal extension (E24)

The same path mechanism now accepts prefix readiness and existing-only removal.
Membership is const and non-creating; mutable removal begins only after a complete
membership pass. A vector may occur as an intermediate receiver but has no erase
protocol. Prefix readiness returns a scalar observation, never a retained pointer,
and accepts only saved keys for that prefix. Leaf readiness requires a registered
receiver protocol. It is not `isset`: null-sensitive final value observations and
`empty` dispatch remain separate follow-up work, along with boxed heterogeneous
composition and field-owner retention. Source evaluation stays compiler-owned.


## Value-observation extension (E25)

Typed leaf observations now select a shared physical metadata descriptor and reuse
native `isset_one` / `empty` authorities after private representation borrowing.
Missing membership is false for `isset` and the native invalid-empty error for
`empty`. Probe access never copies the leaf. The registry admits explicit scalar,
boxed, container and carrier domains for presence; emptiness stays limited to string,
containers and runtime-checked boxes. Existing source preparation remains the
admission owner. The direct typed-key surface still uses each family's concrete key;
nullable/dynamic key adaptation, boxed heterogeneous descent and field retention
remain separate integration work. Standalone observation consolidation is not
claimed by this path slice.


## Native boxed element composition (E26)

Access metadata now distinguishes public-wrapper elements from native stored elements.
The same composer unwraps public children and directly borrows native children; owned
read return transport and saved store-value transport use the existing representation
owner. Mixed/dynamic recipes register existing-only operations with mixed key borrowing.
A three-step vector-to-boxed-to-boxed proof covers both COW and shared identity.
Composed boxed probes/removal remain gated pending receiver readiness/error-mode
integration; ordinary missing/creating writes are separate from existing-only paths.
No mixed-to-dynamic implicit crossing, weak-path admission or frontend integration
is implied.
