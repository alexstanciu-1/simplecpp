# uint64 constant evaluation in the PHP bootstrap

Doc Status: planning

Date: 2026-11-14.

## Agreed direction and deferred work

Support native uint64 without requiring PHP to evaluate values above PHP_INT_MAX.
This is a constant-evaluation limitation, not permission to emit incorrect arithmetic,
round through a PHP float, reinterpret a negative PHP integer as a positive fact, or
prove an unsafe conversion safe. Complete uint64 constant evaluation is deferred to v0.3.
This document does not claim uint64 source support is already implemented.

- Keep ordinary representable scalar facts as PHP integers.
- An upper-range uint64 result may have a known type and an unknown constant value.
- Generate native arithmetic and preserve the existing overflow/conversion contracts.
- Retain runtime checked narrowing when preparation cannot prove a conversion safe.
- Exact upper bounds may be decimal text used during generation, not PHP numerical facts.
- Exact mixed signed/unsigned comparisons and validation before narrowing shift counts
  remain correctness requirements. They are not deferred with constant evaluation.
- Full-range unsigned source literal syntax is a separate decision. Ordinary integer
  literals retain their current int64 identity and range until that decision is made.

## Inspection of current constant propagation

The AST already distinguishes a known null from an unknown value using a scalar payload
and constant_known. It can represent unknown uint64 without adding an AST field or
two-limb integer object. Conversion callers currently derive knownness from the source,
however, so returning null from an unevaluated conversion alone is unsafe.

| Owner | Current assumption | Needed boundary |
| --- | --- | --- |
| ast_cast_expression::settle_cast | Source known plus destination exclusions implies result known | Conversion must report whether it evaluated the result |
| Preparation::declaration | Source known plus carrier/boxed exclusions implies result known | Store the converted payload only with its actual knownness |
| ast_constant_declaration | Payload is converted on demand, knownness follows initializer | Derived knownness must honor unavailable evaluation |
| ast_assignment_statement | Plain assignment forwards source knownness with type exclusions | Converted writes must propagate unavailable facts |
| ast_name_expression | Copies occurrence facts and invalidates uncertain reads | Existing mechanism can carry an unknown result |
| ast_expression / ast_local_declaration | Non-null payload automatically means known | An unavailable conversion must supply null and false, never a placeholder |
| Generated scalar conversions | Host casts/comparisons assume representable numerical payloads | Do not execute host numerical operations on upper-range values |

Relevant sources: [cast](../../compiler/03_parse/structures/expressions.php),
[declaration preparation](../../compiler/04_analyze/prepare/prepare.php),
[constants](../../compiler/03_parse/structures/constants.php),
[assignments and references](../../compiler/03_parse/structures/common.php),
[fact storage](../../compiler/03_parse/structures/abstract.php),
[conversion owner](../../compiler/types/definition.php), and
[conversion generation](../../tools/api_generator/php/render_compiler.php).

## Implemented evaluation boundary

Conversion evaluation now reports unavailable through the singleton constant_evaluation::unavailable.
The shared type_definition::evaluate_constant method preserves source unknownness before conversion.
Callers normalize unavailable to the existing null payload plus constant_known=false. Known null
remains distinct. Numerical policy stays in generated type handlers, without uint64 branches in AST
consumers or per-conversion result objects. Source admission and uint64 activation are unchanged.

Casts, local declarations, named constants and plain assignments consume the explicit outcome.
A test-only destination exercises unavailable evaluation through those consumers, references and
reset/re-preparation, alongside known null, false and zero. Existing all-flavor conversion and
incremental recovery suites verify production paths. Dependencies and watcher ownership are unchanged.

After that proof, activate optional widths through module selection and generated
promotion/conversion rules in a separate slice. Uint64 upper-range conversions use
the unavailable result where exact host evaluation is absent, retaining native checks.
Do not weaken source-required constant-expression contexts merely by marking a fact
unknown; review such contexts when enabling the type.

## Optional-width activation boundary

The host-representable widths are enabled through runtime-numeric. Different unsigned
representations still require uint64 under the agreed promotion policy. The user selected
temporary rejection of common-domain operations between those widths rather than changing
the table or activating uint64 in the same slice. Remove that omission when uint64 execution,
checked conversions, exact comparisons and shift transport are implemented and verified.
