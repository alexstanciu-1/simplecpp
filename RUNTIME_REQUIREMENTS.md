# Runtime Requirements

## 1. Purpose

This document defines the implementation contract for the Simple C++ runtime.

It translates the language/specification layer into concrete runtime requirements.

This file is subordinate to:
- `SPECIFICATIONS.md`
- `CASTING.md`
- `OBJECT_COMPARISON.md`
- `ARCHITECTURAL_RULES.md`
- `RUNTIME_CODING_RULES.md`
- `RUNTIME_DESIGN_NOTE.md`

If a conflict exists, the language/specification documents win.

---

## 2. Scope

This document covers:
- the public runtime API exposed in `namespace scpp`
- the minimum file/module structure for the runtime
- the v1 implementation priorities
- per-type runtime obligations
- traceability between spec rules and code
- comment requirements for runtime source files
- test obligations for the runtime layer

This document does **not** define:
- frontend lowering rules beyond the codegen contract already defined in the specs
- the future full runtime error policy
- a final ABI stability promise
- every future extension

---

## 3. Runtime Principles

### 3.1 Controlled Semantic Backend

The runtime must act as a controlled semantic backend for generated Simple C++ code.

It must not degrade into a thin aliasing layer over unrestricted C++.

### 3.2 Public Boundary

Generated code may use only the public API exposed inside `namespace scpp`.

The runtime may use `std::*` internally, but this must not leak into the generated-code-visible contract.

Generated code must not directly use native primitives, direct `std::*` types/functions, or native C++ structures/classes as generated-language-visible values.

### 3.3 Spec-driven Behavior

Any runtime-visible conversion, operator, or comparison must be traceable to an explicit spec rule.

Anything not defined by the specs must remain unavailable.

### 3.4 No Fallback Semantics

The runtime must not silently fall back to native C++ behavior where Simple C++ does not define behavior.

### 3.5 Explicit Runtime Entry

Generated values must enter the runtime model explicitly.

Examples:

```cpp
auto x = (int_t)12;
auto s = (string_t)"abc";
auto a = create<MyClass>();
auto b = shared<MyClass>();
auto c = unique<MyClass>();
auto w = weak(a);
```

---

## 4. Priority Model

### 4.1 V1 Required

The following are required for runtime v1:
- namespace boundary and public surface
- `null_t`
- `bool_t`
- `int_t`
- `float_t`
- `string_t`
- `nullable<T>`
- `shared_p<T>`
- `weak_p<T>`
- `unique_p<T>`
- `create<T>()`
- `shared<T>()`
- `unique<T>()`
- `weak(x)`
- primitive arithmetic/comparison/logical coverage required by `CASTING.md`
- object/null comparison coverage required by `OBJECT_COMPARISON.md`
- compile-time blocking of forbidden conversions where practical

### 4.2 V1 Limited / Minimal

The following may exist in minimal form in v1:
- `vector_t<T>`
- limited helper accessors used internally by the runtime
- explicit conversion helpers needed to support `(type)expression` lowering

### 4.3 Deferred

The following are deferred unless later required:
- full runtime error model
- broader string parsing policy beyond currently defined explicit conversions
- richer container API
- user-defined conversion hooks
- direct ABI commitments
- extended reference semantics

---

## 5. Public Runtime Surface

The runtime must expose the following public surface in `namespace scpp`:

### 5.1 Value Types
- `null_t`
- `bool_t`
- `int_t`
- `float_t`
- `string_t`

### 5.2 Wrapper / Composite Types
- `nullable<T>`
- `vector_t<T>`
- `shared_p<T>`
- `weak_p<T>`
- `unique_p<T>`

### 5.3 Ownership Helpers
- `create<T>(...)`
- `shared<T>(...)`
- `unique<T>(...)`
- `weak(x)`

### 5.4 Aggregation

The runtime should provide one simple aggregation header:
- `scpp/runtime.hpp`

---

## 6. Requirement ID Scheme

Requirement IDs must remain short but understandable.

Format:

`RT-<AREA>-<NN>`

