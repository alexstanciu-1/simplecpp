# Agreed scalar families and conversion plan

Doc Status: planning
Date: 2026-11-06
Scope: v0.2 scalar metadata, generated compiler types and their runtime bindings.
Status: identity, active numeric family conversions, integer arithmetic/comparisons, checked integer compounds and mixed numeric operator families implemented. Collections are separate.

The [normative scalar amendment](../../../specs/compiler_scalars_v0_2.md#agreed-family-and-representation-model--2026-11-06)
records the decision. This document organizes implementation, not proof of support.
The [identity slice](../../compiler/docs/byte_alias.md) and [active family conversions](../../compiler/docs/family_conversions.md) are implemented. Optional representations and remaining operator groups await separate slices.

## Family, representation and activation

| Family | Default identity/spelling | Representation facts |
| --- | --- | --- |
| Null | null | One value; distinct from void/unknown |
| Boolean | bool | false/true |
| Integer | int = int64 | Width, signedness and exact range; byte = uint8 |
| Floating | float = float64 | Format, precision, exponent/range and signed zero |
| String | string | Explicit-length byte sequence |

Families own operation and conversion algorithms. Concrete representations supply
parameters, not independent handwritten matrices. Generate specialized concrete
classes/handlers with direct dispatch; do not introduce metadata interpretation or
conversion-path search during preparation. AST facts continue to reference canonical
definitions; no redundant prepared tree or speculative retained registry is needed.

Byte is an ordinary unsigned eight-bit integer and a true alias of uint8. Both names
resolve to one canonical identity, including reference binding. Remove the earlier
bitwise-only restriction. Arithmetic/overflow/write-back follow the agreed integer
policies, not a byte-specific rule. Checked integer conversion remains value-preserving:
out-of-range input does not silently wrap. Alias identity does not imply that every
optional source spelling/module is activated by default; runtime module metadata
continues to own activation. Exact source-name exposure is settled during migration.

Unsigned types are not merely narrower signed integers: uint64 and int64 have
incomparable value domains. Floating storage width and operation execution precision
must also be distinguished. Do not infer either policy from host PHP/C++ conversions.
The runtime integer-domain promotion table was adopted on 2026-11-06 for arithmetic;
comparisons use exact integer values. The active int64/uint8 arithmetic and comparison
groups are implemented; active int64/uint8 compounds now use checked result write-back. char is deferred until byte/code-unit/
code-point meaning is agreed; do not inherit platform-dependent C++ char semantics.

## Agreed conversion behavior (when permitted)

| Source → destination | Behavior |
| --- | --- |
| Same canonical type | Identity; aliases count as the same type |
| Integer → integer | Preserve value; check range unless the destination covers the whole source domain |
| Integer → floating | Numeric conversion; rounding allowed |
| Floating → integer | Truncate toward zero, range-check; reject NaN/infinity |
| Floating → floating | Exact widening; narrowing rounds, accepts underflow/signed zero, rejects finite overflow |
| Bool → integer/floating | false → 0, true → 1 |
| Integer/floating → bool | Zero → false; nonzero → true, including NaN |
| String → string | Existing value-copy semantics |
| Null → null | Identity |
| Null ↔ other families | Rejected until nullable-type work |
| Numeric/bool ↔ string | Deferred; text conversion needs its own contract |

This derives byte↔float and bool↔byte conversions from the families, rather than
preserving the old special exclusions. Future smaller floating formats need concrete
rounding/nonfinite and finite-overflow edge proofs before activation. No particular
new format or wider-than-64-bit representation is activated by this agreement.

## Agreed context permissions

| Context | Permission |
| --- | --- |
| Explicit cast | Defined conversions, with required checks |
| Typed initialization, value arguments, literal defaults, returns | Defined conversions; destination supplies intent |
| Ordinary assignment | Same canonical type only for now |
| Inferred local | Preserve initializer identity; bare null remains rejected |
| Reference binding | Exact canonical identity; aliases are identical |
| Operator operands | Operator-family promotion policy, not general cast permission |
| Compound assignment | Explicit operation and destination/write-back policy |
| Conditions | Separate truthiness policy |

The inferred-local rule does not silently change existing global-literal inference.
The default-value context does not expand the supported default-expression grammar.
General conversions do not automatically authorize bool arithmetic, null arithmetic,
string coercion or mixed narrowing compounds. Built-in overload selection should
consume these policies; user-defined overloads and conversion ranking are deferred.

## Implementation slices

Slices 1–2, active integer arithmetic/comparison/compound groups and [mixed numeric families](../../compiler/docs/mixed_numeric_families.md) are implemented. Remaining groups require agreement.

1. **Family/representation foundation and byte migration.** Give canonical definitions
   truthful family/representation ownership; derive byte/uint8 identity from runtime
   metadata and remove byte-only restrictions. Reconcile default/module naming and
   runtime ABI/bindings. Prove alias identity, checked conversions, existing integer
   operation groups, reference binding and incremental invalidation/recovery. Do not
   activate every optional numeric width or invent new operator families in this slice.
2. **Family-derived conversion generation.** Generate conversion admissions/checks for
   active scalar representations from the agreed table and context permissions.
   Consolidate existing rows rather than add a second matrix. Prove all supported
   family directions and rejection boundaries; ordinary assignment stays same-type.
3. **Grouped operator policy.** Agree arithmetic/comparison/logical/bitwise/shift and
   string-family tables: operand admission, execution/result types, overflow/zero/
   shift failures. Generate handlers in groups. Derive compounds/updates with explicit
   write-back permission; no blanket implicit narrowing or new expression forms.
4. **Optional representations.** Activate agreed integer widths and then floating
   formats through module metadata, using the same machinery. Prove signed/unsigned
   domain boundaries and storage-versus-execution precision, without PHP host limits
   becoming language rules.

Before each slice, review its touched owners and concrete proof set. If byte aliasing
requires a broad cross-owner ABI redesign, report the boundary and agree the smallest
migration before coding. Use all three flavors, incremental errors/retry/recovery
against fresh compilation, and Clang frontend/LLVM/link timings. Preserve preparation's
single traversal, stable identities and native ownership/join boundaries.
