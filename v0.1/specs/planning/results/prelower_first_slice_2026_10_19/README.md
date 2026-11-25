# First concrete integer/vector slice
Doc Status: supporting

Date: 2026-10-19. The [coverage guide](../../../../compiler/my-try/docs/architecture/cpp_template_prelowering.md)
is the implementation handoff. [Raw trial data and results](summary.json) distinguish
this small production-backend fixture from the earlier whole-accounting experiment.

## Result

Clang 18.1.3, Linux x86_64, C++23, `-g0`, no PCH in either path. Median of three
serial fresh-object compilations; variant order shuffled per trial. Filesystem/tool
caches are not flushed. This is an indicative local measurement, not a controlled
machine-wide benchmark or a universal C++ speed ratio.

| Native work | Existing O0 | Concrete O0 | Existing O2 | Concrete O2 |
|---|---:|---:|---:|---:|
| Application units | 4.767 s | 0.854 s | 5.767 s | 0.917 s |
| Concrete support units | — | 1.957 s | — | 3.017 s |
| All generated-unit compilation | 4.767 s | 2.806 s | 5.767 s | 3.947 s |
| Linking | 0.164 s | 0.144 s | 0.142 s | 0.141 s |

Rows are separately computed medians; they need not sum exactly. Both variants
execute the same source checks. The existing path compiles runtime headers into
its application units; the concrete path moves CLI/error/container implementation
work to two support units. Both use the production error contract. Shared runtime
provider objects are excluded from these manually compiled fixture timings, as are
lowering and native-builder dependency scanning. The separate native-cache proof
below uses the production builder and its provider membership.

Application compilation is about 5.6x / 6.3x faster. Including generated support
compilation, the improvement is about 1.7x / 1.5x. Runtime throughput has not been
benchmarked. The full accounting application needs additional family recipes.

Preprocessed application implementation: 3,587,412 bytes / 6,950 `template<` tokens
on the existing path; 1,641,069 bytes / 3,736 on the concrete path. Token counts are
an exposure indicator, not template-instantiation counts. The concrete sample still
includes `bool_t`/detail machinery; it is not fully template-free after preprocessing.
Generated integer/vector bindings themselves contain neither `scpp::int_t<...>` nor
`scpp::vector_t<...>`, and application code no longer includes the generated operator
umbrella. Remaining runtime exposures belong in the early family review.

## Proofs

- All eight signed/unsigned integer widths through one recipe; concrete storage-size
  assertions and native min/max values; checked narrowing and widening conversions.
- One vector recipe: bool, every integer width, nested vectors, growth, independent
  copies, self-assignment, move, clear and append aliasing across reallocation.
- Separately compiled source records with identical short names in different
  namespaces, nested record vectors and independent copies; both backend modes pass.
- Identical bounds, division-by-zero, modulo-by-zero and signed/unsigned checked-cast
  errors: messages, codes, component/operator and ordered details. Trace addresses
  are deliberately excluded.
- Same-instance flag switching preserves semantic work versions. No-op generation
  preserves fragment identity and support bytes; body edits retain support. Request
  removal, failed generation followed by repair and reversed discovery order pass.
- Existing default incremental C++ regression passes.
- Concrete generated program passes ASan/UBSan (`detect_leaks=0`, UB halts).
- Production native builder, no PCH: cold build compiles seven objects; unchanged
  build reuses all seven and does not link; body-only edit compiles one, reuses six
  and links. Each executable passes.
- Portable-PHP checks and conversion pass for the C++ backend; the shared type-query
  directory converts. This does not claim a native rebuild of the compiler itself.

## Reproduction

```bash
python3 compiler/my-try/tests/prelower_templates.py --results /absolute/fresh-results
```

The runner retains source, generated artifacts, native commands and logs. The main
measurement/proof run was `/tmp/prelower-integration-20261019-7`; the additional
source-record proof was `/tmp/prelower-records-20261019` and is now part of the same
retained runner. Final consolidation checks in `/tmp/prelower-final-20261019` passed,
including exact artifact agreement with the measured version, record execution,
lifecycle, portability checks and conversion. PHP-FPM socket creation was unavailable in the sandbox; the shared
runner used its normal PHP CLI fallback. Native C++ ran normally.

Scope remains integer/vector specialization and its supported operation adapters.
Hash, ownership, wrappers, provider ABI bridges, selective methods and additional
operations remain follow-up work in the [plan](../../cpp_template_prelowering_plan_2026_10_19.md).