Examples:
- `RT-NS-01`     → namespace rule
- `RT-NULL-02`   → null behavior rule
- `RT-INT-05`    → integer wrapper rule
- `RT-STR-04`    → string wrapper rule
- `RT-NBL-03`    → nullable rule
- `RT-MEM-06`    → ownership helper rule
- `RT-SH-04`     → shared ownership comparison/null rule
- `RT-CGEN-02`   → code-generation-facing rule
- `RT-TST-03`    → test rule

The area code must be explicit enough to understand quickly and short enough to reference comfortably in code comments.

---

## 7. Module / File Plan

The initial runtime should be organized into the following files:

### 7.1 Public Headers
- `include/scpp/runtime_fwd.hpp`
- `include/scpp/null_t.hpp`
- `include/scpp/bool_t.hpp`
- `include/scpp/int_t.hpp`
- `include/scpp/float_t.hpp`
- `include/scpp/string_t.hpp`
- `include/scpp/nullable.hpp`
- `include/scpp/vector_t.hpp`
- `include/scpp/shared_p.hpp`
- `include/scpp/weak_p.hpp`
- `include/scpp/unique_p.hpp`
- `include/scpp/memory.hpp`
- `include/scpp/runtime.hpp`

### 7.2 Source Files
- `src/bool_t.cpp`
- `src/int_t.cpp`
- `src/float_t.cpp`
- `src/string_t.cpp`

Template-heavy wrappers may remain header-only.

---

## 8. Cross-cutting Runtime Requirements

### 8.1 Namespace and Visibility

#### RT-NS-01
All public runtime types and helpers must live in `namespace scpp`.

#### RT-NS-02
No generated-code-visible API may require direct use of the global namespace.

#### RT-NS-03
Internal implementation may use `std::*`, but that use must remain behind the public runtime boundary.

### 8.2 Public Surface Discipline

#### RT-PUB-01
The runtime must expose only API needed by generated code and controlled runtime-internal code.

#### RT-PUB-02
Public APIs must not leak raw pointers.

#### RT-PUB-03
Public APIs must not require generated code to manipulate `std::string`, `std::optional`, `std::shared_ptr`, `std::unique_ptr`, or `std::weak_ptr` directly.

### 8.3 Spec Traceability

#### RT-TRACE-01
Each public class/function must be traceable to one or more runtime requirement IDs.

#### RT-TRACE-02
Each runtime requirement should reference its source spec documents.

### 8.4 Failure Mode Discipline

#### RT-FAIL-01
Anything forbidden by the language specs must remain unavailable in the runtime API where practical.

#### RT-FAIL-02
Where compile-time blocking is practical, it is preferred over runtime failure.

#### RT-FAIL-03
The runtime must not invent fallback coercions not defined in the specs.

---

## 9. Type Requirements

## 9.1 `null_t`

Source basis:
- `SPECIFICATIONS.md` type system and null model
- `CASTING.md` null behavior and comparison rules

#### RT-NULL-01
The runtime must provide a public `null_t` type and a public constant `null`.

#### RT-NULL-02
`null_t` must represent both pointer-null and optional-empty semantics.

#### RT-NULL-03
`null_t` may interoperate with `std::nullptr_t` and `std::nullopt_t` internally or at the generated-code boundary only as required by the specs.

#### RT-NULL-04
`null_t` must not implicitly convert to primitive wrappers.

#### RT-NULL-05
`null_t` must remain context-resolved and must not become a general-purpose dynamic value carrier.

#### RT-NULL-06
`null_t == null_t` and `null_t != null_t` must behave exactly as specified.

#### RT-NULL-07
Comparison with `null` must be available only where permitted by the specs.

#### RT-NULL-08
An untyped `auto x = null;` path must remain rejected by the frontend/codegen contract; the runtime must not try to normalize that into a generic boxed value.

## 9.2 `bool_t`

Source basis:
- `SPECIFICATIONS.md` primitive model
- `CASTING.md` conversion matrix, logical rules, comparison rules

#### RT-BOOL-01
`bool_t` must wrap one boolean value and behave as a value type.

#### RT-BOOL-02
`bool_t` must support explicit construction from native boolean input for runtime entry.

