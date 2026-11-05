# Discarded-value numeric updates

Doc Status: supporting

The [scalar contract](../../../specs/compiler_scalars_v0_2.md#discarded-value-int64-updates--2026-11-05)
admits all four prefix/postfix increment/decrement spellings on established int64
locals and parameters, including reference parameters. Standalone statements and
both for header write positions are supported in all flavors. Expression results,
constants/direct globals and complex targets remain deferred.
[Float64 updates](float_updates.md) now reuse this same path.

## Owners and preparation

`ast_storage_write` extracts the existing destination name and prepared non-owning
storage binding from `ast_assignment_statement`. It owns binding readiness/reset;
assignment still owns its RHS, optional binary operator and conversion decision.
`ast_update_statement` adds only `update_operator` direction and a postfix flag.
It has no fake literal, RHS, name-expression child or prepared result value.

For initializer/update slots now accept the common write base. The shared parser
recognizes updates only at those boundaries and ordinary statement boundaries.
Collection/reset/inspection skip nonexistent RHS expressions while preserving the
common source spans, lexical scopes and destination binding. No duplicate symbol
index or watcher category is introduced.

`Preparation::write()` resolves established storage once for both assignments and
updates. Update admission calls the generated capability handler and publishes its
binding only after success. Latest-value knowledge becomes unknown after mutation,
using the existing body-local index; a later narrowing conversion cannot reuse a
stale literal fact. Reference aliases retain existing conservative handling. There
is no additional traversal or expression visit for an update without an RHS.

## Metadata and native lowering

`compiler.updates.int64` explicitly lists increment/decrement with native lowering
and overflow policy. The API generator emits `supports_update()` and
`lower_cpp_update()` on the canonical int64 definition; `compiler.updates.float64` now generates the
same handlers for float64. Other definitions inherit rejection. Removing metadata capability removes generated admission. Invalid type,
operator and policy rows reject during generation. Compiler inventory version is 12.

Lowering uses the resolved storage once, emitting native `++local_N`, `local_N++`,
`--local_N` or `local_N--`. No result is consumed, so no snapshot, lambda, runtime
helper or new public header is needed. Mixed compound/update headers retain snapshot
storage only for their compound part. Existing signed-overflow limitations remain.

## Mining and validation

Reviewed donor `operators/mutations.php`, its increment/decrement contract and the
normative expression-evaluation snapshot rules. Retained established writable
storage, generated operator admission and source spelling intent. Did not adopt
the donor's separate prepared mutation record or enable value-producing updates.

Tests cover all four forms and all flavors, statement/header positions, local/value/
reference parameters, nested loops and body shadowing, continue/break/return/false
conditions, mixed compound headers, no synthetic expression visits, reset/purity,
inspection and invalid targets/types. Byte conversions after increment/decrement
verify stale facts cannot bypass checks. Incremental tests exercise return-type
failure/recovery without replacing the caller AST/tokens, body-only reuse, same-size
operator edits with a changed mtime, and no-op reuse. Native recovery uses a fresh
multifile preparation; incremental native emission remains deferred.


## Recorded proof — 2026-11-05

All 47 selected compiler CTest checks passed (`^compiler_`, excluding native suites
and the persistent-service harness); writing checks passed for 116 PHP files.
Twelve update native programs, eighteen for regressions and three assignment native
regressions passed. Generated compiler/runtime freshness checks pass; the runtime
publication is unchanged. Raw fixtures, generated code, traces and logs are retained
locally under `v0.2/build/updates-proof-2026-11-05/` (ignored build output).

Final review detected `prepare.php` reverted exactly to its committed content after
initial validation. The writer was not identified. The intended update handling was
restored, then all 47 selected checks and all 12 update native programs passed again;
a file checksum remained stable through final verification. The table uses that final
native run. No API retry or network-delay claim is made from this event.

Single instrumented fresh builds: O2/g0, libc++, Ninja8, mold, no PCH/LTO, prebuilt
runtime. These are not medians. Trace durations are not additive wall time;
optimizer/codegen are parts of LLVM work, and link measures command duration.

| Flavor | Native wall ms | Frontend ms | LLVM backend ms | Optimizer ms | Codegen ms | Link ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| cpp | 200.48 | 17.24 | 23.82 | 13.32 | 7.64 | 79.00 |
| php | 156.71 | 12.78 | 15.56 | 8.73 | 4.96 | 73.00 |
| js | 453.84 | 45.18 | 74.52 | 49.76 | 20.47 | 178.00 |

PHP fixture generation and assertions took 125.38 ms across all update cases,
including incremental edits. This is not an isolated preparation-stage timing.
