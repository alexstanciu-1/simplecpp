# Runtime-core public conversion coverage
Doc Status: planning

Audit date: 2026-10-27. This is implementation evidence and backlog routing, not
semantic authority. Authorities: `runtime/specs/config.json`,
`specs/dynamic_types.md`, `specs/compact_layout_types.md`, and the accepted carrier
policy in `runtime_wrapper_policy.md`.

## Scalar scope (A2)

| Obligation | Public owner / evidence |
| --- | --- |
| Nine numeric types to numeric destinations | `conversions` metadata; sources normalize losslessly to int64/uint64/float64, targets use checked native casts; `test_public_conversions` |
| Strict string parsing to numeric types | Same registry; full uint64 range; finite decimal float parsing; native boundary tests and public conversion tests |
| Numeric and string to boolean | Same registry; string literal whitelist, separate from truthiness; `test_scalar_conversion_surface` |
| Boolean to numeric widths | Same registry; exact zero/one; `test_boxed_integer_casts` |
| Scalar string results and null exception | Same registry; native return transport; boolean false/null become empty string; owned result lifetime/allocation tests |
| Mixed/dynamic to scalar | Same registry; preserve payload origin then checked target cast; reject null/container numeric/bool casts; origin and scalar-surface tests |
| Canonical scalar boxing into mixed/dynamic | Existing generated family constructors in public mixed/dynamic headers; metadata declares each signature; `test_public_dynamic`, mixed/scalar conversion tests |
| Mixed/dynamic explicit family crossing | Existing generated `to_dynamic` / `to_mixed`; preserve scalar/null kind, reject containers before destination replacement; `test_public_dynamic` |
| Scalar copy/identity | Generated lifecycle for boxed values; concrete scalar copy; identity named cast where configured (string), plus explicit scalar conversion APIs |
| Source bool/native bool bridge | Public boolean explicit native conversion; an implementation bridge rather than an additional source scalar type |

`check_conversion_coverage.py` checks all 17 distinct canonical scalar cast pairs
from config against the conversion registry and publication records, plus five
configured boxing pairs, symmetric dynamic boxing and the two existing family
functions. It checks coverage, not value semantics. Native/public executable tests
provide the separate boundary, ownership and allocation evidence. The fixed-width
origin rule is recorded in the top-level specs after the user's decision.

Scalar boxing remains restricted to representable canonical payloads. No uint64
storage is introduced into a box; casting a boxed string to uint64 produces a typed
uint64 result, not a new boxed payload kind. Boxing is constructor metadata, not a
duplicate global cast adapter. No frontend integration is claimed.

## Deferred conversion owners (not omissions from scalar A2)

| Contract family | Backlog owner |
| --- | --- |
| Nullable/result/result_or_false/result_or_bool checked extraction, state-preserving conversion and string exceptions | C |
| Mixed/dynamic to nullable and nullable to typed destination | C, one-layer policy |
| shared/unique/weak ancestry/absence conversions, shared/weak mixed table handles | D with E table payload publication |
| `value_p`, `ref_p` construction/reference boundaries | D, source admission and lifetime contracts |
| Container payload conversions and structural typed extraction | E; do not infer recursive family conversion |
| Implicit numeric source admission and prepared lowering | Existing language contracts; full frontend adoption excluded from this goal |

A2 closure requires its focused executable and metadata checks to pass and its
publication to be committed; this inventory does not close any deferred batch or
prove all of runtime-core complete. The first complete carrier path remains a
review gate before expanding ownership/callback work.