#### RT-BOOL-03
`bool_t` must support equality and inequality with `bool_t`.

#### RT-BOOL-04
`bool_t` must support logical operators required by `CASTING.md`.

#### RT-BOOL-05
`bool_t` must support the implicit `bool -> int` path through the runtime-visible wrapper model.

#### RT-BOOL-06
Arithmetic on `bool_t` must not be exposed.

#### RT-BOOL-07
String/numeric/pointer-like conversions involving `bool_t` must follow the explicit/implicit matrix exactly.

## 9.3 `int_t`

Source basis:
- `SPECIFICATIONS.md` primitive model
- `CASTING.md` arithmetic, comparison, conditional, conversion rules

#### RT-INT-01
`int_t` must store an 8-byte signed integer semantic value backed by `long long`.

#### RT-INT-02
`int_t` must behave as a value type.

#### RT-INT-03
`int_t` must support arithmetic operators `+`, `-`, `*`, `/` against `int_t` and mixed arithmetic with `float_t` as defined.

#### RT-INT-04
`int_t` must support comparison operators against `int_t` and `float_t` exactly as defined.

#### RT-INT-05
`int_t` must support conditional evaluation semantics used by the language (`0` false, non-zero true) without implying a general implicit conversion to `bool_t`.

#### RT-INT-06
`int_t` must support the implicit `int -> float` path.

#### RT-INT-07
`int_t` must support explicit conversion paths defined by the matrix and no others.

#### RT-INT-08
Arithmetic with `bool_t`, `null_t`, and pointer-like types must remain unavailable.

## 9.4 `float_t`

Source basis:
- `SPECIFICATIONS.md` primitive model
- `CASTING.md` arithmetic, comparison, explicit conversion rules

#### RT-FLOAT-01
`float_t` must store an 8-byte floating semantic value backed by `double`.

#### RT-FLOAT-02
`float_t` must behave as a value type.

#### RT-FLOAT-03
`float_t` must support arithmetic operators required by `CASTING.md`.

#### RT-FLOAT-04
Mixed `int_t` / `float_t` arithmetic and comparison must promote the `int_t` side to `float_t` semantics.

#### RT-FLOAT-05
`float_t` is not valid in conditional expressions by default under the language rules; the runtime must not expose a general truthiness API that undermines this rule.

#### RT-FLOAT-06
`float_t` must support only the explicit conversion paths defined by the matrix.

## 9.5 `string_t`

Source basis:
- `SPECIFICATIONS.md` wrapped type model
- `CASTING.md` comparison rules, string operators, explicit conversions

#### RT-STR-01
`string_t` must wrap `std::string` internally while preserving a Simple C++ runtime-visible wrapper surface.

#### RT-STR-02
`string_t` must be passable by `const &` in generated/runtime-facing APIs by default.

#### RT-STR-03
`string_t` must support `+` and `+=` with `string_t` only.

#### RT-STR-04
`string_t` must support lexicographic equality/inequality and relational comparison against `string_t`.

#### RT-STR-05
Implicit numeric concatenation must remain unavailable.

#### RT-STR-06
Explicit conversion from `string_t` to `int_t`, `float_t`, and `bool_t` must follow the allowed conversion matrix.

#### RT-STR-07
`string_t` to `bool_t` conversion must enforce the currently allowed values exactly: `"1"`, `"0"`, `"true"`, `"false"`.

#### RT-STR-08
If a string-to-primitive explicit conversion is invalid at runtime, the runtime must route through one central failure path or throw path so that a later error policy can replace it cleanly.

## 9.6 `nullable<T>`

Source basis:
- `SPECIFICATIONS.md` nullable model
- `CASTING.md` null assignment and comparison constraints
- `OBJECT_COMPARISON.md` nullable comparison rules

#### RT-NBL-01
`nullable<T>` must wrap optional-like storage and integrate with `null_t`.

#### RT-NBL-02
`nullable<T>` must support construction from `null` and from `T`.

#### RT-NBL-03
`nullable<T>` must expose a clear empty/non-empty state for runtime use.

#### RT-NBL-04
`nullable<T> == nullable<T>` must compare as specified: both null true, one null false, both non-null compare underlying values.

