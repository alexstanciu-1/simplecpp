# Native scalar alias ABI

Doc Status: supporting
Date: 2026-11-06
Status: alias migration slice 3 implemented

## Representation and contracts

Published integers use exact target-probed builtin aliases (`__INT16_TYPE__`,
`__UINT16_TYPE__`, and the equivalent 8/32/64-bit types). float64 aliases double;
bool_t aliases bool. byte aliases uint8. Runtime and generated application C++ keep
explicit scpp type names. These aliases have native C++ identity and behavior;
construction and builtin arithmetic do not enforce Simple C++ conversions or
arithmetic rules. Compiler-generated handlers retain source admission, promotion,
checked arithmetic and conversion semantics. Optional runtime widths are not newly
activated in the source compiler.

String, vectors, hashes, carriers, ownership handles and null remain concrete
library types. Runtime null_t retains its explicit default constructor and sentinel;
compiler null lowering remains decltype(nullptr). Scalar fields requiring zero must
use value initialization (`{}`); error_t's line field now does this explicitly.

Named checked conversions reuse the existing private algorithms, including published
string parsers. String formatting stays in string constructors. Metadata construction
recipes now feed checked conversion generation, not scalar constructor declarations.
The old native_bridge header and build option, scalar storage templates, scalar
extraction adapters and overloaded scalar arithmetic are retired. This is an ABI
break: regenerate headers and rebuild the runtime and all consumers together.
Do not link an old wrapper archive against alias headers.

## Scalar declaration placement

Core scalar declarations now live in metadata provider `core.scalars`, default
`scpp/core/scalars.hpp`; optional widths live in `numeric.scalars`, default
`scpp/numeric/scalars.hpp`. Published aliases follow their target. Core-only and
optional-width-only publications do not pull an unselected scalar provider.
Checked functions remain in separate `core.conversions`, with signature-derived
dependencies. `integers.hpp` and `values.hpp` are retired; regenerate and rebuild
consumers together. This consolidation changes no native representation or admission.

## Strict library inputs and carriers

A shared generator helper uses target native identity to deduplicate overloads and
emit deleted concrete overloads for other builtin scalar inputs, nullptr and object
pointers. This preserves strict scalar filtering on vectors and affected library
APIs, including const receivers. Existing explicitly native adapter parameters keep
their previous native policy. Equal width alone does not establish native identity.
No public templates or generic user-defined conversion prohibition are introduced.

Vectors store the metadata-selected alias directly. Public reads return values;
private bool-vector proxies are normalized before checked conversion. Vector casts
call named checked helpers and retain failure atomicity. Numeric int adapters reuse
the existing conversion recipe rather than assuming every destination is integer.

The approved carrier policy retains implicit checked payload extraction and allows
subsequent native conversions and truthiness. `if (carrier)` tests payload truth,
not presence; `has_value()` tests presence. Empty/error/sentinel extraction still
throws. A false payload remains distinct from a false sentinel. Boxing remains
explicit with strict inputs; no cross-carrier conversion is added. Source-language
carrier integration remains deferred.

## Structure changes

- New generator helper `php/scalar_abi.php` owns native identity, input fences and
  native adapter recipe selection; temporary scalar plans retain native_name.
- Runtime type_exports and submodule providers select scalar names and public headers;
  the temporary compiler.scalar_headers mapping was removed during integration.
  Generated type plans carry cpp_header; emitted numeric types use scpp aliases. Expression
  emission collects its resolved type's headers, including bool lambda results.
- The scalar_bool binding replaces boxed_bool and has no extraction accessor.
- Checked conversion publication includes selected string parsing rows; emission
  follows private string storage definitions.
- Publication inventory version is 3; compiler generated inventory version is 19.
  Cleanup removes retired generated outputs, including native_bridge.hpp.
- No AST, prepared-fact, watcher, session or incremental ownership structures change.

## Verification

All three source flavors passed 105 native executable fixtures across scalar,
floating, family conversion, integer compound and string equality tools, plus
boundary drivers. Incremental edits, rejection/retry, deletion and recovery were
compared against fresh compilation. PHP compiler tests and frontend-service checks
passed. Runtime consumers cover scalar boundaries, all integer representations,
string, vector casts/filtering, carriers, boxed values/results, hashes, JSON,
ownership and datetime. Public closure checks remain template-free.

Publication captures retain 130 accepted/247 rejected runtime cases, 19 accepted/
537 rejected scalar cases and 17 accepted/37 rejected CLI cases. Scalar planning
still validates 2,692 operation recipes; that count does not mean emitted scalar
overloads. ABI snapshots change intentionally and require coordinated regeneration.

Representative instrumented builds, ranges across three flavors, milliseconds:

| Fixture | Native wall | Clang frontend | LLVM backend | Link command |
| --- | ---: | ---: | ---: | ---: |
| Scalars | 116.3–121.2 | 11.6–12.4 | 5.2–5.9 | 56–60 |
| Floating | 130.5–139.6 | 39.5–44.0 | 11.1–12.7 | 55 |
| Family conversions | 127.4–134.3 | 16.7–17.5 | 14.6–14.8 | 54–58 |
| Integer compounds | 133.5–136.6 | 17.3–18.1 | 19.4–19.8 | 54–56 |
| String equality | 207.3–260.6 | 39.1–45.9 | 69.6–103.0 | 56–66 |

O2/g0, libc++, Ninja8, mold, no PCH/LTO, fresh app objects and prebuilt runtime.
One sample per fixture/flavor: no speedup conclusion. Frontend/backend values sum
translation-unit traces; link includes driver overhead and phase totals need not
sum to build wall time. Raw results are retained under
`/tmp/scpp-alias-{scalars,floating,family,compounds,string}/`.
