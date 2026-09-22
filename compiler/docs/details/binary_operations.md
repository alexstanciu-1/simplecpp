# Binary operations and boolean results
Doc Status: supporting

Status: implemented after the approved coordinated migration, as the prerequisite
for the [growing source list](source_list_plan.md). Integer arithmetic belongs to
the compiler; this slice does not add a runtime operator bridge.

## Contracts

The language catalog grants integer definitions `addition: wrapping` and/or
`comparison: ordered`. Exact matching of both operand types is required. `<`
selects signed or unsigned ordering from that definition; it does not insert
mixed-width or mixed-signedness conversions. The optional `literal_types.boolean`
role names the result type and the type of `true`/`false`. Its representation is
an unsigned one-bit integer. Catalogs without that role remain valid but cannot
select comparison or boolean literals.

The default role names `bool`; consumers do not recognize that spelling. The role
has no numeric-conversion family, addition or ordering permission in the default
catalog. Scalar locals, source parameters/results, copying and condition checks
use existing type contracts. Runtime boolean ABI import is separate.

## Owners and flow

Paths are relative to `src/`.

| Owner | Responsibility |
|---|---|
| `03_parse/utilities/binary_syntax.php` / `Binary_Syntax` | Operator syntax, precedence and a linear, file-private angle-pair index. |
| `03_parse/handlers/expressions.php` / `Expression_Parsing` | Iterative postfix/binary reduction, preserving ordered operands in the existing flat AST. |
| `01_prepare_inputs/load_runtime/` and `04_analyze/type_model/` | Validate and retain declared capabilities and the boolean role; runtime catalog composition preserves it. |
| `04_analyze/check_bodies/utilities/operations.php` / `Operation_Resolver` | Select an operation contract with operand types, independent result type and native implementation. The body-private cache includes operator and operand IDs. |
| `05_generate_code/lower/binary_operations.php` / `Binary_Operations` | Validate the selected contract and carry its native primitive in `binary_operands`. |
| `05_generate_code/emit_llvm/handlers/instructions.php` / `Instruction_Emission` | Emit wrapping `add`, `icmp slt` or `icmp ult` from the accepted primitive and representations. |

Addition binds tighter than `<`; both associate left to right. Postfix access and
calls bind first. Operand evaluation and lifetime consumption remain left to right
through the existing checked-value and lowering traversal. Comparison produces an
`i1` result independently from its integer operand width.

Type positions retain template argument syntax. In value positions, a bare name
followed by balanced angle brackets is parsed as a template application; an
unmatched `<` is comparison. Angle pairing respects parenthesis/bracket scopes and
statement boundaries and performs no symbol lookup. In template arguments, group a
comparison involving a bare name, e.g. `family<(N < 3)>`. Preserving that syntax does
not enable constant evaluation. `>` and other new operators remain unsupported.

No additional stage, coordinator path or mutable shared cache is introduced.
Workers read fixed catalog/type inputs and produce private bodies/instructions;
existing joins accept them. Operator changes participate in AST equality and
normal body-edit invalidation. Catalog changes retain the existing rebuild policy.

## Proof

[Integer comparison tests](../../tests/features/integer_comparisons.php)
cover precedence, signed/unsigned boundaries, equal values, boolean literals/locals/fields
and source calls, left-to-right mutations, named constants beside template calls,
nested/grouped syntax, 2,000-level groups/chains, malformed operands, renamed
boolean metadata, a new 17-bit integer, malformed result roles and denied capabilities. They also prove reordered/missing/
duplicate body results, old-snapshot purity and one native body increment.

The existing addition and template regressions share the migrated path. The
growing-list proof exercises comparisons in real storage-copy/cleanup loops at
normal settings and O1/ThinLTO. No list type, element name or expected result is
recognized by production code.
