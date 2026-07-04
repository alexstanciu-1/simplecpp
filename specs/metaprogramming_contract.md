# Metaprogramming Contract
Doc Status: normative

Status: Draft contract for future implementation.
Purpose: define the intended Simple C++ metaprogramming boundary so future
compiler work can support generic materialization without growing into
unbounded C++-style template behavior.

This document defines language and compiler policy. It intentionally avoids
describing any one compiler implementation's internal data structures.

## 1. Core Position

Simple C++ may support metaprogramming, but the supported model is bounded,
deterministic, and interface-bound.

Generic discovery must complete in a known, bounded number of compiler steps
for a given loaded program surface. A compiler may walk explicit dependencies
discovered from a generic use, but it must not perform open-ended speculative
search to determine whether a generic use is valid.

The compiler is allowed to reject code that would require unbounded discovery,
body-validity probing, unresolved dynamic symbols, or arbitrary compile-time
execution to determine its meaning.

## 2. Logical Compiler Layers

Simple C++ metaprogramming assumes these logical layers:

1. Source intake and parsing.
2. Compile-time surface discovery.
3. Generic materialization and discovery.
4. Concrete semantic compilation.
5. Lowering, code generation, and backend execution.

These are logical layers, not necessarily separate executables or separate full
syntax trees.

The key boundary is between generic materialization and concrete compilation.
Generic declarations, concepts, type families, and compile-time values belong
to the materialization layer. Concrete compilation should see resolved,
concrete types, operations, and callable targets.

## 3. Bounded Interface-Bound Discovery

A generic use may be resolved from:

- concrete non-dynamic types;
- explicit concepts or interfaces;
- loaded generic declarations;
- loaded type-family metadata;
- loaded operation metadata;
- loaded runtime or ABI metadata;
- compile-time literal values with known types;
- imported compile-time surfaces from already known source units or modules.

A generic use must not require resolution from:

- arbitrary runtime values;
- dynamic symbols whose compile-time surface is unknown;
- probing whether a body happens to compile;
- substitution failure as an overload-selection strategy;
- searching unloaded files during materialization;
- recursively expanding an unbounded compile-time program;
- compiler-specific hidden knowledge not represented in the loaded surfaces.

If a generic use cannot be resolved through explicit loaded interfaces and
known compile-time values, the compiler should report a diagnostic instead of
guessing.

## 4. Compile-Time Surfaces

Any generic declaration intended for cross-file or cross-module use must publish
a compile-time surface. That surface is the contract other files may use for
generic discovery.

A compile-time surface should include:

- exported name;
- generic parameter list;
- parameter kinds;
- constraints or concepts;
- callable parameter types;
- return type rule;
- relevant operation requirements;
- relevant ABI or runtime binding metadata when the declaration is runtime
  backed;
- a version or shape identity suitable for incremental invalidation.

The body of a generic declaration may discover additional explicit materialized
dependencies after the declaration has already been selected. The body must not
be required to decide whether the declaration is a viable candidate.

## 5. Generic Parameter Kinds

The intended first-class parameter kinds are:

- type parameters;
- compile-time value parameters;
- concept or interface constraints;
- type-family parameters represented through explicit metadata;
- ABI or layout parameters represented through explicit metadata.

Examples:

```text
vector<T>
  T: type parameter
  T constraint: value_storable

hash<K, V>
  K: type parameter
  K constraint: hashable and comparable
  V: type parameter
  V constraint: value_storable

fixed_array<T, N>
  T: type parameter
  T constraint: value_storable
  N: compile-time value parameter
  N constraint: non-negative integer known at compile time
```

The following parameter kinds are not part of the initial contract unless a
future normative spec explicitly promotes them:

- arbitrary function or policy parameters;
- unconstrained template-family parameters;
- lifetime parameters;
- backend strategy parameters;
- runtime-value-dependent parameters.

## 6. Concepts And Constraints

Concepts are the primary mechanism for controlling generic validity.

A concept should describe a compile-time capability, not a hidden body probe.
Examples include:

- `value_storable`;
- `hashable`;
- `comparable`;
- `copyable`;
- `movable`;
- `layout_known`;
- `abi_lowerable`.

Constraint checks should be deterministic and based on loaded type, concept,
operation, and metadata surfaces.

The compiler may reject generic code that omits a necessary concept and then
depends on operations that are not guaranteed by the declared interface.

## 7. Explicitly Rejected C++-Style Behavior

Simple C++ should not support C++ SFINAE-style failure-as-selection.

Rejected patterns include:

- selecting overloads by trying arbitrary substitutions and keeping the one
  whose body does not fail;
- using invalid body expressions as a normal control-flow path in generic
  resolution;
- delaying ordinary symbol lookup until unrelated future code is discovered;
- allowing unconstrained dependent member calls to define a generic interface;
- treating compiler diagnostics as a metaprogramming mechanism;
- instantiating all possible type combinations from a generic declaration.

This restriction is intentional. Simple C++ favors predictable compilation,
clear diagnostics, and incremental friendliness over maximum metaprogramming
expressiveness.

## 8. Demand-Driven Materialization

Simple C++ generic materialization should be demand-driven.

A generic declaration or type family is a recipe. It should not materialize
every possible concrete instance.

When concrete source usage requires an instance, the compiler may create the
needed concrete type, operation, layout, or callable instance and then discover
further explicit demands from that instance.

For example:

```text
source uses vector<int64>
  materialize vector<int64> type instance

source calls vector_get(items, 0), where items is vector<int64>
  materialize vector_get for vector<int64>
  result type is int64
```

If a future generic function calls another generic function, materialization may
walk that explicit dependency:

```text
append<int64>
  discovers append_safe<int64>
  discovers vector<int64>.push
```

This is allowed because the path is discovered from concrete, selected,
interface-bound instances. It is not allowed to branch into unbounded speculative
candidate probing.

## 9. Cross-File And Module Discovery

Cross-file generic discovery is allowed only through published compile-time
surfaces.

A compiler may use already-loaded source-unit, module, package, runtime, and ABI
metadata to resolve generic uses. It must not search or parse arbitrary unloaded
files during generic materialization.

Cold compilation may build the initial compile-time surfaces for all relevant
project inputs. Incremental compilation may reuse resident surfaces and refresh
only changed or invalidated surfaces. In both cases, generic materialization
should operate over known surfaces rather than ad hoc filesystem search.

## 10. Incremental Compilation Contract

Generic surfaces and materialized instances should have stable identities so an
incremental compiler can decide what changed.

The contract requires stable identity for at least:

- concept definitions;
- generic declarations;
- type-family definitions;
- operation-family definitions;
- runtime or ABI binding metadata;
- concrete type instances;
- concrete operation instances;
- concrete callable instances.

Changing a published generic interface may invalidate concrete instances that
depend on it. Changing only a generic body should not invalidate unrelated
callers unless the materialized body shape or emitted code for their concrete
instances changes.

## 11. Diagnostics

Metaprogramming diagnostics should explain the explicit resolution path.

A diagnostic should identify:

- the generic target being resolved;
- the provided type and value arguments;
- the concept or interface that failed;
- the source location of the use;
- the dependency chain that requested the instance when available;
- whether the failure is an unsupported feature or an invalid program.

Diagnostics should not expose compiler implementation internals as the primary
explanation.

## 12. Current And Future Scope

The expected first implementation area is metadata-defined type families such
as:

- `vector<T>`;
- `hash<K, V>`;
- `fixed_array<T, N>`.

Future expansion may add user-authored generic functions and generic types, but
they must continue to obey this contract:

- bounded deterministic discovery;
- explicit concepts or concrete non-dynamic types;
- published compile-time surfaces for cross-file use;
- demand-driven materialization;
- no failure-as-selection metaprogramming.

If a future feature needs more power than this contract allows, that feature
must first update this normative contract explicitly rather than expanding the
compiler behavior by accident.