#### RT-NBL-05
`nullable<T> == null` and `nullable<T> != null` must test empty state.

#### RT-NBL-06
Relational operators on `nullable<T>` must be available only when both sides are non-null and `T` supports the operation.

#### RT-NBL-07
`nullable<T>` must not expose pointer-like ownership semantics.

## 9.7 `vector_t<T>`

Source basis:
- `SPECIFICATIONS.md` wrapped type model
- `CASTING.md` vector behavior section

#### RT-VEC-01
`vector_t<T>` must wrap vector-like storage internally.

#### RT-VEC-02
Arithmetic operators on `vector_t<T>` must remain unavailable.

#### RT-VEC-03
Indexing must be supported.

#### RT-VEC-04
Append must be supported in a form the frontend can lower to from the Simple C++ append model.

#### RT-VEC-05
A minimal v1 implementation is acceptable provided it does not expose behavior outside the spec.

## 9.8 `shared_p<T>`

Source basis:
- `SPECIFICATIONS.md` ownership and pointer-like model
- `OBJECT_COMPARISON.md` identity/null rules

#### RT-SH-01
`shared_p<T>` must provide shared managed ownership.

#### RT-SH-02
`shared_p<T>` must not expose raw-pointer ownership transfer in its public generated-code-facing API.

#### RT-SH-03
`shared_p<T> == shared_p<T>` and `!=` must compare identity.

#### RT-SH-04
`shared_p<T> == null` and `!= null` must test empty/null state.

#### RT-SH-05
Relational operators for `shared_p<T>` must remain unavailable.

#### RT-SH-06
Cross-wrapper comparison with `unique_p<T>` or `weak_p<T>` must remain unavailable.

## 9.9 `unique_p<T>`

Source basis:
- `SPECIFICATIONS.md` ownership and pointer-like model
- `OBJECT_COMPARISON.md` identity/null rules

#### RT-UQ-01
`unique_p<T>` must provide exclusive managed ownership.

#### RT-UQ-02
`unique_p<T>` must support move semantics internally while keeping generated-code-visible behavior controlled.

#### RT-UQ-03
`unique_p<T> == unique_p<T>` and `!=` must compare identity.

#### RT-UQ-04
`unique_p<T> == null` and `!= null` must test empty/null state.

#### RT-UQ-05
Relational operators for `unique_p<T>` must remain unavailable.

#### RT-UQ-06
Cross-wrapper comparison with `shared_p<T>` or `weak_p<T>` must remain unavailable.

## 9.10 `weak_p<T>`

Source basis:
- `SPECIFICATIONS.md` weak-reference model
- `OBJECT_COMPARISON.md` weak comparison rules

#### RT-WK-01
`weak_p<T>` must be non-owning.

#### RT-WK-02
`weak_p<T>` must not be a primary allocation result.

#### RT-WK-03
`weak_p<T>` must be derivable from an owning managed value through `weak(x)`.

#### RT-WK-04
Expired weak references must behave as `null` in comparison contexts.

#### RT-WK-05
`weak_p<T> == weak_p<T>` and `!=` must follow resolved-identity / expired-null rules exactly.

#### RT-WK-06
`weak_p<T> == null` and `!= null` must test empty/expired state.

#### RT-WK-07
Relational operators for `weak_p<T>` must remain unavailable.

## 9.11 Ownership Helpers

Source basis:
- `SPECIFICATIONS.md` ownership model and allocation rules
- `CASTING.md` code generation rule
- `ARCHITECTURAL_RULES.md` explicit runtime entry

#### RT-MEM-01
`create<T>(...)` must exist as the default managed creation helper.

#### RT-MEM-02
The current v1 behavior of `create<T>(...)` must be equivalent to `shared<T>(...)` in ownership result.

#### RT-MEM-03
`shared<T>(...)` must produce `shared_p<T>`.

#### RT-MEM-04
`unique<T>(...)` must produce `unique_p<T>`.

#### RT-MEM-05
`weak(x)` must derive a `weak_p<T>` from an existing owning managed value.

#### RT-MEM-06
`weak(x)` must not allocate.

