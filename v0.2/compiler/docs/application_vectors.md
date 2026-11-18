# Application-defined vector elements

Doc Status: supporting

The first application specialization slice admits `vector<Record>` local values and by-value function/method signatures in
all three flavors. Empty construction, append, checked indexed reads/replacement,
count/empty and ordinary vector copying reuse the existing collection protocols.
The element retains its normal storage semantics: structs are copied inline; bare
classes use required shared owners. Copying a vector copies its storage, while copied
class owners and shared fields preserve their existing object identity.

```php
struct Point { public int $x = 7; }
$points vector<Point> = [];
$p Point;
$points[] = $p;
$copy = $points;
$q = $copy[0];
$q->x = 9;
$copy[0] = $q; // $points still contains x = 7.
```

## Ownership and preparation

Type arguments resolve through the ordinary application lookup and stable symbol
watches. Each record definition lazily owns one canonical vector of its default
value type. This follows the existing declaration-generation lifetime rather than
adding a session-global registry that could retain obsolete ASTs. The specialization
retains its non-owning element link and deterministic native name; it copies no field,
method, constructor or AST data. Unchanged source and prepared facts remain reusable.

The vector compiler handler is generated from the same template and validated member
bindings as runtime vectors, with the canonical element supplied to its constructor.
Preparation still visits each operand once and calls the element's existing conversion
handler. Collection lowering now passes the existing private conversion-adapter context
through append/store handlers, including class upcasts; it introduces no conversion matrix.

## Concrete generation and publication

`cpp_concrete_type` implements the common `Scpp\Api\ConcreteType` interface over a
settled compiler type. It exists only during private lowering. The generator's
storage-to-recipe selection supplies the same `collections/vector` recipe used by
runtime types. Recipe parameters specify the private access name, helper namespace
and inline definition prefix; runtime output remains unchanged.

Concrete definitions are guarded application-owned headers, included after complete
element declarations through the existing source adapter mechanism. This first slice
uses inline definitions rather than introducing another native publication queue.
Cross-source demand deduplicates by stable identity and the App namespace. Existing
source-adapter retention, depfiles and manifest cleanup handle reuse and removal.
Separately compiled specialization bodies are a future build optimization.

The generated implementation reuses the runtime's untyped buffer helper source and
concrete lifetime recipe. Application code does not include a private runtime header.
Capacity is raw storage: growth never invokes an element's user constructor. Copying
uses ordinary copies, and all native recipe assertions remain in place. No C++
templates, source-template parsing or compiler-generated templates are introduced.

## Boundaries and proofs

Application-vector writable references, instance fields, nested collections, explicit
shared/weak element spellings, enums and foreach remain separate slices. References and
fields receive preparation diagnostics rather than incomplete native output. Existing runtime specializations retain their current capabilities.

`application_vectors_native.py` verifies Clang and GCC in all flavors, including
required constructors, heap-backed string copies, class/shared-field identity, growth,
class upcasts, typed copies, no-op reuse, element-layout edits, deletion, failed edits,
recovery against fresh compilations, source-demand deduplication/retirement and App
namespace changes. Its `--memcheck` option adds ASan/UBSan and leak checks. Instrumented
O2/g0, Ninja8/mold timings separate PHP work, native wall, frontend, LLVM and link work.

## By-value signatures

Functions and methods reuse ordinary argument binding, exact-type conversions and return
preparation. Passing a vector copies its storage; inline elements copy independently,
while class elements and shared fields keep their object identity. Native returns retain
the existing value/move behavior. No new AST nodes or retained preparation facts are needed.

Signature dependency discovery yields the concrete vector and its nominal element. Public
callable headers forward-declare the vector; record member signatures do likewise, so a
method on Point may accept vector<Point> without a recursive header definition. Each
caller/callee source requests the same recipe through existing adapter publication, after
complete element layouts/owner definitions and before executable bodies. Native payload
member signatures qualify the concrete type with the configured application namespace.

`application_vector_signatures_native.py` tests function forwarding, struct and class
methods, independent copies, shared identity, cross-file signature/element edits, deletion,
retry and recovery against fresh builds in all three flavors with Clang and GCC. It also
checks no-op reuse and configured/disabled application namespaces, and records stage timings.
