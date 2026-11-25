# Descriptive metaprogramming materializer direction

Doc Status: planning

Discussion date: 2026-09-27

## Purpose and status

This note records the proposed implementation direction for metaprogramming in
`compiler/my-try`. It is an architectural plan, not an implemented feature or a
new language contract. The normative boundary remains
[`specs/metaprogramming_contract.md`](../../../../specs/metaprogramming_contract.md).

The direction is to use one small recursive materializer inspired by
DescriptiveJS rather than growing independent implementations for generic
functions, generic types, compile-time conditions and provider families.
Implementation is deferred until the direction and first vertical slice are
reviewed.

LLVM remains outside the default implementation scope. Standard LLVM IR cannot
retain unresolved template parameters; any LLVM path must receive concrete or
deliberately erased compiler results after materialization.

## Reference implementation

DescriptiveJS is a compact recursive MVVM materializer. The relevant references
are:

- public repository: [alexstanciu-1/descriptivejs](https://github.com/alexstanciu-1/descriptivejs);
- Open M3 legacy copy:
  `/home/alexv/__AI/open_m3/open_m3_primary/_legacy_code/descriptivejs`;
- main legacy materializer:
  `/home/alexv/__AI/open_m3/open_m3_primary/_legacy_code/descriptivejs/src/core/dnode.js`;
- legacy dynamic dependency engine:
  `/home/alexv/__AI/open_m3/open_m3_primary/_legacy_code/descriptivejs/src/core/data-proxy.js`.

DescriptiveJS supplies architectural evidence, not source to copy. Its useful
shape is:

```text
q-func definition + formal arguments
    -> q-call with actual arguments
    -> per-call binding context
    -> recursive materialization of nested templates
    -> one rendered result
```

The compiler analogue is:

```text
template definition + formal parameters
    -> concrete use with actual type/value arguments
    -> per-instance compile-time binding context
    -> recursive materialization of demanded dependencies
    -> one concrete semantic result
```

DescriptiveJS also implements runtime expression compilation, DOM updates,
events, proxy-based dependency discovery and reverse model synchronization.
Those mechanisms are not part of this compiler direction. The compiler needs a
one-way, statically recorded materialization path.

## Terminology

Two meanings of specialization must remain distinct:

- an **AST specialization** is the typed `node_structure` payload selected by an
  `ast_node` kind;
- a **template specialization** is one concrete semantic instance of a template
  definition with ordered concrete arguments.

The source AST should describe a template definition once. Concrete template
specializations should not be represented by mutating that AST or by attaching
per-instance bindings to its shared syntax payloads.

## Core direction

A code template should be represented like a DescriptiveJS `q-func`: one shared
definition with an ordered formal parameter list and one retained body. A use is
like `q-call`: it supplies ordered actual arguments and requests a concrete
instance.

The materializer should perform this one-way operation:

```text
resolve definition and canonical arguments
    -> find or create exact instance identity
    -> bind formal parameter slots in an instance context
    -> prepare the shared body through that context
    -> recursively request explicitly discovered dependencies
    -> publish the concrete result
```

The normal compiler stages continue to own name binding, type checking, layout,
lifetime analysis and code generation. The materializer coordinates concrete
work; it does not become a second semantic compiler.

## Proposed ownership model

### Template-definition syntax

A template should have its own AST specialization rather than being treated only
as an ordinary function whose `template_parameters` collection happens to be
nonempty. Conceptually, the specialization owns:

- ordered formal parameter syntax;
- the wrapped function, struct or other permitted declaration;
- the source span and child order required for diagnostics and traversal.

This makes the template wrapper responsible for preventing an unresolved generic
declaration from entering ordinary concrete preparation. The wrapped declaration
retains its normal grammar and existing processing owners.

The exact node kind, payload name and whether the wrapper directly owns or aliases
the wrapped declaration remain implementation details to review against the
current AST invariants.

### Definition record

Collection should publish a semantic definition record with:

- stable definition identity;
- source template node;
- ordered parameter kinds and slots;
- constraints or required capabilities;
- published callable/type surface;
- source provenance and version/shape identity.

The definition record is a recipe. It is not a concrete type or callable.

### Instance record

Materialization should own one instance record per exact definition and ordered
canonical argument list. The record should carry:

- definition reference;
- canonical type and compile-time value arguments;
- formal-slot bindings;
- materialization state;
- requesting dependency/provenance chain;
- concrete prepared result or diagnostic.

Instance identity must compare exact definition identity and exact canonical
arguments. A hash may index instances but must not be treated as proof of
identity.

### Binding environment

The binding environment is the compile-time analogue of a DescriptiveJS call
context. It maps formal slots to canonical arguments and may refer to an enclosing
environment for nested materialization.

Parameter-reference syntax resolves through the current environment while the
shared template body is prepared. The environment belongs to the instance or
invocation context, never to the shared AST. This permits the same syntax tree to
be processed safely for several concrete instances.

This is **dynamic binding inside the compiler process**, not runtime dynamic
typing or unresolved backend behavior. Every fact required by concrete checking,
layout or code generation must be concrete before reaching that owner.

### Materializer

One materializer should own:

- exact instance lookup and creation;
- materialization state transitions;
- binding-context construction;
- recursive dependency requests;
- cycle and expansion-budget diagnostics;
- publication of completed instances.

AST specializations may route operations to this owner, as current specialization
hooks route preparation and C++ generation. They must not retain the materializer,
workers or mutable invocation contexts.

## Recursion and demand discovery

Recursive processing is a feature of the model, not a reason to copy complete
syntax trees eagerly.

When preparing an instance discovers a selected generic dependency, it requests
the dependent instance through the same materializer. Repeated requests for the
same definition and arguments reuse the same record.

An instance must be registered before its body is prepared. This distinction is
required for recursion:

- a callable that refers to the same callable specialization can reuse its
  already-declared in-progress identity;
- a recursively expanding request that requires another incomplete layout or
  unbounded compile-time expansion must produce a diagnostic;
- indirection may make a recursive type relationship valid, but that decision
  belongs to type/layout semantics rather than the generic cache.

Materialization must retain the request chain so an expansion failure can report
which concrete instance requested the next one.

## Constraints and selection

The DescriptiveJS model accepts dynamic expressions and discovers dependencies
while executing them. The compiler must use the narrower normative policy:

- select a definition from already loaded compile-time surfaces;
- validate concrete arguments and declared concepts/capabilities;
- do not probe arbitrary bodies to select a candidate;
- do not use substitution failure as overload control flow;
- do not search unloaded files during recursive materialization.

Body processing may discover further explicit dependencies after a definition is
selected. It must not decide retroactively that another definition would have
been a better candidate.

## Features served by the common mechanism

Subject to separate syntax and semantic contracts, the same kernel could support:

- generic functions and structs;
- type and compile-time value parameters;
- explicit template applications;
- compile-time conditional selection;
- bounded compile-time repetition or declaration expansion;
- nested generic dependencies;
- metadata-defined runtime/provider families;
- generated fields, operations or declarations;
- exact instance reuse and incremental invalidation.

This list does not authorize all features at once. Each feature still requires an
owning language decision and a narrow proof through the common materializer.

## Relationship to the current `my-try` compiler

The current AST already supplies useful boundaries:

- one final `ast_node` with typed `node_structure` specializations;
- fixed, build-once syntax and linked child traversal;
- specialization hooks that route to process owners;
- explicit `preparation_context` and `cpp_generation_context` invocation state;
- preparation facts stored at their applicable syntax specializations.

The active C++ S2S preparation currently rejects function templates and calls with
template arguments. The new path should replace those explicit deferrals through
one materialization owner. It should not revive or extend the parked LLVM-only
template stack as a second semantic implementation.

The likely integration shape is:

```text
parser
    -> template-definition/application syntax
collector
    -> definition identities and published surfaces
materializer
    -> exact instances and binding environments
existing preparation
    -> concrete signatures, expressions and statements
existing C++ generation
    -> concrete declarations and bodies
```

The `preparation_context` may carry or reference the current binding environment,
but retained syntax and preparation facts must not retain the context itself.

## First vertical slice

A small first proof should include only:

- explicit type parameters on source functions;
- explicit type arguments at calls;
- one shared definition AST processed under per-instance bindings;
- exact reuse of repeated definition/argument pairs;
- a nested generic call that recursively requests another instance;
- a same-specialization recursive callable that reuses its in-progress identity;
- clear rejection of missing, extra, unresolved or unsupported arguments;
- concrete C++ generation and execution for the supported cases.

The slice should exclude deduction, overload ranking, partial specialization,
general compile-time evaluation, packs and unrestricted dependent operations.
Those are not required to prove the materializer shape.

## Size expectation

The complete legacy DescriptiveJS source is about 4,400 JavaScript lines, but its
definition/call/context/recursive-materialization idea is a small fraction of that
code. The estimated language-independent kernel is roughly 250–400 lines. A
compiler implementation with typed records, exact identity, provenance, state
management and diagnostics may be roughly 400–600 lines, excluding existing
parser and semantic owners.

These figures are planning estimates, not an acceptance criterion. Clear ownership
and one reusable path matter more than a line target.

## Non-goals

This direction does not propose:

- copying DescriptiveJS code into the compiler;
- JavaScript evaluation, proxies or runtime dependency discovery;
- DOM-style reverse synchronization;
- one AST copy per concrete instance;
- unresolved template parameters in LLVM IR;
- debug and release language semantics that differ;
- SFINAE, arbitrary body probing or unrestricted compile-time execution;
- a second template implementation inside each backend;
- immediate support for every feature the kernel could eventually express.

## Validation required before adoption

Before implementation, review this shape against:

- the normative metaprogramming contract;
- the current `ast_node` and specialization ownership rules;
- declaration collection and identity ownership;
- preparation-context lifetime and cleanup;
- C++ declaration ordering and recursive-call emission;
- exact cache identity and failure recovery;
- source provenance and diagnostic-chain requirements.

An implementation should prove behavior with focused PHP checks and emitted C++
executions. Compiling `compiler/my-try` itself to a native executable remains
opt-in and must not be run without an explicit user request.