#### RT-MEM-07
Ownership choice must not be inferred from operator usage or optimization.

#### RT-MEM-08
The helper surface must be stable enough for generated code to target directly.

---

## 10. Code Generation-facing Requirements

Source basis:
- `SPECIFICATIONS.md`
- `CASTING.md`
- `ARCHITECTURAL_RULES.md`
- `RUNTIME_DESIGN_NOTE.md`

`RT-CGEN-06` and `RT-CGEN-07` define hard generated-code boundary invariants.
All other code-generation-facing requirements operate within those constraints.

#### RT-CGEN-01
Generated code must be able to construct runtime values explicitly through wrapper constructors or explicit wrapper entry points.

#### RT-CGEN-02
Generated code must be able to create managed objects through `create<T>()`, `shared<T>()`, and `unique<T>()`.

#### RT-CGEN-03
Generated code must be able to derive non-owning references through `weak(x)`.

#### RT-CGEN-04
Generated code must not need to emit raw C++ allocation primitives for language-level object creation.

#### RT-CGEN-05
Generated code must not need to name internal storage types such as `std::string` or smart-pointer types.

#### RT-CGEN-06
Generated Simple C++ code must never contain native C++ primitive types, direct `std::*` usage, or native C++ structures/classes as generated-language-visible values.

#### RT-CGEN-07
Native interoperability belongs to explicit C++ bridge/integration code outside the generated Simple C++ semantic surface.

## 11. Commenting Requirements for Source Code

#### RT-CMT-01
Each public class, public helper function, and public operator overload must include comments referencing the requirement IDs it implements.

#### RT-CMT-02
Comments must state the semantic purpose of the element, not just restate the syntax.

#### RT-CMT-03
Where useful, comments should state what the element must **not** expose or must **not** imply.

#### RT-CMT-04
Internal members may use shorter comments, but any internal member that protects an important semantic boundary should also cite requirement IDs.

Recommended comment pattern:

```cpp
// Implements: RT-INT-01, RT-INT-03, RT-CGEN-01
// Purpose: semantic integer wrapper used by generated Simple C++ code.
// Constraint: must not expose unrestricted native arithmetic or conversion behavior.
```

---

## 12. Test Requirements

#### RT-TST-01
Every public wrapper/helper must have at least one positive construction test.

#### RT-TST-02
Every allowed operator family must have positive tests.

#### RT-TST-03
Every forbidden conversion/operator family that can be blocked at compile time should have negative compile tests.

#### RT-TST-04
Ownership helpers must have result-type tests.

#### RT-TST-05
Object comparison rules must have identity/null/expired coverage.

#### RT-TST-06
String-to-bool explicit conversion must have valid and invalid tests.

#### RT-TST-07
Nullable comparison behavior must have both null and non-null coverage.

---

## 13. Implementation Order

Recommended order:

1. `runtime_fwd.hpp`
2. `null_t.hpp`
3. `bool_t.hpp`
4. `int_t.hpp`
5. `float_t.hpp`
6. primitive arithmetic/comparison support
7. `string_t.hpp`
8. `nullable.hpp`
9. `shared_p.hpp`
10. `weak_p.hpp`
11. `unique_p.hpp`
12. `memory.hpp`
13. `vector_t.hpp`
14. aggregation header and tests

This order minimizes semantic drift and keeps the first runtime slice aligned with the most explicit parts of the specs.

---

## 14. Initial Code-generation Target Set

The first generated/runtime-facing code set should be able to support at least:

```cpp
auto a = (int_t)12;
auto b = (float_t)4.5;
auto c = a + b;
auto s = (string_t)"abc";
auto n = null;
nullable<int_t> x = null;
auto o1 = create<MyClass>();
auto o2 = shared<MyClass>();
auto o3 = unique<MyClass>();
auto ow = weak(o1);
```

This target set is not the whole language, but it is enough to validate the initial runtime kernel.


## Hardening Note
The runtime now prefers explicit deleted overloads, deleted constructors, and constrained templates/concepts for unsupported or type-dependent paths so forbidden operations fail deterministically at compile time where practical.
