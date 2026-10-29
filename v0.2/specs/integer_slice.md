# Integer metadata and native API contract

Doc Status: normative

Scope: the first standalone v0.2 integer slice, not full language/compiler admission.
The Rust tool emits C++23 with Clang/GCC fixed-width type macros and uses explicit
native C++ probe facts. Generation runs for the
native target; cross-compilation and portable prebuilt ABI packages are not provided.

## Owners

- `runtime/metadata/runtime.json`: integer identities, supported native constructor
  input signatures, checked conversion policy, domain promotion and operation-form bindings.
- `runtime/metadata/publication.json`: concrete type/operator demands and header groups.
- `tools/api_generator/src/scalar.rs`: validates those records and prepares concrete
  types, native range facts, conversion guarantees and operation execution/result types.
- `tools/api_generator/src/emission.rs`: emits only the prepared surface. It
  neither infers source semantics nor chooses an alternative promotion policy.
- `runtime/src/numeric/scalar_semantics.hpp`: private range conversion and arithmetic.

The generator is independent of the generated runtime. Generated artifacts live in
the selected CMake build directory's `generated/`; no old-tree headers, libraries or tools are used. There is one active
runtime generator in v0.2. The JSON prepared-plan description is diagnostic/tooling
output, not a validated external source-admission protocol.

## Value and conversion behavior

Published int32/int64 are concrete, zero-initialized, trivially copyable values with
native-sized aligned storage. `native_value()` is an explicit observation; there is
no implicit conversion back to native arithmetic types. Public include closures
expose no templates or private implementation headers. `short_names.hpp` is optional.

All admitted numeric constructors are explicit and check representability before
conversion. `static_cast` uses the same constructors. Cross-public-type construction
is emitted only among published types. Lossless source ranges produce noexcept
constructors; native ranges are obtained from the validated C++ layout probe and
asserted in the generated runtime implementation. Regenerate for a different target.

For this standalone slice, failure throws standard `std::out_of_range` with
`integer_cast_out_of_range` as its message. This is a bounded bootstrap error contract,
not adoption of the old runtime's structured exception/trace ABI. Integration with a
future runtime error surface remains explicit work. No runtime error module is imported.

The six metadata-listed fundamental native types get exact constructor signatures.
Omitted supported fundamental integer types receive deleted signatures. Short,
unsigned short, boolean, character, floating and Clang/GCC 128-bit integer inputs
are explicitly deleted in this initial slice to avoid premature standard numeric
conversion. This restriction can evolve when those inputs are admitted. Arbitrary
user-defined conversion functions and enums remain subject to normal C++ overload
resolution; the API does not promise to prohibit every user-defined adaptation.

## Integer operators and publication

Same-type integer arithmetic and bitwise operations keep their type. Differing signed types normalize to
int64; differing unsigned types to uint64. Signed/unsigned mixtures normalize to
int64 only when the unsigned width is below 64; signed/uint64 pairs reject. Every
implicit operand conversion must be lossless. Missing execution types must be
explicitly added to the publication, not silently generated outside its request.

The default publication contains int32/int64 plus the simple values described in
[the value contract](values_slice.md). Integer signatures are requested by operation
and operands. Same-type operators cover binary arithmetic/bitwise, comparison,
unary, shifts and mutation. The prior mixed addition pair and a mixed checked
write-back example remain. Optional native-int adapters cover arithmetic/bitwise
and corresponding compound assignment. It does not publish a Cartesian mixed matrix.
Operators are the only public arithmetic spelling; there is no numeric_add export.
Private templates share the arithmetic algorithm. Same-type implementation targets
do not imply a separate out-of-line function per target after compiler optimization.

Preserve the former native arithmetic domain: signed overflow is not defined by
this API and does not acquire checked or wrapping semantics. Tests exercise only
representable signed sums. Unsigned arithmetic retains modular conversion behavior.
Checked construction must not be mistaken for checked addition. No post-overflow
promotion or recovery occurs. This includes signed division/remainder of minimum
by -1: it remains outside the defined domain rather than gaining a new checked
arithmetic policy. Division/remainder by zero throw std::domain_error with
`division_by_zero`/`modulo_by_zero`. Signed division truncates toward zero and the
remainder follows C++ sign rules. Unsigned arithmetic is modular at the target
width; unsigned intermediates avoid promoted signed overflow for small operands.

The default two-type publication is compiled end to end. An independent generated
publication compiles all eight integer identities with the eight binary operators,
checks exact result types and exercises signed/unsigned behavior. Planning tests
also cover 56 admitted addition pairs. Unary, comparison, shifts and mutation now
have their own form-specific contracts in [values_slice.md](values_slice.md).

## Native arithmetic operand adaptation

Arithmetic/bitwise binary and compound operation metadata records enable `native_arguments` with `policy: "type_range"` and
`inputs: "native_integer_inputs"`, referencing the existing constructor input
inventory. Each publication request identifies native arguments structurally:

```json
{
  "operation": "add",
  "operands": ["int32", {"native": "int"}],
  "both_orders": true
}
```

Preparation maps a native type's full signedness/range to an exact registered
integer identity, then passes those identities through the same domain-maximum
resolver as public/public pairs. Mapping does not publish another class or require
the equivalent type to be public. The selected execution/result type must be public.
No literal-value inspection, checked narrowing or premature native conversion occurs.
Unsupported ranges, missing identities, native-only requests and duplicate operation/operand tuples
reject before emission. Native-only C++ arithmetic remains the C++ compiler's job.

The default requests only native int: both directions for value arithmetic,
and right operands only for compound assignment.
With 32-bit int, int32 + int returns int32; int64 + int returns int64. Larger or
suffixed native literals require explicit checked conversion to a runtime type.
C++ cannot distinguish literals from variables of the same native type; restricting
native int usage to literals is coding discipline, not a property of the overloads.
The resolver retains its existing general native range model for reviewed future
publications; this does not publish additional native adapters by default.

Prepared operands record C++ spelling, normalized identity and native/public role.
Native adapters are inline functions in the optional bridge. Value adapters
explicitly cast both operands to the resolved execution type. Compound adapters
retain the original left reference and explicitly cast the right operand. Both
delegate to the requested core operator. Preparation requires that operator to be explicitly requested; it does
not silently publish dependencies. Core generated source contains only runtime-type
operators and checked constructors. No public implicit conversion or second
arithmetic owner is introduced. Constructors remain explicit: use `int64 x{12}` or
`static_cast<int64>(12)`, not `int64 x = 12`. Implicit int construction was considered
and rejected because copy initialization can bypass explicit constructors for other
native types, narrowing to int before the runtime can check the original value.

For each published type, the bridge declares exact deleted overloads for unrequested
fundamental native inputs in either direction. Without them, standard conversions
could select an admitted overload or produce ambiguity. These declarations have no
runtime bodies. Bridge rejection signatures include guarded 128-bit types. Only operations with requested
native adapters receive bridge fences; these are not additional runtime algorithms. Core-only consumers need no
native fences: explicit constructors already prevent native operator adaptation.
Enums and user-defined adapters still follow normal C++ rules as noted above.
Simple C++ compiler lowering should target core runtime types and explicit casts;
it does not depend on this optional handwritten-C++ convenience header.

Acceptance includes combined-header Clang resolution checks, exact result types,
both operand orders and const/lvalue/rvalue forms, native boundary values, and
negative compile probes. A separate one-direction publication proves unrequested
reverse, integer and floating inputs select deleted candidates rather than an
unintended numeric conversion. Compiler-source preparation remains separate.

## Generation and limits

The CMake-configured Rust launcher accepts metadata, publication and an output
directory, with optional
`--check` for read-only freshness. Complete validation/emission precedes file writes.
An existing inventory containing retired output paths rejects and requires fresh
staging. I/O failure during writes is not transactionally rolled back; use a fresh
staging directory for publication. No package sealing/import ABI protocol is claimed.

Output is deterministic for the same metadata and native target. Type and operator
request order is normalized. Each output includes an inventory and a prepared-plan
description. The initial CMake integration uses the default publication's known
header groups; arbitrary header regrouping requires updating that integration.

Floating/scalar-cast behavior is covered by [floating_slice.md](floating_slice.md).
Carriers, application imports, source-prepared evidence and compiler integration are excluded.
No claim of safe source sequencing follows from a physical operator plan.

## Reuse provenance

Reviewed reference revision: `a2dbdb018` in the old tree. Naming/identity roles and
domain-maximum selection come from public/module metadata and the scalar portions
of `runtime/tools/api_generator/operators.cpp`. The prepare-then-emit boundary is
retained with a bounded typed integer plan, replacing the broader carrier-dependent
registry in this slice. This is an adapted extraction, not a byte-for-byte import.
Scalar class emission adapts `runtime/tools/concrete_integers.py` into the common
native generator, with explicit checked constructors and requested-only operators.
Checked range behavior comes from `runtime/include/scpp/cast.hpp` and uses standard
`std::in_range`; the addition primitive preserves the same-type expression in
`runtime/include/scpp/generated/operator_detail.hpp`. These paths are historical
references only. No second engine or old runtime dependency is maintained.

## Binary operation extension provenance

The scalar subtraction/multiplication, division/remainder and bitwise expressions
were reviewed in legacy `runtime/include/scpp/generated/operator_detail.hpp` at the
same reference revision. Only those primitive responsibilities were adapted into
`runtime/src/numeric/scalar_semantics.hpp`; no legacy wrapper, dispatch registry,
error ABI or transitive headers were imported. Zero failures use the standalone
slice's standard exception boundary. Operation binding, symbol and implementation
are now prepared metadata facts, keyed together with operand types. The generator
validates bindings against implemented primitive capabilities before publication.
